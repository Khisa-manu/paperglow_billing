export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'manager' | 'staff';
  status: 'active' | 'inactive';
}

export interface CompanySettings {
  id: number;
  user_id: number;
  company_name: string;
  logo?: string;
  address: string;
  phone: string;
  email: string;
  website: string;
  tax_number: string;
  currency: string;
  bank_name: string;
  bank_account_name: string;
  bank_account_number: string;
  bank_routing: string;
  bank_swift: string;
  mobile_money_name: string;
  mobile_money_number: string;
  default_invoice_terms: string;
  default_quotation_terms: string;
}

export interface Customer {
  id: number;
  user_id: number;
  name: string;
  company?: string;
  email: string;
  phone: string;
  address: string;
  tax_number?: string;
  notes?: string;
  created_at: string;
}

export interface LineItem {
  id: number;
  description: string;
  quantity: number;
  unit_price: number;
  discount: number;
  tax_rate: number;
  tax_amount: number;
  line_total: number;
  sort_order: number;
}

export type QuotationStatus = 'Draft' | 'Sent' | 'Accepted' | 'Rejected' | 'Expired';

export interface Quotation {
  id: number;
  user_id: number;
  customer_id: number;
  quotation_number: string;
  quotation_date: string;
  expiry_date: string;
  status: QuotationStatus;
  subtotal: number;
  discount_total: number;
  tax_total: number;
  grand_total: number;
  converted_invoice_id?: number | null;
  notes: string;
  terms: string;
  items: LineItem[];
  created_at: string;
}

export type InvoiceStatus = 'Draft' | 'Unpaid' | 'Partially Paid' | 'Paid' | 'Overdue' | 'Cancelled';

export interface Invoice {
  id: number;
  user_id: number;
  customer_id: number;
  from_quotation_id?: number | null;
  invoice_number: string;
  invoice_date: string;
  due_date: string;
  status: InvoiceStatus;
  subtotal: number;
  discount_total: number;
  tax_total: number;
  grand_total: number;
  paid_amount: number;
  balance: number;
  notes: string;
  terms: string;
  items: LineItem[];
  created_at: string;
}

export type PaymentMethod = 'Cash' | 'Bank Transfer' | 'Mobile Money' | 'Card' | 'Other';

export interface Payment {
  id: number;
  user_id: number;
  invoice_id: number;
  payment_date: string;
  amount: number;
  payment_method: PaymentMethod;
  reference_number?: string;
  notes?: string;
  created_at: string;
}
