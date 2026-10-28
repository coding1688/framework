<script setup>
import { reactive, ref } from 'vue';
import { authApi } from '../api';
import NotificationPanel from './NotificationPanel.vue';

const props = defineProps({ user: { type: Object, required: true } });
const emit = defineEmits(['updated', 'logout']);
const busy = ref(false), error = ref(''), notice = ref('');
const profile = reactive({ username: props.user.username, name: props.user.name });
const emailForm = reactive({ email: props.user.email, current_password: '' });
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });
const avatar = ref(null);
const oauthBind = window.frontendConfig?.oauthBind || {};

function handleFailure(exception) { error.value = exception.message; }
async function run(callback) {
    if (busy.value) return;
    busy.value = true; error.value = ''; notice.value = '';
    try { await callback(); } catch (exception) { handleFailure(exception); }
    finally { busy.value = false; }
}
function updated(result) { notice.value = result.message; emit('updated', result.data); }
async function saveProfile() { await run(async () => updated(await authApi.profile({ ...profile }))); }
async function changeEmail() { await run(async () => { updated(await authApi.email({ ...emailForm })); emailForm.current_password = ''; }); }
async function changePassword() { await run(async () => { const result = await authApi.password({ ...passwordForm }); notice.value = result.message; Object.assign(passwordForm, { current_password: '', password: '', password_confirmation: '' }); }); }
async function uploadAvatar() {
    if (!avatar.value?.files[0]) { error.value = '请选择头像图片。'; return; }
    const data = new FormData(); data.append('avatar', avatar.value.files[0]);
    await run(async () => updated(await authApi.avatar(data)));
}
async function unlink(provider) { await run(async () => updated(await authApi.unlink(provider))); }
async function resendVerification() { await run(async () => { notice.value = (await authApi.resend()).message; }); }
async function logout() { await run(async () => { await authApi.logout(); emit('logout'); }); }
</script>

<template>
    <section class="account-layout">
        <div class="account-intro"><span class="eyebrow">YOUR ACCOUNT</span><h1>欢迎回来，{{ user.name }}</h1><p>管理个人资料、登录方式和账户安全。</p></div>
        <div class="account-card">
            <div v-if="notice" class="feedback feedback--success" role="status">{{ notice }}</div><div v-if="error" class="feedback feedback--error" role="alert">{{ error }}</div>
            <div class="profile-heading"><img class="profile-avatar" :src="user.avatar" :alt="`${user.name}的头像`"><div class="profile-identity"><div class="profile-name-row"><h2>{{ user.name }}</h2><span class="vip-badge">VIP Lv.{{ user.vip_level }}</span></div><p>@{{ user.username }}</p><p v-if="user.vip_status === 'permanent'">永久 VIP 会员</p><p v-else-if="user.vip_expires_at">VIP 有效期至 {{ user.vip_expires_at.slice(0, 10) }}</p></div></div>
            <div v-if="!user.email_verified" class="verification-panel"><div><strong>验证你的邮箱</strong><p>完成验证以使用完整的账户服务。</p></div><button class="button button--secondary" :disabled="busy" @click="resendVerification">重新发送</button></div>
            <div class="account-section"><h3>个人资料</h3><form class="auth-form" @submit.prevent="saveProfile"><div class="form-grid"><label class="form-field"><span>用户名</span><input v-model.trim="profile.username" minlength="3" maxlength="32" required></label><label class="form-field"><span>昵称</span><input v-model.trim="profile.name" maxlength="128" required></label></div><button class="button button--secondary" :disabled="busy">保存资料</button></form><form class="auth-form" @submit.prevent="uploadAvatar"><label class="form-field"><span>更换头像</span><input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/webp" @change="avatar = $event.target"></label><button class="button button--secondary" :disabled="busy">上传头像</button></form></div>
            <div class="account-section"><h3>邮箱地址 <span class="status-badge" :class="user.email_verified ? 'is-verified' : 'is-pending'">{{ user.email_verified ? '已验证' : '待验证' }}</span></h3><form class="auth-form" @submit.prevent="changeEmail"><label class="form-field"><span>邮箱</span><input v-model.trim="emailForm.email" type="email" maxlength="256" required></label><label v-if="user.password_set" class="form-field"><span>当前密码</span><input v-model="emailForm.current_password" type="password" maxlength="128"></label><button class="button button--secondary" :disabled="busy">修改邮箱并重新验证</button></form></div>
            <div class="account-section"><h3>登录方式</h3><div v-for="provider in ['google', 'facebook']" :key="provider" class="provider-row"><span>{{ provider === 'google' ? 'Google' : 'Facebook' }}</span><a v-if="!user.providers.includes(provider) && oauthBind[provider]" class="button button--secondary" :href="oauthBind[provider]">绑定</a><button v-else-if="user.providers.includes(provider)" class="button button--secondary" :disabled="busy" @click="unlink(provider)">解绑</button></div></div>
            <div class="account-section"><h3>修改密码</h3><form class="auth-form" @submit.prevent="changePassword"><label class="form-field"><span>当前密码（第三方注册可留空）</span><input v-model="passwordForm.current_password" type="password" maxlength="128"></label><label class="form-field"><span>新密码</span><input v-model="passwordForm.password" type="password" minlength="12" maxlength="128" required></label><label class="form-field"><span>确认新密码</span><input v-model="passwordForm.password_confirmation" type="password" minlength="12" maxlength="128" required></label><button class="button button--secondary" :disabled="busy">更新密码</button></form></div>
            <NotificationPanel />
            <button class="button button--logout" :disabled="busy" @click="logout">退出登录</button>
        </div>
    </section>
</template>
