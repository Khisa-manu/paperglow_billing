import React from 'react';
import { useBilling } from '../context/BillingContext';
import {
  BarChart3,
  TrendingUp,
  DollarSign,
  PieChart,
  Users,
  CheckCircle2,
  Clock,
  AlertTriangle,
  Receipt
} from 'lucide-react';

export const ReportsView: React.FC = () => {
  const {
    invoices,
    payments,
    customers,
    formatCurrency,
    getCustomerById
  } = useBilling();

  // Financial aggregates
  const totalBilled = invoices.reduce((sum, i) => sum + Number(i.grand_total || 0), 0);
  const totalCollected = invoices.reduce((sum, i) => sum + Number(i.paid_amount || 0), 0);
  const totalOutstanding = invoices.reduce((sum, i) => sum + Number(i.balance || 0), 0);
  const totalTax = invoices.reduce((sum, i) => sum + Number(i.tax_total || 0), 0);
  const totalDiscounts = invoices.reduce((sum, i) => sum + Number(i.discount_total || 0), 0);

  const collectionRate = totalBilled > 0 ? Math.round((totalCollected / totalBilled) * 100) : 0;

  // Breakdown by payment method
  const methodTotals: Record<string, number> = {};
  payments.forEach((p) => {
    methodTotals[p.payment_method] = (methodTotals[p.payment_method] || 0) + Number(p.amount);
  });

  // Top Customers by Revenue
  const customerBilled: Record<number, { billed: number; paid: number; outstanding: number }> = {};
  invoices.forEach((inv) => {
    if (!customerBilled[inv.customer_id]) {
      customerBilled[inv.customer_id] = { billed: 0, paid: 0, outstanding: 0 };
    }
    customerBilled[inv.customer_id].billed += Number(inv.grand_total);
    customerBilled[inv.customer_id].paid += Number(inv.paid_amount);
    customerBilled[inv.customer_id].outstanding += Number(inv.balance);
  });

  const sortedCustomerRankings = Object.entries(customerBilled)
    .map(([cid, data]) => ({
      customer: getCustomerById(Number(cid)),
      ...data,
    }))
    .sort((a, b) => b.billed - a.billed);

  // Status breakdown
  const statusStats = {
    Paid: invoices.filter((i) => i.status === 'Paid'),
    'Partially Paid': invoices.filter((i) => i.status === 'Partially Paid'),
    Unpaid: invoices.filter((i) => i.status === 'Unpaid'),
    Overdue: invoices.filter((i) => i.status === 'Overdue'),
    Draft: invoices.filter((i) => i.status === 'Draft'),
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h2 className="h5 fw-bold text-slate-900 mb-1">Financial Intelligence & Executive Reports</h2>
        <p className="text-xs text-slate-500 mb-0">
          Reconciliation metrics, tax reporting, cash flow analysis, and client ranking.
        </p>
      </div>

      {/* Primary KPI Row */}
      <div className="row g-3">
        <div className="col-xl-3 col-sm-6">
          <div className="pg-stat-card">
            <div className="text-muted small fw-semibold text-uppercase text-[11px] mb-1">
              Gross Invoiced Sales
            </div>
            <div className="h3 fw-bold text-slate-900 mono-num mb-1">
              {formatCurrency(totalBilled)}
            </div>
            <div className="text-xs text-slate-500">
              Across {invoices.length} issued invoices
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-sm-6">
          <div className="pg-stat-card">
            <div className="text-muted small fw-semibold text-uppercase text-[11px] mb-1">
              Actual Cash Collected
            </div>
            <div className="h3 fw-bold text-emerald-600 mono-num mb-1">
              {formatCurrency(totalCollected)}
            </div>
            <div className="text-xs text-emerald-700 fw-semibold">
              {collectionRate}% Overall Settlement Rate
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-sm-6">
          <div className="pg-stat-card">
            <div className="text-muted small fw-semibold text-uppercase text-[11px] mb-1">
              Accounts Receivable
            </div>
            <div className="h3 fw-bold text-rose-600 mono-num mb-1">
              {formatCurrency(totalOutstanding)}
            </div>
            <div className="text-xs text-slate-500">
              Pending collections
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-sm-6">
          <div className="pg-stat-card">
            <div className="text-muted small fw-semibold text-uppercase text-[11px] mb-1">
              Sales Tax Liability
            </div>
            <div className="h3 fw-bold text-slate-800 mono-num mb-1">
              {formatCurrency(totalTax)}
            </div>
            <div className="text-xs text-slate-500">
              Tax assessed on invoices
            </div>
          </div>
        </div>
      </div>

      {/* Two Column Section */}
      <div className="row g-4">
        {/* Top Clients by Revenue */}
        <div className="col-lg-7">
          <div className="pg-card p-4 h-100">
            <div className="d-flex align-items-center justify-content-between mb-3">
              <h3 className="h6 fw-bold mb-0 text-slate-900 d-flex align-items-center gap-2">
                <Users className="w-4 h-4 text-amber-500" />
                Client Account Rankings
              </h3>
              <span className="text-xs text-slate-400">By Total Invoiced</span>
            </div>

            <div className="table-responsive">
              <table className="table table-sm table-pg mb-0">
                <thead>
                  <tr>
                    <th>Client Organization</th>
                    <th className="text-end">Total Billed</th>
                    <th className="text-end">Settled</th>
                    <th className="text-end">Balance</th>
                  </tr>
                </thead>
                <tbody>
                  {sortedCustomerRankings.map((row, idx) => (
                    <tr key={idx}>
                      <td>
                        <div className="fw-semibold text-slate-900 text-xs">
                          {row.customer?.name}
                        </div>
                        <div className="text-[11px] text-slate-400">
                          {row.customer?.company || '-'}
                        </div>
                      </td>
                      <td className="text-end font-mono fw-bold text-slate-900 text-xs">
                        {formatCurrency(row.billed)}
                      </td>
                      <td className="text-end font-mono text-emerald-600 text-xs">
                        {formatCurrency(row.paid)}
                      </td>
                      <td className="text-end font-mono text-xs">
                        {row.outstanding > 0 ? (
                          <span className="text-rose-600 fw-bold">{formatCurrency(row.outstanding)}</span>
                        ) : (
                          <span className="text-emerald-600">{formatCurrency(0)}</span>
                        )}
                      </td>
                    </tr>
                  ))}
                  {sortedCustomerRankings.length === 0 && (
                    <tr>
                      <td colSpan={4} className="text-center py-3 text-muted text-xs">
                        No billing data yet.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Payment Methods & Tax Summary */}
        <div className="col-lg-5 space-y-4">
          <div className="pg-card p-4">
            <h3 className="h6 fw-bold mb-3 text-slate-900 d-flex align-items-center gap-2">
              <PieChart className="w-4 h-4 text-emerald-500" />
              Collections by Payment Method
            </h3>

            <div className="space-y-3">
              {Object.entries(methodTotals).map(([method, amount]) => {
                const pct = totalCollected > 0 ? Math.round((amount / totalCollected) * 100) : 0;
                return (
                  <div key={method} className="space-y-1">
                    <div className="d-flex justify-content-between text-xs font-medium text-slate-700">
                      <span>{method}</span>
                      <span className="font-mono">{formatCurrency(amount)} ({pct}%)</span>
                    </div>
                    <div className="w-100 bg-slate-100 h-2 rounded-full overflow-hidden">
                      <div
                        className="bg-emerald-500 h-100 rounded-full"
                        style={{ width: `${pct}%` }}
                      />
                    </div>
                  </div>
                );
              })}
              {Object.keys(methodTotals).length === 0 && (
                <p className="text-xs text-muted mb-0">No recorded payments.</p>
              )}
            </div>
          </div>

          <div className="pg-card p-4">
            <h3 className="h6 fw-bold mb-3 text-slate-900 d-flex align-items-center gap-2">
              <Receipt className="w-4 h-4 text-indigo-500" />
              Tax & Concession Summary
            </h3>
            <div className="space-y-2 text-xs font-mono">
              <div className="d-flex justify-content-between text-slate-600">
                <span>Total Tax Assessed:</span>
                <span className="fw-bold text-slate-900">{formatCurrency(totalTax)}</span>
              </div>
              <div className="d-flex justify-content-between text-slate-600">
                <span>Discounts Extended:</span>
                <span className="text-emerald-700">-{formatCurrency(totalDiscounts)}</span>
              </div>
              <div className="border-t border-slate-200 pt-2 d-flex justify-content-between text-slate-800">
                <span>Net Taxable Volume:</span>
                <span>{formatCurrency(Math.max(0, totalBilled - totalTax))}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
