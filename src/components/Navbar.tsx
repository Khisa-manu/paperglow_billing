import React, { useState } from 'react';
import { useBilling } from '../context/BillingContext';
import {
  Menu,
  Plus,
  Receipt,
  FileText,
  UserPlus,
  Wallet,
  Building2,
  DollarSign,
  LogOut,
  Settings,
  User as UserIcon
} from 'lucide-react';

interface NavbarProps {
  setMobileOpen: (open: boolean) => void;
}

export const Navbar: React.FC<NavbarProps> = ({ setMobileOpen }) => {
  const {
    activeTab,
    setActiveTab,
    company,
    user,
    logout,
    setIsCreatingInvoice,
    setViewingInvoiceId,
    setEditingInvoiceId,
    setIsCreatingQuotation,
    setViewingQuotationId,
    setEditingQuotationId,
    setIsCreatingCustomer,
    setPaymentModalInvoiceId
  } = useBilling();

  const [quickMenuOpen, setQuickMenuOpen] = useState(false);
  const [userMenuOpen, setUserMenuOpen] = useState(false);

  const getPageTitle = () => {
    switch (activeTab) {
      case 'dashboard':
        return 'Executive Dashboard';
      case 'invoices':
        return 'Invoice Management';
      case 'quotations':
        return 'Quotation & Estimates';
      case 'payments':
        return 'Payment Transactions';
      case 'customers':
        return 'Customer Directory';
      case 'reports':
        return 'Financial Intelligence & Reports';
      case 'company':
        return 'Company & Billing Settings';
      case 'schema':
        return 'Database Specification';
      default:
        return 'PaperGlow Billing';
    }
  };

  return (
    <header className="pg-topbar d-flex align-items-center justify-content-between">
      <div className="d-flex align-items-center gap-3">
        <button
          onClick={() => setMobileOpen(true)}
          className="btn btn-sm btn-outline-secondary d-lg-none p-1.5"
          aria-label="Toggle navigation menu"
        >
          <Menu className="w-5 h-5 text-slate-700" />
        </button>

        <div>
          <h1 className="h5 fw-bold mb-0 text-slate-900 tracking-tight flex items-center gap-2">
            <span>{getPageTitle()}</span>
          </h1>
          <span className="text-xs text-slate-500 font-medium">
            {company.company_name} &bull; Currency: <strong className="text-amber-700">{company.currency}</strong>
          </span>
        </div>
      </div>

      <div className="d-flex align-items-center gap-2 position-relative">
        {/* Currency Pill */}
        <span className="badge bg-slate-100 text-slate-700 border border-slate-300 px-2.5 py-1.5 rounded-md font-mono text-xs d-none d-sm-inline-flex align-items-center gap-1">
          <DollarSign className="w-3.5 h-3.5 text-amber-600" />
          <span>{company.currency}</span>
        </span>

        {/* Quick New Dropdown */}
        <div className="position-relative">
          <button
            onClick={() => setQuickMenuOpen(!quickMenuOpen)}
            className="btn btn-pg-primary d-flex align-items-center gap-1.5 text-sm"
          >
            <Plus className="w-4 h-4" />
            <span className="d-none d-sm-inline">Create New</span>
          </button>

          {quickMenuOpen && (
            <>
              <div
                className="position-fixed top-0 start-0 w-100 h-100"
                style={{ zIndex: 1040 }}
                onClick={() => setQuickMenuOpen(false)}
              />
              <div
                className="position-absolute end-0 mt-2 bg-white border border-slate-200 rounded-lg shadow-lg py-2 min-w-[200px]"
                style={{ zIndex: 1050 }}
              >
                <div className="px-3 py-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                  Quick Actions
                </div>
                <button
                  onClick={() => {
                    setActiveTab('invoices');
                    setViewingInvoiceId(null);
                    setEditingInvoiceId(null);
                    setIsCreatingInvoice(true);
                    setQuickMenuOpen(false);
                  }}
                  className="w-100 text-start px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 border-0 bg-transparent"
                >
                  <Receipt className="w-4 h-4 text-amber-600" />
                  <span>New Invoice</span>
                </button>

                <button
                  onClick={() => {
                    setActiveTab('quotations');
                    setViewingQuotationId(null);
                    setEditingQuotationId(null);
                    setIsCreatingQuotation(true);
                    setQuickMenuOpen(false);
                  }}
                  className="w-100 text-start px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 border-0 bg-transparent"
                >
                  <FileText className="w-4 h-4 text-indigo-600" />
                  <span>New Quotation</span>
                </button>

                <button
                  onClick={() => {
                    setActiveTab('customers');
                    setIsCreatingCustomer(true);
                    setQuickMenuOpen(false);
                  }}
                  className="w-100 text-start px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 border-0 bg-transparent"
                >
                  <UserPlus className="w-4 h-4 text-emerald-600" />
                  <span>Add Customer</span>
                </button>

                <div className="border-t border-slate-100 my-1"></div>

                <button
                  onClick={() => {
                    setPaymentModalInvoiceId(null);
                    setActiveTab('payments');
                    setQuickMenuOpen(false);
                  }}
                  className="w-100 text-start px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 border-0 bg-transparent"
                >
                  <Wallet className="w-4 h-4 text-cyan-600" />
                  <span>Record Payment</span>
                </button>
              </div>
            </>
          )}
        </div>

        {/* User Account Menu */}
        <div className="position-relative">
          <button
            onClick={() => setUserMenuOpen(!userMenuOpen)}
            className="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 py-1 px-2 rounded-lg bg-white border-slate-300 hover:bg-slate-50"
            title="User Profile & Session"
          >
            <div
              className="rounded-full bg-amber-500 text-slate-950 font-bold d-flex align-items-center justify-content-center flex-shrink-0"
              style={{ width: '26px', height: '26px', fontSize: '0.75rem' }}
            >
              {user.name.charAt(0).toUpperCase()}
            </div>
            <span className="text-xs fw-semibold text-slate-700 d-none d-md-inline">
              {user.name}
            </span>
          </button>

          {userMenuOpen && (
            <>
              <div
                className="position-fixed top-0 start-0 w-100 h-100"
                style={{ zIndex: 1040 }}
                onClick={() => setUserMenuOpen(false)}
              />
              <div
                className="position-absolute end-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl py-2 min-w-[220px]"
                style={{ zIndex: 1050 }}
              >
                {/* User Info Header */}
                <div className="px-3 py-2 border-b border-slate-100">
                  <div className="fw-bold text-slate-900 text-xs">{user.name}</div>
                  <div className="text-[11px] text-slate-500 truncate">{user.email}</div>
                  <div className="mt-1 d-flex align-items-center justify-content-between">
                    <span className="badge bg-amber-100 text-amber-800 text-[10px] text-uppercase">
                      {user.role}
                    </span>
                    <span className="text-[10px] text-slate-400">
                      {user.department || 'Operations'}
                    </span>
                  </div>
                </div>

                {/* Menu items */}
                <div className="py-1">
                  <button
                    onClick={() => {
                      setActiveTab('company');
                      setUserMenuOpen(false);
                    }}
                    className="w-100 text-start px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 border-0 bg-transparent"
                  >
                    <Settings className="w-3.5 h-3.5 text-slate-500" />
                    <span>Company & Billing Settings</span>
                  </button>

                  <div className="border-t border-slate-100 my-1"></div>

                  <button
                    onClick={() => {
                      setUserMenuOpen(false);
                      if (window.confirm('Are you sure you want to sign out of PaperGlow Enterprise?')) {
                        logout();
                      }
                    }}
                    className="w-100 text-start px-3 py-2 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 border-0 bg-transparent fw-semibold"
                  >
                    <LogOut className="w-3.5 h-3.5 text-rose-500" />
                    <span>Sign Out</span>
                  </button>
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </header>
  );
};
