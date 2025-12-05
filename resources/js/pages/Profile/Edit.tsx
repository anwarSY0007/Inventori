import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { update } from '@/routes/profile';
import { switchMethod } from '@/routes/team';
import { User } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Camera, Shield, Store } from 'lucide-react';
import { useRef, useState } from 'react';

interface PageProps {
  user: User;
}

export default function ProfileEditPage({ user }: PageProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [avatarPreview, setAvatarPreview] = useState<string | undefined>(user.avatar);

  const { data, setData, post, processing, errors } = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone || '',
    avatar: null as File | null,
    _method: 'PUT',
  });

  const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setData('avatar', file);
      setAvatarPreview(URL.createObjectURL(file));
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(update.url(), {
      preserveScroll: true,
      onSuccess: () => {
        setData('avatar', null);
      },
    });
  };

  const handleSwitchTeam = (teamId: string) => {
    router.post(
      switchMethod.url(),
      { team_id: teamId },
      {
        preserveScroll: true,
      },
    );
  };

  const getTeamName = (team: User['current_teams']) => {
    if (!team) return 'No Team';
    if (typeof team === 'string') return team;
    return team.name;
  };

  return (
    <AppLayout>
      <Head title='Edit Profile' />
      {/* <SettingsLayout> */}
      <div className='flex h-full flex-col gap-6 p-6'>
        <HeadingSmall title='Edit Profile' description='Perbarui informasi akun Anda' />

        <div className='grid gap-6 lg:grid-cols-3'>
          {/* Left Column - Avatar & Roles */}
          <div className='space-y-6 lg:col-span-1'>
            {/* Avatar Card */}
            <Card>
              <CardHeader>
                <CardTitle>Foto Profile</CardTitle>
                <CardDescription>Update foto profile Anda</CardDescription>
              </CardHeader>
              <CardContent className='space-y-4'>
                <div className='flex flex-col items-center gap-4'>
                  <div className='relative'>
                    <Avatar className='h-32 w-32 border-4 border-muted'>
                      <AvatarImage src={avatarPreview} />
                      <AvatarFallback className='text-4xl'>
                        {user.name.substring(0, 2).toUpperCase()}
                      </AvatarFallback>
                    </Avatar>
                    <Button
                      type='button'
                      size='icon'
                      variant='secondary'
                      className='absolute right-0 bottom-0 rounded-full'
                      onClick={() => fileInputRef.current?.click()}
                    >
                      <Camera className='h-4 w-4' />
                    </Button>
                  </div>

                  <input
                    ref={fileInputRef}
                    type='file'
                    accept='image/*'
                    onChange={handleAvatarChange}
                    className='hidden'
                  />

                  <div className='text-center'>
                    <p className='text-sm font-medium'>{user.name}</p>
                    <p className='text-xs text-muted-foreground'>{user.email}</p>
                  </div>

                  <p className='text-center text-xs text-muted-foreground'>
                    Max 2MB. Format: JPG, PNG, GIF
                  </p>
                </div>

                {errors.avatar && <p className='text-sm text-destructive'>{errors.avatar}</p>}
              </CardContent>
            </Card>

            {/* Roles Card */}
            <Card>
              <CardHeader>
                <CardTitle className='flex items-center gap-2'>
                  <Shield className='h-5 w-5' />
                  Roles Anda
                </CardTitle>
                <CardDescription>Role yang dimiliki akun Anda</CardDescription>
              </CardHeader>
              <CardContent>
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
              </CardContent>
            </Card>
          </div>

          {/* Right Column - Form & Teams */}
          <div className='space-y-6 lg:col-span-2'>
            {/* Profile Form */}
            <Card>
              <CardHeader>
                <CardTitle>Informasi Pribadi</CardTitle>
                <CardDescription>Update informasi dasar akun Anda</CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleSubmit} className='space-y-4'>
                  {/* Name */}
                  <div className='space-y-2'>
                    <Label htmlFor='name'>Nama Lengkap</Label>
                    <Input
                      id='name'
                      type='text'
                      value={data.name}
                      onChange={(e) => setData('name', e.target.value)}
                      className={errors.name ? 'border-destructive' : ''}
                    />
                    {errors.name && <p className='text-sm text-destructive'>{errors.name}</p>}
                  </div>

                  {/* Email */}
                  <div className='space-y-2'>
                    <Label htmlFor='email'>Email</Label>
                    <Input
                      id='email'
                      type='email'
                      value={data.email}
                      onChange={(e) => setData('email', e.target.value)}
                      className={errors.email ? 'border-destructive' : ''}
                    />
                    {errors.email && <p className='text-sm text-destructive'>{errors.email}</p>}
                  </div>

                  {/* Phone */}
                  <div className='space-y-2'>
                    <Label htmlFor='phone'>No. Telepon</Label>
                    <Input
                      id='phone'
                      type='tel'
                      value={data.phone}
                      onChange={(e) => setData('phone', e.target.value)}
                      className={errors.phone ? 'border-destructive' : ''}
                    />
                    {errors.phone && <p className='text-sm text-destructive'>{errors.phone}</p>}
                  </div>

                  <Button type='submit' disabled={processing} className='w-full sm:w-auto'>
                    {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                  </Button>
                </form>
              </CardContent>
            </Card>

            {/* Teams Card */}
            <Card>
              <CardHeader>
                <CardTitle className='flex items-center gap-2'>
                  <Store className='h-5 w-5' />
                  Tim Anda
                  <span className='max-w-[150px] truncate font-medium'>
                    {getTeamName(user.current_teams)}
                  </span>
                </CardTitle>
                <CardDescription>Kelola dan switch antar tim yang Anda ikuti</CardDescription>
              </CardHeader>
              <CardContent>
                {user.teams && Array.isArray(user.teams) && user.teams.length > 0 ? (
                  <div className='space-y-3'>
                    {user.teams.map((team) => (
                      <div
                        key={team.id}
                        className='flex items-center justify-between rounded-lg border bg-muted/50 p-4'
                      >
                        <div className='flex items-center gap-3'>
                          <div className='flex h-10 w-10 items-center justify-center rounded-full bg-primary/10'>
                            <Store className='h-5 w-5 text-primary' />
                          </div>
                          <div>
                            <p className='font-semibold'>{team.name}</p>
                            <p className='text-xs text-muted-foreground'>
                              {team.id === user.current_team_id ? 'Tim Aktif' : 'Klik untuk switch'}
                            </p>
                          </div>
                        </div>

                        {team.id === user.current_team_id ? (
                          <Badge variant='default'>Aktif</Badge>
                        ) : (
                          <Button
                            size='sm'
                            variant='outline'
                            onClick={() => handleSwitchTeam(team.id)}
                          >
                            Switch
                          </Button>
                        )}
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className='py-8 text-center text-muted-foreground'>
                    <Store className='mx-auto mb-3 h-12 w-12 opacity-20' />
                    <p className='text-sm'>Anda belum tergabung dalam tim apapun</p>
                  </div>
                )}
              </CardContent>
            </Card>
            <DeleteUser />
          </div>
        </div>
      </div>

      {/* </SettingsLayout> */}
    </AppLayout>
  );
}
