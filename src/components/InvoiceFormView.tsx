import React, { useState, useEffect } from 'react';
import { useBilling } from '../context/BillingContext';
import { LineItem, InvoiceStatus } from '../types';
import {
  ArrowLeft,
  Plus,
  Trash2,
  Save,
  DollarSign,
  UserPlus
} from 'lucide-react';

interface InvoiceFormViewProps {
  invoiceId?: number | null;
}

export const InvoiceFormView: React.FC<InvoiceFormViewProps> = ({ invoiceId }) => {
  const {
    invoices,
    customers,
    company,
    createInvoice,
    updateInvoice,
    formatCurrency,
    setEditingInvoiceId,
    setIsCreatingInvoice,
    setViewingInvoiceId,
    setIsCreatingCustomer
  } = useBilling();

  const isEdit = Boolean(invoiceId);
  const existingInvoice = invoiceId ? invoices.find((i) => i.id === invoiceId) : null;

  // Next invoice number
  const nextInvId = invoices.length > 0 ? Math.max(...invoices.map((i) => i.id)) + 1 : 1;
  const defaultInvoiceNumber = `INV-${new Date().getFullYear()}-${String(nextInvId).padStart(4, '0')}`;
  const today = new Date().toISOString().split('T')[0];
  const defaultDue = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

  const [customerId, setCustomerId] = useState<number>(existingInvoice?.customer_id || (customers[0]?.id || 1));
  const [invoiceNumber, setInvoiceNumber] = useState<string>(existingInvoice?.invoice_number || defaultInvoiceNumber);
  const [invoiceDate, setInvoiceDate] = useState<string>(existingInvoice?.invoice_date || today);
  const [dueDate, setDueDate] = useState<string>(existingInvoice?.due_date || defaultDue);
  const [status, setStatus] = useState<InvoiceStatus>(existingInvoice?.status || 'Unpaid');
  const [notes, setNotes] = useState<string>(existingInvoice?.notes || '');
  const [terms, setTerms] = useState<string>(existingInvoice?.terms || company.default_invoice_terms);

  const [items, setItems] = useState<LineItem[]>(
    existingInvoice?.items && existingInvoice.items.length > 0
      ? existingInvoice.items
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

  // Totals
  const subtotal = items.reduce(
    (sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0),
    0
  );
  const discountTotal = items.reduce((sum, item) => sum + (Number(item.discount) || 0), 0);
  const taxTotal = items.reduce((sum, item) => sum + (Number(item.tax_amount) || 0), 0);
  const grandTotal = items.reduce((sum, item) => sum + (Number(item.line_total) || 0), 0);

  const handleCancel = () => {
    setEditingInvoiceId(null);
    setIsCreatingInvoice(false);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!customerId) {
      alert('Please select a customer');
      return;
    }
    if (items.length === 0 || !items[0].description) {
      alert('Please add at least one line item with a description');
      return;
    }

    if (isEdit && invoiceId) {
      updateInvoice(invoiceId, {
        customer_id: Number(customerId),
        invoice_number: invoiceNumber,
        invoice_date: invoiceDate,
        due_date: dueDate,
        status: status,
        subtotal,
        discount_total: discountTotal,
        tax_total: taxTotal,
        grand_total: grandTotal,
        notes,
        terms,
        items,
      });
      setEditingInvoiceId(null);
      setViewingInvoiceId(invoiceId);
    } else {
      const created = createInvoice({
        user_id: 1,
        customer_id: Number(customerId),
        from_quotation_id: null,
        invoice_number: invoiceNumber,
        invoice_date: invoiceDate,
        due_date: dueDate,
        status: status,
        subtotal,
        discount_total: discountTotal,
        tax_total: taxTotal,
        grand_total: grandTotal,
        notes,
        terms,
        items,
      });
      setIsCreatingInvoice(false);
      setViewingInvoiceId(created.id);
    }
  };

  return (
    <div className="space-y-4">
      {/* Top action header */}
      <div className="d-flex align-items-center justify-content-between mb-2">
        <button
          onClick={handleCancel}
          className="btn btn-sm btn-pg-secondary d-flex align-items-center gap-1.5"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Cancel & Return</span>
        </button>

        <h2 className="h5 fw-bold text-slate-900 mb-0">
          {isEdit ? `Edit Invoice ${invoiceNumber}` : 'Create New Invoice'}
        </h2>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Main Details Card */}
        <div className="pg-card p-4">
          <div className="row g-3">
            {/* Customer selector */}
            <div className="col-md-6">
              <label className="form-label-pg d-flex align-items-center justify-content-between">
                <span>Select Client / Organization *</span>
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

            {/* Invoice Number */}
            <div className="col-md-6">
              <label className="form-label-pg">Invoice Number *</label>
              <input
                type="text"
                className="form-control form-control-pg font-mono"
                value={invoiceNumber}
                onChange={(e) => setInvoiceNumber(e.target.value)}
                required
              />
            </div>

            {/* Dates */}
            <div className="col-md-4">
              <label className="form-label-pg">Date of Issue *</label>
              <input
                type="date"
                className="form-control form-control-pg font-mono"
                value={invoiceDate}
                onChange={(e) => setInvoiceDate(e.target.value)}
                required
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">Payment Due Date *</label>
              <input
                type="date"
                className="form-control form-control-pg font-mono"
                value={dueDate}
                onChange={(e) => setDueDate(e.target.value)}
                required
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">Initial Status</label>
              <select
                className="form-select form-select-pg"
                value={status}
                onChange={(e) => setStatus(e.target.value as InvoiceStatus)}
              >
                <option value="Unpaid">Unpaid</option>
                <option value="Draft">Draft</option>
                <option value="Partially Paid">Partially Paid</option>
                <option value="Paid">Paid</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
          </div>
        </div>

        {/* Dynamic Line Items Table Card */}
        <div className="pg-card p-4">
          <div className="d-flex align-items-center justify-content-between mb-3">
            <h3 className="h6 fw-bold mb-0 text-slate-900">Line Items & Services</h3>
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
                      placeholder="Service or product description..."
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
                      placeholder="Qty"
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
                      placeholder="Unit Price"
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
                      placeholder="Disc"
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
                      placeholder="Tax %"
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

          {/* Totals Summary */}
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
                    <span>Tax Total:</span>
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

        {/* Terms & Notes Card */}
        <div className="pg-card p-4">
          <div className="row g-3">
            <div className="col-md-6">
              <label className="form-label-pg">Default Payment Terms</label>
              <textarea
                className="form-control form-control-pg text-xs"
                rows={3}
                value={terms}
                onChange={(e) => setTerms(e.target.value)}
              />
            </div>
            <div className="col-md-6">
              <label className="form-label-pg">Client Notes / Scope Reference</label>
              <textarea
                className="form-control form-control-pg text-xs"
                rows={3}
                placeholder="Optional internal or client visible reference notes..."
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
            <span>{isEdit ? 'Update Invoice' : 'Create & Save Invoice'}</span>
          </button>
        </div>
      </form>
    </div>
  );
};
