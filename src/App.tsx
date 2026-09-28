import React, { useState } from 'react';
import { BillingProvider, useBilling } from './context/BillingContext';
import { Sidebar } from './components/Sidebar';
import { Navbar } from './components/Navbar';
import { DashboardView } from './components/DashboardView';
import { InvoicesView } from './components/InvoicesView';
import { InvoiceDetailView } from './components/InvoiceDetailView';
import { InvoiceFormView } from './components/InvoiceFormView';
import { QuotationsView } from './components/QuotationsView';
import { QuotationDetailView } from './components/QuotationDetailView';
import { QuotationFormView } from './components/QuotationFormView';
import { PaymentsView } from './components/PaymentsView';
import { PaymentModal } from './components/PaymentModal';
import { CustomersView } from './components/CustomersView';
import { CustomerModal } from './components/CustomerModal';
import { ReportsView } from './components/ReportsView';
import { CompanySettingsView } from './components/CompanySettingsView';
import { SchemaView } from './components/SchemaView';
import { LoginView } from './components/LoginView';
import { LogoutScreen } from './components/LogoutScreen';
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-react';

const MainApp: React.FC = () => {
  const [mobileOpen, setMobileOpen] = useState(false);
  const {
    isAuthenticated,
    isLoggedOutScreen,
    setIsLoggedOutScreen,
    lastUserLoggedOut,
    activeTab,
    flash,
    clearFlash,
    viewingInvoiceId,
    editingInvoiceId,
    isCreatingInvoice,
    viewingQuotationId,
    editingQuotationId,
    isCreatingQuotation,
    paymentModalInvoiceId,
    setPaymentModalInvoiceId,
    isCreatingCustomer,
    setIsCreatingCustomer,
  } = useBilling();

  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return <DashboardView />;

      case 'invoices':
        if (viewingInvoiceId !== null) {
          return <InvoiceDetailView invoiceId={viewingInvoiceId} />;
        }
        if (isCreatingInvoice || editingInvoiceId !== null) {
          return <InvoiceFormView invoiceId={editingInvoiceId} />;
        }
        return <InvoicesView />;

      case 'quotations':
        if (viewingQuotationId !== null) {
          return <QuotationDetailView quotationId={viewingQuotationId} />;
        }
        if (isCreatingQuotation || editingQuotationId !== null) {
          return <QuotationFormView quotationId={editingQuotationId} />;
        }
        return <QuotationsView />;

      case 'payments':
        return <PaymentsView />;

      case 'customers':
        return <CustomersView />;

      case 'reports':
        return <ReportsView />;

      case 'company':
        return <CompanySettingsView />;

      case 'schema':
        return <SchemaView />;

      default:
        return <DashboardView />;
    }
  };

  // If user has explicitly logged out, show the Logout Screen
  if (isLoggedOutScreen) {
    return (
      <LogoutScreen
        onReturnToLogin={() => setIsLoggedOutScreen(false)}
        lastUserEmail={lastUserLoggedOut?.email}
        lastUserName={lastUserLoggedOut?.name}
      />
    );
  }

  // If not authenticated, show Login View
  if (!isAuthenticated) {
    return <LoginView />;
  }

  return (
    <div className="pg-layout min-h-screen bg-slate-50">
      {/* Sidebar Navigation */}
      <Sidebar mobileOpen={mobileOpen} setMobileOpen={setMobileOpen} />

      {/* Main App Content Area */}
      <div className="pg-main-wrapper flex flex-col min-w-0 flex-1">
        <Navbar setMobileOpen={setMobileOpen} />

        <main className="pg-content-body flex-1 p-3 sm:p-6 max-w-7xl w-full mx-auto">
          {/* Flash Notification Banner */}
          {flash && (
            <div
              className={`alert alert-${flash.type} alert-dismissible fade show d-flex align-items-center justify-content-between mb-4 shadow-sm rounded-xl p-3 border`}
              role="alert"
            >
              <div className="d-flex align-items-center gap-2">
                {flash.type === 'success' && <CheckCircle2 className="w-5 h-5 text-emerald-600" />}
                {flash.type === 'danger' && <AlertCircle className="w-5 h-5 text-rose-600" />}
                {flash.type === 'info' && <Info className="w-5 h-5 text-cyan-600" />}
                {flash.type === 'warning' && <AlertCircle className="w-5 h-5 text-amber-600" />}
                <span className="text-sm font-medium">{flash.message}</span>
              </div>
              <button
                type="button"
                onClick={clearFlash}
                className="btn btn-sm btn-link p-0 text-slate-500 hover:text-slate-800"
                aria-label="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
          )}

          {renderContent()}
        </main>
      </div>

      {/* Global Payment Modal */}
      {paymentModalInvoiceId !== null && (
        <PaymentModal
          invoiceId={paymentModalInvoiceId > 0 ? paymentModalInvoiceId : null}
          onClose={() => setPaymentModalInvoiceId(null)}
        />
      )}

      {/* Global Customer Modal (when triggered from outside customers view) */}
      {isCreatingCustomer && activeTab !== 'customers' && (
        <CustomerModal
          onClose={() => setIsCreatingCustomer(false)}
        />
      )}
    </div>
  );
};

export default function App() {
  return (
    <BillingProvider>
      <MainApp />
    </BillingProvider>
  );
}
