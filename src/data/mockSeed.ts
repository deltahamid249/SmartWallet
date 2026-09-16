import {
  User,
  Wallet,
  Transaction,
  Transfer,
  DepositRequest,
  WithdrawalRequest,
  Payment,
  ServiceRequest,
  Notification,
  AuditLog,
} from '../types';

const now = Date.now();
const hour = 3600 * 1000;
const day = 24 * hour;

export const INITIAL_USERS: User[] = [
  {
    id: 1,
    fullName: 'أحمد مصطفى سليمان',
    phone: '0912345678',
    email: 'ahmed@smartwallet.sd',
    role: 'user',
    pin: '1234',
    status: 'active',
    createdAt: now - 30 * day,
  },
  {
    id: 2,
    fullName: 'سارة عثمان علي',
    phone: '0998765432',
    email: 'sara@smartwallet.sd',
    role: 'user',
    pin: '1234',
    status: 'active',
    createdAt: now - 20 * day,
  },
  {
    id: 3,
    fullName: 'مدير النظام (Admin)',
    phone: '0123456789',
    email: 'admin@smartwallet.sd',
    role: 'admin',
    pin: '9999',
    status: 'active',
    createdAt: now - 60 * day,
  },
];

export const INITIAL_WALLETS: Wallet[] = [
  {
    id: 1,
    userId: 1,
    balance: 154500.0,
    currency: 'SDG',
    status: 'active',
    updatedAt: now,
  },
  {
    id: 2,
    userId: 2,
    balance: 32000.0,
    currency: 'SDG',
    status: 'active',
    updatedAt: now,
  },
  {
    id: 3,
    userId: 3,
    balance: 5000000.0,
    currency: 'SDG',
    status: 'active',
    updatedAt: now,
  },
];

export const INITIAL_TRANSACTIONS: Transaction[] = [
  {
    id: 1,
    walletId: 1,
    type: 'deposit',
    amount: 200000.0,
    balanceAfter: 200000.0,
    reference: 'DEP-20250101-001',
    description: 'إيداع نقدي عبر بنك الخرطوم (بنكك)',
    status: 'completed',
    createdAt: now - 5 * day,
  },
  {
    id: 2,
    walletId: 1,
    type: 'recharge',
    amount: 5500.0,
    balanceAfter: 194500.0,
    reference: 'SRV-20250102-002',
    description: 'شحن رصيد زين السودان (0912345678)',
    status: 'completed',
    createdAt: now - 3 * day,
  },
  {
    id: 3,
    walletId: 1,
    type: 'transfer_out',
    amount: 40000.0,
    balanceAfter: 154500.0,
    reference: 'TRF-20250103-003',
    description: 'تحويل مالي إلى سارة عثمان علي (0998765432)',
    status: 'completed',
    createdAt: now - 1 * day,
  },
  {
    id: 4,
    walletId: 2,
    type: 'transfer_in',
    amount: 40000.0,
    balanceAfter: 40000.0,
    reference: 'TRF-20250103-003',
    description: 'تحويل مالي وارد من أحمد مصطفى سليمان',
    status: 'completed',
    createdAt: now - 1 * day,
  },
  {
    id: 5,
    walletId: 2,
    type: 'payment',
    amount: 8000.0,
    balanceAfter: 32000.0,
    reference: 'PAY-20250104-004',
    description: 'دفع مشتريات سوبرماركت الواحة (MRC-10023)',
    status: 'completed',
    createdAt: now - 8 * hour,
  },
];

export const INITIAL_TRANSFERS: Transfer[] = [
  {
    id: 1,
    senderWalletId: 1,
    receiverWalletId: 2,
    senderUserId: 1,
    receiverPhone: '0998765432',
    amount: 40000.0,
    reference: 'TRF-20250103-003',
    note: 'مصاريف تسوق',
    status: 'completed',
    createdAt: now - 1 * day,
  },
];

export const INITIAL_DEPOSITS: DepositRequest[] = [
  {
    id: 1,
    userId: 1,
    walletId: 1,
    amount: 50000.0,
    bankName: 'بنك الخرطوم (بنكك)',
    senderAccount: '1234567',
    referenceNumber: 'BNK-987654321',
    note: 'شحن رصيد شهري',
    status: 'pending',
    createdAt: now - 4 * hour,
    reviewedAt: null,
  },
];

export const INITIAL_WITHDRAWALS: WithdrawalRequest[] = [
  {
    id: 1,
    userId: 1,
    walletId: 1,
    amount: 10000.0,
    withdrawalMethod: 'وكيل معتمد (Cash Agent)',
    recipientName: 'أحمد مصطفى سليمان',
    recipientPhone: '0912345678',
    note: 'فرع السوق العربي',
    status: 'pending',
    createdAt: now - 2 * hour,
    reviewedAt: null,
  },
];

export const INITIAL_PAYMENTS: Payment[] = [
  {
    id: 1,
    walletId: 2,
    merchantName: 'سوبرماركت الواحة',
    merchantAccount: 'MRC-10023',
    amount: 8000.0,
    reference: 'PAY-20250104-004',
    createdAt: now - 8 * hour,
  },
];

export const INITIAL_SERVICES: ServiceRequest[] = [
  {
    id: 1,
    userId: 1,
    walletId: 1,
    serviceType: 'recharge',
    provider: 'زين (Zain)',
    accountOrMeter: '0912345678',
    amount: 5500.0,
    tokenOrReceipt: 'RCG-998811',
    status: 'completed',
    createdAt: now - 3 * day,
  },
];

export const INITIAL_NOTIFICATIONS: Notification[] = [
  {
    id: 1,
    userId: 1,
    title: 'مرحباً بك في المحفظة الذكية!',
    message: 'تم تفعيل حساب محفظتك بنجاح برقم #1. يمكنك الآن إجراء التحويلات والمدفوعات.',
    type: 'success',
    isRead: false,
    createdAt: now - 30 * day,
  },
  {
    id: 2,
    userId: 1,
    title: 'طلب إيداع بنكي قيد المراجعة',
    message: 'تم استلام إشعار التحويل البنكي بمبلغ 50,000 SDG وهو بانتظار موافقة الإدارة.',
    type: 'info',
    isRead: false,
    createdAt: now - 4 * hour,
  },
  {
    id: 3,
    userId: 2,
    title: 'تحويل مالي وارد',
    message: 'وصلك تحويل مالي بقيمة 40,000 SDG من أحمد مصطفى سليمان.',
    type: 'success',
    isRead: true,
    createdAt: now - 1 * day,
  },
];

export const INITIAL_AUDIT_LOGS: AuditLog[] = [
  {
    id: 1,
    adminUserId: 3,
    action: 'SYSTEM_INITIALIZATION',
    targetEntity: 'SYSTEM',
    targetId: 0,
    details: 'تهيئة وتجهيز قواعد بيانات المحفظة الذكية الأولية',
    createdAt: now - 30 * day,
  },
];
