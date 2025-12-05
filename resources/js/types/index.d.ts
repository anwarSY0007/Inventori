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
  isActive?: boolean;
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
