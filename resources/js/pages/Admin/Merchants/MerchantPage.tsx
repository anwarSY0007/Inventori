import { DataTable } from '@/components/data-table/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { create } from '@/routes/admin/merchants';
import { Merchant } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { DollarSign, Plus, TrendingUp, Users } from 'lucide-react';
import { merchantColumn } from './MerchantColumn';

interface MerchantProps {
  merchants: Merchant[];
  stats: {
    total_transactions: number;
    total_revenue: number;
    total_customers: number;
    vip_merchants: number;
  };
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
  filters?: {
    search?: string;
    sort_by?: string;
    sort_direction?: string;
  };
}

export default function MerchantPage({ merchants, meta, filters, stats }: MerchantProps) {
  const formatIDR = (val: number) =>
    new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(val);

  const toolbar = (
    <div className='flex items-center gap-2'>
      <Select
        value={filters?.sort_by || 'recent'}
        onValueChange={(value) => {
          router.get(
            'admin/merchants',
            {
              ...filters,
              sort_by: value,
            },
            {
              preserveState: true,
              preserveScroll: true,
            },
          );
        }}
      >
        <SelectTrigger className='h-8 w-[180px]'>
          <SelectValue placeholder='Sort by...' />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value='recent'>Most Recent</SelectItem>
          <SelectItem value='name'>Name (A-Z)</SelectItem>
          <SelectItem value='orders'>Most Orders</SelectItem>
          <SelectItem value='spent'>Highest Spent</SelectItem>
        </SelectContent>
      </Select>
    </div>
  );
  return (
    <AppLayout>
      <Head title={`Merchant Total ${merchants.length}`} />
      <div className='flex h-full flex-col gap-6 p-6'>
        <div className='flex items-center justify-between'>
          <div className='mb-8'>
            <h1 className='text-3xl font-bold tracking-tight'>Merchant</h1>
            <p className='text-muted-foreground'> Manage your merchants ({meta.total} total)</p>
          </div>
          <div className='mb-8'>
            <Button asChild>
              <Link href={create.url()}>
                <Plus className='mr-2 h-4 w-4' />
                Add Merchant
              </Link>
            </Button>
          </div>
        </div>

        {/* STATS CARDS */}
        <div className='grid gap-4 md:grid-cols-4'>
          {/* Card 1: Total Pelanggan (Akumulasi dari semua toko) */}
          <div className='rounded-xl border bg-linear-to-br from-blue-50 to-blue-100 p-4 dark:from-blue-950 dark:to-blue-900'>
            <div className='flex items-center justify-between'>
              <div>
                <p className='text-sm font-medium text-muted-foreground'>Total Customers</p>
                <p className='mt-1 text-2xl font-bold'>{stats.total_customers}</p>
              </div>
              <div className='rounded-full bg-blue-200/50 p-2 dark:bg-blue-800/50'>
                <Users className='h-5 w-5 text-blue-600 dark:text-blue-300' />
              </div>
            </div>
          </div>

          {/* Card 2: VIP Merchants */}
          <div className='rounded-xl border bg-linear-to-br from-purple-50 to-purple-100 p-4 dark:from-purple-950 dark:to-purple-900'>
            <div className='flex items-center justify-between'>
              <div>
                <p className='text-sm font-medium text-muted-foreground'>VIP Merchants</p>
                <p className='mt-1 text-2xl font-bold'>{stats.vip_merchants}</p>
              </div>
              <Badge className='bg-purple-500 hover:bg-purple-600'>VIP</Badge>
            </div>
          </div>

          {/* Card 3: Transaction Count (Opsional) */}
          <div className='rounded-xl border bg-linear-to-br from-orange-50 to-orange-100 p-4 dark:from-orange-950 dark:to-orange-900'>
            <div className='flex items-center justify-between'>
              <div>
                <p className='text-sm font-medium text-muted-foreground'>Total Transactions</p>
                <p className='mt-1 text-2xl font-bold'>{stats.total_transactions}</p>
              </div>
              <div className='rounded-full bg-orange-200/50 p-2 dark:bg-orange-800/50'>
                <TrendingUp className='h-5 w-5 text-orange-600 dark:text-orange-300' />
              </div>
            </div>
          </div>

          {/* Card 4: Total Revenue (Omzet) */}
          <div className='rounded-xl border bg-linear-to-br from-green-50 to-green-100 p-4 dark:from-green-950 dark:to-green-900'>
            <div className='flex items-center justify-between'>
              <div className='overflow-hidden'>
                <p className='text-sm font-medium text-muted-foreground'>Total Revenue</p>
                <p
                  className='mt-1 truncate text-xl font-bold'
                  title={formatIDR(stats.total_revenue)}
                >
                  {formatIDR(stats.total_revenue)}
                </p>
              </div>
              <div className='rounded-full bg-green-200/50 p-2 dark:bg-green-800/50'>
                <DollarSign className='h-5 w-5 text-green-600 dark:text-green-300' />
              </div>
            </div>
          </div>
        </div>

        <DataTable
          columns={merchantColumn}
          data={merchants}
          searchKey='name'
          toolbar={toolbar}
          searchPlaceholder='Search merchant by name...'
          onRowClick={(merchant) => router.visit(`/admin/merchants'/${merchant.id}`)}
        />
      </div>
    </AppLayout>
  );
}
