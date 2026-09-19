package com.example.data.repository

import com.example.data.local.AppDatabase
import com.example.data.model.DepositRequest
import com.example.data.model.NotificationItem
import com.example.data.model.PaymentRequest
import com.example.data.model.ServiceRequest
import com.example.data.model.TransactionEntity
import com.example.data.model.User
import com.example.data.model.Wallet
import com.example.data.model.WithdrawalRequest
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.flatMapLatest
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.UUID

class WalletRepository(private val db: AppDatabase) {

    private val userDao = db.userDao()
    private val walletDao = db.walletDao()
    private val transactionDao = db.transactionDao()
    private val depositDao = db.depositDao()
    private val withdrawalDao = db.withdrawalDao()
    private val paymentDao = db.paymentDao()
    private val serviceDao = db.serviceDao()
    private val notificationDao = db.notificationDao()

    private val _currentUserId = MutableStateFlow<Long>(1L)
    val currentUserId: StateFlow<Long> = _currentUserId.asStateFlow()

    val currentUser: Flow<User?> = _currentUserId.flatMapLatest { id ->
        userDao.getUserById(id)
    }

    val currentWallet: Flow<Wallet?> = _currentUserId.flatMapLatest { id ->
        walletDao.getWalletByUserId(id)
    }

    val userTransactions: Flow<List<TransactionEntity>> = _currentUserId.flatMapLatest { id ->
        walletDao.getWalletByUserId(id).flatMapLatest { wallet ->
            val walletId = wallet?.id ?: 0L
            transactionDao.getTransactionsByWalletId(walletId)
        }
    }

    val userDeposits: Flow<List<DepositRequest>> = _currentUserId.flatMapLatest { id ->
        depositDao.getDepositsByUserId(id)
    }

    val userWithdrawals: Flow<List<WithdrawalRequest>> = _currentUserId.flatMapLatest { id ->
        withdrawalDao.getWithdrawalsByUserId(id)
    }

    val allNotifications: Flow<List<NotificationItem>> = notificationDao.getAllNotifications()
    val allUsers: Flow<List<User>> = userDao.getAllUsers()
    val allDeposits: Flow<List<DepositRequest>> = depositDao.getAllDeposits()
    val allWithdrawals: Flow<List<WithdrawalRequest>> = withdrawalDao.getAllWithdrawals()
    val allTransactions: Flow<List<TransactionEntity>> = transactionDao.getAllTransactions()

    init {
        CoroutineScope(Dispatchers.IO).launch {
            seedInitialDataIfNeeded()
        }
    }

    suspend fun seedInitialDataIfNeeded() = withContext(Dispatchers.IO) {
        val existing = userDao.getUserByPhone("0912345678")
        if (existing == null) {
            val user1Id = userDao.insertUser(
                User(
                    id = 1L,
                    fullName = "أحمد محمد عثمان",
                    username = "ahmed_osman",
                    phone = "0912345678",
                    email = "ahmed@example.com",
                    role = "user",
                    status = "active"
                )
            )
            val wallet1Id = walletDao.insertWallet(
                Wallet(
                    id = 1L,
                    userId = user1Id,
                    balance = 75000.0,
                    currency = "SDG"
                )
            )

            val user2Id = userDao.insertUser(
                User(
                    id = 2L,
                    fullName = "فاطمة الحسن عمر",
                    username = "fatima_hassan",
                    phone = "0998765432",
                    email = "fatima@example.com",
                    role = "user",
                    status = "active"
                )
            )
            val wallet2Id = walletDao.insertWallet(
                Wallet(
                    id = 2L,
                    userId = user2Id,
                    balance = 32000.0,
                    currency = "SDG"
                )
            )

            val adminId = userDao.insertUser(
                User(
                    id = 3L,
                    fullName = "مدير النظام (Admin)",
                    username = "admin_infinity",
                    phone = "0900000000",
                    email = "admin@smartwallet.sd",
                    role = "admin",
                    status = "active"
                )
            )
            walletDao.insertWallet(
                Wallet(
                    id = 3L,
                    userId = adminId,
                    balance = 500000.0,
                    currency = "SDG"
                )
            )

            // Seed initial transactions
            val now = System.currentTimeMillis()
            val day = 86400000L

            transactionDao.insertTransaction(
                TransactionEntity(
                    walletId = wallet1Id,
                    type = "deposit",
                    amount = 50000.0,
                    reference = "DEP-20260901-7A39B2",
                    description = "إيداع عبر تطبيق بنكك - بنك الخرطوم",
                    status = "completed",
                    createdAt = now - (3 * day)
                )
            )
            transactionDao.insertTransaction(
                TransactionEntity(
                    walletId = wallet1Id,
                    type = "transfer_in",
                    amount = 35000.0,
                    reference = "TRF-20260910-8C91F4",
                    description = "استلام تحويل من فاطمة الحسن",
                    status = "completed",
                    createdAt = now - (2 * day)
                )
            )
            transactionDao.insertTransaction(
                TransactionEntity(
                    walletId = wallet1Id,
                    type = "payment",
                    amount = 10000.0,
                    reference = "PAY-20260915-4D12A9",
                    description = "دفع مشتريات سوبرماركت المدينة",
                    status = "completed",
                    createdAt = now - day
                )
            )

            // Seed deposits
            depositDao.insertDeposit(
                DepositRequest(
                    reference = "DEP-REQ-1001",
                    userId = user1Id,
                    walletId = wallet1Id,
                    amount = 25000.0,
                    bankName = "بنك الخرطوم (بنكك)",
                    senderName = "أحمد محمد عثمان",
                    bankReference = "BOK-9843211",
                    note = "تغذية المحفظة الشهرية",
                    status = "pending",
                    createdAt = now - 3600000L
                )
            )

            // Seed withdrawals
            withdrawalDao.insertWithdrawal(
                WithdrawalRequest(
                    reference = "WTH-REQ-1001",
                    userId = user1Id,
                    walletId = wallet1Id,
                    amount = 5000.0,
                    recipientName = "أحمد محمد عثمان",
                    recipientPhone = "0912345678",
                    withdrawalMethod = "بنكك - بنك الخرطوم",
                    note = "سحب نقدي للطوارئ",
                    status = "pending",
                    createdAt = now - 7200000L
                )
            )

            // Seed notifications
            notificationDao.insertNotification(
                NotificationItem(
                    title = "مرحباً بك في المحفظة الذكية",
                    message = "تم تفعيل محفظتك بنجاح برصيد أولي 75,000 SDG.",
                    type = "system",
                    createdAt = now - (3 * day)
                )
            )
            notificationDao.insertNotification(
                NotificationItem(
                    title = "إيداع ناجح",
                    message = "تمت إضافة مبلغ 50,000 SDG إلى حسابك عبر بنك الخرطوم.",
                    type = "deposit",
                    createdAt = now - (3 * day)
                )
            )
            notificationDao.insertNotification(
                NotificationItem(
                    title = "استلام حوالة مالية",
                    message = "وصلك مبلغ 35,000 SDG من فاطمة الحسن عمر.",
                    type = "transfer",
                    createdAt = now - (2 * day)
                )
            )
        }
    }

    fun switchUser(userId: Long) {
        _currentUserId.value = userId
    }

    suspend fun transferMoney(
        receiverPhone: String,
        amount: Double,
        note: String
    ): Result<String> = withContext(Dispatchers.IO) {
        if (amount <= 0) {
            return@withContext Result.failure(IllegalArgumentException("يرجى إدخال مبلغ صحيح أكبر من الصفر."))
        }
        val senderId = _currentUserId.value
        val sender = userDao.getUserById(senderId)
        val senderWallet = walletDao.getWalletByUserIdDirect(senderId)
            ?: return@withContext Result.failure(IllegalStateException("لم يتم العثور على محفظة المرسل."))

        if (senderWallet.balance < amount) {
            return@withContext Result.failure(IllegalStateException("الرصيد غير كافٍ لإتمام التحويل. رصيدك الحالي ${formatMoney(senderWallet.balance)} SDG"))
        }

        val receiver = userDao.getUserByPhone(receiverPhone.trim())
            ?: return@withContext Result.failure(IllegalArgumentException("لم يتم العثور على مستخدم مسجل برقم الهاتف هذا ($receiverPhone)."))

        if (receiver.id == senderId) {
            return@withContext Result.failure(IllegalArgumentException("لا يمكنك التحويل إلى نفسك."))
        }

        if (receiver.status != "active") {
            return@withContext Result.failure(IllegalStateException("حساب المستلم غير نشط حالياً."))
        }

        val receiverWallet = walletDao.getWalletByUserIdDirect(receiver.id)
            ?: return@withContext Result.failure(IllegalStateException("لم يتم العثور على محفظة المستلم."))

        // Execute transfer debit and credit
        val debited = walletDao.debitBalance(senderWallet.id, amount)
        if (debited == 0) {
            return@withContext Result.failure(IllegalStateException("فشلت عملية الخصم. الرصيد غير كافٍ."))
        }
        walletDao.creditBalance(receiverWallet.id, amount)

        val timestamp = System.currentTimeMillis()
        val refCode = generateReference("TRF")

        // Record sender transaction (out)
        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = senderWallet.id,
                type = "transfer_out",
                amount = amount,
                reference = refCode,
                description = "تحويل إلى ${receiver.fullName} (${receiver.phone}) ${if (note.isNotBlank()) "- $note" else ""}",
                status = "completed",
                createdAt = timestamp
            )
        )

        // Record receiver transaction (in)
        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = receiverWallet.id,
                type = "transfer_in",
                amount = amount,
                reference = "IN-${refCode.substringAfter("-")}",
                description = "استلام تحويل من ${senderWallet.id} ${if (note.isNotBlank()) "- $note" else ""}",
                status = "completed",
                createdAt = timestamp
            )
        )

        // Record notifications
        notificationDao.insertNotification(
            NotificationItem(
                title = "تحويل صادر بنجاح",
                message = "تم تحويل ${formatMoney(amount)} SDG إلى ${receiver.fullName} بنجاح.",
                type = "transfer",
                createdAt = timestamp
            )
        )

        Result.success(refCode)
    }

    suspend fun requestDeposit(
        bankName: String,
        senderName: String,
        bankRef: String,
        amount: Double,
        note: String
    ): Result<String> = withContext(Dispatchers.IO) {
        if (amount <= 0) {
            return@withContext Result.failure(IllegalArgumentException("يرجى إدخال مبلغ صحيح للإيداع."))
        }
        if (bankRef.isBlank()) {
            return@withContext Result.failure(IllegalArgumentException("يرجى إدخال الرقم المرجعي للإشعار البنكي."))
        }

        val userId = _currentUserId.value
        val wallet = walletDao.getWalletByUserIdDirect(userId)
            ?: return@withContext Result.failure(IllegalStateException("لم يتم العثور على المحفظة."))

        val refCode = generateReference("DEP")
        val req = DepositRequest(
            reference = refCode,
            userId = userId,
            walletId = wallet.id,
            amount = amount,
            bankName = bankName,
            senderName = senderName,
            bankReference = bankRef,
            note = note,
            status = "pending",
            createdAt = System.currentTimeMillis()
        )
        depositDao.insertDeposit(req)

        notificationDao.insertNotification(
            NotificationItem(
                title = "طلب إيداع قيد المراجعة",
                message = "تم استلام طلب إيداعك بمبلغ ${formatMoney(amount)} SDG عبر $bankName وهو قيد المعالجة.",
                type = "deposit"
            )
        )

        Result.success(refCode)
    }

    suspend fun requestWithdrawal(
        recipientName: String,
        recipientPhone: String,
        method: String,
        amount: Double,
        note: String
    ): Result<String> = withContext(Dispatchers.IO) {
        if (amount <= 0) {
            return@withContext Result.failure(IllegalArgumentException("يرجى إدخال مبلغ صحيح للسحب."))
        }
        val userId = _currentUserId.value
        val wallet = walletDao.getWalletByUserIdDirect(userId)
            ?: return@withContext Result.failure(IllegalStateException("لم يتم العثور على المحفظة."))

        if (wallet.balance < amount) {
            return@withContext Result.failure(IllegalStateException("رصيدك الحالي لا يكفي لطلب هذا المبلغ."))
        }

        val debited = walletDao.debitBalance(wallet.id, amount)
        if (debited == 0) {
            return@withContext Result.failure(IllegalStateException("فشلت عملية الخصم لسحب الرصيد."))
        }

        val refCode = generateReference("WTH")
        val req = WithdrawalRequest(
            reference = refCode,
            userId = userId,
            walletId = wallet.id,
            amount = amount,
            recipientName = recipientName,
            recipientPhone = recipientPhone,
            withdrawalMethod = method,
            note = note,
            status = "pending",
            createdAt = System.currentTimeMillis()
        )
        withdrawalDao.insertWithdrawal(req)

        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = wallet.id,
                type = "withdraw",
                amount = amount,
                reference = refCode,
                description = "طلب سحب نقدي - $method ($recipientName)",
                status = "pending",
                createdAt = System.currentTimeMillis()
            )
        )

        notificationDao.insertNotification(
            NotificationItem(
                title = "طلب سحب أموال",
                message = "تم تسجيل طلب سحب بمبلغ ${formatMoney(amount)} SDG عبر $method.",
                type = "withdraw"
            )
        )

        Result.success(refCode)
    }

    suspend fun makePayment(
        merchantName: String,
        merchantAccount: String,
        amount: Double
    ): Result<String> = withContext(Dispatchers.IO) {
        if (amount <= 0) {
            return@withContext Result.failure(IllegalArgumentException("يرجى إدخال مبلغ صحيح للدفع."))
        }
        val userId = _currentUserId.value
        val wallet = walletDao.getWalletByUserIdDirect(userId)
            ?: return@withContext Result.failure(IllegalStateException("لم يتم العثور على المحفظة."))

        if (wallet.balance < amount) {
            return@withContext Result.failure(IllegalStateException("رصيد المحفظة غير كافٍ للدفع."))
        }

        val debited = walletDao.debitBalance(wallet.id, amount)
        if (debited == 0) {
            return@withContext Result.failure(IllegalStateException("تعذر خصم المبلغ من المحفظة."))
        }

        val refCode = generateReference("PAY")
        paymentDao.insertPayment(
            PaymentRequest(
                reference = refCode,
                userId = userId,
                amount = amount,
                merchantName = merchantName,
                merchantAccount = merchantAccount,
                status = "completed"
            )
        )

        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = wallet.id,
                type = "payment",
                amount = amount,
                reference = refCode,
                description = "دفع مشتريات إلى $merchantName (حساب: $merchantAccount)",
                status = "completed"
            )
        )

        notificationDao.insertNotification(
            NotificationItem(
                title = "عملية دفع ناجحة",
                message = "تم دفع ${formatMoney(amount)} SDG إلى $merchantName بنجاح.",
                type = "service"
            )
        )

        Result.success(refCode)
    }

    suspend fun requestService(
        serviceType: String,
        provider: String,
        phoneOrAccount: String,
        amount: Double,
        fee: Double
    ): Result<String> = withContext(Dispatchers.IO) {
        val total = amount + fee
        if (total <= 0) {
            return@withContext Result.failure(IllegalArgumentException("يرجى تحديد تفاصيل الخدمة والمبلغ."))
        }
        val userId = _currentUserId.value
        val wallet = walletDao.getWalletByUserIdDirect(userId)
            ?: return@withContext Result.failure(IllegalStateException("لم يتم العثور على المحفظة."))

        if (wallet.balance < total) {
            return@withContext Result.failure(IllegalStateException("رصيدك الحالي لا يكفي لسداد الخدمة والرسوم (${formatMoney(total)} SDG)."))
        }

        val debited = walletDao.debitBalance(wallet.id, total)
        if (debited == 0) {
            return@withContext Result.failure(IllegalStateException("تعذر إتمام عملية السداد."))
        }

        val refCode = generateReference("SRV")
        val typeTitle = when (serviceType) {
            "recharge" -> "شحن رصيد"
            "electricity" -> "سداد كهرباء"
            "internet" -> "باقة إنترنت"
            "bills" -> "سداد فواتير"
            "education" -> "رسوم تعليمية"
            else -> "خدمات حكومية"
        }

        val respMsg = when (serviceType) {
            "electricity" -> "رمز التغذية (Token): 5492-3810-9428-1194-0418"
            "recharge" -> "تم شحن الرصيد للرقم $phoneOrAccount بنجاح"
            else -> "تم سداد المعاملة واعتماد الإيصال بنجاح"
        }

        serviceDao.insertService(
            ServiceRequest(
                reference = refCode,
                userId = userId,
                serviceType = serviceType,
                provider = provider,
                phoneNumber = if (serviceType == "recharge") phoneOrAccount else "",
                accountNumber = if (serviceType != "recharge") phoneOrAccount else "",
                amount = amount,
                fee = fee,
                totalAmount = total,
                status = "completed",
                responseMessage = respMsg
            )
        )

        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = wallet.id,
                type = "service",
                amount = total,
                reference = refCode,
                description = "$typeTitle - $provider ($phoneOrAccount)",
                status = "completed"
            )
        )

        notificationDao.insertNotification(
            NotificationItem(
                title = "تم تنفيذ خدمة $typeTitle",
                message = "تم بنجاح سداد $typeTitle ($provider) بمبلغ ${formatMoney(total)} SDG.",
                type = "service"
            )
        )

        Result.success("$refCode|$respMsg")
    }

    suspend fun approveDeposit(depositId: Long): Result<String> = withContext(Dispatchers.IO) {
        val deposit = depositDao.getDepositById(depositId)
            ?: return@withContext Result.failure(IllegalArgumentException("طلب الإيداع غير موجود."))

        if (deposit.status != "pending") {
            return@withContext Result.failure(IllegalStateException("تم البت في هذا الطلب مسبقاً."))
        }

        walletDao.creditBalance(deposit.walletId, deposit.amount)
        depositDao.updateDeposit(deposit.copy(status = "approved", reviewNote = "تمت الموافقة وإيداع الرصيد"))

        transactionDao.insertTransaction(
            TransactionEntity(
                walletId = deposit.walletId,
                type = "deposit",
                amount = deposit.amount,
                reference = deposit.reference,
                description = "إيداع معتمد عبر ${deposit.bankName} (مرجع: ${deposit.bankReference})",
                status = "completed"
            )
        )

        notificationDao.insertNotification(
            NotificationItem(
                title = "تمت الموافقة على الإيداع",
                message = "تمت الموافقة على إيداع مبلغ ${formatMoney(deposit.amount)} SDG وإضافته إلى رصيدك.",
                type = "deposit"
            )
        )

        Result.success("تمت الموافقة على الإيداع وإضافة الرصيد بنجاح.")
    }

    suspend fun rejectDeposit(depositId: Long, note: String): Result<String> = withContext(Dispatchers.IO) {
        val deposit = depositDao.getDepositById(depositId)
            ?: return@withContext Result.failure(IllegalArgumentException("طلب الإيداع غير موجود."))

        depositDao.updateDeposit(deposit.copy(status = "rejected", reviewNote = note.ifBlank { "تم الرفض من قبل الإدارة" }))

        notificationDao.insertNotification(
            NotificationItem(
                title = "تم رفض طلب الإيداع",
                message = "تم رفض طلب الإيداع رقم ${deposit.reference}. السبب: ${note.ifBlank { "عدم تطابق الإشعار" }}",
                type = "deposit"
            )
        )

        Result.success("تم رفض طلب الإيداع.")
    }

    suspend fun approveWithdrawal(withdrawalId: Long): Result<String> = withContext(Dispatchers.IO) {
        val withdrawal = withdrawalDao.getWithdrawalById(withdrawalId)
            ?: return@withContext Result.failure(IllegalArgumentException("طلب السحب غير موجود."))

        if (withdrawal.status != "pending") {
            return@withContext Result.failure(IllegalStateException("تم البت في هذا الطلب مسبقاً."))
        }

        withdrawalDao.updateWithdrawal(withdrawal.copy(status = "approved", reviewNote = "تم تسليم المبلغ واعتماد العملية"))

        notificationDao.insertNotification(
            NotificationItem(
                title = "تمت الموافقة على السحب",
                message = "تمت الموافقة على سحب مبلغ ${formatMoney(withdrawal.amount)} SDG وتسليمه بنجاح.",
                type = "withdraw"
            )
        )

        Result.success("تمت الموافقة على السحب بنجاح.")
    }

    suspend fun rejectWithdrawal(withdrawalId: Long, note: String): Result<String> = withContext(Dispatchers.IO) {
        val withdrawal = withdrawalDao.getWithdrawalById(withdrawalId)
            ?: return@withContext Result.failure(IllegalArgumentException("طلب السحب غير موجود."))

        // Refund balance back to user
        walletDao.creditBalance(withdrawal.walletId, withdrawal.amount)
        withdrawalDao.updateWithdrawal(withdrawal.copy(status = "rejected", reviewNote = note.ifBlank { "تم الرفض وإعادة الرصيد للمحفظة" }))

        notificationDao.insertNotification(
            NotificationItem(
                title = "تم رفض طلب السحب وإعادة الرصيد",
                message = "تم رفض طلب السحب رقم ${withdrawal.reference} وإعادة مبلغ ${formatMoney(withdrawal.amount)} SDG لمحفظتك.",
                type = "withdraw"
            )
        )

        Result.success("تم رفض طلب السحب واسترجاع الرصيد بنجاح.")
    }

    suspend fun markAllNotificationsAsRead() = withContext(Dispatchers.IO) {
        notificationDao.markAllAsRead()
    }

    suspend fun updateUserProfile(name: String, email: String, phone: String): Result<String> = withContext(Dispatchers.IO) {
        val user = userDao.getUserById(_currentUserId.value)
        // update user
        val updated = User(
            id = _currentUserId.value,
            fullName = name.trim(),
            username = name.trim().lowercase().replace(" ", "_"),
            phone = phone.trim(),
            email = email.trim()
        )
        userDao.updateUser(updated)
        Result.success("تم تحديث الملف الشخصي بنجاح")
    }

    private fun generateReference(prefix: String): String {
        val dateStr = SimpleDateFormat("yyyyMMdd-HHmmss", Locale.ENGLISH).format(Date())
        val randomHex = UUID.randomUUID().toString().substring(0, 6).uppercase(Locale.ENGLISH)
        return "$prefix-$dateStr-$randomHex"
    }

    companion object {
        fun formatMoney(amount: Double): String {
            return String.format(Locale.ENGLISH, "%,.2f", amount)
        }
    }
}
