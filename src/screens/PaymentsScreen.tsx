import React, { useState } from 'react';
import { useWallet } from '../context/WalletContext';
import { ScreenType } from '../types';
import { SuccessReceiptModal } from '../components/SuccessReceiptModal';
import { ArrowLeft, Receipt, Store, AlertCircle, History } from 'lucide-react';

interface PaymentsScreenProps {
  onBack: () => void;
  setScreen: (screen: ScreenType) => void;
}

export const PaymentsScreen: React.FC<PaymentsScreenProps> = ({ onBack, setScreen }) => {
  const { currentWallet, payMerchant, transactions } = useWallet();

  const [merchantName, setMerchantName] = useState('');
  const [merchantAccount, setMerchantAccount] = useState('');
  const [amount, setAmount] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [receipt, setReceipt] = useState<{ reference: string; amount: number } | null>(null);

  const suggestedMerchants = [
    { name: 'سوبرماركت الواحة', account: 'MRC-10023', category: 'تسوق ومواد غذائية' },
    { name: 'صيدلية النيل الكبرى', account: 'MRC-40911', category: 'أدوية وصحة' },
    { name: 'مطاعم ومطابخ الشام', account: 'MRC-50388', category: 'مطاعم وكافيهات' },
    { name: 'إلكترونيات الخرطوم', account: 'MRC-90212', category: 'أجهزة وإلكترونيات' },
  ];

  const paymentTransactions = transactions.filter((t) => t.type === 'payment');

  const selectMerchant = (m: (typeof suggestedMerchants)[0]) => {
    setMerchantName(m.name);
    setMerchantAccount(m.account);
  };

  const handlePay = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    const numAmount = parseFloat(amount);
    if (!merchantName.trim() || !merchantAccount.trim()) {
      setError('يرجى تعبئة اسم التاجر وكود الحساب.');
      return;
    }
    if (isNaN(numAmount) || numAmount <= 0) {
      setError('يرجى إدخال مبلغ دفع صحيح.');
      return;
    }
    if ((currentWallet?.balance ?? 0) < numAmount) {
      setError(
        `الرصيد المتاح غير كافٍ. رصيدك الحالي: ${(currentWallet?.balance ?? 0).toLocaleString()} SDG`
      );
      return;
    }

    setLoading(true);
    const res = await payMerchant(merchantName, merchantAccount, numAmount);
    setLoading(false);

    if (res.success) {
      setReceipt({
        reference: res.reference,
        amount: numAmount,
      });
      setAmount('');
    } else {
      setError(res.error || 'فشلت عملية الدفع.');
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
        <h2 className="text-base font-black text-[#0D2238]">المدفوعات والمشتريات</h2>
        <div className="w-16"></div>
      </div>

      {/* Balance Card */}
      <div className="bg-[#0D2238] text-white p-5 rounded-2xl flex items-center justify-between border border-[#1E3A5F]">
        <div>
          <span className="text-xs text-gray-400">الرصيد المتاح للمدفوعات</span>
          <div className="text-2xl font-mono font-black text-emerald-400 mt-0.5">
            {(currentWallet?.balance ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}{' '}
            <span className="text-sm font-sans font-bold text-gray-300">SDG</span>
          </div>
        </div>
        <div className="w-10 h-10 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-xl">
          🧾
        </div>
      </div>

      {/* Suggested Merchants */}
      <div>
        <span className="text-xs font-bold text-gray-500 mb-2 block">متاجر وتجار مقترحون:</span>
        <div className="grid grid-cols-2 gap-2">
          {suggestedMerchants.map((m) => (
            <button
              key={m.account}
              type="button"
              onClick={() => selectMerchant(m)}
              className={`p-3 rounded-2xl border text-right transition-all flex items-start gap-2.5 ${
                merchantAccount === m.account
                  ? 'bg-purple-50 border-purple-500 ring-2 ring-purple-200'
                  : 'bg-white border-gray-200 hover:bg-gray-50'
              }`}
            >
              <div className="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0 mt-0.5">
                <Store className="w-4 h-4" />
              </div>
              <div className="overflow-hidden">
                <div className="font-bold text-xs text-[#0D2238] truncate">{m.name}</div>
                <div className="text-[10px] text-gray-500">{m.category}</div>
                <div className="text-[10px] text-purple-600 font-mono font-bold mt-0.5">
                  {m.account}
                </div>
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* Payment Form */}
      <form onSubmit={handlePay} className="bg-white rounded-3xl p-6 border border-gray-200 shadow-xs space-y-4">
        <div>
          <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
            اسم التاجر أو المتجر
          </label>
          <input
            type="text"
            required
            value={merchantName}
            onChange={(e) => setMerchantName(e.target.value)}
            placeholder="مثال: سوبرماركت الواحة"
            className="w-full px-4 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0D2238]"
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
            رقم حساب أو كود التاجر
          </label>
          <input
            type="text"
            required
            value={merchantAccount}
            onChange={(e) => setMerchantAccount(e.target.value)}
            placeholder="مثال: MRC-10023"
            className="w-full px-4 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
            مبلغ الفاتورة (SDG)
          </label>
          <input
            type="number"
            min="1"
            required
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            placeholder="0.00"
            id="payment_amount_input"
            className="w-full px-4 py-3 bg-[#F4F6F8] border border-gray-200 rounded-xl text-lg font-mono font-bold focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
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
          id="pay_merchant_button"
          className="w-full py-3.5 bg-purple-600 hover:bg-purple-700 text-white font-black rounded-xl transition-all shadow-md active:scale-98 flex items-center justify-center gap-2 text-sm disabled:opacity-50"
        >
          {loading ? (
            <span className="animate-spin">⏳</span>
          ) : (
            <>
              <Receipt className="w-4 h-4" />
              <span>تأكيد دفع الفاتورة</span>
            </>
          )}
        </button>
      </form>

      {/* Recent Payments History */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200">
        <div className="flex items-center gap-2 mb-3">
          <History className="w-4 h-4 text-gray-500" />
          <h3 className="font-black text-sm text-[#0D2238]">سجل المدفوعات السابقة</h3>
        </div>

        {paymentTransactions.length === 0 ? (
          <p className="text-xs text-gray-400 text-center py-4">لا توجد مدفوعات مسجلة بعد.</p>
        ) : (
          <div className="space-y-3">
            {paymentTransactions.map((tx) => (
              <div
                key={tx.id}
                className="p-3.5 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-between text-xs"
              >
                <div>
                  <div className="font-bold text-[#0D2238]">{tx.description}</div>
                  <div className="text-gray-400 font-mono text-[11px] mt-0.5">
                    {tx.reference} • {new Date(tx.createdAt).toLocaleDateString('ar-SD')}
                  </div>
                </div>

                <div className="text-left font-mono font-black text-sm text-[#0D2238]">
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
          title="تمت عملية الدفع بنجاح!"
          reference={receipt.reference}
          amount={receipt.amount}
          details={`تم سداد الفاتورة لـ ${merchantName} وحساب ${merchantAccount} مباشرة.`}
          extraInfo={[
            { label: 'المتجر', value: merchantName },
            { label: 'كود التاجر', value: merchantAccount },
          ]}
        />
      )}
    </div>
  );
};
