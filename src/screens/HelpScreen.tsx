import React, { useState } from 'react';
import { ScreenType } from '../types';
import {
  ArrowLeft,
  PhoneCall,
  Mail,
  MessageSquare,
  HelpCircle,
  ChevronDown,
  ChevronUp,
} from 'lucide-react';

interface HelpScreenProps {
  onBack: () => void;
  setScreen: (screen: ScreenType) => void;
}

export const HelpScreen: React.FC<HelpScreenProps> = ({ onBack, setScreen }) => {
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  const faqs = [
    {
      q: 'كيف يمكنني شحن رصيد المحفظة؟',
      a: 'يمكنك شحن رصيدك عبر طريقتين: الإيداع الفوري التجريبي للاختبار بدون انتظار، أو عبر إشعار تحويل بنكي (تطبيق بنكك، فوري، أو أوكاش) حيث تقوم بإدخال رقم الإشعار ومراجعته من قبل المشرف.',
    },
    {
      q: 'هل هناك أي رسوم على تحويل الأموال بين المستخدمين؟',
      a: 'جميع التحويلات الفورية بين مستخدمي المحفظة الذكية مجانية تماماً وبدون أي استقطاعات إضافية (0.00 SDG).',
    },
    {
      q: 'كيف أقوم بسحب الأموال نقداً؟',
      a: 'من خلال شاشة "سحب"، قم بتحديد طريقة الاستلام (وكيل معتمد، تحويل بنكي بنكك، أو صراف آلي ATM)، وأدخل المبلغ المطلوب. سيتم حجز المبلغ ومراجعته لتسليمه لك فوراً.',
    },
    {
      q: 'كيف أحصل على كود شحن الكهرباء بعد السداد؟',
      a: 'بمجرد تأكيد سداد فاتورة الكهرباء عبر شاشة "الخدمات"، يظهر لك إيصال فوري يحتوي على كود التوكن (Token) المكون من 20 رقماً لتعبئته مباشرة في العداد المنزلي.',
    },
    {
      q: 'ماذا أفعل إذا أدخلت رقم هاتف بالخطأ أثناء التحويل؟',
      a: 'يرجى التأكد من اسم المستلم ورقم الهاتف قبل الضغط على تأكيد التحويل. في حالة الخطأ يمكنك التواصل فوراً مع مركز خدمة العملاء على الرقم 4949.',
    },
  ];

  return (
    <div className="space-y-6 pb-12">
      {/* Top Header */}
      <div className="flex items-center justify-between">
        <button
          onClick={onBack}
          id="back_button"
          className="flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-[#0D2238] bg-white px-3 py-2 rounded-xl border border-gray-200 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 rotate-180" />
          <span>رجوع</span>
        </button>
        <h2 className="text-base font-black text-[#0D2238]">المساعدة ومركز الدعم</h2>
        <div className="w-16"></div>
      </div>

      {/* Contact Channels */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div className="bg-white p-4 rounded-2xl border border-gray-200 text-center space-y-1 shadow-xs">
          <div className="w-10 h-10 mx-auto bg-emerald-100 text-emerald-700 rounded-xl flex items-center justify-center">
            <PhoneCall className="w-5 h-5" />
          </div>
          <h4 className="text-xs font-bold text-[#0D2238]">الرقم الموحد المجاني</h4>
          <p className="text-sm font-mono font-black text-emerald-600">4949</p>
          <p className="text-[10px] text-gray-400">متاح 24/7 طوال الأسبوع</p>
        </div>

        <div className="bg-white p-4 rounded-2xl border border-gray-200 text-center space-y-1 shadow-xs">
          <div className="w-10 h-10 mx-auto bg-blue-100 text-blue-700 rounded-xl flex items-center justify-center">
            <MessageSquare className="w-5 h-5" />
          </div>
          <h4 className="text-xs font-bold text-[#0D2238]">واتساب الدعم السريع</h4>
          <p className="text-xs font-mono font-bold text-[#0D2238]">+249 91 234 5678</p>
          <p className="text-[10px] text-gray-400">رد فوري على الاستفسارات</p>
        </div>

        <div className="bg-white p-4 rounded-2xl border border-gray-200 text-center space-y-1 shadow-xs">
          <div className="w-10 h-10 mx-auto bg-purple-100 text-purple-700 rounded-xl flex items-center justify-center">
            <Mail className="w-5 h-5" />
          </div>
          <h4 className="text-xs font-bold text-[#0D2238]">البريد الإلكتروني</h4>
          <p className="text-xs font-mono font-bold text-gray-700">support@smartwallet.sd</p>
          <p className="text-[10px] text-gray-400">الرد خلال ساعات العمل</p>
        </div>
      </div>

      {/* FAQs */}
      <div className="bg-white rounded-3xl p-6 border border-gray-200 shadow-xs space-y-3">
        <div className="flex items-center gap-2 mb-2">
          <HelpCircle className="w-4 h-4 text-gray-500" />
          <h3 className="font-black text-sm text-[#0D2238]">الأسئلة الشائعة (FAQ)</h3>
        </div>

        <div className="space-y-2">
          {faqs.map((faq, idx) => {
            const isOpen = openFaq === idx;
            return (
              <div
                key={idx}
                className="border border-gray-100 rounded-2xl overflow-hidden transition-colors"
              >
                <button
                  type="button"
                  onClick={() => setOpenFaq(isOpen ? null : idx)}
                  className="w-full p-4 bg-gray-50/70 hover:bg-gray-100 flex items-center justify-between text-right text-xs font-bold text-[#0D2238] transition-colors"
                >
                  <span>{faq.q}</span>
                  {isOpen ? (
                    <ChevronUp className="w-4 h-4 text-gray-500 shrink-0" />
                  ) : (
                    <ChevronDown className="w-4 h-4 text-gray-500 shrink-0" />
                  )}
                </button>
                {isOpen && (
                  <div className="p-4 text-xs text-gray-600 bg-white leading-relaxed border-t border-gray-100">
                    {faq.a}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};
