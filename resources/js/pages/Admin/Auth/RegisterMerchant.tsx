import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import AuthLayout from '@/layouts/auth-layout'; // Pastikan path layout ini benar
import { login } from '@/routes';
import { merchant } from '@/routes/register';
import { Head, Link, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { FormEventHandler } from 'react';

// Tipe data untuk prop 'roles' yang dikirim dari Controller
interface RegisterMerchantProps {
  roles: { value: string; label: string }[];
}

export default function RegisterMerchant({ roles }: RegisterMerchantProps) {
  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '', // Menambahkan field phone sesuai logika umum merchant
    merchant_name: '', // Field khusus untuk nama toko/merchant
    role: '', // Role pilihan user (Merchant Owner / Admin)
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    post(merchant.url(), {
      onFinish: () => reset('password', 'password_confirmation'),
    });
  };

  return (
    <AuthLayout title='Daftar Merchant Baru' description='Buat akun baru untuk mengelola toko Anda'>
      <Head title='Register Merchant' />
      <div className='flex w-full max-w-sm flex-col gap-6'>
        <form onSubmit={submit}>
          <div className='grid gap-6'>
            {/* Nama User */}
            <div className='grid gap-2'>
              <Label htmlFor='name'>Nama Lengkap</Label>
              <Input
                id='name'
                name='name'
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                required
                autoFocus
                placeholder='Contoh: Budi Santoso'
              />
              <InputError message={errors.name} />
            </div>

            {/* Email */}
            <div className='grid gap-2'>
              <Label htmlFor='email'>Email</Label>
              <Input
                id='email'
                type='email'
                name='email'
                value={data.email}
                onChange={(e) => setData('email', e.target.value)}
                required
                placeholder='nama@email.com'
              />
              <InputError message={errors.email} />
            </div>

            {/* Nama Merchant / Toko */}
            <div className='grid gap-2'>
              <Label htmlFor='merchant_name'>Nama Toko (Merchant)</Label>
              <Input
                id='merchant_name'
                name='merchant_name'
                value={data.merchant_name}
                onChange={(e) => setData('merchant_name', e.target.value)}
                required
                placeholder='Contoh: Toko Maju Jaya'
              />
              <InputError message={errors.merchant_name} />
            </div>

            {/* Role Selection */}
            <div className='grid gap-2'>
              <Label htmlFor='role'>Daftar Sebagai</Label>
              <Select value={data.role} onValueChange={(val) => setData('role', val)}>
                <SelectTrigger>
                  <SelectValue placeholder='Pilih Peran' />
                </SelectTrigger>
                <SelectContent>
                  {roles.map((role) => (
                    <SelectItem key={role.value} value={role.value}>
                      {role.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={errors.role} />
            </div>

            {/* Password */}
            <div className='grid gap-2'>
              <Label htmlFor='password'>Password</Label>
              <Input
                id='password'
                type='password'
                name='password'
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
                required
                autoComplete='new-password'
              />
              <InputError message={errors.password} />
            </div>

            {/* Konfirmasi Password */}
            <div className='grid gap-2'>
              <Label htmlFor='password_confirmation'>Konfirmasi Password</Label>
              <Input
                id='password_confirmation'
                type='password'
                name='password_confirmation'
                value={data.password_confirmation}
                onChange={(e) => setData('password_confirmation', e.target.value)}
                required
                autoComplete='new-password'
              />
              <InputError message={errors.password_confirmation} />
            </div>

            {/* Tombol Submit */}
            <Button type='submit' className='w-full' disabled={processing}>
              {processing && <Loader2 className='mr-2 h-4 w-4 animate-spin' />}
              Daftar Sekarang
            </Button>

            {/* Link Login */}
            <div className='text-center text-sm'>
              Sudah punya akun?{' '}
              <Link href={login.url()} className='underline underline-offset-4 hover:text-primary'>
                Masuk
              </Link>
            </div>
          </div>
        </form>
      </div>
    </AuthLayout>
  );
}
