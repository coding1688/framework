import { request } from '@/utils/request';

export const userApi = {
    list: options => request('admin.user.list', options),
    detail: options => request('admin.user.detail', options),
    options: options => request('admin.user.options', options),
    create: options => request('admin.user.create', options),
    update: options => request('admin.user.update', options),
    delete: options => request('admin.user.delete', options),
    status: options => request('admin.user.status', options),
    password: options => request('admin.user.password', options),
};
