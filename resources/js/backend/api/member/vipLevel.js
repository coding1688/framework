import { request } from '@/utils/request';

export const vipLevelApi = {
    list: options => request('member.vipLevel.list', options),
    options: options => request('member.vipLevel.options', options),
    detail: options => request('member.vipLevel.detail', options),
    create: options => request('member.vipLevel.create', options),
    update: options => request('member.vipLevel.update', options),
    status: options => request('member.vipLevel.status', options),
    delete: options => request('member.vipLevel.delete', options),
};
