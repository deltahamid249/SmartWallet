package com.smartwallet.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
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

data class ServiceDefinition(
    val id: String,
    val title: String,
    val icon: String,
    val description: String,
    val providers: List<String>,
    val targetLabel: String,
    val targetPlaceholder: String,
    val defaultFee: Double
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ServicesScreen(
    viewModel: SmartWalletViewModel,
    onBack: () -> Unit
) {
    val currentWallet by viewModel.currentWallet.collectAsState()
    val userServices by viewModel.userServices.collectAsState()

    val services = listOf(
        ServiceDefinition(
            id = "recharge",
            title = "شحن رصيد الهاتف",
            icon = "📱",
            description = "شحن رصيد فوري لجميع شبكات الاتصالات في السودان",
            providers = listOf("زين (Zain)", "سوداني (Sudani)", "إم تي إن (MTN)"),
            targetLabel = "رقم الهاتف المراد شحنه",
            targetPlaceholder = "09xxxxxxxx أو 01xxxxxxxx",
            defaultFee = 0.0
        ),
        ServiceDefinition(
            id = "electricity",
            title = "شراء كهرباء",
            icon = "⚡",
            description = "شراء كروت وتغذية عداد الكهرباء مع توليد كود الشحن فوراً",
            providers = listOf("الهيئة القومية للكهرباء (NEC)"),
            targetLabel = "رقم العداد (11 خانة)",
            targetPlaceholder = "مثال: 04123456789",
            defaultFee = 100.0
        ),
        ServiceDefinition(
            id = "internet",
            title = "باقات الإنترنت",
            icon = "🌐",
            description = "تفعيل وتجديد اشتراكات باقات الإنترنت المنزلية والشخصية",
            providers = listOf("سوداني 4G", "زين فايبر", "إم تي إن برودباند", "كنار تليكوم"),
            targetLabel = "رقم الشريحة أو حساب المودم",
            targetPlaceholder = "مثال: 0912345678",
            defaultFee = 50.0
        ),
        ServiceDefinition(
            id = "bills",
            title = "سداد الفواتير",
            icon = "🧾",
            description = "سداد فواتير المياه، الهاتف الثابت، ورسوم النفايات البلدية",
            providers = listOf("هيئة مياه ولاية الخرطوم", "الهاتف الثابت سوداتل", "الرسوم البلدية"),
            targetLabel = "رقم المشترك / الفاتورة",
            targetPlaceholder = "مثال: 981203",
            defaultFee = 150.0
        ),
        ServiceDefinition(
            id = "education",
            title = "الرسوم الدراسية",
            icon = "🎓",
            description = "سداد المصروفات والرسوم الجامعية والتعليمية",
            providers = listOf("جامعة الخرطوم", "جامعة السودان للعلوم والتكنولوجيا", "جامعة النيلين", "جامعة الجزيرة"),
            targetLabel = "الرقم الجامعي للطالب",
            targetPlaceholder = "مثال: UOK-2023-994",
            defaultFee = 200.0
        ),
        ServiceDefinition(
            id = "government",
            title = "الخدمات الحكومية",
            icon = "🏛️",
            description = "سداد المخالفات المرورية، رسوم أورنيك 15، والخدمات الإلكترونية",
            providers = listOf("أورنيك 15 الإلكتروني", "المخالفات المرورية", "السجل المدني والجوازات"),
            targetLabel = "رقم المعاملة أو اللوحة المرورية",
            targetPlaceholder = "مثال: GOV-89412",
            defaultFee = 250.0
        )
    )

    var activeService by remember { mutableStateOf<ServiceDefinition?>(null) }
    var selectedProvider by remember { mutableStateOf("") }
    var targetIdentifier by remember { mutableStateOf("") }
    var amountText by remember { mutableStateOf("") }

    var errorMessage by remember { mutableStateOf<String?>(null) }
    var successReceipt by remember { mutableStateOf<Pair<String, Double>?>(null) }
    var isSubmitting by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "الخدمات الإلكترونية",
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
                .testTag("services_screen"),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Balance card
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
                            Text("الرصيد المتاح للخدمات", color = Color(0xFF94A3B8), fontSize = 13.sp)
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "${formatSdg(currentWallet?.balance ?: 0.0)} SDG",
                                color = EmeraldLight,
                                fontSize = 22.sp,
                                fontWeight = FontWeight.ExtraBold
                            )
                        }
                        Text("🛠️", fontSize = 28.sp)
                    }
                }
            }

            // Services Grid / List
            item {
                Text(
                    text = "اختر الخدمة المطلوبة:",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
            }

            items(services) { service ->
                Surface(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(18.dp))
                        .clickable {
                            activeService = service
                            selectedProvider = service.providers.first()
                            targetIdentifier = ""
                            amountText = ""
                            errorMessage = null
                        }
                        .testTag("service_item_${service.id}"),
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
                                    .size(50.dp)
                                    .clip(RoundedCornerShape(14.dp))
                                    .background(Color(0xFFF1F5F9)),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(service.icon, fontSize = 24.sp)
                            }
                            Column(modifier = Modifier.widthIn(max = 240.dp)) {
                                Text(
                                    text = service.title,
                                    fontSize = 15.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = TextPrimary
                                )
                                Spacer(modifier = Modifier.height(2.dp))
                                Text(
                                    text = service.description,
                                    fontSize = 12.sp,
                                    color = TextSecondary,
                                    maxLines = 2
                                )
                            }
                        }
                        Text("←", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = TextSecondary)
                    }
                }
            }

            // Recent service requests
            item {
                Spacer(modifier = Modifier.height(10.dp))
                Text(
                    text = "الخدمات المنفذة مؤخراً",
                    fontSize = 17.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
            }

            if (userServices.isEmpty()) {
                item {
                    Text("لم يتم تنفيذ خدمات بعد.", color = TextSecondary, fontSize = 13.sp)
                }
            } else {
                items(userServices) { req ->
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
                                    text = "${req.provider} - ${req.targetIdentifier}",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 13.sp,
                                    color = TextPrimary
                                )
                                Text(
                                    text = "المرجع: ${req.reference} • ${formatDate(req.createdAt)}",
                                    fontSize = 11.sp,
                                    color = TextSecondary
                                )
                            }
                            Column(horizontalAlignment = Alignment.End) {
                                Text(
                                    text = "-${formatSdg(req.totalAmount)} SDG",
                                    fontWeight = FontWeight.ExtraBold,
                                    fontSize = 14.sp,
                                    color = TextPrimary
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

    // Modal Sheet / Dialog for executing the service
    if (activeService != null) {
        val s = activeService!!
        ModalBottomSheet(
            onDismissRequest = { activeService = null },
            sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
            containerColor = Color.White
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 10.dp)
                    .padding(bottom = 32.dp),
                verticalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Text(s.icon, fontSize = 28.sp)
                    Column {
                        Text(s.title, fontSize = 18.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
                        Text(s.description, fontSize = 12.sp, color = TextSecondary)
                    }
                }

                HorizontalDivider()

                // Provider selection
                Text("اختر المزود / الجهة:", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = TextSecondary)
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    s.providers.forEach { p ->
                        FilterChip(
                            selected = selectedProvider == p,
                            onClick = { selectedProvider = p },
                            label = { Text(p, fontSize = 12.sp) }
                        )
                    }
                }

                OutlinedTextField(
                    value = targetIdentifier,
                    onValueChange = { targetIdentifier = it },
                    label = { Text(s.targetLabel) },
                    placeholder = { Text(s.targetPlaceholder) },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                )

                OutlinedTextField(
                    value = amountText,
                    onValueChange = { amountText = it },
                    label = { Text("المبلغ (SDG)") },
                    placeholder = { Text("0.00") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                )

                if (s.defaultFee > 0) {
                    Surface(
                        color = SlateBackground,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text(
                            text = "رسوم الخدمة: ${formatSdg(s.defaultFee)} SDG",
                            fontSize = 12.sp,
                            color = TextSecondary,
                            modifier = Modifier.padding(10.dp)
                        )
                    }
                }

                if (errorMessage != null) {
                    Text(errorMessage!!, color = ErrorRed, fontSize = 13.sp, fontWeight = FontWeight.Bold)
                }

                Button(
                    onClick = {
                        val amt = amountText.toDoubleOrNull()
                        if (targetIdentifier.trim().isEmpty()) {
                            errorMessage = "يرجى تعبئة ${s.targetLabel}."
                            return@Button
                        }
                        if (amt == null || amt <= 0) {
                            errorMessage = "يرجى إدخال مبلغ صحيح."
                            return@Button
                        }
                        errorMessage = null
                        isSubmitting = true
                        viewModel.payService(
                            serviceType = s.id,
                            serviceTitle = s.title,
                            provider = selectedProvider,
                            targetIdentifier = targetIdentifier,
                            amount = amt,
                            fee = s.defaultFee
                        ) { success, refOrErr ->
                            isSubmitting = false
                            if (success) {
                                successReceipt = Pair(refOrErr, amt + s.defaultFee)
                                activeService = null
                            } else {
                                errorMessage = refOrErr
                            }
                        }
                    },
                    enabled = !isSubmitting,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(52.dp)
                        .testTag("confirm_service_button"),
                    colors = ButtonDefaults.buttonColors(containerColor = EmeraldGreen),
                    shape = RoundedCornerShape(14.dp)
                ) {
                    if (isSubmitting) {
                        CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                    } else {
                        Text("تأكيد وسداد الخدمة", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }

    if (successReceipt != null) {
        SuccessReceiptDialog(
            title = "تم تنفيذ وسداد الخدمة بنجاح!",
            reference = successReceipt!!.first,
            amount = successReceipt!!.second,
            details = "تم إصدار الإيصال وسداد الرسوم المترتبة على الخدمة فورياً.",
            onDismiss = { successReceipt = null }
        )
    }
}
