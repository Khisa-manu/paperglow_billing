import React, { useState } from 'react';
import { useBilling } from '../context/BillingContext';
import { LineItem, QuotationStatus } from '../types';
import {
  ArrowLeft,
  Plus,
  Trash2,
  Save,
  UserPlus
} from 'lucide-react';

interface QuotationFormViewProps {
  quotationId?: number | null;
}

export const QuotationFormView: React.FC<QuotationFormViewProps> = ({ quotationId }) => {
  const {
    quotations,
    customers,
    company,
    createQuotation,
    updateQuotation,
    formatCurrency,
    setEditingQuotationId,
    setIsCreatingQuotation,
    setViewingQuotationId,
    setIsCreatingCustomer
  } = useBilling();

  const isEdit = Boolean(quotationId);
  const existing = quotationId ? quotations.find((q) => q.id === quotationId) : null;

  const nextQId = quotations.length > 0 ? Math.max(...quotations.map((q) => q.id)) + 1 : 1;
  const defaultQNumber = `QUO-${new Date().getFullYear()}-${String(nextQId).padStart(4, '0')}`;
  const today = new Date().toISOString().split('T')[0];
  const defaultExpiry = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

  const [customerId, setCustomerId] = useState<number>(existing?.customer_id || (customers[0]?.id || 1));
  const [quotationNumber, setQuotationNumber] = useState<string>(existing?.quotation_number || defaultQNumber);
  const [quotationDate, setQuotationDate] = useState<string>(existing?.quotation_date || today);
  const [expiryDate, setExpiryDate] = useState<string>(existing?.expiry_date || defaultExpiry);
  const [status, setStatus] = useState<QuotationStatus>(existing?.status || 'Draft');
  const [notes, setNotes] = useState<string>(existing?.notes || '');
  const [terms, setTerms] = useState<string>(existing?.terms || company.default_quotation_terms);

  const [items, setItems] = useState<LineItem[]>(
    existing?.items && existing.items.length > 0
      ? existing.items
      : [
          {
            id: 1,
            description: '',
            quantity: 1,
            unit_price: 0,
            discount: 0,
            tax_rate: 0,
            tax_amount: 0,
            line_total: 0,
            sort_order: 1,
          },
        ]
  );

  const calculateLine = (item: LineItem) => {
    const qty = Number(item.quantity) || 0;
    const price = Number(item.unit_price) || 0;
    const discount = Number(item.discount) || 0;
    const taxRate = Number(item.tax_rate) || 0;

    const net = Math.max(0, qty * price - discount);
    const tax = net * (taxRate / 100);
    const total = net + tax;

    return {
      tax_amount: Number(tax.toFixed(2)),
      line_total: Number(total.toFixed(2)),
    };
  };

  const handleItemChange = (index: number, field: keyof LineItem, value: any) => {
    setItems((prev) => {
      const updated = [...prev];
      const item = { ...updated[index], [field]: value };
      const calcs = calculateLine(item);
      updated[index] = { ...item, ...calcs };
      return updated;
    });
  };

  const addItem = () => {
    setItems((prev) => [
      ...prev,
      {
        id: prev.length + 1,
        description: '',
        quantity: 1,
        unit_price: 0,
        discount: 0,
        tax_rate: 0,
        tax_amount: 0,
        line_total: 0,
        sort_order: prev.length + 1,
      },
    ]);
  };

  const removeItem = (index: number) => {
    if (items.length <= 1) return;
    setItems((prev) => prev.filter((_, i) => i !== index));
  };

  const subtotal = items.reduce(
    (sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0),
    0
  );
  const discountTotal = items.reduce((sum, item) => sum + (Number(item.discount) || 0), 0);
  const taxTotal = items.reduce((sum, item) => sum + (Number(item.tax_amount) || 0), 0);
  const grandTotal = items.reduce((sum, item) => sum + (Number(item.line_total) || 0), 0);

  const handleCancel = () => {
    setEditingQuotationId(null);
    setIsCreatingQuotation(false);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!customerId) {
      alert('Please select a customer');
      return;
    }
    if (items.length === 0 || !items[0].description) {
      alert('Please add at least one item description');
      return;
    }

    if (isEdit && quotationId) {
      updateQuotation(quotationId, {
        customer_id: Number(customerId),
        quotation_number: quotationNumber,
        quotation_date: quotationDate,
        expiry_date: expiryDate,
        status: status,
        subtotal,
        discount_total: discountTotal,
        tax_total: taxTotal,
        grand_total: grandTotal,
        notes,
        terms,
        items,
      });
      setEditingQuotationId(null);
      setViewingQuotationId(quotationId);
    } else {
      const created = createQuotation({
        user_id: 1,
        customer_id: Number(customerId),
        quotation_number: quotationNumber,
        quotation_date: quotationDate,
        expiry_date: expiryDate,
        status: status,
        subtotal,
        discount_total: discountTotal,
        tax_total: taxTotal,
        grand_total: grandTotal,
        notes,
        terms,
        items,
      });
      setIsCreatingQuotation(false);
      setViewingQuotationId(created.id);
    }
  };

  return (
    <div className="space-y-4">
      {/* Top Header */}
      <div className="d-flex align-items-center justify-content-between mb-2">
        <button
          onClick={handleCancel}
          className="btn btn-sm btn-pg-secondary d-flex align-items-center gap-1.5"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Cancel</span>
        </button>

        <h2 className="h5 fw-bold text-slate-900 mb-0">
          {isEdit ? `Edit Quotation ${quotationNumber}` : 'Create New Quotation'}
        </h2>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Main Details */}
        <div className="pg-card p-4">
          <div className="row g-3">
            <div className="col-md-6">
              <label className="form-label-pg d-flex align-items-center justify-content-between">
                <span>Client / Organization *</span>
                <button
                  type="button"
                  onClick={() => setIsCreatingCustomer(true)}
                  className="text-amber-600 hover:text-amber-700 text-xs font-semibold p-0 bg-transparent border-0 d-flex align-items-center gap-1"
                >
                  <UserPlus className="w-3 h-3" />
                  <span>+ New Client</span>
                </button>
              </label>
              <select
                className="form-select form-select-pg"
                value={customerId}
                onChange={(e) => setCustomerId(Number(e.target.value))}
                required
              >
                {customers.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name} {c.company ? `(${c.company})` : ''}
                  </option>
                ))}
              </select>
            </div>

            <div className="col-md-6">
              <label className="form-label-pg">Quotation Number *</label>
              <input
                type="text"
                className="form-control form-control-pg font-mono"
                value={quotationNumber}
                onChange={(e) => setQuotationNumber(e.target.value)}
                required
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">Quotation Date *</label>
              <input
                type="date"
                className="form-control form-control-pg font-mono"
                value={quotationDate}
                onChange={(e) => setQuotationDate(e.target.value)}
                required
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">Expiry Date *</label>
              <input
                type="date"
                className="form-control form-control-pg font-mono"
                value={expiryDate}
                onChange={(e) => setExpiryDate(e.target.value)}
                required
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">Status</label>
              <select
                className="form-select form-select-pg"
                value={status}
                onChange={(e) => setStatus(e.target.value as QuotationStatus)}
              >
                <option value="Draft">Draft</option>
                <option value="Sent">Sent</option>
                <option value="Accepted">Accepted</option>
                <option value="Rejected">Rejected</option>
                <option value="Expired">Expired</option>
              </select>
            </div>
          </div>
        </div>

        {/* Dynamic Items */}
        <div className="pg-card p-4">
          <div className="d-flex align-items-center justify-content-between mb-3">
            <h3 className="h6 fw-bold mb-0 text-slate-900">Scope Deliverables & Pricing</h3>
            <button
              type="button"
              onClick={addItem}
              className="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 text-xs"
            >
              <Plus className="w-3.5 h-3.5" />
              <span>Add Item</span>
            </button>
          </div>

          <div className="space-y-3">
            {items.map((item, index) => (
              <div key={index} className="pg-item-row">
                <div className="row g-2 align-items-center">
                  <div className="col-md-4">
                    <label className="small text-muted mb-1 d-md-none">Description</label>
                    <input
                      type="text"
                      className="form-control form-control-sm text-xs"
                      placeholder="Scope item description..."
                      value={item.description}
                      onChange={(e) => handleItemChange(index, 'description', e.target.value)}
                      required
                    />
                  </div>

                  <div className="col-6 col-md-2">
                    <label className="small text-muted mb-1 d-md-none">Qty</label>
                    <input
                      type="number"
                      step="any"
                      min="0.1"
                      className="form-control form-control-sm text-xs font-mono"
                      value={item.quantity}
                      onChange={(e) => handleItemChange(index, 'quantity', parseFloat(e.target.value) || 0)}
                      required
                    />
                  </div>

                  <div className="col-6 col-md-2">
                    <label className="small text-muted mb-1 d-md-none">Price</label>
                    <input
                      type="number"
                      step="any"
                      min="0"
                      className="form-control form-control-sm text-xs font-mono"
                      value={item.unit_price}
                      onChange={(e) => handleItemChange(index, 'unit_price', parseFloat(e.target.value) || 0)}
                      required
                    />
                  </div>

                  <div className="col-4 col-md-1">
                    <label className="small text-muted mb-1 d-md-none">Disc</label>
                    <input
                      type="number"
                      step="any"
                      min="0"
                      className="form-control form-control-sm text-xs font-mono"
                      value={item.discount}
                      onChange={(e) => handleItemChange(index, 'discount', parseFloat(e.target.value) || 0)}
                    />
                  </div>

                  <div className="col-4 col-md-1">
                    <label className="small text-muted mb-1 d-md-none">Tax %</label>
                    <input
                      type="number"
                      step="any"
                      min="0"
                      className="form-control form-control-sm text-xs font-mono"
                      value={item.tax_rate}
                      onChange={(e) => handleItemChange(index, 'tax_rate', parseFloat(e.target.value) || 0)}
                    />
                  </div>

                  <div className="col-3 col-md-1 text-end">
                    <div className="fw-bold font-mono text-xs text-slate-800">
                      {formatCurrency(item.line_total)}
                    </div>
                  </div>

                  <div className="col-1 text-end">
                    <button
                      type="button"
                      onClick={() => removeItem(index)}
                      disabled={items.length <= 1}
                      className="btn btn-xs text-slate-400 hover:text-rose-600 p-1 border-0 bg-transparent"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Totals */}
          <div className="row justify-content-end mt-4">
            <div className="col-md-5">
              <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-2 text-xs font-mono">
                <div className="d-flex justify-content-between text-slate-600">
                  <span>Subtotal:</span>
                  <span>{formatCurrency(subtotal)}</span>
                </div>
                {discountTotal > 0 && (
                  <div className="d-flex justify-content-between text-emerald-700">
                    <span>Discount Total:</span>
                    <span>-{formatCurrency(discountTotal)}</span>
                  </div>
                )}
                {taxTotal > 0 && (
                  <div className="d-flex justify-content-between text-slate-600">
                    <span>Estimated Tax:</span>
                    <span>+{formatCurrency(taxTotal)}</span>
                  </div>
                )}
                <div className="border-t border-slate-300 pt-2 d-flex justify-content-between text-slate-900 fw-bold fs-6">
                  <span>Grand Total:</span>
                  <span>{formatCurrency(grandTotal)}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Terms */}
        <div className="pg-card p-4">
          <div className="row g-3">
            <div className="col-md-6">
              <label className="form-label-pg">Quotation Terms & Validity</label>
              <textarea
                className="form-control form-control-pg text-xs"
                rows={3}
                value={terms}
                onChange={(e) => setTerms(e.target.value)}
              />
            </div>
            <div className="col-md-6">
              <label className="form-label-pg">Internal Notes / Delivery Timeline</label>
              <textarea
                className="form-control form-control-pg text-xs"
                rows={3}
                placeholder="Optional scope notes..."
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
              />
            </div>
          </div>
        </div>

        {/* Form Actions */}
        <div className="d-flex align-items-center justify-content-end gap-2 pt-2">
          <button
            type="button"
            onClick={handleCancel}
            className="btn btn-pg-secondary text-sm"
          >
            Cancel
          </button>
          <button
            type="submit"
            className="btn btn-pg-primary d-flex align-items-center gap-1.5 text-sm"
          >
            <Save className="w-4 h-4" />
            <span>{isEdit ? 'Update Quotation' : 'Create Quotation'}</span>
          </button>
        </div>
      </form>
    </div>
  );
};
