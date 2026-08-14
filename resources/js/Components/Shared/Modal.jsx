import { useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

const sizeClasses = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
};

export default function Modal({ isOpen, onClose, title, children, size = 'md' }) {
    // Prevent body scroll when open
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [isOpen]);

    // Close on Escape key
    useEffect(() => {
        if (!isOpen) return;
        const handleEscape = (e) => {
            if (e.key === 'Escape') onClose();
        };
        window.addEventListener('keydown', handleEscape);
        return () => window.removeEventListener('keydown', handleEscape);
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    return (
        <div
            className="fixed inset-0 z-50 flex items-start justify-center py-8 px-4 overflow-y-auto bg-black/50"
            onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div className={cn('w-full bg-card rounded-2xl shadow-xl overflow-hidden', sizeClasses[size])}>
                <div className={cn(
                    'flex items-center px-6 py-4',
                    title ? 'justify-between border-b border-border' : 'justify-end',
                )}>
                    {title && (
                        <h2 className="text-lg font-bold text-foreground">{title}</h2>
                    )}
                    <button
                        onClick={onClose}
                        className="p-1.5 rounded-xl hover:bg-muted transition-colors cursor-pointer"
                        type="button"
                        aria-label="Tutup"
                    >
                        <X size={20} />
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}
