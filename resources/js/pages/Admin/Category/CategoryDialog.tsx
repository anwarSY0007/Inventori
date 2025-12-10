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
import { Category } from '@/types';
import { Eye, Pencil, TagsIcon } from 'lucide-react';

interface Props {
  categories: Category;
}

export default function CategoryDialog({ categories }: Props) {
  return (
    <Dialog>
      <DialogTrigger asChild>
        <>
          <Button size='sm' variant='outline' className='rounded-full' aria-label='Lihat Detail'>
            <Eye className='h-4 w-4' />
          </Button>
          <Button size='sm' variant='outline' className='rounded-full' aria-label='Edit'>
            <Pencil className='h-4 w-4' />
          </Button>
        </>
      </DialogTrigger>

      <DialogContent className='sm:max-w-[425px]'>
        <DialogHeader>
          <DialogTitle>Detail category</DialogTitle>
          <DialogDescription>Informasi lengkap category.</DialogDescription>
        </DialogHeader>

        <div className='grid gap-6 py-4'>
          <div className='grid gap-3 border-t pt-4 text-sm'>
            <div className='flex items-center justify-between rounded-lg p-2 hover:bg-muted/50'>
              <div className='flex items-center gap-3 text-muted-foreground'>
                <TagsIcon className='h-4 w-4' />
                <span>Nama Category</span>
              </div>
              <span className='font-medium text-foreground'>{categories.name}</span>
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
