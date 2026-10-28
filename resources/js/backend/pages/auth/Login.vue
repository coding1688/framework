<script setup>
import { authApi } from '@/api/auth';
import BaseAlert from '@/components/ui/BaseAlert.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { config } from '@/config';
import { t } from '@/locales';
import { userinfo } from '@/store/modules/user';
import { language } from '@/utils/language';
import { fail } from '@/utils/feedback';

const router = useRouter();
const query = new URLSearchParams(location.search);
const mode = ref(query.get('mode') === 'reset' ? 'reset' : 'login');
const form = reactive({ email: '', password: '' });
const recovery = reactive({ email: query.get('email') || '', token: query.get('token') || '', password: '', password_confirmation: '' });
const busy = ref(false), error = ref(''), notice = ref('');

function switchMode(value) {
    mode.value = value;
    error.value = '';
    notice.value = '';
}
async function submit() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    notice.value = '';
    try {
        if (mode.value === 'login') {
            await authApi.login({ data: form });
            await userinfo();
            await router.replace(config.pages.home);
        } else if (mode.value === 'forgot') {
            const result = await authApi.forgotPassword({ data: { email: recovery.email } });
            notice.value = result.message;
        } else {
            const result = await authApi.resetPassword({ data: recovery });
            notice.value = result.message;
            mode.value = 'login';
        }
    } catch (e) { error.value = e.message; fail(e); }
    finally { busy.value = false; }
}
</script>
<template><div class="spa-login"><section class="login-story"><div class="spa-brand"><b>F</b><span>Framework</span></div><div><span class="eyebrow">FRAMEWORK / ADMIN</span><h1>{{ t('auth.signin_title') }}</h1><p>{{ t('layout.welcome') }}</p></div><div class="login-orbit" /></section><section class="login-panel"><BaseCard shadow="never"><h2>{{ mode === 'login' ? t('auth.login') : t('auth.password_recovery') }}</h2><p class="muted">{{ mode === 'login' ? t('auth.signin_intro') : t('auth.recovery_intro') }}</p><BaseAlert v-if="error" :title="error" type="error" :closable="false" /><BaseAlert v-if="notice" :title="notice" type="success" :closable="false" /><el-form label-position="right" label-width="144px" class="horizontal-editor-form" @submit.prevent="submit"><template v-if="mode === 'login'"><el-form-item :label="t('form.email')" required><el-input v-model="form.email" type="email" autocomplete="username" maxlength="256" required /></el-form-item><el-form-item :label="t('form.password')" required><el-input v-model="form.password" type="password" show-password autocomplete="current-password" maxlength="128" required /></el-form-item></template><template v-else><el-form-item :label="t('form.email')" required><el-input v-model="recovery.email" type="email" autocomplete="email" maxlength="256" required /></el-form-item><template v-if="mode === 'reset'"><el-form-item :label="t('form.password')" required><el-input v-model="recovery.password" type="password" show-password autocomplete="new-password" maxlength="128" required /></el-form-item><el-form-item :label="t('auth.confirm_password')" required><el-input v-model="recovery.password_confirmation" type="password" show-password autocomplete="new-password" maxlength="128" required /></el-form-item></template></template><el-button class="full-width" type="primary" native-type="submit" :loading="busy">{{ mode === 'login' ? t('auth.login') : t('auth.submit_recovery') }}</el-button><el-button text @click="switchMode(mode === 'login' ? 'forgot' : 'login')">{{ mode === 'login' ? t('auth.forgot_password') : t('auth.back_login') }}</el-button></el-form><el-select :model-value="config.locale" :aria-label="t('common.locale')" @change="language"><el-option v-for="(label,key) in (config.interfaceLocales || config.locales)" :key="key" :label="label" :value="key" /></el-select><p class="muted login-note">{{ t('auth.login_note') }}</p></BaseCard></section></div></template>
