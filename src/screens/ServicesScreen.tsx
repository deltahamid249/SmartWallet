import React, { useState } from 'react';
import { useWallet } from '../context/WalletContext';
import { ScreenType } from '../types';
import { SuccessReceiptModal } from '../components/SuccessReceiptModal';
import {
  ArrowLeft,
  Smartphone,
  Zap,
  Globe,
  Droplets,
  GraduationCap,
  Building2,
  AlertCircle,
  History,
  X,
} from 'lucide-react';

interface ServicesScreenProps {
  onBack: () => void;
  setScreen: (screen: ScreenType) => void;
}

interface ServiceCategory {
  id: string;
  title: string;
  desc: string;
  icon: any;
  color: string;
  providers: string[];
  placeholder: string;
  label: string;
  presets: number[];
}

export const ServicesScreen: React.FC<ServicesScreenProps> = ({ onBack, setScreen }) => {
  const { currentWallet, currentUser, payService, transactions } = useWallet();

  const [activeService, setActiveService] = useState<ServiceCategory | null>(null);
  const [provider, setProvider] = useState('');
  const [accountNumber, setAccountNumber] = useState('');
  const [amount, setAmount] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [receipt, setReceipt] = useState<{
    reference: string;
    amount: number;
    token: string;
    title: string;
    details: string;
  } | null>(null);

  const categories: ServiceCategory[] = [
    {
      id: 'recharge',
      title: 'شحن رصيد اتصالات',
      desc: 'زين، سوداني، إم تي إن (دفع مسبق وآجل)',
      icon: Smartphone,
      color: 'bg-purple-100 text-purple-700',
      providers: ['زين السودان (Zain)', 'سوداني (Sudani)', 'إم تي إن (MTN)'],
      placeholder: 'رقم الهاتف (09xxxxxxxx أو 01xxxxxxxx)',
      label: 'رقم الهاتف المحمول',
      presets: [1000, 2500, 5000, 10000],
    },
    {
      id: 'electricity',
      title: 'شراء كهرباء',
      desc: 'الشركة السودانية لتوزيع الكهرباء (كود العداد)',
      icon: Zap,
      color: 'bg-amber-100 text-amber-700',
      providers: ['الشركة القومية للكهرباء (سدك)'],
      placeholder: 'أدخل رقم العداد المكون من 11 رقماً',
      label: 'رقم العداد (Meter Number)',
      presets: [2000, 5000, 10000, 20000],
    },
    {
      id: 'internet',
      title: 'فواتير إنترنت وباقات',
      desc: 'سوداتل، كنار، فايبر، راوترات ماي فاي',
      icon: Globe,
      color: 'bg-blue-100 text-blue-700',
      providers: ['سوداني فايبر وإنترنت', 'كنار تيليكوم', 'زين برودباند المنزلي'],
      placeholder: 'رقم خط الإنترنت أو كود الاشتراك',
      label: 'رقم الحساب / كود الخدمة',
      presets: [5000, 10000, 25000, 45000],
    },
    {
      id: 'bills',
      title: 'مياه وخدمات بلدية',
      desc: 'هيئة مياه الخرطوم والولايات ورسوم النظافة',
      icon: Droplets,
      color: 'bg-cyan-100 text-cyan-700',
      providers: ['هيئة مياه ولاية الخرطوم', 'هيئة مياه ولاية الجزيرة', 'رسوم خدمات بلدية'],
      placeholder: 'رقم اشتراك المياه أو العداد',
      label: 'رقم الحساب أو الاشتراك',
      presets: [2000, 4000, 8000, 15000],
    },
    {
      id: 'education',
      title: 'جامعات ورسوم تعليم',
      desc: 'سداد رسوم الجامعات والشهادة السودانية',
      icon: GraduationCap,
      color: 'bg-emerald-100 text-emerald-700',
      providers: [
        'جامعة الخرطوم',
        'جامعة السودان للعلوم والتكنولوجيا',
        'جامعة النيلين',
        'رسوم استمارة الشهادة الثانوية',
      ],
      placeholder: 'الرقم الجامعي أو رقم الجلوس',
      label: 'رقم القيد أو الاستمارة',
      presets: [15000, 30000, 50000, 100000],
    },
    {
      id: 'government',
      title: 'خدمات حكومية (إي-15)',
      desc: 'سداد إيصال 15 الإلكتروني والجوازات والمرور',
      icon: Building2,
      color: 'bg-rose-100 text-rose-700',
      providers: ['إيصال 15 الإلكتروني (E-15)', 'رسوم ترخيص المرور', 'رسوم تجديد الجوازات'],
      placeholder: 'رقم المطالبة (E-15 Invoice No)',
      label: 'رقم الفاتورة الإلكترونية',
      presets: [10000, 25000, 50000, 120000],
    },
  ];

  const serviceTransactions = transactions.filter((t) =>
    ['recharge', 'electricity', 'internet', 'bills', 'education', 'government'].includes(t.type)
  );

  const openServiceModal = (cat: ServiceCategory) => {
    setActiveService(cat);
    setProvider(cat.providers[0]);
    setAccountNumber(cat.id === 'recharge' ? currentUser?.phone || '' : '');
    setAmount(cat.presets[0].toString());
    setError(null);
  };

  const handlePayService = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!activeService) return;
    setError(null);

    const numAmount = parseFloat(amount);
    if (!accountNumber.trim()) {
      setError(`يرجى إدخال ${activeService.label}.`);
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
    const res = await payService(activeService.id, provider, accountNumber, numAmount);
    setLoading(false);

    if (res.success) {
      setReceipt({
        reference: res.reference,
        amount: numAmount,
        token: res.tokenOrReceipt,
        title: `تم سداد ${activeService.title} بنجاح!`,
        details:
          activeService.id === 'electricity'
            ? `كود شحن الكهرباء (Token): ${res.tokenOrReceipt}`
            : `تم السداد لحساب ${accountNumber} عبر ${provider}.`,
      });
      setActiveService(null);
    } else {
      setError(res.error || 'فشلت عملية السداد.');
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
        <h2 className="text-base font-black text-[#0D2238]">الخدمات والفواتير العامة</h2>
        <div className="w-16"></div>
      </div>

      {/* Balance Card */}
      <div className="bg-[#0D2238] text-white p-5 rounded-2xl flex items-center justify-between border border-[#1E3A5F]">
        <div>
          <span className="text-xs text-gray-400">الرصيد المتاح لدفع الخدمات</span>
          <div className="text-2xl font-mono font-black text-emerald-400 mt-0.5">
            {(currentWallet?.balance ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}{' '}
            <span className="text-sm font-sans font-bold text-gray-300">SDG</span>
          </div>
        </div>
        <div className="w-10 h-10 rounded-xl bg-cyan-500/20 border border-cyan-500/30 flex items-center justify-center text-xl">
          ⚡
        </div>
      </div>

      {/* Services Grid */}
      <div>
        <span className="text-xs font-bold text-gray-500 mb-3 block">اختر الخدمة المطلوبة:</span>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {categories.map((cat) => {
            const Icon = cat.icon;
            return (
              <button
                key={cat.id}
                onClick={() => openServiceModal(cat)}
                className="bg-white hover:bg-gray-50 p-4 rounded-2xl border border-gray-200 text-right transition-all flex items-center gap-3.5 shadow-xs group cursor-pointer"
              >
                <div
                  className={`w-12 h-12 rounded-xl flex items-center justify-center shrink-0 ${cat.color} group-hover:scale-105 transition-transform`}
                >
                  <Icon className="w-6 h-6" />
                </div>
                <div className="overflow-hidden">
                  <h3 className="font-black text-sm text-[#0D2238]">{cat.title}</h3>
                  <p className="text-xs text-gray-500 mt-0.5 truncate">{cat.desc}</p>
                </div>
              </button>
            );
          })}
        </div>
      </div>

      {/* Interactive Service Payment Dialog Modal */}
      {activeService && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden border border-gray-100 p-6 text-right">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <div className="flex items-center gap-2.5">
                <div
                  className={`w-9 h-9 rounded-xl flex items-center justify-center ${activeService.color}`}
                >
                  <activeService.icon className="w-5 h-5" />
                </div>
                <h3 className="text-base font-black text-[#0D2238]">{activeService.title}</h3>
              </div>
              <button
                onClick={() => setActiveService(null)}
                className="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handlePayService} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
                  مزود الخدمة / الشبكة
                </label>
                <select
                  value={provider}
                  onChange={(e) => setProvider(e.target.value)}
                  className="w-full px-3.5 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm font-bold focus:outline-none focus:ring-2 focus:ring-[#0D2238]"
                >
                  {activeService.providers.map((p) => (
                    <option key={p} value={p}>
                      {p}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
                  {activeService.label}
                </label>
                <input
                  type="text"
                  required
                  value={accountNumber}
                  onChange={(e) => setAccountNumber(e.target.value)}
                  placeholder={activeService.placeholder}
                  className="w-full px-3.5 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-[#0D2238] mb-1.5">
                  مبلغ السداد (SDG)
                </label>
                <input
                  type="number"
                  min="1"
                  required
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  placeholder="0.00"
                  className="w-full px-3.5 py-2.5 bg-[#F4F6F8] border border-gray-200 rounded-xl text-base font-mono font-bold focus:outline-none focus:ring-2 focus:ring-[#0D2238] text-right"
                />

                {/* Preset chips */}
                <div className="flex flex-wrap gap-1.5 mt-2">
                  {activeService.presets.map((preset) => (
                    <button
                      key={preset}
                      type="button"
                      onClick={() => setAmount(preset.toString())}
                      className="px-2.5 py-1 bg-gray-100 hover:bg-emerald-50 text-gray-700 text-xs font-bold rounded-lg border border-gray-200 transition-colors font-mono"
                    >
                      {preset.toLocaleString()} SDG
                    </button>
                  ))}
                </div>
              </div>

              {error && (
                <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2 text-xs font-bold text-rose-700">
                  <AlertCircle className="w-4 h-4 shrink-0" />
                  <span>{error}</span>
                </div>
              )}

              <div className="pt-2 flex gap-2">
                <button
                  type="submit"
                  disabled={loading}
                  className="flex-1 py-3 bg-[#0D2238] hover:bg-[#1E3A5F] text-white font-black rounded-xl text-sm transition-all shadow-md active:scale-98 flex items-center justify-center gap-2"
                >
                  {loading ? (
                    <span className="animate-spin">⏳</span>
                  ) : (
                    <span>تأكيد السداد والخصم</span>
                  )}
                </button>
                <button
                  type="button"
                  onClick={() => setActiveService(null)}
                  className="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm"
                >
                  إلغاء
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Recent Services Payments History */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200">
        <div className="flex items-center gap-2 mb-3">
          <History className="w-4 h-4 text-gray-500" />
          <h3 className="font-black text-sm text-[#0D2238]">سجل سداد الخدمات والفواتير</h3>
        </div>

        {serviceTransactions.length === 0 ? (
          <p className="text-xs text-gray-400 text-center py-4">لم تقم بسداد فواتير خدمات بعد.</p>
        ) : (
          <div className="space-y-3">
            {serviceTransactions.map((tx) => (
              <div
                key={tx.id}
                className="p-3.5 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-between text-xs"
              >
                <div>
                  <div className="font-bold text-[#0D2238]">{tx.description}</div>
                  <div className="text-gray-400 font-mono text-[11px] mt-0.5">
                    المرجع: {tx.reference} • {new Date(tx.createdAt).toLocaleDateString('ar-SD')}
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

      {/* Success Receipt Modal */}
      {receipt && (
        <SuccessReceiptModal
          isOpen={true}
          onClose={() => setReceipt(null)}
          title={receipt.title}
          reference={receipt.reference}
          amount={receipt.amount}
          details={receipt.details}
          extraInfo={[
            { label: 'كود الإشعار / التوكن', value: receipt.token },
            { label: 'الحالة', value: 'سداد ناجح' },
          ]}
        />
      )}
    </div>
  );
};
