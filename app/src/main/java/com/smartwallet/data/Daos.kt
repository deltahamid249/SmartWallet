package com.smartwallet.data

import androidx.room.*
import kotlinx.coroutines.flow.Flow

@Dao
interface UserDao {
    @Query("SELECT * FROM users WHERE id = :userId LIMIT 1")
    fun getUserById(userId: Long): Flow<UserEntity?>

    @Query("SELECT * FROM users WHERE phone = :phone LIMIT 1")
    suspend fun getUserByPhone(phone: String): UserEntity?

    @Query("SELECT * FROM users ORDER BY id ASC")
    fun getAllUsers(): Flow<List<UserEntity>>

    @Insert(onConflict = OnConflictStrategy.ABORT)
    suspend fun insertUser(user: UserEntity): Long

    @Update
    suspend fun updateUser(user: UserEntity)

    @Query("SELECT COUNT(*) FROM users")
    fun getUserCount(): Flow<Int>

    @Query("SELECT COUNT(*) FROM users WHERE status = 'active'")
    fun getActiveUserCount(): Flow<Int>
}

@Dao
interface WalletDao {
    @Query("SELECT * FROM wallets WHERE userId = :userId LIMIT 1")
    fun getWalletByUserId(userId: Long): Flow<WalletEntity?>

    @Query("SELECT * FROM wallets WHERE userId = :userId LIMIT 1")
    suspend fun getWalletByUserIdSync(userId: Long): WalletEntity?

    @Query("SELECT * FROM wallets WHERE id = :walletId LIMIT 1")
    suspend fun getWalletById(walletId: Long): WalletEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertWallet(wallet: WalletEntity): Long

    @Update
    suspend fun updateWallet(wallet: WalletEntity)

    @Query("UPDATE wallets SET balance = balance + :amount, updatedAt = :time WHERE id = :walletId")
    suspend fun addBalance(walletId: Long, amount: Double, time: Long = System.currentTimeMillis())

    @Query("UPDATE wallets SET balance = balance - :amount, updatedAt = :time WHERE id = :walletId")
    suspend fun deductBalance(walletId: Long, amount: Double, time: Long = System.currentTimeMillis())

    @Query("SELECT SUM(balance) FROM wallets")
    fun getTotalCirculatingBalance(): Flow<Double?>
}

@Dao
interface TransactionDao {
    @Query("SELECT * FROM transactions WHERE walletId = :walletId ORDER BY createdAt DESC")
    fun getTransactionsForWallet(walletId: Long): Flow<List<TransactionEntity>>

    @Query("SELECT * FROM transactions ORDER BY createdAt DESC")
    fun getAllTransactions(): Flow<List<TransactionEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertTransaction(transaction: TransactionEntity): Long
}

@Dao
interface TransferDao {
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertTransfer(transfer: TransferEntity): Long

    @Query("SELECT * FROM transfers ORDER BY createdAt DESC")
    fun getAllTransfers(): Flow<List<TransferEntity>>
}

@Dao
interface DepositRequestDao {
    @Query("SELECT * FROM deposit_requests WHERE userId = :userId ORDER BY createdAt DESC")
    fun getDepositsForUser(userId: Long): Flow<List<DepositRequestEntity>>

    @Query("SELECT * FROM deposit_requests ORDER BY createdAt DESC")
    fun getAllDeposits(): Flow<List<DepositRequestEntity>>

    @Query("SELECT * FROM deposit_requests WHERE id = :id LIMIT 1")
    suspend fun getDepositById(id: Long): DepositRequestEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertDeposit(deposit: DepositRequestEntity): Long

    @Update
    suspend fun updateDeposit(deposit: DepositRequestEntity)

    @Query("SELECT COUNT(*) FROM deposit_requests WHERE status = 'pending'")
    fun getPendingDepositCount(): Flow<Int>
}

@Dao
interface WithdrawalRequestDao {
    @Query("SELECT * FROM withdrawal_requests WHERE userId = :userId ORDER BY createdAt DESC")
    fun getWithdrawalsForUser(userId: Long): Flow<List<WithdrawalRequestEntity>>

    @Query("SELECT * FROM withdrawal_requests ORDER BY createdAt DESC")
    fun getAllWithdrawals(): Flow<List<WithdrawalRequestEntity>>

    @Query("SELECT * FROM withdrawal_requests WHERE id = :id LIMIT 1")
    suspend fun getWithdrawalById(id: Long): WithdrawalRequestEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertWithdrawal(withdrawal: WithdrawalRequestEntity): Long

    @Update
    suspend fun updateWithdrawal(withdrawal: WithdrawalRequestEntity)

    @Query("SELECT COUNT(*) FROM withdrawal_requests WHERE status = 'pending'")
    fun getPendingWithdrawalCount(): Flow<Int>
}

@Dao
interface PaymentDao {
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertPayment(payment: PaymentEntity): Long

    @Query("SELECT * FROM payments WHERE userId = :userId ORDER BY createdAt DESC")
    fun getPaymentsForUser(userId: Long): Flow<List<PaymentEntity>>
}

@Dao
interface ServiceRequestDao {
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertServiceRequest(request: ServiceRequestEntity): Long

    @Query("SELECT * FROM service_requests WHERE userId = :userId ORDER BY createdAt DESC")
    fun getServicesForUser(userId: Long): Flow<List<ServiceRequestEntity>>

    @Query("SELECT * FROM service_requests ORDER BY createdAt DESC")
    fun getAllServices(): Flow<List<ServiceRequestEntity>>
}

@Dao
interface NotificationDao {
    @Query("SELECT * FROM notifications WHERE userId = :userId ORDER BY createdAt DESC")
    fun getNotificationsForUser(userId: Long): Flow<List<NotificationEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertNotification(notification: NotificationEntity): Long

    @Query("UPDATE notifications SET isRead = 1 WHERE id = :id")
    suspend fun markAsRead(id: Long)

    @Query("UPDATE notifications SET isRead = 1 WHERE userId = :userId")
    suspend fun markAllAsRead(userId: Long)
}

@Dao
interface AuditLogDao {
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertLog(log: AuditLogEntity): Long

    @Query("SELECT * FROM audit_logs ORDER BY createdAt DESC")
    fun getAllLogs(): Flow<List<AuditLogEntity>>
}
