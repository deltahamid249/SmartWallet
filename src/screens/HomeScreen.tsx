import React, { useState } from 'react';
import { useWallet } from '../context/WalletContext';
import { ScreenType } from '../types';
import { StatusBadge } from '../components/StatusBadge';
import {
  Send,
  ArrowDownLeft,
  ArrowUpRight,
  Receipt,
  Grid,
  Eye,
  EyeOff,
  ChevronLeft,
  Clock,
  Sparkles,
  ShieldAlert,
} from 'lucide-react';

interface HomeScreenProps {
  setScreen: (screen: ScreenType) => void;
}

export const HomeScreen: React.FC<HomeScreenProps> = ({ setScreen }) => {
  const { currentUser, currentWallet, transactions } = useWallet();
  const [showBalance, setShowBalance] = useState(true);

  const recentTransactions = transactions.slice(0, 5);

  const getTxIcon = (type: string) => {
    switch (type) {
      case 'deposit':
        return { icon: '💵', bg: 'bg-emerald-100 text-emerald-700' };
      case 'withdraw':
        return { icon: '💴', bg: 'bg-amber-100 text-amber-700' };
      case 'transfer_out':
        return { icon: '📤', bg: 'bg-rose-100 text-rose-700' };
      case 'transfer_in':
        return { icon: '📥', bg: 'bg-emerald-100 text-emerald-700' };
      case 'payment':
        return { icon: '🧾', bg: 'bg-blue-100 text-blue-700' };
      case 'recharge':
        return { icon: '📱', bg: 'bg-purple-100 text-purple-700' };
      case 'electricity':
        return { icon: '⚡', bg: 'bg-amber-100 text-amber-700' };
      case 'internet':
        return { icon: '🌐', bg: 'bg-cyan-100 text-cyan-700' };
      case 'bills':
        return { icon: '🧾', bg: 'bg-indigo-100 text-indigo-700' };
      case 'education':
        return { icon: '🎓', bg: 'bg-teal-100 text-teal-700' };
      case 'government':
        return { icon: '🏛️', bg: 'bg-slate-100 text-slate-700' };
      default:
        return { icon: '💳', bg: 'bg-gray-100 text-gray-700' };
    }
  };

  const formatDate = (timestamp: number) => {
    const d = new Date(timestamp);
    return `${d.toLocaleDateString('ar-SD', {
      month: 'short',
      day: 'numeric',
    })} • ${d.toLocaleTimeString('ar-SD', { hour: '2-digit', minute: '2-digit' })}`;
  };

  return (
    <div className="space-y-6 pb-12">
      {/* Balance Card matching NavyPrimary / EmeraldLight aesthetic */}
      <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0D2238] via-[#132B45] to-[#0A1A2C] text-white p-6 sm:p-8 shadow-xl border border-[#1E3A5F]">
        {/* Subtle decorative circles */}
        <div className="absolute -left-12 -bottom-12 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none" />
        <div className="absolute -right-12 -top-12 w-48 h-48 bg-blue-500/10 rounded-full blur-2xl pointer-events-none" />

        <div className="relative z-10">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="text-xs font-semibold text-gray-300">الرصيد الكلي المتاح</span>
              <button
                onClick={() => setShowBalance(!showBalance)}
                className="text-gray-400 hover:text-white transition-colors p-1"
                title={showBalance ? 'إخفاء الرصيد' : 'إظهار الرصيد'}
              >
                {showBalance ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
              </button>
            </div>
            <div className="text-[11px] font-mono bg-white/10 px-2.5 py-1 rounded-full text-gray-300 border border-white/10">
              رقم المحفظة: #{currentWallet?.id ?? '-'}
            </div>
          </div>

          <div className="mt-3 flex items-baseline gap-2">
            <h1 className="text-3xl sm:text-4xl font-black tracking-tight font-mono text-emerald-400">
              {showBalance
                ? (currentWallet?.balance ?? 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  })
                : '••••••••'}
            </h1>
            <span className="text-sm font-bold text-gray-300">جنيه سوداني (SDG)</span>
          </div>

          <div className="mt-4 pt-4 border-t border-white/10 flex items-center justify-between text-xs text-gray-300">
            <div className="flex items-center gap-1.5">
              <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>الحساب: {currentUser?.fullName}</span>
            </div>
            <div className="text-gray-400 font-mono">{currentUser?.phone}</div>
          </div>
        </div>
      </div>

      {/* Quick Action Grid */}
      <div>
        <h2 className="text-sm font-bold text-gray-600 mb-3 px-1">العمليات السريعة</h2>
        <div className="grid grid-cols-5 gap-2 sm:gap-3 text-center">
          <button
            onClick={() => setScreen('transfer')}
            id="quick_transfer_btn"
            className="flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-emerald-50/60 rounded-2xl border border-gray-200 hover:border-emerald-300 transition-all shadow-xs group"
          >
            <div className="w-11 h-11 rounded-2xl bg-emerald-500 text-white flex items-center justify-center mb-2 shadow-sm group-hover:scale-105 transition-transform">
              <Send className="w-5 h-5 -rotate-45 ml-0.5" />
            </div>
            <span className="text-xs font-bold text-[#0D2238] whitespace-nowrap">تحويل</span>
          </button>

          <button
            onClick={() => setScreen('deposit')}
            id="quick_deposit_btn"
            className="flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-blue-50/60 rounded-2xl border border-gray-200 hover:border-blue-300 transition-all shadow-xs group"
          >
            <div className="w-11 h-11 rounded-2xl bg-[#0D2238] text-white flex items-center justify-center mb-2 shadow-sm group-hover:scale-105 transition-transform">
              <ArrowDownLeft className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-[#0D2238] whitespace-nowrap">إيداع</span>
          </button>

          <button
            onClick={() => setScreen('withdraw')}
            id="quick_withdraw_btn"
            className="flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-amber-50/60 rounded-2xl border border-gray-200 hover:border-amber-300 transition-all shadow-xs group"
          >
            <div className="w-11 h-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center mb-2 shadow-sm group-hover:scale-105 transition-transform">
              <ArrowUpRight className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-[#0D2238] whitespace-nowrap">سحب</span>
          </button>

          <button
            onClick={() => setScreen('payments')}
            id="quick_payments_btn"
            className="flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-purple-50/60 rounded-2xl border border-gray-200 hover:border-purple-300 transition-all shadow-xs group"
          >
            <div className="w-11 h-11 rounded-2xl bg-purple-600 text-white flex items-center justify-center mb-2 shadow-sm group-hover:scale-105 transition-transform">
              <Receipt className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-[#0D2238] whitespace-nowrap">مدفوعات</span>
          </button>

          <button
            onClick={() => setScreen('services')}
            id="quick_services_btn"
            className="flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-cyan-50/60 rounded-2xl border border-gray-200 hover:border-cyan-300 transition-all shadow-xs group"
          >
            <div className="w-11 h-11 rounded-2xl bg-cyan-600 text-white flex items-center justify-center mb-2 shadow-sm group-hover:scale-105 transition-transform">
              <Grid className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-[#0D2238] whitespace-nowrap">خدمات</span>
          </button>
        </div>
      </div>

      {/* Feature notice banner */}
      <div className="bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-blue-500/10 border border-emerald-200/60 rounded-2xl p-4 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-lg shadow-xs">
            <Sparkles className="w-5 h-5" />
          </div>
          <div>
            <h4 className="text-sm font-bold text-[#0D2238]">تحويلات فورية بدون أي رسوم</h4>
            <p className="text-xs text-gray-600 mt-0.5">
              حوّل أموالك بين أي محفظة أو رقم هاتف في السودان خلال ثوانٍ وبأمان تام.
            </p>
          </div>
        </div>
        <button
          onClick={() => setScreen('transfer')}
          className="hidden sm:block text-xs font-bold bg-[#0D2238] hover:bg-[#1E3A5F] text-white px-3.5 py-2 rounded-xl transition-all whitespace-nowrap"
        >
          تحويل الآن
        </button>
      </div>

      {/* Recent Transactions List */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200 shadow-xs">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <Clock className="w-4 h-4 text-gray-500" />
            <h3 className="font-black text-sm text-[#0D2238]">آخر المعاملات والعمليات</h3>
          </div>
          <button
            onClick={() => setScreen('transactions')}
            className="text-xs font-bold text-emerald-700 hover:text-emerald-900 flex items-center gap-1 transition-colors"
          >
            <span>عرض الكل</span>
            <ChevronLeft className="w-3.5 h-3.5" />
          </button>
        </div>

        {recentTransactions.length === 0 ? (
          <div className="text-center py-8 text-gray-400 text-xs">لا توجد معاملات مسجلة بعد.</div>
        ) : (
          <div className="space-y-3">
            {recentTransactions.map((tx) => {
              const isPositive = ['deposit', 'transfer_in'].includes(tx.type);
              const { icon, bg } = getTxIcon(tx.type);
              return (
                <div
                  key={tx.id}
                  onClick={() => setScreen('transactions')}
                  className="p-3.5 rounded-2xl hover:bg-[#F4F6F8] transition-colors border border-gray-100 flex items-center justify-between cursor-pointer"
                >
                  <div className="flex items-center gap-3">
                    <div
                      className={`w-11 h-11 rounded-xl flex items-center justify-center text-lg ${bg}`}
                    >
                      {icon}
                    </div>
                    <div>
                      <h4 className="text-sm font-bold text-[#0D2238] max-w-[200px] sm:max-w-xs truncate">
                        {tx.description}
                      </h4>
                      <div className="text-[11px] text-gray-400 font-mono mt-0.5">
                        {tx.reference} • {formatDate(tx.createdAt)}
                      </div>
                    </div>
                  </div>

                  <div className="text-left">
                    <div
                      className={`font-black font-mono text-sm ${
                        isPositive ? 'text-emerald-600' : 'text-[#0D2238]'
                      }`}
                    >
                      {isPositive ? '+' : '-'}
                      {tx.amount.toLocaleString('en-US', { minimumFractionDigits: 2 })}{' '}
                      <span className="text-[10px] font-sans font-bold">SDG</span>
                    </div>
                    <div className="mt-1 flex justify-end">
                      <StatusBadge status={tx.status} />
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
};
