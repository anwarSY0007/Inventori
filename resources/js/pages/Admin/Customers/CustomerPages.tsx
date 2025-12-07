import { DataTable } from '@/components/data-table/data-table';
import { DataTableColumnHeader } from '@/components/data-table/data-table-column-header';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Customer } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Calendar, DollarSign, Eye, MoreHorizontal, ShoppingBag, TrendingUp } from 'lucide-react';
import { useEffect } from 'react';

interface PageProps {
  customers: Customer[];
  team: {
    id: string;
    name: string;
  };
  stats: {
    total_customers: number;
  };
  filters?: {
    search?: string;
    sort_by?: string;
    sort_direction?: string;
  };
}

export default function CustomerPage({ customers, team, stats, filters }: PageProps) {
  useEffect(() => {
    console.table(customers);
  }, [customers]);

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const getCustomerSegment = (
    totalSpent: number,
  ): { label: string; variant: 'default' | 'secondary' | 'outline' } => {
    if (totalSpent >= 10000000) {
      return { label: 'VIP', variant: 'default' };
    } else if (totalSpent >= 5000000) {
      return { label: 'Gold', variant: 'secondary' };
    } else if (totalSpent >= 1000000) {
      return { label: 'Silver', variant: 'outline' };
    }
    return { label: 'New', variant: 'outline' };
  };

  const columns: ColumnDef<Customer>[] = [
    {
      accessorKey: 'name',
      header: ({ column }) => <DataTableColumnHeader column={column} title='Customer' />,
      cell: ({ row }) => {
        const customer = row.original;
        return (
          <div className='flex items-center gap-3'>
            <Avatar className='h-10 w-10'>
              <AvatarImage src={customer.avatar} alt={customer.name} />
              <AvatarFallback className='bg-gradient-to-br from-purple-500 to-pink-500 text-white'>
                {customer.name
                  .split(' ')
                  .map((n) => n[0])
                  .join('')
                  .toUpperCase()}
              </AvatarFallback>
            </Avatar>
            <div className='flex flex-col'>
              <span className='font-medium'>{customer.name}</span>
              <span className='text-sm text-muted-foreground'>{customer.email}</span>
              {customer.phone && (
                <span className='text-xs text-muted-foreground'>{customer.phone}</span>
              )}
            </div>
          </div>
        );
      },
    },
    {
      accessorKey: 'total_orders',
      header: ({ column }) => <DataTableColumnHeader column={column} title='Orders' />,
      cell: ({ row }) => {
        const orders = row.getValue('total_orders') as number;
        return (
          <div className='flex items-center gap-2'>
            <ShoppingBag className='h-4 w-4 text-muted-foreground' />
            <span className='font-medium'>{orders}</span>
          </div>
        );
      },
    },
    {
      accessorKey: 'total_spent',
      header: ({ column }) => <DataTableColumnHeader column={column} title='Total Spent' />,
      cell: ({ row }) => {
        const amount = row.getValue('total_spent') as number;
        const segment = getCustomerSegment(amount);

        return (
          <div className='flex flex-col gap-1'>
            <div className='flex items-center gap-2'>
              <DollarSign className='h-4 w-4 text-green-600' />
              <span className='font-medium'>{formatCurrency(amount)}</span>
            </div>
            <Badge variant={segment.variant} className='w-fit text-xs'>
              {segment.label}
            </Badge>
          </div>
        );
      },
    },
    {
      accessorKey: 'last_order_at',
      header: ({ column }) => <DataTableColumnHeader column={column} title='Last Order' />,
      cell: ({ row }) => {
        const lastOrder = row.getValue('last_order_at') as string | undefined;

        if (!lastOrder) {
          return <span className='text-sm text-muted-foreground'>No orders yet</span>;
        }

        const daysSinceOrder = Math.floor(
          (new Date().getTime() - new Date(lastOrder).getTime()) / (1000 * 60 * 60 * 24),
        );

        return (
          <div className='flex flex-col'>
            <div className='flex items-center gap-2'>
              <Calendar className='h-3 w-3 text-muted-foreground' />
              <span className='text-sm'>{lastOrder}</span>
            </div>
            <span
              className={cn(
                'text-xs',
                daysSinceOrder > 30 ? 'text-orange-600' : 'text-muted-foreground',
              )}
            >
              {daysSinceOrder} days ago
            </span>
          </div>
        );
      },
    },
    {
      accessorKey: 'registered_at',
      header: ({ column }) => <DataTableColumnHeader column={column} title='Member Since' />,
      cell: ({ row }) => {
        return (
          <div className='flex flex-col'>
            <span className='text-sm'>{row.getValue('registered_at')}</span>
            {row.original.joined_team_at && (
              <span className='text-xs text-muted-foreground'>
                Joined: {row.original.joined_team_at}
              </span>
            )}
          </div>
        );
      },
    },
    {
      id: 'actions',
      cell: ({ row }) => {
        const customer = row.original;

        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant='ghost' className='h-8 w-8 p-0'>
                <span className='sr-only'>Open menu</span>
                <MoreHorizontal className='h-4 w-4' />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align='end'>
              <DropdownMenuLabel>Actions</DropdownMenuLabel>
              <DropdownMenuItem onClick={() => router.visit(`/team/customers/${customer.id}`)}>
                <Eye className='mr-2 h-4 w-4' />
                View Details
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => navigator.clipboard.writeText(customer.email)}>
                Copy email
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                onClick={() => router.visit(`/cashier/transactions/create?customer=${customer.id}`)}
              >
                <ShoppingBag className='mr-2 h-4 w-4' />
                Create Order
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];

  // Advanced toolbar with sort options
  const toolbar = (
    <div className='flex items-center gap-2'>
      <Select
        value={filters?.sort_by || 'recent'}
        onValueChange={(value) => {
          router.get(
            '/team/customers',
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
      <Head title={`Customers - ${team.name}`} />

      <div className='flex h-full flex-col gap-6 p-6'>
        {/* Header */}
        <div className='mb-8'>
          <h1 className='text-3xl font-bold tracking-tight'>Customers</h1>
          <p className='text-muted-foreground'>
            Manage customers for <strong>{team.name}</strong>
          </p>
        </div>

        {/* Stats Dashboard */}
        <div className='mb-8 grid gap-4 md:grid-cols-4'>
          <div className='rounded-lg border bg-gradient-to-br from-blue-50 to-blue-100 p-4 dark:from-blue-950 dark:to-blue-900'>
            <div className='flex items-center justify-between'>
              <div>
                <p className='text-sm font-medium text-muted-foreground'>Total Customers</p>
                <p className='text-3xl font-bold'>{stats.total_customers}</p>
              </div>
              <TrendingUp className='h-8 w-8 text-blue-600' />
            </div>
          </div>

          <div className='rounded-lg border bg-gradient-to-br from-green-50 to-green-100 p-4 dark:from-green-950 dark:to-green-900'>
            <div className='flex items-center justify-between'>
              <div>
                <p className='text-sm font-medium text-muted-foreground'>VIP Customers</p>
                <p className='text-3xl font-bold'>
                  {customers.filter((c) => c.total_spent >= 10000000).length}
                </p>
              </div>
              <Badge className='bg-yellow-500 text-white'>VIP</Badge>
            </div>
          </div>

          <div className='rounded-lg border bg-gradient-to-br from-purple-50 to-purple-100 p-4 dark:from-purple-950 dark:to-purple-900'>
            <div className='flex items-center justify-between'>
              <div>
                <p className='text-sm font-medium text-muted-foreground'>Total Orders</p>
                <p className='text-3xl font-bold'>
                  {customers.reduce((sum, c) => sum + c.total_orders, 0)}
                </p>
              </div>
              <ShoppingBag className='h-8 w-8 text-purple-600' />
            </div>
          </div>

          <div className='rounded-lg border bg-gradient-to-br from-orange-50 to-orange-100 p-4 dark:from-orange-950 dark:to-orange-900'>
            <div className='flex items-center justify-between'>
              <div>
                <p className='text-sm font-medium text-muted-foreground'>Total Revenue</p>
                <p className='text-xl font-bold'>
                  {formatCurrency(customers.reduce((sum, c) => sum + c.total_spent, 0))}
                </p>
              </div>
              <DollarSign className='h-8 w-8 text-orange-600' />
            </div>
          </div>
        </div>

        {/* DataTable */}
        <DataTable
          columns={columns}
          data={customers}
          searchKey='name'
          searchPlaceholder='Search customers by name or email...'
          toolbar={toolbar}
          onRowClick={(customer) => router.visit(`/team/customers/${customer.id}`)}
        />
      </div>
    </AppLayout>
  );
}
