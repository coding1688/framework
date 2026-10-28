import { request } from '@/utils/request';

export const settingDefinitionApi = {
    list: options => request('development.setting.list', options),
    optionValues: options => request('development.setting.optionValues', options),
    options: options => request('development.setting.options', options),
    detail: options => request('development.setting.detail', options),
    create: options => request('development.setting.create', options),
    update: options => request('development.setting.update', options),
    delete: options => request('development.setting.delete', options),
};
