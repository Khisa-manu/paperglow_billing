import React from 'react';
import { useBilling } from '../context/BillingContext';
import {
  Receipt,
  CheckCircle2,
  Clock,
  AlertTriangle,
  FileText,
  Users,
  Wallet,
  ArrowRight,
  TrendingUp,
  Plus
} from 'lucide-react';

export const DashboardView: React.FC = () => {
  const {
    invoices,
    quotations,
    customers,
    payments,
    formatCurrency,
    setActiveTab,
    setViewingInvoiceId,
    setViewingQuotationId,
    setIsCreatingInvoice,
    setIsCreatingQuotation,
    setIsCreatingCustomer,
    setPaymentModalInvoiceId,
    getCustomerById
  } = useBilling();

  // Financial KPI calculations
  const totalInvoices = invoices.length;
  const totalInvoicedValue = invoices.reduce((sum, i) => sum + Number(i.grand_total || 0), 0);
  const totalPaid = invoices.reduce((sum, i) => sum + Number(i.paid_amount || 0), 0);
  const totalOutstanding = invoices.reduce((sum, i) => sum + Number(i.balance || 0), 0);

  const countPaid = invoices.filter((i) => i.status === 'Paid').length;
  const countUnpaid = invoices.filter((i) => i.status === 'Unpaid').length;
  const countPartial = invoices.filter((i) => i.status === 'Partially Paid').length;
  const countOverdue = invoices.filter((i) => i.status === 'Overdue').length;

  const totalQuotationsValue = quotations.reduce((sum, q) => sum + Number(q.grand_total || 0), 0);
  const acceptedQuotations = quotations.filter((q) => q.status === 'Accepted').length;

  const recentInvoices = [...invoices].sort(
    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  ).slice(0, 5);

  const recentQuotations = [...quotations].sort(
    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  ).slice(0, 5);

  const recentPayments = [...payments].sort(
    (a, b) => new Date(b.payment_date).getTime() - new Date(a.payment_date).getTime()
  ).slice(0, 5);

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'Paid':
        return <span className="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Paid</span>;
      case 'Partially Paid':
        return <span className="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Partially Paid</span>;
      case 'Overdue':
        return <span className="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Overdue</span>;
      case 'Unpaid':
        return <span className="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Unpaid</span>;
      case 'Accepted':
        return <span className="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Accepted</span>;
      case 'Sent':
        return <span className="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Sent</span>;
      case 'Draft':
        return <span className="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Draft</span>;
      default:
        return <span className="badge bg-light text-dark px-2 py-1">{status}</span>;
    }
  };

  return (
    <div className="space-y-6">
      {/* Overdue Alert banner if any */}
      {countOverdue > 0 && (
        <div className="alert alert-warning border border-amber-300 bg-amber-50 text-amber-900 d-flex align-items-center justify-content-between p-3 rounded-xl mb-4">
          <div className="d-flex align-items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-amber-200 text-amber-900 d-flex align-items-center justify-content-center flex-shrink-0">
              <AlertTriangle className="w-5 h-5 text-amber-700" />
            </div>
            <div>
              <div className="fw-bold text-sm">
                Attention Required: {countOverdue} Overdue Invoice{countOverdue > 1 ? 's' : ''}
              </div>
              <div className="text-xs text-amber-800">
                You have overdue invoices pending settlement. Review and send reminders to maintain cash flow.
              </div>
            </div>
          </div>
          <button
            onClick={() => setActiveTab('invoices')}
            className="btn btn-sm btn-outline-warning text-amber-900 border-amber-400 bg-white hover:bg-amber-100 fw-semibold text-xs"
          >
            Review Invoices
          </button>
        </div>
      )}

      {/* KPI Stats Cards */}
      <div className="row g-3">
        <div className="col-xl-3 col-sm-6">
          <div className="pg-stat-card">
            <div className="d-flex align-items-center justify-content-between mb-2">
              <span className="text-muted small fw-semibold text-uppercase letter-spacing text-xs">Total Invoices</span>
              <div className="pg-stat-icon" style={{ background: 'rgba(15, 23, 42, 0.06)', color: '#0f172a' }}>
                <Receipt className="w-5 h-5" />
              </div>
            </div>
            <div className="h3 fw-bold mb-1 text-slate-900">{totalInvoices}</div>
            <div className="small text-muted d-flex align-items-center justify-content-between">
              <span>Billed Value:</span>
              <span className="fw-semibold text-slate-800 mono-num">{formatCurrency(totalInvoicedValue)}</span>
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-sm-6">
          <div className="pg-stat-card">
            <div className="d-flex align-items-center justify-content-between mb-2">
              <span className="text-muted small fw-semibold text-uppercase letter-spacing text-xs">Total Collected</span>
              <div className="pg-stat-icon" style={{ background: 'rgba(16, 185, 129, 0.12)', color: '#10b981' }}>
                <CheckCircle2 className="w-5 h-5" />
              </div>
            </div>
            <div className="h3 fw-bold mb-1 text-emerald-600 mono-num">{formatCurrency(totalPaid)}</div>
            <div className="small text-muted d-flex align-items-center justify-content-between">
              <span>Fully Paid Invoices:</span>
              <span className="fw-semibold text-emerald-700">{countPaid}</span>
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-sm-6">
          <div className="pg-stat-card">
            <div className="d-flex align-items-center justify-content-between mb-2">
              <span className="text-muted small fw-semibold text-uppercase letter-spacing text-xs">Outstanding Balance</span>
              <div className="pg-stat-icon" style={{ background: 'rgba(239, 68, 68, 0.12)', color: '#ef4444' }}>
                <Clock className="w-5 h-5" />
              </div>
            </div>
            <div className="h3 fw-bold mb-1 text-rose-600 mono-num">{formatCurrency(totalOutstanding)}</div>
            <div className="small text-muted d-flex align-items-center justify-content-between">
              <span>Pending Invoices:</span>
              <span className="fw-semibold text-slate-800">{countUnpaid + countPartial + countOverdue}</span>
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-sm-6">
          <div className="pg-stat-card">
            <div className="d-flex align-items-center justify-content-between mb-2">
              <span className="text-muted small fw-semibold text-uppercase letter-spacing text-xs">Active Quotations</span>
              <div className="pg-stat-icon" style={{ background: 'rgba(245, 158, 11, 0.12)', color: '#f59e0b' }}>
                <FileText className="w-5 h-5" />
              </div>
            </div>
            <div className="h3 fw-bold mb-1 text-amber-600 mono-num">{quotations.length}</div>
            <div className="small text-muted d-flex align-items-center justify-content-between">
              <span>Pipeline Value:</span>
              <span className="fw-semibold text-slate-800 mono-num">{formatCurrency(totalQuotationsValue)}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Settlement Ratio & Quick Action Bar */}
      <div className="row g-3">
        <div className="col-lg-8">
          <div className="pg-card p-4">
            <div className="d-flex align-items-center justify-content-between mb-3">
              <h2 className="h6 fw-bold mb-0 text-slate-900 d-flex align-items-center gap-2">
                <TrendingUp className="w-4 h-4 text-amber-500" />
                Collection & Settlement Progress
              </h2>
              <span className="text-xs text-muted">
                {totalInvoicedValue > 0
                  ? `${Math.round((totalPaid / totalInvoicedValue) * 100)}% Collected`
                  : '0% Collected'}
              </span>
            </div>

            {/* Progress Bar */}
            <div className="w-100 bg-slate-100 rounded-full h-3 mb-4 overflow-hidden d-flex">
              <div
                className="bg-emerald-500 h-100 transition-all duration-500"
                style={{
                  width: `${totalInvoicedValue > 0 ? (totalPaid / totalInvoicedValue) * 100 : 0}%`,
                }}
                title={`Collected: ${formatCurrency(totalPaid)}`}
              />
              <div
                className="bg-rose-400 h-100 transition-all duration-500"
                style={{
                  width: `${totalInvoicedValue > 0 ? (totalOutstanding / totalInvoicedValue) * 100 : 0}%`,
                }}
                title={`Outstanding: ${formatCurrency(totalOutstanding)}`}
              />
            </div>

            {/* Invoices Status Breakdown Pills */}
            <div className="row g-2 text-center">
              <div className="col-3">
                <div className="p-2.5 bg-emerald-50 border border-emerald-100 rounded-lg">
                  <div className="text-xs text-emerald-800 font-semibold uppercase">Paid</div>
                  <div className="text-lg font-bold text-emerald-700">{countPaid}</div>
                </div>
              </div>
              <div className="col-3">
                <div className="p-2.5 bg-amber-50 border border-amber-100 rounded-lg">
                  <div className="text-xs text-amber-800 font-semibold uppercase">Partial</div>
                  <div className="text-lg font-bold text-amber-700">{countPartial}</div>
                </div>
              </div>
              <div className="col-3">
                <div className="p-2.5 bg-yellow-50 border border-yellow-200 rounded-lg">
                  <div className="text-xs text-yellow-800 font-semibold uppercase">Unpaid</div>
                  <div className="text-lg font-bold text-yellow-700">{countUnpaid}</div>
                </div>
              </div>
              <div className="col-3">
                <div className="p-2.5 bg-rose-50 border border-rose-100 rounded-lg">
                  <div className="text-xs text-rose-800 font-semibold uppercase">Overdue</div>
                  <div className="text-lg font-bold text-rose-700">{countOverdue}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Quick Launchpad */}
        <div className="col-lg-4">
          <div className="pg-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
              <h2 className="h6 fw-bold mb-3 text-slate-900">Billing Launchpad</h2>
              <div className="space-y-2">
                <button
                  onClick={() => {
                    setActiveTab('invoices');
                    setIsCreatingInvoice(true);
                  }}
                  className="w-100 btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-between p-2 text-slate-700 hover:text-slate-900 border-slate-200 hover:border-slate-300"
                >
                  <span className="d-flex align-items-center gap-2">
                    <Receipt className="w-4 h-4 text-amber-600" />
                    <span className="font-medium text-xs">Issue New Invoice</span>
                  </span>
                  <Plus className="w-4 h-4 text-slate-400" />
                </button>

                <button
                  onClick={() => {
                    setActiveTab('quotations');
                    setIsCreatingQuotation(true);
                  }}
                  className="w-100 btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-between p-2 text-slate-700 hover:text-slate-900 border-slate-200 hover:border-slate-300"
                >
                  <span className="d-flex align-items-center gap-2">
                    <FileText className="w-4 h-4 text-indigo-600" />
                    <span className="font-medium text-xs">Create Quotation</span>
                  </span>
                  <Plus className="w-4 h-4 text-slate-400" />
                </button>

                <button
                  onClick={() => {
                    setActiveTab('customers');
                    setIsCreatingCustomer(true);
                  }}
                  className="w-100 btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-between p-2 text-slate-700 hover:text-slate-900 border-slate-200 hover:border-slate-300"
                >
                  <span className="d-flex align-items-center gap-2">
                    <Users className="w-4 h-4 text-emerald-600" />
                    <span className="font-medium text-xs">Add Customer</span>
                  </span>
                  <Plus className="w-4 h-4 text-slate-400" />
                </button>

                <button
                  onClick={() => {
                    setActiveTab('payments');
                    setPaymentModalInvoiceId(null);
                  }}
                  className="w-100 btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-between p-2 text-slate-700 hover:text-slate-900 border-slate-200 hover:border-slate-300"
                >
                  <span className="d-flex align-items-center gap-2">
                    <Wallet className="w-4 h-4 text-cyan-600" />
                    <span className="font-medium text-xs">Record Received Payment</span>
                  </span>
                  <Plus className="w-4 h-4 text-slate-400" />
                </button>
              </div>
            </div>

            <div className="pt-3 border-t border-slate-100 d-flex align-items-center justify-content-between text-xs text-slate-500">
              <span>Total Customers: <strong>{customers.length}</strong></span>
              <button
                onClick={() => setActiveTab('customers')}
                className="text-amber-600 hover:text-amber-700 font-semibold p-0 bg-transparent border-0"
              >
                Directory &rarr;
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Recent Invoices Table */}
      <div className="pg-card">
        <div className="pg-card-header">
          <div className="d-flex align-items-center gap-2">
            <Receipt className="w-4 h-4 text-amber-500" />
            <h2 className="h6 fw-bold mb-0 text-slate-900">Recent Invoices</h2>
          </div>
          <button
            onClick={() => setActiveTab('invoices')}
            className="text-xs text-amber-600 hover:text-amber-700 font-semibold d-flex align-items-center gap-1 bg-transparent border-0"
          >
            <span>View All ({invoices.length})</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        </div>
        <div className="table-responsive">
          <table className="table table-pg mb-0">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Due Date</th>
                <th className="text-end">Total</th>
                <th className="text-end">Balance</th>
                <th>Status</th>
                <th className="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              {recentInvoices.map((inv) => {
                const customer = getCustomerById(inv.customer_id);
                return (
                  <tr key={inv.id}>
                    <td className="fw-bold font-mono text-slate-900 text-xs">
                      {inv.invoice_number}
                    </td>
                    <td>
                      <div className="fw-semibold text-slate-800 text-xs">{customer?.name || 'Customer'}</div>
                      <div className="text-slate-400 text-[11px]">{customer?.company}</div>
                    </td>
                    <td className="text-slate-600 text-xs">{inv.invoice_date}</td>
                    <td className="text-slate-600 text-xs">{inv.due_date}</td>
                    <td className="text-end fw-bold font-mono text-slate-900 text-xs">
                      {formatCurrency(inv.grand_total)}
                    </td>
                    <td className="text-end font-mono text-xs">
                      {inv.balance > 0 ? (
                        <span className="text-rose-600 fw-bold">{formatCurrency(inv.balance)}</span>
                      ) : (
                        <span className="text-emerald-600 fw-semibold">{formatCurrency(0)}</span>
                      )}
                    </td>
                    <td>{getStatusBadge(inv.status)}</td>
                    <td className="text-center">
                      <button
                        onClick={() => {
                          setActiveTab('invoices');
                          setViewingInvoiceId(inv.id);
                        }}
                        className="btn btn-xs btn-outline-secondary px-2 py-1 text-xs"
                      >
                        View
                      </button>
                    </td>
                  </tr>
                );
              })}
              {recentInvoices.length === 0 && (
                <tr>
                  <td colSpan={8} className="text-center py-4 text-slate-400 text-xs">
                    No invoices issued yet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Recent Quotations & Payments 2-Column Grid */}
      <div className="row g-3">
        {/* Recent Quotations */}
        <div className="col-lg-6">
          <div className="pg-card h-100">
            <div className="pg-card-header">
              <div className="d-flex align-items-center gap-2">
                <FileText className="w-4 h-4 text-indigo-500" />
                <h2 className="h6 fw-bold mb-0 text-slate-900">Recent Quotations</h2>
              </div>
              <button
                onClick={() => setActiveTab('quotations')}
                className="text-xs text-amber-600 hover:text-amber-700 font-semibold bg-transparent border-0"
              >
                All ({quotations.length})
              </button>
            </div>
            <div className="table-responsive">
              <table className="table table-pg mb-0">
                <thead>
                  <tr>
                    <th>Quotation #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th className="text-end">Total</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {recentQuotations.map((q) => {
                    const cust = getCustomerById(q.customer_id);
                    return (
                      <tr
                        key={q.id}
                        className="cursor-pointer"
                        onClick={() => {
                          setActiveTab('quotations');
                          setViewingQuotationId(q.id);
                        }}
                      >
                        <td className="fw-bold font-mono text-slate-900 text-xs">
                          {q.quotation_number}
                        </td>
                        <td className="text-xs text-slate-800">{cust?.name}</td>
                        <td className="text-xs text-slate-500">{q.quotation_date}</td>
                        <td className="text-end fw-bold font-mono text-slate-900 text-xs">
                          {formatCurrency(q.grand_total)}
                        </td>
                        <td>{getStatusBadge(q.status)}</td>
                      </tr>
                    );
                  })}
                  {recentQuotations.length === 0 && (
                    <tr>
                      <td colSpan={5} className="text-center py-4 text-slate-400 text-xs">
                        No quotations created yet.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Recent Payments */}
        <div className="col-lg-6">
          <div className="pg-card h-100">
            <div className="pg-card-header">
              <div className="d-flex align-items-center gap-2">
                <Wallet className="w-4 h-4 text-emerald-500" />
                <h2 className="h6 fw-bold mb-0 text-slate-900">Recent Payment Collections</h2>
              </div>
              <button
                onClick={() => setActiveTab('payments')}
                className="text-xs text-amber-600 hover:text-amber-700 font-semibold bg-transparent border-0"
              >
                All ({payments.length})
              </button>
            </div>
            <div className="table-responsive">
              <table className="table table-pg mb-0">
                <thead>
                  <tr>
                    <th>Invoice #</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Ref #</th>
                    <th className="text-end">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  {recentPayments.map((p) => {
                    const inv = invoices.find((i) => i.id === p.invoice_id);
                    return (
                      <tr key={p.id}>
                        <td className="fw-bold font-mono text-slate-900 text-xs">
                          {inv?.invoice_number || `#${p.invoice_id}`}
                        </td>
                        <td className="text-xs">
                          <span className="badge bg-slate-100 text-slate-700 border border-slate-200">
                            {p.payment_method}
                          </span>
                        </td>
                        <td className="text-xs text-slate-500">{p.payment_date}</td>
                        <td className="text-xs font-mono text-slate-500">{p.reference_number || '-'}</td>
                        <td className="text-end fw-bold font-mono text-emerald-600 text-xs">
                          +{formatCurrency(p.amount)}
                        </td>
                      </tr>
                    );
                  })}
                  {recentPayments.length === 0 && (
                    <tr>
                      <td colSpan={5} className="text-center py-4 text-muted text-xs">
                        No payments recorded yet.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
