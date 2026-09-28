import React, { useState } from 'react';
import {
  Database,
  Copy,
  Check,
  Code2,
  Table,
  Layers,
  ShieldCheck,
  Server
} from 'lucide-react';

export const SchemaView: React.FC = () => {
  const [copied, setCopied] = useState<string | null>(null);

  const copyToClipboard = (text: string, id: string) => {
    navigator.clipboard.writeText(text);
    setCopied(id);
    setTimeout(() => setCopied(null), 2000);
  };

  const schemaTables = [
    {
      name: 'users',
      purpose: 'Multi-role authentication, passwords (bcrypt), and account state',
      columns: [
        { name: 'id', type: 'INT UNSIGNED AUTO_INCREMENT', key: 'PK' },
        { name: 'name', type: 'VARCHAR(100)', key: '' },
        { name: 'email', type: 'VARCHAR(150)', key: 'UNIQUE' },
        { name: 'password', type: 'VARCHAR(255)', key: '' },
        { name: 'role', type: "ENUM('admin', 'manager', 'staff')", key: '' },
        { name: 'status', type: "ENUM('active', 'inactive')", key: '' },
        { name: 'created_at, updated_at', type: 'TIMESTAMP', key: '' }
      ]
    },
    {
      name: 'company_settings',
      purpose: 'Corporate profile, logos, tax PIN, bank remittance & mobile money',
      columns: [
        { name: 'id', type: 'INT UNSIGNED AUTO_INCREMENT', key: 'PK' },
        { name: 'user_id', type: 'INT UNSIGNED', key: 'FK -> users.id' },
        { name: 'company_name', type: 'VARCHAR(150)', key: '' },
        { name: 'currency', type: "VARCHAR(10) DEFAULT 'KES'", key: '' },
        { name: 'bank_name, bank_account_number', type: 'VARCHAR(100)', key: '' },
        { name: 'bank_routing, bank_swift', type: 'VARCHAR(50)', key: '' },
        { name: 'mobile_money_name, mobile_money_number', type: 'VARCHAR(100)', key: '' },
        { name: 'default_invoice_terms, default_quotation_terms', type: 'TEXT', key: '' }
      ]
    },
    {
      name: 'customers',
      purpose: 'Client organizations, billing contacts, and accounting notes',
      columns: [
        { name: 'id', type: 'INT UNSIGNED AUTO_INCREMENT', key: 'PK' },
        { name: 'user_id', type: 'INT UNSIGNED', key: 'FK -> users.id' },
        { name: 'name', type: 'VARCHAR(150)', key: 'INDEX' },
        { name: 'company', type: 'VARCHAR(150)', key: '' },
        { name: 'email', type: 'VARCHAR(150)', key: 'INDEX' },
        { name: 'phone', type: 'VARCHAR(50)', key: '' },
        { name: 'address', type: 'TEXT', key: '' },
        { name: 'tax_number', type: 'VARCHAR(50)', key: '' }
      ]
    },
    {
      name: 'quotations',
      purpose: 'Scope estimates with line totals and 1-click invoice conversion',
      columns: [
        { name: 'id', type: 'INT UNSIGNED AUTO_INCREMENT', key: 'PK' },
        { name: 'user_id', type: 'INT UNSIGNED', key: 'FK -> users.id' },
        { name: 'customer_id', type: 'INT UNSIGNED', key: 'FK -> customers.id' },
        { name: 'quotation_number', type: 'VARCHAR(50)', key: 'UNIQUE' },
        { name: 'quotation_date, expiry_date', type: 'DATE', key: 'INDEX' },
        { name: 'status', type: "ENUM('Draft', 'Sent', 'Accepted', 'Rejected', 'Expired')", key: '' },
        { name: 'subtotal, discount_total, tax_total, grand_total', type: 'DECIMAL(12,2)', key: '' },
        { name: 'converted_invoice_id', type: 'INT UNSIGNED NULL', key: '' }
      ]
    },
    {
      name: 'quotation_items',
      purpose: 'Individual scoped deliverables with quantity, discounts, and tax rates',
      columns: [
        { name: 'id', type: 'INT UNSIGNED AUTO_INCREMENT', key: 'PK' },
        { name: 'quotation_id', type: 'INT UNSIGNED', key: 'FK -> quotations.id CASCADE' },
        { name: 'description', type: 'VARCHAR(255)', key: '' },
        { name: 'quantity', type: 'DECIMAL(10,2)', key: '' },
        { name: 'unit_price, discount, tax_amount, line_total', type: 'DECIMAL(12,2)', key: '' },
        { name: 'tax_rate', type: 'DECIMAL(5,2)', key: '' }
      ]
    },
    {
      name: 'invoices',
      purpose: 'Billing documents with auto-computed balances and payment status',
      columns: [
        { name: 'id', type: 'INT UNSIGNED AUTO_INCREMENT', key: 'PK' },
        { name: 'user_id', type: 'INT UNSIGNED', key: 'FK -> users.id' },
        { name: 'customer_id', type: 'INT UNSIGNED', key: 'FK -> customers.id' },
        { name: 'from_quotation_id', type: 'INT UNSIGNED NULL', key: '' },
        { name: 'invoice_number', type: 'VARCHAR(50)', key: 'UNIQUE' },
        { name: 'invoice_date, due_date', type: 'DATE', key: 'INDEX' },
        { name: 'status', type: "ENUM('Draft', 'Unpaid', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled')", key: '' },
        { name: 'subtotal, discount_total, tax_total, grand_total', type: 'DECIMAL(12,2)', key: '' },
        { name: 'paid_amount, balance', type: 'DECIMAL(12,2)', key: '' }
      ]
    },
    {
      name: 'invoice_items',
      purpose: 'Billed items and line calculations linked directly to parent invoice',
      columns: [
        { name: 'id', type: 'INT UNSIGNED AUTO_INCREMENT', key: 'PK' },
        { name: 'invoice_id', type: 'INT UNSIGNED', key: 'FK -> invoices.id CASCADE' },
        { name: 'description', type: 'VARCHAR(255)', key: '' },
        { name: 'quantity, unit_price, discount, tax_rate, line_total', type: 'DECIMAL', key: '' }
      ]
    },
    {
      name: 'payments',
      purpose: 'Payment ledger entries that automatically reduce invoice balance',
      columns: [
        { name: 'id', type: 'INT UNSIGNED AUTO_INCREMENT', key: 'PK' },
        { name: 'user_id', type: 'INT UNSIGNED', key: 'FK -> users.id' },
        { name: 'invoice_id', type: 'INT UNSIGNED', key: 'FK -> invoices.id CASCADE' },
        { name: 'payment_date', type: 'DATE', key: 'INDEX' },
        { name: 'amount', type: 'DECIMAL(12,2)', key: '' },
        { name: 'payment_method', type: "ENUM('Cash', 'Bank Transfer', 'Mobile Money', 'Card', 'Other')", key: '' },
        { name: 'reference_number', type: 'VARCHAR(100)', key: '' }
      ]
    }
  ];

  return (
    <div className="space-y-6">
      <div className="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <h2 className="h5 fw-bold text-slate-900 mb-1 d-flex align-items-center gap-2">
            <Database className="w-5 h-5 text-amber-500" />
            MySQL 8+ Normalized Architecture Specification
          </h2>
          <p className="text-xs text-slate-500 mb-0">
            Source blueprint: <code className="text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded font-mono">database.sql</code> (InnoDB engine, utf8mb4_unicode_ci, strict relational integrity)
          </p>
        </div>

        <button
          onClick={() => copyToClipboard('mysql -u root -p paperglow_billing < database.sql', 'sql_cmd')}
          className="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1.5 text-xs font-mono self-start"
        >
          {copied === 'sql_cmd' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
          <span>Copy MySQL CLI Import</span>
        </button>
      </div>

      {/* Tables Grid */}
      <div className="row g-3">
        {schemaTables.map((t) => (
          <div key={t.name} className="col-lg-6">
            <div className="pg-card p-4 h-100">
              <div className="d-flex align-items-center justify-content-between pb-2 border-b border-slate-200 mb-3">
                <span className="font-mono text-sm font-bold text-amber-600 d-flex align-items-center gap-1.5">
                  <Table className="w-4 h-4 text-slate-400" />
                  {t.name}
                </span>
                <span className="text-[11px] text-slate-500">{t.purpose}</span>
              </div>

              <div className="table-responsive">
                <table className="table table-sm table-borderless text-xs mb-0 font-mono">
                  <tbody>
                    {t.columns.map((col, idx) => (
                      <tr key={idx} className="border-b border-slate-100">
                        <td className="text-slate-900 fw-semibold">{col.name}</td>
                        <td className="text-slate-500 text-[11px]">{col.type}</td>
                        <td className="text-end">
                          {col.key && (
                            <span
                              className={`badge text-[10px] ${
                                col.key.includes('PK')
                                  ? 'bg-amber-100 text-amber-800'
                                  : col.key.includes('FK')
                                  ? 'bg-indigo-100 text-indigo-800'
                                  : 'bg-slate-100 text-slate-700'
                              }`}
                            >
                              {col.key}
                            </span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};
