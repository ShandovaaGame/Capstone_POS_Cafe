import { router } from '@inertiajs/react';
import { User, LogOut } from 'lucide-react';
import { buttonVariants } from '@/components/ui/button';
import { formatDate } from '@/helpers';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';

export default function ProfileDropdown({ user }) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger>
                <button
                    type="button"
                    className={buttonVariants({ variant: 'outline', size: 'icon' })}
                    aria-label="Menu akun"
                >
                    <User size={20} />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" sideOffset={8} className="min-w-[200px] p-0">
                <div className="px-3.5 py-3">
                    <p className="text-sm font-semibold text-foreground">
                        {user?.name ?? 'Kasir'}
                    </p>
                    <p className="text-xs text-muted-foreground mt-0.5">
                        {user?.email}
                    </p>
                    <p className="text-xs text-muted-foreground mt-0.5">
                        Terdaftar sejak {user?.created_at ? formatDate(user.created_at) : '-'}
                    </p>
                </div>
                <div className="h-px bg-border" />
                <DropdownMenuItem onClick={() => router.post('/logout')} variant="destructive" className="rounded-none px-3.5 py-2.5">
                    <LogOut size={16} />
                    Keluar
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
