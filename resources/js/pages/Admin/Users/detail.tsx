import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { assignRole, index } from '@/routes/admin/users';
import { SharedData, User } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Calendar, Mail, Phone, Shield, Store, Users as UsersIcon } from 'lucide-react';
import { useState } from 'react';

interface PageProps {
  user: User;
}

export default function UserDetailPage({ user }: PageProps) {
  const { auth } = usePage<SharedData>().props;
  const [selectedRole, setSelectedRole] = useState('');
  const [isAssigning, setIsAssigning] = useState(false);

  const getTeamName = (team: User['current_teams']) => {
    if (!team) return 'No Team';
    if (typeof team === 'string') return team;
    return team.name;
  };

  const handleAssignRole = () => {
    if (!selectedRole) return;

    setIsAssigning(true);
    router.post(
      assignRole.url(user.id),
      { role: selectedRole },
      {
        preserveScroll: true,
        onSuccess: () => {
          setSelectedRole('');
        },
        onFinish: () => {
          setIsAssigning(false);
        },
      },
    );
  };

  const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const canAssignRole = auth.user.roles?.some((role) => role.name === 'super_admin');

  return (
    <AppLayout>
      <Head title={`Detail User - ${user.name}`} />
      <div className='flex h-full flex-col gap-6 p-6'>
        {/* Header with Back Button */}
        <div className='flex items-center gap-4'>
          <Link href={index.url()}>
            <Button variant='outline' size='icon'>
              <ArrowLeft className='h-4 w-4' />
            </Button>
          </Link>
          <HeadingSmall title='Detail Pengguna' description='Informasi lengkap pengguna' />
        </div>

        <div className='grid gap-6 lg:grid-cols-3'>
          {/* Left Column - Profile */}
          <div className='lg:col-span-1'>
            <Card>
              <CardHeader className='text-center'>
                <div className='mb-4 flex justify-center'>
                  <Avatar className='h-24 w-24 border-4 border-muted'>
                    <AvatarImage src={user.avatar} />
                    <AvatarFallback className='text-3xl'>
                      {user.name.substring(0, 2).toUpperCase()}
                    </AvatarFallback>
                  </Avatar>
                </div>
                <CardTitle className='text-xl'>{user.name}</CardTitle>
                <CardDescription>{user.email}</CardDescription>
              </CardHeader>
              <CardContent>
                <div className='space-y-4'>
                  <Separator />
                  <div className='space-y-3'>
                    <h4 className='flex items-center gap-2 text-sm font-semibold'>
                      <Shield className='h-4 w-4' />
                      Roles
                    </h4>
                    <div className='flex flex-wrap gap-2'>
                      {user.roles && Array.isArray(user.roles) && user.roles.length > 0 ? (
                        user.roles.map((role) => (
                          <Badge key={role.id} variant='secondary' className='capitalize'>
                            {role.name.replace('_', ' ')}
                          </Badge>
                        ))
                      ) : (
                        <Badge variant='outline'>No Role</Badge>
                      )}
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Right Column - Details */}
          <div className='space-y-6 lg:col-span-2'>
            {/* Contact Information */}
            <Card>
              <CardHeader>
                <CardTitle>Informasi Kontak</CardTitle>
                <CardDescription>Detail kontak dan informasi pribadi</CardDescription>
              </CardHeader>
              <CardContent className='space-y-4'>
                <div className='grid gap-4 sm:grid-cols-2'>
                  <div className='space-y-2'>
                    <div className='flex items-center gap-2 text-muted-foreground'>
                      <Mail className='h-4 w-4' />
                      <span className='text-sm font-medium'>Email</span>
                    </div>
                    <p className='text-sm font-semibold'>{user.email}</p>
                  </div>

                  <div className='space-y-2'>
                    <div className='flex items-center gap-2 text-muted-foreground'>
                      <Phone className='h-4 w-4' />
                      <span className='text-sm font-medium'>Telepon</span>
                    </div>
                    <p className='text-sm font-semibold'>{user.phone || '-'}</p>
                  </div>

                  <div className='space-y-2'>
                    <div className='flex items-center gap-2 text-muted-foreground'>
                      <Calendar className='h-4 w-4' />
                      <span className='text-sm font-medium'>Bergabung</span>
                    </div>
                    <p className='text-sm font-semibold'>{formatDate(user.created_at)}</p>
                  </div>

                  <div className='space-y-2'>
                    <div className='flex items-center gap-2 text-muted-foreground'>
                      <Store className='h-4 w-4' />
                      <span className='text-sm font-medium'>Tim Aktif</span>
                    </div>
                    <p className='text-sm font-semibold'>{getTeamName(user.current_teams)}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Teams Information */}
            {user.teams && Array.isArray(user.teams) && user.teams.length > 0 && (
              <Card>
                <CardHeader>
                  <CardTitle className='flex items-center gap-2'>
                    <UsersIcon className='h-5 w-5' />
                    Tim yang Diikuti
                  </CardTitle>
                  <CardDescription>Daftar tim yang diikuti oleh pengguna ini</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className='space-y-3'>
                    {user.teams.map((team) => (
                      <div
                        key={team.id}
                        className='flex items-center justify-between rounded-lg border bg-muted/50 p-3'
                      >
                        <div className='flex items-center gap-3'>
                          <div className='flex h-10 w-10 items-center justify-center rounded-full bg-primary/10'>
                            <Store className='h-5 w-5 text-primary' />
                          </div>
                          <div>
                            <p className='font-semibold'>{team.name}</p>
                            <p className='text-xs text-muted-foreground'>ID: {team.id}</p>
                          </div>
                        </div>
                        {user.current_team_id === team.id && <Badge variant='default'>Aktif</Badge>}
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Assign Role - Admin Only */}
            {canAssignRole && (
              <Card>
                <CardHeader>
                  <CardTitle className='flex items-center gap-2'>
                    <Shield className='h-5 w-5' />
                    Kelola Role
                  </CardTitle>
                  <CardDescription>Assign atau ubah role pengguna</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className='flex gap-3'>
                    <div className='flex-1'>
                      <Label htmlFor='role-select' className='sr-only'>
                        Pilih Role
                      </Label>
                      <Select value={selectedRole} onValueChange={setSelectedRole}>
                        <SelectTrigger id='role-select'>
                          <SelectValue placeholder='Pilih role...' />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value='super_admin'>Super Admin</SelectItem>
                          <SelectItem value='merchant_owner'>Merchant Owner</SelectItem>
                          <SelectItem value='cashier'>Cashier</SelectItem>
                          <SelectItem value='guest_user'>Guest User</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <Button onClick={handleAssignRole} disabled={!selectedRole || isAssigning}>
                      {isAssigning ? 'Menyimpan...' : 'Assign Role'}
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
