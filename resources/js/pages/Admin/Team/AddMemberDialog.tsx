import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { store } from '@/routes/team/members';
import { useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useState } from 'react';

interface Props {
  children: React.ReactNode;
  available_roles?: { value: string; label: string }[];
}

export default function AddMemberDialog({ children, available_roles = [] }: Props) {
  const [open, setOpen] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(store.url(), {
      onSuccess: () => {
        setOpen(false);
        reset();
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className='sm:max-w-[425px]'>
        <DialogHeader>
          <DialogTitle>Tambah Anggota Tim</DialogTitle>
          <DialogDescription>
            Undang staf baru untuk membantu mengelola toko Anda.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='grid gap-4 py-4'>
          <div className='grid gap-2'>
            <Label htmlFor='name'>Nama Lengkap</Label>
            <Input
              id='name'
              value={data.name}
              onChange={(e) => setData('name', e.target.value)}
              placeholder='Contoh: Budi Santoso'
              required
            />
            <InputError message={errors.name} />
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='email'>Email</Label>
            <Input
              id='email'
              type='email'
              value={data.email}
              onChange={(e) => setData('email', e.target.value)}
              placeholder='budi@toko.com'
              required
            />
            <InputError message={errors.email} />
          </div>

          <div className='grid grid-cols-2 gap-4'>
            <div className='grid gap-2'>
              <Label htmlFor='password'>Password</Label>
              <Input
                id='password'
                type='password'
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
                required
              />
              <InputError message={errors.password} />
            </div>
            <div className='grid gap-2'>
              <Label htmlFor='password_confirmation'>Konfirmasi</Label>
              <Input
                id='password_confirmation'
                type='password'
                value={data.password_confirmation}
                onChange={(e) => setData('password_confirmation', e.target.value)}
                required
              />
            </div>
          </div>

          <div className='grid gap-2'>
            <Label htmlFor='role'>Peran (Role)</Label>
            <Select value={data.role} onValueChange={(val) => setData('role', val)}>
              <SelectTrigger>
                <SelectValue placeholder='Pilih peran' />
              </SelectTrigger>
              <SelectContent>
                {available_roles.map((role) => (
                  <SelectItem key={role.value} value={role.value}>
                    {role.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={errors.role} />
          </div>

          <DialogFooter className='pt-4'>
            <Button type='button' variant='outline' onClick={() => setOpen(false)}>
              Batal
            </Button>
            <Button type='submit' disabled={processing}>
              {processing && <Loader2 className='mr-2 h-4 w-4 animate-spin' />}
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
