import { AlertCircle } from 'lucide-react';
import React from 'react';
import { ManchesterColor } from '@/types';

interface Props {
  level: ManchesterColor;
  message: string;
}

const levelStyles: Record<ManchesterColor, string> = {
  red: 'bg-manchester-red text-white border-manchester-red',
  orange: 'bg-manchester-orange text-white border-manchester-orange',
  yellow: 'bg-manchester-yellow text-gray-900 border-manchester-yellow',
  green: 'bg-manchester-green text-white border-manchester-green',
  blue: 'bg-manchester-blue text-white border-manchester-blue',
};

export default function VitalSignAlert({ level, message }: Props) {
  return (
    <div 
      className={`flex items-center gap-3 p-4 rounded-md border shadow-sm animate-pulse ${levelStyles[level]}`}
      role="alert" 
      aria-live="assertive"
    >
      <AlertCircle size={24} />
      <span className="font-bold tracking-wide">{message}</span>
    </div>
  );
}
