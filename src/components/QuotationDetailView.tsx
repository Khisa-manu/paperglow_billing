import React from 'react';
import { useBilling } from '../context/BillingContext';
import {
  ArrowLeft,
  Printer,
  Edit2,
  ArrowRightCircle,
  FileCheck,
  Calendar,
  AlertCircle
} from 'lucide-react';

interface QuotationDetailViewProps {
  quotationId: number;
}

export const QuotationDetailView: React.FC<QuotationDetailViewProps> = ({ quotationId }) => {
  const {
    getQuotationById,
    getCustomerById,
    company,
    formatCurrency,
    setViewingQuotationId,
    setEditingQuotationId,
    convertQuotationToInvoice,
    setActiveTab,
    setViewingInvoiceId
  } = useBilling();

  const quotation = getQuotationById(quotationId);
  if (!quotation) {
    return (
      <div className="p-4 text-center">
        <p className="text-muted">Quotation not found.</p>
        <button
          onClick={() => setViewingQuotationId(null)}
          className="btn btn-sm btn-outline-secondary"
        >
          Back to List
        </button>
      </div>
    );
  }

  const customer = getCustomerById(quotation.customer_id);

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'Accepted':
        return <span className="badge bg-success px-3 py-1.5 fs-6">Accepted</span>;
      case 'Sent':
        return <span className="badge bg-primary px-3 py-1.5 fs-6">Sent</span>;
      case 'Draft':
        return <span className="badge bg-secondary px-3 py-1.5 fs-6">Draft</span>;
      case 'Rejected':
        return <span className="badge bg-danger px-3 py-1.5 fs-6">Rejected</span>;
      case 'Expired':
        return <span className="badge bg-warning text-dark px-3 py-1.5 fs-6">Expired</span>;
      default:
        return <span className="badge bg-light text-dark px-3 py-1.5 fs-6">{status}</span>;
    }
  };

  const handleConvert = () => {
    if (window.confirm(`Convert quotation ${quotation.quotation_number} into a formal Invoice?`)) {
      const inv = convertQuotationToInvoice(quotation.id);
      if (inv) {
        setActiveTab('invoices');
        setViewingInvoiceId(inv.id);
      }
    }
  };

  return (
    <div className="space-y-4">
      {/* Top Action Bar */}
      <div className="d-flex align-items-center justify-content-between no-print mb-2">
        <button
          onClick={() => setViewingQuotationId(null)}
          className="btn btn-sm btn-pg-secondary d-flex align-items-center gap-1.5"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Back to Quotations</span>
        </button>

        <div className="d-flex align-items-center gap-2">
          {!quotation.converted_invoice_id && quotation.status !== 'Accepted' && (
            <button
              onClick={handleConvert}
              className="btn btn-sm btn-success d-flex align-items-center gap-1.5"
            >
              <ArrowRightCircle className="w-4 h-4" />
              <span>Convert to Invoice</span>
            </button>
          )}

          <button
            onClick={() => {
              setViewingQuotationId(null);
              setEditingQuotationId(quotation.id);
            }}
            className="btn btn-sm btn-pg-secondary d-flex align-items-center gap-1.5"
          >
            <Edit2 className="w-4 h-4" />
            <span>Edit</span>
          </button>

          <button
            onClick={() => window.print()}
            className="btn btn-sm btn-pg-dark d-flex align-items-center gap-1.5"
          >
            <Printer className="w-4 h-4" />
            <span>Print / PDF</span>
          </button>
        </div>
      </div>

      {/* Official Quotation Document Paper */}
      <div className="pg-document-paper bg-white">
        {/* Header */}
        <div className="pg-doc-header d-flex flex-column flex-sm-row justify-content-between gap-4">
          <div className="d-flex flex-column gap-2">
            <div className="d-flex align-items-center gap-3">
              {company.logo ? (
                <img
                  src={company.logo}
                  alt={company.company_name}
                  referrerPolicy="no-referrer"
                  style={{ maxHeight: '68px', maxWidth: '240px', objectFit: 'contain' }}
                />
              ) : (
                <span className="h4 fw-extrabold text-slate-900 tracking-tight mb-0">
                  {company.company_name}
                </span>
              )}
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
              Quotation
            </div>
            <div className="font-mono fw-bold text-slate-700 text-sm mb-2">
              {quotation.quotation_number}
            </div>
            <div>{getStatusBadge(quotation.status)}</div>
          </div>
        </div>

        {/* Number Banner */}
        <div className="pg-doc-number-banner mb-4">
          <div className="row g-3">
            <div className="col-sm-4">
              <span className="text-xs text-slate-500 text-uppercase fw-semibold d-block">
                Quotation Date
              </span>
              <strong className="text-sm text-slate-800 font-mono">{quotation.quotation_date}</strong>
            </div>
            <div className="col-sm-4">
              <span className="text-xs text-slate-500 text-uppercase fw-semibold d-block">
                Valid Until / Expiry
              </span>
              <strong className="text-sm font-mono text-slate-800">{quotation.expiry_date}</strong>
            </div>
            <div className="col-sm-4 text-sm-end">
              <span className="text-xs text-slate-500 text-uppercase fw-semibold d-block">
                Total Estimate
              </span>
              <strong className="text-base text-amber-600 font-mono fw-bold">
                {formatCurrency(quotation.grand_total)}
              </strong>
            </div>
          </div>
        </div>

        {/* Client details */}
        <div className="row mb-4">
          <div className="col-sm-7">
            <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg">
              <div className="text-[11px] font-bold text-slate-400 text-uppercase mb-1">
                Prepared For
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
              </div>
            </div>
          </div>
        </div>

        {/* Line Items Table */}
        <div className="table-responsive mb-4">
          <table className="table table-pg table-bordered mb-0">
            <thead>
              <tr>
                <th style={{ width: '45%' }}>Scope & Deliverables Description</th>
                <th className="text-center" style={{ width: '10%' }}>Qty</th>
                <th className="text-end" style={{ width: '15%' }}>Unit Price</th>
                <th className="text-end" style={{ width: '10%' }}>Disc.</th>
                <th className="text-end" style={{ width: '10%' }}>Tax</th>
                <th className="text-end" style={{ width: '15%' }}>Total</th>
              </tr>
            </thead>
            <tbody>
              {quotation.items.map((item, idx) => (
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

        {/* Summary */}
        <div className="row justify-content-end mb-4">
          <div className="col-sm-6">
            <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-2 text-xs font-mono">
              <div className="d-flex justify-content-between text-slate-600">
                <span>Subtotal</span>
                <span>{formatCurrency(quotation.subtotal)}</span>
              </div>
              {quotation.discount_total > 0 && (
                <div className="d-flex justify-content-between text-emerald-700">
                  <span>Discount Total</span>
                  <span>-{formatCurrency(quotation.discount_total)}</span>
                </div>
              )}
              {quotation.tax_total > 0 && (
                <div className="d-flex justify-content-between text-slate-600">
                  <span>Estimated Tax</span>
                  <span>+{formatCurrency(quotation.tax_total)}</span>
                </div>
              )}
              <div className="border-t border-slate-300 pt-2 d-flex justify-content-between text-slate-900 fw-bold fs-6">
                <span>Total Quotation Value</span>
                <span className="text-slate-950">{formatCurrency(quotation.grand_total)}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Terms */}
        <div className="pt-3 border-t border-slate-200 text-xs text-slate-500 space-y-2">
          <div>
            <strong className="text-slate-700 d-block mb-1">Quotation Terms & Validity:</strong>
            <p className="mb-0">{quotation.terms || company.default_quotation_terms}</p>
          </div>
          {quotation.notes && (
            <div>
              <strong className="text-slate-700 d-block mb-1">Scope Notes:</strong>
              <p className="mb-0">{quotation.notes}</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
