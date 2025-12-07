import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { PageProps, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
  ArrowLeftRight,
  BoxIcon,
  LayoutGrid,
  Monitor,
  ReceiptText,
  StoreIcon,
  Tags,
  UserCog,
  Users,
  UsersRound,
  Warehouse,
} from 'lucide-react';
import AppLogoIcon from './app-logo-icon';

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/',
    icon: LayoutGrid,
    roles: ['super_admin', 'merchant_owner', 'admin_merchant', 'warehouse_staff', 'cashier'],
  },

  // ==========================================
  // SUPER ADMIN ONLY
  // ==========================================
  {
    title: 'All Users',
    href: '/admin/users',
    icon: Users,
    roles: ['super_admin'],
  },
  {
    title: 'All Customers',
    href: '/admin/customers/all',
    icon: UsersRound,
    roles: ['super_admin'],
  },
  {
    title: 'Merchants',
    href: '/admin/merchants',
    icon: StoreIcon,
    roles: ['super_admin'],
  },

  // ==========================================
  // MERCHANT OWNER & ADMIN
  // ==========================================
  {
    title: 'Team Members',
    href: '/team/members',
    icon: UserCog,
    roles: ['merchant_owner', 'admin_merchant'],
  },
  {
    title: 'Customers',
    href: '/team/customers',
    icon: UsersRound,
    roles: ['merchant_owner', 'admin_merchant', 'cashier', 'warehouse_staff'],
  },

  // ==========================================
  // PRODUCT & INVENTORY MANAGEMENT
  // ==========================================
  {
    title: 'Categories',
    href: '/admin/categories',
    icon: Tags,
    roles: ['super_admin', 'merchant_owner', 'admin_merchant'],
  },
  {
    title: 'Products',
    href: '/admin/products',
    icon: BoxIcon,
    roles: ['super_admin', 'merchant_owner', 'admin_merchant', 'warehouse_staff'],
  },
  {
    title: 'Warehouses',
    href: '/admin/warehouses',
    icon: Warehouse,
    roles: ['super_admin', 'merchant_owner', 'admin_merchant', 'warehouse_staff'],
  },
  {
    title: 'Stock Mutations',
    href: '/admin/stock',
    icon: ArrowLeftRight,
    roles: ['super_admin', 'merchant_owner', 'admin_merchant', 'warehouse_staff'],
  },

  // ==========================================
  // TRANSACTIONS & REPORTS
  // ==========================================
  {
    title: 'Transactions',
    href: '/admin/transactions',
    icon: ReceiptText,
    roles: ['super_admin', 'merchant_owner', 'admin_merchant'],
  },
  {
    title: 'POS (Cashier)',
    href: '/cashier/transactions/create',
    icon: Monitor,
    roles: ['super_admin', 'merchant_owner', 'cashier'],
  },
];

export function AppSidebar() {
  const { auth } = usePage<PageProps>().props;
  const user = auth.user;
  const userRoles = user.roles?.map((r) => r.name) ?? [];

  const filteredNavItems = mainNavItems.filter((item) => {
    // Jika item tidak punya properti roles, berarti PUBLIC (tampilkan ke semua)
    if (!item.roles || item.roles.length === 0) {
      return true;
    }

    // Cek apakah user memiliki SALAH SATU role yang diizinkan
    return item.roles.some((role) => userRoles.includes(role));
  });
  return (
    <Sidebar collapsible='icon' variant='inset'>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size='lg' asChild>
              <Link href={dashboard()} prefetch>
                <AppLogoIcon text='POS' appName='My POS' />
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent>
        <NavMain items={filteredNavItems} />
      </SidebarContent>

      <SidebarFooter>
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  );
}
