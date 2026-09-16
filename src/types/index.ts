export interface User {
  id: number;
  fullName: string;
  phone: string;
  email: string | null;
  role: 'user' | 'admin';
  pin: string;
  status: string;
  createdAt: number;
}

export interface Wallet {
  id: number;
  userId: number;
  balance: number;
  currency: string;
  status: string;
  updatedAt: number;
}

export interface Transaction {
  id: number;
  walletId: number;
  type: string;
  amount: number;
  balanceAfter: number;
  reference: string;
  description: string;
  status: string;
  createdAt: number;
}

export interface Transfer {
  id: number;
  senderWalletId: number;
  receiverWalletId: number;
  senderUserId: number;
  receiverPhone: string;
  amount: number;
  reference: string;
  note: string | null;
  status: string;
  createdAt: number;
}

export interface DepositRequest {
  id: number;
  userId: number;
  walletId: number;
  amount: number;
  bankName: string;
  senderAccount: string;
  referenceNumber: string;
  note: string | null;
  status: 'pending' | 'approved' | 'rejected';
  createdAt: number;
  reviewedAt: number | null;
}

export interface WithdrawalRequest {
  id: number;
  userId: number;
  walletId: number;
  amount: number;
  withdrawalMethod: string;
  recipientName: string;
  recipientPhone: string;
  note: string | null;
  status: 'pending' | 'approved' | 'rejected';
  createdAt: number;
  reviewedAt: number | null;
}

export interface Payment {
  id: number;
  walletId: number;
  merchantName: string;
  merchantAccount: string;
  amount: number;
  reference: string;
  createdAt: number;
}

export interface ServiceRequest {
  id: number;
  userId: number;
  walletId: number;
  serviceType: string;
  provider: string;
  accountOrMeter: string;
  amount: number;
  tokenOrReceipt: string;
  status: string;
  createdAt: number;
}

export interface Notification {
  id: number;
  userId: number;
  title: string;
  message: string;
  type: 'info' | 'success' | 'warning' | 'error';
  isRead: boolean;
  createdAt: number;
}

export interface AuditLog {
  id: number;
  adminUserId: number;
  action: string;
  targetEntity: string;
  targetId: number;
  details: string;
  createdAt: number;
}

export type ScreenType =
  | 'home'
  | 'transfer'
  | 'deposit'
  | 'withdraw'
  | 'payments'
  | 'services'
  | 'transactions'
  | 'notifications'
  | 'profile'
  | 'admin'
  | 'settings'
  | 'help';
