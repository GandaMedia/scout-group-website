import type { PageBlock } from '@/components/pageBlocks/types';

export interface NewsTag {
    name: string;
    slug: string;
}

export interface NewsPostSummary {
    title: string;
    slug: string;
    author_name: string | null;
    published_at: string | null;
    excerpt: string | null;
    image: string | null;
    tags: NewsTag[];
    is_password_protected: boolean;
}

export interface NewsArchiveTag {
    name: string;
    slug: string;
}

export interface NewsPost {
    title: string;
    slug: string;
    author_name: string | null;
    published_at: string | null;
    excerpt: string | null;
    image: string | null;
    tags: NewsTag[];
    blocks: PageBlock[];
    is_password_protected: boolean;
    is_authorized: boolean;
}
