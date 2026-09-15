package com.smartwallet.data

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.withContext
import java.util.UUID

class SmartWalletRepository(private val db: SmartWalletDatabase) {

    private val userDao = db.userDao()
    private val walletDao = db.walletDao()
    private val transactionDao = db.transactionDao()
    private val transferDao = db.transferDao()
    private val depositDao = db.depositRequestDao()
    private val withdrawalDao = db.withdrawalRequestDao()
    private val paymentDao = db.paymentDao()
    private val serviceDao = db.serviceRequestDao()
    private val notificationDao = db.notificationDao()
    private val auditLogDao = db.auditLogDao()

    fun getUser(userId: Long): Flow<UserEntity?> = userDao.getUserById(userId)
    fun getAllUsers(): Flow<List<UserEntity>> = userDao.getAllUsers()
    fun getWallet(userId: Long): Flow<WalletEntity?> = walletDao.getWalletByUserId(userId)
    fun getTransactions(walletId: Long): Flow<List<TransactionEntity>> = transactionDao.getTransactionsForWallet(walletId)
    fun getAllTransactions(): Flow<List<TransactionEntity>> = transactionDao.getAllTransactions()
    fun getNotifications(userId: Long): Flow<List<NotificationEntity>> = notificationDao.getNotificationsForUser(userId)
    fun getAllDeposits(): Flow<List<DepositRequestEntity>> = depositDao.getAllDeposits()
    fun getDepositsForUser(userId: Long): Flow<List<DepositRequestEntity>> = depositDao.getDepositsForUser(userId)
    fun getAllWithdrawals(): Flow<List<WithdrawalRequestEntity>> = withdrawalDao.getAllWithdrawals()
    fun getWithdrawalsForUser(userId: Long): Flow<List<WithdrawalRequestEntity>> = withdrawalDao.getWithdrawalsForUser(userId)
    fun getAllServices(): Flow<List<ServiceRequestEntity>> = serviceDao.getAllServices()
    fun getServicesForUser(userId: Long): Flow<List<ServiceRequestEntity>> = serviceDao.getServicesForUser(userId)
    fun getAllAuditLogs(): Flow<List<AuditLogEntity>> = auditLogDao.getAllLogs()
    fun getUserCount(): Flow<Int> = userDao.getUserCount()
    fun getActiveUserCount(): Flow<Int> = userDao.getActiveUserCount()
    fun getTotalCirculatingBalance(): Flow<Double?> = walletDao.getTotalCirculatingBalance()
    fun getPendingDepositCount(): Flow<Int> = depositDao.getPendingDepositCount()
    fun getPendingWithdrawalCount(): Flow<Int> = withdrawalDao.getPendingWithdrawalCount()

    suspend fun markNotificationAsRead(id: Long) = withContext(Dispatchers.IO) {
        notificationDao.markAsRead(id)
    }

    suspend fun markAllNotificationsAsRead(userId: Long) = withContext(Dispatchers.IO) {
        notificationDao.markAllAsRead(userId)
    }

    // 1. Transfer Money (تحويل الأموال)
    suspend fun transfer(
        senderUserId: Long,
        receiverPhone: String,
        amount: Double,
        note: String?
    ): Result<String> = withContext(Dispatchers.IO) {
        if (amount <= 0) return@withContext Result.failure(IllegalArgumentException("يرجى إدخال مبلغ صحيح."))

        val senderWallet = walletDao.getWalletByUserIdSync(senderUserId)
            ?: return@withContext Result.failure(IllegalStateException("محفظتك غير موجودة."))

        if (senderWallet.balance < amount) {
            return@withContext Result.failure(IllegalStateException("الرصيد غير كافٍ لإتمام التحويل."))
        }

        val cleanedPhone = receiverPhone.trim().replace("\\s+".toRegex(), "")
        val receiverUser = userDao.getUserByPhone(cleanedPhone)
            ?: return@withContext Result.failure(IllegalArgumentException("لم يتم العثور على مستخدم مسجل برقم الهاتف هذا."))

        if (receiverUser.id == senderUserId) {
            return@withContext Result.failure(IllegalArgumentException("لا يمكنك إرسال الأموال إلى نفسك."))
        }

        if (receiverUser.status != "active") {
            return@withContext Result.failure(IllegalStateException("حساب المستلم غير نشط."))
        }

        val receiverWallet = walletDao.getWalletByUserIdSync(receiverUser.id)
            ?: return@withContext Result.failure(IllegalStateException("محفظة المستلم غير مهيأة."))

        val reference = "TRF-" + UUID.randomUUID().toString().take(8).uppercase()

        // Deduct sender & Credit receiver
        walletDao.deductBalance(senderWallet.id, amount)
        walletDao.addBalance(receiverWallet.id, amount)

        // Save transfer record
        transferDao.insertTransfer(
            TransferEntity(
                senderWalletId = senderWallet.id,
                receiverWalletId = receiverWallet.id,
                senderPhone = senderWallet.id.toString(), // or user phone
                receiverPhone = receiverUser.phone,
                receiverName = receiverUser.fullName,
                amount = amount,
                reference = reference,
                note = note
            )
        )

        // Transaction for Sender
        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = senderWallet.id,
                type = "transfer_out",
                amount = amount,
                reference = reference,
                description = "تحويل صادر إلى ${receiverUser.fullName} (${receiverUser.phone})",
                status = "completed"
            )
        )

        // Transaction for Receiver
        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = receiverWallet.id,
                type = "transfer_in",
                amount = amount,
                reference = reference + "-IN",
                description = "تحويل وارد من محفظة SmartWallet",
                status = "completed"
            )
        )

        // Notification for sender
        notificationDao.insertNotification(
            NotificationEntity(
                userId = senderUserId,
                title = "تم التحويل بنجاح",
                message = "تم إرسال ${String.format("%,.2f", amount)} SDG إلى ${receiverUser.fullName} بنجاح. مرجع العملية: $reference",
                type = "success"
            )
        )

        // Notification for receiver
        notificationDao.insertNotification(
            NotificationEntity(
                userId = receiverUser.id,
                title = "استلام أموال",
                message = "تم إيداع ${String.format("%,.2f", amount)} SDG في محفظتك عبر تحويل مباشر.",
                type = "info"
            )
        )

        Result.success(reference)
    }

    // 2. Instant Demo Deposit (إيداع تجريبي فوري)
    suspend fun instantDeposit(userId: Long, amount: Double): Result<String> = withContext(Dispatchers.IO) {
        if (amount <= 0) return@withContext Result.failure(IllegalArgumentException("المبلغ غير صحيح."))
        val wallet = walletDao.getWalletByUserIdSync(userId)
            ?: return@withContext Result.failure(IllegalStateException("المحفظة غير موجودة."))

        val reference = "DEP-INST-" + UUID.randomUUID().toString().take(8).uppercase()
        walletDao.addBalance(wallet.id, amount)

        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = wallet.id,
                type = "deposit",
                amount = amount,
                reference = reference,
                description = "إيداع رصيد فوري مباشر (تجريبي)",
                status = "completed"
            )
        )

        notificationDao.insertNotification(
            NotificationEntity(
                userId = userId,
                title = "إيداع ناجح",
                message = "تمت إضافة ${String.format("%,.2f", amount)} SDG إلى رصيدك. مرجع العملية: $reference",
                type = "success"
            )
        )

        Result.success(reference)
    }

    // 3. Bank Deposit Request (طلب إيداع بنكي)
    suspend fun submitBankDeposit(
        userId: Long,
        bankName: String,
        senderName: String,
        bankReference: String,
        amount: Double,
        note: String?
    ): Result<String> = withContext(Dispatchers.IO) {
        if (amount <= 0) return@withContext Result.failure(IllegalArgumentException("المبلغ غير صحيح."))
        val wallet = walletDao.getWalletByUserIdSync(userId)
            ?: return@withContext Result.failure(IllegalStateException("المحفظة غير موجودة."))

        val reference = "DEP-REQ-" + UUID.randomUUID().toString().take(8).uppercase()
        depositDao.insertDeposit(
            DepositRequestEntity(
                userId = userId,
                walletId = wallet.id,
                amount = amount,
                bankName = bankName,
                senderName = senderName,
                bankReference = bankReference,
                note = note,
                status = "pending"
            )
        )

        notificationDao.insertNotification(
            NotificationEntity(
                userId = userId,
                title = "طلب إيداع بنكي",
                message = "تم استلام طلب إيداع $bankName بمبلغ ${String.format("%,.2f", amount)} SDG. الطلب قيد مراجعة المشرف.",
                type = "info"
            )
        )

        Result.success(reference)
    }

    // 4. Withdrawal Request (طلب سحب أموال)
    suspend fun submitWithdrawal(
        userId: Long,
        amount: Double,
        recipientName: String,
        recipientPhone: String,
        method: String,
        note: String?
    ): Result<String> = withContext(Dispatchers.IO) {
        if (amount <= 0) return@withContext Result.failure(IllegalArgumentException("المبلغ غير صحيح."))
        val wallet = walletDao.getWalletByUserIdSync(userId)
            ?: return@withContext Result.failure(IllegalStateException("المحفظة غير موجودة."))

        if (wallet.balance < amount) {
            return@withContext Result.failure(IllegalStateException("رصيدك الحالي لا يكفي لإتمام طلب السحب."))
        }

        val reference = "WTH-REQ-" + UUID.randomUUID().toString().take(8).uppercase()
        withdrawalDao.insertWithdrawal(
            WithdrawalRequestEntity(
                userId = userId,
                walletId = wallet.id,
                amount = amount,
                recipientName = recipientName,
                recipientPhone = recipientPhone,
                withdrawalMethod = method,
                note = note,
                status = "pending"
            )
        )

        notificationDao.insertNotification(
            NotificationEntity(
                userId = userId,
                title = "طلب سحب قيد المعالجة",
                message = "تم تسجيل طلب سحب بمبلغ ${String.format("%,.2f", amount)} SDG لصالح $recipientName.",
                type = "info"
            )
        )

        Result.success(reference)
    }

    // 5. Merchant Payment (دفع المشتريات / المدفوعات)
    suspend fun payMerchant(
        userId: Long,
        merchantName: String,
        merchantAccount: String,
        amount: Double
    ): Result<String> = withContext(Dispatchers.IO) {
        if (amount <= 0) return@withContext Result.failure(IllegalArgumentException("المبلغ غير صحيح."))
        val wallet = walletDao.getWalletByUserIdSync(userId)
            ?: return@withContext Result.failure(IllegalStateException("المحفظة غير موجودة."))

        if (wallet.balance < amount) {
            return@withContext Result.failure(IllegalStateException("الرصيد غير كافٍ لدفع الفاتورة."))
        }

        val reference = "PAY-" + UUID.randomUUID().toString().take(8).uppercase()
        walletDao.deductBalance(wallet.id, amount)

        paymentDao.insertPayment(
            PaymentEntity(
                userId = userId,
                merchantName = merchantName,
                merchantAccount = merchantAccount,
                amount = amount,
                reference = reference
            )
        )

        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = wallet.id,
                type = "payment",
                amount = amount,
                reference = reference,
                description = "دفع لـ $merchantName (حساب: $merchantAccount)",
                status = "completed"
            )
        )

        notificationDao.insertNotification(
            NotificationEntity(
                userId = userId,
                title = "دفع تاجر ناجح",
                message = "تم دفع ${String.format("%,.2f", amount)} SDG لـ $merchantName. رقم العملية: $reference",
                type = "success"
            )
        )

        Result.success(reference)
    }

    // 6. Utility Service Request (دفع الخدمات: شحن، كهرباء، إنترنت، فواتير، تعليم، حكومة)
    suspend fun payService(
        userId: Long,
        serviceType: String,
        serviceTitle: String,
        provider: String,
        targetIdentifier: String,
        amount: Double,
        fee: Double = 0.0
    ): Result<String> = withContext(Dispatchers.IO) {
        if (amount <= 0) return@withContext Result.failure(IllegalArgumentException("المبلغ غير صحيح."))
        val total = amount + fee
        val wallet = walletDao.getWalletByUserIdSync(userId)
            ?: return@withContext Result.failure(IllegalStateException("المحفظة غير موجودة."))

        if (wallet.balance < total) {
            return@withContext Result.failure(IllegalStateException("الرصيد غير كافٍ (المبلغ + الرسوم = ${String.format("%,.2f", total)} SDG)."))
        }

        val reference = "SRV-" + serviceType.take(3).uppercase() + "-" + UUID.randomUUID().toString().take(6).uppercase()
        walletDao.deductBalance(wallet.id, total)

        serviceDao.insertServiceRequest(
            ServiceRequestEntity(
                userId = userId,
                serviceType = serviceType,
                provider = provider,
                targetIdentifier = targetIdentifier,
                amount = amount,
                fee = fee,
                totalAmount = total,
                reference = reference,
                status = "completed",
                responseMessage = "تم تنفيذ خدمة $serviceTitle بنجاح للرقم/الحساب $targetIdentifier"
            )
        )

        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = wallet.id,
                type = serviceType,
                amount = total,
                reference = reference,
                description = "$serviceTitle ($provider) - $targetIdentifier",
                status = "completed"
            )
        )

        notificationDao.insertNotification(
            NotificationEntity(
                userId = userId,
                title = "خدمة مكتملة: $serviceTitle",
                message = "تم سداد $serviceTitle لـ $targetIdentifier بقيمة ${String.format("%,.2f", total)} SDG.",
                type = "success"
            )
        )

        Result.success(reference)
    }

    // 7. Admin approvals
    suspend fun approveDeposit(depositId: Long, adminId: Long): Result<Unit> = withContext(Dispatchers.IO) {
        val deposit = depositDao.getDepositById(depositId)
            ?: return@withContext Result.failure(IllegalArgumentException("الطلب غير موجود."))

        if (deposit.status != "pending") {
            return@withContext Result.failure(IllegalStateException("تمت مراجعة هذا الطلب مسبقاً."))
        }

        walletDao.addBalance(deposit.walletId, deposit.amount)
        depositDao.updateDeposit(
            deposit.copy(
                status = "approved",
                reviewedAt = System.currentTimeMillis(),
                reviewNote = "تمت الموافقة وتأكيد التحويل البنكي"
            )
        )

        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = deposit.walletId,
                type = "deposit",
                amount = deposit.amount,
                reference = deposit.bankReference.ifEmpty { "DEP-APR-$depositId" },
                description = "إيداع بنكي معتمد (${deposit.bankName})",
                status = "completed"
            )
        )

        notificationDao.insertNotification(
            NotificationEntity(
                userId = deposit.userId,
                title = "تمت الموافقة على الإيداع",
                message = "تمت إضافة مبلغ ${String.format("%,.2f", deposit.amount)} SDG إلى محفظتك.",
                type = "success"
            )
        )

        auditLogDao.insertLog(
            AuditLogEntity(
                adminId = adminId,
                action = "APPROVE_DEPOSIT",
                targetType = "deposit_requests",
                targetId = depositId,
                description = "الموافقة على إيداع بمبلغ ${deposit.amount} SDG بنك ${deposit.bankName}"
            )
        )

        Result.success(Unit)
    }

    suspend fun rejectDeposit(depositId: Long, adminId: Long, reason: String): Result<Unit> = withContext(Dispatchers.IO) {
        val deposit = depositDao.getDepositById(depositId)
            ?: return@withContext Result.failure(IllegalArgumentException("الطلب غير موجود."))

        depositDao.updateDeposit(
            deposit.copy(
                status = "rejected",
                reviewedAt = System.currentTimeMillis(),
                reviewNote = reason
            )
        )

        notificationDao.insertNotification(
            NotificationEntity(
                userId = deposit.userId,
                title = "رفض طلب الإيداع",
                message = "تم رفض طلب الإيداع: $reason",
                type = "warning"
            )
        )

        auditLogDao.insertLog(
            AuditLogEntity(
                adminId = adminId,
                action = "REJECT_DEPOSIT",
                targetType = "deposit_requests",
                targetId = depositId,
                description = "رفض طلب إيداع: $reason"
            )
        )

        Result.success(Unit)
    }

    suspend fun approveWithdrawal(withdrawalId: Long, adminId: Long): Result<Unit> = withContext(Dispatchers.IO) {
        val withdrawal = withdrawalDao.getWithdrawalById(withdrawalId)
            ?: return@withContext Result.failure(IllegalArgumentException("الطلب غير موجود."))

        if (withdrawal.status != "pending") {
            return@withContext Result.failure(IllegalStateException("تمت معالجة الطلب مسبقاً."))
        }

        val wallet = walletDao.getWalletById(withdrawal.walletId)
            ?: return@withContext Result.failure(IllegalStateException("المحفظة غير موجودة."))

        if (wallet.balance < withdrawal.amount) {
            return@withContext Result.failure(IllegalStateException("رصيد المحفظة لم يعد كافياً."))
        }

        walletDao.deductBalance(withdrawal.walletId, withdrawal.amount)
        withdrawalDao.updateWithdrawal(
            withdrawal.copy(
                status = "approved",
                reviewedAt = System.currentTimeMillis(),
                reviewNote = "تم تسليم المبلغ بنجاح"
            )
        )

        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = withdrawal.walletId,
                type = "withdraw",
                amount = withdrawal.amount,
                reference = "WTH-APR-$withdrawalId",
                description = "سحب نقدي معتمد لصالح ${withdrawal.recipientName}",
                status = "completed"
            )
        )

        notificationDao.insertNotification(
            NotificationEntity(
                userId = withdrawal.userId,
                title = "تم تنفيذ السحب بنجاح",
                message = "تمت الموافقة وتسليم مبلغ ${String.format("%,.2f", withdrawal.amount)} SDG.",
                type = "success"
            )
        )

        auditLogDao.insertLog(
            AuditLogEntity(
                adminId = adminId,
                action = "APPROVE_WITHDRAWAL",
                targetType = "withdrawal_requests",
                targetId = withdrawalId,
                description = "الموافقة على سحب بمبلغ ${withdrawal.amount} SDG للمستلم ${withdrawal.recipientName}"
            )
        )

        Result.success(Unit)
    }

    suspend fun rejectWithdrawal(withdrawalId: Long, adminId: Long, reason: String): Result<Unit> = withContext(Dispatchers.IO) {
        val withdrawal = withdrawalDao.getWithdrawalById(withdrawalId)
            ?: return@withContext Result.failure(IllegalArgumentException("الطلب غير موجود."))

        withdrawalDao.updateWithdrawal(
            withdrawal.copy(
                status = "rejected",
                reviewedAt = System.currentTimeMillis(),
                reviewNote = reason
            )
        )

        notificationDao.insertNotification(
            NotificationEntity(
                userId = withdrawal.userId,
                title = "رفض طلب السحب",
                message = "تم رفض طلب السحب: $reason",
                type = "warning"
            )
        )

        auditLogDao.insertLog(
            AuditLogEntity(
                adminId = adminId,
                action = "REJECT_WITHDRAWAL",
                targetType = "withdrawal_requests",
                targetId = withdrawalId,
                description = "رفض طلب سحب: $reason"
            )
        )

        Result.success(Unit)
    }

    // 8. Register new user
    suspend fun registerUser(fullName: String, phone: String, email: String?): Result<Long> = withContext(Dispatchers.IO) {
        val cleanedPhone = phone.trim().replace("\\s+".toRegex(), "")
        if (cleanedPhone.isEmpty() || fullName.trim().isEmpty()) {
            return@withContext Result.failure(IllegalArgumentException("الاسم ورقم الهاتف مطلوبان."))
        }

        val existing = userDao.getUserByPhone(cleanedPhone)
        if (existing != null) {
            return@withContext Result.failure(IllegalArgumentException("رقم الهاتف مسجل بالفعل في النظام."))
        }

        val userId = userDao.insertUser(
            UserEntity(
                fullName = fullName.trim(),
                phone = cleanedPhone,
                email = email?.trim()?.ifEmpty { null },
                role = "user",
                status = "active"
            )
        )

        walletDao.insertWallet(
            WalletEntity(
                userId = userId,
                balance = 10000.0, // Welcome bonus of 10,000 SDG for testing
                currency = "SDG"
            )
        )

        notificationDao.insertNotification(
            NotificationEntity(
                userId = userId,
                title = "مرحباً بك في المحفظة الذكية",
                message = "تم تسجيل حسابك بنجاح ومنحك رصيد ترحيبي 10,000.00 SDG لتجربة الخدمات.",
                type = "success"
            )
        )

        Result.success(userId)
    }
}
