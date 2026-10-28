import { request } from '@/utils/request';

export const authApi = {
    login: options => request('backend.auth.login', options),
    forgotPassword: options => request('backend.auth.forgotPassword', options),
    resetPassword: options => request('backend.auth.resetPassword', options),
    logout: options => request('backend.auth.logout', options),
    password: options => request('backend.auth.password', options),
    preferences: options => request('backend.auth.preferences', options),
    securityPassword: options => request('backend.auth.securityPassword', options),
    userinfo: options => request('backend.auth.userinfo', options),
};
