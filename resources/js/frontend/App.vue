<script setup>
import { onMounted, ref } from 'vue';
import { authApi } from './api';
import AuthPanel from './components/AuthPanel.vue';
import AccountPanel from './components/AccountPanel.vue';

const query = new URLSearchParams(location.search);
const initialMode = query.has('token') ? 'reset' : 'login';
const user = ref(null), loading = ref(true), error = ref('');

function authenticated(account) {
    user.value = account;
    history.replaceState({}, '', location.pathname);
}
async function loadUser() {
    try { user.value = (await authApi.user()).data; }
    catch (exception) { if (exception.status !== 401) error.value = exception.message; }
    finally { loading.value = false; }
}
onMounted(loadUser);
</script>

<template>
    <main class="site-shell">
        <header class="site-header"><a class="brand" href="/"><span class="brand-mark">F</span><span>Framework</span></a><span class="header-caption">用户中心</span></header>
        <div v-if="loading" class="loading-state"><span class="loading-spinner" />正在加载账户信息…</div>
        <template v-else><div v-if="error" class="feedback feedback--error">{{ error }}</div><AccountPanel v-if="user" :user="user" @updated="user = $event" @logout="user = null" /><AuthPanel v-else :initial-mode="initialMode" @authenticated="authenticated" /></template>
        <footer class="site-footer">© 2026 Framework · 安全、简单地管理你的账户</footer>
    </main>
</template>
