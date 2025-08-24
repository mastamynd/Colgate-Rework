import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Permission {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions: Permission[];
    created_at: string;
    updated_at: string;
}

export interface SalesPerson {
    id: string;
    name: string;
    code: string;
    email?: string;
    phone?: string;
    type?: 'Sales Representative' | 'Distributor';
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Customer {
    id: string;
    name: string;
    phone?: string;
    email?: string;
    address?: string;
    county_id?: number;
    constituency_id?: number;
    ward_id?: number;
    sales_person_id?: string;
    route_id?: string;
    is_active: boolean;
    sales_person?: SalesPerson;
    route?: Route;
    county?: Boundary;
    constituency?: Boundary;
    ward?: Boundary;
    created_at: string;
    updated_at: string;
}

export interface Route {
    id: string;
    name: string;
    line: string;
    description: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Partner {
    id: string;
    name: string;
    link?: string;
    photo?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Boundary {
    id: number;
    name: string;
    code: number;
    type: 'county' | 'constituency' | 'ward';
    parent_type: string;
    parent_code: string;
    geometry?: object;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
