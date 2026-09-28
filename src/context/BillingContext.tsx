import React, { createContext, useContext, useState, useEffect } from 'react';
import {
  User,
  CompanySettings,
  Customer,
  Quotation,
  Invoice,
  Payment,
  LineItem
} from '../types';
import {
  initialUser,
  initialCompany,
  initialCustomers,
  initialQuotations,
  initialInvoices,
  initialPayments
} from '../data/seedData';

export type NavigationTab = 
  | 'dashboard'
  | 'invoices'
  | 'quotations'
  | 'payments'
  | 'customers'
  | 'reports'
  | 'company'
  | 'schema';

interface FlashMessage {
  message: string;
  type: 'success' | 'danger' | 'info' | 'warning';
}

export interface AuthResult {
  success: boolean;
  error?: string;
}

interface BillingContextType {
  // Auth state
  isAuthenticated: boolean;
  isLoggedOutScreen: boolean;
  setIsLoggedOutScreen: (show: boolean) => void;
  lastUserLoggedOut: User | null;
  login: (email: string, password?: string, rememberMe?: boolean) => AuthResult;
  logout: () => void;
  register: (name: string, email: string, password?: string, role?: 'admin' | 'manager' | 'staff') => AuthResult;

  user: User;
  company: CompanySettings;
  customers: Customer[];
  quotations: Quotation[];
  invoices: Invoice[];
  payments: Payment[];
  activeTab: NavigationTab;
  setActiveTab: (tab: NavigationTab) => void;
  flash: FlashMessage | null;
  showFlash: (message: string, type?: 'success' | 'danger' | 'info' | 'warning') => void;
  clearFlash: () => void;
  
  // Navigation / Modal States
  viewingInvoiceId: number | null;
  setViewingInvoiceId: (id: number | null) => void;
  editingInvoiceId: number | null;
  setEditingInvoiceId: (id: number | null) => void;
  isCreatingInvoice: boolean;
  setIsCreatingInvoice: (isCreating: boolean) => void;

  viewingQuotationId: number | null;
  setViewingQuotationId: (id: number | null) => void;
  editingQuotationId: number | null;
  setEditingQuotationId: (id: number | null) => void;
  isCreatingQuotation: boolean;
  setIsCreatingQuotation: (isCreating: boolean) => void;

  viewingCustomerId: number | null;
  setViewingCustomerId: (id: number | null) => void;
  editingCustomerId: number | null;
  setEditingCustomerId: (id: number | null) => void;
  isCreatingCustomer: boolean;
  setIsCreatingCustomer: (isCreating: boolean) => void;

  paymentModalInvoiceId: number | null;
  setPaymentModalInvoiceId: (invoiceId: number | null) => void;

  // Actions
  formatCurrency: (amount: number) => string;
  getCustomerById: (id: number) => Customer | undefined;
  getInvoiceById: (id: number) => Invoice | undefined;
  getQuotationById: (id: number) => Quotation | undefined;
  
  createInvoice: (data: Omit<Invoice, 'id' | 'paid_amount' | 'balance' | 'created_at'>) => Invoice;
  updateInvoice: (id: number, data: Partial<Invoice>) => void;
  deleteInvoice: (id: number) => void;

  createQuotation: (data: Omit<Quotation, 'id' | 'created_at'>) => Quotation;
  updateQuotation: (id: number, data: Partial<Quotation>) => void;
  deleteQuotation: (id: number) => void;
  convertQuotationToInvoice: (quotationId: number) => Invoice | null;

  createPayment: (data: Omit<Payment, 'id' | 'created_at'>) => void;
  deletePayment: (id: number) => void;

  createCustomer: (data: Omit<Customer, 'id' | 'created_at'>) => Customer;
  updateCustomer: (id: number, data: Partial<Customer>) => void;
  deleteCustomer: (id: number) => void;

  updateCompanySettings: (data: Partial<CompanySettings>) => void;
  resetToSeedData: () => void;
}

const BillingContext = createContext<BillingContextType | undefined>(undefined);

interface StoredUser extends User {
  password?: string;
}

const DEFAULT_USERS: StoredUser[] = [
  {
    id: 1,
    name: 'John Kamau',
    email: 'johnkamaukibe126@gmail.com',
    password: 'admin123',
    role: 'admin',
    status: 'active',
    department: 'Executive Operations',
    last_login: '2026-09-28'
  },
  {
    id: 2,
    name: 'Admin PaperGlow',
    email: 'admin@paperglow.co.ke',
    password: 'admin123',
    role: 'admin',
    status: 'active',
    department: 'Administration',
    last_login: '2026-09-28'
  },
  {
    id: 3,
    name: 'Sarah Wanjiku',
    email: 'finance@paperglow.co.ke',
    password: 'finance123',
    role: 'manager',
    status: 'active',
    department: 'Finance & Accounts',
    last_login: '2026-09-28'
  },
  {
    id: 4,
    name: 'David Omondi',
    email: 'billing@paperglow.co.ke',
    password: 'billing123',
    role: 'staff',
    status: 'active',
    department: 'Billing & Invoicing',
    last_login: '2026-09-28'
  }
];

const STORAGE_KEY = 'paperglow_billing_data_v4';
const AUTH_SESSION_KEY = 'paperglow_auth_session_v1';
const USERS_STORAGE_KEY = 'paperglow_users_v1';

export const BillingProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  // Load initial data from localStorage if available
  const [dataLoaded, setDataLoaded] = useState(false);
  const [user, setUser] = useState<User>(DEFAULT_USERS[0]);
  const [usersList, setUsersList] = useState<StoredUser[]>(DEFAULT_USERS);
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(true);
  const [isLoggedOutScreen, setIsLoggedOutScreen] = useState<boolean>(false);
  const [lastUserLoggedOut, setLastUserLoggedOut] = useState<User | null>(null);

  const [company, setCompany] = useState<CompanySettings>(initialCompany);
  const [customers, setCustomers] = useState<Customer[]>(initialCustomers);
  const [quotations, setQuotations] = useState<Quotation[]>(initialQuotations);
  const [invoices, setInvoices] = useState<Invoice[]>(initialInvoices);
  const [payments, setPayments] = useState<Payment[]>(initialPayments);

  const [activeTab, setActiveTab] = useState<NavigationTab>('dashboard');
  const [flash, setFlash] = useState<FlashMessage | null>(null);

  // View states
  const [viewingInvoiceId, setViewingInvoiceId] = useState<number | null>(null);
  const [editingInvoiceId, setEditingInvoiceId] = useState<number | null>(null);
  const [isCreatingInvoice, setIsCreatingInvoice] = useState(false);

  const [viewingQuotationId, setViewingQuotationId] = useState<number | null>(null);
  const [editingQuotationId, setEditingQuotationId] = useState<number | null>(null);
  const [isCreatingQuotation, setIsCreatingQuotation] = useState(false);

  const [viewingCustomerId, setViewingCustomerId] = useState<number | null>(null);
  const [editingCustomerId, setEditingCustomerId] = useState<number | null>(null);
  const [isCreatingCustomer, setIsCreatingCustomer] = useState(false);

  const [paymentModalInvoiceId, setPaymentModalInvoiceId] = useState<number | null>(null);

  // Authentication functions
  const login = (emailInput: string, passwordInput?: string, rememberMe: boolean = true): AuthResult => {
    const cleanEmail = emailInput.trim().toLowerCase();
    const foundUser = usersList.find((u) => u.email.toLowerCase() === cleanEmail);

    if (!foundUser) {
      return { success: false, error: 'No account found with this email address.' };
    }

    if (foundUser.password && passwordInput && foundUser.password !== passwordInput.trim()) {
      return { success: false, error: 'Incorrect password. Please try again.' };
    }

    const loggedInUser: User = {
      ...foundUser,
      last_login: new Date().toISOString().split('T')[0]
    };

    setUser(loggedInUser);
    setIsAuthenticated(true);
    setIsLoggedOutScreen(false);
    setLastUserLoggedOut(null);

    try {
      if (rememberMe) {
        localStorage.setItem(AUTH_SESSION_KEY, JSON.stringify(loggedInUser));
      } else {
        sessionStorage.setItem(AUTH_SESSION_KEY, JSON.stringify(loggedInUser));
      }
    } catch (e) {
      console.error(e);
    }

    showFlash(`Welcome back, ${loggedInUser.name}!`, 'success');
    return { success: true };
  };

  const logout = () => {
    setLastUserLoggedOut(user);
    setIsAuthenticated(false);
    setIsLoggedOutScreen(true);
    try {
      localStorage.removeItem(AUTH_SESSION_KEY);
      sessionStorage.removeItem(AUTH_SESSION_KEY);
    } catch (e) {
      console.error(e);
    }
    showFlash('You have been safely signed out.', 'info');
  };

  const register = (
    name: string,
    emailInput: string,
    passwordInput?: string,
    role: 'admin' | 'manager' | 'staff' = 'admin'
  ): AuthResult => {
    const cleanEmail = emailInput.trim().toLowerCase();
    const existing = usersList.find((u) => u.email.toLowerCase() === cleanEmail);
    if (existing) {
      return { success: false, error: 'An account with this email address already exists.' };
    }

    const newUser: StoredUser = {
      id: Date.now(),
      name: name.trim(),
      email: cleanEmail,
      password: passwordInput?.trim() || 'password123',
      role,
      status: 'active',
      department: role === 'admin' ? 'Administration' : role === 'manager' ? 'Finance' : 'Operations',
      last_login: new Date().toISOString().split('T')[0]
    };

    const updatedUsers = [...usersList, newUser];
    setUsersList(updatedUsers);
    try {
      localStorage.setItem(USERS_STORAGE_KEY, JSON.stringify(updatedUsers));
    } catch (e) {
      console.error(e);
    }

    const sessionUser: User = { ...newUser };
    setUser(sessionUser);
    setIsAuthenticated(true);
    setIsLoggedOutScreen(false);
    setLastUserLoggedOut(null);
    try {
      localStorage.setItem(AUTH_SESSION_KEY, JSON.stringify(sessionUser));
    } catch (e) {
      console.error(e);
    }
    showFlash(`Account registered! Welcome, ${newUser.name}.`, 'success');
    return { success: true };
  };

  // Load state on mount
  useEffect(() => {
    try {
      localStorage.removeItem('paperglow_billing_data_v1');
      localStorage.removeItem('paperglow_billing_data_v2');
      localStorage.removeItem('paperglow_billing_data_v3');
      
      // Load stored custom users
      const savedUsers = localStorage.getItem(USERS_STORAGE_KEY);
      if (savedUsers) {
        try {
          const parsedUsers = JSON.parse(savedUsers);
          if (Array.isArray(parsedUsers) && parsedUsers.length > 0) {
            setUsersList(parsedUsers);
          }
        } catch (err) {
          console.error(err);
        }
      }

      // Load session
      const savedSession = localStorage.getItem(AUTH_SESSION_KEY) || sessionStorage.getItem(AUTH_SESSION_KEY);
      if (savedSession) {
        try {
          const parsedSession = JSON.parse(savedSession);
          if (parsedSession && parsedSession.email) {
            setUser(parsedSession);
            setIsAuthenticated(true);
          }
        } catch (err) {
          console.error(err);
        }
      }

      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved) {
        const parsed = JSON.parse(saved);
        if (parsed.company) {
          const currency = parsed.company.currency === 'USD' || !parsed.company.currency ? 'KES' : parsed.company.currency;
          const companyName = !parsed.company.company_name || parsed.company.company_name === 'PaperGlow Studio' ? 'PaperGlow Enterprise' : parsed.company.company_name;
          const logo = !parsed.company.logo || parsed.company.logo.includes('sample_logo') ? '/uploads/logos/paperglow_enterprise.png' : parsed.company.logo;
          setCompany({ ...parsed.company, currency, company_name: companyName, logo });
        }
        if (parsed.customers) setCustomers(parsed.customers);
        if (parsed.quotations) setQuotations(parsed.quotations);
        if (parsed.invoices) setInvoices(parsed.invoices);
        if (parsed.payments) setPayments(parsed.payments);
      }
    } catch (e) {
      console.error('Failed to load from storage', e);
    } finally {
      setDataLoaded(true);
    }
  }, []);

  // Save state on change
  useEffect(() => {
    if (!dataLoaded) return;
    try {
      localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
          user,
          company,
          customers,
          quotations,
          invoices,
          payments,
        })
      );
    } catch (e) {
      console.error('Failed to save to storage', e);
    }
  }, [dataLoaded, user, company, customers, quotations, invoices, payments]);

  const showFlash = (message: string, type: 'success' | 'danger' | 'info' | 'warning' = 'success') => {
    setFlash({ message, type });
    setTimeout(() => {
      setFlash((prev) => (prev?.message === message ? null : prev));
    }, 4000);
  };

  const clearFlash = () => setFlash(null);

  const formatCurrency = (amount: number): string => {
    const curr = company.currency || 'KES';
    const formatted = Number(amount || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
    return `${curr} ${formatted}`;
  };

  const getCustomerById = (id: number) => customers.find((c) => c.id === id);
  const getInvoiceById = (id: number) => invoices.find((inv) => inv.id === id);
  const getQuotationById = (id: number) => quotations.find((q) => q.id === id);

  // Invoices Actions
  const createInvoice = (data: Omit<Invoice, 'id' | 'paid_amount' | 'balance' | 'created_at'>): Invoice => {
    const nextId = invoices.length > 0 ? Math.max(...invoices.map((i) => i.id)) + 1 : 1;
    const now = new Date().toISOString().replace('T', ' ').substring(0, 19);
    const newInvoice: Invoice = {
      ...data,
      id: nextId,
      paid_amount: 0,
      balance: data.grand_total,
      created_at: now,
    };
    setInvoices([newInvoice, ...invoices]);
    showFlash(`Invoice ${newInvoice.invoice_number} created successfully.`);
    return newInvoice;
  };

  const updateInvoice = (id: number, data: Partial<Invoice>) => {
    setInvoices((prev) =>
      prev.map((inv) => {
        if (inv.id !== id) return inv;
        const updated = { ...inv, ...data };
        // Recalculate balance
        updated.balance = Math.max(0, updated.grand_total - updated.paid_amount);
        if (updated.status !== 'Cancelled' && updated.status !== 'Draft') {
          if (updated.balance <= 0) {
            updated.status = 'Paid';
          } else if (updated.paid_amount > 0) {
            updated.status = 'Partially Paid';
          }
        }
        return updated;
      })
    );
    showFlash(`Invoice updated successfully.`);
  };

  const deleteInvoice = (id: number) => {
    const target = invoices.find((i) => i.id === id);
    setInvoices((prev) => prev.filter((i) => i.id !== id));
    // Also remove associated payments
    setPayments((prev) => prev.filter((p) => p.invoice_id !== id));
    showFlash(`Invoice ${target?.invoice_number || ''} deleted.`);
  };

  // Quotations Actions
  const createQuotation = (data: Omit<Quotation, 'id' | 'created_at'>): Quotation => {
    const nextId = quotations.length > 0 ? Math.max(...quotations.map((q) => q.id)) + 1 : 1;
    const now = new Date().toISOString().replace('T', ' ').substring(0, 19);
    const newQuo: Quotation = {
      ...data,
      id: nextId,
      created_at: now,
    };
    setQuotations([newQuo, ...quotations]);
    showFlash(`Quotation ${newQuo.quotation_number} generated.`);
    return newQuo;
  };

  const updateQuotation = (id: number, data: Partial<Quotation>) => {
    setQuotations((prev) =>
      prev.map((q) => (q.id === id ? { ...q, ...data } : q))
    );
    showFlash(`Quotation updated.`);
  };

  const deleteQuotation = (id: number) => {
    const target = quotations.find((q) => q.id === id);
    setQuotations((prev) => prev.filter((q) => q.id !== id));
    showFlash(`Quotation ${target?.quotation_number || ''} deleted.`);
  };

  const convertQuotationToInvoice = (quotationId: number): Invoice | null => {
    const quotation = quotations.find((q) => q.id === quotationId);
    if (!quotation) return null;

    const nextInvId = invoices.length > 0 ? Math.max(...invoices.map((i) => i.id)) + 1 : 1;
    const year = new Date().getFullYear();
    const invoiceNumber = `INV-${year}-${String(nextInvId).padStart(4, '0')}`;
    const today = new Date().toISOString().split('T')[0];
    const dueDate = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    const newInvoice: Invoice = {
      id: nextInvId,
      user_id: user.id,
      customer_id: quotation.customer_id,
      from_quotation_id: quotation.id,
      invoice_number: invoiceNumber,
      invoice_date: today,
      due_date: dueDate,
      status: 'Unpaid',
      subtotal: quotation.subtotal,
      discount_total: quotation.discount_total,
      tax_total: quotation.tax_total,
      grand_total: quotation.grand_total,
      paid_amount: 0,
      balance: quotation.grand_total,
      notes: quotation.notes || `Converted from quotation ${quotation.quotation_number}.`,
      terms: quotation.terms || company.default_invoice_terms,
      items: quotation.items.map((item, idx) => ({ ...item, id: idx + 1 })),
      created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
    };

    setInvoices([newInvoice, ...invoices]);
    // Mark quotation accepted
    updateQuotation(quotationId, {
      status: 'Accepted',
      converted_invoice_id: newInvoice.id,
    });

    showFlash(`Converted ${quotation.quotation_number} into invoice ${newInvoice.invoice_number}!`);
    return newInvoice;
  };

  // Payments Actions
  const createPayment = (data: Omit<Payment, 'id' | 'created_at'>) => {
    const nextId = payments.length > 0 ? Math.max(...payments.map((p) => p.id)) + 1 : 1;
    const now = new Date().toISOString().replace('T', ' ').substring(0, 19);
    const newPayment: Payment = {
      ...data,
      id: nextId,
      created_at: now,
    };

    setPayments([newPayment, ...payments]);

    // Recalculate target invoice paid amount and balance
    setInvoices((prev) =>
      prev.map((inv) => {
        if (inv.id !== data.invoice_id) return inv;
        const newPaid = Number(inv.paid_amount || 0) + Number(data.amount);
        const newBalance = Math.max(0, inv.grand_total - newPaid);
        let newStatus = inv.status;
        if (newBalance <= 0) {
          newStatus = 'Paid';
        } else if (newPaid > 0) {
          newStatus = 'Partially Paid';
        }
        return {
          ...inv,
          paid_amount: newPaid,
          balance: newBalance,
          status: newStatus,
        };
      })
    );

    showFlash(`Payment of ${formatCurrency(data.amount)} recorded successfully.`);
  };

  const deletePayment = (id: number) => {
    const payment = payments.find((p) => p.id === id);
    if (!payment) return;

    setPayments((prev) => prev.filter((p) => p.id !== id));

    // Restore target invoice balance
    setInvoices((prev) =>
      prev.map((inv) => {
        if (inv.id !== payment.invoice_id) return inv;
        const newPaid = Math.max(0, Number(inv.paid_amount || 0) - Number(payment.amount));
        const newBalance = Math.max(0, inv.grand_total - newPaid);
        let newStatus = inv.status;
        if (newPaid === 0) {
          newStatus = new Date(inv.due_date) < new Date() ? 'Overdue' : 'Unpaid';
        } else if (newBalance > 0) {
          newStatus = 'Partially Paid';
        } else {
          newStatus = 'Paid';
        }
        return {
          ...inv,
          paid_amount: newPaid,
          balance: newBalance,
          status: newStatus,
        };
      })
    );

    showFlash(`Payment deleted and invoice balance restored.`);
  };

  // Customers Actions
  const createCustomer = (data: Omit<Customer, 'id' | 'created_at'>): Customer => {
    const nextId = customers.length > 0 ? Math.max(...customers.map((c) => c.id)) + 1 : 1;
    const now = new Date().toISOString().replace('T', ' ').substring(0, 19);
    const newCustomer: Customer = {
      ...data,
      id: nextId,
      created_at: now,
    };
    setCustomers([newCustomer, ...customers]);
    showFlash(`Customer ${newCustomer.name} added.`);
    return newCustomer;
  };

  const updateCustomer = (id: number, data: Partial<Customer>) => {
    setCustomers((prev) =>
      prev.map((c) => (c.id === id ? { ...c, ...data } : c))
    );
    showFlash(`Customer profile updated.`);
  };

  const deleteCustomer = (id: number) => {
    const cust = customers.find((c) => c.id === id);
    // Check if customer has invoices
    const hasInvoices = invoices.some((i) => i.customer_id === id);
    if (hasInvoices) {
      showFlash(`Cannot delete customer who has existing invoices.`, 'danger');
      return;
    }
    setCustomers((prev) => prev.filter((c) => c.id !== id));
    showFlash(`Customer ${cust?.name || ''} deleted.`);
  };

  // Company Settings
  const updateCompanySettings = (data: Partial<CompanySettings>) => {
    setCompany((prev) => ({ ...prev, ...data }));
    showFlash(`Company & remittance preferences saved.`);
  };

  const resetToSeedData = () => {
    setUser(initialUser);
    setCompany(initialCompany);
    setCustomers(initialCustomers);
    setQuotations(initialQuotations);
    setInvoices(initialInvoices);
    setPayments(initialPayments);
    localStorage.removeItem(STORAGE_KEY);
    showFlash(`All records cleared and reset successfully!`, 'info');
  };

  return (
    <BillingContext.Provider
      value={{
        isAuthenticated,
        isLoggedOutScreen,
        setIsLoggedOutScreen,
        lastUserLoggedOut,
        login,
        logout,
        register,

        user,
        company,
        customers,
        quotations,
        invoices,
        payments,
        activeTab,
        setActiveTab,
        flash,
        showFlash,
        clearFlash,

        viewingInvoiceId,
        setViewingInvoiceId,
        editingInvoiceId,
        setEditingInvoiceId,
        isCreatingInvoice,
        setIsCreatingInvoice,

        viewingQuotationId,
        setViewingQuotationId,
        editingQuotationId,
        setEditingQuotationId,
        isCreatingQuotation,
        setIsCreatingQuotation,

        viewingCustomerId,
        setViewingCustomerId,
        editingCustomerId,
        setEditingCustomerId,
        isCreatingCustomer,
        setIsCreatingCustomer,

        paymentModalInvoiceId,
        setPaymentModalInvoiceId,

        formatCurrency,
        getCustomerById,
        getInvoiceById,
        getQuotationById,

        createInvoice,
        updateInvoice,
        deleteInvoice,

        createQuotation,
        updateQuotation,
        deleteQuotation,
        convertQuotationToInvoice,

        createPayment,
        deletePayment,

        createCustomer,
        updateCustomer,
        deleteCustomer,

        updateCompanySettings,
        resetToSeedData,
      }}
    >
      {children}
    </BillingContext.Provider>
  );
};

export const useBilling = () => {
  const context = useContext(BillingContext);
  if (!context) {
    throw new Error('useBilling must be used within a BillingProvider');
  }
  return context;
};
