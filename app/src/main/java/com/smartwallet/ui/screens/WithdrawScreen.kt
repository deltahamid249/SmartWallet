package com.smartwallet.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
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
import com.smartwallet.ui.components.StatusBadge
import com.smartwallet.ui.components.SuccessReceiptDialog
import com.smartwallet.ui.components.WalletTopAppBar
import com.smartwallet.ui.components.formatDate
import com.smartwallet.ui.components.formatSdg
import com.smartwallet.ui.theme.*

@Composable
fun WithdrawScreen(
    viewModel: SmartWalletViewModel,
    onBack: () -> Unit
) {
    val currentWallet by viewModel.currentWallet.collectAsState()
    val userWithdrawals by viewModel.userWithdrawals.collectAsState()
    val currentUser by viewModel.currentUser.collectAsState()

    var recipientName by remember { mutableStateOf(currentUser?.fullName ?: "") }
    var recipientPhone by remember { mutableStateOf(currentUser?.phone ?: "") }
    var method by remember { mutableStateOf("وكيل معتمد (Cash Agent)") }
    var amountText by remember { mutableStateOf("") }
    var note by remember { mutableStateOf("") }

    var errorMessage by remember { mutableStateOf<String?>(null) }
    var successReceipt by remember { mutableStateOf<Pair<String, Double>?>(null) }
    var isSubmitting by remember { mutableStateOf(false) }

    val methodOptions = listOf(
        "وكيل معتمد (Cash Agent)",
        "تحويل بنكي مباشر (بنكك)",
        "صراف آلي (ATM Code)"
    )

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "سحب الأموال",
                onBack = onBack
            )
        },
        containerColor = SlateBackground
    ) { padding ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .padding(16.dp)
                .testTag("withdraw_screen"),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Balance card
            item {
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
                            Text("الرصيد المتاح للسحب", color = Color(0xFF94A3B8), fontSize = 13.sp)
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "${formatSdg(currentWallet?.balance ?: 0.0)} SDG",
                                color = EmeraldLight,
                                fontSize = 22.sp,
                                fontWeight = FontWeight.ExtraBold
                            )
                        }
                        Text("💴", fontSize = 28.sp)
                    }
                }
            }

            // Withdrawal form
            item {
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(18.dp),
                    color = Color.White,
                    border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight),
                    shadowElevation = 2.dp
                ) {
                    Column(
                        modifier = Modifier.padding(18.dp),
                        verticalArrangement = Arrangement.spacedBy(14.dp)
                    ) {
                        Text(
                            text = "تقديم طلب سحب نقدي",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = TextPrimary
                        )

                        OutlinedTextField(
                            value = recipientName,
                            onValueChange = { recipientName = it },
                            label = { Text("اسم المستلم رباعي") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )

                        OutlinedTextField(
                            value = recipientPhone,
                            onValueChange = { recipientPhone = it },
                            label = { Text("رقم هاتف المستلم") },
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )

                        Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            Text("طريقة استلام المبلغ:", fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = TextSecondary)
                            methodOptions.forEach { opt ->
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    RadioButton(
                                        selected = method == opt,
                                        onClick = { method = opt }
                                    )
                                    Text(opt, fontSize = 13.sp, color = TextPrimary)
                                }
                            }
                        }

                        OutlinedTextField(
                            value = amountText,
                            onValueChange = { amountText = it },
                            label = { Text("المبلغ المراد سحبه (SDG)") },
                            placeholder = { Text("0.00") },
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            modifier = Modifier
                                .fillMaxWidth()
                                .testTag("withdraw_amount_input"),
                            shape = RoundedCornerShape(12.dp)
                        )

                        OutlinedTextField(
                            value = note,
                            onValueChange = { note = it },
                            label = { Text("ملاحظة أو تفاصيل الموقع") },
                            placeholder = { Text("مثال: فرع السوق العربي") },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )

                        if (errorMessage != null) {
                            Text(errorMessage!!, color = ErrorRed, fontSize = 13.sp, fontWeight = FontWeight.Bold)
                        }

                        Button(
                            onClick = {
                                val amt = amountText.toDoubleOrNull()
                                if (recipientName.trim().isEmpty() || recipientPhone.trim().isEmpty()) {
                                    errorMessage = "يرجى تعبئة اسم ورقم هاتف المستلم."
                                    return@Button
                                }
                                if (amt == null || amt <= 0) {
                                    errorMessage = "يرجى إدخال مبلغ سحب صحيح."
                                    return@Button
                                }
                                errorMessage = null
                                isSubmitting = true
                                viewModel.submitWithdrawal(amt, recipientName, recipientPhone, method, note.ifEmpty { null }) { success, refOrErr ->
                                    isSubmitting = false
                                    if (success) {
                                        successReceipt = Pair(refOrErr, amt)
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
                                .testTag("withdraw_submit_button"),
                            colors = ButtonDefaults.buttonColors(containerColor = NavyPrimary),
                            shape = RoundedCornerShape(14.dp)
                        ) {
                            if (isSubmitting) {
                                CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                            } else {
                                Text("إرسال طلب السحب", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }
            }

            // WITHDRAWALS HISTORY
            item {
                Spacer(modifier = Modifier.height(10.dp))
                Text(
                    text = "طلبات السحب السابقة",
                    fontSize = 17.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
            }

            if (userWithdrawals.isEmpty()) {
                item {
                    Text("لا توجد طلبات سحب سابقة.", color = TextSecondary, fontSize = 13.sp)
                }
            } else {
                items(userWithdrawals) { req ->
                    Surface(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(14.dp),
                        color = Color.White,
                        border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight)
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(14.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = req.withdrawalMethod,
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 14.sp,
                                    color = TextPrimary
                                )
                                Text(
                                    text = "المستلم: ${req.recipientName} (${req.recipientPhone})",
                                    fontSize = 12.sp,
                                    color = TextSecondary
                                )
                                Text(
                                    text = formatDate(req.createdAt),
                                    fontSize = 11.sp,
                                    color = TextSecondary
                                )
                            }
                            Column(horizontalAlignment = Alignment.End) {
                                Text(
                                    text = "-${formatSdg(req.amount)} SDG",
                                    fontWeight = FontWeight.ExtraBold,
                                    fontSize = 14.sp,
                                    color = TextPrimary
                                )
                                Spacer(modifier = Modifier.height(4.dp))
                                StatusBadge(req.status)
                            }
                        }
                    }
                }
            }
        }
    }

    if (successReceipt != null) {
        SuccessReceiptDialog(
            title = "تم استلام طلب السحب بنجاح!",
            reference = successReceipt!!.first,
            amount = successReceipt!!.second,
            details = "طلبك قيد المراجعة الإدارية. سيتم تجهيز المبلغ وإشعارك عند الاعتماد.",
            onDismiss = { successReceipt = null }
        )
    }
}
