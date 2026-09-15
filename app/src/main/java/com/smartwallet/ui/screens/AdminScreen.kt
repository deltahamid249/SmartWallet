package com.smartwallet.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
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
import com.smartwallet.ui.SmartWalletViewModel
import com.smartwallet.ui.components.StatusBadge
import com.smartwallet.ui.components.WalletTopAppBar
import com.smartwallet.ui.components.formatDate
import com.smartwallet.ui.components.formatSdg
import com.smartwallet.ui.theme.*

@Composable
fun AdminScreen(
    viewModel: SmartWalletViewModel,
    onBack: () -> Unit
) {
    var selectedTab by remember { mutableIntStateOf(0) } // 0: Overview, 1: Deposits, 2: Withdrawals, 3: Audit Logs

    val userCount by viewModel.userCount.collectAsState()
    val activeUserCount by viewModel.activeUserCount.collectAsState()
    val totalCirculatingBalance by viewModel.totalCirculatingBalance.collectAsState()
    val pendingDepositCount by viewModel.pendingDepositCount.collectAsState()
    val pendingWithdrawalCount by viewModel.pendingWithdrawalCount.collectAsState()

    val allDeposits by viewModel.allDeposits.collectAsState()
    val allWithdrawals by viewModel.allWithdrawals.collectAsState()
    val allAuditLogs by viewModel.allAuditLogs.collectAsState()

    var statusMessage by remember { mutableStateOf<String?>(null) }
    var isProcessing by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "لوحة إدارة النظام",
                onBack = onBack
            )
        },
        containerColor = SlateBackground
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .testTag("admin_screen")
        ) {
            // Admin Tab Bar
            ScrollableTabRow(
                selectedTabIndex = selectedTab,
                containerColor = Color.White,
                contentColor = NavyPrimary,
                edgePadding = 12.dp
            ) {
                Tab(
                    selected = selectedTab == 0,
                    onClick = { selectedTab = 0; statusMessage = null },
                    text = { Text("الإحصائيات", fontWeight = FontWeight.Bold, fontSize = 13.sp) }
                )
                Tab(
                    selected = selectedTab == 1,
                    onClick = { selectedTab = 1; statusMessage = null },
                    text = {
                        Text(
                            "طلبات الإيداع ($pendingDepositCount)",
                            fontWeight = FontWeight.Bold,
                            fontSize = 13.sp,
                            color = if (pendingDepositCount > 0) EmeraldGreen else TextPrimary
                        )
                    }
                )
                Tab(
                    selected = selectedTab == 2,
                    onClick = { selectedTab = 2; statusMessage = null },
                    text = {
                        Text(
                            "طلبات السحب ($pendingWithdrawalCount)",
                            fontWeight = FontWeight.Bold,
                            fontSize = 13.sp,
                            color = if (pendingWithdrawalCount > 0) WarningAmber else TextPrimary
                        )
                    }
                )
                Tab(
                    selected = selectedTab == 3,
                    onClick = { selectedTab = 3; statusMessage = null },
                    text = { Text("سجل العمليات الإدارية", fontWeight = FontWeight.Bold, fontSize = 13.sp) }
                )
            }

            if (statusMessage != null) {
                Surface(
                    color = EmeraldContainer,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp),
                    shape = RoundedCornerShape(10.dp)
                ) {
                    Text(
                        text = statusMessage!!,
                        color = EmeraldDark,
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(12.dp)
                    )
                }
            }

            when (selectedTab) {
                0 -> {
                    // STATS OVERVIEW
                    LazyColumn(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(14.dp)
                    ) {
                        item {
                            Surface(
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(18.dp),
                                color = NavyPrimary,
                                shadowElevation = 4.dp
                            ) {
                                Column(modifier = Modifier.padding(20.dp)) {
                                    Text("إجمالي الأرصدة المتداولة بالنظام", color = Color(0xFF94A3B8), fontSize = 13.sp)
                                    Spacer(modifier = Modifier.height(6.dp))
                                    Text(
                                        text = "${formatSdg(totalCirculatingBalance ?: 0.0)} SDG",
                                        color = EmeraldLight,
                                        fontSize = 24.sp,
                                        fontWeight = FontWeight.ExtraBold
                                    )
                                }
                            }
                        }

                        item {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                Surface(
                                    modifier = Modifier
                                        .weight(1f)
                                        .height(110.dp),
                                    shape = RoundedCornerShape(16.dp),
                                    color = Color.White,
                                    border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight)
                                ) {
                                    Column(
                                        modifier = Modifier.padding(14.dp),
                                        verticalArrangement = Arrangement.Center
                                    ) {
                                        Text("إجمالي المستخدمين", fontSize = 12.sp, color = TextSecondary)
                                        Spacer(modifier = Modifier.height(6.dp))
                                        Text("$userCount مستخدم", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
                                        Text("$activeUserCount نشط", fontSize = 11.sp, color = EmeraldGreen, fontWeight = FontWeight.Bold)
                                    }
                                }

                                Surface(
                                    modifier = Modifier
                                        .weight(1f)
                                        .height(110.dp),
                                    shape = RoundedCornerShape(16.dp),
                                    color = Color.White,
                                    border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight)
                                ) {
                                    Column(
                                        modifier = Modifier.padding(14.dp),
                                        verticalArrangement = Arrangement.Center
                                    ) {
                                        Text("إيداعات قيد المراجعة", fontSize = 12.sp, color = TextSecondary)
                                        Spacer(modifier = Modifier.height(6.dp))
                                        Text("$pendingDepositCount طلب", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = EmeraldDark)
                                    }
                                }
                            }
                        }

                        item {
                            Surface(
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(16.dp),
                                color = Color.White,
                                border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight)
                            ) {
                                Column(
                                    modifier = Modifier.padding(16.dp),
                                    verticalArrangement = Arrangement.spacedBy(8.dp)
                                ) {
                                    Text("سحوبات نقدية معلقة", fontSize = 13.sp, color = TextSecondary)
                                    Text("$pendingWithdrawalCount طلبات سحب", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = WarningAmber)
                                }
                            }
                        }
                    }
                }
                1 -> {
                    // DEPOSITS MANAGEMENT
                    if (allDeposits.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text("لا توجد طلبات إيداع مسجلة بالنظام.", color = TextSecondary)
                        }
                    } else {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            items(allDeposits) { dep ->
                                Surface(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(16.dp),
                                    color = Color.White,
                                    border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight)
                                ) {
                                    Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text(dep.bankName, fontWeight = FontWeight.Bold, fontSize = 15.sp, color = TextPrimary)
                                            StatusBadge(dep.status)
                                        }

                                        Text("المودع: ${dep.senderName} • إشعار: ${dep.bankReference}", fontSize = 12.sp, color = TextSecondary)
                                        Text("المبلغ: ${formatSdg(dep.amount)} SDG", fontWeight = FontWeight.ExtraBold, fontSize = 16.sp, color = EmeraldGreen)

                                        if (dep.status == "pending") {
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                                            ) {
                                                Button(
                                                    onClick = {
                                                        isProcessing = true
                                                        viewModel.approveDeposit(dep.id) { success, msg ->
                                                            isProcessing = false
                                                            statusMessage = msg
                                                        }
                                                    },
                                                    colors = ButtonDefaults.buttonColors(containerColor = EmeraldGreen),
                                                    shape = RoundedCornerShape(10.dp),
                                                    modifier = Modifier.weight(1f)
                                                ) {
                                                    Text("موافقة وإيداع الرصيد", fontSize = 13.sp)
                                                }

                                                OutlinedButton(
                                                    onClick = {
                                                        isProcessing = true
                                                        viewModel.rejectDeposit(dep.id, "بيانات التحويل غير مطابقة") { success, msg ->
                                                            isProcessing = false
                                                            statusMessage = msg
                                                        }
                                                    },
                                                    shape = RoundedCornerShape(10.dp),
                                                    colors = ButtonDefaults.outlinedButtonColors(contentColor = ErrorRed),
                                                    modifier = Modifier.weight(1f)
                                                ) {
                                                    Text("رفض الطلب", fontSize = 13.sp)
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                2 -> {
                    // WITHDRAWALS MANAGEMENT
                    if (allWithdrawals.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text("لا توجد طلبات سحب مسجلة.", color = TextSecondary)
                        }
                    } else {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            items(allWithdrawals) { with ->
                                Surface(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(16.dp),
                                    color = Color.White,
                                    border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight)
                                ) {
                                    Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text(with.withdrawalMethod, fontWeight = FontWeight.Bold, fontSize = 15.sp, color = TextPrimary)
                                            StatusBadge(with.status)
                                        }

                                        Text("المستلم: ${with.recipientName} (${with.recipientPhone})", fontSize = 12.sp, color = TextSecondary)
                                        Text("المبلغ: ${formatSdg(with.amount)} SDG", fontWeight = FontWeight.ExtraBold, fontSize = 16.sp, color = TextPrimary)

                                        if (with.status == "pending") {
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                                            ) {
                                                Button(
                                                    onClick = {
                                                        isProcessing = true
                                                        viewModel.approveWithdrawal(with.id) { success, msg ->
                                                            isProcessing = false
                                                            statusMessage = msg
                                                        }
                                                    },
                                                    colors = ButtonDefaults.buttonColors(containerColor = EmeraldGreen),
                                                    shape = RoundedCornerShape(10.dp),
                                                    modifier = Modifier.weight(1f)
                                                ) {
                                                    Text("اعتماد السحب", fontSize = 13.sp)
                                                }

                                                OutlinedButton(
                                                    onClick = {
                                                        isProcessing = true
                                                        viewModel.rejectWithdrawal(with.id, "عدم توفر سيولة لدى الوكيل") { success, msg ->
                                                            isProcessing = false
                                                            statusMessage = msg
                                                        }
                                                    },
                                                    shape = RoundedCornerShape(10.dp),
                                                    colors = ButtonDefaults.outlinedButtonColors(contentColor = ErrorRed),
                                                    modifier = Modifier.weight(1f)
                                                ) {
                                                    Text("رفض", fontSize = 13.sp)
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                3 -> {
                    // AUDIT LOGS
                    if (allAuditLogs.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text("لا توجد سجلات تدقيق.", color = TextSecondary)
                        }
                    } else {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            items(allAuditLogs) { log ->
                                Surface(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(14.dp),
                                    color = Color.White,
                                    border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight)
                                ) {
                                    Column(modifier = Modifier.padding(14.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Text(log.action, fontWeight = FontWeight.Bold, fontSize = 13.sp, color = NavyPrimary)
                                            Text(formatDate(log.createdAt), fontSize = 11.sp, color = TextMuted)
                                        }
                                        Spacer(modifier = Modifier.height(4.dp))
                                        Text(log.description, fontSize = 13.sp, color = TextPrimary)
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
