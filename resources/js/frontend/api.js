async function request(path, { method = 'GET', data } = {}) {
    const formData = data instanceof FormData;
    const response = await fetch(path.startsWith('/api/') ? path : `/api/v1/auth${path}`, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(data === undefined || formData ? {} : { 'Content-Type': 'application/json' }),
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: data === undefined ? undefined : (formData ? data : JSON.stringify(data)),
    });
    const result = await response.json().catch(() => ({}));
    if (result.csrf_token) document.querySelector('meta[name="csrf-token"]').content = result.csrf_token;
    if (!response.ok) {
        const error = new Error(result.message || '请求失败，请稍后重试。');
        error.status = response.status;
        error.fields = result.errors || {};
        throw error;
    }
    return result;
}

export const authApi = {
    user: () => request('/user'),
    login: data => request('/login', { method: 'POST', data }),
    register: data => request('/register', { method: 'POST', data }),
    forgotPassword: data => request('/password/forgot', { method: 'POST', data }),
    resetPassword: data => request('/password/reset', { method: 'POST', data }),
    profile: data => request('/profile', { method: 'POST', data }),
    password: data => request('/password', { method: 'POST', data }),
    email: data => request('/email', { method: 'POST', data }),
    avatar: data => request('/avatar', { method: 'POST', data }),
    unlink: provider => request(`/oauth/${provider}/unlink`, { method: 'POST', data: {} }),
    logout: () => request('/logout', { method: 'POST' }),
    resend: () => request('/email/verification-notification', { method: 'POST' }),
};

export const notificationApi = {
    list: () => request('/api/v1/notifications/list'),
    read: id => request(`/api/v1/notifications/read/${id}`, { method: 'POST', data: {} }),
    readAll: () => request('/api/v1/notifications/read-all', { method: 'POST', data: {} }),
    preferences: preferences => request('/api/v1/notifications/preferences', { method: 'POST', data: { preferences } }),
};
