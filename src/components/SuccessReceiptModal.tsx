import React from 'react';
import { CheckCircle2, Copy, Check, X } from 'lucide-react';

interface SuccessReceiptModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  reference: string;
  amount?: number;
  details?: string;
  extraInfo?: { label: string; value: string }[];
}

export const SuccessReceiptModal: React.FC<SuccessReceiptModalProps> = ({
  isOpen,
  onClose,
  title,
  reference,
  amount,
  details,
  extraInfo,
}) => {
  const [copied, setCopied] = React.useState(false);

  if (!isOpen) return null;

  const copyRef = () => {
    navigator.clipboard.writeText(reference);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-gray-100 transform transition-all p-6 text-center">
        <div className="w-16 h-16 mx-auto bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4 ring-8 ring-emerald-50">
          <CheckCircle2 className="w-9 h-9 stroke-[2.5]" />
        </div>

        <h3 className="text-xl font-black text-[#0D2238] mb-1">{title}</h3>

        {amount !== undefined && (
          <div className="my-3 text-3xl font-black text-emerald-600 font-mono tracking-tight">
            {amount.toLocaleString('en-US', { minimumFractionDigits: 2 })}
            <span className="text-base font-bold text-gray-500 mr-1.5 font-sans">SDG</span>
          </div>
        )}

        <div className="bg-[#F4F6F8] rounded-xl p-4 my-4 text-right space-y-2.5 border border-gray-200">
          <div className="flex items-center justify-between">
            <span className="text-xs text-gray-500 font-medium">الرقم المرجعي للعملية</span>
            <button
              onClick={copyRef}
              className="text-xs flex items-center gap-1 text-[#1E3A5F] hover:text-[#0D2238] font-bold"
              title="نسخ الرقم المرجعي"
            >
              {copied ? (
                <>
                  <Check className="w-3.5 h-3.5 text-emerald-600" />
                  <span className="text-emerald-600">تم النسخ</span>
                </>
              ) : (
                <>
                  <Copy className="w-3.5 h-3.5" />
                  <span>نسخ</span>
                </>
              )}
            </button>
          </div>
          <div className="font-mono text-sm font-bold text-[#0D2238] bg-white px-2.5 py-1.5 rounded-lg border border-gray-200 select-all text-center">
            {reference}
          </div>

          {details && <p className="text-xs text-gray-600 leading-relaxed pt-1">{details}</p>}

          {extraInfo && extraInfo.length > 0 && (
            <div className="pt-2 border-t border-gray-200 space-y-1.5">
              {extraInfo.map((info, idx) => (
                <div key={idx} className="flex justify-between text-xs">
                  <span className="text-gray-500">{info.label}:</span>
                  <span className="font-bold text-[#0D2238]">{info.value}</span>
                </div>
              ))}
            </div>
          )}
        </div>

        <button
          onClick={onClose}
          id="receipt_done_button"
          className="w-full py-3 px-4 bg-[#0D2238] hover:bg-[#1E3A5F] text-white font-bold rounded-xl transition-all shadow-md active:scale-95"
        >
          تم
        </button>
      </div>
    </div>
  );
};
