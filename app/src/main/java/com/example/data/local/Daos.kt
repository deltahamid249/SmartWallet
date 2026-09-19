package com.example.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Update
import com.example.data.model.DepositRequest
import com.example.data.model.NotificationItem
import com.example.data.model.PaymentRequest
import com.example.data.model.ServiceRequest
import com.example.data.model.TransactionEntity
import com.example.data.model.User
import com.example.data.model.Wallet
import com.example.data.model.WithdrawalRequest
import kotlinx.coroutines.flow.Flow

@Dao
interface UserDao {
    @Query("SELECT * FROM users WHERE id = :userId LIMIT 1")
    fun getUserById(userId: Long): Flow<User?>

    @Query("SELECT * FROM users WHERE phone = :phone LIMIT 1")
    suspend fun getUserByPhone(phone: String): User?

    @Query("SELECT * FROM users ORDER BY id ASC")
    fun getAllUsers(): Flow<List<User>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertUser(user: User): Long

    @Update
    suspend fun updateUser(user: User)
}

@Dao
interface WalletDao {
    @Query("SELECT * FROM wallets WHERE userId = :userId LIMIT 1")
    fun getWalletByUserId(userId: Long): Flow<Wallet?>

    @Query("SELECT * FROM wallets WHERE userId = :userId LIMIT 1")
    suspend fun getWalletByUserIdDirect(userId: Long): Wallet?

    @Query("SELECT * FROM wallets WHERE id = :walletId LIMIT 1")
    suspend fun getWalletById(walletId: Long): Wallet?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertWallet(wallet: Wallet): Long

    @Query("UPDATE wallets SET balance = balance + :amount, updatedAt = :timestamp WHERE id = :walletId")
    suspend fun creditBalance(walletId: Long, amount: Double, timestamp: Long = System.currentTimeMillis())

    @Query("UPDATE wallets SET balance = balance - :amount, updatedAt = :timestamp WHERE id = :walletId AND balance >= :amount")
    suspend fun debitBalance(walletId: Long, amount: Double, timestamp: Long = System.currentTimeMillis()): Int
}

@Dao
interface TransactionDao {
    @Query("SELECT * FROM transactions WHERE walletId = :walletId ORDER BY createdAt DESC")
    fun getTransactionsByWalletId(walletId: Long): Flow<List<TransactionEntity>>

    @Query("SELECT * FROM transactions ORDER BY createdAt DESC")
    fun getAllTransactions(): Flow<List<TransactionEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertTransaction(transaction: TransactionEntity): Long
}

@Dao
interface DepositDao {
    @Query("SELECT * FROM deposit_requests WHERE userId = :userId ORDER BY createdAt DESC")
    fun getDepositsByUserId(userId: Long): Flow<List<DepositRequest>>

    @Query("SELECT * FROM deposit_requests ORDER BY createdAt DESC")
    fun getAllDeposits(): Flow<List<DepositRequest>>

    @Query("SELECT * FROM deposit_requests WHERE id = :id LIMIT 1")
    suspend fun getDepositById(id: Long): DepositRequest?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertDeposit(deposit: DepositRequest): Long

    @Update
    suspend fun updateDeposit(deposit: DepositRequest)
}

@Dao
interface WithdrawalDao {
    @Query("SELECT * FROM withdrawal_requests WHERE userId = :userId ORDER BY createdAt DESC")
    fun getWithdrawalsByUserId(userId: Long): Flow<List<WithdrawalRequest>>

    @Query("SELECT * FROM withdrawal_requests ORDER BY createdAt DESC")
    fun getAllWithdrawals(): Flow<List<WithdrawalRequest>>

    @Query("SELECT * FROM withdrawal_requests WHERE id = :id LIMIT 1")
    suspend fun getWithdrawalById(id: Long): WithdrawalRequest?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertWithdrawal(withdrawal: WithdrawalRequest): Long

    @Update
    suspend fun updateWithdrawal(withdrawal: WithdrawalRequest)
}

@Dao
interface PaymentDao {
    @Query("SELECT * FROM payments WHERE userId = :userId ORDER BY createdAt DESC")
    fun getPaymentsByUserId(userId: Long): Flow<List<PaymentRequest>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertPayment(payment: PaymentRequest): Long
}

@Dao
interface ServiceDao {
    @Query("SELECT * FROM service_requests WHERE userId = :userId ORDER BY createdAt DESC")
    fun getServicesByUserId(userId: Long): Flow<List<ServiceRequest>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertService(service: ServiceRequest): Long
}

@Dao
interface NotificationDao {
    @Query("SELECT * FROM notifications ORDER BY createdAt DESC")
    fun getAllNotifications(): Flow<List<NotificationItem>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertNotification(notification: NotificationItem): Long

    @Query("UPDATE notifications SET isRead = 1 WHERE isRead = 0")
    suspend fun markAllAsRead()
}
