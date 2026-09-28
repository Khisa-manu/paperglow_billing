import React, { useState } from 'react';
import { useBilling } from '../context/BillingContext';
import {
  Building2,
  CreditCard,
  Phone,
  Mail,
  Globe,
  FileText,
  Save,
  RotateCcw,
  Check,
  ShieldCheck
} from 'lucide-react';

export const CompanySettingsView: React.FC = () => {
  const { company, updateCompanySettings, resetToSeedData } = useBilling();

  const [companyName, setCompanyName] = useState(company.company_name);
  const [taxNumber, setTaxNumber] = useState(company.tax_number);
  const [currency, setCurrency] = useState(company.currency);
  const [email, setEmail] = useState(company.email);
  const [phone, setPhone] = useState(company.phone);
  const [website, setWebsite] = useState(company.website);
  const [address, setAddress] = useState(company.address);

  // Remittance
  const [bankName, setBankName] = useState(company.bank_name);
  const [bankAccountName, setBankAccountName] = useState(company.bank_account_name);
  const [bankAccountNumber, setBankAccountNumber] = useState(company.bank_account_number);
  const [bankRouting, setBankRouting] = useState(company.bank_routing);
  const [bankSwift, setBankSwift] = useState(company.bank_swift);

  const [mobileMoneyName, setMobileMoneyName] = useState(company.mobile_money_name);
  const [mobileMoneyNumber, setMobileMoneyNumber] = useState(company.mobile_money_number);

  // Defaults
  const [defaultInvoiceTerms, setDefaultInvoiceTerms] = useState(company.default_invoice_terms);
  const [defaultQuotationTerms, setDefaultQuotationTerms] = useState(company.default_quotation_terms);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateCompanySettings({
      company_name: companyName,
      tax_number: taxNumber,
      currency,
      email,
      phone,
      website,
      address,
      bank_name: bankName,
      bank_account_name: bankAccountName,
      bank_account_number: bankAccountNumber,
      bank_routing: bankRouting,
      bank_swift: bankSwift,
      mobile_money_name: mobileMoneyName,
      mobile_money_number: mobileMoneyNumber,
      default_invoice_terms: defaultInvoiceTerms,
      default_quotation_terms: defaultQuotationTerms,
    });
  };

  return (
    <div className="space-y-6 max-w-4xl">
      <div>
        <h2 className="h5 fw-bold text-slate-900 mb-1">Company & Billing Remittance Settings</h2>
        <p className="text-xs text-slate-500 mb-0">
          Configure corporate branding, banking instructions, tax IDs, and default contracts.
        </p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Business Profile */}
        <div className="pg-card p-4">
          <h3 className="h6 fw-bold text-slate-900 mb-3 d-flex align-items-center gap-2">
            <Building2 className="w-4 h-4 text-amber-500" />
            Corporate Identity
          </h3>

          <div className="row g-3">
            <div className="col-md-6">
              <label className="form-label-pg">Company / Business Name *</label>
              <input
                type="text"
                className="form-control form-control-pg"
                value={companyName}
                onChange={(e) => setCompanyName(e.target.value)}
                required
              />
            </div>

            <div className="col-md-3">
              <label className="form-label-pg">Currency Code *</label>
              <select
                className="form-select form-select-pg font-mono"
                value={currency}
                onChange={(e) => setCurrency(e.target.value)}
              >
                <option value="KES">KES (Kenya Shilling)</option>
                <option value="USD">USD ($ - US Dollar)</option>
                <option value="EUR">EUR (€ - Euro)</option>
                <option value="GBP">GBP (£ - British Pound)</option>
                <option value="CAD">CAD (C$ - Canadian Dollar)</option>
                <option value="AUD">AUD (A$ - Australian Dollar)</option>
                <option value="ZAR">ZAR (R - South African Rand)</option>
                <option value="UGX">UGX (Uganda Shilling)</option>
                <option value="TZS">TZS (Tanzania Shilling)</option>
              </select>
            </div>

            <div className="col-md-3">
              <label className="form-label-pg">Tax / PIN Number</label>
              <input
                type="text"
                className="form-control form-control-pg font-mono"
                value={taxNumber}
                onChange={(e) => setTaxNumber(e.target.value)}
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">Official Billing Email</label>
              <input
                type="email"
                className="form-control form-control-pg"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">Telephone / Support Line</label>
              <input
                type="text"
                className="form-control form-control-pg"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">Website URL</label>
              <input
                type="text"
                className="form-control form-control-pg"
                value={website}
                onChange={(e) => setWebsite(e.target.value)}
              />
            </div>

            <div className="col-12">
              <label className="form-label-pg">Physical / Mailing Address</label>
              <textarea
                className="form-control form-control-pg text-xs"
                rows={2}
                value={address}
                onChange={(e) => setAddress(e.target.value)}
              />
            </div>
          </div>
        </div>

        {/* Banking & Remittance */}
        <div className="pg-card p-4">
          <h3 className="h6 fw-bold text-slate-900 mb-3 d-flex align-items-center gap-2">
            <CreditCard className="w-4 h-4 text-emerald-500" />
            Remittance & Banking Instructions
          </h3>
          <p className="text-xs text-slate-500 mb-3">
            These instructions are automatically rendered at the bottom of all customer invoices.
          </p>

          <div className="row g-3">
            <div className="col-md-6">
              <label className="form-label-pg">Bank Institution Name</label>
              <input
                type="text"
                className="form-control form-control-pg"
                placeholder="e.g. Cascade Horizon Bank"
                value={bankName}
                onChange={(e) => setBankName(e.target.value)}
              />
            </div>

            <div className="col-md-6">
              <label className="form-label-pg">Account Holder Name</label>
              <input
                type="text"
                className="form-control form-control-pg"
                placeholder="e.g. PaperGlow Studio LLC"
                value={bankAccountName}
                onChange={(e) => setBankAccountName(e.target.value)}
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">Bank Account Number</label>
              <input
                type="text"
                className="form-control form-control-pg font-mono"
                value={bankAccountNumber}
                onChange={(e) => setBankAccountNumber(e.target.value)}
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">Routing Number</label>
              <input
                type="text"
                className="form-control form-control-pg font-mono"
                value={bankRouting}
                onChange={(e) => setBankRouting(e.target.value)}
              />
            </div>

            <div className="col-md-4">
              <label className="form-label-pg">SWIFT / BIC Code</label>
              <input
                type="text"
                className="form-control form-control-pg font-mono"
                value={bankSwift}
                onChange={(e) => setBankSwift(e.target.value)}
              />
            </div>

            <div className="col-md-6 pt-2">
              <label className="form-label-pg">Mobile Money Provider / Service Name</label>
              <input
                type="text"
                className="form-control form-control-pg"
                placeholder="e.g. PaperGlow Pay / M-Pesa Till"
                value={mobileMoneyName}
                onChange={(e) => setMobileMoneyName(e.target.value)}
              />
            </div>

            <div className="col-md-6 pt-2">
              <label className="form-label-pg">Mobile Money Number / Till / Paybill</label>
              <input
                type="text"
                className="form-control form-control-pg font-mono"
                placeholder="+254 700 000000 or Till 5550199"
                value={mobileMoneyNumber}
                onChange={(e) => setMobileMoneyNumber(e.target.value)}
              />
            </div>
          </div>
        </div>

        {/* Default Contract Terms */}
        <div className="pg-card p-4">
          <h3 className="h6 fw-bold text-slate-900 mb-3 d-flex align-items-center gap-2">
            <FileText className="w-4 h-4 text-indigo-500" />
            Standard Document Terms
          </h3>

          <div className="row g-3">
            <div className="col-12">
              <label className="form-label-pg">Default Invoice Terms & Conditions</label>
              <textarea
                className="form-control form-control-pg text-xs"
                rows={2}
                value={defaultInvoiceTerms}
                onChange={(e) => setDefaultInvoiceTerms(e.target.value)}
              />
            </div>

            <div className="col-12">
              <label className="form-label-pg">Default Quotation Validity Terms</label>
              <textarea
                className="form-control form-control-pg text-xs"
                rows={2}
                value={defaultQuotationTerms}
                onChange={(e) => setDefaultQuotationTerms(e.target.value)}
              />
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="d-flex align-items-center justify-content-between pt-2">
          <button
            type="button"
            onClick={() => {
              if (window.confirm('Clear all invoices, quotations, payments, and customers from the system?')) {
                resetToSeedData();
              }
            }}
            className="btn btn-sm btn-outline-danger d-flex align-items-center gap-1.5"
          >
            <RotateCcw className="w-3.5 h-3.5" />
            <span>Clear All Records</span>
          </button>

          <button
            type="submit"
            className="btn btn-pg-primary d-flex align-items-center gap-1.5"
          >
            <Save className="w-4 h-4" />
            <span>Save Preferences</span>
          </button>
        </div>
      </form>
    </div>
  );
};
