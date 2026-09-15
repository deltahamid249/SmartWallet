package com.smartwallet.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.smartwallet.ui.SmartWalletViewModel
import com.smartwallet.ui.components.formatDate
import com.smartwallet.ui.components.formatSdg
import com.smartwallet.ui.theme.*

data class ServiceGridItem(
    val id: String,
    val icon: String,
    val title: String,
    val route: String,
    val isHighlighted: Boolean = false
)

@Composable
fun HomeScreen(
    viewModel: SmartWalletViewModel,
    onNavigate: (String) -> Unit
) {
    val currentUser by viewModel.currentUser.collectAsState()
    val currentWallet by viewModel.currentWallet.collectAsState()
    val transactions by viewModel.transactions.collectAsState()
    val notifications by viewModel.notifications.collectAsState()
    val unreadNotificationsCount = notifications.count { !it.isRead }

    val isAdmin = currentUser?.role == "admin"

    val serviceItems = listOf(
        ServiceGridItem("deposit", "💵", "إضافة الأموال", "deposit"),
        ServiceGridItem("withdraw", "💴", "سحب الأموال", "withdraw"),
        ServiceGridItem("transfer", "📤", "إرسال الأموال", "transfer"),
        ServiceGridItem("transactions", "📊", "سجل العمليات", "transactions"),
        ServiceGridItem("profile", "👤", "الملف الشخصي", "profile"),
        ServiceGridItem("payments", "🧾", "المدفوعات", "payments"),
        ServiceGridItem("services", "🛠️", "الخدمات", "services"),
        ServiceGridItem("notifications", "🔔", "الإشعارات", "notifications"),
        ServiceGridItem("settings", "⚙️", "الإعدادات", "settings"),
        ServiceGridItem("help", "❓", "المساعدة", "help"),
        ServiceGridItem("admin", "🛡️", "لوحة الإدارة", "admin", isHighlighted = true)
    )

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(SlateBackground)
            .testTag("home_screen"),
        contentPadding = PaddingValues(bottom = 32.dp)
    ) {
        // TOP NAVY HEADER
        item {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(bottomStart = 28.dp, bottomEnd = 28.dp))
                    .background(NavyPrimary)
                    .padding(horizontal = 20.dp, vertical = 22.dp)
            ) {
                Column(modifier = Modifier.fillMaxWidth()) {
                    // Top bar with Brand and User Avatar
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(48.dp, 44.dp)
                                    .clip(RoundedCornerShape(12.dp))
                                    .background(Color.White),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = "SDG",
                                    color = NavyPrimary,
                                    fontWeight = FontWeight.Black,
                                    fontSize = 16.sp
                                )
                            }
                            Column {
                                Text(
                                    text = "المحفظة الذكية",
                                    color = Color.White,
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 19.sp
                                )
                                Text(
                                    text = "الجنيه السوداني",
                                    color = Color(0xFF94A3B8),
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.Medium
                                )
                            }
                        }

                        // Profile Icon with notification dot
                        Box(
                            modifier = Modifier
                                .size(46.dp)
                                .clip(CircleShape)
                                .background(Color.White)
                                .clickable { onNavigate("profile") }
                                .testTag("profile_avatar_button"),
                            contentAlignment = Alignment.Center
                        ) {
                            Text("👤", fontSize = 22.sp)
                            if (unreadNotificationsCount > 0) {
                                Box(
                                    modifier = Modifier
                                        .size(12.dp)
                                        .align(Alignment.TopEnd)
                                        .clip(CircleShape)
                                        .background(ErrorRed)
                                )
                            }
                        }
                    }

                    Spacer(modifier = Modifier.height(20.dp))

                    // BALANCE CARD (Signature SmartWallet element)
                    Surface(
                        modifier = Modifier
                            .fillMaxWidth()
                            .testTag("balance_card"),
                        shape = RoundedCornerShape(22.dp),
                        color = Color.White,
                        shadowElevation = 8.dp
                    ) {
                        Column(
                            modifier = Modifier.padding(20.dp)
                        ) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(
                                    text = "الرصيد المتاح",
                                    fontSize = 14.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = TextSecondary
                                )
                                if (isAdmin) {
                                    Surface(
                                        color = Color(0xFFEFF6FF),
                                        shape = RoundedCornerShape(8.dp)
                                    ) {
                                        Text(
                                            text = "مشرف النظام 🛡️",
                                            color = InfoBlue,
                                            fontSize = 11.sp,
                                            fontWeight = FontWeight.Bold,
                                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                                        )
                                    }
                                }
                            }

                            Spacer(modifier = Modifier.height(6.dp))

                            Row(
                                verticalAlignment = Alignment.Bottom,
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                Text(
                                    text = formatSdg(currentWallet?.balance ?: 0.0),
                                    fontSize = 32.sp,
                                    fontWeight = FontWeight.ExtraBold,
                                    color = EmeraldGreen,
                                    modifier = Modifier.testTag("balance_amount")
                                )
                                Text(
                                    text = "SDG",
                                    fontSize = 16.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = NavyPrimary,
                                    modifier = Modifier.padding(bottom = 4.dp)
                                )
                            }

                            Spacer(modifier = Modifier.height(10.dp))

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(
                                    text = currentUser?.fullName ?: "مستخدم المحفظة",
                                    fontSize = 13.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = TextPrimary
                                )
                                Text(
                                    text = currentUser?.phone ?: "",
                                    fontSize = 12.sp,
                                    color = TextSecondary
                                )
                            }
                        }
                    }
                }
            }
        }

        // SERVICES SECTION TITLE
        item {
            PaddingValues(horizontal = 18.dp).let {
                Column(modifier = Modifier.padding(it)) {
                    Spacer(modifier = Modifier.height(20.dp))
                    Text(
                        text = "الخدمات",
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextPrimary
                    )
                    Spacer(modifier = Modifier.height(12.dp))
                }
            }
        }

        // SERVICES GRID (3 columns)
        item {
            val chunkedItems = serviceItems.chunked(3)
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                for (row in chunkedItems) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        for (item in row) {
                            Box(
                                modifier = Modifier
                                    .weight(1f)
                                    .height(115.dp)
                                    .clip(RoundedCornerShape(18.dp))
                                    .background(Color.White)
                                    .border(
                                        width = if (item.isHighlighted) 1.5.dp else 1.dp,
                                        color = if (item.isHighlighted) InfoBlue.copy(alpha = 0.4f) else BorderLight,
                                        shape = RoundedCornerShape(18.dp)
                                    )
                                    .clickable { onNavigate(item.route) }
                                    .testTag("service_card_${item.id}")
                                    .padding(8.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Column(
                                    horizontalAlignment = Alignment.CenterHorizontally,
                                    verticalArrangement = Arrangement.Center
                                ) {
                                    Box(
                                        modifier = Modifier
                                            .size(48.dp)
                                            .clip(RoundedCornerShape(14.dp))
                                            .background(if (item.isHighlighted) Color(0xFFEFF6FF) else Color(0xFFF1F5F9)),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Text(text = item.icon, fontSize = 24.sp)
                                    }
                                    Spacer(modifier = Modifier.height(8.dp))
                                    Text(
                                        text = item.title,
                                        fontSize = 12.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = if (item.isHighlighted) InfoBlue else TextPrimary,
                                        textAlign = TextAlign.Center,
                                        maxLines = 1
                                    )
                                }
                            }
                        }
                        // Fill empty slots in last row if not 3
                        val emptySlots = 3 - row.size
                        for (i in 0 until emptySlots) {
                            Spacer(modifier = Modifier.weight(1f))
                        }
                    }
                }
            }
        }

        // TRANSACTION HISTORY SHORTCUT CARD
        item {
            Spacer(modifier = Modifier.height(16.dp))
            Surface(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp)
                    .clip(RoundedCornerShape(18.dp))
                    .clickable { onNavigate("transactions") }
                    .testTag("history_shortcut_card"),
                shape = RoundedCornerShape(18.dp),
                color = Color.White,
                border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight),
                shadowElevation = 2.dp
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
                        horizontalArrangement = Arrangement.spacedBy(14.dp)
                    ) {
                        Box(
                            modifier = Modifier
                                .size(48.dp)
                                .clip(RoundedCornerShape(14.dp))
                                .background(Color(0xFFF1F5F9)),
                            contentAlignment = Alignment.Center
                        ) {
                            Text("📋", fontSize = 24.sp)
                        }
                        Column {
                            Text(
                                text = "سجل العمليات",
                                fontWeight = FontWeight.Bold,
                                fontSize = 15.sp,
                                color = TextPrimary
                            )
                            Text(
                                text = "عرض جميع عمليات المحفظة والتفاصيل",
                                fontSize = 12.sp,
                                color = TextSecondary
                            )
                        }
                    }
                    Text("←", fontSize = 22.sp, fontWeight = FontWeight.Bold, color = TextSecondary)
                }
            }
        }

        // RECENT TRANSACTIONS PREVIEW
        item {
            Spacer(modifier = Modifier.height(20.dp))
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 18.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "آخر العمليات",
                    fontSize = 17.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
                Text(
                    text = "عرض الكل",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = NavyLight,
                    modifier = Modifier.clickable { onNavigate("transactions") }
                )
            }
            Spacer(modifier = Modifier.height(10.dp))
        }

        if (transactions.isEmpty()) {
            item {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(32.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = "لا توجد عمليات سابقة بعد.",
                        color = TextSecondary,
                        fontSize = 14.sp
                    )
                }
            }
        } else {
            items(transactions.take(4)) { tx ->
                val isPositive = tx.type in listOf("deposit", "transfer_in")
                Surface(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 5.dp),
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
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            val icon = when (tx.type) {
                                "deposit" -> "💵"
                                "withdraw" -> "💴"
                                "transfer_out" -> "📤"
                                "transfer_in" -> "📥"
                                "payment" -> "🧾"
                                "recharge" -> "📱"
                                "electricity" -> "⚡"
                                else -> "🛠️"
                            }
                            Box(
                                modifier = Modifier
                                    .size(40.dp)
                                    .clip(RoundedCornerShape(10.dp))
                                    .background(if (isPositive) Color(0xFFDCFCE7) else Color(0xFFF1F5F9)),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(icon, fontSize = 20.sp)
                            }
                            Column {
                                Text(
                                    text = tx.description,
                                    fontSize = 13.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = TextPrimary,
                                    maxLines = 1
                                )
                                Text(
                                    text = formatDate(tx.createdAt),
                                    fontSize = 11.sp,
                                    color = TextSecondary
                                )
                            }
                        }

                        Text(
                            text = (if (isPositive) "+" else "-") + formatSdg(tx.amount) + " SDG",
                            fontSize = 14.sp,
                            fontWeight = FontWeight.Bold,
                            color = if (isPositive) EmeraldGreen else TextPrimary
                        )
                    }
                }
            }
        }
    }
}
