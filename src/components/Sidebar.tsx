import React from 'react';
import { useBilling, NavigationTab } from '../context/BillingContext';
import {
  LayoutDashboard,
  Receipt,
  FileText,
  Wallet,
  Users,
  BarChart3,
  Settings,
  Database,
  ExternalLink,
  Plus,
  LogOut
} from 'lucide-react';

interface SidebarProps {
  mobileOpen: boolean;
  setMobileOpen: (open: boolean) => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ mobileOpen, setMobileOpen }) => {
  const {
    activeTab,
    setActiveTab,
    company,
    user,
    logout,
    setViewingInvoiceId,
    setEditingInvoiceId,
    setIsCreatingInvoice,
    setViewingQuotationId,
    setEditingQuotationId,
    setIsCreatingQuotation,
    setViewingCustomerId,
    setEditingCustomerId,
    setIsCreatingCustomer
  } = useBilling();

  const handleNav = (tab: NavigationTab) => {
    setActiveTab(tab);
    // Reset sub-views
    setViewingInvoiceId(null);
    setEditingInvoiceId(null);
    setIsCreatingInvoice(false);
    setViewingQuotationId(null);
    setEditingQuotationId(null);
    setIsCreatingQuotation(false);
    setViewingCustomerId(null);
    setEditingCustomerId(null);
    setIsCreatingCustomer(false);
    setMobileOpen(false);
  };

  return (
    <>
      {/* Mobile backdrop */}
      <div
        className={`pg-sidebar-backdrop ${mobileOpen ? 'show' : ''}`}
        onClick={() => setMobileOpen(false)}
      />

      <aside className={`pg-sidebar ${mobileOpen ? 'show' : ''}`}>
        {/* Sidebar Header */}
        <div className="pg-sidebar-header d-flex align-items-center justify-content-between">
          <button
            onClick={() => handleNav('dashboard')}
            className="d-flex align-items-center text-decoration-none gap-2 bg-transparent border-0 text-start p-0 cursor-pointer w-100"
          >
            {company.logo ? (
              <div className="d-flex align-items-center gap-2 py-1">
                <img
                  src={company.logo}
                  alt={company.company_name}
                  referrerPolicy="no-referrer"
                  className="max-h-[46px] max-w-[190px] object-contain rounded bg-white p-1 shadow-xs"
                />
              </div>
            ) : (
              <div className="d-flex align-items-center gap-2">
                <div className="font-extrabold text-base tracking-tight text-white truncate max-w-[180px]" title={company.company_name || 'PaperGlow Enterprise'}>
                  {company.company_name || 'PaperGlow Enterprise'}
                </div>
              </div>
            )}
          </button>
        </div>

        {/* Sidebar Navigation */}
        <nav className="pg-sidebar-nav overflow-y-auto">
          <div className="pg-nav-label">Core Overview</div>
          <button
            onClick={() => handleNav('dashboard')}
            className={`pg-nav-item w-100 text-start border-0 bg-transparent ${
              activeTab === 'dashboard' ? 'active' : ''
            }`}
          >
            <LayoutDashboard className="w-4 h-4" />
            <span>Dashboard</span>
          </button>

          <div className="pg-nav-label">Sales & Documents</div>
          <button
            onClick={() => handleNav('invoices')}
            className={`pg-nav-item w-100 text-start border-0 bg-transparent ${
              activeTab === 'invoices' ? 'active' : ''
            }`}
          >
            <Receipt className="w-4 h-4" />
            <span>Invoices</span>
          </button>
          <button
            onClick={() => handleNav('quotations')}
            className={`pg-nav-item w-100 text-start border-0 bg-transparent ${
              activeTab === 'quotations' ? 'active' : ''
            }`}
          >
            <FileText className="w-4 h-4" />
            <span>Quotations</span>
          </button>
          <button
            onClick={() => handleNav('payments')}
            className={`pg-nav-item w-100 text-start border-0 bg-transparent ${
              activeTab === 'payments' ? 'active' : ''
            }`}
          >
            <Wallet className="w-4 h-4" />
            <span>Payments</span>
          </button>

          <div className="pg-nav-label">Relationships</div>
          <button
            onClick={() => handleNav('customers')}
            className={`pg-nav-item w-100 text-start border-0 bg-transparent ${
              activeTab === 'customers' ? 'active' : ''
            }`}
          >
            <Users className="w-4 h-4" />
            <span>Customers</span>
          </button>

          <div className="pg-nav-label">Intelligence & System</div>
          <button
            onClick={() => handleNav('reports')}
            className={`pg-nav-item w-100 text-start border-0 bg-transparent ${
              activeTab === 'reports' ? 'active' : ''
            }`}
          >
            <BarChart3 className="w-4 h-4" />
            <span>Financial Reports</span>
          </button>
          <button
            onClick={() => handleNav('company')}
            className={`pg-nav-item w-100 text-start border-0 bg-transparent ${
              activeTab === 'company' ? 'active' : ''
            }`}
          >
            <Settings className="w-4 h-4" />
            <span>Company Settings</span>
          </button>
          <button
            onClick={() => handleNav('schema')}
            className={`pg-nav-item w-100 text-start border-0 bg-transparent ${
              activeTab === 'schema' ? 'active' : ''
            }`}
          >
            <Database className="w-4 h-4" />
            <span>MySQL Schema</span>
          </button>
        </nav>

        {/* Sidebar User Footer */}
        <div className="pg-sidebar-footer">
          <div className="d-flex align-items-center justify-content-between text-muted small mb-2">
            <div className="d-flex align-items-center gap-2 overflow-hidden">
              <div
                className="rounded-circle bg-amber-500 text-slate-950 font-bold d-flex align-items-center justify-content-center flex-shrink-0"
                style={{ width: '32px', height: '32px', fontSize: '0.8rem' }}
              >
                {user.name.charAt(0).toUpperCase()}
              </div>
              <div className="text-truncate" style={{ maxWidth: '125px' }}>
                <div className="text-slate-100 fw-semibold text-truncate text-xs">
                  {user.name}
                </div>
                <div className="text-slate-400 text-truncate" style={{ fontSize: '0.7rem' }}>
                  {user.email}
                </div>
              </div>
            </div>
            <button
              type="button"
              onClick={() => {
                if (window.confirm('Are you sure you want to sign out of PaperGlow Enterprise?')) {
                  logout();
                }
              }}
              title="Sign Out"
              className="btn btn-sm btn-outline-danger p-1.5 d-flex align-items-center justify-content-center border-slate-700 hover:border-rose-500 text-slate-300 hover:text-rose-400 rounded-lg"
            >
              <LogOut className="w-3.5 h-3.5" />
            </button>
          </div>

          <div className="d-flex align-items-center justify-content-between pt-1 border-t border-slate-800 text-[10px]">
            <span className="text-slate-400">Signed in as:</span>
            <span className="badge bg-slate-800 text-amber-400 border border-slate-700 text-[10px] text-uppercase">
              {user.role}
            </span>
          </div>
        </div>
      </aside>
    </>
  );
};
