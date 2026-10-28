import { request } from '@/utils/request';

export const settingGroupApi = {
    list: options => request('development.settingGroup.list', options),
    options: options => request('development.settingGroup.options', options),
    detail: options => request('development.settingGroup.detail', options),
    create: options => request('development.settingGroup.create', options),
    update: options => request('development.settingGroup.update', options),
    delete: options => request('development.settingGroup.delete', options),
};
