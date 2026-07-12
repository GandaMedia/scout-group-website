import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(
    urlToCheck: NonNullable<InertiaLinkProps['href']>,
    currentUrl: string,
) {
    return toUrl(urlToCheck) === currentUrl;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function minorUnitsToPoundsInput(minorUnits: number | null | undefined) {
    return ((minorUnits ?? 0) / 100).toFixed(2);
}

export function poundsInputToMinorUnits(value: string) {
    const normalized = value.replace(/[£,\s]/g, '');

    if (!/^\d+(?:\.\d{0,2})?$/.test(normalized)) {
        return null;
    }

    const [pounds, pence = ''] = normalized.split('.');

    return Number(pounds) * 100 + Number(pence.padEnd(2, '0'));
}
