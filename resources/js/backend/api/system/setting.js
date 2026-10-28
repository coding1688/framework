import { request } from '@/utils/request';

export const settingApi = {
    list: options => request('system.setting.list', options),
    options: options => request('system.setting.options', options),
    update: options => request('system.setting.update', options),
    status: options => request('system.setting.status', options),
};
