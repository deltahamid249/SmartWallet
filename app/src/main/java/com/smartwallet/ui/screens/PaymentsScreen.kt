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
import com.smartwallet.ui.components.SuccessReceiptDialog
import com.smartwallet.ui.components.WalletTopAppBar
import com.smartwallet.ui.components.formatDate
import com.smartwallet.ui.components.formatSdg
import com.smartwallet.ui.theme.*

data class SuggestedMerchant(val name: String, val account: String, val category: String)

@Composable
fun PaymentsScreen(
    viewModel: SmartWalletViewModel,
    onBack: () -> Unit
) {
    val currentWallet by viewModel.currentWallet.collectAsState()
    val transactions by viewModel.transactions.collectAsState()
    val paymentTransactions = transactions.filter { it.type == "payment" }

    var merchantName by remember { mutableStateOf("") }
    var merchantAccount by remember { mutableStateOf("") }
    var amountText by remember { mutableStateOf("") }

    var errorMessage by remember { mutableStateOf<String?>(null) }
    var successReceipt by remember { mutableStateOf<Pair<String, Double>?>(null) }
    var isSubmitting by remember { mutableStateOf(false) }

    val suggestedMerchants = listOf(
        SuggestedMerchant("سوبرماركت الواحة", "MRC-10023", "تسوق ومواد غذائية"),
        SuggestedMerchant("صيدلية النيل الكبرى", "MRC-40911", "أدوية وصحة"),
        SuggestedMerchant("مطاعم ومطابخ الشام", "MRC-50388", "مطاعم وكافيهات"),
        SuggestedMerchant("إلكترونيات الخرطوم", "MRC-90212", "أجهزة وإلكترونيات")
    )

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "المدفوعات والمشتريات",
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
                .testTag("payments_screen"),
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
                            Text("الرصيد المتاح للمدفوعات", color = Color(0xFF94A3B8), fontSize = 13.sp)
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "${formatSdg(currentWallet?.balance ?: 0.0)} SDG",
                                color = EmeraldLight,
                                fontSize = 22.sp,
                                fontWeight = FontWeight.ExtraBold
                            )
                        }
                        Text("🧾", fontSize = 28.sp)
                    }
                }
            }

            // Quick merchant selector
            item {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(
                        text = "تجار ومتاجر مقترحة:",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextSecondary
                    )
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        suggestedMerchants.take(2).forEach { merchant ->
                            FilterChip(
                                selected = merchantAccount == merchant.account,
                                onClick = {
                                    merchantName = merchant.name
                                    merchantAccount = merchant.account
                                },
                                label = { Text(merchant.name, fontSize = 12.sp) }
                            )
                        }
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        suggestedMerchants.drop(2).forEach { merchant ->
                            FilterChip(
                                selected = merchantAccount == merchant.account,
                                onClick = {
                                    merchantName = merchant.name
                                    merchantAccount = merchant.account
                                },
                                label = { Text(merchant.name, fontSize = 12.sp) }
                            )
                        }
                    }
                }
            }

            // Payment Form
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
                            text = "دفع فاتورة لتاجر / متجر",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = TextPrimary
                        )

                        OutlinedTextField(
                            value = merchantName,
                            onValueChange = { merchantName = it },
                            label = { Text("اسم التاجر أو المتجر") },
                            placeholder = { Text("مثال: سوبرماركت الواحة") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )

                        OutlinedTextField(
                            value = merchantAccount,
                            onValueChange = { merchantAccount = it },
                            label = { Text("رقم حساب أو كود التاجر") },
                            placeholder = { Text("مثال: MRC-10023") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )

                        OutlinedTextField(
                            value = amountText,
                            onValueChange = { amountText = it },
                            label = { Text("مبلغ الفاتورة (SDG)") },
                            placeholder = { Text("0.00") },
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            modifier = Modifier
                                .fillMaxWidth()
                                .testTag("payment_amount_input"),
                            shape = RoundedCornerShape(12.dp)
                        )

                        if (errorMessage != null) {
                            Text(errorMessage!!, color = ErrorRed, fontSize = 13.sp, fontWeight = FontWeight.Bold)
                        }

                        Button(
                            onClick = {
                                val amt = amountText.toDoubleOrNull()
                                if (merchantName.trim().isEmpty() || merchantAccount.trim().isEmpty()) {
                                    errorMessage = "يرجى تعبئة اسم التاجر ورقم حسابه."
                                    return@Button
                                }
                                if (amt == null || amt <= 0) {
                                    errorMessage = "يرجى إدخال مبلغ صحيح."
                                    return@Button
                                }
                                errorMessage = null
                                isSubmitting = true
                                viewModel.payMerchant(merchantName, merchantAccount, amt) { success, refOrErr ->
                                    isSubmitting = false
                                    if (success) {
                                        successReceipt = Pair(refOrErr, amt)
                                        amountText = ""
                                    } else {
                                        errorMessage = refOrErr
                                    }
                                }
                            },
                            enabled = !isSubmitting,
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(52.dp)
                                .testTag("pay_merchant_button"),
                            colors = ButtonDefaults.buttonColors(containerColor = EmeraldGreen),
                            shape = RoundedCornerShape(14.dp)
                        ) {
                            if (isSubmitting) {
                                CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                            } else {
                                Text("تأكيد دفع الفاتورة", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }
            }

            // Recent payments list
            item {
                Spacer(modifier = Modifier.height(10.dp))
                Text(
                    text = "سجل المدفوعات السابقة",
                    fontSize = 17.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
            }

            if (paymentTransactions.isEmpty()) {
                item {
                    Text("لا توجد مدفوعات سابقة.", color = TextSecondary, fontSize = 13.sp)
                }
            } else {
                items(paymentTransactions) { tx ->
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
                                    text = tx.description,
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 13.sp,
                                    color = TextPrimary
                                )
                                Text(
                                    text = "المرجع: ${tx.reference} • ${formatDate(tx.createdAt)}",
                                    fontSize = 11.sp,
                                    color = TextSecondary
                                )
                            }
                            Text(
                                text = "-${formatSdg(tx.amount)} SDG",
                                fontWeight = FontWeight.ExtraBold,
                                fontSize = 14.sp,
                                color = TextPrimary
                            )
                        }
                    }
                }
            }
        }
    }

    if (successReceipt != null) {
        SuccessReceiptDialog(
            title = "تمت عملية الدفع بنجاح!",
            reference = successReceipt!!.first,
            amount = successReceipt!!.second,
            details = "تم سداد الفاتورة لـ $merchantName وحساب $merchantAccount مباشرة.",
            onDismiss = { successReceipt = null }
        )
    }
}
