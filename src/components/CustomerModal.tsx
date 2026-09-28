import React, { useState } from 'react';
import { useBilling } from '../context/BillingContext';
import { Customer } from '../types';
import { X, UserPlus, Save } from 'lucide-react';

interface CustomerModalProps {
  customerId?: number | null;
  onClose: () => void;
}

export const CustomerModal: React.FC<CustomerModalProps> = ({ customerId, onClose }) => {
  const { customers, createCustomer, updateCustomer } = useBilling();

  const isEdit = Boolean(customerId);
  const existing = customerId ? customers.find((c) => c.id === customerId) : null;

  const [name, setName] = useState(existing?.name || '');
  const [company, setCompany] = useState(existing?.company || '');
  const [email, setEmail] = useState(existing?.email || '');
  const [phone, setPhone] = useState(existing?.phone || '');
  const [address, setAddress] = useState(existing?.address || '');
  const [taxNumber, setTaxNumber] = useState(existing?.tax_number || '');
  const [notes, setNotes] = useState(existing?.notes || '');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim()) {
      alert('Customer name is required');
      return;
    }

    if (isEdit && customerId) {
      updateCustomer(customerId, {
        name,
        company,
        email,
        phone,
        address,
        tax_number: taxNumber,
        notes,
      });
    } else {
      createCustomer({
        user_id: 1,
        name,
        company,
        email,
        phone,
        address,
        tax_number: taxNumber,
        notes,
      });
    }

    onClose();
  };

  return (
    <div
      className="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-3"
      style={{ zIndex: 1060, backgroundColor: 'rgba(15, 23, 42, 0.65)' }}
    >
      <div className="bg-white rounded-xl shadow-2xl max-w-lg w-100 overflow-hidden border border-slate-200">
        <div className="px-4 py-3 bg-slate-900 text-white d-flex align-items-center justify-content-between">
          <div className="d-flex align-items-center gap-2">
            <div className="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 d-flex align-items-center justify-content-center">
              <UserPlus className="w-4 h-4 text-amber-400" />
            </div>
            <div>
              <div className="fw-bold text-sm">
                {isEdit ? `Edit Client: ${existing?.name}` : 'Add New Client'}
              </div>
              <div className="text-[11px] text-slate-400">Customer contact & billing profile</div>
            </div>
          </div>
          <button
            onClick={onClose}
            className="btn btn-sm btn-link text-slate-400 hover:text-white p-0"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-4 space-y-3">
          <div className="row g-2">
            <div className="col-sm-6">
              <label className="form-label-pg">Client Full Name *</label>
              <input
                type="text"
                className="form-control form-control-pg"
                placeholder="Marcus Thorne"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
              />
            </div>
            <div className="col-sm-6">
              <label className="form-label-pg">Company / Organization</label>
              <input
                type="text"
                className="form-control form-control-pg"
                placeholder="Aura Architectures Inc"
                value={company}
                onChange={(e) => setCompany(e.target.value)}
              />
            </div>
          </div>

          <div className="row g-2">
            <div className="col-sm-6">
              <label className="form-label-pg">Email Address *</label>
              <input
                type="email"
                className="form-control form-control-pg"
                placeholder="client@company.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>
            <div className="col-sm-6">
              <label className="form-label-pg">Phone Number</label>
              <input
                type="tel"
                className="form-control form-control-pg"
                placeholder="+1 (555) 012-3456"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
              />
            </div>
          </div>

          <div>
            <label className="form-label-pg">Billing Address</label>
            <textarea
              className="form-control form-control-pg text-xs"
              rows={2}
              placeholder="Full street address, City, State, Postal code, Country"
              value={address}
              onChange={(e) => setAddress(e.target.value)}
            />
          </div>

          <div>
            <label className="form-label-pg">Tax ID / PIN Number</label>
            <input
              type="text"
              className="form-control form-control-pg font-mono text-xs"
              placeholder="e.g. TAX-CA-9921 or P051234567Z"
              value={taxNumber}
              onChange={(e) => setTaxNumber(e.target.value)}
            />
          </div>

          <div>
            <label className="form-label-pg">Customer Notes / Account Terms</label>
            <input
              type="text"
              className="form-control form-control-pg text-xs"
              placeholder="e.g. Requires PO reference on all invoices."
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
            />
          </div>

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
              className="btn btn-sm btn-pg-primary d-flex align-items-center gap-1.5"
            >
              <Save className="w-4 h-4" />
              <span>{isEdit ? 'Update Client' : 'Save Client'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
