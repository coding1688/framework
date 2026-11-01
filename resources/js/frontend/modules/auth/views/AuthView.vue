<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { authApi } from '../api';
import { useSession } from '@/shared/composables/useSession';
import FeedbackMessage from '@/shared/components/FeedbackMessage.vue';

const route = useRoute(), router = useRouter();
const { setUser } = useSession();
const mode = computed(() => route.meta.mode || 'login');
const busy = ref(false), error = ref(''), notice = ref(''), fields = ref({});
const loginForm = reactive({ login: '', password: '' });
const registerForm = reactive({ username: '', name: '', email: '', password: '', password_confirmation: '' });
const recoveryForm = reactive({ email: route.query.email || '', token: route.query.token || '', password: '', password_confirmation: '' });
const oauth = window.frontendConfig?.oauth || {};
const headings = {
    login: ['欢迎回来', '使用你的账户继续。'],
    register: ['创建你的账号', '注册后即可进入个人工作台。'],
    forgot: ['找回账户密码', '输入邮箱，我们将发送重置链接。'],
    reset: ['设置新密码', '请设置一个新的账户密码。'],
};
const heading = computed(() => headings[mode.value]);

function fieldError(name) { return fields.value[name]?.[0] || ''; }
function clearFeedback() { error.value = ''; notice.value = ''; fields.value = {}; }
function switchMode(name) { clearFeedback(); router.push({ name: `auth.${name}` }); }
async function run(callback) {
    if (busy.value) return;
    clearFeedback();
    busy.value = true;
    try { await callback(); }
    catch (exception) { fields.value = exception.fields || {}; error.value = exception.message; }
    finally { busy.value = false; }
}
async function authenticated(apiCall) {
    const result = await apiCall();
    setUser(result.data);
    const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/manage') ? route.query.redirect : null;
    await router.push(redirect || { name: 'account' });
}
async function submitLogin() { await run(() => authenticated(() => authApi.login({ ...loginForm }))); }
async function submitRegister() { await run(() => authenticated(() => authApi.register({ ...registerForm }))); }
async function submitRecovery() {
    await run(async () => {
        const result = mode.value === 'forgot' ? await authApi.forgotPassword({ email: recoveryForm.email }) : await authApi.resetPassword({ ...recoveryForm });
        notice.value = result.message;
        if (mode.value === 'reset') await router.replace({ name: 'auth.login' });
    });
}
</script>

<template>
    <section class="auth-layout">
        <div class="auth-story"><div><span class="eyebrow">WELCOME TO FRAMEWORK</span><h1>一个账号，<br>开启你的专属空间。</h1><p>安全登录，管理个人资料、资产和消息，在一个工作台完成日常事务。</p></div><ul class="feature-list"><li><span>01</span><div><strong>简单安全的账户</strong><p>支持用户名、邮箱和第三方登录。</p></div></li><li><span>02</span><div><strong>清晰的业务工作区</strong><p>用户中心、钱包和通知分类管理。</p></div></li><li><span>03</span><div><strong>可信的邮箱验证</strong><p>清楚掌握账户验证状态。</p></div></li></ul></div>
        <div class="auth-panel"><div class="auth-card">
            <div v-if="['login', 'register'].includes(mode)" class="auth-tabs" role="tablist"><button type="button" :class="{ 'is-active': mode === 'login' }" @click="switchMode('login')">登录</button><button type="button" :class="{ 'is-active': mode === 'register' }" @click="switchMode('register')">注册</button></div>
            <div class="auth-heading"><h2>{{ heading[0] }}</h2><p>{{ heading[1] }}</p></div>
            <FeedbackMessage :message="error" /><FeedbackMessage :message="notice" type="success" />
            <form v-if="mode === 'login'" class="auth-form" @submit.prevent="submitLogin">
                <label class="form-field"><span>用户名或邮箱</span><input v-model.trim="loginForm.login" autocomplete="username" maxlength="256" required><small class="field-error">{{ fieldError('login') }}</small></label>
                <label class="form-field"><span>密码</span><input v-model="loginForm.password" type="password" autocomplete="current-password" maxlength="128" required><small class="field-error">{{ fieldError('password') }}</small></label>
                <button class="button button--primary button--full" :disabled="busy">登录</button><button class="link-button" type="button" @click="switchMode('forgot')">忘记密码？</button>
            </form>
            <form v-else-if="mode === 'register'" class="auth-form" @submit.prevent="submitRegister">
                <div class="form-grid"><label class="form-field"><span>用户名</span><input v-model.trim="registerForm.username" minlength="3" maxlength="32" required><small class="field-error">{{ fieldError('username') }}</small></label><label class="form-field"><span>昵称</span><input v-model.trim="registerForm.name" maxlength="128"><small class="field-error">{{ fieldError('name') }}</small></label></div>
                <label class="form-field"><span>邮箱地址</span><input v-model.trim="registerForm.email" type="email" maxlength="256" required><small class="field-error">{{ fieldError('email') }}</small></label>
                <label class="form-field"><span>密码</span><input v-model="registerForm.password" type="password" minlength="12" maxlength="128" required><small class="field-error">{{ fieldError('password') }}</small></label>
                <label class="form-field"><span>确认密码</span><input v-model="registerForm.password_confirmation" type="password" minlength="12" maxlength="128" required></label>
                <button class="button button--primary button--full" :disabled="busy">注册并登录</button>
            </form>
            <form v-else class="auth-form" @submit.prevent="submitRecovery">
                <label class="form-field"><span>邮箱地址</span><input v-model.trim="recoveryForm.email" type="email" maxlength="256" required><small class="field-error">{{ fieldError('email') }}</small></label>
                <template v-if="mode === 'reset'"><label class="form-field"><span>新密码</span><input v-model="recoveryForm.password" type="password" minlength="12" maxlength="128" required></label><label class="form-field"><span>确认新密码</span><input v-model="recoveryForm.password_confirmation" type="password" minlength="12" maxlength="128" required></label></template>
                <button class="button button--primary button--full" :disabled="busy">{{ mode === 'forgot' ? '发送重置链接' : '重置密码' }}</button><button class="link-button" type="button" @click="switchMode('login')">返回登录</button>
            </form>
            <template v-if="['login', 'register'].includes(mode)"><div class="social-divider"><span>或使用第三方账号</span></div><div class="social-actions"><a v-if="oauth.google" class="social-button" :href="oauth.google">Google</a><a v-if="oauth.facebook" class="social-button" :href="oauth.facebook">Facebook</a></div></template>
        </div></div>
    </section>
</template>
