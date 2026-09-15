package com.smartwallet.data

import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

@Entity(
    tableName = "users",
    indices = [Index(value = ["phone"], unique = true)]
)
data class UserEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val fullName: String,
    val phone: String,
    val email: String? = null,
    val passwordHash: String = "secret",
    val role: String = "user", // "user" or "admin"
    val status: String = "active",
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(
    tableName = "wallets",
    indices = [Index(value = ["userId"], unique = true)]
)
data class WalletEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val userId: Long,
    val balance: Double = 0.0,
    val currency: String = "SDG",
    val updatedAt: Long = System.currentTimeMillis()
)

@Entity(
    tableName = "transactions",
    indices = [Index(value = ["reference"], unique = true), Index(value = ["walletId"])]
)
data class TransactionEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val walletId: Long,
    val type: String, // "deposit", "withdraw", "transfer_in", "transfer_out", "payment", "recharge", "electricity", "internet", "bills", "education", "government"
    val amount: Double,
    val reference: String,
    val description: String,
    val status: String = "completed",
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "transfers")
data class TransferEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val senderWalletId: Long,
    val receiverWalletId: Long,
    val senderPhone: String,
    val receiverPhone: String,
    val receiverName: String,
    val amount: Double,
    val reference: String,
    val note: String? = null,
    val status: String = "completed",
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "deposit_requests")
data class DepositRequestEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val userId: Long,
    val walletId: Long,
    val amount: Double,
    val bankName: String,
    val senderName: String,
    val bankReference: String,
    val note: String? = null,
    val status: String = "pending", // pending, approved, rejected
    val reviewNote: String? = null,
    val reviewedAt: Long? = null,
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "withdrawal_requests")
data class WithdrawalRequestEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val userId: Long,
    val walletId: Long,
    val amount: Double,
    val recipientName: String,
    val recipientPhone: String,
    val withdrawalMethod: String = "manual",
    val note: String? = null,
    val status: String = "pending", // pending, approved, rejected
    val reviewNote: String? = null,
    val reviewedAt: Long? = null,
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "payments")
data class PaymentEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val userId: Long,
    val merchantName: String,
    val merchantAccount: String,
    val amount: Double,
    val reference: String,
    val status: String = "completed",
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "service_requests")
data class ServiceRequestEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val userId: Long,
    val serviceType: String, // recharge, electricity, internet, bills, education, government
    val provider: String,
    val targetIdentifier: String,
    val amount: Double,
    val fee: Double = 0.0,
    val totalAmount: Double,
    val reference: String,
    val status: String = "completed",
    val responseMessage: String? = null,
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "notifications")
data class NotificationEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val userId: Long,
    val title: String,
    val message: String,
    val type: String = "info", // info, success, warning
    val isRead: Boolean = false,
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "audit_logs")
data class AuditLogEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val adminId: Long,
    val action: String,
    val targetType: String? = null,
    val targetId: Long? = null,
    val description: String,
    val createdAt: Long = System.currentTimeMillis()
)
