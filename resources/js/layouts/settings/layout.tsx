import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn, resolveUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { SharedData, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function SettingsLayout({ children }: PropsWithChildren) {
  const { url } = usePage();
  const { auth } = usePage<SharedData>().props;

  const showStoreSettings = auth.user.roles?.some((r) =>
    ['merchant_owner', 'super_admin'].includes(r.name),
  );

  const sidebarNavItems: NavItem[] = [
    {
      title: 'Profile',
      href: edit(),
      icon: null,
    },
    {
      title: 'Password',
      href: editPassword(),
      icon: null,
    },
    {
      title: 'Two-Factor Auth',
      href: show(),
      icon: null,
    },
    ...(showStoreSettings
      ? [
          {
            title: 'Store Settings',
            href: '/settings/team',
            icon: null,
          },
        ]
      : []),
    {
      title: 'Appearance',
      href: editAppearance(),
      icon: null,
    },
  ];

  return (
    <div className='px-4 py-6'>
      <Heading title='Settings' description='Manage your profile and account settings' />

      <div className='flex flex-col lg:flex-row lg:space-x-12'>
        <aside className='w-full max-w-xl lg:w-48'>
          <nav className='flex flex-col space-y-1 space-x-0'>
            {sidebarNavItems.map((item, index) => {
              const isActive = typeof item.href === 'string' && url.startsWith(item.href);
              return (
                <Button
                  key={`${resolveUrl(item.href)}-${index}`}
                  size='sm'
                  variant='ghost'
                  asChild
                  className={cn('w-full justify-start', {
                    'bg-muted font-medium text-primary': isActive,
                    'text-muted-foreground': !isActive,
                  })}
                >
                  <Link href={item.href}>
                    {item.icon && <item.icon className='h-4 w-4' />}
                    {item.title}
                  </Link>
                </Button>
              );
            })}
          </nav>
        </aside>

        <Separator className='my-6 lg:hidden' />

        <div className='flex-1 md:max-w-2xl'>
          <section className='max-w-xl space-y-12'>{children}</section>
        </div>
      </div>
    </div>
  );
}
