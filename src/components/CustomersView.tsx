import React, { useState } from 'react';
import { useBilling } from '../context/BillingContext';
import { Customer } from '../types';
import { CustomerModal } from './CustomerModal';
import {
  Users,
  Plus,
  Search,
  Mail,
  Phone,
  MapPin,
  FileText,
  Receipt,
  Edit2,
  Trash2,
  Eye,
  AlertCircle,
  Building,
  CheckCircle2
} from 'lucide-react';

export const CustomersView: React.FC = () => {
  const {
    customers,
    invoices,
    quotations,
    formatCurrency,
    deleteCustomer,
    isCreatingCustomer,
    setIsCreatingCustomer,
    editingCustomerId,
    setEditingCustomerId,
    setActiveTab,
    setViewingInvoiceId,
    setViewingQuotationId
  } = useBilling();

  const [search, setSearch] = useState('');
  const [selectedCustomerForDetail, setSelectedCustomerForDetail] = useState<Customer | null>(null);

  const filteredCustomers = customers.filter(
    (c) =>
      c.name.toLowerCase().includes(search.toLowerCase()) ||
      (c.company?.toLowerCase() || '').includes(search.toLowerCase()) ||
      c.email.toLowerCase().includes(search.toLowerCase()) ||
      c.phone.toLowerCase().includes(search.toLowerCase())
  );

  const getCustomerStats = (customerId: number) => {
    const custInvoices = invoices.filter((i) => i.customer_id === customerId);
    const totalBilled = custInvoices.reduce((sum, i) => sum + Number(i.grand_total || 0), 0);
    const totalPaid = custInvoices.reduce((sum, i) => sum + Number(i.paid_amount || 0), 0);
    const outstanding = custInvoices.reduce((sum, i) => sum + Number(i.balance || 0), 0);
    const custQuotations = quotations.filter((q) => q.customer_id === customerId);

    return {
      invoicesCount: custInvoices.length,
      quotationsCount: custQuotations.length,
      totalBilled,
      totalPaid,
      outstanding,
      invoices: custInvoices,
      quotations: custQuotations,
    };
  };

  return (
    <div className="space-y-4">
      {/* Header controls */}
      <div className="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <h2 className="h5 fw-bold text-slate-900 mb-1">Customer Directory</h2>
          <p className="text-xs text-slate-500 mb-0">
            Manage client contact information, billing accounts, and lifetime order histories.
          </p>
        </div>

        <button
          onClick={() => setIsCreatingCustomer(true)}
          className="btn btn-pg-primary d-inline-flex align-items-center gap-2 self-start"
        >
          <Plus className="w-4 h-4" />
          <span>Add Customer</span>
        </button>
      </div>

      {/* Search Bar */}
      <div className="pg-card p-3">
        <div className="d-flex align-items-center gap-2">
          <Search className="w-4 h-4 text-slate-400" />
          <input
            type="text"
            className="form-control form-control-sm border-0 shadow-none text-xs"
            placeholder="Search by client name, company, email, or phone number..."
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

      {/* Customers Table / Cards */}
      <div className="pg-card overflow-hidden">
        <div className="table-responsive">
          <table className="table table-pg mb-0 align-middle">
            <thead>
              <tr>
                <th>Customer / Company</th>
                <th>Contact Details</th>
                <th>Address & Tax PIN</th>
                <th className="text-end">Total Billed</th>
                <th className="text-end">Total Paid</th>
                <th className="text-end">Outstanding</th>
                <th className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredCustomers.map((c) => {
                const stats = getCustomerStats(c.id);
                return (
                  <tr key={c.id}>
                    <td>
                      <div className="fw-bold text-slate-900 text-xs">{c.name}</div>
                      {c.company && (
                        <div className="text-[11px] text-slate-500 fw-medium d-flex align-items-center gap-1">
                          <Building className="w-3 h-3 text-slate-400" />
                          <span>{c.company}</span>
                        </div>
                      )}
                      <div className="text-[10px] text-slate-400 mt-0.5">
                        {stats.invoicesCount} Invoices &bull; {stats.quotationsCount} Quotations
                      </div>
                    </td>
                    <td>
                      <div className="text-xs text-slate-700 d-flex align-items-center gap-1">
                        <Mail className="w-3 h-3 text-slate-400" />
                        <span>{c.email}</span>
                      </div>
                      {c.phone && (
                        <div className="text-xs text-slate-500 d-flex align-items-center gap-1 mt-0.5">
                          <Phone className="w-3 h-3 text-slate-400" />
                          <span>{c.phone}</span>
                        </div>
                      )}
                    </td>
                    <td>
                      <div className="text-xs text-slate-600 max-w-[200px] truncate" title={c.address}>
                        {c.address || '-'}
                      </div>
                      {c.tax_number && (
                        <div className="text-[11px] font-mono text-slate-500">
                          Tax PIN: {c.tax_number}
                        </div>
                      )}
                    </td>
                    <td className="text-end font-mono fw-bold text-slate-900 text-xs">
                      {formatCurrency(stats.totalBilled)}
                    </td>
                    <td className="text-end font-mono text-emerald-600 fw-semibold text-xs">
                      {formatCurrency(stats.totalPaid)}
                    </td>
                    <td className="text-end font-mono text-xs">
                      {stats.outstanding > 0 ? (
                        <span className="text-rose-600 fw-bold">{formatCurrency(stats.outstanding)}</span>
                      ) : (
                        <span className="text-emerald-600">{formatCurrency(0)}</span>
                      )}
                    </td>
                    <td className="text-end">
                      <div className="d-flex align-items-center justify-content-end gap-1">
                        <button
                          onClick={() => setSelectedCustomerForDetail(c)}
                          className="btn btn-xs btn-outline-secondary p-1.5"
                          title="View Statement & History"
                        >
                          <Eye className="w-3.5 h-3.5 text-slate-600" />
                        </button>
                        <button
                          onClick={() => setEditingCustomerId(c.id)}
                          className="btn btn-xs btn-outline-secondary p-1.5"
                          title="Edit Customer"
                        >
                          <Edit2 className="w-3.5 h-3.5 text-slate-600" />
                        </button>
                        <button
                          onClick={() => {
                            if (window.confirm(`Delete client ${c.name}?`)) {
                              deleteCustomer(c.id);
                            }
                          }}
                          className="btn btn-xs btn-outline-danger p-1.5"
                          title="Delete Customer"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}

              {filteredCustomers.length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-slate-400">
                    <AlertCircle className="w-6 h-6 mx-auto mb-2 text-slate-300" />
                    <p className="text-sm mb-1">No customers found.</p>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Customer Statement & Detail Modal */}
      {selectedCustomerForDetail && (
        <div
          className="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-3"
          style={{ zIndex: 1060, backgroundColor: 'rgba(15, 23, 42, 0.65)' }}
        >
          <div className="bg-white rounded-xl shadow-2xl max-w-2xl w-100 max-h-[85vh] flex flex-col overflow-hidden border border-slate-200">
            <div className="px-4 py-3 bg-slate-900 text-white d-flex align-items-center justify-content-between flex-none">
              <div>
                <div className="h6 fw-bold mb-0 text-white">{selectedCustomerForDetail.name}</div>
                <div className="text-xs text-amber-400">
                  {selectedCustomerForDetail.company || 'Individual Client'}
                </div>
              </div>
              <button
                onClick={() => setSelectedCustomerForDetail(null)}
                className="btn btn-sm btn-link text-slate-400 hover:text-white p-0"
              >
                Close
              </button>
            </div>

            <div className="p-4 overflow-y-auto space-y-4">
              {/* Financial Stats Grid */}
              {(() => {
                const stats = getCustomerStats(selectedCustomerForDetail.id);
                return (
                  <>
                    <div className="row g-2 text-center font-mono">
                      <div className="col-4">
                        <div className="p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                          <div className="text-[10px] text-slate-500 uppercase">Total Billed</div>
                          <div className="text-sm font-bold text-slate-900">{formatCurrency(stats.totalBilled)}</div>
                        </div>
                      </div>
                      <div className="col-4">
                        <div className="p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg">
                          <div className="text-[10px] text-emerald-700 uppercase">Total Paid</div>
                          <div className="text-sm font-bold text-emerald-700">{formatCurrency(stats.totalPaid)}</div>
                        </div>
                      </div>
                      <div className="col-4">
                        <div className="p-2.5 bg-rose-50 border border-rose-200 rounded-lg">
                          <div className="text-[10px] text-rose-700 uppercase">Outstanding</div>
                          <div className="text-sm font-bold text-rose-700">{formatCurrency(stats.outstanding)}</div>
                        </div>
                      </div>
                    </div>

                    {/* Customer Invoices */}
                    <div>
                      <h4 className="h6 fw-bold text-slate-900 mb-2">Invoices ({stats.invoices.length})</h4>
                      <div className="table-responsive">
                        <table className="table table-sm table-bordered text-xs mb-0">
                          <thead className="table-light">
                            <tr>
                              <th>Invoice #</th>
                              <th>Date</th>
                              <th>Due Date</th>
                              <th className="text-end">Total</th>
                              <th className="text-end">Balance</th>
                              <th>Status</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            {stats.invoices.map((inv) => (
                              <tr key={inv.id}>
                                <td className="font-mono fw-bold">{inv.invoice_number}</td>
                                <td>{inv.invoice_date}</td>
                                <td>{inv.due_date}</td>
                                <td className="text-end font-mono">{formatCurrency(inv.grand_total)}</td>
                                <td className="text-end font-mono fw-bold text-rose-600">{formatCurrency(inv.balance)}</td>
                                <td><span className="badge bg-light text-dark">{inv.status}</span></td>
                                <td>
                                  <button
                                    onClick={() => {
                                      setSelectedCustomerForDetail(null);
                                      setActiveTab('invoices');
                                      setViewingInvoiceId(inv.id);
                                    }}
                                    className="btn btn-xs btn-outline-secondary py-0 px-1 text-[11px]"
                                  >
                                    View
                                  </button>
                                </td>
                              </tr>
                            ))}
                            {stats.invoices.length === 0 && (
                              <tr>
                                <td colSpan={7} className="text-center py-2 text-muted">No invoices found.</td>
                              </tr>
                            )}
                          </tbody>
                        </table>
                      </div>
                    </div>

                    {/* Customer Quotations */}
                    <div>
                      <h4 className="h6 fw-bold text-slate-900 mb-2">Quotations ({stats.quotations.length})</h4>
                      <div className="table-responsive">
                        <table className="table table-sm table-bordered text-xs mb-0">
                          <thead className="table-light">
                            <tr>
                              <th>Quotation #</th>
                              <th>Date</th>
                              <th>Expiry</th>
                              <th className="text-end">Total</th>
                              <th>Status</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            {stats.quotations.map((q) => (
                              <tr key={q.id}>
                                <td className="font-mono fw-bold">{q.quotation_number}</td>
                                <td>{q.quotation_date}</td>
                                <td>{q.expiry_date}</td>
                                <td className="text-end font-mono">{formatCurrency(q.grand_total)}</td>
                                <td><span className="badge bg-light text-dark">{q.status}</span></td>
                                <td>
                                  <button
                                    onClick={() => {
                                      setSelectedCustomerForDetail(null);
                                      setActiveTab('quotations');
                                      setViewingQuotationId(q.id);
                                    }}
                                    className="btn btn-xs btn-outline-secondary py-0 px-1 text-[11px]"
                                  >
                                    View
                                  </button>
                                </td>
                              </tr>
                            ))}
                            {stats.quotations.length === 0 && (
                              <tr>
                                <td colSpan={6} className="text-center py-2 text-muted">No quotations found.</td>
                              </tr>
                            )}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </>
                );
              })()}
            </div>
          </div>
        </div>
      )}

      {/* Customer Create/Edit Modal */}
      {(isCreatingCustomer || editingCustomerId) && (
        <CustomerModal
          customerId={editingCustomerId}
          onClose={() => {
            setIsCreatingCustomer(false);
            setEditingCustomerId(null);
          }}
        />
      )}
    </div>
  );
};
