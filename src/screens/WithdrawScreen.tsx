import React, { useState } from 'react';
import { useWallet } from '../context/WalletContext';
import { ScreenType } from '../types';
import { StatusBadge } from '../components/StatusBadge';
import { SuccessReceiptModal } from '../components/SuccessReceiptModal';
import { ArrowLeft, ArrowUpRight, AlertCircle, History, MapPin } from 'lucide-react';

interface WithdrawScreenProps {
  onBack: () => void;
  setScreen: (screen: ScreenType) => void;
}

export const WithdrawScreen: React.FC<WithdrawScreenProps> = ({ onBack, setScreen }) => {
  const { currentWallet, currentUser, submitWithdrawal, userWithdrawals } = useWallet();

  const [recipientName, setRecipientName] = useState(currentUser?.fullName || '');
  const [recipientPhone, setRecipientPhone] = useState(currentUser?.phone || '');
  const [method, setMethod] = useState('وكيل معتمد (Cash Agent)');
  const [amount, setAmount] = useState('');
  const [note, setNote] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [receipt, setReceipt] = useState<{ reference: string; amount: number } | null>(null);

  const methodOptions = [
    { id: 'وكيل معتمد (Cash Agent)', label: 'وكيل معتمد (Cash Agent)', desc: 'استلام نقدي فوري عبر شبكة الوكلاء في السودان' },
    { id: 'تحويل بنكي مباشر (بنكك)', label: 'تحويل بنكي مباشر (بنكك)', desc: 'تحويل مباشر إلى حسابك في بنك الخرطوم' },
    { id: 'صراف آلي (ATM Code)', label: 'صراف آلي (ATM Code)', desc: 'سحب من ماكينات الصراف الآلي بدون بطاقة' },
  ];

  const handleWithdrawal = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    const numAmount = parseFloat(amount);
    if (!recipientName.trim() || !recipientPhone.trim()) {
      setError('يرجى تعبئة اسم ورقم هاتف المستلم.');
      return;
    }
    if (isNaN(numAmount) || numAmount <= 0) {
      setError('يرجى إدخال مبلغ سحب صحيح.');
      return;
    }
    if ((currentWallet?.balance ?? 0) < numAmount) {
      setError(
        `الرصيد المتاح غير كافٍ. رصيدك الحالي: ${(currentWallet?.balance ?? 0).toLocaleString()} SDG`
      );
      return;
    }

    setLoading(true);
    const res = await submitWithdrawal(numAmount, recipientName, recipientPhone, method, note || null);
    setLoading(false);

    if (res.success) {
      setReceipt({
        reference: res.reference,
        amount: numAmount,
      });
      setAmount('');
      setNote('');
    } else {
      setError(res.error || 'حدث خطأ أثناء تقديم طلب السحب.');
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
        <h2 className="text-base font-black text-[#0D2238]">سحب الأموال نقداً</h2>
        <div className="w-16"></div>
      </div>

      {/* Balance Card */}
      <div className="bg-[#0D2238] text-white p-5 rounded-2xl flex items-center justify-between border border-[#1E3A5F]">
        <div>
          <span className="text-xs text-gray-400">الرصيد المتاح للسحب</span>
          <div className="text-2xl font-mono font-black text-emerald-400 mt-0.5">
            {(currentWallet?.balance ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}{' '}
            <span className="text-sm font-sans font-bold text-gray-300">SDG</span>
          </div>
        </div>
        <div className="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-xl">
          💴
        </div>
      </div>

      {/* Withdrawal Form */}
      <form onSubmit={handleWithdrawal} className="bg-white rounded-3xl p-6 border border-gray-200 shadow-xs space-y-4">
        <div>
          <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
            اسم المستلم رباعي
          </label>
          <input
            type="text"
            required
            value={recipientName}
            onChange={(e) => setRecipientName(e.target.value)}
            placeholder="الاسم الكامل"
            className="w-full px-4 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0D2238]"
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
            رقم هاتف المستلم
          </label>
          <input
            type="tel"
            required
            value={recipientPhone}
            onChange={(e) => setRecipientPhone(e.target.value)}
            placeholder="09xxxxxxxx"
            className="w-full px-4 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-[#0D2238] mb-2">طريقة استلام المبلغ:</label>
          <div className="space-y-2">
            {methodOptions.map((opt) => (
              <label
                key={opt.id}
                className={`flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all ${
                  method === opt.id
                    ? 'bg-amber-50/60 border-amber-400 ring-1 ring-amber-300'
                    : 'bg-gray-50 border-gray-200 hover:bg-gray-100'
                }`}
              >
                <input
                  type="radio"
                  name="withdrawal_method"
                  checked={method === opt.id}
                  onChange={() => setMethod(opt.id)}
                  className="mt-1 accent-amber-600"
                />
                <div>
                  <span className="text-xs font-bold text-[#0D2238] block">{opt.label}</span>
                  <span className="text-[11px] text-gray-500">{opt.desc}</span>
                </div>
              </label>
            ))}
          </div>
        </div>

        <div>
          <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
            المبلغ المراد سحبه (SDG)
          </label>
          <input
            type="number"
            min="100"
            required
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            placeholder="0.00"
            id="withdraw_amount_input"
            className="w-full px-4 py-3 bg-[#F4F6F8] border border-gray-200 rounded-xl text-lg font-mono font-bold focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
            ملاحظة أو تفاصيل الموقع / الفرع
          </label>
          <input
            type="text"
            value={note}
            onChange={(e) => setNote(e.target.value)}
            placeholder="مثال: فرع السوق العربي - الخرطوم"
            className="w-full px-4 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0D2238]"
          />
        </div>

        {error && (
          <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2 text-xs font-bold text-rose-700">
            <AlertCircle className="w-4 h-4 shrink-0" />
            <span>{error}</span>
          </div>
        )}

        <button
          type="submit"
          disabled={loading}
          id="withdraw_submit_button"
          className="w-full py-3.5 bg-[#0D2238] hover:bg-[#1E3A5F] text-white font-black rounded-xl transition-all shadow-md active:scale-98 flex items-center justify-center gap-2 text-sm disabled:opacity-50"
        >
          {loading ? (
            <span className="animate-spin">⏳</span>
          ) : (
            <>
              <ArrowUpRight className="w-4 h-4" />
              <span>إرسال طلب السحب</span>
            </>
          )}
        </button>
      </form>

      {/* Previous Withdrawal Requests */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200">
        <div className="flex items-center gap-2 mb-3">
          <History className="w-4 h-4 text-gray-500" />
          <h3 className="font-black text-sm text-[#0D2238]">طلبات السحب السابقة</h3>
        </div>

        {userWithdrawals.length === 0 ? (
          <p className="text-xs text-gray-400 text-center py-4">لا توجد طلبات سحب سابقة.</p>
        ) : (
          <div className="space-y-3">
            {userWithdrawals.map((wth) => (
              <div
                key={wth.id}
                className="p-3.5 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-between text-xs"
              >
                <div>
                  <div className="font-bold text-[#0D2238]">{wth.withdrawalMethod}</div>
                  <div className="text-gray-500 text-[11px] mt-0.5">
                    المستلم: {wth.recipientName} ({wth.recipientPhone})
                  </div>
                  <div className="text-gray-400 text-[10px]">
                    {new Date(wth.createdAt).toLocaleDateString('ar-SD')}
                  </div>
                </div>

                <div className="text-left">
                  <div className="font-mono font-black text-sm text-[#0D2238]">
                    -{wth.amount.toLocaleString()} SDG
                  </div>
                  <div className="mt-1 flex justify-end">
                    <StatusBadge status={wth.status} />
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Receipt Modal */}
      {receipt && (
        <SuccessReceiptModal
          isOpen={true}
          onClose={() => setReceipt(null)}
          title="تم استلام طلب السحب بنجاح!"
          reference={receipt.reference}
          amount={receipt.amount}
          details="طلبك قيد المراجعة الإدارية. سيتم تجهيز المبلغ وإشعارك برمز الاستلام فور اعتماده."
          extraInfo={[
            { label: 'طريقة السحب', value: method },
            { label: 'المستلم', value: recipientName },
          ]}
        />
      )}
    </div>
  );
};
