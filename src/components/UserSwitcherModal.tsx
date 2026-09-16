import React, { useState } from 'react';
import { useWallet } from '../context/WalletContext';
import { UserPlus, Check, X, Shield, User as UserIcon, RefreshCw } from 'lucide-react';

interface UserSwitcherModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const UserSwitcherModal: React.FC<UserSwitcherModalProps> = ({ isOpen, onClose }) => {
  const { allUsers, currentUser, switchUser, registerNewUser, allWallets, resetToDefault } = useWallet();

  const [isAdding, setIsAdding] = useState(false);
  const [fullName, setFullName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  if (!isOpen) return null;

  const handleSelect = (userId: number) => {
    switchUser(userId);
    onClose();
  };

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    const res = await registerNewUser(fullName, phone, email || null);
    setLoading(false);
    if (res.success) {
      setIsAdding(false);
      setFullName('');
      setPhone('');
      setEmail('');
      onClose();
    } else {
      setError(res.message);
    }
  };

  const handleReset = () => {
    if (window.confirm('هل أنت متأكد من رغبتك في إعادة ضبط بيانات المحفظة إلى الإعدادات الأولية؟')) {
      resetToDefault();
      onClose();
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-gray-100 p-6 text-right">
        <div className="flex items-center justify-between pb-4 border-b border-gray-100">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
              👥
            </div>
            <h3 className="text-lg font-black text-[#0D2238]">
              {isAdding ? 'تسجيل حساب محفظة جديد' : 'تبديل الحساب التجريبي'}
            </h3>
          </div>
          <button
            onClick={onClose}
            className="p-1 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {!isAdding ? (
          <div className="mt-4 space-y-4">
            <p className="text-xs text-gray-500">
              يمكنك التبديل بين الحسابات لاختبار التحويل الفوري المتبادل بين المستخدمين، أو الدخول كمدير نظام لاعتماد طلبات الإيداع والسحب.
            </p>

            <div className="space-y-2.5 max-h-[300px] overflow-y-auto pr-1">
              {allUsers.map((user) => {
                const isCurrent = user.id === currentUser?.id;
                const wallet = allWallets.find((w) => w.userId === user.id);
                return (
                  <div
                    key={user.id}
                    onClick={() => handleSelect(user.id)}
                    className={`p-3.5 rounded-xl border transition-all cursor-pointer flex items-center justify-between ${
                      isCurrent
                        ? 'bg-blue-50/70 border-blue-500 shadow-xs'
                        : 'bg-white hover:bg-gray-50 border-gray-200'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <div
                        className={`w-10 h-10 rounded-full flex items-center justify-center font-bold ${
                          user.role === 'admin'
                            ? 'bg-amber-100 text-amber-700'
                            : 'bg-blue-100 text-blue-700'
                        }`}
                      >
                        {user.role === 'admin' ? (
                          <Shield className="w-5 h-5" />
                        ) : (
                          <UserIcon className="w-5 h-5" />
                        )}
                      </div>
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-bold text-sm text-[#0D2238]">{user.fullName}</span>
                          {user.role === 'admin' ? (
                            <span className="text-[10px] bg-amber-100 text-amber-800 font-bold px-1.5 py-0.2 rounded-sm">
                              مشرف
                            </span>
                          ) : (
                            <span className="text-[10px] bg-gray-100 text-gray-600 font-medium px-1.5 py-0.2 rounded-sm">
                              مستخدم
                            </span>
                          )}
                        </div>
                        <div className="text-xs text-gray-500 font-mono mt-0.5">{user.phone}</div>
                      </div>
                    </div>

                    <div className="text-left">
                      <div className="font-bold text-sm text-emerald-600 font-mono">
                        {(wallet?.balance ?? 0).toLocaleString()} SDG
                      </div>
                      {isCurrent && (
                        <span className="text-[10px] text-blue-600 font-bold flex items-center gap-0.5 justify-end mt-0.5">
                          <Check className="w-3 h-3" /> الحساب الحالي
                        </span>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>

            <div className="pt-2 flex items-center justify-between border-t border-gray-100">
              <button
                type="button"
                onClick={() => setIsAdding(true)}
                className="flex items-center gap-1.5 text-xs font-bold text-[#1E3A5F] hover:text-[#0D2238] bg-blue-50 hover:bg-blue-100 px-3 py-2 rounded-xl transition-all"
              >
                <UserPlus className="w-4 h-4" />
                <span>+ تسجيل حساب جديد</span>
              </button>

              <button
                type="button"
                onClick={handleReset}
                className="flex items-center gap-1 text-xs text-gray-400 hover:text-rose-600 transition-colors p-1"
                title="إعادة ضبط البيانات الأصلية"
              >
                <RefreshCw className="w-3.5 h-3.5" />
                <span>إعادة ضبط البيانات</span>
              </button>
            </div>
          </div>
        ) : (
          <form onSubmit={handleRegister} className="mt-4 space-y-3.5">
            <div>
              <label className="block text-xs font-bold text-gray-700 mb-1">الاسم الكامل رباعي</label>
              <input
                type="text"
                required
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
                placeholder="مثال: محمد عبد الله إبراهيم"
                className="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0D2238]"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-gray-700 mb-1">رقم الهاتف (سوداني)</label>
              <input
                type="tel"
                required
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="09xxxxxxxx أو 01xxxxxxxx"
                className="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0D2238] font-mono text-right"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-gray-700 mb-1">
                البريد الإلكتروني (اختياري)
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="example@domain.com"
                className="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right font-mono"
              />
            </div>

            {error && (
              <div className="p-2.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl font-bold">
                {error}
              </div>
            )}

            <div className="pt-2 flex gap-2">
              <button
                type="submit"
                disabled={loading}
                className="flex-1 py-2.5 bg-[#0D2238] hover:bg-[#1E3A5F] text-white font-bold rounded-xl text-sm transition-all shadow-sm"
              >
                {loading ? 'جاري الإنشاء...' : 'تأكيد إنشاء المحفظة'}
              </button>
              <button
                type="button"
                onClick={() => setIsAdding(false)}
                className="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm transition-all"
              >
                رجوع
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
};
