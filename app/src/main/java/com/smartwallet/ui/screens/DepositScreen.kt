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
import androidx.compose.ui.draw.clip
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
fun DepositScreen(
    viewModel: SmartWalletViewModel,
    onBack: () -> Unit
) {
    val currentWallet by viewModel.currentWallet.collectAsState()
    val userDeposits by viewModel.userDeposits.collectAsState()

    var selectedTab by remember { mutableIntStateOf(0) } // 0: Instant Demo, 1: Bank Deposit Request

    // Instant deposit state
    var instantAmountText by remember { mutableStateOf("") }
    // Bank deposit request state
    var bankName by remember { mutableStateOf("بنك الخرطوم (بنكك)") }
    var senderName by remember { mutableStateOf("") }
    var bankRef by remember { mutableStateOf("") }
    var bankAmountText by remember { mutableStateOf("") }
    var bankNote by remember { mutableStateOf("") }

    var errorMessage by remember { mutableStateOf<String?>(null) }
    var successReceipt by remember { mutableStateOf<Pair<String, Double>?>(null) }
    var isSubmitting by remember { mutableStateOf(false) }

    val bankOptions = listOf(
        "بنك الخرطوم (بنكك)",
        "بنك فيصل الإسلامي (فوري)",
        "بنك أمدرمان الوطني (أو كاش)",
        "بنك النيلين",
        "بنك المال المتحد"
    )

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "إضافة الأموال",
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
                .testTag("deposit_screen"),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Balance banner
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
                            Text("الرصيد الحالي", color = Color(0xFF94A3B8), fontSize = 13.sp)
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "${formatSdg(currentWallet?.balance ?: 0.0)} SDG",
                                color = EmeraldLight,
                                fontSize = 22.sp,
                                fontWeight = FontWeight.ExtraBold
                            )
                        }
                        Text("💵", fontSize = 28.sp)
                    }
                }
            }

            // Tab selector
            item {
                TabRow(
                    selectedTabIndex = selectedTab,
                    containerColor = Color.White,
                    contentColor = NavyPrimary,
                    modifier = Modifier.clip(RoundedCornerShape(14.dp))
                ) {
                    Tab(
                        selected = selectedTab == 0,
                        onClick = { selectedTab = 0; errorMessage = null },
                        text = { Text("إيداع فوري (تجريبي)", fontWeight = FontWeight.Bold) },
                        modifier = Modifier.testTag("tab_instant_deposit")
                    )
                    Tab(
                        selected = selectedTab == 1,
                        onClick = { selectedTab = 1; errorMessage = null },
                        text = { Text("إيداع بنكي رسمي", fontWeight = FontWeight.Bold) },
                        modifier = Modifier.testTag("tab_bank_deposit")
                    )
                }
            }

            if (selectedTab == 0) {
                // INSTANT DEMO DEPOSIT
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
                            verticalArrangement = Arrangement.spacedBy(16.dp)
                        ) {
                            Text(
                                text = "شحن المحفظة برصيد تجريبي مباشر",
                                fontSize = 15.sp,
                                fontWeight = FontWeight.Bold,
                                color = TextPrimary
                            )
                            Text(
                                text = "ميزة تجريبية سريعة تسمح لك بتعبئة رصيد المحفظة فوراً لتجربة جميع الخدمات والتحويلات.",
                                fontSize = 13.sp,
                                color = TextSecondary,
                                lineHeight = 18.sp
                            )

                            OutlinedTextField(
                                value = instantAmountText,
                                onValueChange = { instantAmountText = it },
                                label = { Text("المبلغ المراد إيداعه (SDG)") },
                                placeholder = { Text("مثال: 25000") },
                                singleLine = true,
                                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .testTag("instant_deposit_amount_input"),
                                shape = RoundedCornerShape(12.dp)
                            )

                            // Quick chips
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                listOf(10000.0, 25000.0, 50000.0, 100000.0).forEach { quickAmt ->
                                    SuggestionChip(
                                        onClick = { instantAmountText = quickAmt.toInt().toString() },
                                        label = { Text("+${quickAmt.toInt()}", fontSize = 12.sp) }
                                    )
                                }
                            }

                            if (errorMessage != null) {
                                Text(errorMessage!!, color = ErrorRed, fontSize = 13.sp, fontWeight = FontWeight.Bold)
                            }

                            Button(
                                onClick = {
                                    val amt = instantAmountText.toDoubleOrNull()
                                    if (amt == null || amt <= 0) {
                                        errorMessage = "يرجى إدخال مبلغ إيداع صحيح."
                                        return@Button
                                    }
                                    errorMessage = null
                                    isSubmitting = true
                                    viewModel.instantDeposit(amt) { success, refOrErr ->
                                        isSubmitting = false
                                        if (success) {
                                            successReceipt = Pair(refOrErr, amt)
                                            instantAmountText = ""
                                        } else {
                                            errorMessage = refOrErr
                                        }
                                    }
                                },
                                enabled = !isSubmitting,
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(52.dp)
                                    .testTag("instant_deposit_button"),
                                colors = ButtonDefaults.buttonColors(containerColor = EmeraldGreen),
                                shape = RoundedCornerShape(14.dp)
                            ) {
                                if (isSubmitting) {
                                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                                } else {
                                    Text("تأكيد الإيداع الفوري", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                                }
                            }
                        }
                    }
                }
            } else {
                // BANK DEPOSIT REQUEST
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
                                text = "تقديم إشعار تحويل بنكي للمراجعة",
                                fontSize = 15.sp,
                                fontWeight = FontWeight.Bold,
                                color = TextPrimary
                            )

                            Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                                Text("اختر البنك:", fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = TextSecondary)
                                bankOptions.forEach { opt ->
                                    Row(
                                        verticalAlignment = Alignment.CenterVertically,
                                        modifier = Modifier.fillMaxWidth()
                                    ) {
                                        RadioButton(
                                            selected = bankName == opt,
                                            onClick = { bankName = opt }
                                        )
                                        Text(opt, fontSize = 13.sp, color = TextPrimary)
                                    }
                                }
                            }

                            OutlinedTextField(
                                value = senderName,
                                onValueChange = { senderName = it },
                                label = { Text("اسم صاحب الحساب المحوِّل") },
                                singleLine = true,
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(12.dp)
                            )

                            OutlinedTextField(
                                value = bankRef,
                                onValueChange = { bankRef = it },
                                label = { Text("رقم العملية / الإشعار البنكي") },
                                placeholder = { Text("مثال: TRX-8912304") },
                                singleLine = true,
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(12.dp)
                            )

                            OutlinedTextField(
                                value = bankAmountText,
                                onValueChange = { bankAmountText = it },
                                label = { Text("المبلغ المودع (SDG)") },
                                placeholder = { Text("0.00") },
                                singleLine = true,
                                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(12.dp)
                            )

                            OutlinedTextField(
                                value = bankNote,
                                onValueChange = { bankNote = it },
                                label = { Text("ملاحظات إضافية") },
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(12.dp)
                            )

                            if (errorMessage != null) {
                                Text(errorMessage!!, color = ErrorRed, fontSize = 13.sp, fontWeight = FontWeight.Bold)
                            }

                            Button(
                                onClick = {
                                    val amt = bankAmountText.toDoubleOrNull()
                                    if (senderName.trim().isEmpty() || bankRef.trim().isEmpty()) {
                                        errorMessage = "يرجى تعبئة اسم المحول ورقم الإشعار البنكي."
                                        return@Button
                                    }
                                    if (amt == null || amt <= 0) {
                                        errorMessage = "يرجى إدخال مبلغ إيداع صحيح."
                                        return@Button
                                    }
                                    errorMessage = null
                                    isSubmitting = true
                                    viewModel.submitBankDeposit(bankName, senderName, bankRef, amt, bankNote.ifEmpty { null }) { success, refOrErr ->
                                        isSubmitting = false
                                        if (success) {
                                            successReceipt = Pair(refOrErr, amt)
                                            senderName = ""
                                            bankRef = ""
                                            bankAmountText = ""
                                            bankNote = ""
                                        } else {
                                            errorMessage = refOrErr
                                        }
                                    }
                                },
                                enabled = !isSubmitting,
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(52.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = NavyPrimary),
                                shape = RoundedCornerShape(14.dp)
                            ) {
                                if (isSubmitting) {
                                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                                } else {
                                    Text("إرسال طلب الإيداع للمراجعة", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                                }
                            }
                        }
                    }
                }
            }

            // PREVIOUS DEPOSITS LIST
            item {
                Spacer(modifier = Modifier.height(10.dp))
                Text(
                    text = "طلبات الإيداع السابقة",
                    fontSize = 17.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
            }

            if (userDeposits.isEmpty()) {
                item {
                    Text("لا توجد طلبات إيداع سابقة.", color = TextSecondary, fontSize = 13.sp)
                }
            } else {
                items(userDeposits) { req ->
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
                                    text = req.bankName,
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 14.sp,
                                    color = TextPrimary
                                )
                                Text(
                                    text = "إشعار: ${req.bankReference} • ${formatDate(req.createdAt)}",
                                    fontSize = 11.sp,
                                    color = TextSecondary
                                )
                                if (req.reviewNote != null) {
                                    Text(
                                        text = "ملاحظة المشرف: ${req.reviewNote}",
                                        fontSize = 11.sp,
                                        color = InfoBlue
                                    )
                                }
                            }
                            Column(horizontalAlignment = Alignment.End) {
                                Text(
                                    text = "${formatSdg(req.amount)} SDG",
                                    fontWeight = FontWeight.ExtraBold,
                                    fontSize = 14.sp,
                                    color = EmeraldGreen
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
            title = if (selectedTab == 0) "تم الإيداع الفوري بنجاح!" else "تم استلام طلب الإيداع!",
            reference = successReceipt!!.first,
            amount = successReceipt!!.second,
            details = if (selectedTab == 0) "تمت إضافة الرصيد إلى محفظتك بنجاح." else "طلبك قيد المراجعة لدى فريق الإدارة وسيتم إشعارك فور الاعتماد.",
            onDismiss = { successReceipt = null }
        )
    }
}
