import React, { useState } from 'react';
import { useBilling } from '../context/BillingContext';
import { InvoiceStatus } from '../types';
import {
  Receipt,
  Plus,
  Search,
  Filter,
  Eye,
  Edit2,
  Trash2,
  Wallet,
  Calendar,
  AlertCircle
} from 'lucide-react';

export const InvoicesView: React.FC = () => {
  const {
    invoices,
    formatCurrency,
    getCustomerById,
    setViewingInvoiceId,
    setEditingInvoiceId,
    setIsCreatingInvoice,
    deleteInvoice,
    setPaymentModalInvoiceId
  } = useBilling();

  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('All');

  const filteredInvoices = invoices.filter((inv) => {
    const customer = getCustomerById(inv.customer_id);
    const matchesSearch =
      inv.invoice_number.toLowerCase().includes(search.toLowerCase()) ||
      customer?.name.toLowerCase().includes(search.toLowerCase()) ||
      customer?.company?.toLowerCase().includes(search.toLowerCase());

    const matchesStatus =
      statusFilter === 'All' ? true : inv.status === statusFilter;

    return matchesSearch && matchesStatus;
  });

  const getStatusBadge = (status: InvoiceStatus) => {
    switch (status) {
      case 'Paid':
        return <span className="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Paid</span>;
      case 'Partially Paid':
        return <span className="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Partially Paid</span>;
      case 'Overdue':
        return <span className="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Overdue</span>;
      case 'Unpaid':
        return <span className="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Unpaid</span>;
      case 'Draft':
        return <span className="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Draft</span>;
      case 'Cancelled':
        return <span className="badge bg-dark-subtle text-muted border px-2 py-1">Cancelled</span>;
      default:
        return <span className="badge bg-light text-dark px-2 py-1">{status}</span>;
    }
  };

  const statusCounts = {
    All: invoices.length,
    Unpaid: invoices.filter((i) => i.status === 'Unpaid').length,
    'Partially Paid': invoices.filter((i) => i.status === 'Partially Paid').length,
    Paid: invoices.filter((i) => i.status === 'Paid').length,
    Overdue: invoices.filter((i) => i.status === 'Overdue').length,
    Draft: invoices.filter((i) => i.status === 'Draft').length,
  };

  return (
    <div className="space-y-4">
      {/* Header controls */}
      <div className="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <h2 className="h5 fw-bold text-slate-900 mb-1">Invoices Directory</h2>
          <p className="text-xs text-slate-500 mb-0">
            Create, track, and reconcile sales invoices with real-time payment status.
          </p>
        </div>

        <button
          onClick={() => {
            setIsCreatingInvoice(true);
          }}
          className="btn btn-pg-primary d-inline-flex align-items-center gap-2 self-start"
        >
          <Plus className="w-4 h-4" />
          <span>New Invoice</span>
        </button>
      </div>

      {/* Filter Tabs */}
      <div className="d-flex flex-wrap gap-1 border-b border-slate-200 pb-2">
        {(['All', 'Unpaid', 'Partially Paid', 'Paid', 'Overdue', 'Draft'] as const).map((st) => (
          <button
            key={st}
            onClick={() => setStatusFilter(st)}
            className={`btn btn-sm px-3 py-1.5 text-xs rounded-lg font-medium transition-all ${
              statusFilter === st
                ? 'bg-slate-900 text-white shadow-sm'
                : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
            }`}
          >
            <span>{st}</span>
            <span
              className={`ms-1.5 px-1.5 py-0.2 rounded-full text-[10px] font-bold ${
                statusFilter === st ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600'
              }`}
            >
              {statusCounts[st] || 0}
            </span>
          </button>
        ))}
      </div>

      {/* Search Bar */}
      <div className="pg-card p-3">
        <div className="d-flex align-items-center gap-2">
          <Search className="w-4 h-4 text-slate-400" />
          <input
            type="text"
            className="form-control form-control-sm border-0 shadow-none text-xs"
            placeholder="Search by invoice number (#INV-...), client name, or company..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          {search && (
            <button
              onClick={() => setSearch('')}
              className="btn btn-xs text-slate-400 hover:text-slate-600 border-0 bg-transparent text-xs"
            >
              Clear
            </button>
          )}
        </div>
      </div>

      {/* Invoices Table */}
      <div className="pg-card overflow-hidden">
        <div className="table-responsive">
          <table className="table table-pg mb-0 align-middle">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Client / Organization</th>
                <th>Date Issued</th>
                <th>Due Date</th>
                <th className="text-end">Grand Total</th>
                <th className="text-end">Balance Due</th>
                <th>Status</th>
                <th className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredInvoices.map((inv) => {
                const customer = getCustomerById(inv.customer_id);
                return (
                  <tr key={inv.id}>
                    <td>
                      <button
                        onClick={() => setViewingInvoiceId(inv.id)}
                        className="fw-bold font-mono text-slate-900 text-xs hover:text-amber-600 bg-transparent border-0 p-0 text-start"
                      >
                        {inv.invoice_number}
                      </button>
                      {inv.from_quotation_id && (
                        <div className="text-[10px] text-slate-400">From Quotation</div>
                      )}
                    </td>
                    <td>
                      <div className="fw-semibold text-slate-900 text-xs">{customer?.name || 'Unknown'}</div>
                      <div className="text-[11px] text-slate-500">{customer?.company}</div>
                    </td>
                    <td className="text-xs text-slate-600">{inv.invoice_date}</td>
                    <td className="text-xs text-slate-600">
                      <span className={inv.status === 'Overdue' ? 'text-danger fw-bold' : ''}>
                        {inv.due_date}
                      </span>
                    </td>
                    <td className="text-end font-mono fw-bold text-slate-900 text-xs">
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
                    <td className="text-end">
                      <div className="d-flex align-items-center justify-content-end gap-1">
                        <button
                          onClick={() => setViewingInvoiceId(inv.id)}
                          className="btn btn-xs btn-outline-secondary p-1.5"
                          title="View Invoice"
                        >
                          <Eye className="w-3.5 h-3.5 text-slate-600" />
                        </button>

                        {inv.balance > 0 && (
                          <button
                            onClick={() => setPaymentModalInvoiceId(inv.id)}
                            className="btn btn-xs btn-outline-success p-1.5"
                            title="Record Payment"
                          >
                            <Wallet className="w-3.5 h-3.5" />
                          </button>
                        )}

                        <button
                          onClick={() => setEditingInvoiceId(inv.id)}
                          className="btn btn-xs btn-outline-secondary p-1.5"
                          title="Edit Invoice"
                        >
                          <Edit2 className="w-3.5 h-3.5 text-slate-600" />
                        </button>

                        <button
                          onClick={() => {
                            if (window.confirm(`Delete invoice ${inv.invoice_number}?`)) {
                              deleteInvoice(inv.id);
                            }
                          }}
                          className="btn btn-xs btn-outline-danger p-1.5"
                          title="Delete Invoice"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}

              {filteredInvoices.length === 0 && (
                <tr>
                  <td colSpan={8} className="text-center py-5 text-slate-400">
                    <AlertCircle className="w-6 h-6 mx-auto mb-2 text-slate-300" />
                    <p className="text-sm mb-1">No invoices found matching criteria.</p>
                    <button
                      onClick={() => {
                        setSearch('');
                        setStatusFilter('All');
                      }}
                      className="btn btn-sm btn-link text-xs text-amber-600"
                    >
                      Reset filters
                    </button>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
