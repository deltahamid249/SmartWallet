import React, { createContext, useContext, useState, useEffect, useMemo } from 'react';
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
import {
  INITIAL_USERS,
  INITIAL_WALLETS,
  INITIAL_TRANSACTIONS,
  INITIAL_TRANSFERS,
  INITIAL_DEPOSITS,
  INITIAL_WITHDRAWALS,
  INITIAL_PAYMENTS,
  INITIAL_SERVICES,
  INITIAL_NOTIFICATIONS,
  INITIAL_AUDIT_LOGS,
} from '../data/mockSeed';

interface WalletContextType {
  currentUser: User | null;
  currentWallet: Wallet | null;
  allUsers: User[];
  allWallets: Wallet[];
  transactions: Transaction[];
  allTransactions: Transaction[];
  notifications: Notification[];
  unreadCount: number;
  userDeposits: DepositRequest[];
  userWithdrawals: WithdrawalRequest[];
  allDeposits: DepositRequest[];
  allWithdrawals: WithdrawalRequest[];
  allAuditLogs: AuditLog[];
  switchUser: (userId: number) => void;
  registerNewUser: (
    fullName: string,
    phone: string,
    email: string | null
  ) => Promise<{ success: boolean; message: string }>;
  transfer: (
    receiverPhone: string,
    amount: number,
    note: string | null
  ) => Promise<{ success: boolean; reference: string; error?: string }>;
  submitInstantDeposit: (
    amount: number
  ) => Promise<{ success: boolean; reference: string; error?: string }>;
  submitBankDeposit: (
    amount: number,
    bankName: string,
    senderAccount: string,
    referenceNumber: string,
    note: string | null
  ) => Promise<{ success: boolean; reference: string; error?: string }>;
  submitWithdrawal: (
    amount: number,
    recipientName: string,
    recipientPhone: string,
    method: string,
    note: string | null
  ) => Promise<{ success: boolean; reference: string; error?: string }>;
  payMerchant: (
    merchantName: string,
    merchantAccount: string,
    amount: number
  ) => Promise<{ success: boolean; reference: string; error?: string }>;
  payService: (
    serviceType: string,
    provider: string,
    accountOrMeter: string,
    amount: number
  ) => Promise<{ success: boolean; reference: string; tokenOrReceipt: string; error?: string }>;
  approveDeposit: (depositId: number) => Promise<boolean>;
  rejectDeposit: (depositId: number, reason?: string) => Promise<boolean>;
  approveWithdrawal: (withdrawalId: number) => Promise<boolean>;
  rejectWithdrawal: (withdrawalId: number, reason?: string) => Promise<boolean>;
  toggleUserStatus: (userId: number) => Promise<boolean>;
  markNotificationAsRead: (id: number) => void;
  markAllNotificationsAsRead: () => void;
  resetToDefault: () => void;
}

const WalletContext = createContext<WalletContextType | undefined>(undefined);

function loadFromStorage<T>(key: string, fallback: T): T {
  try {
    const item = localStorage.getItem(`smartwallet_${key}`);
    return item ? JSON.parse(item) : fallback;
  } catch {
    return fallback;
  }
}

function saveToStorage<T>(key: string, data: T) {
  try {
    localStorage.setItem(`smartwallet_${key}`, JSON.stringify(data));
  } catch (e) {
    console.error('Failed to save to localStorage', e);
  }
}

export const WalletProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [users, setUsers] = useState<User[]>(() => loadFromStorage('users', INITIAL_USERS));
  const [wallets, setWallets] = useState<Wallet[]>(() => loadFromStorage('wallets', INITIAL_WALLETS));
  const [currentUserId, setCurrentUserId] = useState<number>(() => loadFromStorage('current_user_id', 1));
  const [transactions, setTransactions] = useState<Transaction[]>(() =>
    loadFromStorage('transactions', INITIAL_TRANSACTIONS)
  );
  const [transfers, setTransfers] = useState<Transfer[]>(() =>
    loadFromStorage('transfers', INITIAL_TRANSFERS)
  );
  const [deposits, setDeposits] = useState<DepositRequest[]>(() =>
    loadFromStorage('deposits', INITIAL_DEPOSITS)
  );
  const [withdrawals, setWithdrawals] = useState<WithdrawalRequest[]>(() =>
    loadFromStorage('withdrawals', INITIAL_WITHDRAWALS)
  );
  const [payments, setPayments] = useState<Payment[]>(() =>
    loadFromStorage('payments', INITIAL_PAYMENTS)
  );
  const [services, setServices] = useState<ServiceRequest[]>(() =>
    loadFromStorage('services', INITIAL_SERVICES)
  );
  const [notifications, setNotifications] = useState<Notification[]>(() =>
    loadFromStorage('notifications', INITIAL_NOTIFICATIONS)
  );
  const [auditLogs, setAuditLogs] = useState<AuditLog[]>(() =>
    loadFromStorage('audit_logs', INITIAL_AUDIT_LOGS)
  );

  // Auto-sync state to localStorage
  useEffect(() => {
    saveToStorage('users', users);
  }, [users]);
  useEffect(() => {
    saveToStorage('wallets', wallets);
  }, [wallets]);
  useEffect(() => {
    saveToStorage('current_user_id', currentUserId);
  }, [currentUserId]);
  useEffect(() => {
    saveToStorage('transactions', transactions);
  }, [transactions]);
  useEffect(() => {
    saveToStorage('transfers', transfers);
  }, [transfers]);
  useEffect(() => {
    saveToStorage('deposits', deposits);
  }, [deposits]);
  useEffect(() => {
    saveToStorage('withdrawals', withdrawals);
  }, [withdrawals]);
  useEffect(() => {
    saveToStorage('payments', payments);
  }, [payments]);
  useEffect(() => {
    saveToStorage('services', services);
  }, [services]);
  useEffect(() => {
    saveToStorage('notifications', notifications);
  }, [notifications]);
  useEffect(() => {
    saveToStorage('audit_logs', auditLogs);
  }, [auditLogs]);

  const currentUser = useMemo(
    () => users.find((u) => u.id === currentUserId) || users[0] || null,
    [users, currentUserId]
  );

  const currentWallet = useMemo(
    () => (currentUser ? wallets.find((w) => w.userId === currentUser.id) || null : null),
    [wallets, currentUser]
  );

  const userTransactions = useMemo(
    () =>
      currentWallet
        ? transactions
            .filter((t) => t.walletId === currentWallet.id)
            .sort((a, b) => b.createdAt - a.createdAt)
        : [],
    [transactions, currentWallet]
  );

  const userNotifications = useMemo(
    () =>
      currentUser
        ? notifications
            .filter((n) => n.userId === currentUser.id)
            .sort((a, b) => b.createdAt - a.createdAt)
        : [],
    [notifications, currentUser]
  );

  const unreadCount = useMemo(
    () => userNotifications.filter((n) => !n.isRead).length,
    [userNotifications]
  );

  const userDeposits = useMemo(
    () =>
      currentUser
        ? deposits
            .filter((d) => d.userId === currentUser.id)
            .sort((a, b) => b.createdAt - a.createdAt)
        : [],
    [deposits, currentUser]
  );

  const userWithdrawals = useMemo(
    () =>
      currentUser
        ? withdrawals
            .filter((w) => w.userId === currentUser.id)
            .sort((a, b) => b.createdAt - a.createdAt)
        : [],
    [withdrawals, currentUser]
  );

  const switchUser = (userId: number) => {
    const target = users.find((u) => u.id === userId);
    if (target) {
      setCurrentUserId(target.id);
    }
  };

  const registerNewUser = async (
    fullName: string,
    phone: string,
    email: string | null
  ): Promise<{ success: boolean; message: string }> => {
    if (!fullName.trim() || !phone.trim()) {
      return { success: false, message: 'يرجى تعبئة الاسم ورقم الهاتف كاملاً.' };
    }
    const cleanPhone = phone.trim();
    if (users.some((u) => u.phone === cleanPhone)) {
      return { success: false, message: 'رقم الهاتف مسجل مسبقاً في النظام.' };
    }

    const newUserId = users.length ? Math.max(...users.map((u) => u.id)) + 1 : 1;
    const newWalletId = wallets.length ? Math.max(...wallets.map((w) => w.id)) + 1 : 1;

    const newUser: User = {
      id: newUserId,
      fullName: fullName.trim(),
      phone: cleanPhone,
      email: email ? email.trim() : null,
      role: 'user',
      pin: '1234',
      status: 'active',
      createdAt: Date.now(),
    };

    const newWallet: Wallet = {
      id: newWalletId,
      userId: newUserId,
      balance: 10000.0, // Initial welcome bonus
      currency: 'SDG',
      status: 'active',
      updatedAt: Date.now(),
    };

    const welcomeNotif: Notification = {
      id: Date.now(),
      userId: newUserId,
      title: 'أهلاً بك في SmartWallet!',
      message: `تم إنشاء محفظتك بنجاح برقم #${newWalletId}. تم إيداع رصيد ترحيبي تجريبي بقيمة 10,000 SDG.`,
      type: 'success',
      isRead: false,
      createdAt: Date.now(),
    };

    const welcomeTx: Transaction = {
      id: Date.now(),
      walletId: newWalletId,
      type: 'deposit',
      amount: 10000.0,
      balanceAfter: 10000.0,
      reference: `BONUS-${Date.now().toString().slice(-6)}`,
      description: 'مكافأة افتتاح المحفظة الترحيبية',
      status: 'completed',
      createdAt: Date.now(),
    };

    setUsers((prev) => [...prev, newUser]);
    setWallets((prev) => [...prev, newWallet]);
    setNotifications((prev) => [welcomeNotif, ...prev]);
    setTransactions((prev) => [welcomeTx, ...prev]);
    setCurrentUserId(newUserId);

    return { success: true, message: 'تم إنشاء المحفظة وتسجيل الدخول بنجاح!' };
  };

  const transfer = async (
    receiverPhone: string,
    amount: number,
    note: string | null
  ): Promise<{ success: boolean; reference: string; error?: string }> => {
    if (!currentWallet || !currentUser) {
      return { success: false, reference: '', error: 'المحفظة غير جاهزة.' };
    }
    const cleanPhone = receiverPhone.trim();
    if (cleanPhone === currentUser.phone) {
      return { success: false, reference: '', error: 'لا يمكنك التحويل إلى نفس رقمك الحالي.' };
    }
    if (amount <= 0) {
      return { success: false, reference: '', error: 'يرجى إدخال مبلغ تحويل صحيح.' };
    }
    if (currentWallet.balance < amount) {
      return {
        success: false,
        reference: '',
        error: `الرصيد غير كافٍ. رصيدك الحالي: ${currentWallet.balance.toLocaleString()} SDG.`,
      };
    }

    // Find receiver user & wallet
    const foundUser = users.find((u) => u.phone === cleanPhone);
    let receiverUser: User;
    let receiverWallet: Wallet;

    if (!foundUser) {
      const newUserId = Math.max(...users.map((u) => u.id), 0) + 1;
      const newWalletId = Math.max(...wallets.map((w) => w.id), 0) + 1;
      receiverUser = {
        id: newUserId,
        fullName: `مستلم (${cleanPhone})`,
        phone: cleanPhone,
        email: null,
        role: 'user',
        pin: '1234',
        status: 'active',
        createdAt: Date.now(),
      };
      receiverWallet = {
        id: newWalletId,
        userId: newUserId,
        balance: 0,
        currency: 'SDG',
        status: 'active',
        updatedAt: Date.now(),
      };
      setUsers((prev) => [...prev, receiverUser]);
      setWallets((prev) => [...prev, receiverWallet]);
    } else {
      receiverUser = foundUser;
      const foundWallet = wallets.find((w) => w.userId === receiverUser.id);
      if (!foundWallet) {
        const newWalletId = Math.max(...wallets.map((w) => w.id), 0) + 1;
        receiverWallet = {
          id: newWalletId,
          userId: receiverUser.id,
          balance: 0,
          currency: 'SDG',
          status: 'active',
          updatedAt: Date.now(),
        };
        setWallets((prev) => [...prev, receiverWallet]);
      } else {
        receiverWallet = foundWallet;
      }
    }

    const ref = `TRF-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-${Math.floor(
      1000 + Math.random() * 9000
    )}`;

    const newSenderBal = currentWallet.balance - amount;
    const newReceiverBal = receiverWallet.balance + amount;

    // Update wallets
    setWallets((prev) =>
      prev.map((w) => {
        if (w.id === currentWallet.id) {
          return { ...w, balance: newSenderBal, updatedAt: Date.now() };
        }
        if (w.id === receiverWallet!.id) {
          return { ...w, balance: newReceiverBal, updatedAt: Date.now() };
        }
        return w;
      })
    );

    // Create transactions
    const senderTx: Transaction = {
      id: Date.now(),
      walletId: currentWallet.id,
      type: 'transfer_out',
      amount: amount,
      balanceAfter: newSenderBal,
      reference: ref,
      description: `تحويل مالي إلى ${receiverUser.fullName} (${cleanPhone})`,
      status: 'completed',
      createdAt: Date.now(),
    };

    const receiverTx: Transaction = {
      id: Date.now() + 1,
      walletId: receiverWallet.id,
      type: 'transfer_in',
      amount: amount,
      balanceAfter: newReceiverBal,
      reference: ref,
      description: `تحويل مالي وارد من ${currentUser.fullName} (${currentUser.phone})`,
      status: 'completed',
      createdAt: Date.now(),
    };

    setTransactions((prev) => [senderTx, receiverTx, ...prev]);

    // Create Transfer record
    const transferRecord: Transfer = {
      id: Date.now(),
      senderWalletId: currentWallet.id,
      receiverWalletId: receiverWallet.id,
      senderUserId: currentUser.id,
      receiverPhone: cleanPhone,
      amount: amount,
      reference: ref,
      note: note || null,
      status: 'completed',
      createdAt: Date.now(),
    };
    setTransfers((prev) => [transferRecord, ...prev]);

    // Send notifications to sender and receiver
    const senderNotif: Notification = {
      id: Date.now() + 2,
      userId: currentUser.id,
      title: 'تم إرسال التحويل بنجاح',
      message: `تم تحويل مبلغ ${amount.toLocaleString()} SDG بنجاح إلى ${receiverUser.fullName}.`,
      type: 'success',
      isRead: false,
      createdAt: Date.now(),
    };

    const receiverNotif: Notification = {
      id: Date.now() + 3,
      userId: receiverUser.id,
      title: 'تحويل مالي وارد',
      message: `استلمت تحويلاً مالياً بقيمة ${amount.toLocaleString()} SDG من ${currentUser.fullName}.`,
      type: 'success',
      isRead: false,
      createdAt: Date.now(),
    };

    setNotifications((prev) => [senderNotif, receiverNotif, ...prev]);

    return { success: true, reference: ref };
  };

  const submitInstantDeposit = async (
    amount: number
  ): Promise<{ success: boolean; reference: string; error?: string }> => {
    if (!currentWallet || !currentUser) {
      return { success: false, reference: '', error: 'المحفظة غير جاهزة.' };
    }
    if (amount <= 0) {
      return { success: false, reference: '', error: 'يرجى إدخال مبلغ إيداع صحيح.' };
    }

    const ref = `DEP-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-${Math.floor(
      1000 + Math.random() * 9000
    )}`;
    const newBal = currentWallet.balance + amount;

    setWallets((prev) =>
      prev.map((w) => (w.id === currentWallet.id ? { ...w, balance: newBal, updatedAt: Date.now() } : w))
    );

    const tx: Transaction = {
      id: Date.now(),
      walletId: currentWallet.id,
      type: 'deposit',
      amount: amount,
      balanceAfter: newBal,
      reference: ref,
      description: 'إيداع نقدي فوري تجريبي',
      status: 'completed',
      createdAt: Date.now(),
    };
    setTransactions((prev) => [tx, ...prev]);

    const notif: Notification = {
      id: Date.now() + 1,
      userId: currentUser.id,
      title: 'تم الإيداع الفوري بنجاح',
      message: `تمت إضافة مبلغ ${amount.toLocaleString()} SDG إلى رصيد محفظتك مباشرة.`,
      type: 'success',
      isRead: false,
      createdAt: Date.now(),
    };
    setNotifications((prev) => [notif, ...prev]);

    return { success: true, reference: ref };
  };

  const submitBankDeposit = async (
    amount: number,
    bankName: string,
    senderAccount: string,
    referenceNumber: string,
    note: string | null
  ): Promise<{ success: boolean; reference: string; error?: string }> => {
    if (!currentWallet || !currentUser) {
      return { success: false, reference: '', error: 'المحفظة غير جاهزة.' };
    }
    if (amount <= 0) {
      return { success: false, reference: '', error: 'يرجى إدخال مبلغ إيداع صحيح.' };
    }
    if (!referenceNumber.trim()) {
      return { success: false, reference: '', error: 'يرجى إدخال رقم إشعار التحويل البنكي.' };
    }

    const depositReq: DepositRequest = {
      id: Date.now(),
      userId: currentUser.id,
      walletId: currentWallet.id,
      amount,
      bankName: bankName.trim(),
      senderAccount: senderAccount.trim() || 'حساب بنكي',
      referenceNumber: referenceNumber.trim(),
      note: note?.trim() || null,
      status: 'pending',
      createdAt: Date.now(),
      reviewedAt: null,
    };

    setDeposits((prev) => [depositReq, ...prev]);

    const notif: Notification = {
      id: Date.now() + 1,
      userId: currentUser.id,
      title: 'طلب إيداع بنكي قيد المراجعة',
      message: `تم تسجيل إشعار الإيداع البنكي بمبلغ ${amount.toLocaleString()} SDG وسيتم اعتماده من إدارة المحفظة.`,
      type: 'info',
      isRead: false,
      createdAt: Date.now(),
    };
    setNotifications((prev) => [notif, ...prev]);

    return { success: true, reference: referenceNumber };
  };

  const submitWithdrawal = async (
    amount: number,
    recipientName: string,
    recipientPhone: string,
    method: string,
    note: string | null
  ): Promise<{ success: boolean; reference: string; error?: string }> => {
    if (!currentWallet || !currentUser) {
      return { success: false, reference: '', error: 'المحفظة غير جاهزة.' };
    }
    if (amount <= 0) {
      return { success: false, reference: '', error: 'يرجى إدخال مبلغ سحب صحيح.' };
    }
    if (currentWallet.balance < amount) {
      return {
        success: false,
        reference: '',
        error: `الرصيد غير كافٍ. رصيدك المتاح: ${currentWallet.balance.toLocaleString()} SDG.`,
      };
    }

    const ref = `WTH-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-${Math.floor(
      1000 + Math.random() * 9000
    )}`;
    const newBal = currentWallet.balance - amount;

    // Deduct immediately and hold in pending state
    setWallets((prev) =>
      prev.map((w) => (w.id === currentWallet.id ? { ...w, balance: newBal, updatedAt: Date.now() } : w))
    );

    const withdrawalReq: WithdrawalRequest = {
      id: Date.now(),
      userId: currentUser.id,
      walletId: currentWallet.id,
      amount,
      withdrawalMethod: method,
      recipientName: recipientName.trim(),
      recipientPhone: recipientPhone.trim(),
      note: note?.trim() || null,
      status: 'pending',
      createdAt: Date.now(),
      reviewedAt: null,
    };
    setWithdrawals((prev) => [withdrawalReq, ...prev]);

    const tx: Transaction = {
      id: Date.now() + 1,
      walletId: currentWallet.id,
      type: 'withdraw',
      amount: amount,
      balanceAfter: newBal,
      reference: ref,
      description: `طلب سحب نقدي عبر ${method} (${recipientName})`,
      status: 'pending',
      createdAt: Date.now(),
    };
    setTransactions((prev) => [tx, ...prev]);

    const notif: Notification = {
      id: Date.now() + 2,
      userId: currentUser.id,
      title: 'تم استلام طلب السحب',
      message: `تم حجز مبلغ ${amount.toLocaleString()} SDG لطلب السحب عبر ${method}. بانتظار اعتماد الإدارة.`,
      type: 'info',
      isRead: false,
      createdAt: Date.now(),
    };
    setNotifications((prev) => [notif, ...prev]);

    return { success: true, reference: ref };
  };

  const payMerchant = async (
    merchantName: string,
    merchantAccount: string,
    amount: number
  ): Promise<{ success: boolean; reference: string; error?: string }> => {
    if (!currentWallet || !currentUser) {
      return { success: false, reference: '', error: 'المحفظة غير جاهزة.' };
    }
    if (amount <= 0) {
      return { success: false, reference: '', error: 'يرجى إدخال مبلغ صحيح.' };
    }
    if (currentWallet.balance < amount) {
      return {
        success: false,
        reference: '',
        error: `الرصيد غير كافٍ. رصيدك المتاح: ${currentWallet.balance.toLocaleString()} SDG.`,
      };
    }

    const ref = `PAY-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-${Math.floor(
      1000 + Math.random() * 9000
    )}`;
    const newBal = currentWallet.balance - amount;

    setWallets((prev) =>
      prev.map((w) => (w.id === currentWallet.id ? { ...w, balance: newBal, updatedAt: Date.now() } : w))
    );

    const paymentRecord: Payment = {
      id: Date.now(),
      walletId: currentWallet.id,
      merchantName: merchantName.trim(),
      merchantAccount: merchantAccount.trim(),
      amount,
      reference: ref,
      createdAt: Date.now(),
    };
    setPayments((prev) => [paymentRecord, ...prev]);

    const tx: Transaction = {
      id: Date.now() + 1,
      walletId: currentWallet.id,
      type: 'payment',
      amount,
      balanceAfter: newBal,
      reference: ref,
      description: `دفع مشتريات وفاتورة: ${merchantName} (${merchantAccount})`,
      status: 'completed',
      createdAt: Date.now(),
    };
    setTransactions((prev) => [tx, ...prev]);

    const notif: Notification = {
      id: Date.now() + 2,
      userId: currentUser.id,
      title: 'تم دفع الفاتورة بنجاح',
      message: `تم سداد مبلغ ${amount.toLocaleString()} SDG لصالح ${merchantName}.`,
      type: 'success',
      isRead: false,
      createdAt: Date.now(),
    };
    setNotifications((prev) => [notif, ...prev]);

    return { success: true, reference: ref };
  };

  const payService = async (
    serviceType: string,
    provider: string,
    accountOrMeter: string,
    amount: number
  ): Promise<{ success: boolean; reference: string; tokenOrReceipt: string; error?: string }> => {
    if (!currentWallet || !currentUser) {
      return { success: false, reference: '', tokenOrReceipt: '', error: 'المحفظة غير جاهزة.' };
    }
    if (amount <= 0) {
      return { success: false, reference: '', tokenOrReceipt: '', error: 'يرجى إدخال مبلغ صحيح.' };
    }
    if (currentWallet.balance < amount) {
      return {
        success: false,
        reference: '',
        tokenOrReceipt: '',
        error: `الرصيد غير كافٍ. رصيدك المتاح: ${currentWallet.balance.toLocaleString()} SDG.`,
      };
    }

    const ref = `SRV-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-${Math.floor(
      1000 + Math.random() * 9000
    )}`;

    // Generate utility token or receipt voucher code
    let token = '';
    if (serviceType === 'electricity') {
      token = `${Math.floor(1000 + Math.random() * 9000)}-${Math.floor(
        1000 + Math.random() * 9000
      )}-${Math.floor(1000 + Math.random() * 9000)}-${Math.floor(1000 + Math.random() * 9000)}`;
    } else if (serviceType === 'recharge') {
      token = `RCG-${Math.floor(100000 + Math.random() * 900000)}`;
    } else {
      token = `RCP-${Math.floor(10000000 + Math.random() * 90000000)}`;
    }

    const newBal = currentWallet.balance - amount;

    setWallets((prev) =>
      prev.map((w) => (w.id === currentWallet.id ? { ...w, balance: newBal, updatedAt: Date.now() } : w))
    );

    const srvRecord: ServiceRequest = {
      id: Date.now(),
      userId: currentUser.id,
      walletId: currentWallet.id,
      serviceType,
      provider,
      accountOrMeter,
      amount,
      tokenOrReceipt: token,
      status: 'completed',
      createdAt: Date.now(),
    };
    setServices((prev) => [srvRecord, ...prev]);

    const serviceLabels: Record<string, string> = {
      recharge: 'شحن رصيد',
      electricity: 'شراء كهرباء',
      internet: 'فاتورة إنترنت',
      bills: 'سداد فواتير مياه وخدمات',
      education: 'رسوم جامعية وتعليم',
      government: 'خدمات حكومية إي-15',
    };

    const label = serviceLabels[serviceType] || 'خدمة إلكترونية';

    const tx: Transaction = {
      id: Date.now() + 1,
      walletId: currentWallet.id,
      type: serviceType,
      amount,
      balanceAfter: newBal,
      reference: ref,
      description: `${label} - ${provider} (${accountOrMeter})`,
      status: 'completed',
      createdAt: Date.now(),
    };
    setTransactions((prev) => [tx, ...prev]);

    const notif: Notification = {
      id: Date.now() + 2,
      userId: currentUser.id,
      title: `تم سداد ${label} بنجاح`,
      message:
        serviceType === 'electricity'
          ? `تم شراء كهرباء بمبلغ ${amount.toLocaleString()} SDG. كود الشحن (Token): ${token}`
          : `تم سداد عملية ${label} بمبلغ ${amount.toLocaleString()} SDG لحساب ${accountOrMeter}.`,
      type: 'success',
      isRead: false,
      createdAt: Date.now(),
    };
    setNotifications((prev) => [notif, ...prev]);

    return { success: true, reference: ref, tokenOrReceipt: token };
  };

  // Admin Actions
  const approveDeposit = async (depositId: number): Promise<boolean> => {
    const dep = deposits.find((d) => d.id === depositId);
    if (!dep || dep.status !== 'pending') return false;

    const targetWallet = wallets.find((w) => w.id === dep.walletId);
    if (!targetWallet) return false;

    const newBal = targetWallet.balance + dep.amount;

    setWallets((prev) =>
      prev.map((w) => (w.id === targetWallet.id ? { ...w, balance: newBal, updatedAt: Date.now() } : w))
    );

    setDeposits((prev) =>
      prev.map((d) => (d.id === depositId ? { ...d, status: 'approved', reviewedAt: Date.now() } : d))
    );

    const tx: Transaction = {
      id: Date.now(),
      walletId: targetWallet.id,
      type: 'deposit',
      amount: dep.amount,
      balanceAfter: newBal,
      reference: dep.referenceNumber,
      description: `إيداع معتمد عبر ${dep.bankName}`,
      status: 'completed',
      createdAt: Date.now(),
    };
    setTransactions((prev) => [tx, ...prev]);

    const notif: Notification = {
      id: Date.now() + 1,
      userId: dep.userId,
      title: 'تم اعتماد إيداعك البنكي!',
      message: `وافقت الإدارة على إيداعك بمبلغ ${dep.amount.toLocaleString()} SDG وأضيفت لمحفظتك.`,
      type: 'success',
      isRead: false,
      createdAt: Date.now(),
    };
    setNotifications((prev) => [notif, ...prev]);

    const audit: AuditLog = {
      id: Date.now() + 2,
      adminUserId: currentUser?.id || 3,
      action: 'APPROVE_DEPOSIT',
      targetEntity: 'DepositRequest',
      targetId: depositId,
      details: `اعتماد إيداع بنكي للمستخدم #${dep.userId} بمبلغ ${dep.amount.toLocaleString()} SDG`,
      createdAt: Date.now(),
    };
    setAuditLogs((prev) => [audit, ...prev]);

    return true;
  };

  const rejectDeposit = async (depositId: number, reason?: string): Promise<boolean> => {
    const dep = deposits.find((d) => d.id === depositId);
    if (!dep || dep.status !== 'pending') return false;

    setDeposits((prev) =>
      prev.map((d) => (d.id === depositId ? { ...d, status: 'rejected', reviewedAt: Date.now() } : d))
    );

    const notif: Notification = {
      id: Date.now(),
      userId: dep.userId,
      title: 'تم رفض طلب الإيداع البنكي',
      message: `تم رفض إشعار الإيداع رقم ${dep.referenceNumber}. ${reason ? `السبب: ${reason}` : 'يرجى التأكد من البيانات أو مراجعة الدعم.'}`,
      type: 'warning',
      isRead: false,
      createdAt: Date.now(),
    };
    setNotifications((prev) => [notif, ...prev]);

    const audit: AuditLog = {
      id: Date.now() + 1,
      adminUserId: currentUser?.id || 3,
      action: 'REJECT_DEPOSIT',
      targetEntity: 'DepositRequest',
      targetId: depositId,
      details: `رفض إيداع للمستخدم #${dep.userId}. السبب: ${reason || 'غير محدد'}`,
      createdAt: Date.now(),
    };
    setAuditLogs((prev) => [audit, ...prev]);

    return true;
  };

  const approveWithdrawal = async (withdrawalId: number): Promise<boolean> => {
    const wth = withdrawals.find((w) => w.id === withdrawalId);
    if (!wth || wth.status !== 'pending') return false;

    setWithdrawals((prev) =>
      prev.map((w) => (w.id === withdrawalId ? { ...w, status: 'approved', reviewedAt: Date.now() } : w))
    );

    // Update pending transaction status to completed
    setTransactions((prev) =>
      prev.map((t) => {
        if (t.walletId === wth.walletId && t.type === 'withdraw' && t.status === 'pending') {
          return { ...t, status: 'completed' };
        }
        return t;
      })
    );

    const notif: Notification = {
      id: Date.now(),
      userId: wth.userId,
      title: 'تم اعتماد طلب السحب بنجاح',
      message: `تمت الموافقة على سحب مبلغ ${wth.amount.toLocaleString()} SDG عبر ${wth.withdrawalMethod}. كود الاستلام جاهز لدى الوكيل.`,
      type: 'success',
      isRead: false,
      createdAt: Date.now(),
    };
    setNotifications((prev) => [notif, ...prev]);

    const audit: AuditLog = {
      id: Date.now() + 1,
      adminUserId: currentUser?.id || 3,
      action: 'APPROVE_WITHDRAWAL',
      targetEntity: 'WithdrawalRequest',
      targetId: withdrawalId,
      details: `اعتماد سحب نقدي للمستخدم #${wth.userId} بمبلغ ${wth.amount.toLocaleString()} SDG`,
      createdAt: Date.now(),
    };
    setAuditLogs((prev) => [audit, ...prev]);

    return true;
  };

  const rejectWithdrawal = async (withdrawalId: number, reason?: string): Promise<boolean> => {
    const wth = withdrawals.find((w) => w.id === withdrawalId);
    if (!wth || wth.status !== 'pending') return false;

    const targetWallet = wallets.find((w) => w.id === wth.walletId);
    if (!targetWallet) return false;

    // Refund deducted balance
    const newBal = targetWallet.balance + wth.amount;

    setWallets((prev) =>
      prev.map((w) => (w.id === targetWallet.id ? { ...w, balance: newBal, updatedAt: Date.now() } : w))
    );

    setWithdrawals((prev) =>
      prev.map((w) => (w.id === withdrawalId ? { ...w, status: 'rejected', reviewedAt: Date.now() } : w))
    );

    // Refund transaction
    const tx: Transaction = {
      id: Date.now(),
      walletId: targetWallet.id,
      type: 'deposit',
      amount: wth.amount,
      balanceAfter: newBal,
      reference: `REFUND-${Date.now().toString().slice(-6)}`,
      description: `استرجاع رصيد لطلب سحب مرفوض (${wth.withdrawalMethod})`,
      status: 'completed',
      createdAt: Date.now(),
    };
    setTransactions((prev) => [tx, ...prev]);

    const notif: Notification = {
      id: Date.now() + 1,
      userId: wth.userId,
      title: 'تم رفض طلب السحب واسترجاع الرصيد',
      message: `تم رفض طلب السحب واستعادة مبلغ ${wth.amount.toLocaleString()} SDG إلى محفظتك. ${reason ? `السبب: ${reason}` : ''}`,
      type: 'warning',
      isRead: false,
      createdAt: Date.now(),
    };
    setNotifications((prev) => [notif, ...prev]);

    const audit: AuditLog = {
      id: Date.now() + 2,
      adminUserId: currentUser?.id || 3,
      action: 'REJECT_WITHDRAWAL',
      targetEntity: 'WithdrawalRequest',
      targetId: withdrawalId,
      details: `رفض سحب نقدي للمستخدم #${wth.userId} واسترجاع الرصيد. السبب: ${reason || 'غير محدد'}`,
      createdAt: Date.now(),
    };
    setAuditLogs((prev) => [audit, ...prev]);

    return true;
  };

  const toggleUserStatus = async (userId: number): Promise<boolean> => {
    setUsers((prev) =>
      prev.map((u) => {
        if (u.id === userId) {
          const nextStatus = u.status === 'active' ? 'suspended' : 'active';
          return { ...u, status: nextStatus };
        }
        return u;
      })
    );
    return true;
  };

  const markNotificationAsRead = (id: number) => {
    setNotifications((prev) => prev.map((n) => (n.id === id ? { ...n, isRead: true } : n)));
  };

  const markAllNotificationsAsRead = () => {
    if (!currentUser) return;
    setNotifications((prev) =>
      prev.map((n) => (n.userId === currentUser.id ? { ...n, isRead: true } : n))
    );
  };

  const resetToDefault = () => {
    localStorage.clear();
    setUsers(INITIAL_USERS);
    setWallets(INITIAL_WALLETS);
    setCurrentUserId(1);
    setTransactions(INITIAL_TRANSACTIONS);
    setTransfers(INITIAL_TRANSFERS);
    setDeposits(INITIAL_DEPOSITS);
    setWithdrawals(INITIAL_WITHDRAWALS);
    setPayments(INITIAL_PAYMENTS);
    setServices(INITIAL_SERVICES);
    setNotifications(INITIAL_NOTIFICATIONS);
    setAuditLogs(INITIAL_AUDIT_LOGS);
  };

  return (
    <WalletContext.Provider
      value={{
        currentUser,
        currentWallet,
        allUsers: users,
        allWallets: wallets,
        transactions: userTransactions,
        allTransactions: transactions,
        notifications: userNotifications,
        unreadCount,
        userDeposits,
        userWithdrawals,
        allDeposits: deposits,
        allWithdrawals: withdrawals,
        allAuditLogs: auditLogs,
        switchUser,
        registerNewUser,
        transfer,
        submitInstantDeposit,
        submitBankDeposit,
        submitWithdrawal,
        payMerchant,
        payService,
        approveDeposit,
        rejectDeposit,
        approveWithdrawal,
        rejectWithdrawal,
        toggleUserStatus,
        markNotificationAsRead,
        markAllNotificationsAsRead,
        resetToDefault,
      }}
    >
      {children}
    </WalletContext.Provider>
  );
};

export const useWallet = () => {
  const context = useContext(WalletContext);
  if (!context) {
    throw new Error('useWallet must be used within a WalletProvider');
  }
  return context;
};
