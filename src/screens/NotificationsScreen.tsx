import React from 'react';
import { useWallet } from '../context/WalletContext';
import { ArrowLeft, CheckCheck, Bell, BellRing, Sparkles } from 'lucide-react';

interface NotificationsScreenProps {
  onBack: () => void;
}

export const NotificationsScreen: React.FC<NotificationsScreenProps> = ({ onBack }) => {
  const { notifications, markNotificationAsRead, markAllNotificationsAsRead, unreadCount } =
    useWallet();

  return (
    <div className="space-y-5 pb-12">
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
        <div className="flex items-center gap-2">
          <h2 className="text-base font-black text-[#0D2238]">مركز الإشعارات</h2>
          {unreadCount > 0 && (
            <span className="text-[10px] bg-rose-500 text-white font-black px-2 py-0.5 rounded-full">
              {unreadCount} جديد
            </span>
          )}
        </div>
        {unreadCount > 0 ? (
          <button
            onClick={markAllNotificationsAsRead}
            className="flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1.5 rounded-lg border border-emerald-200 transition-all"
          >
            <CheckCheck className="w-3.5 h-3.5" />
            <span className="hidden sm:inline">تحديد الكل كمقروء</span>
          </button>
        ) : (
          <div className="w-16"></div>
        )}
      </div>

      {/* Notifications List */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200 shadow-xs">
        {notifications.length === 0 ? (
          <div className="text-center py-12 text-gray-400 text-xs">لا توجد إشعارات حالياً.</div>
        ) : (
          <div className="space-y-3">
            {notifications.map((n) => (
              <div
                key={n.id}
                onClick={() => !n.isRead && markNotificationAsRead(n.id)}
                className={`p-4 rounded-2xl border transition-all cursor-pointer flex items-start gap-3.5 ${
                  !n.isRead
                    ? 'bg-blue-50/50 border-blue-200 shadow-xs'
                    : 'bg-white hover:bg-gray-50 border-gray-100'
                }`}
              >
                <div
                  className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5 ${
                    !n.isRead
                      ? 'bg-emerald-100 text-emerald-700'
                      : 'bg-gray-100 text-gray-500'
                  }`}
                >
                  {!n.isRead ? <BellRing className="w-5 h-5" /> : <Bell className="w-5 h-5" />}
                </div>

                <div className="flex-1 overflow-hidden">
                  <div className="flex items-center justify-between">
                    <h4 className="text-sm font-black text-[#0D2238] flex items-center gap-2">
                      {n.title}
                      {!n.isRead && (
                        <span className="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                      )}
                    </h4>
                    <span className="text-[10px] text-gray-400 font-mono">
                      {new Date(n.createdAt).toLocaleDateString('ar-SD')} •{' '}
                      {new Date(n.createdAt).toLocaleTimeString('ar-SD', {
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </span>
                  </div>

                  <p className="text-xs text-gray-600 mt-1 leading-relaxed">{n.message}</p>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};
