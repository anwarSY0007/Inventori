import HeadingSmall from '@/components/heading-small';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Customer } from '@/types';
import { Head } from '@inertiajs/react';

interface PageProps {
  customers: Customer[];

  filters: {
    search?: string;
    role?: string;
  };
}

export default function CustomerIndexPage({ customers }: PageProps) {
  return (
    <AppLayout>
      <Head title='Pelanggan' />
      <div className='p-6'>
        <HeadingSmall title='Daftar Pelanggan' description='Manajemen data pelanggan toko Anda.' />

        <Card className='mt-6'>
          <CardContent className='p-6'>
            {/* Tampilkan tabel customer di sini */}
            <p>Total Pelanggan: {customers.length || 0}</p>
            <div className='mt-4 text-sm text-muted-foreground'>
              Fitur list customer akan tampil di sini.
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
