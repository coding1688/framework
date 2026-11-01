<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSession } from '@/shared/composables/useSession';
import AppIcon from '@/shared/components/AppIcon.vue';
import FeedbackMessage from '@/shared/components/FeedbackMessage.vue';

const menus = [
    { name: 'account', label: '用户中心', caption: '资料与安全', icon: 'user' },
    { name: 'wallet', label: '我的钱包', caption: '余额与账变', icon: 'wallet' },
    { name: 'notifications', label: '通知中心', caption: '消息与偏好', icon: 'notification' },
];
const route = useRoute(), router = useRouter();
const { user, logout: destroySession } = useSession();
const busy = ref(false), error = ref('');
const title = computed(() => route.meta.title || '前台管理');
const description = computed(() => route.meta.description || '');

async function logout() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        await destroySession();
        await router.replace({ name: 'auth.login' });
    } catch (exception) { error.value = exception.message; }
    finally { busy.value = false; }
}
</script>

<template>
    <section v-if="user" class="console-shell">
        <aside class="console-sidebar">
            <div class="console-sidebar-heading"><span class="console-symbol"><AppIcon name="home" :size="19" /></span><div><strong>个人工作台</strong><span>MEMBER CONSOLE</span></div></div>
            <nav class="console-menu" aria-label="前台管理菜单">
                <span class="console-menu-label">工作区</span>
                <RouterLink v-for="menu in menus" :key="menu.name" :to="{ name: menu.name }" class="console-menu-item"><span class="console-menu-icon"><AppIcon :name="menu.icon" :size="19" /></span><span><strong>{{ menu.label }}</strong><small>{{ menu.caption }}</small></span></RouterLink>
            </nav>
            <div class="console-sidebar-footer">
                <div class="console-profile"><img :src="user.avatar" :alt="`${user.name}的头像`"><div><strong>{{ user.name }}</strong><span>@{{ user.username }}</span></div><span class="online-dot" title="当前在线" /></div>
                <button class="console-logout" type="button" :disabled="busy" @click="logout"><AppIcon name="logout" :size="17" />退出登录</button>
            </div>
        </aside>
        <div class="console-workspace">
            <header class="console-header"><div><div class="console-breadcrumb"><span>前台管理</span><span>/</span><span>{{ title }}</span></div><h1>{{ title }}</h1><p>{{ description }}</p></div><div class="console-status"><span class="online-dot" />账户在线</div></header>
            <FeedbackMessage :message="error" />
            <main class="console-content"><RouterView /></main>
        </div>
    </section>
</template>
