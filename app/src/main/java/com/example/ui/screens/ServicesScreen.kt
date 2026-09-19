package com.example.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
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
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.Check
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import com.example.data.repository.WalletRepository
import com.example.ui.theme.BorderLight
import com.example.ui.theme.EmeraldDark
import com.example.ui.theme.EmeraldGreen
import com.example.ui.theme.EmeraldLight
import com.example.ui.theme.Navy900
import com.example.ui.theme.TextPrimary
import com.example.ui.theme.TextSecondary
import com.example.ui.theme.TextTertiary
import com.example.ui.viewmodel.WalletViewModel

data class ServiceItem(
    val id: String,
    val emoji: String,
    val title: String,
    val description: String,
    val providers: List<String>,
    val defaultFee: Double = 50.0
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ServicesScreen(
    viewModel: WalletViewModel,
    onNavigateBack: () -> Unit
) {
    val wallet by viewModel.currentWallet.collectAsState()

    val services = listOf(
        ServiceItem(
            id = "recharge",
            emoji = "📱",
            title = "شحن الهاتف والرصيد",
            description = "شحن أرقام زين وسوداني وMTN ودفع فواتير الباقات.",
            providers = listOf("زين (Zain)", "سوداني (Sudani)", "إم تي إن (MTN)")
        ),
        ServiceItem(
            id = "electricity",
            emoji = "⚡",
            title = "سداد وشراء الكهرباء",
            description = "شراء رمز كهرباء فوري وإصدار التوكن برقم العداد.",
            providers = listOf("الشركة السودانية لتوزيع الكهرباء")
        ),
        ServiceItem(
            id = "internet",
            emoji = "🌐",
            title = "باقات الإنترنت المنزلي والفايبر",
            description = "تجديد اشتراكات كنار وسوداتل وخدمات البيانات السريعة.",
            providers = listOf("سوداني فايبر", "كنار تيليكوم", "سوداتل للإنترنت")
        ),
        ServiceItem(
            id = "bills",
            emoji = "🧾",
            title = "سداد الفواتير والخدمات",
            description = "إدارة ودفع فواتير المياه والخدمات البلدية والمشتركة.",
            providers = listOf("هيئة مياه ولاية الخرطوم", "رسوم الخدمات المحلية")
        ),
        ServiceItem(
            id = "education",
            emoji = "🎓",
            title = "الخدمات والرسوم التعليمية",
            description = "دفع الرسوم الجامعية، المدارس، واستمارة الشهادة السودانية.",
            providers = listOf("جامعة الخرطوم", "جامعة السودان للعلوم والتكنولوجيا", "رسوم الامتحانات والشهادة")
        ),
        ServiceItem(
            id = "government",
            emoji = "🏛️",
            title = "المعاملات والخدمات الحكومية",
            description = "سداد أورنيك 15، مخالفات ورخص المرور، والمعاملات الرقمية.",
            providers = listOf("أورنيك 15 الموحد", "الإدارة العامة للمرور", "خدمات السجل المدني")
        )
    )

    var activeService by remember { mutableStateOf<ServiceItem?>(null) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "دليل الخدمات (Services Hub)",
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "رجوع"
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Navy900,
                    titleContentColor = Color.White,
                    navigationIconContentColor = Color.White
                )
            )
        },
        containerColor = Color(0xFFF4F6F8)
    ) { innerPadding ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .padding(16.dp)
                .testTag("services_screen")
        ) {
            // Balance card
            item {
                Card(
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = "رصيد المحفظة المتاح:",
                            fontSize = 13.sp,
                            color = TextSecondary
                        )
                        Text(
                            text = "${WalletRepository.formatMoney(wallet?.balance ?: 0.0)} SDG",
                            fontSize = 16.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = EmeraldGreen
                        )
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    text = "اختر الخدمة المطلوبة للدفع الفوري",
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary,
                    modifier = Modifier.padding(bottom = 8.dp)
                )
            }

            items(services) { srv ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 5.dp)
                        .clip(RoundedCornerShape(16.dp))
                        .clickable { activeService = srv },
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier.weight(1f)
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(48.dp)
                                    .background(Color(0xFFF1F5F9), RoundedCornerShape(14.dp)),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(text = srv.emoji, fontSize = 24.sp)
                            }
                            Spacer(modifier = Modifier.width(14.dp))
                            Column {
                                Text(
                                    text = srv.title,
                                    fontSize = 15.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = Navy900
                                )
                                Spacer(modifier = Modifier.height(3.dp))
                                Text(
                                    text = srv.description,
                                    fontSize = 11.sp,
                                    color = TextSecondary,
                                    lineHeight = 15.sp
                                )
                            }
                        }

                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowForward,
                            contentDescription = null,
                            tint = Navy900,
                            modifier = Modifier.size(20.dp)
                        )
                    }
                }
            }
        }
    }

    // Modal dialog to execute service
    if (activeService != null) {
        val srv = activeService!!
        ServiceExecutionDialog(
            service = srv,
            availableBalance = wallet?.balance ?: 0.0,
            onDismiss = { activeService = null },
            onSubmit = { provider, phoneOrAcc, amount ->
                viewModel.requestService(
                    serviceType = srv.id,
                    provider = provider,
                    phoneOrAccount = phoneOrAcc,
                    amount = amount,
                    fee = srv.defaultFee
                )
                activeService = null
            }
        )
    }
}

@Composable
fun ServiceExecutionDialog(
    service: ServiceItem,
    availableBalance: Double,
    onDismiss: () -> Unit,
    onSubmit: (provider: String, phoneOrAcc: String, amount: Double) -> Unit
) {
    var selectedProvider by remember { mutableStateOf(service.providers.firstOrNull() ?: "") }
    var accountInput by remember { mutableStateOf("") }
    var amountInput by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }

    Dialog(onDismissRequest = onDismiss) {
        Card(
            shape = RoundedCornerShape(22.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            modifier = Modifier
                .fillMaxWidth()
                .padding(8.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp)
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(text = service.emoji, fontSize = 28.sp)
                    Spacer(modifier = Modifier.width(10.dp))
                    Text(
                        text = service.title,
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = Navy900
                    )
                }

                Spacer(modifier = Modifier.height(16.dp))

                // Provider dropdown or chips
                if (service.providers.size > 1) {
                    Text(
                        text = "الشبكة / مقدم الخدمة",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextPrimary
                    )
                    Spacer(modifier = Modifier.height(6.dp))
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        service.providers.forEach { prov ->
                            val isSelected = selectedProvider == prov
                            Box(
                                modifier = Modifier
                                    .weight(1f)
                                    .clip(RoundedCornerShape(8.dp))
                                    .background(if (isSelected) EmeraldLight else Color(0xFFF1F5F9))
                                    .clickable { selectedProvider = prov }
                                    .padding(vertical = 8.dp, horizontal = 4.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = prov.split(" ").firstOrNull() ?: prov,
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = if (isSelected) EmeraldDark else Navy900
                                )
                            }
                        }
                    }
                    Spacer(modifier = Modifier.height(12.dp))
                }

                val accountLabel = when (service.id) {
                    "recharge" -> "رقم هاتف المستفيد"
                    "electricity" -> "رقم العداد (Meter No.)"
                    "bills" -> "رقم المشترك / الفاتورة"
                    "education" -> "رقم جلوس أو قيد الطالب"
                    else -> "رقم المعاملة أو الحساب"
                }

                Text(
                    text = accountLabel,
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
                Spacer(modifier = Modifier.height(4.dp))
                OutlinedTextField(
                    value = accountInput,
                    onValueChange = { accountInput = it },
                    placeholder = { Text("أدخل $accountLabel") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(10.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Navy900,
                        unfocusedBorderColor = BorderLight
                    )
                )

                Spacer(modifier = Modifier.height(12.dp))

                Text(
                    text = "المبلغ المطلوب سداده (SDG)",
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
                Spacer(modifier = Modifier.height(4.dp))
                OutlinedTextField(
                    value = amountInput,
                    onValueChange = {
                        amountInput = it
                        error = null
                    },
                    placeholder = { Text("0.00") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(10.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Navy900,
                        unfocusedBorderColor = BorderLight
                    )
                )

                // Quick preset amounts for recharge/electricity
                Spacer(modifier = Modifier.height(8.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    listOf(500, 1000, 3000, 5000).forEach { amt ->
                        Box(
                            modifier = Modifier
                                .weight(1f)
                                .clip(RoundedCornerShape(6.dp))
                                .background(Color(0xFFF8FAFC))
                                .clickable { amountInput = amt.toString() }
                                .padding(vertical = 6.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = "$amt",
                                fontSize = 10.sp,
                                fontWeight = FontWeight.Bold,
                                color = Navy900
                            )
                        }
                    }
                }

                Spacer(modifier = Modifier.height(10.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text(text = "رسوم الخدمة:", fontSize = 11.sp, color = TextSecondary)
                    Text(text = "${service.defaultFee} SDG", fontSize = 11.sp, fontWeight = FontWeight.Bold)
                }

                if (error != null) {
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = error ?: "",
                        fontSize = 11.sp,
                        color = Color(0xFFDC2626),
                        fontWeight = FontWeight.Bold
                    )
                }

                Spacer(modifier = Modifier.height(16.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    TextButton(
                        onClick = onDismiss,
                        modifier = Modifier.weight(1f)
                    ) {
                        Text(text = "إلغاء", color = TextSecondary)
                    }

                    Button(
                        onClick = {
                            val amt = amountInput.toDoubleOrNull()
                            if (accountInput.isBlank()) {
                                error = "يرجى إدخال $accountLabel"
                            } else if (amt == null || amt <= 0) {
                                error = "يرجى تحديد المبلغ"
                            } else if ((amt + service.defaultFee) > availableBalance) {
                                error = "الرصيد المتاح غير كافٍ"
                            } else {
                                onSubmit(selectedProvider, accountInput, amt)
                            }
                        },
                        modifier = Modifier.weight(1.5f),
                        colors = ButtonDefaults.buttonColors(containerColor = EmeraldGreen),
                        shape = RoundedCornerShape(10.dp)
                    ) {
                        Text(text = "تأكيد وسداد", fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}
