import { Head } from '@inertiajs/react';
import KitchenLayout from '@/Layouts/KitchenLayout';
import RiwayatTable from '@/Components/Shared/RiwayatTable';

export default function KitchenRiwayat({ riwayatOrders }) {
    return (
        <>
            <Head title="Riwayat | W9 Cafe" />
            <KitchenLayout>
                <RiwayatTable
                    orders={{ data: riwayatOrders, current_page: 1, last_page: 1 }}
                    showFilters={false}
                    showPagination={false}
                    baseRoute={route('dapur.riwayat-pesanan')}
                />
            </KitchenLayout>
        </>
    );
}
