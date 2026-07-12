export interface HeroBlockData {
    eyebrow?: string | null;
    title: string;
    body?: string | null;
    image?: string | null;
    primary_label?: string | null;
    primary_url?: string | null;
}

export interface RichTextBlockData {
    content: string;
}

export interface ImageTextBlockData {
    title: string;
    content: string;
    image?: string | null;
    image_position?: 'left' | 'right';
    image_width?: 'one-third' | 'one-half' | 'two-thirds';
}

export interface SectionLeaderCard {
    name: string;
    scout_name?: string | null;
    bio: string;
    fun_fact?: string | null;
    photo?: string | null;
}

export interface SectionLeadersBlockData {
    eyebrow: string;
    title: string;
    intro: string;
    section: string;
    leaders: SectionLeaderCard[];
}

export interface CtaBlockData {
    title: string;
    body: string;
    button_label: string;
    button_url: string;
}

export interface ContactDetailsBlockCard {
    title: string;
    body: string;
}

export interface ContactDetailsBlockData {
    eyebrow: string;
    title: string;
    intro: string;
    cards: ContactDetailsBlockCard[];
    primary_label?: string | null;
    primary_url?: string | null;
    secondary_label?: string | null;
    secondary_url?: string | null;
}

export interface ContactFormBlockData {
    eyebrow: string;
    title: string;
    intro: string;
    submit_label: string;
    turnstile_site_key?: string | null;
    is_configured: boolean;
}

export interface GoogleMapBlockData {
    eyebrow: string;
    title: string;
    intro: string;
    map_embed_url?: string | null;
    map_label?: string | null;
    map_address?: string | null;
    has_map: boolean;
}

export interface PageBlock<T = Record<string, unknown>> {
    id: string;
    type: string;
    data: T;
}

export interface CmsPage {
    title: string;
    slug: string;
    blocks: PageBlock[];
}
