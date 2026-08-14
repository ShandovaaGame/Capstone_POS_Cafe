import { useState, useEffect, useRef } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';
import StatusBadge from '@/Components/Common/StatusBadge';
import { formatRupiah, formatTime, formatDate, summarizeItems } from '@/helpers';
import { cn } from '@/lib/utils';

/* ── Timer helpers (kitchen variant) ──────────────────────────── */
function getElapsedSeconds(createdAt) {
    return Math.floor((Date.now() - new Date(createdAt).getTime()) / 1000);
}

function getUrgency(seconds) {
    if (seconds >= 600) return 'critical';
    if (seconds >= 300) return 'warning';
    return 'normal';
}

const URGENCY_STYLES = {
    normal:   { cardBg: 'bg-white',          border: 'border-border',          timerClass: 'text-muted-foreground' },
    warning:  { cardBg: 'bg-amber-50',       border: 'border-amber-400',       timerClass: 'text-amber-700'       },
    critical: { cardBg: 'bg-red-50',         border: 'border-red-400',         timerClass: 'text-red-700'         },
};

/* ── Bump button labels per status ────────────────────────────── */
const BUMP_LABEL = {
    pending:  '→ Ambil',
    diproses: '✔ Selesai',
    selesai:  'Selesai',
};

/* ── Component ────────────────────────────────────────────────── */
export default function SharedOrderCard({
    order,
    onBump,
    onDetail,
    showTimer = false,
    variant = 'cashier',
    isBumping = false,
}) {
    const isKitchen = variant === 'kitchen';

    // ── Live timer for kitchen ──
    const [now, setNow] = useState(Date.now());
    const intervalRef = useRef(null);
    useEffect(() => {
        if (!isKitchen || !showTimer) return;
        intervalRef.current = setInterval(() => setNow(Date.now()), 1000);
        return () => clearInterval(intervalRef.current);
    }, [isKitchen, showTimer]);

    const elapsed = isKitchen && showTimer ? getElapsedSeconds(order.created_at) : 0;
    const urgency = isKitchen && showTimer ? getUrgency(elapsed) : 'normal';
    const theme   = URGENCY_STYLES[urgency];

    const canBump = isKitchen && (order.status === 'pending' || order.status === 'diproses');
    const items   = Array.isArray(order.items)
        ? order.items
        : [];

    return (
        <Card
            size="sm"
            className={cn(
                'transition-all duration-300',
                theme.cardBg,
                theme.border,
                isKitchen && showTimer && urgency === 'critical' && 'shadow-[0_4px_16px_rgba(239,68,68,0.25)]',
                !isKitchen && 'shadow-sm',
            )}
        >
            <CardContent className="flex flex-col gap-2.5">
                {/* ── Header: order code + status badge ── */}
                <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2 min-w-0 overflow-hidden">
                        <span className="text-base font-semibold text-foreground truncate">
                            #{order.order_code}
                        </span>
                        {order.table_number && (
                            <Badge variant="outline" className="shrink-0 text-[11px] px-2 py-0">
                                Meja {order.table_number}
                            </Badge>
                        )}
                    </div>
                    <StatusBadge status={order.status} />
                </div>

                {/* ── Timestamp ── */}
                {!isKitchen && (
                    <div className="text-xs text-muted-foreground">
                        {formatTime(order.created_at)} · {formatDate(order.created_at)}
                    </div>
                )}

                {/* ── Items list ── */}
                <div className="text-sm text-muted-foreground">
                    {items.length > 0 ? (
                        <div className="flex flex-col gap-1">
                            {items.map((item, i) => (
                                <div key={item.id || i} className="flex items-center justify-between">
                                    <span>
                                        <span className="font-semibold text-foreground">
                                            {item.quantity}x
                                        </span>{' '}
                                        {item.name}
                                    </span>
                                    {item.category && (
                                        <Badge variant="secondary" className="text-[10px] px-1.5 py-0 h-4">
                                            {item.category}
                                        </Badge>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <span className="truncate block">
                            {order.items_summary || summarizeItems(order.items) || '-'}
                        </span>
                    )}
                </div>

                {/* ── Footer: total + actions ── */}
                <div className="flex items-center justify-between gap-2 pt-1">
                    <span className="text-base font-bold text-foreground">
                        {formatRupiah(order.total_amount)}
                    </span>

                    <div className="flex items-center gap-2">
                        {onDetail && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => onDetail(order.id || order)}
                            >
                                Detail
                            </Button>
                        )}

                        {isKitchen && onBump && (
                            <Button
                                variant={order.status === 'pending' ? 'default' : 'secondary'}
                                size="sm"
                                className={isBumping ? 'opacity-50' : ''}
                                disabled={!canBump || isBumping}
                                onClick={() => !isBumping && onBump(order)}
                            >
                                {isBumping ? (
                                    <Loader2 className="animate-spin h-4 w-4" />
                                ) : (
                                    BUMP_LABEL[order.status] || 'Selesai'
                                )}
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
