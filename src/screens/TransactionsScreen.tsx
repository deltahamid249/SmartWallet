import React, { useState, useMemo } from 'react';
import { useWallet } from '../context/WalletContext';
import { Transaction } from '../types';
import { StatusBadge } from '../components/StatusBadge';
import {
  ArrowLeft,
  Search,
  Filter,
  ArrowDownLeft,
  ArrowUpRight,
  Receipt,
  Calendar,
  X,
  Copy,
  Check,
} from 'lucide-react';

interface TransactionsScreenProps {
  onBack: () => void;
}

export const TransactionsScreen: React.FC<TransactionsScreenProps> = ({ onBack }) => {
  const { transactions } = useWallet();

  const [selectedFilter, setSelectedFilter] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedTx, setSelectedTx] = useState<Transaction | null>(null);
  const [copied, setCopied] = useState(false);

  const filterOptions = [
    { id: 'all', label: 'الكل' },
    { id: 'deposit', label: 'إيداع' },
    { id: 'withdraw', label: 'سحب' },
    { id: 'transfer_out', label: 'تحويل صادر' },
    { id: 'transfer_in', label: 'تحويل وارد' },
    { id: 'payment', label: 'مدفوعات' },
    { id: 'service', label: 'خدمات وفواتير' },
  ];

  const filteredTransactions = useMemo(() => {
    return transactions.filter((tx) => {
      // Type match
      let matchType = true;
      if (selectedFilter === 'service') {
        matchType = [
          'recharge',
          'electricity',
          'internet',
          'bills',
          'education',
          'government',
        ].includes(tx.type);
      } else if (selectedFilter !== 'all') {
        matchType = tx.type === selectedFilter;
      }

      // Query match
      let matchQuery = true;
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        matchQuery =
          tx.description.toLowerCase().includes(q) ||
          tx.reference.toLowerCase().includes(q) ||
          tx.amount.toString().includes(q);
      }

      return matchType && matchQuery;
    });
  }, [transactions, selectedFilter, searchQuery]);

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

  const copyReference = (ref: string) => {
    navigator.clipboard.writeText(ref);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="space-y-5 pb-12">
      {/* Header */}
      <div className="flex items-center justify-between">
        <button
          onClick={onBack}
          id="back_button"
          className="flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-[#0D2238] bg-white px-3 py-2 rounded-xl border border-gray-200 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 rotate-180" />
          <span>رجوع</span>
        </button>
        <h2 className="text-base font-black text-[#0D2238]">سجل المعاملات والعمليات</h2>
        <div className="w-16"></div>
      </div>

      {/* Search Input */}
      <div className="relative">
        <input
          type="text"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          placeholder="بحث بالوصف، الرقم المرجعي، أو المبلغ..."
          className="w-full pr-10 pl-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-[#0D2238]"
        />
        <Search className="w-4 h-4 text-gray-400 absolute right-3.5 top-3" />
        {searchQuery && (
          <button
            onClick={() => setSearchQuery('')}
            className="absolute left-3.5 top-3 text-gray-400 hover:text-gray-600"
          >
            <X className="w-4 h-4" />
          </button>
        )}
      </div>

      {/* Filter Chips Horizontal Bar */}
      <div className="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
        {filterOptions.map((opt) => (
          <button
            key={opt.id}
            onClick={() => setSelectedFilter(opt.id)}
            className={`px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all ${
              selectedFilter === opt.id
                ? 'bg-[#0D2238] text-white shadow-xs'
                : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'
            }`}
          >
            {opt.label}
          </button>
        ))}
      </div>

      {/* Transactions List */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200 shadow-xs">
        {filteredTransactions.length === 0 ? (
          <div className="text-center py-12 text-gray-400 text-xs">
            لا توجد معاملات تطابق هذا الفلتر أو كلمة البحث.
          </div>
        ) : (
          <div className="space-y-3">
            {filteredTransactions.map((tx) => {
              const isPositive = ['deposit', 'transfer_in'].includes(tx.type);
              const { icon, bg } = getTxIcon(tx.type);
              return (
                <div
                  key={tx.id}
                  onClick={() => setSelectedTx(tx)}
                  className="p-3.5 rounded-2xl hover:bg-[#F4F6F8] transition-colors border border-gray-100 flex items-center justify-between cursor-pointer group"
                >
                  <div className="flex items-center gap-3">
                    <div
                      className={`w-11 h-11 rounded-xl flex items-center justify-center text-lg shrink-0 ${bg}`}
                    >
                      {icon}
                    </div>
                    <div>
                      <h4 className="text-xs sm:text-sm font-bold text-[#0D2238] group-hover:text-blue-900 transition-colors line-clamp-1">
                        {tx.description}
                      </h4>
                      <div className="text-[11px] text-gray-400 font-mono mt-0.5">
                        {new Date(tx.createdAt).toLocaleDateString('ar-SD')} •{' '}
                        {new Date(tx.createdAt).toLocaleTimeString('ar-SD', {
                          hour: '2-digit',
                          minute: '2-digit',
                        })}{' '}
                        • {tx.reference}
                      </div>
                    </div>
                  </div>

                  <div className="text-left shrink-0">
                    <div
                      className={`font-black font-mono text-xs sm:text-sm ${
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

      {/* Transaction Detail Modal */}
      {selectedTx && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border border-gray-100 p-6 text-right">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <h3 className="text-base font-black text-[#0D2238]">تفاصيل المعاملة</h3>
              <button
                onClick={() => setSelectedTx(null)}
                className="p-1 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="my-5 text-center">
              <div
                className={`text-3xl font-black font-mono tracking-tight ${
                  ['deposit', 'transfer_in'].includes(selectedTx.type)
                    ? 'text-emerald-600'
                    : 'text-[#0D2238]'
                }`}
              >
                {['deposit', 'transfer_in'].includes(selectedTx.type) ? '+' : '-'}
                {selectedTx.amount.toLocaleString('en-US', { minimumFractionDigits: 2 })}
                <span className="text-sm font-bold text-gray-500 mr-1 font-sans">SDG</span>
              </div>
              <div className="mt-1">
                <StatusBadge status={selectedTx.status} />
              </div>
            </div>

            <div className="bg-[#F4F6F8] rounded-2xl p-4 space-y-2.5 text-xs border border-gray-200">
              <div className="flex justify-between items-center">
                <span className="text-gray-500">الرقم المرجعي:</span>
                <div className="flex items-center gap-1">
                  <span className="font-mono font-bold text-[#0D2238]">{selectedTx.reference}</span>
                  <button
                    onClick={() => copyReference(selectedTx.reference)}
                    className="text-gray-400 hover:text-gray-700 p-0.5"
                  >
                    {copied ? (
                      <Check className="w-3.5 h-3.5 text-emerald-600" />
                    ) : (
                      <Copy className="w-3.5 h-3.5" />
                    )}
                  </button>
                </div>
              </div>

              <div className="flex justify-between">
                <span className="text-gray-500">الوصف:</span>
                <span className="font-bold text-[#0D2238] max-w-[220px] text-left">
                  {selectedTx.description}
                </span>
              </div>

              <div className="flex justify-between">
                <span className="text-gray-500">نوع العملية:</span>
                <span className="font-bold text-[#0D2238]">{selectedTx.type}</span>
              </div>

              <div className="flex justify-between">
                <span className="text-gray-500">تاريخ ووقت التنفيذ:</span>
                <span className="font-bold text-[#0D2238]">
                  {new Date(selectedTx.createdAt).toLocaleString('ar-SD')}
                </span>
              </div>

              {selectedTx.balanceAfter !== undefined && (
                <div className="flex justify-between pt-2 border-t border-gray-200">
                  <span className="text-gray-500">الرصيد بعد العملية:</span>
                  <span className="font-mono font-bold text-emerald-700">
                    {selectedTx.balanceAfter.toLocaleString()} SDG
                  </span>
                </div>
              )}
            </div>

            <button
              onClick={() => setSelectedTx(null)}
              className="mt-5 w-full py-3 bg-[#0D2238] hover:bg-[#1E3A5F] text-white font-bold rounded-xl text-sm transition-all"
            >
              إغلاق
            </button>
          </div>
        </div>
      )}
    </div>
  );
};
