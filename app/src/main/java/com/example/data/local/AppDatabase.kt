package com.example.data.local

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase
import com.example.data.model.DepositRequest
import com.example.data.model.NotificationItem
import com.example.data.model.PaymentRequest
import com.example.data.model.ServiceRequest
import com.example.data.model.TransactionEntity
import com.example.data.model.User
import com.example.data.model.Wallet
import com.example.data.model.WithdrawalRequest

@Database(
    entities = [
        User::class,
        Wallet::class,
        TransactionEntity::class,
        DepositRequest::class,
        WithdrawalRequest::class,
        PaymentRequest::class,
        ServiceRequest::class,
        NotificationItem::class
    ],
    version = 1,
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun userDao(): UserDao
    abstract fun walletDao(): WalletDao
    abstract fun transactionDao(): TransactionDao
    abstract fun depositDao(): DepositDao
    abstract fun withdrawalDao(): WithdrawalDao
    abstract fun paymentDao(): PaymentDao
    abstract fun serviceDao(): ServiceDao
    abstract fun notificationDao(): NotificationDao

    companion object {
        @Volatile
        private var INSTANCE: AppDatabase? = null

        fun getDatabase(context: Context): AppDatabase {
            return INSTANCE ?: synchronized(this) {
                val instance = Room.databaseBuilder(
                    context.applicationContext,
                    AppDatabase::class.java,
                    "smart_wallet.db"
                ).fallbackToDestructiveMigration().build()
                INSTANCE = instance
                instance
            }
        }
    }
}
