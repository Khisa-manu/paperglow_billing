import React, { useState } from 'react';
import { useBilling } from '../context/BillingContext';
import { PaymentMethod } from '../types';
import {
  Wallet,
  Plus,
  Search,
  Trash2,
  Receipt,
  AlertCircle
} from 'lucide-react';

export const PaymentsView: React.FC = () => {
  const {
    payments,
    invoices,
    formatCurrency,
    getCustomerById,
    setPaymentModalInvoiceId,
    deletePayment,
    setActiveTab,
    setViewingInvoiceId
  } = useBilling();

  const [search, setSearch] = useState('');
  const [methodFilter, setMethodFilter] = useState<string>('All');

  const filteredPayments = payments.filter((p) => {
    const inv = invoices.find((i) => i.id === p.invoice_id);
    const customer = inv ? getCustomerById(inv.customer_id) : null;

    const matchesSearch =
      (inv?.invoice_number.toLowerCase() || '').includes(search.toLowerCase()) ||
      (customer?.name.toLowerCase() || '').includes(search.toLowerCase()) ||
      (p.reference_number?.toLowerCase() || '').includes(search.toLowerCase()) ||
      (p.notes?.toLowerCase() || '').includes(search.toLowerCase());

    const matchesMethod =
      methodFilter === 'All' ? true : p.payment_method === methodFilter;

    return matchesSearch && matchesMethod;
  });

  const totalCollected = payments.reduce((sum, p) => sum + Number(p.amount || 0), 0);

  return (
    <div className="space-y-4">
      {/* Header controls */}
      <div className="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <h2 className="h5 fw-bold text-slate-900 mb-1">Payment Transactions</h2>
          <p className="text-xs text-slate-500 mb-0">
            Audit trail of received customer funds and automatic invoice balance reductions.
          </p>
        </div>

        <div className="d-flex align-items-center gap-3">
          <div className="text-end d-none d-sm-block">
            <span className="text-[11px] text-slate-500 d-block uppercase fw-semibold">
              Total Recorded Revenue
            </span>
            <span className="font-mono fw-black text-emerald-600 text-base">
              {formatCurrency(totalCollected)}
            </span>
          </div>

          <button
            onClick={() => setPaymentModalInvoiceId(null)}
            className="btn btn-pg-primary d-inline-flex align-items-center gap-2"
          >
            <Plus className="w-4 h-4" />
            <span>Record Payment</span>
          </button>
        </div>
      </div>

      {/* Method Filters */}
      <div className="d-flex flex-wrap gap-1 border-b border-slate-200 pb-2">
        {(['All', 'Bank Transfer', 'Mobile Money', 'Card', 'Cash', 'Other'] as const).map((m) => (
          <button
            key={m}
            onClick={() => setMethodFilter(m)}
            className={`btn btn-sm px-3 py-1.5 text-xs rounded-lg font-medium transition-all ${
              methodFilter === m
                ? 'bg-slate-900 text-white shadow-sm'
                : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
            }`}
          >
            <span>{m}</span>
            <span
              className={`ms-1.5 px-1.5 py-0.2 rounded-full text-[10px] font-bold ${
                methodFilter === m ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600'
              }`}
            >
              {m === 'All' ? payments.length : payments.filter((p) => p.payment_method === m).length}
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
            placeholder="Search by reference #, invoice number, or client name..."
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

      {/* Payments Table */}
      <div className="pg-card overflow-hidden">
        <div className="table-responsive">
          <table className="table table-pg mb-0 align-middle">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Client</th>
                <th>Payment Date</th>
                <th>Method</th>
                <th>Reference Number</th>
                <th>Notes</th>
                <th className="text-end">Amount Received</th>
                <th className="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              {filteredPayments.map((p) => {
                const inv = invoices.find((i) => i.id === p.invoice_id);
                const customer = inv ? getCustomerById(inv.customer_id) : null;
                return (
                  <tr key={p.id}>
                    <td>
                      {inv ? (
                        <button
                          onClick={() => {
                            setActiveTab('invoices');
                            setViewingInvoiceId(inv.id);
                          }}
                          className="fw-bold font-mono text-slate-900 text-xs hover:text-amber-600 bg-transparent border-0 p-0 text-start"
                        >
                          {inv.invoice_number}
                        </button>
                      ) : (
                        <span className="text-slate-400 font-mono text-xs">#{p.invoice_id}</span>
                      )}
                    </td>
                    <td>
                      <div className="fw-semibold text-slate-900 text-xs">{customer?.name || 'Customer'}</div>
                      <div className="text-[11px] text-slate-400">{customer?.company}</div>
                    </td>
                    <td className="text-xs text-slate-600">{p.payment_date}</td>
                    <td>
                      <span className="badge bg-slate-100 text-slate-800 border border-slate-200 text-[11px]">
                        {p.payment_method}
                      </span>
                    </td>
                    <td className="text-xs font-mono text-slate-700">{p.reference_number || '-'}</td>
                    <td className="text-xs text-slate-500 max-w-[200px] truncate">{p.notes || '-'}</td>
                    <td className="text-end font-mono fw-bold text-emerald-700 text-xs">
                      +{formatCurrency(p.amount)}
                    </td>
                    <td className="text-end">
                      <button
                        onClick={() => {
                          if (window.confirm(`Delete this payment of ${formatCurrency(p.amount)}? The target invoice balance will be restored.`)) {
                            deletePayment(p.id);
                          }
                        }}
                        className="btn btn-xs btn-outline-danger p-1.5"
                        title="Delete Payment & Revert Balance"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </td>
                  </tr>
                );
              })}

              {filteredPayments.length === 0 && (
                <tr>
                  <td colSpan={8} className="text-center py-5 text-slate-400">
                    <AlertCircle className="w-6 h-6 mx-auto mb-2 text-slate-300" />
                    <p className="text-sm mb-1">No payments match your criteria.</p>
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
