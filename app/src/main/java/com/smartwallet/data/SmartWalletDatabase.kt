package com.smartwallet.data

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase
import androidx.sqlite.db.SupportSQLiteDatabase
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

@Database(
    entities = [
        UserEntity::class,
        WalletEntity::class,
        TransactionEntity::class,
        TransferEntity::class,
        DepositRequestEntity::class,
        WithdrawalRequestEntity::class,
        PaymentEntity::class,
        ServiceRequestEntity::class,
        NotificationEntity::class,
        AuditLogEntity::class
    ],
    version = 1,
    exportSchema = false
)
abstract class SmartWalletDatabase : RoomDatabase() {
    abstract fun userDao(): UserDao
    abstract fun walletDao(): WalletDao
    abstract fun transactionDao(): TransactionDao
    abstract fun transferDao(): TransferDao
    abstract fun depositRequestDao(): DepositRequestDao
    abstract fun withdrawalRequestDao(): WithdrawalRequestDao
    abstract fun paymentDao(): PaymentDao
    abstract fun serviceRequestDao(): ServiceRequestDao
    abstract fun notificationDao(): NotificationDao
    abstract fun auditLogDao(): AuditLogDao

    companion object {
        @Volatile
        private var INSTANCE: SmartWalletDatabase? = null

        fun getDatabase(context: Context, scope: CoroutineScope): SmartWalletDatabase {
            return INSTANCE ?: synchronized(this) {
                val instance = Room.databaseBuilder(
                    context.applicationContext,
                    SmartWalletDatabase::class.java,
                    "smart_wallet.db"
                )
                .addCallback(DatabaseCallback(scope))
                .build()
                INSTANCE = instance
                instance
            }
        }
    }

    private class DatabaseCallback(
        private val scope: CoroutineScope
    ) : RoomDatabase.Callback() {
        override fun onCreate(db: SupportSQLiteDatabase) {
            super.onCreate(db)
            INSTANCE?.let { database ->
                scope.launch(Dispatchers.IO) {
                    populateInitialData(database)
                }
            }
        }

        private suspend fun populateInitialData(db: SmartWalletDatabase) {
            val userDao = db.userDao()
            val walletDao = db.walletDao()
            val transactionDao = db.transactionDao()
            val notificationDao = db.notificationDao()
            val auditLogDao = db.auditLogDao()

            // 1. Primary demo user (أحمد محمد علي)
            val user1Id = userDao.insertUser(
                UserEntity(
                    fullName = "أحمد محمد علي",
                    phone = "0911223344",
                    email = "ahmed@example.com",
                    role = "user",
                    status = "active"
                )
            )

            val wallet1Id = walletDao.insertWallet(
                WalletEntity(
                    userId = user1Id,
                    balance = 75000.0,
                    currency = "SDG"
                )
            )

            // 2. Second user (فاطمة إبراهيم)
            val user2Id = userDao.insertUser(
                UserEntity(
                    fullName = "فاطمة إبراهيم",
                    phone = "0988776655",
                    email = "fatima@example.com",
                    role = "user",
                    status = "active"
                )
            )

            val wallet2Id = walletDao.insertWallet(
                WalletEntity(
                    userId = user2Id,
                    balance = 30000.0,
                    currency = "SDG"
                )
            )

            // 3. Admin user (مدير النظام)
            val adminId = userDao.insertUser(
                UserEntity(
                    fullName = "مدير النظام",
                    phone = "0912345678",
                    email = "admin@smartwallet.sd",
                    role = "admin",
                    status = "active"
                )
            )

            walletDao.insertWallet(
                WalletEntity(
                    userId = adminId,
                    balance = 100000.0,
                    currency = "SDG"
                )
            )

            // Initial transactions for User 1
            transactionDao.insertTransaction(
                TransactionEntity(
                    walletId = wallet1Id,
                    type = "deposit",
                    amount = 50000.0,
                    reference = "DEP-INIT-001",
                    description = "إيداع رصيد افتتاحي عبر بنك الخرطوم (بنكك)",
                    status = "completed"
                )
            )

            transactionDao.insertTransaction(
                TransactionEntity(
                    walletId = wallet1Id,
                    type = "deposit",
                    amount = 30000.0,
                    reference = "DEP-INIT-002",
                    description = "إيداع نقدي عبر وكيل معتمد",
                    status = "completed"
                )
            )

            transactionDao.insertTransaction(
                TransactionEntity(
                    walletId = wallet1Id,
                    type = "recharge",
                    amount = 5000.0,
                    reference = "SRV-RCH-9812",
                    description = "شحن رصيد زين - 0911223344",
                    status = "completed"
                )
            )

            // Initial notifications
            notificationDao.insertNotification(
                NotificationEntity(
                    userId = user1Id,
                    title = "مرحبًا بك في المحفظة الذكية",
                    message = "تم تفعيل محفظتك الرقمية بنجاح بعملة الجنيه السوداني (SDG).",
                    type = "success"
                )
            )

            notificationDao.insertNotification(
                NotificationEntity(
                    userId = user1Id,
                    title = "إيداع ناجح",
                    message = "تمت إضافة 30,000.00 SDG إلى محفظتك بنجاح.",
                    type = "info"
                )
            )

            // Audit log
            auditLogDao.insertLog(
                AuditLogEntity(
                    adminId = adminId,
                    action = "SYSTEM_INITIALIZATION",
                    description = "تهيئة النظام وقواعد البيانات الافتتاحية للمحفظة الذكية"
                )
            )
        }
    }
}
