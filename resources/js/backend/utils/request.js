import { config } from '@/config';
import { t } from '@/locales';
import { createApiError, readApiPayload } from '../../shared/http/response.js';

export async function request(name, {id, data, query, signal} = {}) {
    const endpoint = config.routes[name];
    if (!endpoint) { throw new Error(t('common.error')); }
    let url = endpoint.url;
    if (url.includes('__id__')) {
        if (id == null) { throw new Error(t('common.error')); }
        url = url.replaceAll('__id__',encodeURIComponent(id));
    }
    const search = new URLSearchParams(Object.entries(query || {}).filter(([,value]) => value !== '' && value != null)).toString();
    if (search) { url += '?' + search; }
    const formData = data instanceof FormData;
    let response;
    try { response = await fetch(url, {
        method:endpoint.method, credentials:'same-origin',
        headers:{Accept:'application/json',...(formData ? {} : {'Content-Type':'application/json'}),'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'X-Lang':config.locale},
        body:data === undefined ? undefined : (formData ? data : JSON.stringify(data)),
        signal:signal ? AbortSignal.any([signal,AbortSignal.timeout(config.ui.request_timeout)]) : AbortSignal.timeout(config.ui.request_timeout),
    }); } catch(error) { if (error.name === 'TimeoutError') { throw new Error(t('common.request_timeout')); } throw error; }
    const result = await readApiPayload(response);
    if (result.csrf_token) { document.querySelector('meta[name="csrf-token"]').content = result.csrf_token; }
    if (!response.ok) {
        const error = createApiError(response, result, {
            generic: t('common.error'), payloadTooLarge: t('common.payload_too_large'),
            sessionExpired: t('common.session_expired'), serverError: t('common.server_error'),
        });
        if (response.status === 401) { error.redirected = true; window.dispatchEvent(new Event('backend:unauthorized')); }
        if (response.status === 403 && response.headers.get('X-Backend-Redirect') === 'home') { error.redirected = true; window.dispatchEvent(new Event('backend:forbidden')); }
        throw error;
    }
    return result;
}
