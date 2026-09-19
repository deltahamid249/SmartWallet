package com.example.ui.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.data.model.DepositRequest
import com.example.data.model.NotificationItem
import com.example.data.model.TransactionEntity
import com.example.data.model.User
import com.example.data.model.Wallet
import com.example.data.model.WithdrawalRequest
import com.example.data.repository.WalletRepository
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch

sealed interface UiMessage {
    data class Success(val message: String) : UiMessage
    data class Error(val message: String) : UiMessage
    data class ServiceReceipt(val title: String, val reference: String, val note: String) : UiMessage
}

class WalletViewModel(private val repository: WalletRepository) : ViewModel() {

    val currentUser: StateFlow<User?> = repository.currentUser
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), null)

    val currentWallet: StateFlow<Wallet?> = repository.currentWallet
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), null)

    val transactions: StateFlow<List<TransactionEntity>> = repository.userTransactions
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val deposits: StateFlow<List<DepositRequest>> = repository.userDeposits
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val withdrawals: StateFlow<List<WithdrawalRequest>> = repository.userWithdrawals
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val notifications: StateFlow<List<NotificationItem>> = repository.allNotifications
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val allDeposits: StateFlow<List<DepositRequest>> = repository.allDeposits
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val allWithdrawals: StateFlow<List<WithdrawalRequest>> = repository.allWithdrawals
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val allUsers: StateFlow<List<User>> = repository.allUsers
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    private val _isBalanceVisible = MutableStateFlow(true)
    val isBalanceVisible: StateFlow<Boolean> = _isBalanceVisible.asStateFlow()

    private val _uiMessage = MutableSharedFlow<UiMessage>()
    val uiMessage: SharedFlow<UiMessage> = _uiMessage.asSharedFlow()

    fun toggleBalanceVisibility() {
        _isBalanceVisible.value = !_isBalanceVisible.value
    }

    fun switchUser(userId: Long) {
        repository.switchUser(userId)
    }

    fun transferMoney(receiverPhone: String, amount: Double, note: String) {
        viewModelScope.launch {
            val result = repository.transferMoney(receiverPhone, amount, note)
            result.onSuccess { ref ->
                _uiMessage.emit(UiMessage.Success("تم التحويل بنجاح! رقم المرجع: $ref"))
            }.onFailure { e ->
                _uiMessage.emit(UiMessage.Error(e.message ?: "فشلت عملية التحويل"))
            }
        }
    }

    fun requestDeposit(
        bankName: String,
        senderName: String,
        bankRef: String,
        amount: Double,
        note: String
    ) {
        viewModelScope.launch {
            val result = repository.requestDeposit(bankName, senderName, bankRef, amount, note)
            result.onSuccess { ref ->
                _uiMessage.emit(UiMessage.Success("تم إرسال طلب الإيداع بنجاح برقم مرجعي: $ref"))
            }.onFailure { e ->
                _uiMessage.emit(UiMessage.Error(e.message ?: "فشل إرسال طلب الإيداع"))
            }
        }
    }

    fun requestWithdrawal(
        recipientName: String,
        recipientPhone: String,
        method: String,
        amount: Double,
        note: String
    ) {
        viewModelScope.launch {
            val result = repository.requestWithdrawal(recipientName, recipientPhone, method, amount, note)
            result.onSuccess { ref ->
                _uiMessage.emit(UiMessage.Success("تم تسجيل طلب السحب بنجاح برقم: $ref"))
            }.onFailure { e ->
                _uiMessage.emit(UiMessage.Error(e.message ?: "فشل تسجيل طلب السحب"))
            }
        }
    }

    fun makePayment(merchantName: String, merchantAccount: String, amount: Double) {
        viewModelScope.launch {
            val result = repository.makePayment(merchantName, merchantAccount, amount)
            result.onSuccess { ref ->
                _uiMessage.emit(UiMessage.Success("تمت عملية الدفع بنجاح إلى $merchantName! رقم المرجع: $ref"))
            }.onFailure { e ->
                _uiMessage.emit(UiMessage.Error(e.message ?: "فشلت عملية الدفع"))
            }
        }
    }

    fun requestService(
        serviceType: String,
        provider: String,
        phoneOrAccount: String,
        amount: Double,
        fee: Double
    ) {
        viewModelScope.launch {
            val result = repository.requestService(serviceType, provider, phoneOrAccount, amount, fee)
            result.onSuccess { data ->
                val parts = data.split("|")
                val ref = parts.getOrNull(0) ?: ""
                val msg = parts.getOrNull(1) ?: "تم التنفيذ"
                _uiMessage.emit(UiMessage.ServiceReceipt(provider, ref, msg))
            }.onFailure { e ->
                _uiMessage.emit(UiMessage.Error(e.message ?: "فشلت عملية سداد الخدمة"))
            }
        }
    }

    fun approveDeposit(depositId: Long) {
        viewModelScope.launch {
            val result = repository.approveDeposit(depositId)
            result.onSuccess { msg ->
                _uiMessage.emit(UiMessage.Success(msg))
            }.onFailure { e ->
                _uiMessage.emit(UiMessage.Error(e.message ?: "فشلت الموافقة"))
            }
        }
    }

    fun rejectDeposit(depositId: Long, note: String) {
        viewModelScope.launch {
            val result = repository.rejectDeposit(depositId, note)
            result.onSuccess { msg ->
                _uiMessage.emit(UiMessage.Success(msg))
            }.onFailure { e ->
                _uiMessage.emit(UiMessage.Error(e.message ?: "فشل رفض الطلب"))
            }
        }
    }

    fun approveWithdrawal(withdrawalId: Long) {
        viewModelScope.launch {
            val result = repository.approveWithdrawal(withdrawalId)
            result.onSuccess { msg ->
                _uiMessage.emit(UiMessage.Success(msg))
            }.onFailure { e ->
                _uiMessage.emit(UiMessage.Error(e.message ?: "فشلت الموافقة"))
            }
        }
    }

    fun rejectWithdrawal(withdrawalId: Long, note: String) {
        viewModelScope.launch {
            val result = repository.rejectWithdrawal(withdrawalId, note)
            result.onSuccess { msg ->
                _uiMessage.emit(UiMessage.Success(msg))
            }.onFailure { e ->
                _uiMessage.emit(UiMessage.Error(e.message ?: "فشل الرفض"))
            }
        }
    }

    fun markNotificationsAsRead() {
        viewModelScope.launch {
            repository.markAllNotificationsAsRead()
        }
    }

    fun updateProfile(name: String, email: String, phone: String) {
        viewModelScope.launch {
            val result = repository.updateUserProfile(name, email, phone)
            result.onSuccess { msg ->
                _uiMessage.emit(UiMessage.Success(msg))
            }.onFailure { e ->
                _uiMessage.emit(UiMessage.Error(e.message ?: "فشل التحديث"))
            }
        }
    }
}

class WalletViewModelFactory(private val repository: WalletRepository) : ViewModelProvider.Factory {
    @Suppress("UNCHECKED_CAST")
    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        if (modelClass.isAssignableFrom(WalletViewModel::class.java)) {
            return WalletViewModel(repository) as T
        }
        throw IllegalArgumentException("Unknown ViewModel class")
    }
}
