import React from 'react';
import { useWallet } from '../context/WalletContext';
import { Home, ArrowLeftRight, LayoutGrid, Bell, User, ShieldCheck } from 'lucide-react';
import { ScreenType } from '../types';

interface NavigationProps {
  currentScreen: ScreenType;
  setScreen: (screen: ScreenType) => void;
}

export const Navigation: React.FC<NavigationProps> = ({ currentScreen, setScreen }) => {
  const { currentUser, unreadCount, allDeposits, allWithdrawals } = useWallet();

  const pendingAdminCount =
    currentUser?.role === 'admin'
      ? allDeposits.filter((d) => d.status === 'pending').length +
        allWithdrawals.filter((w) => w.status === 'pending').length
      : 0;

  const navItems = [
    { id: 'home' as ScreenType, label: 'الرئيسية', icon: Home },
    { id: 'transactions' as ScreenType, label: 'العمليات', icon: ArrowLeftRight },
    { id: 'services' as ScreenType, label: 'الخدمات', icon: LayoutGrid },
    { id: 'notifications' as ScreenType, label: 'الإشعارات', icon: Bell, badge: unreadCount },
    { id: 'profile' as ScreenType, label: 'حسابي', icon: User },
    ...(currentUser?.role === 'admin'
      ? [{ id: 'admin' as ScreenType, label: 'الإدارة', icon: ShieldCheck, badge: pendingAdminCount }]
      : []),
  ];

  return (
    <>
      {/* Top Desktop Navigation (visible on md and above) */}
      <nav className="hidden md:block bg-white border-b border-gray-200">
        <div className="max-w-5xl mx-auto px-4 flex items-center justify-between">
          <div className="flex space-x-reverse space-x-1 py-1">
            {navItems.map((item) => {
              const Icon = item.icon;
              const isActive = currentScreen === item.id;
              return (
                <button
                  key={item.id}
                  onClick={() => setScreen(item.id)}
                  className={`relative flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all ${
                    isActive
                      ? 'bg-[#0D2238] text-white shadow-xs'
                      : 'text-gray-600 hover:text-[#0D2238] hover:bg-gray-100'
                  }`}
                >
                  <Icon className="w-4 h-4" />
                  <span>{item.label}</span>
                  {item.badge !== undefined && item.badge > 0 && (
                    <span
                      className={`text-[10px] font-black px-1.5 py-0.2 rounded-full ${
                        isActive ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'
                      }`}
                    >
                      {item.badge}
                    </span>
                  )}
                </button>
              );
            })}
          </div>

          <div className="flex items-center gap-2 text-xs text-gray-500">
            <span>العملة:</span>
            <span className="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
              الجنيه السوداني (SDG)
            </span>
          </div>
        </div>
      </nav>

      {/* Bottom Mobile Navigation (visible on small screens) */}
      <nav className="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 py-1.5 px-2 shadow-lg">
        <div className="max-w-md mx-auto flex items-center justify-around">
          {navItems.map((item) => {
            const Icon = item.icon;
            const isActive = currentScreen === item.id;
            return (
              <button
                key={item.id}
                onClick={() => setScreen(item.id)}
                className={`relative flex flex-col items-center justify-center py-1 px-2.5 rounded-xl transition-all ${
                  isActive ? 'text-[#0D2238] font-bold' : 'text-gray-400 hover:text-gray-600'
                }`}
              >
                <div className="relative">
                  <Icon className={`w-5 h-5 ${isActive ? 'stroke-[2.5]' : ''}`} />
                  {item.badge !== undefined && item.badge > 0 && (
                    <span className="absolute -top-1.5 -right-2 min-w-[16px] h-4 bg-rose-500 text-white text-[9px] font-black rounded-full px-1 flex items-center justify-center">
                      {item.badge}
                    </span>
                  )}
                </div>
                <span className="text-[11px] mt-0.5">{item.label}</span>
                {isActive && (
                  <span className="w-1.5 h-1.5 rounded-full bg-[#0D2238] mt-0.5"></span>
                )}
              </button>
            );
          })}
        </div>
      </nav>
    </>
  );
};
