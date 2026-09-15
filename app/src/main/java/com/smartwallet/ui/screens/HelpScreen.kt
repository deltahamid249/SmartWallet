package com.smartwallet.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
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
fun HelpScreen(onBack: () -> Unit) {
    val faqs = listOf(
        Pair("كيف يمكنني إرسال الأموال؟", "من الواجهة الرئيسية، اضغط على زر 'إرسال الأموال'، ثم أدخل رقم هاتف المستلم والمبلغ بالجنيه السوداني، واضغط تأكيد وسيتم التحويل فورياً وبدون رسوم."),
        Pair("كيف أقوم بشحن رصيد محفظتي؟", "يمكنك استخدام خيار 'الإيداع الفوري التجريبي' لشحن الرصيد مباشرة أثناء التجربة، أو تقديم إشعار تحويل بنكي عبر بنك الخرطوم (بنكك) أو بنك فيصل ليتم اعتماده من المشرف."),
        Pair("ما هي الخدمات المتاحة للدفع؟", "تتيح المحفظة شحن رصيد شبكات زين وسوداني وMTN، شراء كهرباء وسداد فواتير المياه والإنترنت، وسداد الرسوم الجامعية والخدمات الحكومية."),
        Pair("كيف يتم سحب الأموال نقداً؟", "عبر خيار 'سحب الأموال' واختيار الوكيل المعتمد الأقرب إليك أو التحويل إلى حسابك البنكي، وسيتم تزويدك بكود الاستلام فور موافقة النظام.")
    )

    Scaffold(
        topBar = {
            WalletTopAppBar(
                title = "المساعدة والدعم",
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
                .testTag("help_screen"),
            verticalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            item {
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(18.dp),
                    color = NavyPrimary
                ) {
                    Column(modifier = Modifier.padding(18.dp)) {
                        Text("مركز الدعم الفني وخدمة العملاء", color = Color.White, fontWeight = FontWeight.Bold, fontSize = 16.sp)
                        Spacer(modifier = Modifier.height(6.dp))
                        Text("نحن هنا لمساعدتك على مدار الساعة في أي استفسار يتعلق بالعمليات المالية أو شحن الرصيد.", color = Color(0xFF94A3B8), fontSize = 13.sp)
                        Spacer(modifier = Modifier.height(10.dp))
                        Text("الرقم الموحد: 4949 • info@smartwallet.sd", color = EmeraldLight, fontWeight = FontWeight.Bold, fontSize = 13.sp)
                    }
                }
            }

            item {
                Text("الأسئلة الشائعة", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
            }

            items(faqs.size) { index ->
                val (q, a) = faqs[index]
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    color = Color.White,
                    border = androidx.compose.foundation.BorderStroke(1.dp, BorderLight)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text(q, fontWeight = FontWeight.Bold, fontSize = 14.sp, color = NavyPrimary)
                        Spacer(modifier = Modifier.height(6.dp))
                        Text(a, fontSize = 13.sp, color = TextSecondary, lineHeight = 19.sp)
                    }
                }
            }
        }
    }
}
