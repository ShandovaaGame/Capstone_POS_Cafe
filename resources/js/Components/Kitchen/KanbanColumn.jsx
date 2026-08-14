import React from 'react';
import { cn } from '@/lib/utils';

export default function KanbanColumn({ title, count, colorClass, badgeClass, children, className, hideHeader = false }) {
    return (
        <div className={cn('flex flex-col min-h-0 min-w-0 flex-1', className)}>
            {!hideHeader && (
                <div className={`flex items-center justify-between mb-2 pb-2 px-3 ${colorClass}`}>
                    <span className="text-base font-bold text-foreground">
                        {title}
                    </span>
                    <span className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${badgeClass}`}>
                        {count}
                    </span>
                </div>
            )}

            <div className="flex-1 overflow-y-auto flex flex-col gap-3 pb-2 px-1">
                {React.Children.count(children) === 0 ? (
                    <div className="text-center py-10 px-4 text-[13px] text-muted-foreground border border-dashed border-border rounded-xl">
                        Tidak ada pesanan
                    </div>
                ) : (
                    children
                )}
            </div>
        </div>
    );
}
