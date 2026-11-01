export async function request(path, { method = 'GET', data } = {}) {
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
