import React from 'react';
import { useWallet } from '../context/WalletContext';
import { ScreenType } from '../types';
import {
  ArrowLeft,
  User,
  Phone,
  Mail,
  Shield,
  CreditCard,
  ArrowLeftRight,
  Settings,
  HelpCircle,
  ShieldCheck,
  CheckCircle2,
} from 'lucide-react';

interface ProfileScreenProps {
  onBack: () => void;
  setScreen: (screen: ScreenType) => void;
  onOpenUserSwitcher: () => void;
}

export const ProfileScreen: React.FC<ProfileScreenProps> = ({
  onBack,
  setScreen,
  onOpenUserSwitcher,
}) => {
  const { currentUser, currentWallet } = useWallet();

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
        <h2 className="text-base font-black text-[#0D2238]">الملف الشخصي والحساب</h2>
        <div className="w-16"></div>
      </div>

      {/* User Hero Card */}
      <div className="bg-white rounded-3xl p-6 border border-gray-200 shadow-xs text-center relative overflow-hidden">
        <div className="w-20 h-20 mx-auto rounded-full bg-gradient-to-tr from-[#0D2238] to-[#1E3A5F] text-white flex items-center justify-center text-3xl font-black mb-3 ring-4 ring-gray-100">
          {currentUser?.role === 'admin' ? '🛡️' : '👤'}
        </div>

        <h3 className="text-lg font-black text-[#0D2238]">{currentUser?.fullName}</h3>
        <p className="text-xs text-gray-500 font-mono mt-0.5">{currentUser?.phone}</p>

        <div className="mt-3 flex items-center justify-center gap-2">
          <span
            className={`text-xs font-bold px-3 py-1 rounded-full border ${
              currentUser?.role === 'admin'
                ? 'bg-amber-100 text-amber-800 border-amber-200'
                : 'bg-blue-100 text-blue-800 border-blue-200'
            }`}
          >
            {currentUser?.role === 'admin' ? 'مشرف عام للنظام' : 'مستخدم المحفظة'}
          </span>

          <span className="text-xs font-bold px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
            <CheckCircle2 className="w-3.5 h-3.5" />
            <span>حساب موثق ونشط</span>
          </span>
        </div>
      </div>

      {/* Wallet Information */}
      <div className="bg-[#0D2238] text-white rounded-3xl p-6 border border-[#1E3A5F] space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <CreditCard className="w-5 h-5 text-emerald-400" />
            <h4 className="font-bold text-sm">بيانات المحفظة الرقمية</h4>
          </div>
          <span className="text-[11px] font-mono text-gray-400">
            ID: #{currentWallet?.id}
          </span>
        </div>

        <div className="pt-2">
          <span className="text-xs text-gray-400">الرصيد الفعلي الحالي:</span>
          <div className="text-3xl font-mono font-black text-emerald-400 mt-1">
            {(currentWallet?.balance ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}{' '}
            <span className="text-sm font-sans font-bold text-gray-300">SDG</span>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-3 pt-2 text-xs border-t border-white/10 text-gray-300">
          <div>
            <span className="text-gray-400 block">العملة المعتمدة:</span>
            <span className="font-bold text-white">الجنيه السوداني (SDG)</span>
          </div>
          <div>
            <span className="text-gray-400 block">حالة المحفظة:</span>
            <span className="font-bold text-emerald-400">جاهزة للمعاملات</span>
          </div>
        </div>
      </div>

      {/* Account Details */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200 space-y-3">
        <h4 className="text-xs font-bold text-gray-500 mb-2">معلومات الاتصال والحساب</h4>

        <div className="flex items-center justify-between p-3 bg-gray-50 rounded-xl text-xs">
          <div className="flex items-center gap-2 text-gray-600">
            <Phone className="w-4 h-4 text-gray-400" />
            <span>رقم الهاتف:</span>
          </div>
          <span className="font-mono font-bold text-[#0D2238]">{currentUser?.phone}</span>
        </div>

        <div className="flex items-center justify-between p-3 bg-gray-50 rounded-xl text-xs">
          <div className="flex items-center gap-2 text-gray-600">
            <Mail className="w-4 h-4 text-gray-400" />
            <span>البريد الإلكتروني:</span>
          </div>
          <span className="font-mono font-bold text-[#0D2238]">
            {currentUser?.email || 'غير مسجل'}
          </span>
        </div>

        <div className="flex items-center justify-between p-3 bg-gray-50 rounded-xl text-xs">
          <div className="flex items-center gap-2 text-gray-600">
            <Shield className="w-4 h-4 text-gray-400" />
            <span>الصلاحية:</span>
          </div>
          <span className="font-bold text-[#0D2238]">
            {currentUser?.role === 'admin' ? 'مدير نظام (Admin)' : 'مستخدم عادي (User)'}
          </span>
        </div>
      </div>

      {/* Action shortcuts */}
      <div className="space-y-2">
        <button
          onClick={onOpenUserSwitcher}
          className="w-full flex items-center justify-between p-4 bg-white hover:bg-gray-50 border border-gray-200 rounded-2xl transition-colors text-xs font-bold text-[#0D2238]"
        >
          <div className="flex items-center gap-2.5">
            <ArrowLeftRight className="w-4 h-4 text-blue-600" />
            <span>تبديل الحساب التجريبي / تسجيل حساب جديد</span>
          </div>
          <span className="text-gray-400">تغيير</span>
        </button>

        {currentUser?.role === 'admin' && (
          <button
            onClick={() => setScreen('admin')}
            className="w-full flex items-center justify-between p-4 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-2xl transition-colors text-xs font-bold text-amber-900"
          >
            <div className="flex items-center gap-2.5">
              <ShieldCheck className="w-4 h-4 text-amber-700" />
              <span>لوحة تحكم وإدارة العمليات للمشرف</span>
            </div>
            <span className="text-amber-700">فتح</span>
          </button>
        )}

        <button
          onClick={() => setScreen('settings')}
          className="w-full flex items-center justify-between p-4 bg-white hover:bg-gray-50 border border-gray-200 rounded-2xl transition-colors text-xs font-bold text-[#0D2238]"
        >
          <div className="flex items-center gap-2.5">
            <Settings className="w-4 h-4 text-gray-600" />
            <span>إعدادات التطبيق والأمان</span>
          </div>
          <span className="text-gray-400">فتح</span>
        </button>

        <button
          onClick={() => setScreen('help')}
          className="w-full flex items-center justify-between p-4 bg-white hover:bg-gray-50 border border-gray-200 rounded-2xl transition-colors text-xs font-bold text-[#0D2238]"
        >
          <div className="flex items-center gap-2.5">
            <HelpCircle className="w-4 h-4 text-gray-600" />
            <span>مركز الدعم والمساعدة</span>
          </div>
          <span className="text-gray-400">فتح</span>
        </button>
      </div>
    </div>
  );
};
