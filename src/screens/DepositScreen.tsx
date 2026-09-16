import React, { useState } from 'react';
import { useWallet } from '../context/WalletContext';
import { ScreenType } from '../types';
import { StatusBadge } from '../components/StatusBadge';
import { SuccessReceiptModal } from '../components/SuccessReceiptModal';
import { ArrowLeft, ArrowDownLeft, Landmark, Zap, AlertCircle, History, CheckCircle2 } from 'lucide-react';

interface DepositScreenProps {
  onBack: () => void;
  setScreen: (screen: ScreenType) => void;
}

export const DepositScreen: React.FC<DepositScreenProps> = ({ onBack, setScreen }) => {
  const { currentWallet, submitInstantDeposit, submitBankDeposit, userDeposits } = useWallet();

  const [mode, setMode] = useState<'instant' | 'bank'>('instant');

  // Instant deposit state
  const [instantAmount, setInstantAmount] = useState('25000');
  const [instantLoading, setInstantLoading] = useState(false);
  const [instantReceipt, setInstantReceipt] = useState<{ reference: string; amount: number } | null>(null);

  // Bank transfer notice state
  const [bankName, setBankName] = useState('بنك الخرطوم (بنكك)');
  const [senderAccount, setSenderAccount] = useState('');
  const [bankRef, setBankRef] = useState('');
  const [bankAmount, setBankAmount] = useState('');
  const [bankNote, setBankNote] = useState('');
  const [bankLoading, setBankLoading] = useState(false);
  const [bankError, setBankError] = useState<string | null>(null);
  const [bankReceipt, setBankReceipt] = useState<{ reference: string; amount: number } | null>(null);

  const bankOptions = [
    'بنك الخرطوم (بنكك)',
    'بنك فيصل الإسلامي (فوري)',
    'بنك أمدرمان الوطني (أوكاش)',
    'بنك النيلين',
    'البنك الأهلي السوداني',
  ];

  const presetInstant = [5000, 10000, 25000, 50000, 100000];

  const handleInstantDeposit = async () => {
    const num = parseFloat(instantAmount);
    if (isNaN(num) || num <= 0) return;

    setInstantLoading(true);
    const res = await submitInstantDeposit(num);
    setInstantLoading(false);

    if (res.success) {
      setInstantReceipt({
        reference: res.reference,
        amount: num,
      });
    }
  };

  const handleBankDeposit = async (e: React.FormEvent) => {
    e.preventDefault();
    setBankError(null);

    const num = parseFloat(bankAmount);
    if (isNaN(num) || num <= 0) {
      setBankError('يرجى إدخال مبلغ الإيداع.');
      return;
    }
    if (!bankRef.trim()) {
      setBankError('يرجى إدخال رقم المعاملة البنكية أو رقم الإشعار.');
      return;
    }

    setBankLoading(true);
    const res = await submitBankDeposit(num, bankName, senderAccount, bankRef, bankNote || null);
    setBankLoading(false);

    if (res.success) {
      setBankReceipt({
        reference: bankRef,
        amount: num,
      });
      setSenderAccount('');
      setBankRef('');
      setBankAmount('');
      setBankNote('');
    } else {
      setBankError(res.error || 'حدث خطأ أثناء تقديم الطلب.');
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
        <h2 className="text-base font-black text-[#0D2238]">شحن وإيداع الرصيد</h2>
        <div className="w-16"></div>
      </div>

      {/* Balance Card */}
      <div className="bg-[#0D2238] text-white p-5 rounded-2xl flex items-center justify-between border border-[#1E3A5F]">
        <div>
          <span className="text-xs text-gray-400">رصيد المحفظة الحالي</span>
          <div className="text-2xl font-mono font-black text-emerald-400 mt-0.5">
            {(currentWallet?.balance ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}{' '}
            <span className="text-sm font-sans font-bold text-gray-300">SDG</span>
          </div>
        </div>
        <div className="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-xl">
          📥
        </div>
      </div>

      {/* Method Tabs */}
      <div className="flex p-1 bg-gray-200/80 rounded-2xl text-xs font-bold">
        <button
          type="button"
          onClick={() => setMode('instant')}
          className={`flex-1 py-2.5 rounded-xl flex items-center justify-center gap-2 transition-all ${
            mode === 'instant'
              ? 'bg-[#0D2238] text-white shadow-xs'
              : 'text-gray-600 hover:text-[#0D2238]'
          }`}
        >
          <Zap className="w-4 h-4 text-emerald-400" />
          <span>إيداع فوري تجريبي</span>
        </button>
        <button
          type="button"
          onClick={() => setMode('bank')}
          className={`flex-1 py-2.5 rounded-xl flex items-center justify-center gap-2 transition-all ${
            mode === 'bank'
              ? 'bg-[#0D2238] text-white shadow-xs'
              : 'text-gray-600 hover:text-[#0D2238]'
          }`}
        >
          <Landmark className="w-4 h-4 text-blue-400" />
          <span>إشعار تحويل بنكي</span>
        </button>
      </div>

      {/* Mode 1: Instant Demo Deposit */}
      {mode === 'instant' ? (
        <div className="bg-white rounded-3xl p-6 border border-gray-200 shadow-xs space-y-5">
          <div>
            <span className="text-xs font-bold text-[#0D2238] block mb-2">
              اختر المبلغ المطلوب شحنه فورياً:
            </span>
            <div className="grid grid-cols-3 sm:grid-cols-5 gap-2">
              {presetInstant.map((p) => (
                <button
                  key={p}
                  type="button"
                  onClick={() => setInstantAmount(p.toString())}
                  className={`py-3 px-2 rounded-xl text-xs font-mono font-black border transition-all text-center ${
                    instantAmount === p.toString()
                      ? 'bg-emerald-50 border-emerald-500 text-emerald-800 ring-2 ring-emerald-200'
                      : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                  }`}
                >
                  {p.toLocaleString()}
                  <span className="block text-[10px] font-sans font-normal text-gray-500">SDG</span>
                </button>
              ))}
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
              أو أدخل مبلغا مخصصاً (SDG):
            </label>
            <input
              type="number"
              min="100"
              value={instantAmount}
              onChange={(e) => setInstantAmount(e.target.value)}
              placeholder="0.00"
              className="w-full px-4 py-3 bg-[#F4F6F8] border border-gray-200 rounded-xl text-lg font-mono font-bold focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
            />
          </div>

          <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center gap-2.5 text-xs text-emerald-800">
            <CheckCircle2 className="w-4 h-4 shrink-0 text-emerald-600" />
            <span>سيتم إضافة المبلغ إلى رصيد محفظتك مباشرة بدون انتظار موافقة.</span>
          </div>

          <button
            type="button"
            onClick={handleInstantDeposit}
            disabled={instantLoading || !parseFloat(instantAmount)}
            id="instant_deposit_btn"
            className="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl transition-all shadow-md active:scale-98 flex items-center justify-center gap-2 text-sm disabled:opacity-50"
          >
            {instantLoading ? (
              <span className="animate-spin">⏳</span>
            ) : (
              <>
                <Zap className="w-4 h-4 fill-current" />
                <span>إيداع فوري في المحفظة</span>
              </>
            )}
          </button>
        </div>
      ) : (
        /* Mode 2: Bank Deposit Request */
        <form
          onSubmit={handleBankDeposit}
          className="bg-white rounded-3xl p-6 border border-gray-200 shadow-xs space-y-4"
        >
          <div>
            <label className="block text-xs font-bold text-[#0D2238] mb-1.5">البنك المحول منه</label>
            <select
              value={bankName}
              onChange={(e) => setBankName(e.target.value)}
              className="w-full px-4 py-3 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm font-bold focus:outline-none focus:ring-2 focus:ring-[#0D2238]"
            >
              {bankOptions.map((b) => (
                <option key={b} value={b}>
                  {b}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
              رقم حساب المحول (اختياري)
            </label>
            <input
              type="text"
              value={senderAccount}
              onChange={(e) => setSenderAccount(e.target.value)}
              placeholder="مثال: 1234567"
              className="w-full px-4 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
              رقم العملية / مرجع الإشعار البنكي
            </label>
            <input
              type="text"
              required
              value={bankRef}
              onChange={(e) => setBankRef(e.target.value)}
              placeholder="مثال: BNK-987654321"
              className="w-full px-4 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
              المبلغ المحول (SDG)
            </label>
            <input
              type="number"
              min="1"
              required
              value={bankAmount}
              onChange={(e) => setBankAmount(e.target.value)}
              placeholder="0.00"
              className="w-full px-4 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-base font-mono font-bold focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
              ملاحظات إضافية
            </label>
            <input
              type="text"
              value={bankNote}
              onChange={(e) => setBankNote(e.target.value)}
              placeholder="مثال: تحويل تطبيق بنكك الساعة 2:30 م"
              className="w-full px-4 py-2 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0D2238]"
            />
          </div>

          {bankError && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2 text-xs font-bold text-rose-700">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{bankError}</span>
            </div>
          )}

          <button
            type="submit"
            disabled={bankLoading}
            className="w-full py-3.5 bg-[#0D2238] hover:bg-[#1E3A5F] text-white font-black rounded-xl transition-all shadow-md active:scale-98 flex items-center justify-center gap-2 text-sm disabled:opacity-50"
          >
            {bankLoading ? (
              <span className="animate-spin">⏳</span>
            ) : (
              <>
                <Landmark className="w-4 h-4" />
                <span>إرسال إشعار الإيداع للمراجعة</span>
              </>
            )}
          </button>
        </form>
      )}

      {/* Previous Deposit Requests History */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200">
        <div className="flex items-center gap-2 mb-3">
          <History className="w-4 h-4 text-gray-500" />
          <h3 className="font-black text-sm text-[#0D2238]">طلبات الإيداع البنكية السابقة</h3>
        </div>

        {userDeposits.length === 0 ? (
          <p className="text-xs text-gray-400 text-center py-4">لا توجد طلبات إيداع سابقة.</p>
        ) : (
          <div className="space-y-3">
            {userDeposits.map((dep) => (
              <div
                key={dep.id}
                className="p-3.5 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-between text-xs"
              >
                <div>
                  <div className="font-bold text-[#0D2238]">{dep.bankName}</div>
                  <div className="text-gray-400 font-mono text-[11px] mt-0.5">
                    المرجع: {dep.referenceNumber}
                  </div>
                  <div className="text-gray-400 text-[10px]">
                    {new Date(dep.createdAt).toLocaleDateString('ar-SD')}
                  </div>
                </div>

                <div className="text-left">
                  <div className="font-mono font-black text-sm text-emerald-600">
                    +{dep.amount.toLocaleString()} SDG
                  </div>
                  <div className="mt-1 flex justify-end">
                    <StatusBadge status={dep.status} />
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Instant Deposit Receipt */}
      {instantReceipt && (
        <SuccessReceiptModal
          isOpen={true}
          onClose={() => setInstantReceipt(null)}
          title="تم الإيداع الفوري بنجاح!"
          reference={instantReceipt.reference}
          amount={instantReceipt.amount}
          details="تمت إضافة الرصيد إلى محفظتك مباشرة وجاهز للاستخدام الفوري."
        />
      )}

      {/* Bank Deposit Notice Receipt */}
      {bankReceipt && (
        <SuccessReceiptModal
          isOpen={true}
          onClose={() => setBankReceipt(null)}
          title="تم استلام إشعار التحويل!"
          reference={bankReceipt.reference}
          amount={bankReceipt.amount}
          details="طلبك قيد المراجعة لدى الإدارة. سيتم مطابقة الإشعار وإيداع المبلغ في محفظتك فورياً."
        />
      )}
    </div>
  );
};
