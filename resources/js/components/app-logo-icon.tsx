import { cn } from '@/lib/utils';
import { AppLogoProps } from '@/types';

export default function AppLogoIcon({
  mode = 'text',
  src,
  text = 'INV',
  content,
  appName = 'Inventori App',
  className,
}: AppLogoProps) {
  return (
    <div className={cn('flex items-center gap-2 self-center font-medium', className)}>
      <div
        className={cn(
          'flex h-6 w-6 items-center justify-center rounded-md bg-primary text-primary-foreground',
          mode === 'image' && 'overflow-hidden bg-transparent',
        )}
      >
        {mode === 'text' && <span className='text-xs font-bold'>{text}</span>}
        {mode === 'image' && src && (
          <img src={src} alt='Logo' className='h-full w-full object-contain' />
        )}
        {mode === 'svg' && content}
      </div>

      {appName}
    </div>
  );
}
