import { User, CompanySettings, Customer, Quotation, Invoice, Payment } from '../types';

export const initialUser: User = {
  id: 1,
  name: 'Admin User',
  email: 'admin@paperglow.com',
  role: 'admin',
  status: 'active'
};

export const initialCompany: CompanySettings = {
  id: 1,
  user_id: 1,
  company_name: 'PaperGlow Studio',
  logo: '',
  address: '',
  phone: '',
  email: '',
  website: '',
  tax_number: '',
  currency: 'KES',
  bank_name: '',
  bank_account_name: '',
  bank_account_number: '',
  bank_routing: '',
  bank_swift: '',
  mobile_money_name: '',
  mobile_money_number: '',
  default_invoice_terms: 'Payment due within 30 days of invoice date. Thank you for your business.',
  default_quotation_terms: 'Quotation valid for 30 calendar days from issue date.'
};

export const initialCustomers: Customer[] = [];

export const initialQuotations: Quotation[] = [];

export const initialInvoices: Invoice[] = [];

export const initialPayments: Payment[] = [];
