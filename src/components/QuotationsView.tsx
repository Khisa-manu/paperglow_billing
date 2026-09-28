import React, { useState } from 'react';
import { useBilling } from '../context/BillingContext';
import { QuotationStatus } from '../types';
import {
  FileText,
  Plus,
  Search,
  Eye,
  Edit2,
  Trash2,
  ArrowRightCircle,
  AlertCircle
} from 'lucide-react';

export const QuotationsView: React.FC = () => {
  const {
    quotations,
    formatCurrency,
    getCustomerById,
    setViewingQuotationId,
    setEditingQuotationId,
    setIsCreatingQuotation,
    convertQuotationToInvoice,
    deleteQuotation,
    setActiveTab,
    setViewingInvoiceId
  } = useBilling();

  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('All');

  const filteredQuotations = quotations.filter((q) => {
    const customer = getCustomerById(q.customer_id);
    const matchesSearch =
      q.quotation_number.toLowerCase().includes(search.toLowerCase()) ||
      customer?.name.toLowerCase().includes(search.toLowerCase()) ||
      customer?.company?.toLowerCase().includes(search.toLowerCase());

    const matchesStatus =
      statusFilter === 'All' ? true : q.status === statusFilter;

    return matchesSearch && matchesStatus;
  });

  const getStatusBadge = (status: QuotationStatus) => {
    switch (status) {
      case 'Accepted':
        return <span className="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Accepted</span>;
      case 'Sent':
        return <span className="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Sent</span>;
      case 'Draft':
        return <span className="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Draft</span>;
      case 'Rejected':
        return <span className="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Rejected</span>;
      case 'Expired':
        return <span className="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Expired</span>;
      default:
        return <span className="badge bg-light text-dark px-2 py-1">{status}</span>;
    }
  };

  return (
    <div className="space-y-4">
      {/* Header controls */}
      <div className="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <h2 className="h5 fw-bold text-slate-900 mb-1">Quotations & Scope Estimates</h2>
          <p className="text-xs text-slate-500 mb-0">
            Generate formal commercial proposals with one-click conversion to billing invoices.
          </p>
        </div>

        <button
          onClick={() => setIsCreatingQuotation(true)}
          className="btn btn-pg-primary d-inline-flex align-items-center gap-2 self-start"
        >
          <Plus className="w-4 h-4" />
          <span>New Quotation</span>
        </button>
      </div>

      {/* Filter Tabs */}
      <div className="d-flex flex-wrap gap-1 border-b border-slate-200 pb-2">
        {(['All', 'Draft', 'Sent', 'Accepted', 'Rejected', 'Expired'] as const).map((st) => (
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
              {st === 'All' ? quotations.length : quotations.filter((q) => q.status === st).length}
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
            placeholder="Search quotations by number (#QUO-...), client name, or organization..."
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

      {/* Quotations Table */}
      <div className="pg-card overflow-hidden">
        <div className="table-responsive">
          <table className="table table-pg mb-0 align-middle">
            <thead>
              <tr>
                <th>Quotation #</th>
                <th>Client / Organization</th>
                <th>Date Issued</th>
                <th>Expiry Date</th>
                <th className="text-end">Proposal Value</th>
                <th>Status</th>
                <th className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredQuotations.map((q) => {
                const customer = getCustomerById(q.customer_id);
                return (
                  <tr key={q.id}>
                    <td>
                      <button
                        onClick={() => setViewingQuotationId(q.id)}
                        className="fw-bold font-mono text-slate-900 text-xs hover:text-amber-600 bg-transparent border-0 p-0 text-start"
                      >
                        {q.quotation_number}
                      </button>
                      {q.converted_invoice_id && (
                        <div className="text-[10px] text-emerald-600 fw-semibold">
                          Converted to Inv #{q.converted_invoice_id}
                        </div>
                      )}
                    </td>
                    <td>
                      <div className="fw-semibold text-slate-900 text-xs">{customer?.name || 'Unknown'}</div>
                      <div className="text-[11px] text-slate-500">{customer?.company}</div>
                    </td>
                    <td className="text-xs text-slate-600">{q.quotation_date}</td>
                    <td className="text-xs text-slate-600">{q.expiry_date}</td>
                    <td className="text-end font-mono fw-bold text-slate-900 text-xs">
                      {formatCurrency(q.grand_total)}
                    </td>
                    <td>{getStatusBadge(q.status)}</td>
                    <td className="text-end">
                      <div className="d-flex align-items-center justify-content-end gap-1">
                        <button
                          onClick={() => setViewingQuotationId(q.id)}
                          className="btn btn-xs btn-outline-secondary p-1.5"
                          title="View Quotation"
                        >
                          <Eye className="w-3.5 h-3.5 text-slate-600" />
                        </button>

                        {!q.converted_invoice_id && q.status !== 'Accepted' && (
                          <button
                            onClick={() => {
                              if (window.confirm(`Convert quotation ${q.quotation_number} to an active Invoice?`)) {
                                const inv = convertQuotationToInvoice(q.id);
                                if (inv) {
                                  setActiveTab('invoices');
                                  setViewingInvoiceId(inv.id);
                                }
                              }
                            }}
                            className="btn btn-xs btn-outline-success p-1.5"
                            title="Convert to Invoice"
                          >
                            <ArrowRightCircle className="w-3.5 h-3.5" />
                          </button>
                        )}

                        <button
                          onClick={() => setEditingQuotationId(q.id)}
                          className="btn btn-xs btn-outline-secondary p-1.5"
                          title="Edit Quotation"
                        >
                          <Edit2 className="w-3.5 h-3.5 text-slate-600" />
                        </button>

                        <button
                          onClick={() => {
                            if (window.confirm(`Delete quotation ${q.quotation_number}?`)) {
                              deleteQuotation(q.id);
                            }
                          }}
                          className="btn btn-xs btn-outline-danger p-1.5"
                          title="Delete Quotation"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}

              {filteredQuotations.length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-slate-400">
                    <AlertCircle className="w-6 h-6 mx-auto mb-2 text-slate-300" />
                    <p className="text-sm mb-1">No quotations found matching filter.</p>
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
