import { cn } from '@/lib/utils';

export default function CardBase({ children, className, onClick }) {
    return (
        <div
            className={cn(
                'rounded-xl border border-border bg-card shadow-sm hover:shadow-md transition-shadow duration-200',
                className,
            )}
            onClick={onClick}
        >
            {children}
        </div>
    );
}
