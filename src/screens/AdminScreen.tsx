import React, { useState } from 'react';
import { useWallet } from '../context/WalletContext';
import { ScreenType } from '../types';
import { StatusBadge } from '../components/StatusBadge';
import {
  ArrowLeft,
  Shield,
  Users,
  CreditCard,
  ArrowDownLeft,
  ArrowUpRight,
  CheckCircle,
  XCircle,
  FileText,
  AlertCircle,
} from 'lucide-react';

interface AdminScreenProps {
  onBack: () => void;
  setScreen: (screen: ScreenType) => void;
}

export const AdminScreen: React.FC<AdminScreenProps> = ({ onBack, setScreen }) => {
  const {
    currentUser,
    allUsers,
    allWallets,
    allDeposits,
    allWithdrawals,
    allAuditLogs,
    approveDeposit,
    rejectDeposit,
    approveWithdrawal,
    rejectWithdrawal,
  } = useWallet();

  const [activeTab, setActiveTab] = useState<'deposits' | 'withdrawals' | 'users' | 'audit'>('deposits');
  const [actionLoading, setActionLoading] = useState<number | null>(null);

  // If user is not admin, show warning
  if (currentUser?.role !== 'admin') {
    return (
      <div className="bg-white rounded-3xl p-8 border border-gray-200 text-center space-y-4">
        <div className="w-16 h-16 mx-auto bg-rose-100 text-rose-600 rounded-full flex items-center justify-center">
          <Shield className="w-8 h-8" />
        </div>
        <h3 className="text-lg font-black text-[#0D2238]">صلاحية المشرف مطلوبة</h3>
        <p className="text-xs text-gray-500 max-w-sm mx-auto">
          أنت مسجل حالياً بحساب مستخدم عادي. يمكنك التبديل إلى حساب المشرف (Admin) من القائمة العلوية لتجربة لوحة التحكم.
        </p>
        <button
          onClick={onBack}
          className="py-2.5 px-6 bg-[#0D2238] text-white font-bold rounded-xl text-xs"
        >
          العودة للرئيسية
        </button>
      </div>
    );
  }

  // System stats
  const totalSystemBalance = allWallets.reduce((acc, w) => acc + w.balance, 0);
  const pendingDeposits = allDeposits.filter((d) => d.status === 'pending');
  const pendingWithdrawals = allWithdrawals.filter((w) => w.status === 'pending');

  const handleApproveDeposit = async (id: number) => {
    setActionLoading(id);
    await approveDeposit(id);
    setActionLoading(null);
  };

  const handleRejectDeposit = async (id: number) => {
    const reason = window.prompt('سبب الرفض (اختياري):', 'عدم تطابق بيانات الإشعار البنكي');
    setActionLoading(id);
    await rejectDeposit(id, reason || undefined);
    setActionLoading(null);
  };

  const handleApproveWithdrawal = async (id: number) => {
    setActionLoading(id);
    await approveWithdrawal(id);
    setActionLoading(null);
  };

  const handleRejectWithdrawal = async (id: number) => {
    const reason = window.prompt('سبب رفض طلب السحب (سيتم إرجاع المبلغ لمحفظة المستخدم):', 'بيانات الاستلام غير مكتملة');
    setActionLoading(id);
    await rejectWithdrawal(id, reason || undefined);
    setActionLoading(null);
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
        <div className="flex items-center gap-2">
          <Shield className="w-5 h-5 text-amber-600" />
          <h2 className="text-base font-black text-[#0D2238]">لوحة المشرف وإدارة النظام</h2>
        </div>
        <div className="w-16"></div>
      </div>

      {/* KPI Metrics */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
          <span className="text-[11px] text-gray-500 font-bold block">إجمالي رصيد النظام</span>
          <div className="text-lg font-mono font-black text-emerald-600 mt-1">
            {totalSystemBalance.toLocaleString()}{' '}
            <span className="text-[10px] font-sans font-normal text-gray-500">SDG</span>
          </div>
        </div>

        <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
          <span className="text-[11px] text-gray-500 font-bold block">عدد المستخدمين</span>
          <div className="text-lg font-mono font-black text-[#0D2238] mt-1">
            {allUsers.length} <span className="text-[10px] font-sans font-normal text-gray-500">مستخدم</span>
          </div>
        </div>

        <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
          <span className="text-[11px] text-gray-500 font-bold block">إيداعات معلقة</span>
          <div className="text-lg font-mono font-black text-blue-600 mt-1">
            {pendingDeposits.length} <span className="text-[10px] font-sans font-normal text-gray-500">طلب</span>
          </div>
        </div>

        <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
          <span className="text-[11px] text-gray-500 font-bold block">سحوبات معلقة</span>
          <div className="text-lg font-mono font-black text-amber-600 mt-1">
            {pendingWithdrawals.length} <span className="text-[10px] font-sans font-normal text-gray-500">طلب</span>
          </div>
        </div>
      </div>

      {/* Navigation Tabs */}
      <div className="flex p-1 bg-gray-200/80 rounded-2xl text-xs font-bold gap-1 overflow-x-auto">
        <button
          onClick={() => setActiveTab('deposits')}
          className={`flex-1 py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 whitespace-nowrap transition-all ${
            activeTab === 'deposits'
              ? 'bg-[#0D2238] text-white shadow-xs'
              : 'text-gray-600 hover:text-[#0D2238]'
          }`}
        >
          <ArrowDownLeft className="w-3.5 h-3.5" />
          <span>طلبات الإيداع ({pendingDeposits.length})</span>
        </button>

        <button
          onClick={() => setActiveTab('withdrawals')}
          className={`flex-1 py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 whitespace-nowrap transition-all ${
            activeTab === 'withdrawals'
              ? 'bg-[#0D2238] text-white shadow-xs'
              : 'text-gray-600 hover:text-[#0D2238]'
          }`}
        >
          <ArrowUpRight className="w-3.5 h-3.5" />
          <span>طلبات السحب ({pendingWithdrawals.length})</span>
        </button>

        <button
          onClick={() => setActiveTab('users')}
          className={`flex-1 py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 whitespace-nowrap transition-all ${
            activeTab === 'users'
              ? 'bg-[#0D2238] text-white shadow-xs'
              : 'text-gray-600 hover:text-[#0D2238]'
          }`}
        >
          <Users className="w-3.5 h-3.5" />
          <span>المستخدمين ({allUsers.length})</span>
        </button>

        <button
          onClick={() => setActiveTab('audit')}
          className={`flex-1 py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 whitespace-nowrap transition-all ${
            activeTab === 'audit'
              ? 'bg-[#0D2238] text-white shadow-xs'
              : 'text-gray-600 hover:text-[#0D2238]'
          }`}
        >
          <FileText className="w-3.5 h-3.5" />
          <span>سجل الرقابة ({allAuditLogs.length})</span>
        </button>
      </div>

      {/* Tab 1: Deposits Management */}
      {activeTab === 'deposits' && (
        <div className="bg-white rounded-3xl p-5 border border-gray-200 space-y-3">
          <h3 className="font-black text-sm text-[#0D2238]">جميع طلبات الإيداع البنكية</h3>
          {allDeposits.length === 0 ? (
            <p className="text-xs text-gray-400 text-center py-6">لا توجد طلبات إيداع.</p>
          ) : (
            <div className="space-y-3">
              {allDeposits.map((dep) => {
                const user = allUsers.find((u) => u.id === dep.userId);
                const isPending = dep.status === 'pending';
                return (
                  <div
                    key={dep.id}
                    className="p-4 rounded-2xl bg-gray-50 border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                  >
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-sm text-[#0D2238]">{user?.fullName}</span>
                        <span className="text-gray-400 font-mono">({user?.phone})</span>
                        <StatusBadge status={dep.status} />
                      </div>
                      <div className="text-gray-600 mt-1">
                        البنك: <strong className="text-gray-800">{dep.bankName}</strong> | المرجع:{' '}
                        <code className="font-bold text-[#0D2238] bg-white px-1.5 py-0.5 rounded border border-gray-200 font-mono">
                          {dep.referenceNumber}
                        </code>
                      </div>
                      {dep.note && <div className="text-gray-500 mt-1 text-[11px]">ملاحظة: {dep.note}</div>}
                    </div>

                    <div className="flex items-center justify-between sm:justify-end gap-3 border-t sm:border-t-0 pt-2 sm:pt-0">
                      <div className="font-mono font-black text-base text-emerald-600">
                        +{dep.amount.toLocaleString()} SDG
                      </div>

                      {isPending && (
                        <div className="flex items-center gap-1.5">
                          <button
                            onClick={() => handleApproveDeposit(dep.id)}
                            disabled={actionLoading === dep.id}
                            className="flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all shadow-xs"
                          >
                            <CheckCircle className="w-3.5 h-3.5" />
                            <span>قبول وإيداع</span>
                          </button>
                          <button
                            onClick={() => handleRejectDeposit(dep.id)}
                            disabled={actionLoading === dep.id}
                            className="flex items-center gap-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-xl text-xs border border-rose-200 transition-all"
                          >
                            <XCircle className="w-3.5 h-3.5" />
                            <span>رفض</span>
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}

      {/* Tab 2: Withdrawals Management */}
      {activeTab === 'withdrawals' && (
        <div className="bg-white rounded-3xl p-5 border border-gray-200 space-y-3">
          <h3 className="font-black text-sm text-[#0D2238]">جميع طلبات السحب النقدي</h3>
          {allWithdrawals.length === 0 ? (
            <p className="text-xs text-gray-400 text-center py-6">لا توجد طلبات سحب.</p>
          ) : (
            <div className="space-y-3">
              {allWithdrawals.map((wth) => {
                const user = allUsers.find((u) => u.id === wth.userId);
                const isPending = wth.status === 'pending';
                return (
                  <div
                    key={wth.id}
                    className="p-4 rounded-2xl bg-gray-50 border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                  >
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-sm text-[#0D2238]">{user?.fullName}</span>
                        <StatusBadge status={wth.status} />
                      </div>
                      <div className="text-gray-600 mt-1">
                        المستلم: <strong>{wth.recipientName}</strong> ({wth.recipientPhone}) | الطريقة:{' '}
                        <span className="font-bold text-[#0D2238]">{wth.withdrawalMethod}</span>
                      </div>
                      {wth.note && <div className="text-gray-500 mt-1 text-[11px]">ملاحظة: {wth.note}</div>}
                    </div>

                    <div className="flex items-center justify-between sm:justify-end gap-3 border-t sm:border-t-0 pt-2 sm:pt-0">
                      <div className="font-mono font-black text-base text-[#0D2238]">
                        -{wth.amount.toLocaleString()} SDG
                      </div>

                      {isPending && (
                        <div className="flex items-center gap-1.5">
                          <button
                            onClick={() => handleApproveWithdrawal(wth.id)}
                            disabled={actionLoading === wth.id}
                            className="flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all shadow-xs"
                          >
                            <CheckCircle className="w-3.5 h-3.5" />
                            <span>تسليم واعتماد</span>
                          </button>
                          <button
                            onClick={() => handleRejectWithdrawal(wth.id)}
                            disabled={actionLoading === wth.id}
                            className="flex items-center gap-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-xl text-xs border border-rose-200 transition-all"
                            title="سيتم إرجاع المبلغ فوراً لمحفظة المستخدم"
                          >
                            <XCircle className="w-3.5 h-3.5" />
                            <span>رفض وإرجاع الرصيد</span>
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}

      {/* Tab 3: Users Management */}
      {activeTab === 'users' && (
        <div className="bg-white rounded-3xl p-5 border border-gray-200 space-y-3">
          <h3 className="font-black text-sm text-[#0D2238]">قائمة مستخدمي النظام</h3>
          <div className="space-y-3">
            {allUsers.map((u) => {
              const wallet = allWallets.find((w) => w.userId === u.id);
              return (
                <div
                  key={u.id}
                  className="p-4 rounded-2xl bg-gray-50 border border-gray-200 flex items-center justify-between text-xs"
                >
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="font-bold text-sm text-[#0D2238]">{u.fullName}</span>
                      <span className="text-[10px] bg-white px-2 py-0.5 rounded border border-gray-200 font-bold">
                        {u.role === 'admin' ? 'مشرف' : 'مستخدم'}
                      </span>
                    </div>
                    <div className="text-gray-500 font-mono mt-0.5">{u.phone}</div>
                  </div>

                  <div className="text-left font-mono font-black text-sm text-emerald-600">
                    {(wallet?.balance ?? 0).toLocaleString()} SDG
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Tab 4: Audit Logs */}
      {activeTab === 'audit' && (
        <div className="bg-white rounded-3xl p-5 border border-gray-200 space-y-3">
          <h3 className="font-black text-sm text-[#0D2238]">سجل المراجعة والرقابة (Audit Trail)</h3>
          {allAuditLogs.length === 0 ? (
            <p className="text-xs text-gray-400 text-center py-6">لا توجد سجلات تدقيق.</p>
          ) : (
            <div className="space-y-2">
              {allAuditLogs.map((log) => (
                <div
                  key={log.id}
                  className="p-3 rounded-xl bg-gray-50 border border-gray-100 text-xs flex items-center justify-between"
                >
                  <div>
                    <span className="font-bold text-[#0D2238] ml-2">{log.action}:</span>
                    <span className="text-gray-600">{log.details}</span>
                  </div>
                  <span className="text-[10px] text-gray-400 font-mono shrink-0">
                    {new Date(log.createdAt).toLocaleTimeString('ar-SD')}
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
};
