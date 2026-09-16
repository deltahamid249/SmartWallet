import React, { useState } from 'react';
import { useWallet } from '../context/WalletContext';
import { ScreenType } from '../types';
import {
  ArrowLeft,
  Bell,
  Fingerprint,
  Moon,
  Globe,
  RefreshCw,
  Info,
  CheckCircle2,
} from 'lucide-react';

interface SettingsScreenProps {
  onBack: () => void;
  setScreen: (screen: ScreenType) => void;
}

export const SettingsScreen: React.FC<SettingsScreenProps> = ({ onBack, setScreen }) => {
  const { resetToDefault } = useWallet();

  const [pushEnabled, setPushEnabled] = useState(true);
  const [biometricsEnabled, setBiometricsEnabled] = useState(true);
  const [smsAlerts, setSmsAlerts] = useState(true);

  const handleReset = () => {
    if (
      window.confirm(
        'هل أنت متأكد من رغبتك في إعادة ضبط جميع بيانات المحفظة إلى الحالة الأولية؟ سيتم مسح أي حسابات مضافة والعودة للحسابات الافتراضية.'
      )
    ) {
      resetToDefault();
      alert('تمت إعادة ضبط البيانات بنجاح.');
    }
  };

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
        <h2 className="text-base font-black text-[#0D2238]">إعدادات التطبيق</h2>
        <div className="w-16"></div>
      </div>

      {/* Security & Preferences */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200 space-y-4">
        <h3 className="font-black text-sm text-[#0D2238]">الأمان والتحقق</h3>

        <div className="flex items-center justify-between py-2 border-b border-gray-100">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
              <Fingerprint className="w-5 h-5" />
            </div>
            <div>
              <span className="text-xs font-bold text-[#0D2238] block">
                تسجيل الدخول بالبصمة والوجه
              </span>
              <span className="text-[11px] text-gray-500">حماية العمليات وتأكيد المدفوعات</span>
            </div>
          </div>
          <input
            type="checkbox"
            checked={biometricsEnabled}
            onChange={(e) => setBiometricsEnabled(e.target.checked)}
            className="w-4 h-4 accent-[#0D2238]"
          />
        </div>

        <div className="flex items-center justify-between py-2 border-b border-gray-100">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
              <Bell className="w-5 h-5" />
            </div>
            <div>
              <span className="text-xs font-bold text-[#0D2238] block">
                الإشعارات والتنبيهات الفورية
              </span>
              <span className="text-[11px] text-gray-500">إشعار فوري عند الإيداع أو التحويل</span>
            </div>
          </div>
          <input
            type="checkbox"
            checked={pushEnabled}
            onChange={(e) => setPushEnabled(e.target.checked)}
            className="w-4 h-4 accent-[#0D2238]"
          />
        </div>

        <div className="flex items-center justify-between py-2">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <CheckCircle2 className="w-5 h-5" />
            </div>
            <div>
              <span className="text-xs font-bold text-[#0D2238] block">رسائل SMS للتأكيد</span>
              <span className="text-[11px] text-gray-500">استلام رسائل نصية برمز التحقق OTP</span>
            </div>
          </div>
          <input
            type="checkbox"
            checked={smsAlerts}
            onChange={(e) => setSmsAlerts(e.target.checked)}
            className="w-4 h-4 accent-[#0D2238]"
          />
        </div>
      </div>

      {/* Regional Settings */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200 space-y-4">
        <h3 className="font-black text-sm text-[#0D2238]">اللغة والعملة</h3>

        <div className="flex items-center justify-between py-2 border-b border-gray-100">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center">
              <Globe className="w-5 h-5" />
            </div>
            <div>
              <span className="text-xs font-bold text-[#0D2238] block">لغة التطبيق</span>
              <span className="text-[11px] text-gray-500">اللغة العربية (السودان)</span>
            </div>
          </div>
          <span className="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg">
            العربية (افتراضي)
          </span>
        </div>

        <div className="flex items-center justify-between py-2">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
              💳
            </div>
            <div>
              <span className="text-xs font-bold text-[#0D2238] block">عملة المعاملات</span>
              <span className="text-[11px] text-gray-500">العملة الرسمية لجميع العمليات</span>
            </div>
          </div>
          <span className="text-xs font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
            الجنيه السوداني (SDG)
          </span>
        </div>
      </div>

      {/* Reset Cache & Data */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200 space-y-3">
        <h3 className="font-black text-sm text-[#0D2238]">إدارة البيانات</h3>
        <p className="text-xs text-gray-500 leading-relaxed">
          يمكنك إعادة ضبط رصيد المحفظة والمعاملات والحسابات التجريبية إلى الوضع الافتراضي في أي وقت.
        </p>
        <button
          onClick={handleReset}
          className="w-full flex items-center justify-center gap-2 py-3 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-xl text-xs border border-rose-200 transition-colors"
        >
          <RefreshCw className="w-4 h-4" />
          <span>إعادة ضبط بيانات المحفظة الافتراضية</span>
        </button>
      </div>

      {/* App Version Info */}
      <div className="text-center py-4 text-xs text-gray-400 space-y-1">
        <p className="font-bold text-[#0D2238]">تطبيق المحفظة الذكية - SmartWallet</p>
        <p className="font-mono text-[11px]">الإصدار: v2.1.0 • بنك السودان المركزي</p>
      </div>
    </div>
  );
};
