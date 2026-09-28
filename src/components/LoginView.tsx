import React, { useState } from 'react';
import { useBilling } from '../context/BillingContext';
import {
  Lock,
  Mail,
  User,
  Eye,
  EyeOff,
  LogIn,
  UserPlus,
  ShieldCheck,
  CheckCircle2,
  AlertCircle,
  Building,
  Sparkles,
  HelpCircle,
  X
} from 'lucide-react';

export const LoginView: React.FC = () => {
  const { login, register, company } = useBilling();

  const [mode, setMode] = useState<'signin' | 'signup'>('signin');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [forgotModalOpen, setForgotModalOpen] = useState(false);
  const [forgotEmail, setForgotEmail] = useState('');
  const [forgotSent, setForgotSent] = useState(false);

  // Sign In form fields
  const [email, setEmail] = useState('johnkamaukibe126@gmail.com');
  const [password, setPassword] = useState('admin123');

  // Sign Up form fields
  const [regName, setRegName] = useState('');
  const [regEmail, setRegEmail] = useState('');
  const [regPassword, setRegPassword] = useState('');
  const [regRole, setRegRole] = useState<'admin' | 'manager' | 'staff'>('admin');

  const handleSignIn = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccessMsg(null);

    if (!email.trim() || !password.trim()) {
      setError('Please provide both email and password.');
      return;
    }

    setIsSubmitting(true);
    // Simulate brief secure authentication delay
    setTimeout(() => {
      const res = login(email.trim(), password.trim(), rememberMe);
      setIsSubmitting(false);
      if (!res.success) {
        setError(res.error || 'Invalid email or password.');
      }
    }, 400);
  };

  const handleSignUp = (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccessMsg(null);

    if (!regName.trim() || !regEmail.trim() || !regPassword.trim()) {
      setError('Please fill in all registration fields.');
      return;
    }

    if (regPassword.length < 6) {
      setError('Password must be at least 6 characters.');
      return;
    }

    setIsSubmitting(true);
    setTimeout(() => {
      const res = register(regName.trim(), regEmail.trim(), regPassword.trim(), regRole);
      setIsSubmitting(false);
      if (!res.success) {
        setError(res.error || 'Failed to create user account.');
      } else {
        setSuccessMsg('Account registered successfully! Signing you in...');
      }
    }, 450);
  };

  const handleQuickLogin = (demoEmail: string, demoPass: string) => {
    setEmail(demoEmail);
    setPassword(demoPass);
    setError(null);
    setSuccessMsg(null);
    setIsSubmitting(true);
    setTimeout(() => {
      login(demoEmail, demoPass, true);
      setIsSubmitting(false);
    }, 350);
  };

  const handleSendResetLink = (e: React.FormEvent) => {
    e.preventDefault();
    if (!forgotEmail.trim()) return;
    setForgotSent(true);
    setTimeout(() => {
      setForgotModalOpen(false);
      setForgotSent(false);
      setForgotEmail('');
      setSuccessMsg(`Password reset instructions simulated for ${forgotEmail}`);
    }, 1800);
  };

  return (
    <div className="min-h-screen bg-slate-900 text-slate-100 flex flex-col justify-center items-center p-4 relative overflow-hidden">
      {/* Background ambient lighting */}
      <div className="absolute top-1/4 -left-32 w-96 h-96 bg-red-600/10 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute bottom-1/4 -right-32 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none" />

      <div className="max-w-md w-full relative z-10 my-8">
        {/* Brand Header */}
        <div className="text-center mb-6">
          <div className="inline-block p-2 bg-white rounded-2xl shadow-xl border border-slate-700/80 mb-3">
            <img
              src={company.logo || '/uploads/logos/paperglow_enterprise.png'}
              alt={company.company_name}
              className="h-16 w-auto object-contain mx-auto"
            />
          </div>
          <h1 className="h4 fw-bold text-white tracking-tight mb-1">
            {company.company_name}
          </h1>
          <p className="text-xs text-slate-400 font-medium">
            Smart Solutions. Real Growth. &bull; Financial Suite
          </p>
        </div>

        {/* Auth Card */}
        <div className="bg-slate-800/95 backdrop-blur-md border border-slate-700/80 rounded-2xl shadow-2xl p-6 sm:p-8">
          {/* Navigation Pills */}
          <div className="grid grid-cols-2 p-1 bg-slate-900/80 rounded-xl mb-6 border border-slate-700/60">
            <button
              type="button"
              onClick={() => {
                setMode('signin');
                setError(null);
                setSuccessMsg(null);
              }}
              className={`py-2 text-xs font-bold rounded-lg transition-all d-flex align-items-center justify-content-center gap-1.5 ${
                mode === 'signin'
                  ? 'bg-amber-500 text-slate-950 shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              <LogIn className="w-3.5 h-3.5" />
              <span>Sign In</span>
            </button>
            <button
              type="button"
              onClick={() => {
                setMode('signup');
                setError(null);
                setSuccessMsg(null);
              }}
              className={`py-2 text-xs font-bold rounded-lg transition-all d-flex align-items-center justify-content-center gap-1.5 ${
                mode === 'signup'
                  ? 'bg-amber-500 text-slate-950 shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              <UserPlus className="w-3.5 h-3.5" />
              <span>Create Account</span>
            </button>
          </div>

          {/* Feedback Banners */}
          {error && (
            <div className="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs d-flex align-items-center gap-2">
              <AlertCircle className="w-4 h-4 flex-shrink-0 text-rose-400" />
              <span>{error}</span>
            </div>
          )}

          {successMsg && (
            <div className="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs d-flex align-items-center gap-2">
              <CheckCircle2 className="w-4 h-4 flex-shrink-0 text-emerald-400" />
              <span>{successMsg}</span>
            </div>
          )}

          {/* Sign In Form */}
          {mode === 'signin' && (
            <form onSubmit={handleSignIn} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Email Address
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <Mail className="w-4 h-4" />
                  </div>
                  <input
                    type="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="name@company.com"
                    className="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                  />
                </div>
              </div>

              <div>
                <div className="d-flex align-items-center justify-content-between mb-1.5">
                  <label className="block text-xs font-semibold text-slate-300 mb-0">
                    Password
                  </label>
                  <button
                    type="button"
                    onClick={() => setForgotModalOpen(true)}
                    className="text-xs text-amber-400 hover:text-amber-300 hover:underline bg-transparent border-0 p-0"
                  >
                    Forgot password?
                  </button>
                </div>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <Lock className="w-4 h-4" />
                  </div>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    required
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="Enter your password"
                    className="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-10 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white"
                  >
                    {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                  </button>
                </div>
              </div>

              <div className="d-flex align-items-center justify-content-between pt-1">
                <label className="d-flex align-items-center gap-2 cursor-pointer text-xs text-slate-300">
                  <input
                    type="checkbox"
                    checked={rememberMe}
                    onChange={(e) => setRememberMe(e.target.checked)}
                    className="rounded border-slate-700 text-amber-500 focus:ring-amber-500 bg-slate-900 w-4 h-4"
                  />
                  <span>Remember my session</span>
                </label>
              </div>

              <button
                type="submit"
                disabled={isSubmitting}
                className="btn btn-pg-primary w-100 py-2.5 rounded-xl font-bold text-sm d-flex align-items-center justify-content-center gap-2 shadow-lg mt-2 disabled:opacity-50"
              >
                {isSubmitting ? (
                  <>
                    <span className="spinner-border spinner-border-sm" role="status" aria-hidden="true" />
                    <span>Signing In...</span>
                  </>
                ) : (
                  <>
                    <LogIn className="w-4 h-4" />
                    <span>Sign In to Dashboard</span>
                  </>
                )}
              </button>

              {/* Quick 1-Click Demo Accounts */}
              <div className="pt-4 border-t border-slate-700/60 mt-5">
                <div className="d-flex align-items-center justify-content-between mb-2">
                  <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                    <Sparkles className="w-3 h-3 text-amber-400" />
                    <span>Quick Access Demo Accounts</span>
                  </span>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => handleQuickLogin('johnkamaukibe126@gmail.com', 'admin123')}
                    className="text-left p-2 rounded-lg bg-slate-900/90 hover:bg-slate-900 border border-slate-700/70 hover:border-amber-500/50 transition-all text-xs group"
                  >
                    <div className="fw-semibold text-white group-hover:text-amber-400 flex items-center justify-between">
                      <span>Administrator</span>
                      <span className="badge bg-amber-500/20 text-amber-300 text-[9px]">Admin</span>
                    </div>
                    <div className="text-[11px] text-slate-400 truncate">johnkamaukibe126@gmail.com</div>
                  </button>

                  <button
                    type="button"
                    onClick={() => handleQuickLogin('finance@paperglow.co.ke', 'finance123')}
                    className="text-left p-2 rounded-lg bg-slate-900/90 hover:bg-slate-900 border border-slate-700/70 hover:border-amber-500/50 transition-all text-xs group"
                  >
                    <div className="fw-semibold text-white group-hover:text-amber-400 flex items-center justify-between">
                      <span>Finance Manager</span>
                      <span className="badge bg-indigo-500/20 text-indigo-300 text-[9px]">Finance</span>
                    </div>
                    <div className="text-[11px] text-slate-400 truncate">finance@paperglow.co.ke</div>
                  </button>
                </div>
              </div>
            </form>
          )}

          {/* Create Account Form */}
          {mode === 'signup' && (
            <form onSubmit={handleSignUp} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Full Name
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <User className="w-4 h-4" />
                  </div>
                  <input
                    type="text"
                    required
                    value={regName}
                    onChange={(e) => setRegName(e.target.value)}
                    placeholder="e.g. John Kamau"
                    className="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Corporate Email
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <Mail className="w-4 h-4" />
                  </div>
                  <input
                    type="email"
                    required
                    value={regEmail}
                    onChange={(e) => setRegEmail(e.target.value)}
                    placeholder="name@paperglow.co.ke"
                    className="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Role / Designation
                </label>
                <select
                  value={regRole}
                  onChange={(e) => setRegRole(e.target.value as 'admin' | 'manager' | 'staff')}
                  className="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                >
                  <option value="admin">Administrator (Full Access)</option>
                  <option value="manager">Finance Manager (Approvals & Invoicing)</option>
                  <option value="staff">Billing Staff (Draft & Proposals)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1.5">
                  Password
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <Lock className="w-4 h-4" />
                  </div>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    required
                    minLength={6}
                    value={regPassword}
                    onChange={(e) => setRegPassword(e.target.value)}
                    placeholder="At least 6 characters"
                    className="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-10 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white"
                  >
                    {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                  </button>
                </div>
              </div>

              <button
                type="submit"
                disabled={isSubmitting}
                className="btn btn-pg-primary w-100 py-2.5 rounded-xl font-bold text-sm d-flex align-items-center justify-content-center gap-2 shadow-lg mt-2 disabled:opacity-50"
              >
                {isSubmitting ? (
                  <>
                    <span className="spinner-border spinner-border-sm" role="status" aria-hidden="true" />
                    <span>Creating Account...</span>
                  </>
                ) : (
                  <>
                    <UserPlus className="w-4 h-4" />
                    <span>Register Account</span>
                  </>
                )}
              </button>
            </form>
          )}
        </div>

        {/* Security & Compliance badges */}
        <div className="mt-6 text-center text-xs text-slate-400 d-flex flex-wrap align-items-center justify-content-center gap-4">
          <span className="d-flex align-items-center gap-1.5">
            <ShieldCheck className="w-4 h-4 text-emerald-400" />
            <span>256-Bit SSL Encryption</span>
          </span>
          <span className="text-slate-600">&bull;</span>
          <span>Kenya KRA Invoicing Ready</span>
          <span className="text-slate-600">&bull;</span>
          <span>Role-Based Access</span>
        </div>
      </div>

      {/* Forgot Password Modal */}
      {forgotModalOpen && (
        <div
          className="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
          onClick={() => setForgotModalOpen(false)}
        >
          <div
            className="bg-slate-800 border border-slate-700 rounded-2xl p-6 max-w-sm w-full text-slate-100 shadow-2xl relative"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="d-flex align-items-center justify-content-between mb-4">
              <h2 className="text-base font-bold text-white mb-0">Reset Password</h2>
              <button
                onClick={() => setForgotModalOpen(false)}
                className="text-slate-400 hover:text-white p-1"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {forgotSent ? (
              <div className="text-center py-4">
                <CheckCircle2 className="w-12 h-12 text-emerald-400 mx-auto mb-3" />
                <p className="text-sm font-semibold text-white">Reset Link Sent!</p>
                <p className="text-xs text-slate-400">
                  We sent a recovery link to <span className="text-amber-400">{forgotEmail}</span>.
                </p>
              </div>
            ) : (
              <form onSubmit={handleSendResetLink}>
                <p className="text-xs text-slate-300 mb-4">
                  Enter your registered work email and we will send you a secure link to reset your account password.
                </p>
                <div className="mb-4">
                  <input
                    type="email"
                    required
                    value={forgotEmail}
                    onChange={(e) => setForgotEmail(e.target.value)}
                    placeholder="name@paperglow.co.ke"
                    className="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500"
                  />
                </div>
                <div className="d-flex justify-content-end gap-2">
                  <button
                    type="button"
                    onClick={() => setForgotModalOpen(false)}
                    className="btn btn-sm btn-secondary text-xs"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="btn btn-sm btn-pg-primary text-xs"
                  >
                    Send Reset Link
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}
    </div>
  );
};
