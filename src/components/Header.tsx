import React from 'react';
import { useWallet } from '../context/WalletContext';
import { Bell, Users, Settings, HelpCircle, Shield, ArrowLeftRight } from 'lucide-react';
import { ScreenType } from '../types';

interface HeaderProps {
  currentScreen: ScreenType;
  setScreen: (screen: ScreenType) => void;
  onOpenUserSwitcher: () => void;
}

export const Header: React.FC<HeaderProps> = ({ currentScreen, setScreen, onOpenUserSwitcher }) => {
  const { currentUser, unreadCount, allDeposits, allWithdrawals } = useWallet();

  const pendingAdminCount =
    currentUser?.role === 'admin'
      ? allDeposits.filter((d) => d.status === 'pending').length +
        allWithdrawals.filter((w) => w.status === 'pending').length
      : 0;

  return (
    <header className="sticky top-0 z-40 bg-[#0D2238] text-white shadow-md border-b border-[#1E3A5F]">
      <div className="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
        {/* Left: Brand / Logo */}
        <div className="flex items-center gap-3">
          <button
            onClick={() => setScreen('home')}
            className="flex items-center gap-2.5 hover:opacity-90 transition-opacity text-right"
          >
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-emerald-400 flex items-center justify-center text-xl shadow-inner font-bold">
              💳
            </div>
            <div>
              <div className="flex items-center gap-1.5">
                <span className="font-extrabold text-lg tracking-tight text-white">SmartWallet</span>
                <span className="text-[10px] bg-emerald-500/20 text-emerald-300 font-bold px-1.5 py-0.5 rounded-sm border border-emerald-500/30">
                  SDG
                </span>
              </div>
              <p className="text-[11px] text-gray-300">المحفظة الذكية</p>
            </div>
          </button>
        </div>

        {/* Center / Right: Active User and Controls */}
        <div className="flex items-center gap-2 sm:gap-3">
          {/* User badge with fast switcher */}
          <button
            onClick={onOpenUserSwitcher}
            className="flex items-center gap-2 bg-[#132B45] hover:bg-[#1E3A5F] px-3 py-1.5 rounded-xl border border-[#1E3A5F] transition-all text-xs"
            title="تبديل الحساب"
          >
            <div className="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-xs">
              {currentUser?.role === 'admin' ? '🛡️' : '👤'}
            </div>
            <div className="text-right hidden sm:block">
              <div className="font-bold text-gray-100 max-w-[120px] truncate">
                {currentUser?.fullName || 'المستخدم'}
              </div>
              <div className="text-[10px] text-emerald-400 font-mono">{currentUser?.phone}</div>
            </div>
            <ArrowLeftRight className="w-3.5 h-3.5 text-gray-400" />
          </button>

          {/* Admin shortcut if user is admin */}
          {currentUser?.role === 'admin' && (
            <button
              onClick={() => setScreen('admin')}
              className={`relative p-2 rounded-xl border transition-all ${
                currentScreen === 'admin'
                  ? 'bg-amber-500 text-slate-900 border-amber-400 font-bold'
                  : 'bg-[#132B45] text-amber-300 border-amber-500/30 hover:bg-[#1E3A5F]'
              }`}
              title="لوحة الإدارة"
            >
              <Shield className="w-4 h-4" />
              {pendingAdminCount > 0 && (
                <span className="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                  {pendingAdminCount}
                </span>
              )}
            </button>
          )}

          {/* Notifications bell */}
          <button
            onClick={() => setScreen('notifications')}
            className={`relative p-2 rounded-xl border transition-all ${
              currentScreen === 'notifications'
                ? 'bg-emerald-600 text-white border-emerald-500'
                : 'bg-[#132B45] text-gray-200 border-[#1E3A5F] hover:bg-[#1E3A5F]'
            }`}
            title="الإشعارات"
          >
            <Bell className="w-4 h-4" />
            {unreadCount > 0 && (
              <span className="absolute -top-1 -right-1 w-4 h-4 bg-emerald-500 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse">
                {unreadCount}
              </span>
            )}
          </button>

          {/* Settings shortcut */}
          <button
            onClick={() => setScreen('settings')}
            className={`p-2 rounded-xl border transition-all hidden xs:flex ${
              currentScreen === 'settings'
                ? 'bg-emerald-600 text-white border-emerald-500'
                : 'bg-[#132B45] text-gray-200 border-[#1E3A5F] hover:bg-[#1E3A5F]'
            }`}
            title="الإعدادات"
          >
            <Settings className="w-4 h-4" />
          </button>

          {/* Help shortcut */}
          <button
            onClick={() => setScreen('help')}
            className={`p-2 rounded-xl border transition-all hidden xs:flex ${
              currentScreen === 'help'
                ? 'bg-emerald-600 text-white border-emerald-500'
                : 'bg-[#132B45] text-gray-200 border-[#1E3A5F] hover:bg-[#1E3A5F]'
            }`}
            title="المساعدة والدعم"
          >
            <HelpCircle className="w-4 h-4" />
          </button>
        </div>
      </div>
    </header>
  );
};
