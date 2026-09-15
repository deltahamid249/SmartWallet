package com.smartwallet.ui.screens

import androidx.compose.foundation.background
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
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.smartwallet.ui.SmartWalletViewModel
import com.smartwallet.ui.components.WalletTopAppBar
import com.smartwallet.ui.components.formatDate
import com.smartwallet.ui.theme.*

@Composable
fun NotificationsScreen(
    viewModel: SmartWalletViewModel,
    onBack: () -> Unit
) {
    val notifications by viewModel.notifications.collectAsState()

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "الإشعارات",
                onBack = onBack,
                actions = {
                    if (notifications.any { !it.isRead }) {
                        TextButton(
                            onClick = { viewModel.markAllNotificationsAsRead() },
                            modifier = Modifier.testTag("mark_all_read_button")
                        ) {
                            Text("تحديد الكل كمقروء", color = Color.White, fontSize = 12.sp)
                        }
                    }
                }
            )
        },
        containerColor = SlateBackground
    ) { padding ->
        if (notifications.isEmpty()) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding)
                    .testTag("notifications_screen"),
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("🔔", fontSize = 48.sp)
                    Spacer(modifier = Modifier.height(12.dp))
                    Text(
                        text = "لا توجد إشعارات حالياً",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextPrimary
                    )
                }
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding)
                    .padding(16.dp)
                    .testTag("notifications_screen"),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                items(notifications) { notif ->
                    val (icon, tint) = when (notif.type) {
                        "success" -> Pair("✓", EmeraldGreen)
                        "warning" -> Pair("⚠", WarningAmber)
                        else -> Pair("ℹ", InfoBlue)
                    }

                    Surface(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(16.dp))
                            .clickable { viewModel.markNotificationAsRead(notif.id) },
                        shape = RoundedCornerShape(16.dp),
                        color = if (!notif.isRead) Color.White else Color(0xFFF9FAFB),
                        border = androidx.compose.foundation.BorderStroke(
                            1.dp,
                            if (!notif.isRead) InfoBlue.copy(alpha = 0.3f) else BorderLight
                        ),
                        shadowElevation = if (!notif.isRead) 2.dp else 0.dp
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp),
                            horizontalArrangement = Arrangement.spacedBy(14.dp),
                            verticalAlignment = Alignment.Top
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(36.dp)
                                    .clip(CircleShape)
                                    .background(tint.copy(alpha = 0.15f)),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(icon, color = tint, fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            }

                            Column(modifier = Modifier.weight(1f)) {
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text(
                                        text = notif.title,
                                        fontWeight = if (!notif.isRead) FontWeight.Bold else FontWeight.SemiBold,
                                        fontSize = 14.sp,
                                        color = TextPrimary
                                    )
                                    if (!notif.isRead) {
                                        Box(
                                            modifier = Modifier
                                                .size(8.dp)
                                                .clip(CircleShape)
                                                .background(InfoBlue)
                                        )
                                    }
                                }
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    text = notif.message,
                                    fontSize = 13.sp,
                                    color = TextSecondary,
                                    lineHeight = 18.sp
                                )
                                Spacer(modifier = Modifier.height(6.dp))
                                Text(
                                    text = formatDate(notif.createdAt),
                                    fontSize = 11.sp,
                                    color = TextMuted
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}
