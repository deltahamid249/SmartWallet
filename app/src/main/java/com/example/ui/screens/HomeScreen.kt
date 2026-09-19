package com.example.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountBalance
import androidx.compose.material.icons.filled.ArrowDownward
import androidx.compose.material.icons.filled.ArrowUpward
import androidx.compose.material.icons.filled.ElectricBolt
import androidx.compose.material.icons.filled.HelpOutline
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Language
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Payment
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.PhoneAndroid
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Send
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Shield
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.ui.theme.BorderLight
import com.example.ui.theme.EmeraldGreen
import com.example.ui.theme.EmeraldLight
import com.example.ui.theme.Navy700
import com.example.ui.theme.Navy800
import com.example.ui.theme.Navy900
import com.example.ui.theme.SoftGold
import com.example.ui.theme.SoftGoldLight
import com.example.ui.theme.TextPrimary
import com.example.ui.theme.TextSecondary
import com.example.ui.theme.TextTertiary
import com.example.ui.viewmodel.WalletViewModel

@Composable
fun HomeScreen(
    viewModel: WalletViewModel,
    onNavigateToTransfer: () -> Unit,
    onNavigateToDeposit: () -> Unit,
    onNavigateToWithdraw: () -> Unit,
    onNavigateToPayments: () -> Unit,
    onNavigateToServices: () -> Unit,
    onNavigateToTransactions: () -> Unit,
    onNavigateToNotifications: () -> Unit,
    onNavigateToProfile: () -> Unit
) {
    val user by viewModel.currentUser.collectAsState()
    val wallet by viewModel.currentWallet.collectAsState()
    val transactions by viewModel.transactions.collectAsState()
    val notifications by viewModel.notifications.collectAsState()
    val isBalanceVisible by viewModel.isBalanceVisible.collectAsState()

    val unreadCount = notifications.count { !it.isRead }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .testTag("home_screen"),
        contentPadding = PaddingValues(bottom = 90.dp)
    ) {
        // TOP APP HEADER (Navy branded header matching original index.php)
        item {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(bottomStart = 28.dp, bottomEnd = 28.dp))
                    .background(Navy900)
                    .padding(top = 16.dp, bottom = 26.dp, start = 20.dp, end = 20.dp)
            ) {
                Column(modifier = Modifier.fillMaxWidth()) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(
                                modifier = Modifier
                                    .size(48.dp)
                                    .background(Color.White, RoundedCornerShape(14.dp)),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = "SDG",
                                    fontSize = 15.sp,
                                    fontWeight = FontWeight.Black,
                                    color = Navy900
                                )
                            }
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text(
                                    text = "المحفظة الذكية",
                                    fontSize = 20.sp,
                                    fontWeight = FontWeight.Black,
                                    color = Color.White
                                )
                                Text(
                                    text = "الجنيه السوداني • Smart Wallet",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Medium,
                                    color = Color.White.copy(alpha = 0.8f)
                                )
                            }
                        }

                        Row(verticalAlignment = Alignment.CenterVertically) {
                            IconButton(
                                onClick = onNavigateToNotifications,
                                modifier = Modifier.size(42.dp)
                            ) {
                                BadgedBox(
                                    badge = {
                                        if (unreadCount > 0) {
                                            Badge(
                                                containerColor = EmeraldGreen,
                                                contentColor = Color.White
                                            ) {
                                                Text(text = "$unreadCount")
                                            }
                                        }
                                    }
                                ) {
                                    Icon(
                                        imageVector = Icons.Default.Notifications,
                                        contentDescription = "الإشعارات",
                                        tint = Color.White,
                                        modifier = Modifier.size(24.dp)
                                    )
                                }
                            }

                            Spacer(modifier = Modifier.width(6.dp))

                            IconButton(
                                onClick = onNavigateToProfile,
                                modifier = Modifier
                                    .size(42.dp)
                                    .background(Color.White.copy(alpha = 0.15f), CircleShape)
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Person,
                                    contentDescription = "الملف الشخصي",
                                    tint = Color.White,
                                    modifier = Modifier.size(22.dp)
                                )
                            }
                        }
                    }

                    Spacer(modifier = Modifier.height(20.dp))

                    BalanceCard(
                        balance = wallet?.balance ?: 0.0,
                        userName = user?.fullName ?: "أحمد محمد عثمان",
                        isVisible = isBalanceVisible,
                        onToggleVisibility = { viewModel.toggleBalanceVisibility() }
                    )
                }
            }
        }

        // QUICK ACTIONS ROW
        item {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 18.dp)
            ) {
                Text(
                    text = "العمليات السريعة",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary,
                    modifier = Modifier.padding(bottom = 12.dp)
                )

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    QuickActionItem(
                        icon = Icons.Default.Send,
                        title = "إرسال أموال",
                        subtitle = "تحويل فوري",
                        bgColor = EmeraldLight,
                        iconTint = EmeraldGreen,
                        modifier = Modifier.weight(1f),
                        onClick = onNavigateToTransfer
                    )
                    QuickActionItem(
                        icon = Icons.Default.ArrowDownward,
                        title = "إضافة أموال",
                        subtitle = "إيداع بنكي",
                        bgColor = Color(0xFFEFF6FF),
                        iconTint = Color(0xFF2563EB),
                        modifier = Modifier.weight(1f),
                        onClick = onNavigateToDeposit
                    )
                    QuickActionItem(
                        icon = Icons.Default.ArrowUpward,
                        title = "سحب أموال",
                        subtitle = "كاش أو بنك",
                        bgColor = SoftGoldLight,
                        iconTint = SoftGold,
                        modifier = Modifier.weight(1f),
                        onClick = onNavigateToWithdraw
                    )
                    QuickActionItem(
                        icon = Icons.Default.Payment,
                        title = "المدفوعات",
                        subtitle = "سداد تجار",
                        bgColor = Color(0xFFF3E8FF),
                        iconTint = Color(0xFF9333EA),
                        modifier = Modifier.weight(1f),
                        onClick = onNavigateToPayments
                    )
                }
            }
        }

        // DIGITAL SERVICES GRID
        item {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "الخدمات الإلكترونية",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextPrimary
                    )
                    TextButton(onClick = onNavigateToServices) {
                        Text(
                            text = "عرض الكل",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Bold,
                            color = Navy900
                        )
                    }
                }

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    ServiceGridCard(
                        emoji = "📱",
                        title = "شحن رصيد",
                        subtitle = "زين • سوداني • MTN",
                        modifier = Modifier.weight(1f),
                        onClick = onNavigateToServices
                    )
                    ServiceGridCard(
                        emoji = "⚡",
                        title = "شراء كهرباء",
                        subtitle = "شحن فوري للكيلوواط",
                        modifier = Modifier.weight(1f),
                        onClick = onNavigateToServices
                    )
                    ServiceGridCard(
                        emoji = "🌐",
                        title = "الإنترنت",
                        subtitle = "سوداني • كنار • فايبر",
                        modifier = Modifier.weight(1f),
                        onClick = onNavigateToServices
                    )
                }

                Spacer(modifier = Modifier.height(10.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    ServiceGridCard(
                        emoji = "🧾",
                        title = "الفواتير",
                        subtitle = "مياه • هاتف • بلدية",
                        modifier = Modifier.weight(1f),
                        onClick = onNavigateToServices
                    )
                    ServiceGridCard(
                        emoji = "🎓",
                        title = "التعليم",
                        subtitle = "رسوم الجامعات والمدارس",
                        modifier = Modifier.weight(1f),
                        onClick = onNavigateToServices
                    )
                    ServiceGridCard(
                        emoji = "🏛️",
                        title = "حكومية",
                        subtitle = "أورنيك 15 • المعاملات",
                        modifier = Modifier.weight(1f),
                        onClick = onNavigateToServices
                    )
                }
            }
        }

        // RECENT TRANSACTIONS
        item {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 20.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "آخر الحركات المالية",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextPrimary
                    )
                    TextButton(onClick = onNavigateToTransactions) {
                        Text(
                            text = "سجل العمليات",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Bold,
                            color = Navy900
                        )
                    }
                }
            }
        }

        if (transactions.isEmpty()) {
            item {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 20.dp)
                        .background(Color.White, RoundedCornerShape(16.dp))
                        .padding(24.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = "لا توجد حركات مالية حتى الآن",
                        fontSize = 13.sp,
                        color = TextSecondary
                    )
                }
            }
        } else {
            items(transactions.take(5)) { tx ->
                Box(modifier = Modifier.padding(horizontal = 16.dp, vertical = 4.dp)) {
                    TransactionRow(
                        transaction = tx,
                        onClick = onNavigateToTransactions
                    )
                }
            }
        }
    }
}

@Composable
fun QuickActionItem(
    icon: ImageVector,
    title: String,
    subtitle: String,
    bgColor: Color,
    iconTint: Color,
    modifier: Modifier = Modifier,
    onClick: () -> Unit
) {
    Card(
        modifier = modifier
            .clip(RoundedCornerShape(16.dp))
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 14.dp, horizontal = 6.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .background(bgColor, RoundedCornerShape(12.dp)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = icon,
                    contentDescription = title,
                    tint = iconTint,
                    modifier = Modifier.size(22.dp)
                )
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = title,
                fontSize = 12.sp,
                fontWeight = FontWeight.Bold,
                color = TextPrimary,
                textAlign = TextAlign.Center
            )
            Text(
                text = subtitle,
                fontSize = 9.sp,
                color = TextTertiary,
                textAlign = TextAlign.Center
            )
        }
    }
}

@Composable
fun ServiceGridCard(
    emoji: String,
    title: String,
    subtitle: String,
    modifier: Modifier = Modifier,
    onClick: () -> Unit
) {
    Card(
        modifier = modifier
            .clip(RoundedCornerShape(16.dp))
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = CardDefaults.outlinedCardBorder()
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Box(
                modifier = Modifier
                    .size(42.dp)
                    .background(Color(0xFFF1F5F9), RoundedCornerShape(12.dp)),
                contentAlignment = Alignment.Center
            ) {
                Text(text = emoji, fontSize = 22.sp)
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = title,
                fontSize = 12.sp,
                fontWeight = FontWeight.Bold,
                color = TextPrimary,
                textAlign = TextAlign.Center
            )
            Spacer(modifier = Modifier.height(2.dp))
            Text(
                text = subtitle,
                fontSize = 9.sp,
                color = TextSecondary,
                textAlign = TextAlign.Center,
                maxLines = 1
            )
        }
    }
}
