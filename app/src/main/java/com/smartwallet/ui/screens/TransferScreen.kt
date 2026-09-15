package com.smartwallet.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.smartwallet.ui.SmartWalletViewModel
import com.smartwallet.ui.components.SuccessReceiptDialog
import com.smartwallet.ui.components.WalletTopAppBar
import com.smartwallet.ui.components.formatSdg
import com.smartwallet.ui.theme.*

@Composable
fun TransferScreen(
    viewModel: SmartWalletViewModel,
    onBack: () -> Unit
) {
    val currentWallet by viewModel.currentWallet.collectAsState()
    val allUsers by viewModel.allUsers.collectAsState()
    val currentUser by viewModel.currentUser.collectAsState()

    var receiverPhone by remember { mutableStateOf("") }
    var amountText by remember { mutableStateOf("") }
    var note by remember { mutableStateOf("") }

    var errorMessage by remember { mutableStateOf<String?>(null) }
    var successReference by remember { mutableStateOf<String?>(null) }
    var transferredAmount by remember { mutableDoubleStateOf(0.0) }
    var isSubmitting by remember { mutableStateOf(false) }

    val otherUsers = allUsers.filter { it.id != currentUser?.id && it.role != "admin" }

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "إرسال الأموال",
                onBack = onBack
            )
        },
        containerColor = SlateBackground
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .verticalScroll(rememberScrollState())
                .padding(18.dp)
                .testTag("transfer_screen"),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Available balance card
            Surface(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(18.dp),
                color = NavyPrimary,
                shadowElevation = 4.dp
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(18.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column {
                        Text("الرصيد المتاح للتحويل", color = Color(0xFF94A3B8), fontSize = 13.sp)
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = "${formatSdg(currentWallet?.balance ?: 0.0)} SDG",
                            color = EmeraldLight,
                            fontSize = 22.sp,
                            fontWeight = FontWeight.ExtraBold
                        )
                    }
                    Text("📤", fontSize = 28.sp)
                }
            }

            // Quick contacts chips
            if (otherUsers.isNotEmpty()) {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(
                        text = "المستلمون المقترحون:",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextSecondary
                    )
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        for (user in otherUsers) {
                            FilterChip(
                                selected = receiverPhone == user.phone,
                                onClick = { receiverPhone = user.phone },
                                label = { Text("${user.fullName} (${user.phone})", fontSize = 12.sp) },
                                colors = FilterChipDefaults.filterChipColors(
                                    selectedContainerColor = EmeraldContainer,
                                    selectedLabelColor = EmeraldDark
                                )
                            )
                        }
                    }
                }
            }

            // Input fields card
            Surface(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(18.dp),
                color = Color.White,
                border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight),
                shadowElevation = 2.dp
            ) {
                Column(
                    modifier = Modifier.padding(18.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    OutlinedTextField(
                        value = receiverPhone,
                        onValueChange = { receiverPhone = it },
                        label = { Text("رقم هاتف المستلم") },
                        placeholder = { Text("مثال: 0912345678") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                        modifier = Modifier
                            .fillMaxWidth()
                            .testTag("transfer_phone_input"),
                        shape = RoundedCornerShape(12.dp)
                    )

                    OutlinedTextField(
                        value = amountText,
                        onValueChange = { amountText = it },
                        label = { Text("المبلغ (SDG)") },
                        placeholder = { Text("0.00") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                        modifier = Modifier
                            .fillMaxWidth()
                            .testTag("transfer_amount_input"),
                        shape = RoundedCornerShape(12.dp)
                    )

                    // Quick amount chips
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        listOf(1000.0, 5000.0, 10000.0, 25000.0).forEach { quickAmt ->
                            SuggestionChip(
                                onClick = { amountText = quickAmt.toInt().toString() },
                                label = { Text("${quickAmt.toInt()}", fontSize = 12.sp) }
                            )
                        }
                    }

                    OutlinedTextField(
                        value = note,
                        onValueChange = { note = it },
                        label = { Text("ملاحظة (اختياري)") },
                        placeholder = { Text("سبب التحويل أو رسالة") },
                        modifier = Modifier
                            .fillMaxWidth()
                            .testTag("transfer_note_input"),
                        shape = RoundedCornerShape(12.dp)
                    )

                    if (errorMessage != null) {
                        Surface(
                            color = Color(0xFFFEE2E2),
                            shape = RoundedCornerShape(10.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text(
                                text = errorMessage!!,
                                color = ErrorRed,
                                fontSize = 13.sp,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(12.dp)
                            )
                        }
                    }

                    Button(
                        onClick = {
                            val amt = amountText.toDoubleOrNull()
                            if (receiverPhone.trim().isEmpty()) {
                                errorMessage = "يرجى إدخال رقم هاتف المستلم."
                                return@Button
                            }
                            if (amt == null || amt <= 0) {
                                errorMessage = "يرجى إدخال مبلغ تحويل صحيح."
                                return@Button
                            }
                            errorMessage = null
                            isSubmitting = true
                            viewModel.sendTransfer(receiverPhone, amt, note.ifEmpty { null }) { success, refOrErr ->
                                isSubmitting = false
                                if (success) {
                                    transferredAmount = amt
                                    successReference = refOrErr
                                    amountText = ""
                                    note = ""
                                } else {
                                    errorMessage = refOrErr
                                }
                            }
                        },
                        enabled = !isSubmitting,
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(52.dp)
                            .testTag("transfer_submit_button"),
                        colors = ButtonDefaults.buttonColors(containerColor = EmeraldGreen),
                        shape = RoundedCornerShape(14.dp)
                    ) {
                        if (isSubmitting) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                        } else {
                            Text(
                                text = "تأكيد إرسال الأموال",
                                fontSize = 16.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                        }
                    }
                }
            }
        }
    }

    if (successReference != null) {
        SuccessReceiptDialog(
            title = "تم التحويل بنجاح!",
            reference = successReference!!,
            amount = transferredAmount,
            details = "تم خصم المبلغ من محفظتك وإيداعه في محفظة المستلم فورياً.",
            onDismiss = { successReference = null }
        )
    }
}
