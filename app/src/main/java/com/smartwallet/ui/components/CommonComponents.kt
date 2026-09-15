package com.smartwallet.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
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
import com.smartwallet.ui.theme.*
import java.text.DecimalFormat
import java.text.SimpleDateFormat
import java.util.*

fun formatSdg(amount: Double): String {
    val formatter = DecimalFormat("#,##0.00")
    return formatter.format(amount)
}

fun formatDate(millis: Long): String {
    val sdf = SimpleDateFormat("yyyy/MM/dd HH:mm", Locale.getDefault())
    return sdf.format(Date(millis))
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun WalletTopAppBar(
    title: String,
    onBack: (() -> Unit)? = null,
    actions: @Composable RowScope.() -> Unit = {}
) {
    TopAppBar(
        title = {
            Text(
                text = title,
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold,
                color = Color.White
            )
        },
        navigationIcon = {
            if (onBack != null) {
                IconButton(
                    onClick = onBack,
                    modifier = Modifier.testTag("back_button")
                ) {
                    Text(
                        text = "←",
                        fontSize = 24.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color.White
                    )
                }
            }
        },
        actions = actions,
        colors = TopAppBarDefaults.topAppBarColors(
            containerColor = NavyPrimary,
            titleContentColor = Color.White,
            navigationIconContentColor = Color.White
        )
    )
}

@Composable
fun StatusBadge(status: String) {
    val (bgColor, textColor, text) = when (status.lowercase()) {
        "completed", "approved", "active" -> Triple(Color(0xFFDCFCE7), Color(0xFF166534), "مكتمل")
        "pending", "processing" -> Triple(Color(0xFFFEF3C7), Color(0xFF92400E), "قيد المراجعة")
        "rejected", "failed", "blocked" -> Triple(Color(0xFFFEE2E2), Color(0xFF991B1B), "مرفوض")
        else -> Triple(Color(0xFFF3F4F6), Color(0xFF374151), status)
    }

    Surface(
        color = bgColor,
        shape = RoundedCornerShape(12.dp)
    ) {
        Text(
            text = text,
            color = textColor,
            fontSize = 11.sp,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp)
        )
    }
}

@Composable
fun SuccessReceiptDialog(
    title: String,
    reference: String,
    amount: Double? = null,
    details: String? = null,
    onDismiss: () -> Unit
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        confirmButton = {
            Button(
                onClick = onDismiss,
                colors = ButtonDefaults.buttonColors(containerColor = NavyPrimary),
                shape = RoundedCornerShape(12.dp),
                modifier = Modifier
                    .fillMaxWidth()
                    .testTag("receipt_done_button")
            ) {
                Text("تم", fontWeight = FontWeight.Bold)
            }
        },
        icon = {
            Box(
                modifier = Modifier
                    .size(60.dp)
                    .clip(CircleShape)
                    .background(Color(0xFFDCFCE7)),
                contentAlignment = Alignment.Center
            ) {
                Text("✓", fontSize = 28.sp, color = EmeraldGreen, fontWeight = FontWeight.Bold)
            }
        },
        title = {
            Text(
                text = title,
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
                modifier = Modifier.fillMaxWidth()
            )
        },
        text = {
            Column(
                modifier = Modifier.fillMaxWidth(),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                if (amount != null) {
                    Text(
                        text = "${formatSdg(amount)} SDG",
                        style = MaterialTheme.typography.headlineMedium,
                        fontWeight = FontWeight.ExtraBold,
                        color = EmeraldGreen
                    )
                }
                Surface(
                    color = SlateBackground,
                    shape = RoundedCornerShape(10.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        Text(
                            text = "مرجع العملية:",
                            fontSize = 12.sp,
                            color = TextSecondary
                        )
                        Text(
                            text = reference,
                            fontSize = 14.sp,
                            fontWeight = FontWeight.Bold,
                            color = NavyPrimary
                        )
                        if (details != null) {
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                text = details,
                                fontSize = 13.sp,
                                color = TextPrimary
                            )
                        }
                    }
                }
            }
        },
        containerColor = Color.White,
        shape = RoundedCornerShape(20.dp)
    )
}
