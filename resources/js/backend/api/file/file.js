import { request } from '@/utils/request';

export const fileApi = {
    list: options => request('file.item.list', options),
    options: options => request('file.item.options', options),
    detail: options => request('file.item.detail', options),
    upload: options => request('file.item.upload', options),
    delete: options => request('file.item.delete', options),
};
