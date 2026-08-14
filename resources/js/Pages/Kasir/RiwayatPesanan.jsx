import { Head } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import CashierLayout from '@/Layouts/CashierLayout';
import RiwayatTable from '@/Components/Shared/RiwayatTable';

export default function RiwayatPesanan({ orders, filters }) {
  return (
    <>
      <Head title="Riwayat Pesanan | W9 Cafe" />
      <CashierLayout title="Riwayat Pesanan" fullscreen>
        <div className="flex-1 overflow-y-auto p-8 bg-muted">
          <Card className="shadow-sm">
            <CardContent className="p-6">
              {/* Title */}
              <div className="mb-7 flex flex-col gap-1">
                <h1 className="text-3xl font-bold text-foreground m-0 tracking-tight">
                  Riwayat Pesanan
                </h1>
                <p className="text-sm text-muted-foreground m-0">
                  Lihat semua transaksi yang telah selesai
                </p>
              </div>

              <RiwayatTable
                orders={orders}
                filters={filters}
                showFilters={true}
                showPagination={true}
                baseRoute={route('kasir.riwayat-pesanan')}
              />
            </CardContent>
          </Card>
        </div>
      </CashierLayout>
    </>
  );
}
