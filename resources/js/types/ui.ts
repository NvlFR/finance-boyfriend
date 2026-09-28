export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type AppRelease = {
    version: string;
    title: string;
    released_at: string;
    highlights: string[];
    tour: Array<{
        path: string;
        target: string;
        title: string;
        description: string;
    }>;
};
