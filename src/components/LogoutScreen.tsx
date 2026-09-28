import React from 'react';
import { useBilling } from '../context/BillingContext';
import { CheckCircle2, ShieldCheck, ArrowRight, Lock, LogIn } from 'lucide-react';

interface LogoutScreenProps {
  onReturnToLogin: () => void;
  lastUserEmail?: string;
  lastUserName?: string;
}

export const LogoutScreen: React.FC<LogoutScreenProps> = ({
  onReturnToLogin,
  lastUserEmail,
  lastUserName
}) => {
  const { company } = useBilling();

  return (
    <div className="min-h-screen bg-slate-900 text-slate-100 flex flex-col justify-center items-center p-4 relative overflow-hidden">
      {/* Decorative gradient accents */}
      <div className="absolute top-1/4 -left-20 w-96 h-96 bg-red-600/10 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute bottom-1/4 -right-20 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none" />

      <div className="max-w-md w-full bg-slate-800/90 backdrop-blur-md border border-slate-700/80 rounded-2xl shadow-2xl p-6 sm:p-8 text-center relative z-10">
        {/* Brand Logo */}
        <div className="d-flex justify-content-center mb-6">
          {company.logo ? (
            <img
              src={company.logo}
              alt={company.company_name}
              className="h-16 w-auto object-contain bg-white rounded-xl p-2 shadow-sm border border-slate-700"
            />
          ) : (
            <div className="h-16 px-4 bg-slate-900 border border-slate-700 rounded-xl d-flex align-items-center justify-content-center font-bold text-amber-500 text-lg">
              {company.company_name}
            </div>
          )}
        </div>

        {/* Success Icon */}
        <div className="w-14 h-14 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-full d-flex align-items-center justify-content-center mx-auto mb-4">
          <CheckCircle2 className="w-8 h-8" />
        </div>

        <h1 className="h4 fw-bold text-white mb-2">Signed Out Successfully</h1>
        <p className="text-slate-400 text-sm mb-6 leading-relaxed">
          Your active session for <strong className="text-slate-200">{lastUserName || company.company_name}</strong> has been safely terminated and local cached session tokens have been cleared.
        </p>

        {lastUserEmail && (
          <div className="bg-slate-900/60 border border-slate-700/60 rounded-xl p-3 mb-6 d-flex align-items-center justify-content-between text-left">
            <div className="d-flex align-items-center gap-2.5 overflow-hidden">
              <div className="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 font-bold d-flex align-items-center justify-content-center text-xs flex-shrink-0">
                {(lastUserName || 'U').charAt(0).toUpperCase()}
              </div>
              <div className="text-truncate">
                <div className="text-xs fw-semibold text-slate-200 text-truncate">{lastUserName || 'User'}</div>
                <div className="text-[11px] text-slate-400 text-truncate">{lastUserEmail}</div>
              </div>
            </div>
            <span className="badge bg-slate-800 text-slate-400 border border-slate-700 text-[10px]">
              Disconnected
            </span>
          </div>
        )}

        {/* Action Button */}
        <button
          onClick={onReturnToLogin}
          className="btn btn-pg-primary w-100 py-2.5 rounded-xl font-semibold d-flex align-items-center justify-content-center gap-2 shadow-lg mb-4"
        >
          <LogIn className="w-4 h-4" />
          <span>Return to Sign In</span>
          <ArrowRight className="w-4 h-4 ml-1" />
        </button>

        {/* Security badge */}
        <div className="pt-4 border-t border-slate-700/60 d-flex align-items-center justify-content-center gap-2 text-xs text-slate-400">
          <ShieldCheck className="w-4 h-4 text-emerald-400" />
          <span>256-Bit SSL Protected &bull; PaperGlow Enterprise</span>
        </div>
      </div>

      <div className="mt-6 text-center text-xs text-slate-500">
        &copy; {new Date().getFullYear()} {company.company_name}. All rights reserved.
      </div>
    </div>
  );
};
