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
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Payment
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material.icons.filled.Store
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
import com.example.data.repository.WalletRepository
import com.example.ui.theme.BorderLight
import com.example.ui.theme.EmeraldDark
import com.example.ui.theme.EmeraldGreen
import com.example.ui.theme.EmeraldLight
import com.example.ui.theme.Navy900
import com.example.ui.theme.TextPrimary
import com.example.ui.theme.TextSecondary
import com.example.ui.viewmodel.WalletViewModel

data class MerchantPreset(val name: String, val account: String, val category: String)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PaymentsScreen(
    viewModel: WalletViewModel,
    onNavigateBack: () -> Unit
) {
    val wallet by viewModel.currentWallet.collectAsState()

    val presets = listOf(
        MerchantPreset("سوبرماركت المدينة", "MER-10023", "تسوق ومواد غذائية"),
        MerchantPreset("صيدلية النيل الكبرى", "MER-20451", "أدوية وصحة"),
        MerchantPreset("مطاعم أمواج النيل", "MER-30882", "أطعمة ومشروبات"),
        MerchantPreset("محطة وقود النيلين", "MER-40919", "وقود وخدمات سيارات")
    )

    var merchantName by remember { mutableStateOf("") }
    var merchantAccount by remember { mutableStateOf("") }
    var amountText by remember { mutableStateOf("") }
    var errorMessage by remember { mutableStateOf<String?>(null) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "المدفوعات والمتاجر (Payments)",
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
                .testTag("payments_screen")
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
                            text = "الرصيد المتاح للدفع:",
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
            }

            // Presets row
            item {
                Text(
                    text = "متاجر ومقدمو خدمات مقترحون",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary,
                    modifier = Modifier.padding(bottom = 8.dp)
                )

                presets.forEach { preset ->
                    val isSelected = merchantAccount == preset.account
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 4.dp)
                            .clip(RoundedCornerShape(12.dp))
                            .background(if (isSelected) EmeraldLight else Color.White)
                            .border(
                                width = if (isSelected) 1.5.dp else 1.dp,
                                color = if (isSelected) EmeraldGreen else BorderLight,
                                shape = RoundedCornerShape(12.dp)
                            )
                            .clickable {
                                merchantName = preset.name
                                merchantAccount = preset.account
                            }
                            .padding(horizontal = 14.dp, vertical = 12.dp)
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(
                                    imageVector = Icons.Default.Store,
                                    contentDescription = null,
                                    tint = if (isSelected) EmeraldDark else Navy900,
                                    modifier = Modifier.size(22.dp)
                                )
                                Spacer(modifier = Modifier.width(10.dp))
                                Column {
                                    Text(
                                        text = preset.name,
                                        fontSize = 13.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = if (isSelected) EmeraldDark else TextPrimary
                                    )
                                    Text(
                                        text = "${preset.category} • رمز التاجر: ${preset.account}",
                                        fontSize = 10.sp,
                                        color = TextSecondary
                                    )
                                }
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))
            }

            // Input card
            item {
                Card(
                    shape = RoundedCornerShape(18.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(18.dp)
                    ) {
                        Text(
                            text = "اسم المتجر أو التاجر",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Bold,
                            color = TextPrimary
                        )
                        Spacer(modifier = Modifier.height(6.dp))
                        OutlinedTextField(
                            value = merchantName,
                            onValueChange = { merchantName = it },
                            placeholder = { Text("اسم المتجر أو الكاشير") },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            colors = OutlinedTextFieldDefaults.colors(
                                focusedBorderColor = Navy900,
                                unfocusedBorderColor = BorderLight
                            )
                        )

                        Spacer(modifier = Modifier.height(14.dp))

                        Text(
                            text = "رقم حساب أو كود التاجر",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Bold,
                            color = TextPrimary
                        )
                        Spacer(modifier = Modifier.height(6.dp))
                        OutlinedTextField(
                            value = merchantAccount,
                            onValueChange = {
                                merchantAccount = it
                                errorMessage = null
                            },
                            placeholder = { Text("مثال: MER-10023 أو رقم هاتف التاجر") },
                            leadingIcon = {
                                Icon(Icons.Default.Payment, contentDescription = null, tint = Navy900)
                            },
                            modifier = Modifier
                                .fillMaxWidth()
                                .testTag("merchant_account_input"),
                            shape = RoundedCornerShape(12.dp),
                            colors = OutlinedTextFieldDefaults.colors(
                                focusedBorderColor = Navy900,
                                unfocusedBorderColor = BorderLight
                            )
                        )

                        Spacer(modifier = Modifier.height(14.dp))

                        Text(
                            text = "المبلغ المطلوب دفعه (SDG)",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Bold,
                            color = TextPrimary
                        )
                        Spacer(modifier = Modifier.height(6.dp))
                        OutlinedTextField(
                            value = amountText,
                            onValueChange = {
                                amountText = it
                                errorMessage = null
                            },
                            placeholder = { Text("0.00") },
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                            trailingIcon = {
                                Text(
                                    text = "SDG",
                                    fontWeight = FontWeight.Bold,
                                    color = Navy900,
                                    modifier = Modifier.padding(end = 12.dp)
                                )
                            },
                            modifier = Modifier
                                .fillMaxWidth()
                                .testTag("payment_amount_input"),
                            shape = RoundedCornerShape(12.dp),
                            colors = OutlinedTextFieldDefaults.colors(
                                focusedBorderColor = Navy900,
                                unfocusedBorderColor = BorderLight
                            )
                        )

                        if (errorMessage != null) {
                            Spacer(modifier = Modifier.height(10.dp))
                            Text(
                                text = errorMessage ?: "",
                                fontSize = 12.sp,
                                color = Color(0xFFDC2626),
                                fontWeight = FontWeight.Bold
                            )
                        }

                        Spacer(modifier = Modifier.height(20.dp))

                        Button(
                            onClick = {
                                val amount = amountText.toDoubleOrNull()
                                val currentBalance = wallet?.balance ?: 0.0
                                if (merchantAccount.isBlank()) {
                                    errorMessage = "يرجى تحديد كود التاجر أو المتجر"
                                } else if (amount == null || amount <= 0) {
                                    errorMessage = "يرجى إدخال مبلغ دفع صحيح"
                                } else if (amount > currentBalance) {
                                    errorMessage = "رصيد المحفظة غير كافٍ للدفع"
                                } else {
                                    errorMessage = null
                                    viewModel.makePayment(
                                        merchantName.ifBlank { "متجر معتمد ($merchantAccount)" },
                                        merchantAccount,
                                        amount
                                    )
                                    amountText = ""
                                    onNavigateBack()
                                }
                            },
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(52.dp)
                                .testTag("submit_payment_button"),
                            shape = RoundedCornerShape(14.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = EmeraldGreen)
                        ) {
                            Icon(Icons.Default.Payment, contentDescription = null)
                            Spacer(modifier = Modifier.width(8.dp))
                            Text(
                                text = "تأكيد الدفع المباشر",
                                fontSize = 15.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                        }
                    }
                }
            }
        }
    }
}
