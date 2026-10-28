export async function readApiPayload(response) {
    const contentType = response.headers.get('Content-Type') || '';
    if (!contentType.includes('application/json')) return {};
    return response.json().catch(() => ({}));
}

export function createApiError(response, payload = {}, messages = {}) {
    const validation = payload.errors ? Object.values(payload.errors).flat().filter(value => typeof value === 'string') : [];
    const forced = response.status === 413
        ? messages.payloadTooLarge
        : response.status === 419 ? messages.sessionExpired : null;
    const fallback = response.status >= 500 ? messages.serverError : messages.generic;
    const error = new Error(forced || validation.join(' / ') || payload.message || fallback || 'Request failed.');
    error.fields = payload.errors || {};
    error.status = response.status;
    error.code = payload.error_code || null;

    return error;
}
