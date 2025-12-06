import AppLayout from '@/layouts/app-layout';
import { index as indexTransaction } from '@/routes/admin/transactions';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, Banknote, Package, ShoppingCart, Store } from 'lucide-react';

// Definisi tipe data yang diterima dari Controller
interface DashboardProps {
  stats: {
    total_revenue: number;
    total_transactions: number;
    total_products: number;
    total_merchants: number;
    total_warehouses: number;
    total_users: number;
  };
  recent_transactions: {
    data: Array<{
      id: number;
      invoice_code: string;
      name: string; // Customer Name
      grand_total: number;
      status: string;
      created_at: string;
    }>;
  };
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
];

// Helper untuk format Rupiah
const formatRupiah = (number: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(number);
};

// Helper untuk status badge color
const getStatusColor = (status: string) => {
  switch (status.toLowerCase()) {
    case 'paid':
      return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800';
    case 'pending':
      return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800';
    case 'cancelled':
      return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800';
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
  }
};

export default function Dashboard({ stats, recent_transactions }: DashboardProps) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title='Dashboard' />

      <div className='flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6'>
        {/* --- Section 1: Key Metrics (Stats Cards) --- */}
        <div className='grid gap-4 md:grid-cols-2 lg:grid-cols-4'>
          {/* Card 1: Revenue */}
          <div className='rounded-xl border border-sidebar-border bg-white p-6 shadow-sm dark:bg-sidebar-accent/10'>
            <div className='flex items-center justify-between'>
              <p className='text-sm font-medium text-gray-500 text-muted-foreground'>
                Total Pendapatan
              </p>
              <Banknote className='h-4 w-4 text-emerald-500' />
            </div>
            <div className='mt-2 flex items-baseline gap-2'>
              <span className='text-2xl font-bold text-gray-900 dark:text-white'>
                {formatRupiah(stats.total_revenue)}
              </span>
            </div>
          </div>

          {/* Card 2: Transactions */}
          <div className='rounded-xl border border-sidebar-border bg-white p-6 shadow-sm dark:bg-sidebar-accent/10'>
            <div className='flex items-center justify-between'>
              <p className='text-sm font-medium text-gray-500 text-muted-foreground'>
                Total Transaksi
              </p>
              <ShoppingCart className='h-4 w-4 text-blue-500' />
            </div>
            <div className='mt-2'>
              <span className='text-2xl font-bold text-gray-900 dark:text-white'>
                {stats.total_transactions}
              </span>
              <span className='ml-2 text-xs text-gray-500'>Order masuk</span>
            </div>
          </div>

          {/* Card 3: Products & Stock */}
          <div className='rounded-xl border border-sidebar-border bg-white p-6 shadow-sm dark:bg-sidebar-accent/10'>
            <div className='flex items-center justify-between'>
              <p className='text-sm font-medium text-gray-500 text-muted-foreground'>
                Produk & Gudang
              </p>
              <Package className='h-4 w-4 text-orange-500' />
            </div>
            <div className='mt-2'>
              <span className='text-2xl font-bold text-gray-900 dark:text-white'>
                {stats.total_products}
              </span>
              <span className='ml-2 text-xs text-gray-500'>di {stats.total_warehouses} Gudang</span>
            </div>
          </div>

          {/* Card 4: Merchants/Users */}
          <div className='rounded-xl border border-sidebar-border bg-white p-6 shadow-sm dark:bg-sidebar-accent/10'>
            <div className='flex items-center justify-between'>
              <p className='text-sm font-medium text-gray-500 text-muted-foreground'>
                Merchant & Users
              </p>
              <Store className='h-4 w-4 text-purple-500' />
            </div>
            <div className='mt-2'>
              <span className='text-2xl font-bold text-gray-900 dark:text-white'>
                {stats.total_merchants}
              </span>
              <span className='ml-2 text-xs text-gray-500'>
                Merchant aktif ({stats.total_users} Users)
              </span>
            </div>
          </div>
        </div>

        {/* --- Section 2: Recent Transactions --- */}
        <div className='flex flex-col gap-4'>
          <div className='flex items-center justify-between'>
            <h2 className='text-lg font-semibold text-gray-900 dark:text-white'>
              Transaksi Terbaru
            </h2>
            <Link
              href={indexTransaction.url()}
              className='flex items-center text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400'
            >
              Lihat Semua <ArrowUpRight className='ml-1 h-4 w-4' />
            </Link>
          </div>

          <div className='overflow-hidden rounded-xl border border-sidebar-border bg-white shadow-sm dark:border-sidebar-border dark:bg-sidebar-accent/10'>
            <div className='overflow-x-auto'>
              <table className='w-full text-left text-sm'>
                <thead className='border-b border-gray-100 bg-gray-50/50 dark:border-gray-800 dark:bg-white/5'>
                  <tr>
                    <th className='px-6 py-4 font-medium text-gray-500 dark:text-gray-400'>
                      Invoice
                    </th>
                    <th className='px-6 py-4 font-medium text-gray-500 dark:text-gray-400'>
                      Customer
                    </th>
                    <th className='px-6 py-4 font-medium text-gray-500 dark:text-gray-400'>
                      Status
                    </th>
                    <th className='px-6 py-4 font-medium text-gray-500 dark:text-gray-400'>
                      Total
                    </th>
                    <th className='px-6 py-4 font-medium text-gray-500 dark:text-gray-400'>
                      Tanggal
                    </th>
                  </tr>
                </thead>
                <tbody className='divide-y divide-gray-100 dark:divide-gray-800'>
                  {recent_transactions?.data?.length > 0 ? (
                    recent_transactions.data.map((trx) => (
                      <tr
                        key={trx.id}
                        className='transition-colors hover:bg-gray-50/50 dark:hover:bg-white/5'
                      >
                        <td className='px-6 py-4 font-medium text-gray-900 dark:text-gray-100'>
                          #{trx.invoice_code}
                        </td>
                        <td className='px-6 py-4 text-gray-600 dark:text-gray-300'>{trx.name}</td>
                        <td className='px-6 py-4'>
                          <span
                            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${getStatusColor(trx.status)}`}
                          >
                            {trx.status}
                          </span>
                        </td>
                        <td className='px-6 py-4 font-medium text-gray-900 dark:text-gray-100'>
                          {formatRupiah(trx.grand_total)}
                        </td>
                        <td className='px-6 py-4 text-gray-500 dark:text-gray-400'>
                          {new Date(trx.created_at).toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                          })}
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td
                        colSpan={5}
                        className='px-6 py-10 text-center text-gray-500 dark:text-gray-400'
                      >
                        Belum ada transaksi terbaru.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
