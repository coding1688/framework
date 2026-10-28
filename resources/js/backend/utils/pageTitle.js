import { config } from '@/config';
export const pageTitle = title => [title, config.ui.brand].filter(Boolean).join(' · ');
