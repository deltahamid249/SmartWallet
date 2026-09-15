package com.smartwallet.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
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
import com.smartwallet.ui.components.WalletTopAppBar
import com.smartwallet.ui.components.formatDate
import com.smartwallet.ui.components.formatSdg
import com.smartwallet.ui.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProfileScreen(
    viewModel: SmartWalletViewModel,
    onBack: () -> Unit
) {
    val currentUser by viewModel.currentUser.collectAsState()
    val currentWallet by viewModel.currentWallet.collectAsState()
    val allUsers by viewModel.allUsers.collectAsState()

    var showRegisterDialog by remember { mutableStateOf(false) }
    var newName by remember { mutableStateOf("") }
    var newPhone by remember { mutableStateOf("") }
    var newEmail by remember { mutableStateOf("") }
    var regError by remember { mutableStateOf<String?>(null) }
    var isRegistering by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "الملف الشخصي والحسابات",
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
                .testTag("profile_screen"),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // User Header Card
            item {
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(22.dp),
                    color = NavyPrimary,
                    shadowElevation = 4.dp
                ) {
                    Column(
                        modifier = Modifier.padding(22.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Box(
                            modifier = Modifier
                                .size(72.dp)
                                .clip(CircleShape)
                                .background(Color.White),
                            contentAlignment = Alignment.Center
                        ) {
                            Text("👤", fontSize = 36.sp)
                        }
                        Spacer(modifier = Modifier.height(12.dp))
                        Text(
                            text = currentUser?.fullName ?: "مستخدم المحفظة",
                            fontSize = 20.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color.White
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = currentUser?.phone ?: "",
                            fontSize = 14.sp,
                            color = Color(0xFF94A3B8)
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                        StatusBadge(currentUser?.status ?: "active")
                    }
                }
            }

            // Wallet details
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
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text(
                            text = "بيانات المحفظة",
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Bold,
                            color = TextPrimary
                        )

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text("رقم المحفظة (Wallet ID):", color = TextSecondary, fontSize = 13.sp)
                            Text("#${currentWallet?.id ?: "-"}", fontWeight = FontWeight.Bold, color = TextPrimary, fontSize = 13.sp)
                        }

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text("العملة:", color = TextSecondary, fontSize = 13.sp)
                            Text("الجنيه السوداني (SDG)", fontWeight = FontWeight.Bold, color = TextPrimary, fontSize = 13.sp)
                        }

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text("الرصيد الفعلي:", color = TextSecondary, fontSize = 13.sp)
                            Text(
                                text = "${formatSdg(currentWallet?.balance ?: 0.0)} SDG",
                                fontWeight = FontWeight.ExtraBold,
                                color = EmeraldGreen,
                                fontSize = 15.sp
                            )
                        }

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text("نوع الحساب:", color = TextSecondary, fontSize = 13.sp)
                            Text(
                                text = if (currentUser?.role == "admin") "مشرف نظام (Admin)" else "مستخدم عادي (User)",
                                fontWeight = FontWeight.Bold,
                                color = if (currentUser?.role == "admin") InfoBlue else TextPrimary,
                                fontSize = 13.sp
                            )
                        }
                    }
                }
            }

            // Switch User Section
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "التبديل بين الحسابات التجريبية:",
                        fontSize = 15.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextPrimary
                    )
                    TextButton(
                        onClick = {
                            regError = null
                            newName = ""
                            newPhone = ""
                            newEmail = ""
                            showRegisterDialog = true
                        },
                        modifier = Modifier.testTag("register_user_button")
                    ) {
                        Text("+ تسجيل جديد", color = NavyLight, fontWeight = FontWeight.Bold, fontSize = 13.sp)
                    }
                }
            }

            item {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    allUsers.forEach { user ->
                        val isSelected = user.id == currentUser?.id
                        Surface(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(RoundedCornerShape(14.dp))
                                .clickable { viewModel.switchUser(user.id) }
                                .testTag("switch_user_${user.id}"),
                            shape = RoundedCornerShape(14.dp),
                            color = if (isSelected) Color(0xFFEFF6FF) else Color.White,
                            border = androidx.compose.foundation.BorderStroke(
                                1.5.dp,
                                if (isSelected) InfoBlue else BorderLight
                            )
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
                                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                                ) {
                                    Text(if (user.role == "admin") "🛡️" else "👤", fontSize = 20.sp)
                                    Column {
                                        Text(
                                            text = user.fullName,
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 14.sp,
                                            color = TextPrimary
                                        )
                                        Text(
                                            text = "${user.phone} • ${if (user.role == "admin") "مشرف" else "مستخدم"}",
                                            fontSize = 11.sp,
                                            color = TextSecondary
                                        )
                                    }
                                }
                                if (isSelected) {
                                    Surface(color = InfoBlue, shape = RoundedCornerShape(8.dp)) {
                                        Text(
                                            text = "الحساب النشط",
                                            color = Color.White,
                                            fontSize = 11.sp,
                                            fontWeight = FontWeight.Bold,
                                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Register User Dialog
    if (showRegisterDialog) {
        AlertDialog(
            onDismissRequest = { showRegisterDialog = false },
            confirmButton = {
                Button(
                    onClick = {
                        if (newName.trim().isEmpty() || newPhone.trim().isEmpty()) {
                            regError = "يرجى تعبئة الاسم ورقم الهاتف."
                            return@Button
                        }
                        regError = null
                        isRegistering = true
                        viewModel.registerNewUser(newName, newPhone, newEmail.ifEmpty { null }) { success, msg ->
                            isRegistering = false
                            if (success) {
                                showRegisterDialog = false
                            } else {
                                regError = msg
                            }
                        }
                    },
                    enabled = !isRegistering,
                    colors = ButtonDefaults.buttonColors(containerColor = NavyPrimary),
                    shape = RoundedCornerShape(12.dp),
                    modifier = Modifier.testTag("submit_register_user_button")
                ) {
                    if (isRegistering) {
                        CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp))
                    } else {
                        Text("إنشاء المحفظة", fontWeight = FontWeight.Bold)
                    }
                }
            },
            dismissButton = {
                TextButton(onClick = { showRegisterDialog = false }) {
                    Text("إلغاء", color = TextSecondary)
                }
            },
            title = {
                Text("تسجيل حساب محفظة جديد", fontWeight = FontWeight.Bold, fontSize = 17.sp)
            },
            text = {
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    OutlinedTextField(
                        value = newName,
                        onValueChange = { newName = it },
                        label = { Text("الاسم الكامل") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(10.dp)
                    )
                    OutlinedTextField(
                        value = newPhone,
                        onValueChange = { newPhone = it },
                        label = { Text("رقم الهاتف") },
                        placeholder = { Text("09xxxxxxxx") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(10.dp)
                    )
                    OutlinedTextField(
                        value = newEmail,
                        onValueChange = { newEmail = it },
                        label = { Text("البريد الإلكتروني (اختياري)") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(10.dp)
                    )
                    if (regError != null) {
                        Text(regError!!, color = ErrorRed, fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    }
                }
            },
            containerColor = Color.White,
            shape = RoundedCornerShape(20.dp)
        )
    }
}
