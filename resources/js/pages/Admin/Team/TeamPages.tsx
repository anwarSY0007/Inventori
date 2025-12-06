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
import { destroy as deleteTeams, index as teamPages } from '@/routes/team/members';
import { User } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Plus, Search, Trash2, UserCog, X } from 'lucide-react';
import { useState } from 'react';
import AddMemberDialog from './AddMemberDialog';

interface PageProps {
  members: User[];
  filters: {
    search?: string;
    role?: string;
  };
  available_roles?: { value: string; label: string }[];
}

export default function MyTeamPage({ members, filters, available_roles }: PageProps) {
  const [search, setSearch] = useState(filters.search || '');
  const [roleFilter, setRoleFilter] = useState(filters.role || '');

  // Handle Search
  const handleSearch = () => {
    router.get(
      teamPages.url(),
      { search: search, role: roleFilter },
      { preserveState: true, replace: true },
    );
  };

  const handleReset = () => {
    setSearch('');
    setRoleFilter('');
    // router.get(team.index());
  };

  // Handle Delete Member
  const handleDelete = (userId: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus anggota ini dari tim?')) {
      router.delete(deleteTeams.url(userId)); // Route: merchant.team.destroy
    }
  };

  const getRoleBadge = (user: User) => {
    // Prioritaskan label dari backend jika ada, atau fallback ke role name
    return user.role_label || user.roles?.[0]?.name || 'Staff';
  };

  return (
    <AppLayout>
      <Head title='Tim Saya' />
      <div className='flex h-full flex-col gap-6 p-6'>
        {/* Header Section */}
        <div className='flex items-center justify-between'>
          <HeadingSmall
            title='Tim Saya'
            description='Kelola staf, kasir, dan admin toko Anda di sini.'
          />
          {/* Tombol Tambah Anggota */}
          <AddMemberDialog available_roles={available_roles}>
            <Button>
              <Plus className='mr-2 h-4 w-4' />
              Tambah Anggota
            </Button>
          </AddMemberDialog>
        </div>

        {/* Filter Section */}
        <div className='flex flex-wrap gap-3'>
          <div className='relative min-w-[200px] flex-1'>
            <Search className='absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground' />
            <Input
              placeholder='Cari nama atau email staf...'
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
              className='pl-9'
            />
          </div>

          <Select value={roleFilter} onValueChange={setRoleFilter}>
            <SelectTrigger className='w-[180px]'>
              <SelectValue placeholder='Filter Role' />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value='-'>Semua Role</SelectItem>
              <SelectItem value='cashier'>Kasir</SelectItem>
              <SelectItem value='warehouse_staff'>Staf Gudang</SelectItem>
              <SelectItem value='admin_merchant'>Admin Toko</SelectItem>
            </SelectContent>
          </Select>

          <Button onClick={handleSearch} variant='secondary'>
            <Search className='mr-2 h-4 w-4' />
            Cari
          </Button>

          {(search || roleFilter) && (
            <Button variant='ghost' onClick={handleReset}>
              <X className='mr-2 h-4 w-4' />
              Reset
            </Button>
          )}
        </div>

        {/* List Member Section */}
        <div className='flex-1 overflow-hidden rounded-xl border bg-background shadow-sm'>
          <ScrollArea className='h-[500px]'>
            <div className='flex flex-col gap-2 p-2'>
              {members.length === 0 ? (
                <div className='flex h-40 flex-col items-center justify-center text-muted-foreground'>
                  <UserCog className='mb-2 h-8 w-8 opacity-50' />
                  <p>Belum ada anggota tim.</p>
                </div>
              ) : (
                members.map((user) => (
                  <Item key={user.id} variant='outline' className='items-center px-4 py-3'>
                    <ItemMedia>
                      <Avatar className='size-10'>
                        <AvatarImage src={user.avatar} />
                        <AvatarFallback>{user.name.substring(0, 2).toUpperCase()}</AvatarFallback>
                      </Avatar>
                    </ItemMedia>

                    <ItemContent className='ml-4 flex flex-1'>
                      <div className='flex flex-col gap-1'>
                        <div className='flex items-center gap-2'>
                          <ItemTitle className='text-sm font-semibold'>{user.name}</ItemTitle>
                          <Badge variant='outline' className='h-5 text-[10px] uppercase'>
                            {getRoleBadge(user)}
                          </Badge>
                        </div>
                        <div className='text-xs text-muted-foreground'>
                          {user.email} • Bergabung {user.created_at || '-'}
                        </div>
                      </div>
                    </ItemContent>

                    <ItemActions className='flex gap-2'>
                      {/* Tombol Lihat Detail */}

                      {/* Tombol Hapus */}
                      <Button
                        variant='ghost'
                        size='icon'
                        className='text-destructive hover:bg-destructive/10 hover:text-destructive'
                        onClick={() => handleDelete(user.id)}
                      >
                        <Trash2 className='h-4 w-4' />
                      </Button>
                    </ItemActions>
                  </Item>
                ))
              )}
            </div>
          </ScrollArea>
        </div>
      </div>
    </AppLayout>
  );
}
