import { request } from '@/utils/request';

export const cacheApi = {
    list: options => request('system.cache.list', options),
    keys: options => request('system.cache.keys', options),
    clear: options => request('system.cache.clear', options),
    refresh: options => request('system.cache.refresh', options),
    rebuild: options => request('system.cache.rebuild', options),
};
