import React, { useState } from 'react';
import {
  FileText,
  Receipt,
  Database,
  Terminal,
  ExternalLink,
  ShieldCheck,
  RefreshCw,
  Layers,
  Code2,
  CheckCircle2,
  Copy,
  Check,
  BookOpen
} from 'lucide-react';

export default function App() {
  const [activeTab, setActiveTab] = useState<'app' | 'schema' | 'architecture' | 'composer'>('app');
  const [copiedText, setCopiedText] = useState<string | null>(null);
  const [iframeKey, setIframeKey] = useState<number>(1);

  const copyToClipboard = (text: string, label: string) => {
    navigator.clipboard.writeText(text);
    setCopiedText(label);
    setTimeout(() => setCopiedText(null), 2000);
  };

  return (
    <div className="flex flex-col h-screen bg-slate-900 text-slate-100 font-sans">
      {/* Top PaperGlow Header Bar */}
      <header className="flex-none bg-slate-950 border-b border-slate-800 px-4 py-2.5 flex items-center justify-between z-20">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center font-black text-white shadow-md shadow-amber-500/20 text-base">
            P
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="font-extrabold text-white text-base tracking-tight">
                Paper<span className="text-amber-500">Glow</span>
              </span>
              <span className="text-xs bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full font-medium">
                PHP 8.2 &bull; MySQL 8 &bull; Dompdf
              </span>
            </div>
            <p className="text-[11px] text-slate-400 hidden sm:block">
              Server-Rendered Invoice & Quotation Management Platform
            </p>
          </div>
        </div>

        {/* View Switcher Tabs */}
        <div className="flex items-center bg-slate-900 border border-slate-800 rounded-lg p-1 text-xs">
          <button
            onClick={() => setActiveTab('app')}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md font-medium transition-all ${
              activeTab === 'app'
                ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm'
                : 'text-slate-400 hover:text-slate-200'
            }`}
          >
            <Receipt className="w-3.5 h-3.5" />
            <span>Live System</span>
          </button>
          <button
            onClick={() => setActiveTab('schema')}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md font-medium transition-all ${
              activeTab === 'schema'
                ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm'
                : 'text-slate-400 hover:text-slate-200'
            }`}
          >
            <Database className="w-3.5 h-3.5" />
            <span>MySQL Schema</span>
          </button>
          <button
            onClick={() => setActiveTab('composer')}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md font-medium transition-all ${
              activeTab === 'composer'
                ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm'
                : 'text-slate-400 hover:text-slate-200'
            }`}
          >
            <FileText className="w-3.5 h-3.5" />
            <span>Dompdf & Setup</span>
          </button>
          <button
            onClick={() => setActiveTab('architecture')}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md font-medium transition-all ${
              activeTab === 'architecture'
                ? 'bg-amber-500 text-slate-950 font-semibold shadow-sm'
                : 'text-slate-400 hover:text-slate-200'
            }`}
          >
            <Layers className="w-3.5 h-3.5" />
            <span>Architecture</span>
          </button>
        </div>

        {/* Quick Actions */}
        <div className="flex items-center gap-2">
          {activeTab === 'app' && (
            <button
              onClick={() => setIframeKey((k) => k + 1)}
              title="Reload PHP Session"
              className="p-1.5 text-slate-400 hover:text-white bg-slate-900 border border-slate-800 rounded-md transition-colors"
            >
              <RefreshCw className="w-3.5 h-3.5" />
            </button>
          )}
          <a
            href="/dashboard/index.php"
            target="_blank"
            rel="noreferrer"
            className="flex items-center gap-1 px-2.5 py-1.5 text-xs text-slate-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-slate-700/60 rounded-md transition-all font-medium"
          >
            <span>Open Tab</span>
            <ExternalLink className="w-3 h-3 text-slate-400" />
          </a>
        </div>
      </header>

      {/* Main Content Area */}
      <div className="flex-1 relative overflow-hidden bg-slate-100">
        {activeTab === 'app' && (
          <iframe
            key={iframeKey}
            src="/dashboard/index.php"
            title="PaperGlow Billing Live PHP Application"
            className="w-full h-full border-0 bg-white"
          />
        )}

        {activeTab === 'schema' && (
          <div className="w-full h-full overflow-y-auto p-6 bg-slate-900 text-slate-200">
            <div className="max-w-5xl mx-auto space-y-6">
              <div className="flex items-center justify-between">
                <div>
                  <h2 className="text-xl font-bold text-white flex items-center gap-2">
                    <Database className="w-5 h-5 text-amber-500" />
                    MySQL 8+ Normalized Database Specification
                  </h2>
                  <p className="text-sm text-slate-400">
                    File: <code className="text-amber-400 bg-slate-950 px-1.5 py-0.5 rounded">/database.sql</code> (InnoDB, utf8mb4_unicode_ci, Strict Foreign Keys, Transactions)
                  </p>
                </div>
                <button
                  onClick={() => copyToClipboard('mysql -u root -p paperglow_billing < database.sql', 'sql_import')}
                  className="flex items-center gap-1.5 text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg border border-slate-700 transition-colors"
                >
                  {copiedText === 'sql_import' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                  <span>Copy Import Command</span>
                </button>
              </div>

              {/* Tables Breakdown Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="bg-slate-950 border border-slate-800 rounded-xl p-4">
                  <div className="flex items-center justify-between pb-2 border-b border-slate-800 mb-3">
                    <span className="font-mono text-sm font-bold text-amber-400">users</span>
                    <span className="text-[11px] text-slate-400">Authentication & Roles</span>
                  </div>
                  <ul className="text-xs space-y-1 font-mono text-slate-300">
                    <li><span className="text-slate-500">PK:</span> id (INT UNSIGNED AUTO_INCREMENT)</li>
                    <li>name (VARCHAR 100)</li>
                    <li><span className="text-amber-400">UNIQUE:</span> email (VARCHAR 150)</li>
                    <li>password (VARCHAR 255 - BCRYPT)</li>
                    <li>role (ENUM: admin, manager, staff)</li>
                    <li>status (ENUM: active, inactive)</li>
                    <li>created_at, updated_at (TIMESTAMP)</li>
                  </ul>
                </div>

                <div className="bg-slate-950 border border-slate-800 rounded-xl p-4">
                  <div className="flex items-center justify-between pb-2 border-b border-slate-800 mb-3">
                    <span className="font-mono text-sm font-bold text-amber-400">company_settings</span>
                    <span className="text-[11px] text-slate-400">Brand & Remittance</span>
                  </div>
                  <ul className="text-xs space-y-1 font-mono text-slate-300">
                    <li><span className="text-slate-500">PK:</span> id (INT UNSIGNED)</li>
                    <li><span className="text-amber-400">FK:</span> user_id &rarr; users(id) ON DELETE CASCADE</li>
                    <li>company_name, logo, address, phone, email</li>
                    <li>tax_number, currency (DEFAULT 'USD')</li>
                    <li>bank_name, bank_account_number, swift, routing</li>
                    <li>mobile_money_name, mobile_money_number</li>
                    <li>default_invoice_terms, default_quotation_terms</li>
                  </ul>
                </div>

                <div className="bg-slate-950 border border-slate-800 rounded-xl p-4">
                  <div className="flex items-center justify-between pb-2 border-b border-slate-800 mb-3">
                    <span className="font-mono text-sm font-bold text-amber-400">customers</span>
                    <span className="text-[11px] text-slate-400">Client Organizations</span>
                  </div>
                  <ul className="text-xs space-y-1 font-mono text-slate-300">
                    <li><span className="text-slate-500">PK:</span> id (INT UNSIGNED)</li>
                    <li><span className="text-amber-400">FK:</span> user_id &rarr; users(id)</li>
                    <li>name (VARCHAR 150), company, email, phone</li>
                    <li>address (TEXT), tax_number, notes</li>
                    <li>INDEX(user_id), INDEX(name), INDEX(email)</li>
                  </ul>
                </div>

                <div className="bg-slate-950 border border-slate-800 rounded-xl p-4">
                  <div className="flex items-center justify-between pb-2 border-b border-slate-800 mb-3">
                    <span className="font-mono text-sm font-bold text-amber-400">quotations & items</span>
                    <span className="text-[11px] text-slate-400">Proposals & Estimations</span>
                  </div>
                  <ul className="text-xs space-y-1 font-mono text-slate-300">
                    <li>quotation_number (UNIQUE VARCHAR 50, eg QUO-2026-0001)</li>
                    <li>status: Draft, Sent, Accepted, Rejected, Expired</li>
                    <li>subtotal, discount_total, tax_total, grand_total (DECIMAL 12,2)</li>
                    <li>converted_invoice_id (INT NULL)</li>
                    <li>quotation_items: qty, unit_price, discount, tax_rate, tax_amount, line_total</li>
                  </ul>
                </div>

                <div className="bg-slate-950 border border-slate-800 rounded-xl p-4">
                  <div className="flex items-center justify-between pb-2 border-b border-slate-800 mb-3">
                    <span className="font-mono text-sm font-bold text-amber-400">invoices & items</span>
                    <span className="text-[11px] text-slate-400">Formal Receivables</span>
                  </div>
                  <ul className="text-xs space-y-1 font-mono text-slate-300">
                    <li>invoice_number (UNIQUE VARCHAR 50, eg INV-2026-0001)</li>
                    <li>status: Draft, Unpaid, Partially Paid, Paid, Overdue, Cancelled</li>
                    <li>grand_total, paid_amount, balance (DECIMAL 12,2)</li>
                    <li>due_date, invoice_date</li>
                    <li>invoice_items: line totals with exact tax calculations</li>
                  </ul>
                </div>

                <div className="bg-slate-950 border border-slate-800 rounded-xl p-4">
                  <div className="flex items-center justify-between pb-2 border-b border-slate-800 mb-3">
                    <span className="font-mono text-sm font-bold text-amber-400">payments</span>
                    <span className="text-[11px] text-slate-400">Collection Receipts</span>
                  </div>
                  <ul className="text-xs space-y-1 font-mono text-slate-300">
                    <li><span className="text-amber-400">FK:</span> invoice_id &rarr; invoices(id) ON DELETE CASCADE</li>
                    <li>amount (DECIMAL 12,2) with overpayment prevention</li>
                    <li>payment_method: Bank Transfer, Card, Mobile Money, Cash, Other</li>
                    <li>reference_number, notes, payment_date</li>
                    <li>Automated balance recalculation & status triggers</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'composer' && (
          <div className="w-full h-full overflow-y-auto p-6 bg-slate-900 text-slate-200">
            <div className="max-w-4xl mx-auto space-y-6">
              <div>
                <h2 className="text-xl font-bold text-white flex items-center gap-2">
                  <FileText className="w-5 h-5 text-amber-500" />
                  Dompdf PDF Engine & Dependencies
                </h2>
                <p className="text-sm text-slate-400">
                  Production PDF generation instructions and setup verification.
                </p>
              </div>

              {/* Composer Box */}
              <div className="bg-slate-950 border border-slate-800 rounded-xl p-5 space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-semibold text-slate-200 flex items-center gap-2">
                    <Terminal className="w-4 h-4 text-emerald-400" />
                    Required Composer Installation Command:
                  </span>
                  <button
                    onClick={() => copyToClipboard('composer require dompdf/dompdf', 'composer_cmd')}
                    className="flex items-center gap-1.5 text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg border border-slate-700 transition-colors"
                  >
                    {copiedText === 'composer_cmd' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                    <span>Copy Command</span>
                  </button>
                </div>
                <pre className="bg-black/60 p-3 rounded-lg text-emerald-400 font-mono text-sm overflow-x-auto">
                  composer require dompdf/dompdf
                </pre>
                <div className="text-xs text-slate-400 flex items-center gap-1.5">
                  <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                  <span>Dompdf v3.1.6 is already installed in <code className="text-slate-300">/vendor/dompdf/dompdf</code> and autoloaded.</span>
                </div>
              </div>

              {/* PDF Features List */}
              <div className="bg-slate-950 border border-slate-800 rounded-xl p-5 space-y-4">
                <h3 className="text-sm font-bold text-white uppercase tracking-wider">
                  PaperGlow PDF Capabilities:
                </h3>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                  <div className="flex items-start gap-2 bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                    <CheckCircle2 className="w-4 h-4 text-amber-500 mt-0.5 flex-none" />
                    <div>
                      <div className="font-semibold text-white">Pixel-Crisp A4 Layout</div>
                      <div className="text-slate-400">Strict CSS2.1 tables and margins matching professional print specifications.</div>
                    </div>
                  </div>
                  <div className="flex items-start gap-2 bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                    <CheckCircle2 className="w-4 h-4 text-amber-500 mt-0.5 flex-none" />
                    <div>
                      <div className="font-semibold text-white">Full Bank & Mobile Money Remittance</div>
                      <div className="text-slate-400">Directly embeds company bank details, SWIFT, and mobile money merchant codes.</div>
                    </div>
                  </div>
                  <div className="flex items-start gap-2 bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                    <CheckCircle2 className="w-4 h-4 text-amber-500 mt-0.5 flex-none" />
                    <div>
                      <div className="font-semibold text-white">Multi-tenant Security</div>
                      <div className="text-slate-400">Validates user ownership before streaming PDFs to prevent IDOR vulnerabilities.</div>
                    </div>
                  </div>
                  <div className="flex items-start gap-2 bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                    <CheckCircle2 className="w-4 h-4 text-amber-500 mt-0.5 flex-none" />
                    <div>
                      <div className="font-semibold text-white">Sample PDF Quick Links</div>
                      <div className="text-slate-400 mt-1 flex gap-2">
                        <a href="/invoices/pdf.php?id=1" target="_blank" className="text-amber-400 hover:underline">Invoice #1 PDF &rarr;</a>
                        <a href="/quotations/pdf.php?id=1" target="_blank" className="text-amber-400 hover:underline">Quotation #1 PDF &rarr;</a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'architecture' && (
          <div className="w-full h-full overflow-y-auto p-6 bg-slate-900 text-slate-200">
            <div className="max-w-4xl mx-auto space-y-6">
              <div>
                <h2 className="text-xl font-bold text-white flex items-center gap-2">
                  <Layers className="w-5 h-5 text-amber-500" />
                  PaperGlow System Architecture & Security
                </h2>
                <p className="text-sm text-slate-400">
                  Clean procedural modular PHP architecture adhering to security and coding constraints.
                </p>
              </div>

              {/* Credentials Box */}
              <div className="bg-slate-950 border border-slate-800 rounded-xl p-5">
                <h3 className="text-sm font-bold text-white uppercase tracking-wider mb-3">
                  Default Demo Access Credentials
                </h3>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs font-mono">
                  <div className="bg-slate-900 p-3 rounded-lg border border-slate-800 flex justify-between items-center">
                    <div>
                      <div className="text-slate-400 text-[10px]">Email</div>
                      <div className="text-amber-400 font-semibold">admin@paperglow.com</div>
                    </div>
                    <button
                      onClick={() => copyToClipboard('admin@paperglow.com', 'email')}
                      className="text-slate-400 hover:text-white"
                    >
                      {copiedText === 'email' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                    </button>
                  </div>
                  <div className="bg-slate-900 p-3 rounded-lg border border-slate-800 flex justify-between items-center">
                    <div>
                      <div className="text-slate-400 text-[10px]">Password</div>
                      <div className="text-amber-400 font-semibold">Password123!</div>
                    </div>
                    <button
                      onClick={() => copyToClipboard('Password123!', 'pass')}
                      className="text-slate-400 hover:text-white"
                    >
                      {copiedText === 'pass' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                    </button>
                  </div>
                </div>
              </div>

              {/* Security Standards */}
              <div className="bg-slate-950 border border-slate-800 rounded-xl p-5 space-y-3">
                <h3 className="text-sm font-bold text-white uppercase tracking-wider">
                  Security Protections Enforced:
                </h3>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                  <div className="flex items-center gap-2">
                    <ShieldCheck className="w-4 h-4 text-emerald-400 flex-none" />
                    <span><strong>PDO Prepared Statements:</strong> 100% parametrized queries</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <ShieldCheck className="w-4 h-4 text-emerald-400 flex-none" />
                    <span><strong>CSRF Protection:</strong> Cryptographic token on all POST requests</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <ShieldCheck className="w-4 h-4 text-emerald-400 flex-none" />
                    <span><strong>Output Escaping:</strong> htmlspecialchars() ENT_QUOTES utf-8</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <ShieldCheck className="w-4 h-4 text-emerald-400 flex-none" />
                    <span><strong>Password Hashing:</strong> PASSWORD_BCRYPT via password_hash()</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <ShieldCheck className="w-4 h-4 text-emerald-400 flex-none" />
                    <span><strong>Atomic Transactions:</strong> Rollback on line item failures</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <ShieldCheck className="w-4 h-4 text-emerald-400 flex-none" />
                    <span><strong>Server-Side Math:</strong> Browser calculations are recomputed on server</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
