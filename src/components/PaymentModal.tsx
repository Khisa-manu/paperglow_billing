import React, { useState, useEffect } from 'react';
import { useBilling } from '../context/BillingContext';
import { PaymentMethod } from '../types';
import {
  X,
  Wallet,
  Save,
  DollarSign
} from 'lucide-react';

interface PaymentModalProps {
  invoiceId: number | null;
  onClose: () => void;
}

export const PaymentModal: React.FC<PaymentModalProps> = ({ invoiceId, onClose }) => {
  const {
    invoices,
    customers,
    createPayment,
    formatCurrency,
    getCustomerById
  } = useBilling();

  // Filter invoices that have an outstanding balance or selected one
  const eligibleInvoices = invoices.filter((i) => i.balance > 0 || i.id === invoiceId);

  const initialInvoiceId = invoiceId || eligibleInvoices[0]?.id || (invoices[0]?.id || 1);
  const [selectedInvoiceId, setSelectedInvoiceId] = useState<number>(initialInvoiceId);

  const currentInvoice = invoices.find((i) => i.id === selectedInvoiceId);
  const customer = currentInvoice ? getCustomerById(currentInvoice.customer_id) : null;

  const [amount, setAmount] = useState<number>(currentInvoice?.balance || 0);
  const [paymentDate, setPaymentDate] = useState<string>(
    new Date().toISOString().split('T')[0]
  );
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('Bank Transfer');
  const [referenceNumber, setReferenceNumber] = useState<string>('');
  const [notes, setNotes] = useState<string>('');

  useEffect(() => {
    if (currentInvoice) {
      setAmount(currentInvoice.balance > 0 ? currentInvoice.balance : currentInvoice.grand_total);
    }
  }, [selectedInvoiceId]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedInvoiceId) {
      alert('Please select an invoice');
      return;
    }
    if (amount <= 0) {
      alert('Payment amount must be greater than 0');
      return;
    }

    createPayment({
      user_id: 1,
      invoice_id: Number(selectedInvoiceId),
      payment_date: paymentDate,
      amount: Number(amount),
      payment_method: paymentMethod,
      reference_number: referenceNumber,
      notes: notes,
    });

    onClose();
  };

  return (
    <div
      className="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-3"
      style={{ zIndex: 1060, backgroundColor: 'rgba(15, 23, 42, 0.65)' }}
    >
      <div className="bg-white rounded-xl shadow-2xl max-w-lg w-100 overflow-hidden border border-slate-200">
        {/* Modal Header */}
        <div className="px-4 py-3 bg-slate-900 text-white d-flex align-items-center justify-content-between">
          <div className="d-flex align-items-center gap-2">
            <div className="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 d-flex align-items-center justify-content-center">
              <Wallet className="w-4 h-4 text-amber-400" />
            </div>
            <div>
              <div className="fw-bold text-sm">Record Received Payment</div>
              <div className="text-[11px] text-slate-400">Reconcile transaction against invoice</div>
            </div>
          </div>
          <button
            onClick={onClose}
            className="btn btn-sm btn-link text-slate-400 hover:text-white p-0"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Modal Form */}
        <form onSubmit={handleSubmit} className="p-4 space-y-3">
          {/* Target Invoice */}
          <div>
            <label className="form-label-pg">Target Invoice *</label>
            <select
              className="form-select form-select-pg"
              value={selectedInvoiceId}
              onChange={(e) => setSelectedInvoiceId(Number(e.target.value))}
              required
            >
              {eligibleInvoices.map((inv) => {
                const c = getCustomerById(inv.customer_id);
                return (
                  <option key={inv.id} value={inv.id}>
                    {inv.invoice_number} — {c?.name} (Outstanding: {formatCurrency(inv.balance)})
                  </option>
                );
              })}
            </select>
          </div>

          {currentInvoice && (
            <div className="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs d-flex justify-content-between font-mono">
              <span className="text-slate-600">Client: <strong>{customer?.name}</strong></span>
              <span className="text-rose-700 fw-bold">Balance: {formatCurrency(currentInvoice.balance)}</span>
            </div>
          )}

          {/* Amount & Date */}
          <div className="row g-2">
            <div className="col-6">
              <label className="form-label-pg">Payment Amount *</label>
              <input
                type="number"
                step="any"
                min="0.01"
                className="form-control form-control-pg font-mono fw-bold"
                value={amount}
                onChange={(e) => setAmount(parseFloat(e.target.value) || 0)}
                required
              />
            </div>
            <div className="col-6">
              <label className="form-label-pg">Payment Date *</label>
              <input
                type="date"
                className="form-control form-control-pg font-mono"
                value={paymentDate}
                onChange={(e) => setPaymentDate(e.target.value)}
                required
              />
            </div>
          </div>

          {/* Method & Ref */}
          <div className="row g-2">
            <div className="col-6">
              <label className="form-label-pg">Payment Method *</label>
              <select
                className="form-select form-select-pg"
                value={paymentMethod}
                onChange={(e) => setPaymentMethod(e.target.value as PaymentMethod)}
              >
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Mobile Money">Mobile Money (M-Pesa)</option>
                <option value="Card">Card</option>
                <option value="Cash">Cash</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div className="col-6">
              <label className="form-label-pg">Reference / Ref #</label>
              <input
                type="text"
                className="form-control form-control-pg font-mono"
                placeholder="WIRE-1234 or STRIPE-..."
                value={referenceNumber}
                onChange={(e) => setReferenceNumber(e.target.value)}
              />
            </div>
          </div>

          {/* Notes */}
          <div>
            <label className="form-label-pg">Payment Notes</label>
            <input
              type="text"
              className="form-control form-control-pg"
              placeholder="e.g. 50% project deposit, cleared at bank"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
            />
          </div>

          {/* Actions */}
          <div className="d-flex align-items-center justify-content-end gap-2 pt-2 border-t border-slate-100">
            <button
              type="button"
              onClick={onClose}
              className="btn btn-sm btn-pg-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="btn btn-sm btn-success d-flex align-items-center gap-1.5"
            >
              <Save className="w-4 h-4" />
              <span>Record & Reconcile</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
