export function createSecurityPasswordState() {
    return { visible: false, pending: false, password: '', error: '', fieldErrors: {}, title: '', description: '', confirmLabel: '' };
}

export function createSecurityPasswordPrompt({ state = createSecurityPasswordState(), isConfigured = () => false, translate = key => key } = {}) {
    let operation;
    let settle;
    let generation = 0;
    let disposed = false;
    let controller;

    function clear() {
        state.password = '';
        state.error = '';
        state.fieldErrors = {};
    }
    function finish(result) {
        generation++;
        state.visible = false;
        state.pending = false;
        clear();
        operation = undefined;
        const resolve = settle;
        settle = undefined;
        resolve?.(result);
    }
    function open(callback, options = {}) {
        if (typeof callback !== 'function') return Promise.reject(new TypeError('A protected operation callback is required.'));
        if (disposed || state.visible) return Promise.resolve({ confirmed: false, reason: disposed ? 'disposed' : 'busy' });
        clear();
        generation++;
        operation = callback;
        state.title = options.title || '';
        state.description = options.description || '';
        state.confirmLabel = options.confirmLabel || '';
        state.visible = true;
        return new Promise(resolve => { settle = resolve; });
    }
    function updatePassword(value) {
        if (state.pending || disposed) return;
        state.password = typeof value === 'string' ? value : '';
        state.error = '';
        state.fieldErrors = {};
    }
    async function confirm() {
        if (disposed || state.pending || !state.visible || !operation) return false;
        if (!isConfigured()) {
            state.error = translate('security.password_not_configured');
            state.password = '';
            return false;
        }
        if (state.password === '') {
            state.fieldErrors = { security_password: [translate('security.password_required')] };
            return false;
        }
        const current = generation;
        const callback = operation;
        const password = state.password;
        controller = new AbortController();
        state.pending = true;
        clear();
        try {
            const result = await callback(password, { signal: controller.signal });
            if (disposed || current !== generation) return false;
            finish({ confirmed: true, result });
            return true;
        } catch (error) {
            if (disposed || current !== generation) return false;
            const fields = error?.fields;
            state.fieldErrors = fields && typeof fields === 'object'
                ? Object.fromEntries(Object.entries(fields).map(([key, messages]) => [key, (Array.isArray(messages) ? messages : [messages]).filter(message => typeof message === 'string')]))
                : {};
            state.error = state.fieldErrors.security_password?.length ? '' : error?.message || translate('security.password_operation_failed');
            return false;
        } finally {
            if (!disposed && current === generation) {
                state.pending = false;
                state.password = '';
            }
            controller = undefined;
        }
    }
    function cancel(reason = 'cancelled') {
        if (state.pending || !state.visible) return false;
        finish({ confirmed: false, reason });
        return true;
    }
    function dispose() {
        if (disposed) return;
        disposed = true;
        controller?.abort();
        finish({ confirmed: false, reason: 'disposed' });
    }
    return { state, open, confirm, updatePassword, cancel, dispose };
}
