package com.smartwallet.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.smartwallet.data.TransactionEntity
import com.smartwallet.ui.SmartWalletViewModel
import com.smartwallet.ui.components.StatusBadge
import com.smartwallet.ui.components.WalletTopAppBar
import com.smartwallet.ui.components.formatDate
import com.smartwallet.ui.components.formatSdg
import com.smartwallet.ui.theme.*

@Composable
fun TransactionsScreen(
    viewModel: SmartWalletViewModel,
    onBack: () -> Unit
) {
    val transactions by viewModel.transactions.collectAsState()

    var selectedFilter by remember { mutableStateOf("all") }
    var selectedTxForDetails by remember { mutableStateOf<TransactionEntity?>(null) }

    val filterOptions = listOf(
        Pair("all", "الكل"),
        Pair("deposit", "إيداع"),
        Pair("withdraw", "سحب"),
        Pair("transfer_out", "تحويل صادر"),
        Pair("transfer_in", "تحويل وارد"),
        Pair("payment", "مدفوعات"),
        Pair("service", "خدمات")
    )

    val filteredTransactions = remember(transactions, selectedFilter) {
        if (selectedFilter == "all") {
            transactions
        } else if (selectedFilter == "service") {
            transactions.filter { it.type in listOf("recharge", "electricity", "internet", "bills", "education", "government") }
        } else {
            transactions.filter { it.type == selectedFilter }
        }
    }

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "سجل العمليات",
                onBack = onBack
            )
        },
        containerColor = SlateBackground
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .testTag("transactions_screen")
        ) {
            // Filter chips horizontal row
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState())
                    .padding(horizontal = 16.dp, vertical = 12.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                filterOptions.forEach { opt ->
                    FilterChip(
                        selected = selectedFilter == opt.first,
                        onClick = { selectedFilter = opt.first },
                        label = { Text(opt.second, fontSize = 13.sp, fontWeight = FontWeight.Bold) },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = NavyPrimary,
                            selectedLabelColor = Color.White
                        )
                    )
                }
            }

            if (filteredTransactions.isEmpty()) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(32.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = "لا توجد معاملات تطابق هذا الفلتر.",
                        color = TextSecondary,
                        fontSize = 14.sp
                    )
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    items(filteredTransactions) { tx ->
                        val isPositive = tx.type in listOf("deposit", "transfer_in")
                        val icon = when (tx.type) {
                            "deposit" -> "💵"
                            "withdraw" -> "💴"
                            "transfer_out" -> "📤"
                            "transfer_in" -> "📥"
                            "payment" -> "🧾"
                            "recharge" -> "📱"
                            "electricity" -> "⚡"
                            "internet" -> "🌐"
                            "bills" -> "🧾"
                            "education" -> "🎓"
                            "government" -> "🏛️"
                            else -> "💳"
                        }

                        Surface(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(RoundedCornerShape(16.dp))
                                .clickable { selectedTxForDetails = tx }
                                .testTag("transaction_row_${tx.id}"),
                            shape = RoundedCornerShape(16.dp),
                            color = Color.White,
                            border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight),
                            shadowElevation = 1.dp
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(16.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                                ) {
                                    Box(
                                        modifier = Modifier
                                            .size(44.dp)
                                            .clip(RoundedCornerShape(12.dp))
                                            .background(if (isPositive) Color(0xFFDCFCE7) else Color(0xFFF1F5F9)),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Text(icon, fontSize = 22.sp)
                                    }
                                    Column {
                                        Text(
                                            text = tx.description,
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 14.sp,
                                            color = TextPrimary
                                        )
                                        Spacer(modifier = Modifier.height(2.dp))
                                        Text(
                                            text = "${formatDate(tx.createdAt)} • ${tx.reference}",
                                            fontSize = 11.sp,
                                            color = TextSecondary
                                        )
                                    }
                                }

                                Column(horizontalAlignment = Alignment.End) {
                                    Text(
                                        text = (if (isPositive) "+" else "-") + "${formatSdg(tx.amount)} SDG",
                                        fontWeight = FontWeight.ExtraBold,
                                        fontSize = 14.sp,
                                        color = if (isPositive) EmeraldGreen else TextPrimary
                                    )
                                    Spacer(modifier = Modifier.height(4.dp))
                                    StatusBadge(tx.status)
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Full transaction receipt dialog
    if (selectedTxForDetails != null) {
        val tx = selectedTxForDetails!!
        val isPositive = tx.type in listOf("deposit", "transfer_in")
        AlertDialog(
            onDismissRequest = { selectedTxForDetails = null },
            confirmButton = {
                Button(
                    onClick = { selectedTxForDetails = null },
                    colors = ButtonDefaults.buttonColors(containerColor = NavyPrimary),
                    shape = RoundedCornerShape(12.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("إغلاق", fontWeight = FontWeight.Bold)
                }
            },
            title = {
                Text(
                    text = "تفاصيل المعاملة",
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.sp
                )
            },
            text = {
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Surface(
                        color = SlateBackground,
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(
                            modifier = Modifier.padding(14.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Text("المبلغ:", fontSize = 12.sp, color = TextSecondary)
                            Text(
                                text = (if (isPositive) "+" else "-") + "${formatSdg(tx.amount)} SDG",
                                fontSize = 24.sp,
                                fontWeight = FontWeight.ExtraBold,
                                color = if (isPositive) EmeraldGreen else TextPrimary
                            )

                            HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                            Text("الوصف: ${tx.description}", fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = TextPrimary)
                            Text("المرجع: ${tx.reference}", fontSize = 12.sp, color = NavyPrimary, fontWeight = FontWeight.Bold)
                            Text("التاريخ: ${formatDate(tx.createdAt)}", fontSize = 12.sp, color = TextSecondary)
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Text("الحالة: ", fontSize = 12.sp, color = TextSecondary)
                                StatusBadge(tx.status)
                            }
                        }
                    }
                }
            },
            containerColor = Color.White,
            shape = RoundedCornerShape(20.dp)
        )
    }
}
