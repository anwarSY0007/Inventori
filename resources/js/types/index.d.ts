import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
  user: User;
}

export interface BreadcrumbItem {
  title: string;
  href: string;
}

export interface NavGroup {
  title: string;
  items: NavItem[];
}

export interface NavItem {
  title: string;
  href: NonNullable<InertiaLinkProps['href']>;
  icon?: LucideIcon | null;
  roles?: string[];
  isActive?: boolean;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & SharedData;

export interface AppLogoProps {
  mode?: 'text' | 'image' | 'svg';
  src?: string; // URL gambar (jika mode='image')
  text?: string; // Teks inisial (jika mode='text')
  content?: ReactNode; // Komponen SVG (jika mode='svg')
  appName?: string; // Nama Aplikasi
  className?: string;
  [key: string]: unknown;
}

export interface SharedData {
  auth: Auth;
  sidebarOpen: boolean;

  flash: {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
  };
  errors: Record<string, string>;
  [key: string]: unknown;
}

export interface Role {
  id: string;
  name: string;
  guard_name: string;
  created_at: string;
  updated_at: string;
}

export interface Team {
  id: string;
  name: string;
  keeper_id: string;
  slug?: string;
  created_at: string;
  updated_at: string;
}

// Customer Type
export interface Customer {
  id: string;
  name: string;
  email: string;
  phone?: string;
  avatar?: string;
  total_orders?: number;
  total_spent?: number;
  last_order_at?: string;
  registered_at: string;
  joined_team_at?: string;
}

export interface TeamMember {
  id: string;
  name: string;
  email: string;
  phone?: string;
  avatar?: string;
  role: string;
  role_label: string;
  current_teams?: {
    name: string;
  };
  created_at: string;
  joined_at: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  phone?: string;
  email_verified_at: string | null;
  two_factor_enabled?: boolean;
  roles?: Role[];
  created_at: string;
  updated_at: string;

  roles?: Role[];
  role_label?: string; // Optional: untuk backward compatibility
  teams?: Team[];
  current_teams?: Team | string | null;

  // Pivot data (jika ada)
  pivot?: {
    team_id: string;
    user_id: string;
    created_at: string;
    updated_at: string;
  };
  [key: string]: unknown; // This allows for additional properties...
}
