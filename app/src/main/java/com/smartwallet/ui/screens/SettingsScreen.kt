package com.smartwallet.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.smartwallet.ui.components.WalletTopAppBar
import com.smartwallet.ui.theme.*

@Composable
fun SettingsScreen(onBack: () -> Unit) {
    var biometricEnabled by remember { mutableStateOf(true) }
    var smsAlertsEnabled by remember { mutableStateOf(true) }
    var instantNotifications by remember { mutableStateOf(true) }

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "الإعدادات",
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
                .padding(16.dp)
                .testTag("settings_screen"),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Text("الأمان والحماية", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = TextPrimary)

            Surface(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                color = Color.White,
                border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight)
            ) {
                Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column {
                            Text("بصمة الإصبع والوجه", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = TextPrimary)
                            Text("تسجيل الدخول السريع وتأكيد العمليات", fontSize = 12.sp, color = TextSecondary)
                        }
                        Switch(
                            checked = biometricEnabled,
                            onCheckedChange = { biometricEnabled = it },
                            colors = SwitchDefaults.colors(checkedThumbColor = Color.White, checkedTrackColor = EmeraldGreen)
                        )
                    }

                    HorizontalDivider()

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column {
                            Text("إشعارات SMS", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = TextPrimary)
                            Text("تلقي رسائل نصية عند كل حركة مالية", fontSize = 12.sp, color = TextSecondary)
                        }
                        Switch(
                            checked = smsAlertsEnabled,
                            onCheckedChange = { smsAlertsEnabled = it },
                            colors = SwitchDefaults.colors(checkedThumbColor = Color.White, checkedTrackColor = EmeraldGreen)
                        )
                    }

                    HorizontalDivider()

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column {
                            Text("التنبيهات الفورية (Push)", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = TextPrimary)
                            Text("تنبيهات فورية بالعروض والتحويلات", fontSize = 12.sp, color = TextSecondary)
                        }
                        Switch(
                            checked = instantNotifications,
                            onCheckedChange = { instantNotifications = it },
                            colors = SwitchDefaults.colors(checkedThumbColor = Color.White, checkedTrackColor = EmeraldGreen)
                        )
                    }
                }
            }

            Text("حول التطبيق", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = TextPrimary)

            Surface(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                color = Color.White,
                border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight)
            ) {
                Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text("اسم التطبيق", color = TextSecondary, fontSize = 13.sp)
                        Text("SmartWallet (المحفظة الذكية)", fontWeight = FontWeight.Bold, color = TextPrimary, fontSize = 13.sp)
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text("الإصدار", color = TextSecondary, fontSize = 13.sp)
                        Text("1.0.0 SDG Native", fontWeight = FontWeight.Bold, color = TextPrimary, fontSize = 13.sp)
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text("العملة المعتمدة", color = TextSecondary, fontSize = 13.sp)
                        Text("الجنيه السوداني (SDG)", fontWeight = FontWeight.Bold, color = EmeraldGreen, fontSize = 13.sp)
                    }
                }
            }
        }
    }
}
