import { request } from '@/shared/api/client';

export const documentApi = {
    navigation: () => request('/api/v1/documents/navigation'),
    show: (type, slug) => request(`/api/v1/documents/${type}/${slug}`),
};
