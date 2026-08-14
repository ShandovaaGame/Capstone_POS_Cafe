import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Plus, Minus } from 'lucide-react';
import { formatRupiah } from '@/helpers';
import { cn } from '@/lib/utils';

export default function SharedMenuItem({ menu, onAdd, variant = 'cashier', inCart = false, quantity = 0, onIncrement, onDecrement, stock }) {
    const isCustomer = variant === 'customer';
    const isAdminDisabled = menu.is_available === false;
    const isUnlimitedStock = stock !== undefined && stock >= 999999;
    const isStockOut = !isUnlimitedStock && stock !== undefined && stock <= 0;
    const isUnavailable = isAdminDisabled || isStockOut;
    const isCashier = variant === 'cashier';

    const displayPrice = menu.is_student_discount && menu.student_price
        ? Number(menu.student_price)
        : Number(menu.price);

    const handleAdd = (e) => {
        if (isUnavailable) return;
        onAdd(menu);
    };

    return (
        <Card
            size="sm"
            className={cn(
                'cursor-pointer transition-all duration-150 select-none relative',
                'hover:shadow-md hover:-translate-y-0.5',
                isCustomer && 'overflow-hidden rounded-[18px]',
                isUnavailable && 'opacity-50 pointer-events-none grayscale',
            )}
            onClick={(e) => { e.stopPropagation(); handleAdd(e); }}
        >
            {isAdminDisabled && (
                <span className="absolute top-2 right-2 z-10 bg-red-600 text-white text-[11px] font-bold px-2.5 py-1 rounded-full shadow">
                    Nonaktif
                </span>
            )}
            {isStockOut && (
                <span className="absolute top-2 right-2 z-10 bg-red-600 text-white text-[11px] font-bold px-2.5 py-1 rounded-full shadow">
                    Habis
                </span>
            )}

            {isCustomer && (
                <div className="bg-muted h-[110px] flex items-center justify-center overflow-hidden">
                    {menu.image_url ? (
                        <img
                            src={menu.image_url}
                            alt={menu.name}
                            className="w-full h-full object-cover"
                            onError={(e) => { e.target.style.display = 'none'; }}
                        />
                    ) : (
                        <div className="text-muted-foreground/40 text-3xl font-bold">☕</div>
                    )}
                </div>
            )}

            <CardContent className={cn('flex flex-col gap-1', isCustomer ? 'px-3 pb-[14px] pt-[10px]' : 'text-center')}>
                {!isCustomer && menu.category?.name && (
                    <span className="text-[11px] uppercase text-muted-foreground tracking-wide font-semibold">
                        {menu.category.name}
                    </span>
                )}

                <span className={cn(
                    'font-bold leading-tight text-foreground',
                    isCustomer ? 'text-sm' : 'text-base',
                )}>
                    {menu.name}
                </span>

                {isCustomer && menu.is_student_discount && menu.student_price && (
                    <span className="text-[11px] text-muted-foreground line-through">
                        {formatRupiah(menu.price)}
                    </span>
                )}

                <span className={cn(
                    'font-semibold',
                    isCustomer ? 'text-[13px]' : 'text-sm',
                    isCustomer ? 'text-orange-primary' : 'text-primary',
                )}
                style={isCustomer ? { color: 'var(--orange-primary)' } : undefined}
                >
                    {formatRupiah(displayPrice)}
                </span>

                {isCustomer && (
                    inCart ? (
                        <div className="flex items-center justify-center gap-2 mt-1.5">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="rounded-[12px] bg-[#F5F0EB] text-[#2D2016] hover:bg-[#E8E0D8] border-none"
                                onClick={(e) => { e.stopPropagation(); onDecrement?.(menu); }}
                                aria-label="Kurangi jumlah"
                            >
                                <Minus size={16} />
                            </Button>
                            <span className="text-base font-bold text-[#2D2016] min-w-[24px] text-center">
                                {quantity}
                            </span>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="rounded-[12px] border-none text-white"
                                style={{ background: 'var(--orange-primary)' }}
                                onClick={(e) => { e.stopPropagation(); onIncrement?.(menu); }}
                                aria-label="Tambah jumlah"
                            >
                                <Plus size={16} />
                            </Button>
                        </div>
                    ) : (
                        <Button
                            variant="default"
                            size="sm"
                            className="w-full mt-1.5 rounded-[14px] min-h-[44px] gap-1.5 text-xs font-bold"
                            style={{ background: 'var(--orange-primary)', color: 'white' }}
                            disabled={isUnavailable}
                            onClick={(e) => { e.stopPropagation(); handleAdd(e); }}
                        >
                            <Plus size={14} />
                            {isAdminDisabled ? 'Nonaktif' : isStockOut ? 'Habis' : 'Tambah'}
                        </Button>
                    )
                )}
            </CardContent>

            {!isCustomer && (
                <div className="px-3 pb-3">
                    <Button
                        variant="ghost"
                        size="sm"
                        className={cn('w-full', isUnavailable ? 'text-red-500 cursor-not-allowed' : 'text-primary')}
                        disabled={isUnavailable}
                        onClick={(e) => { e.stopPropagation(); handleAdd(e); }}
                    >
                        {isAdminDisabled ? 'Nonaktif' : isStockOut ? 'Habis' : '+ Tambah'}
                    </Button>
                </div>
            )}
        </Card>
    );
}
