import { request } from '@/utils/request';

export const frontendUserApi = {
    list: options => request('frontend.user.list', options),
    detail: options => request('frontend.user.detail', options),
    update: options => request('frontend.user.update', options),
    status: options => request('frontend.user.status', options),
    restrictions: options => request('frontend.user.restrictions', options),
    restrictionTypes: options => request('frontend.user.restrictionTypes', options),
    restriction: options => request('frontend.user.restriction', options),
    password: options => request('frontend.user.password', options),
    verification: options => request('frontend.user.verification', options),
    vip: options => request('frontend.user.vip', options),
    vipOptions: options => request('frontend.user.vipOptions', options),
    vipHistory: options => request('frontend.user.vipHistory', options),
    logs: options => request('frontend.user.logs', options),
};
