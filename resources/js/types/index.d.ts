import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User | null;
    approvalStatus: 'pending' | 'approved' | 'rejected' | null;
    canAccessLeaderTools: boolean;
    canAccessAdmin: boolean;
    adminUrl: string | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    groupProfile: GroupProfile;
    quote: { message: string; author: string };
    flash: {
        contactEnquirySubmitted?: string | null;
        waitingListSubmitted?: string | null;
    };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface GroupProfile {
    name: string;
    shortName: string;
    logo: {
        shortLabel: string;
        stackedLine1: string;
        stackedLine2: string;
    };
    websiteUrl: string;
    headquartersLabel: string;
    headquartersAddress: string;
    charityNumber: string;
    charityRegisterUrl: string;
    districtName: string;
    districtUrl: string;
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

export type BreadcrumbItemType = BreadcrumbItem;
