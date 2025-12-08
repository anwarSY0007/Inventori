import { DataTableColumnHeader } from '@/components/data-table/data-table-column-header';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Merchant } from '@/types';
import { router } from '@inertiajs/react';
import { DropdownMenu } from '@radix-ui/react-dropdown-menu';
import { ColumnDef } from '@tanstack/react-table';
import {
  Box,
  DollarSign,
  Eye,
  MapPin,
  MoreHorizontal,
  ShoppingBag,
  UserIcon,
  UsersRound,
} from 'lucide-react';

const getMerchantSegment = (
  totalSpent: number,
): { label: string; variant: 'default' | 'secondary' | 'outline' } => {
  if (totalSpent >= 1000_000_000) {
    return { label: 'VIP', variant: 'default' };
  } else if (totalSpent >= 500_000_000) {
    return { label: 'Gold', variant: 'secondary' };
  } else if (totalSpent >= 100_000_000) {
    return { label: 'Silver', variant: 'outline' };
  }
  return { label: 'New', variant: 'outline' };
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount);
};
export const merchantColumn: ColumnDef<Merchant>[] = [
  {
    id: 'no',
    header: 'No.',
    size: 50,
    cell: (info) => info.row.index + 1,
  },
  {
    accessorKey: 'name',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Merchant' />,
    cell: ({ row }) => {
      const merchant = row.original;
      return (
        <div className='flex items-center gap-3'>
          <Avatar className='h-10 w-10'>
            <AvatarImage src={merchant.thumbnail} alt={merchant.name} />
            <AvatarFallback className='bg-linear-to-br from-purple-500 to-pink-500 text-white'>
              {merchant.name
                .split(' ')
                .map((n) => n[0])
                .join('')
                .toUpperCase()}
            </AvatarFallback>
          </Avatar>
          <div className='flex flex-col'>
            <span className='font-medium'>{merchant.name}</span>
            {merchant.phone && (
              <span className='text-xs text-muted-foreground'>{merchant.phone}</span>
            )}
          </div>
        </div>
      );
    },
  },
  {
    accessorKey: 'keeper.name', // Mengambil dari nested object
    header: ({ column }) => <DataTableColumnHeader column={column} title='Owner' />,
    cell: ({ row }) => {
      const owner_name = row.original.keeper;
      if (!owner_name) return <span className='text-muted-foreground'>-</span>;

      return (
        <div className='flex items-center gap-2 text-sm'>
          <UserIcon className='h-3 w-3 text-muted-foreground' />
          <span>{owner_name.name}</span>
        </div>
      );
    },
  },
  {
    accessorKey: 'alamat', // Sesuai type kamu
    header: 'Address',
    cell: ({ row }) => (
      <div
        className='max-w-[250px] truncate text-sm text-muted-foreground'
        title={row.original.alamat}
      >
        <MapPin className='h-4 w-4 text-red-500' />
        <span className='font-semibold'>{row.original.alamat || '-'}</span>
      </div>
    ),
  },
  {
    accessorKey: 'total_products', // Sesuai type kamu
    header: ({ column }) => <DataTableColumnHeader column={column} title='Products' />,
    cell: ({ row }) => (
      <div className='flex items-center gap-2'>
        <Box className='h-4 w-4 text-blue-500' />
        <span className='font-semibold'>{row.original.total_products ?? 0}</span>
      </div>
    ),
  },
  {
    accessorKey: 'total_transactions',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Total Transactions' />,
    cell: ({ row }) => {
      const amount = row.getValue('total_transactions') as number;
      const segment = getMerchantSegment(amount);

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
    accessorKey: 'total_customers',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Pelanggan' />,
    cell: ({ row }) => (
      <div className='flex items-center gap-2'>
        <UsersRound className='h-4 w-4 text-orange-500' />
        <span className='font-semibold'>{row.original.total_customers ?? 0}</span>
      </div>
    ),
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      const merchant = row.original;

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
            <DropdownMenuItem onClick={() => router.visit(`/admin/merchants'/${merchant.id}`)}>
              <Eye className='mr-2 h-4 w-4' />
              View Details
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => router.visit(`/admin/merchants'/${merchant.id}`)}>
              <ShoppingBag className='mr-2 h-4 w-4' />
              Create Order
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      );
    },
  },
];
