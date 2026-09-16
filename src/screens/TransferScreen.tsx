import React, { useState } from 'react';
import { useWallet } from '../context/WalletContext';
import { ScreenType } from '../types';
import { SuccessReceiptModal } from '../components/SuccessReceiptModal';
import { ArrowLeft, Send, UserCheck, AlertCircle, History, Sparkles } from 'lucide-react';

interface TransferScreenProps {
  onBack: () => void;
  setScreen: (screen: ScreenType) => void;
}

export const TransferScreen: React.FC<TransferScreenProps> = ({ onBack, setScreen }) => {
  const { currentWallet, currentUser, allUsers, transfer, transactions } = useWallet();

  const [receiverPhone, setReceiverPhone] = useState('');
  const [amount, setAmount] = useState('');
  const [note, setNote] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [receipt, setReceipt] = useState<{
    reference: string;
    amount: number;
    recipientName: string;
  } | null>(null);

  // Suggested contacts excluding current user
  const suggestedContacts = allUsers.filter((u) => u.id !== currentUser?.id);

  // Past outbound transfers
  const outboundTransfers = transactions.filter((t) => t.type === 'transfer_out');

  const presetAmounts = [1000, 5000, 10000, 20000, 50000];

  const handleTransfer = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    const numAmount = parseFloat(amount);
    if (!receiverPhone.trim()) {
      setError('يرجى إدخال رقم هاتف المستلم.');
      return;
    }
    if (isNaN(numAmount) || numAmount <= 0) {
      setError('يرجى إدخال مبلغ تحويل صحيح بالجنيه السوداني.');
      return;
    }
    if ((currentWallet?.balance ?? 0) < numAmount) {
      setError(
        `رصيدك غير كافٍ. الرصيد الحالي: ${(currentWallet?.balance ?? 0).toLocaleString()} SDG`
      );
      return;
    }

    setLoading(true);
    const result = await transfer(receiverPhone, numAmount, note || null);
    setLoading(false);

    if (result.success) {
      const recipientUser = allUsers.find((u) => u.phone === receiverPhone.trim());
      setReceipt({
        reference: result.reference,
        amount: numAmount,
        recipientName: recipientUser ? recipientUser.fullName : receiverPhone,
      });
      setAmount('');
      setNote('');
    } else {
      setError(result.error || 'حدث خطأ أثناء إجراء التحويل.');
    }
  };

  return (
    <div className="space-y-6 pb-12">
      {/* Top bar */}
      <div className="flex items-center justify-between">
        <button
          onClick={onBack}
          id="back_button"
          className="flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-[#0D2238] bg-white px-3 py-2 rounded-xl border border-gray-200 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 rotate-180" />
          <span>رجوع</span>
        </button>
        <h2 className="text-base font-black text-[#0D2238]">تحويل مالي فوري</h2>
        <div className="w-16"></div>
      </div>

      {/* Available Balance Header */}
      <div className="bg-[#0D2238] text-white p-5 rounded-2xl flex items-center justify-between border border-[#1E3A5F]">
        <div>
          <span className="text-xs text-gray-400">الرصيد المتاح للتحويل</span>
          <div className="text-2xl font-mono font-black text-emerald-400 mt-0.5">
            {(currentWallet?.balance ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}{' '}
            <span className="text-sm font-sans font-bold text-gray-300">SDG</span>
          </div>
        </div>
        <div className="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-xl">
          💸
        </div>
      </div>

      {/* Quick Contacts */}
      {suggestedContacts.length > 0 && (
        <div>
          <span className="text-xs font-bold text-gray-500 mb-2 block">
            جهات اتصال سريعة ومسجلة:
          </span>
          <div className="flex gap-2 overflow-x-auto pb-1">
            {suggestedContacts.map((contact) => (
              <button
                key={contact.id}
                type="button"
                onClick={() => setReceiverPhone(contact.phone)}
                className={`flex items-center gap-2 px-3 py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition-all ${
                  receiverPhone === contact.phone
                    ? 'bg-blue-50 border-blue-500 text-blue-800 ring-2 ring-blue-200'
                    : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50'
                }`}
              >
                <UserCheck className="w-3.5 h-3.5 text-blue-600" />
                <span>{contact.fullName}</span>
                <span className="text-[10px] text-gray-400 font-mono">({contact.phone})</span>
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Transfer Form */}
      <form onSubmit={handleTransfer} className="bg-white rounded-3xl p-6 border border-gray-200 shadow-xs space-y-4">
        <div>
          <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
            رقم هاتف المستلم (سوداني)
          </label>
          <input
            type="tel"
            required
            value={receiverPhone}
            onChange={(e) => setReceiverPhone(e.target.value)}
            placeholder="مثال: 0998765432"
            id="transfer_phone_input"
            className="w-full px-4 py-3 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
          />
        </div>

        <div>
          <div className="flex items-center justify-between mb-1.5">
            <label className="block text-xs font-bold text-[#0D2238]">المبلغ المراد تحويله (SDG)</label>
            <span className="text-[11px] text-emerald-600 font-bold">الرسوم: 0.00 SDG (مجاناً)</span>
          </div>
          <input
            type="number"
            step="any"
            min="1"
            required
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            placeholder="0.00"
            id="transfer_amount_input"
            className="w-full px-4 py-3 bg-[#F4F6F8] border border-gray-200 rounded-xl text-lg font-mono font-bold focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
          />

          {/* Preset amount buttons */}
          <div className="flex flex-wrap gap-1.5 mt-2.5">
            {presetAmounts.map((preset) => (
              <button
                key={preset}
                type="button"
                onClick={() => setAmount(preset.toString())}
                className="px-2.5 py-1 bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 text-gray-700 text-xs font-bold rounded-lg border border-gray-200 transition-colors font-mono"
              >
                +{preset.toLocaleString()}
              </button>
            ))}
          </div>
        </div>

        <div>
          <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
            ملاحظة أو سبب التحويل (اختياري)
          </label>
          <input
            type="text"
            value={note}
            onChange={(e) => setNote(e.target.value)}
            placeholder="مثال: تسديد حساب مشتريات / مصاريف شخصية"
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
          id="transfer_submit_button"
          className="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl transition-all shadow-md active:scale-98 flex items-center justify-center gap-2 text-sm disabled:opacity-50"
        >
          {loading ? (
            <span className="inline-block animate-spin">⏳</span>
          ) : (
            <>
              <Send className="w-4 h-4 -rotate-45" />
              <span>تأكيد التحويل الآن</span>
            </>
          )}
        </button>
      </form>

      {/* Recent Outbound Transfers */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200">
        <div className="flex items-center gap-2 mb-3">
          <History className="w-4 h-4 text-gray-500" />
          <h3 className="font-black text-sm text-[#0D2238]">التحويلات السابقة الصادرة</h3>
        </div>

        {outboundTransfers.length === 0 ? (
          <p className="text-xs text-gray-400 text-center py-4">لم تقم بإجراء تحويلات بعد.</p>
        ) : (
          <div className="space-y-2.5">
            {outboundTransfers.map((tx) => (
              <div
                key={tx.id}
                className="p-3 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-between text-xs"
              >
                <div>
                  <span className="font-bold text-[#0D2238] block">{tx.description}</span>
                  <span className="text-gray-400 font-mono text-[11px]">{tx.reference}</span>
                </div>
                <div className="text-left font-mono font-black text-rose-600">
                  -{tx.amount.toLocaleString()} SDG
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Success Modal */}
      {receipt && (
        <SuccessReceiptModal
          isOpen={true}
          onClose={() => setReceipt(null)}
          title="تم التحويل بنجاح!"
          reference={receipt.reference}
          amount={receipt.amount}
          details={`تم إرسال المبلغ فورياً إلى ${receipt.recipientName} بدون أي استقطاعات.`}
          extraInfo={[
            { label: 'المستلم', value: receipt.recipientName },
            { label: 'الرسوم', value: '0.00 SDG' },
          ]}
        />
      )}
    </div>
  );
};
