package com.example.data.model

import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

@Entity(
    tableName = "users",
    indices = [
        Index(value = ["phone"], unique = true),
        Index(value = ["email"], unique = true)
    ]
)
data class User(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val fullName: String,
    val username: String,
    val phone: String,
    val email: String,
    val role: String = "user", // "user" or "admin"
    val status: String = "active", // "active" or "blocked"
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(
    tableName = "wallets",
    indices = [Index(value = ["userId"], unique = true)]
)
data class Wallet(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val userId: Long,
    val balance: Double = 0.0,
    val currency: String = "SDG",
    val updatedAt: Long = System.currentTimeMillis()
)

@Entity(
    tableName = "transactions",
    indices = [
        Index(value = ["walletId"]),
        Index(value = ["reference"], unique = true)
    ]
)
data class TransactionEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val walletId: Long,
    val type: String, // "deposit", "withdraw", "transfer_in", "transfer_out", "payment", "service"
    val amount: Double,
    val reference: String,
    val description: String,
    val status: String = "completed", // "completed", "pending", "failed"
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(
    tableName = "deposit_requests",
    indices = [Index(value = ["reference"], unique = true)]
)
data class DepositRequest(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val reference: String,
    val userId: Long,
    val walletId: Long,
    val amount: Double,
    val bankName: String,
    val senderName: String,
    val bankReference: String,
    val note: String = "",
    val status: String = "pending", // "pending", "approved", "rejected"
    val createdAt: Long = System.currentTimeMillis(),
    val reviewNote: String? = null
)

@Entity(
    tableName = "withdrawal_requests",
    indices = [Index(value = ["reference"], unique = true)]
)
data class WithdrawalRequest(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val reference: String,
    val userId: Long,
    val walletId: Long,
    val amount: Double,
    val recipientName: String,
    val recipientPhone: String,
    val withdrawalMethod: String = "bank_transfer",
    val note: String = "",
    val status: String = "pending", // "pending", "approved", "rejected"
    val createdAt: Long = System.currentTimeMillis(),
    val reviewNote: String? = null
)

@Entity(
    tableName = "payments",
    indices = [Index(value = ["reference"], unique = true)]
)
data class PaymentRequest(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val reference: String,
    val userId: Long,
    val amount: Double,
    val merchantName: String,
    val merchantAccount: String,
    val status: String = "completed",
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(
    tableName = "service_requests",
    indices = [Index(value = ["reference"], unique = true)]
)
data class ServiceRequest(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val reference: String,
    val userId: Long,
    val serviceType: String, // "recharge", "electricity", "internet", "bills", "education", "government"
    val provider: String,
    val phoneNumber: String = "",
    val accountNumber: String = "",
    val amount: Double,
    val fee: Double = 0.0,
    val totalAmount: Double,
    val status: String = "completed",
    val responseMessage: String = "",
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "notifications")
data class NotificationItem(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val userId: Long? = null,
    val title: String,
    val message: String,
    val type: String = "info", // "deposit", "withdraw", "transfer", "service", "system"
    val isRead: Boolean = false,
    val createdAt: Long = System.currentTimeMillis()
)
