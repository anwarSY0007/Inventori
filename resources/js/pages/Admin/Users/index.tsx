import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Item, ItemActions, ItemContent, ItemMedia, ItemTitle } from '@/components/ui/item';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/users';
import { User } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Search, Store, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import UserDetailDialog from './UserDialog';

interface PageProps {
  users: User[];

  filters: {
    search?: string;
    role?: string;
  };
}
export default function UsersPage({ users, filters }: PageProps) {
  const [search, setSearch] = useState(filters.search || '');
  const [roleFilter, setRoleFilter] = useState(filters.role || '');

  useEffect(() => {
    console.table(users);
  }, [users]);

  const handleSearch = () => {
    router.get(
      index.url(),
      {
        search: search,
        role: roleFilter,
      },
      {
        preserveState: true,
        replace: true,
      },
    );
  };

  const handleReset = () => {
    setSearch('');
    setRoleFilter('');
    router.get(index.url());
  };

  const getTeamName = (team: User['current_teams']) => {
    if (!team) return 'No Team';
    if (typeof team === 'string') return team;
    return team.name;
  };

  const getRoleBadge = (user: User) => {
    if (user.roles && Array.isArray(user.roles) && user.roles.length > 0) {
      return user.roles[0].name;
    }
    return 'No Role';
  };
  return (
    <AppLayout>
      <Head title='Users Debug' />
      <div className='flex h-full flex-col gap-6 p-6'>
        <div className='flex items-center justify-between'>
          <HeadingSmall
            title='Manajemen Pengguna'
            description={`Total ${users.length} pengguna terdaftar dalam sistem.`}
          />
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

          <Select value={roleFilter} onValueChange={setRoleFilter}>
            <SelectTrigger className='w-[180px]'>
              <SelectValue placeholder='Semua Role' />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value='-'>Semua Role</SelectItem>
              <SelectItem value='super_admin'>Super Admin</SelectItem>
              <SelectItem value='merchant_owner'>Merchant Owner</SelectItem>
              <SelectItem value='cashier'>Cashier</SelectItem>
              <SelectItem value='guest_user'>Guest User</SelectItem>
            </SelectContent>
          </Select>

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
              {users.map((user) => (
                <Item key={user.id} variant='outline' className='items-center px-4 py-3'>
                  <ItemMedia>
                    <Avatar className='size-10'>
                      <AvatarImage src={user.avatar} />
                      <AvatarFallback>{user.name.substring(0, 2).toUpperCase()}</AvatarFallback>
                    </Avatar>
                  </ItemMedia>
                  <ItemContent className='ml-4 flex flex-1'>
                    <div className='flex flex-col gap-0.5'>
                      <ItemTitle className='text-sm font-semibold'>{user.name}</ItemTitle>
                      <div className='m-0 text-xs text-muted-foreground'>
                        <Badge
                          variant='secondary'
                          className='h-5 px-2 text-[10px] font-medium capitalize'
                        >
                          {getRoleBadge(user)}
                        </Badge>
                        <div className='flex items-center gap-1.5 text-xs text-muted-foreground'>
                          <Store className='h-3 w-3' />
                          <span className='max-w-[150px] truncate font-medium'>
                            {getTeamName(user.current_teams)}
                          </span>
                        </div>
                      </div>
                    </div>
                  </ItemContent>
                  <ItemActions>
                    <UserDetailDialog user={user} />
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
