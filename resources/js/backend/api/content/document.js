import { request } from '@/utils/request';

export const documentApi = {
    list: options => request('documentation.document.list', options),
    detail: options => request('documentation.document.detail', options),
    create: options => request('documentation.document.create', options),
    update: options => request('documentation.document.update', options),
    status: options => request('documentation.document.status', options),
    delete: options => request('documentation.document.delete', options),
};
