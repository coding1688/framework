<script setup>
import { reactive, ref } from 'vue';
import { accountApi } from '../api';
import { useSession } from '@/shared/composables/useSession';
import { formatDate } from '@/shared/utils/date';
import AppIcon from '@/shared/components/AppIcon.vue';
import FeedbackMessage from '@/shared/components/FeedbackMessage.vue';

const { user, setUser } = useSession();
const busy = ref(false), error = ref(''), notice = ref('');
const profile = reactive({ username: user.value.username, name: user.value.name });
const emailForm = reactive({ email: user.value.email, current_password: '' });
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });
const avatar = ref(null);
const oauthBind = window.frontendConfig?.oauthBind || {};

async function run(callback) {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    notice.value = '';
    try { await callback(); }
    catch (exception) { error.value = exception.message; }
    finally { busy.value = false; }
}
function updated(result) {
    notice.value = result.message;
    setUser(result.data);
}
async function saveProfile() { await run(async () => updated(await accountApi.profile({ ...profile }))); }
async function changeEmail() { await run(async () => { updated(await accountApi.email({ ...emailForm })); emailForm.current_password = ''; }); }
async function changePassword() { await run(async () => { const result = await accountApi.password({ ...passwordForm }); notice.value = result.message; Object.assign(passwordForm, { current_password: '', password: '', password_confirmation: '' }); }); }
async function uploadAvatar() {
    if (!avatar.value?.files[0]) { error.value = '请选择头像图片。'; return; }
    const data = new FormData();
    data.append('avatar', avatar.value.files[0]);
    await run(async () => updated(await accountApi.avatar(data)));
}
async function unlink(provider) { await run(async () => updated(await accountApi.unlink(provider))); }
async function resendVerification() { await run(async () => { notice.value = (await accountApi.resend()).message; }); }
</script>

<template>
    <div v-if="user" class="account-view">
        <section class="account-overview">
            <div class="account-overview-main"><img class="account-avatar" :src="user.avatar" :alt="`${user.name}的头像`"><div><div class="account-name-row"><h2>{{ user.name }}</h2><span class="vip-badge">VIP Lv.{{ user.vip_level }}</span></div><p>@{{ user.username }} · {{ user.email }}</p><div class="account-badges"><span class="status-badge" :class="user.email_verified ? 'is-verified' : 'is-pending'">{{ user.email_verified ? '邮箱已验证' : '邮箱待验证' }}</span><span class="status-badge is-verified">账户正常</span></div></div></div>
            <dl class="account-facts"><div><dt>会员等级</dt><dd>{{ user.vip_name || '普通用户' }}</dd></div><div><dt>注册时间</dt><dd>{{ formatDate(user.created_at) }}</dd></div></dl>
        </section>
        <div v-if="!user.email_verified" class="verification-panel"><div><strong>完成邮箱验证</strong><p>验证后可使用完整的账户服务，并及时接收安全通知。</p></div><button class="button button--secondary" :disabled="busy" @click="resendVerification">重新发送验证邮件</button></div>
        <FeedbackMessage :message="error" /><FeedbackMessage :message="notice" type="success" />
        <div class="account-grid">
            <section class="content-card">
                <header class="card-heading"><span class="card-icon"><AppIcon name="user" /></span><div><h3>个人资料</h3><p>更新你的公开信息和账户头像。</p></div></header>
                <form class="auth-form" @submit.prevent="saveProfile"><div class="form-grid"><label class="form-field"><span>用户名</span><input v-model.trim="profile.username" minlength="3" maxlength="32" required></label><label class="form-field"><span>昵称</span><input v-model.trim="profile.name" maxlength="128" required></label></div><div class="card-actions"><button class="button button--primary" :disabled="busy">保存资料</button></div></form>
                <div class="card-divider" />
                <form class="auth-form" @submit.prevent="uploadAvatar"><label class="form-field"><span>更换头像</span><input type="file" accept="image/jpeg,image/png,image/webp" @change="avatar = $event.target"></label><div class="card-actions"><button class="button button--secondary" :disabled="busy">上传头像</button></div></form>
            </section>
            <section class="content-card">
                <header class="card-heading"><span class="card-icon"><AppIcon name="notification" /></span><div><h3>邮箱地址</h3><p>用于登录、找回密码和接收通知。</p></div></header>
                <form class="auth-form" @submit.prevent="changeEmail"><label class="form-field"><span>邮箱</span><input v-model.trim="emailForm.email" type="email" maxlength="256" required></label><label v-if="user.password_set" class="form-field"><span>当前密码</span><input v-model="emailForm.current_password" type="password" autocomplete="current-password" maxlength="128"></label><p class="form-hint">修改邮箱后需要重新完成验证。</p><div class="card-actions"><button class="button button--primary" :disabled="busy">更新邮箱</button></div></form>
            </section>
            <section class="content-card">
                <header class="card-heading"><span class="card-icon"><AppIcon name="shield" /></span><div><h3>登录方式</h3><p>绑定第三方账号，丰富登录选择。</p></div></header>
                <div class="provider-list"><div v-for="provider in ['google', 'facebook']" :key="provider" class="provider-row"><span class="provider-name">{{ provider === 'google' ? 'Google' : 'Facebook' }}</span><span class="provider-status" :class="{ connected: user.providers.includes(provider) }">{{ user.providers.includes(provider) ? '已绑定' : '未绑定' }}</span><a v-if="!user.providers.includes(provider) && oauthBind[provider]" class="button button--secondary button--small" :href="oauthBind[provider]">绑定</a><button v-else-if="user.providers.includes(provider)" class="button button--secondary button--small" :disabled="busy" @click="unlink(provider)">解绑</button></div></div>
            </section>
            <section class="content-card">
                <header class="card-heading"><span class="card-icon"><AppIcon name="shield" /></span><div><h3>账户安全</h3><p>定期更新密码，保护账户安全。</p></div></header>
                <form class="auth-form" @submit.prevent="changePassword"><label class="form-field"><span>当前密码（第三方注册可留空）</span><input v-model="passwordForm.current_password" type="password" autocomplete="current-password" maxlength="128"></label><div class="form-grid"><label class="form-field"><span>新密码</span><input v-model="passwordForm.password" type="password" autocomplete="new-password" minlength="12" maxlength="128" required></label><label class="form-field"><span>确认新密码</span><input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="128" required></label></div><div class="card-actions"><button class="button button--primary" :disabled="busy">更新密码</button></div></form>
            </section>
        </div>
    </div>
</template>
