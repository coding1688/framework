import { request } from '@/utils/request';

export const documentCategoryApi = {
    list: options => request('documentation.category.list', options),
    options: options => request('documentation.category.options', options),
    detail: options => request('documentation.category.detail', options),
    create: options => request('documentation.category.create', options),
    update: options => request('documentation.category.update', options),
    status: options => request('documentation.category.status', options),
    delete: options => request('documentation.category.delete', options),
};
