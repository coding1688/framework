import { request } from '@/utils/request';

export const frontendUserApi = {
    list: options => request('frontend.user.list', options),
    detail: options => request('frontend.user.detail', options),
    update: options => request('frontend.user.update', options),
    status: options => request('frontend.user.status', options),
    password: options => request('frontend.user.password', options),
    verification: options => request('frontend.user.verification', options),
    vip: options => request('frontend.user.vip', options),
    vipOptions: options => request('frontend.user.vipOptions', options),
    vipHistory: options => request('frontend.user.vipHistory', options),
};
