import React from 'react';
import { useBilling } from '../context/BillingContext';
import {
  ArrowLeft,
  Printer,
  Edit2,
  Wallet,
  Building,
  CreditCard,
  Phone,
  Mail,
  CheckCircle,
  FileCheck
} from 'lucide-react';

interface InvoiceDetailViewProps {
  invoiceId: number;
}

export const InvoiceDetailView: React.FC<InvoiceDetailViewProps> = ({ invoiceId }) => {
  const {
    getInvoiceById,
    getCustomerById,
    company,
    payments,
    formatCurrency,
    setViewingInvoiceId,
    setEditingInvoiceId,
    setPaymentModalInvoiceId
  } = useBilling();

  const invoice = getInvoiceById(invoiceId);
  if (!invoice) {
    return (
      <div className="p-4 text-center">
        <p className="text-muted">Invoice not found.</p>
        <button
          onClick={() => setViewingInvoiceId(null)}
          className="btn btn-sm btn-outline-secondary"
        >
          Back to List
        </button>
      </div>
    );
  }

  const customer = getCustomerById(invoice.customer_id);
  const invoicePayments = payments.filter((p) => p.invoice_id === invoice.id);

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'Paid':
        return <span className="badge bg-success px-3 py-1.5 fs-6">Paid in Full</span>;
      case 'Partially Paid':
        return <span className="badge bg-info text-dark px-3 py-1.5 fs-6">Partially Paid</span>;
      case 'Overdue':
        return <span className="badge bg-danger px-3 py-1.5 fs-6">Overdue</span>;
      case 'Unpaid':
        return <span className="badge bg-warning text-dark px-3 py-1.5 fs-6">Unpaid</span>;
      case 'Draft':
        return <span className="badge bg-secondary px-3 py-1.5 fs-6">Draft</span>;
      default:
        return <span className="badge bg-light text-dark px-3 py-1.5 fs-6">{status}</span>;
    }
  };

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="space-y-4">
      {/* Top Action Bar (hidden during print) */}
      <div className="d-flex align-items-center justify-content-between no-print mb-2">
        <button
          onClick={() => setViewingInvoiceId(null)}
          className="btn btn-sm btn-pg-secondary d-flex align-items-center gap-1.5"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Back to Invoices</span>
        </button>

        <div className="d-flex align-items-center gap-2">
          {invoice.balance > 0 && (
            <button
              onClick={() => setPaymentModalInvoiceId(invoice.id)}
              className="btn btn-sm btn-success d-flex align-items-center gap-1.5"
            >
              <Wallet className="w-4 h-4" />
              <span>Record Payment</span>
            </button>
          )}

          <button
            onClick={() => {
              setViewingInvoiceId(null);
              setEditingInvoiceId(invoice.id);
            }}
            className="btn btn-sm btn-pg-secondary d-flex align-items-center gap-1.5"
          >
            <Edit2 className="w-4 h-4" />
            <span>Edit</span>
          </button>

          <button
            onClick={handlePrint}
            className="btn btn-sm btn-pg-dark d-flex align-items-center gap-1.5"
          >
            <Printer className="w-4 h-4" />
            <span>Print / PDF</span>
          </button>
        </div>
      </div>

      {/* Official Paper Document View */}
      <div className="pg-document-paper bg-white">
        {/* Document Header */}
        <div className="pg-doc-header d-flex flex-column flex-sm-row justify-content-between gap-4">
          <div className="d-flex flex-column gap-2">
            <div className="d-flex align-items-center gap-2">
              {company.logo ? (
                <img
                  src={company.logo}
                  alt={company.company_name}
                  style={{ maxHeight: '48px', maxWidth: '180px', objectFit: 'contain' }}
                  onError={(e) => {
                    (e.target as HTMLElement).style.display = 'none';
                  }}
                />
              ) : null}
              <div className="pg-brand-emblem" style={{ width: '34px', height: '34px', fontSize: '1rem' }}>
                P
              </div>
              <span className="h4 fw-extrabold text-slate-900 tracking-tight mb-0">
                {company.company_name}
              </span>
            </div>
            <div className="text-xs text-slate-600 whitespace-pre-line leading-relaxed">
              {company.address}
            </div>
            <div className="text-xs text-slate-600 d-flex flex-wrap gap-x-3">
              {company.phone && <span>Tel: {company.phone}</span>}
              {company.email && <span>Email: {company.email}</span>}
              {company.tax_number && <span>Tax PIN: <strong>{company.tax_number}</strong></span>}
            </div>
          </div>

          <div className="text-sm-end">
            <div className="h3 fw-black text-slate-900 tracking-tight text-uppercase mb-1">
              Invoice
            </div>
            <div className="font-mono fw-bold text-slate-700 text-sm mb-2">
              {invoice.invoice_number}
            </div>
            <div>{getStatusBadge(invoice.status)}</div>
          </div>
        </div>

        {/* Invoice Meta Banner */}
        <div className="pg-doc-number-banner mb-4">
          <div className="row g-3">
            <div className="col-sm-4">
              <span className="text-xs text-slate-500 text-uppercase fw-semibold d-block">
                Invoice Date
              </span>
              <strong className="text-sm text-slate-800 font-mono">{invoice.invoice_date}</strong>
            </div>
            <div className="col-sm-4">
              <span className="text-xs text-slate-500 text-uppercase fw-semibold d-block">
                Payment Due Date
              </span>
              <strong className={`text-sm font-mono ${invoice.status === 'Overdue' ? 'text-danger fw-bold' : 'text-slate-800'}`}>
                {invoice.due_date}
              </strong>
            </div>
            <div className="col-sm-4 text-sm-end">
              <span className="text-xs text-slate-500 text-uppercase fw-semibold d-block">
                Balance Due
              </span>
              <strong className="text-base text-rose-600 font-mono fw-bold">
                {formatCurrency(invoice.balance)}
              </strong>
            </div>
          </div>
        </div>

        {/* Bill To Customer Card */}
        <div className="row mb-4">
          <div className="col-sm-7">
            <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg">
              <div className="text-[11px] font-bold text-slate-400 text-uppercase mb-1">
                Billed To
              </div>
              <div className="fw-bold text-slate-900 text-sm">{customer?.name}</div>
              {customer?.company && (
                <div className="text-xs fw-semibold text-slate-700">{customer.company}</div>
              )}
              {customer?.address && (
                <div className="text-xs text-slate-600 mt-1 whitespace-pre-line">
                  {customer.address}
                </div>
              )}
              <div className="text-xs text-slate-500 mt-1">
                {customer?.email && <div>{customer.email}</div>}
                {customer?.phone && <div>{customer.phone}</div>}
                {customer?.tax_number && <div>Tax ID: {customer.tax_number}</div>}
              </div>
            </div>
          </div>
        </div>

        {/* Line Items Table */}
        <div className="table-responsive mb-4">
          <table className="table table-pg table-bordered mb-0">
            <thead>
              <tr>
                <th style={{ width: '45%' }}>Item Description</th>
                <th className="text-center" style={{ width: '10%' }}>Qty</th>
                <th className="text-end" style={{ width: '15%' }}>Unit Price</th>
                <th className="text-end" style={{ width: '10%' }}>Disc.</th>
                <th className="text-end" style={{ width: '10%' }}>Tax</th>
                <th className="text-end" style={{ width: '15%' }}>Total</th>
              </tr>
            </thead>
            <tbody>
              {invoice.items.map((item, idx) => (
                <tr key={item.id || idx}>
                  <td>
                    <div className="fw-semibold text-slate-900 text-xs">{item.description}</div>
                  </td>
                  <td className="text-center font-mono text-xs">{item.quantity}</td>
                  <td className="text-end font-mono text-xs">{formatCurrency(item.unit_price)}</td>
                  <td className="text-end font-mono text-xs">
                    {item.discount > 0 ? `-${formatCurrency(item.discount)}` : '-'}
                  </td>
                  <td className="text-end font-mono text-xs">
                    {item.tax_rate > 0 ? `${item.tax_rate}%` : '0%'}
                  </td>
                  <td className="text-end font-mono fw-bold text-slate-900 text-xs">
                    {formatCurrency(item.line_total)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Financial Summary Box */}
        <div className="row justify-content-end mb-4">
          <div className="col-sm-6">
            <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-2 text-xs font-mono">
              <div className="d-flex justify-content-between text-slate-600">
                <span>Subtotal</span>
                <span>{formatCurrency(invoice.subtotal)}</span>
              </div>
              {invoice.discount_total > 0 && (
                <div className="d-flex justify-content-between text-emerald-700">
                  <span>Total Discount</span>
                  <span>-{formatCurrency(invoice.discount_total)}</span>
                </div>
              )}
              {invoice.tax_total > 0 && (
                <div className="d-flex justify-content-between text-slate-600">
                  <span>Sales Tax Total</span>
                  <span>+{formatCurrency(invoice.tax_total)}</span>
                </div>
              )}
              <div className="border-t border-slate-300 pt-2 d-flex justify-content-between text-slate-900 fw-bold fs-6">
                <span>Grand Total</span>
                <span className="text-slate-950">{formatCurrency(invoice.grand_total)}</span>
              </div>
              <div className="d-flex justify-content-between text-emerald-600 fw-semibold">
                <span>Total Paid</span>
                <span>{formatCurrency(invoice.paid_amount)}</span>
              </div>
              <div className="border-t border-dashed border-slate-300 pt-2 d-flex justify-content-between text-rose-700 fw-bold fs-6">
                <span>Balance Due</span>
                <span>{formatCurrency(invoice.balance)}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Payment History if any */}
        {invoicePayments.length > 0 && (
          <div className="mb-4">
            <h3 className="h6 fw-bold text-slate-900 mb-2 d-flex align-items-center gap-1.5">
              <CheckCircle className="w-4 h-4 text-emerald-600" />
              Recorded Payments
            </h3>
            <div className="table-responsive">
              <table className="table table-sm table-bordered text-xs mb-0">
                <thead className="table-light">
                  <tr>
                    <th>Payment Date</th>
                    <th>Payment Method</th>
                    <th>Reference / Transaction ID</th>
                    <th>Notes</th>
                    <th className="text-end">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  {invoicePayments.map((p) => (
                    <tr key={p.id}>
                      <td className="font-mono">{p.payment_date}</td>
                      <td>
                        <span className="badge bg-slate-100 text-slate-800 border border-slate-200">
                          {p.payment_method}
                        </span>
                      </td>
                      <td className="font-mono text-slate-600">{p.reference_number || '-'}</td>
                      <td className="text-slate-500">{p.notes || '-'}</td>
                      <td className="text-end font-mono fw-bold text-emerald-700">
                        {formatCurrency(p.amount)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Remittance Information & Terms */}
        <div className="row g-3 pt-3 border-t border-slate-200">
          <div className="col-sm-6">
            <div className="p-3 bg-amber-50/50 border border-amber-200/60 rounded-lg text-xs space-y-1">
              <div className="font-bold text-slate-900 text-[11px] text-uppercase mb-1 d-flex align-items-center gap-1">
                <CreditCard className="w-3.5 h-3.5 text-amber-600" />
                Payment & Remittance Instructions
              </div>
              {company.bank_name && (
                <div>
                  <span className="text-slate-500">Bank:</span> <strong>{company.bank_name}</strong>
                </div>
              )}
              {company.bank_account_number && (
                <div>
                  <span className="text-slate-500">Account:</span> <strong className="font-mono">{company.bank_account_number}</strong> ({company.bank_account_name})
                </div>
              )}
              {company.bank_swift && (
                <div>
                  <span className="text-slate-500">SWIFT/BIC:</span> <strong className="font-mono">{company.bank_swift}</strong>
                </div>
              )}
              {company.mobile_money_number && (
                <div className="pt-1 border-t border-amber-200/50 mt-1">
                  <span className="text-slate-500">{company.mobile_money_name}:</span> <strong className="font-mono text-amber-900">{company.mobile_money_number}</strong>
                </div>
              )}
            </div>
          </div>

          <div className="col-sm-6">
            <div className="text-xs text-slate-500 space-y-2">
              <div>
                <strong className="text-slate-700 d-block mb-1">Terms & Conditions:</strong>
                <p className="mb-0">{invoice.terms || company.default_invoice_terms}</p>
              </div>
              {invoice.notes && (
                <div>
                  <strong className="text-slate-700 d-block mb-1">Notes:</strong>
                  <p className="mb-0">{invoice.notes}</p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
