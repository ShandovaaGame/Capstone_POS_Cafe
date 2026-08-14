import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import ProfileDropdown from '@/Components/Shared/ProfileDropdown';

export default function HeaderBar({ tabs, user, pendingCount = 0 }) {
    const currentPath = usePage().url;

    return (
        <header className="sticky top-0 z-50 bg-card border-b border-border px-4 lg:px-6 h-14 flex items-center gap-2">
            <div className="flex items-center gap-2.5 mr-3 shrink-0">
                <img
                    src="/images/logo.jpg"
                    alt="W9 Cafe"
                    className="size-8 rounded-[8px] object-cover shrink-0"
                />
                <span className="font-bold text-base text-foreground whitespace-nowrap select-none hidden sm:inline">
                    W9 Cafe
                </span>
            </div>

            {/* Tab Navigation */}
            <nav className="flex items-center gap-1 flex-1 min-w-0">
                {tabs.map(({ label, href, icon: Icon }) => {
                    const hrefPath = new URL(href, window.location.origin).pathname;
                    const currentPathname = new URL(currentPath, window.location.origin).pathname;
                    const isActive = currentPathname === hrefPath;
                    const showBadge = label === 'Pesanan Aktif' && pendingCount > 0;
                    return (
                        <Link
                            key={href}
                            href={href}
                            prefetch
                            cacheFor="1m"
                            className={cn(
                                'relative inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors no-underline whitespace-nowrap',
                                isActive
                                    ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                            )}
                        >
                            <Icon size={16} className="shrink-0" />
                            <span className="hidden sm:inline">{label}</span>
                            {showBadge && (
                                <span
                                    className={cn(
                                        'inline-flex items-center justify-center rounded-full bg-destructive text-destructive-foreground text-[10px] font-bold leading-none ml-0.5',
                                        pendingCount > 9 ? 'min-w-4 h-4 px-1' : 'size-4',
                                    )}
                                >
                                    {pendingCount > 99 ? '99+' : pendingCount}
                                </span>
                            )}
                        </Link>
                    );
                })}
            </nav>

            {/* Right side: Avatar Dropdown */}
            <div className="flex items-center gap-1 shrink-0">
                <ProfileDropdown user={user} />
            </div>
        </header>
    );
}
