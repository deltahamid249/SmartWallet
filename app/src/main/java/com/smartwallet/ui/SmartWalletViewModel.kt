package com.smartwallet.ui

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.smartwallet.data.*
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

@OptIn(ExperimentalCoroutinesApi::class)
class SmartWalletViewModel(
    private val repository: SmartWalletRepository
) : ViewModel() {

    private val _currentUserId = MutableStateFlow(1L)
    val currentUserId: StateFlow<Long> = _currentUserId.asStateFlow()

    val allUsers: StateFlow<List<UserEntity>> = repository.getAllUsers()
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val currentUser: StateFlow<UserEntity?> = _currentUserId.flatMapLatest { userId ->
        repository.getUser(userId)
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), null)

    val currentWallet: StateFlow<WalletEntity?> = _currentUserId.flatMapLatest { userId ->
        repository.getWallet(userId)
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), null)

    val transactions: StateFlow<List<TransactionEntity>> = currentWallet.flatMapLatest { wallet ->
        if (wallet != null) {
            repository.getTransactions(wallet.id)
        } else {
            flowOf(emptyList())
        }
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val notifications: StateFlow<List<NotificationEntity>> = _currentUserId.flatMapLatest { userId ->
        repository.getNotifications(userId)
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val userDeposits: StateFlow<List<DepositRequestEntity>> = _currentUserId.flatMapLatest { userId ->
        repository.getDepositsForUser(userId)
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val userWithdrawals: StateFlow<List<WithdrawalRequestEntity>> = _currentUserId.flatMapLatest { userId ->
        repository.getWithdrawalsForUser(userId)
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val userServices: StateFlow<List<ServiceRequestEntity>> = _currentUserId.flatMapLatest { userId ->
        repository.getServicesForUser(userId)
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    // Admin dashboard flows
    val allDeposits: StateFlow<List<DepositRequestEntity>> = repository.getAllDeposits()
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val allWithdrawals: StateFlow<List<WithdrawalRequestEntity>> = repository.getAllWithdrawals()
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val allServices: StateFlow<List<ServiceRequestEntity>> = repository.getAllServices()
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val allAuditLogs: StateFlow<List<AuditLogEntity>> = repository.getAllAuditLogs()
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val userCount: StateFlow<Int> = repository.getUserCount()
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0)

    val activeUserCount: StateFlow<Int> = repository.getActiveUserCount()
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0)

    val totalCirculatingBalance: StateFlow<Double?> = repository.getTotalCirculatingBalance()
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0.0)

    val pendingDepositCount: StateFlow<Int> = repository.getPendingDepositCount()
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0)

    val pendingWithdrawalCount: StateFlow<Int> = repository.getPendingWithdrawalCount()
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0)

    fun switchUser(userId: Long) {
        _currentUserId.value = userId
    }

    fun sendTransfer(
        receiverPhone: String,
        amount: Double,
        note: String?,
        onResult: (Boolean, String) -> Unit
    ) {
        viewModelScope.launch {
            val result = repository.transfer(_currentUserId.value, receiverPhone, amount, note)
            result.fold(
                onSuccess = { ref -> onResult(true, ref) },
                onFailure = { err -> onResult(false, err.message ?: "حدث خطأ أثناء التحويل.") }
            )
        }
    }

    fun instantDeposit(amount: Double, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            val result = repository.instantDeposit(_currentUserId.value, amount)
            result.fold(
                onSuccess = { ref -> onResult(true, ref) },
                onFailure = { err -> onResult(false, err.message ?: "حدث خطأ أثناء الإيداع.") }
            )
        }
    }

    fun submitBankDeposit(
        bankName: String,
        senderName: String,
        bankReference: String,
        amount: Double,
        note: String?,
        onResult: (Boolean, String) -> Unit
    ) {
        viewModelScope.launch {
            val result = repository.submitBankDeposit(
                _currentUserId.value,
                bankName,
                senderName,
                bankReference,
                amount,
                note
            )
            result.fold(
                onSuccess = { ref -> onResult(true, ref) },
                onFailure = { err -> onResult(false, err.message ?: "حدث خطأ أثناء تقديم الطلب.") }
            )
        }
    }

    fun submitWithdrawal(
        amount: Double,
        recipientName: String,
        recipientPhone: String,
        method: String,
        note: String?,
        onResult: (Boolean, String) -> Unit
    ) {
        viewModelScope.launch {
            val result = repository.submitWithdrawal(
                _currentUserId.value,
                amount,
                recipientName,
                recipientPhone,
                method,
                note
            )
            result.fold(
                onSuccess = { ref -> onResult(true, ref) },
                onFailure = { err -> onResult(false, err.message ?: "حدث خطأ أثناء طلب السحب.") }
            )
        }
    }

    fun payMerchant(
        merchantName: String,
        merchantAccount: String,
        amount: Double,
        onResult: (Boolean, String) -> Unit
    ) {
        viewModelScope.launch {
            val result = repository.payMerchant(_currentUserId.value, merchantName, merchantAccount, amount)
            result.fold(
                onSuccess = { ref -> onResult(true, ref) },
                onFailure = { err -> onResult(false, err.message ?: "حدث خطأ أثناء عملية الدفع.") }
            )
        }
    }

    fun payService(
        serviceType: String,
        serviceTitle: String,
        provider: String,
        targetIdentifier: String,
        amount: Double,
        fee: Double,
        onResult: (Boolean, String) -> Unit
    ) {
        viewModelScope.launch {
            val result = repository.payService(
                _currentUserId.value,
                serviceType,
                serviceTitle,
                provider,
                targetIdentifier,
                amount,
                fee
            )
            result.fold(
                onSuccess = { ref -> onResult(true, ref) },
                onFailure = { err -> onResult(false, err.message ?: "حدث خطأ أثناء تنفيذ الخدمة.") }
            )
        }
    }

    fun approveDeposit(depositId: Long, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            val result = repository.approveDeposit(depositId, _currentUserId.value)
            result.fold(
                onSuccess = { onResult(true, "تمت الموافقة على الإيداع بنجاح وتحديث الرصيد.") },
                onFailure = { err -> onResult(false, err.message ?: "فشل اعتماد الإيداع.") }
            )
        }
    }

    fun rejectDeposit(depositId: Long, reason: String, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            val result = repository.rejectDeposit(depositId, _currentUserId.value, reason)
            result.fold(
                onSuccess = { onResult(true, "تم رفض طلب الإيداع.") },
                onFailure = { err -> onResult(false, err.message ?: "فشل الرفض.") }
            )
        }
    }

    fun approveWithdrawal(withdrawalId: Long, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            val result = repository.approveWithdrawal(withdrawalId, _currentUserId.value)
            result.fold(
                onSuccess = { onResult(true, "تمت الموافقة على السحب وخصم الرصيد بنجاح.") },
                onFailure = { err -> onResult(false, err.message ?: "فشل اعتماد السحب.") }
            )
        }
    }

    fun rejectWithdrawal(withdrawalId: Long, reason: String, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            val result = repository.rejectWithdrawal(withdrawalId, _currentUserId.value, reason)
            result.fold(
                onSuccess = { onResult(true, "تم رفض طلب السحب.") },
                onFailure = { err -> onResult(false, err.message ?: "فشل الرفض.") }
            )
        }
    }

    fun registerNewUser(fullName: String, phone: String, email: String?, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            val result = repository.registerUser(fullName, phone, email)
            result.fold(
                onSuccess = { newId ->
                    _currentUserId.value = newId
                    onResult(true, "تم إنشاء الحساب بنجاح!")
                },
                onFailure = { err -> onResult(false, err.message ?: "فشل إنشاء الحساب.") }
            )
        }
    }

    fun markNotificationAsRead(id: Long) {
        viewModelScope.launch {
            repository.markNotificationAsRead(id)
        }
    }

    fun markAllNotificationsAsRead() {
        viewModelScope.launch {
            repository.markAllNotificationsAsRead(_currentUserId.value)
        }
    }
}
