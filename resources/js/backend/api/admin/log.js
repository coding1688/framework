import { request } from '@/utils/request';

export const logApi = {
    list: options => request('admin.log.list', options),
};
