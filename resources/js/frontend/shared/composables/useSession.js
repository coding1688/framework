import { readonly, ref } from 'vue';
import { authApi } from '@/modules/auth/api';

const user = ref(null);
const initialized = ref(false);
const loading = ref(false);
const error = ref('');
let initialization = null;

async function initialize() {
    if (initialized.value) return;
    if (initialization) return initialization;

    loading.value = true;
    error.value = '';
    initialization = authApi.user()
        .then(result => { user.value = result.data; })
        .catch(exception => { if (exception.status !== 401) error.value = exception.message; })
        .finally(() => {
            initialized.value = true;
            loading.value = false;
            initialization = null;
        });

    return initialization;
}
function setUser(account) {
    user.value = account;
    initialized.value = true;
    error.value = '';
}
async function logout() {
    await authApi.logout();
    user.value = null;
}

export function useSession() {
    return {
        user: readonly(user),
        initialized: readonly(initialized),
        loading: readonly(loading),
        error: readonly(error),
        initialize,
        setUser,
        logout,
    };
}
