import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { User } from '@/types';
import { Calendar, Eye, Mail, Phone, Store } from 'lucide-react';

interface Props {
  user: User;
}

export default function UserDetailDialog({ user }: Props) {
  const getTeamName = (team: User['current_teams']) => {
    if (!team) return 'No Team';
    if (typeof team === 'string') return team;
    return team.name;
  };
  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button size='sm' variant='outline' className='rounded-full' aria-label='Lihat Detail'>
          <Eye className='h-4 w-4' />
        </Button>
      </DialogTrigger>

      <DialogContent className='sm:max-w-[425px]'>
        <DialogHeader>
          <DialogTitle>Detail Pengguna</DialogTitle>
          <DialogDescription>Informasi lengkap akun pengguna.</DialogDescription>
        </DialogHeader>

        <div className='grid gap-6 py-4'>
          {/* Header Profil: Avatar & Nama */}
          <div className='flex flex-col items-center gap-3 text-center'>
            <Avatar className='h-20 w-20 border-2 border-muted'>
              <AvatarImage src={user.avatar} />
              <AvatarFallback className='text-2xl'>
                {user.name.substring(0, 2).toUpperCase()}
              </AvatarFallback>
            </Avatar>
            <div className='grid gap-1'>
              <h3 className='text-xl leading-none font-semibold'>{user.name}</h3>
              <Badge variant='secondary' className='mx-auto w-fit px-3 capitalize'>
                {user.role_label}
              </Badge>
            </div>
          </div>

          {/* List Informasi */}
          <div className='grid gap-3 border-t pt-4 text-sm'>
            <div className='flex items-center justify-between rounded-lg p-2 hover:bg-muted/50'>
              <div className='flex items-center gap-3 text-muted-foreground'>
                <Mail className='h-4 w-4' />
                <span>Email</span>
              </div>
              <span className='font-medium text-foreground'>{user.email}</span>
            </div>

            <div className='flex items-center justify-between rounded-lg p-2 hover:bg-muted/50'>
              <div className='flex items-center gap-3 text-muted-foreground'>
                <Phone className='h-4 w-4' />
                <span>No. Telepon</span>
              </div>
              {/* Menampilkan phone dari model User */}
              <span className='font-medium text-foreground'>{user.phone}</span>
            </div>

            <div className='flex items-center justify-between rounded-lg p-2 hover:bg-muted/50'>
              <div className='flex items-center gap-3 text-muted-foreground'>
                <Store className='h-4 w-4' />
                <span>Tim / Toko</span>
              </div>
              <span className='max-w-[200px] truncate text-right font-medium text-foreground'>
                {getTeamName(user.current_teams)}
              </span>
            </div>

            <div className='flex items-center justify-between rounded-lg p-2 hover:bg-muted/50'>
              <div className='flex items-center gap-3 text-muted-foreground'>
                <Calendar className='h-4 w-4' />
                <span>Bergabung</span>
              </div>
              <span className='font-medium text-foreground'>{user.created_at}</span>
            </div>
          </div>
        </div>

        <DialogFooter>
          <DialogClose asChild>
            <Button variant='outline' className='w-full sm:w-auto'>
              Tutup
            </Button>
          </DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
