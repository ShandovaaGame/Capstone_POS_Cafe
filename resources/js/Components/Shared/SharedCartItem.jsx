import { Button, buttonVariants } from '@/components/ui/button';
import { Minus, Plus, Trash2 } from 'lucide-react';
import { formatRupiah } from '@/helpers';
import { cn } from '@/lib/utils';

export default function SharedCartItem({ item, onUpdate, onRemove, variant = 'cashier' }) {
    const isCustomer = variant === 'customer';

    if (!isCustomer) {
        return (
            <div className="flex flex-col gap-1.5 py-3 border-b border-border">
                <div className="flex items-start justify-between gap-2">
                    <span className="font-semibold text-sm text-foreground leading-tight">
                        {item.name}
                    </span>
                    {onRemove && (
                        <button
                            type="button"
                            className={cn(
                                buttonVariants({ variant: 'ghost', size: 'icon-lg' }),
                                'text-muted-foreground hover:text-destructive shrink-0',
                            )}
                            onClick={() => onRemove(item.menuId || item.id)}
                            aria-label="Hapus item"
                        >
                            <Trash2 size={20} />
                        </button>
                    )}
                </div>

                <div className="flex items-center justify-between">
                    <span className="text-xs text-muted-foreground">
                        {formatRupiah(item.price)}
                    </span>
                    <span className="text-sm font-semibold text-foreground">
                        {formatRupiah(item.price * (item.quantity || 1))}
                    </span>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        className={buttonVariants({ variant: 'outline', size: 'icon' })}
                        onClick={() => onUpdate(item.menuId || item.id, Math.max(0, (item.quantity || 1) - 1))}
                        aria-label="Kurangi jumlah"
                    >
                        <Minus size={16} />
                    </button>
                    <span className="min-w-[32px] text-center text-sm font-bold text-foreground">
                        {item.quantity}
                    </span>
                    <button
                        type="button"
                        className={buttonVariants({ variant: 'default', size: 'icon' })}
                        onClick={() => onUpdate(item.menuId || item.id, (item.quantity || 0) + 1)}
                        aria-label="Tambah jumlah"
                    >
                        <Plus size={16} />
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex items-center gap-3 py-3.5 px-[18px] border-b border-[#F5F0EB]">
            {/* Name + unit price */}
            <div className="flex-1 min-w-0 flex flex-col gap-0.5">
                <span className="font-semibold text-[15px] text-[#2D2016] truncate">
                    {item.name}
                </span>
                <span className="text-[13px] text-[#8C7B6B]">
                    {formatRupiah(item.price)}
                </span>
            </div>

            {/* Qty controls */}
            <div className="flex items-center gap-2 shrink-0">
                <Button
                    variant="ghost"
                    size="icon"
                    className="rounded-[12px] bg-[#F5F0EB] text-[#2D2016] hover:bg-[#E8E0D8] border-none"
                    onClick={() => onUpdate(item.menuId || item.id, Math.max(0, (item.quantity || 1) - 1))}
                    aria-label="Kurangi jumlah"
                >
                    <Minus size={16} />
                </Button>

                <span className="font-bold text-center min-w-[24px] text-base text-[#2D2016]">
                    {item.quantity}
                </span>

                <Button
                    variant="ghost"
                    size="icon"
                    className="rounded-[12px] border-none text-white"
                    style={{ background: 'var(--orange-primary)' }}
                    onClick={() => onUpdate(item.menuId || item.id, (item.quantity || 0) + 1)}
                    aria-label="Tambah jumlah"
                >
                    <Plus size={16} />
                </Button>

                {onRemove && (
                    <Button
                        variant="ghost"
                        size="icon-xs"
                        className="text-muted-foreground hover:text-destructive"
                        onClick={() => onRemove(item.menuId || item.id)}
                        aria-label="Hapus item"
                    >
                        <Trash2 size={14} />
                    </Button>
                )}
            </div>
        </div>
    );
}
