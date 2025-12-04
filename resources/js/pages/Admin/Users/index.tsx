import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Item, ItemActions, ItemContent, ItemMedia, ItemTitle } from '@/components/ui/item';
import { ScrollArea } from '@/components/ui/scroll-area';
import AppLayout from '@/layouts/app-layout';
import { User } from '@/types';
import { Head } from '@inertiajs/react';
import { Store } from 'lucide-react';
import { useEffect } from 'react';
import UserDetailDialog from './UserDialog';

interface PageProps {
  users: User[];

  filters: {
    search?: string;
    role?: string;
  };
}
export default function UsersPage({ users }: PageProps) {
  useEffect(() => {
    console.table(users);
  }, [users]);

  const getTeamName = (team: User['current_teams']) => {
    if (!team) return 'No Team';
    if (typeof team === 'string') return team;
    return team.name;
  };
  return (
    <AppLayout>
      <Head title='Users Debug' />
      <div className='flex h-full flex-col gap-6 p-6'>
        {/* Header Section */}
        <div className='flex items-center justify-between'>
          <HeadingSmall
            title='Daftar Pengguna'
            description={`Total ${users.length} pengguna terdaftar.`}
          />
          {/* Bisa tambahkan tombol filter/search di sini nanti */}
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
                          {user.role_label}
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
