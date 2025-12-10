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
  href: string;
  icon?: LucideIcon | null;
  roles?: string[];
  isActive?: boolean;
  items?: NavItem[];
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

// User Role Type
export interface Role {
  id: string;
  name: string;
  guard_name: string;
  created_at: string;
  updated_at: string;
}

// User Permission Type
export interface Permission {
  id: string;
  name: string;
  guard_name: string;
  created_at: string;
  updated_at: string;
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
interface Customer {
  id: string;
  name: string;
  email: string;
  phone?: string;
  avatar?: string;
  teams?: string;
  total_orders: number;
  total_spent: number;
  registered_at: string;
  joined_team_at?: string;
}

export interface FlashMessages {
  success?: string;
  error?: string;
  warning?: string;
  info?: string;
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
// Common Types
export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface PaginatedData<T> {
  data: T[];
  links: PaginationLink[];
  current_page: number;
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}
export interface Product {
  id: string;
  name: string;
  slug: string;
  sku?: string;
  description?: string;
  price: number;
  stock?: number;
  image?: string;
  category?: {
    id: string;
    name: string;
  };
  created_at: string;
  updated_at: string;
}

// Category Type 'slug', 'name', 'thumbnail', 'tagline'

export interface Category {
  id: string;
  slug: string;
  name: string;
  tagline?: string;
  thumbnail?: string;
  products_count?: number;
  created_at: string;
  updated_at: string;
}

// Transaction Type
export interface Transaction {
  id: string;
  order_number: string;
  customer_name?: string;
  customer_id?: string;
  total: number;
  status: string;
  payment_method?: string;
  created_at: string;
  updated_at: string;
  items?: TransactionItem[];
}

// Transaction Item Type
export interface TransactionItem {
  id: string;
  product_id: string;
  product_name: string;
  quantity: number;
  price: number;
  subtotal: number;
}

// Warehouse Type
export interface Warehouse {
  id: string;
  name: string;
  slug: string;
  address?: string;
  phone?: string;
  products_count?: number;
  created_at: string;
  updated_at: string;
}

// Stock Mutation Type
export interface StockMutation {
  id: string;
  product_id: string;
  product_name: string;
  warehouse_id?: string;
  warehouse_name?: string;
  type: 'in' | 'out' | 'adjustment';
  quantity: number;
  note?: string;
  created_by?: string;
  created_at: string;
}

// Merchant Type
export interface Merchant {
  id: string;
  name: string;
  slug: string;
  owner_id: string;
  thumbnail?: string;
  keeper?: User;
  phone?: string;
  alamat?: string;
  total_products?: number;
  total_transactions?: number;
  total_customers?: number;
  created_at: string;
  updated_at: string;
}

// Dashboard Stats Type
export interface DashboardStats {
  total_revenue: number;
  total_orders: number;
  total_customers: number;
  total_products: number;
  revenue_growth?: number;
  orders_growth?: number;
}

// Filter Type
export interface Filters {
  search?: string;
  role?: string;
  team_id?: string;
  status?: string;
  sort_by?: string;
  sort_direction?: 'asc' | 'desc';
  per_page?: number;
}
