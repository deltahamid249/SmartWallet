import React from 'react';

interface StatusBadgeProps {
  status: string;
  className?: string;
}

export const StatusBadge: React.FC<StatusBadgeProps> = ({ status, className = '' }) => {
  const norm = (status || '').toLowerCase();

  let bg = 'bg-gray-100 text-gray-700';
  let label = status;

  if (['completed', 'approved', 'active'].includes(norm)) {
    bg = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
    label = 'مكتمل';
  } else if (['pending', 'processing'].includes(norm)) {
    bg = 'bg-amber-100 text-amber-800 border border-amber-200';
    label = 'قيد المراجعة';
  } else if (['rejected', 'failed', 'blocked', 'suspended'].includes(norm)) {
    bg = 'bg-rose-100 text-rose-800 border border-rose-200';
    label = norm === 'suspended' ? 'موقوف' : 'مرفوض';
  }

  return (
    <span
      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wide ${bg} ${className}`}
    >
      {label}
    </span>
  );
};
