import { CheckCircle, XCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function FlashToast({ toast, onDismiss }) {
    if (!toast) return null;

    return (
        <div
            className={cn(
                'fixed top-6 right-6 z-[9999] rounded-[10px] px-4 py-3 flex items-center gap-2.5 text-sm shadow-floating min-w-[280px] max-w-[380px]',
                toast.type === 'success'
                    ? 'bg-green-50 border border-green-200 text-green-800'
                    : 'bg-red-50 border border-red-200 text-red-800',
            )}
        >
            {toast.type === 'success'
                ? <CheckCircle size={18} className="shrink-0" />
                : <XCircle size={18} className="shrink-0" />}
            <span className="flex-1">{toast.message}</span>
        </div>
    );
}
