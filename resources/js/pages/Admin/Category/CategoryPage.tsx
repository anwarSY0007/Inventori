import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Item, ItemActions, ItemContent, ItemTitle } from '@/components/ui/item';
import { ScrollArea } from '@/components/ui/scroll-area';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/categories';
import { Category } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import CategoryDialog from './CategoryDialog';

interface PageProps {
  categories: Category[];
  meta?: {
    total: number;
  };
  filters: {
    search?: string;
    role?: string;
  };
}
export default function CategoryPage({ categories, filters, meta }: PageProps) {
  const [search, setSearch] = useState(filters.search || '');

  useEffect(() => {
    console.table(categories);
  }, [categories]);

  const handleSearch = () => {
    router.get(
      index.url(),
      {
        search: search,
      },
      {
        preserveState: true,
        replace: true,
      },
    );
  };

  const handleReset = () => {
    setSearch('');
    router.get(index.url());
  };
  return (
    <AppLayout>
      <Head title='Categories' />
      <div className='flex h-full flex-col gap-6 p-6'>
        <div className='flex items-center justify-between'>
          <div className='mb-8'>
            <h1 className='text-3xl font-bold tracking-tight'>Category</h1>
            <p className='text-muted-foreground'> Manage your Category ({meta?.total} total)</p>
          </div>
          <div className='mb-8'>
            <Button asChild>
              {/* <CategoryFormDialog /> */}
              <Link href=''>
                <Plus className='mr-2 h-4 w-4' />
                Add Category
              </Link>
            </Button>
          </div>
        </div>

        <div className='flex flex-wrap gap-3'>
          <div className='relative min-w-[200px] flex-1'>
            <Search className='absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground' />
            <Input
              placeholder='Cari nama atau email...'
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
              className='pl-9'
            />
          </div>

          <Button onClick={handleSearch}>
            <Search className='mr-2 h-4 w-4' />
            Cari
          </Button>

          <Button variant='outline' onClick={handleReset}>
            <X className='mr-2 h-4 w-4' />
            Reset
          </Button>
        </div>

        <div className='flex-1 overflow-hidden rounded-xl border bg-background shadow-sm'>
          <ScrollArea className='h-[500px]'>
            <div className='flex flex-col gap-3 p-2'>
              {categories.map((category) => (
                <Item key={category.id} variant='outline' className='items-center px-4 py-3'>
                  <ItemContent className='ml-4 flex flex-1'>
                    <div className='flex flex-col gap-0.5'>
                      <ItemTitle className='text-sm font-semibold'>{category.name}</ItemTitle>
                    </div>
                  </ItemContent>
                  <ItemActions>
                    <CategoryDialog categories={category} />
                  </ItemActions>
                </Item>
              ))}
            </div>
          </ScrollArea>
        </div>
      </div>
    </AppLayout>
  );
}
