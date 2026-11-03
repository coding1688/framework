<script setup>
import { useSession } from '@/shared/composables/useSession';
import AppIcon from '@/shared/components/AppIcon.vue';

const { user } = useSession();
</script>

<template>
    <div class="site-shell">
        <header class="site-header-bar">
            <div class="site-header">
                <RouterLink class="brand" :to="{ name: 'home' }"><span class="brand-mark">F</span><span>Framework</span></RouterLink>
                <nav class="header-navigation" aria-label="文档导航"><RouterLink :to="{ name: 'docs.development.article', params: { slug: 'quick-start' } }">快速开始</RouterLink><RouterLink :to="{ name: 'docs.development' }">开发文档</RouterLink><RouterLink :to="{ name: 'docs.usage' }">使用文档</RouterLink></nav>
                <RouterLink class="account-trigger" :to="{ name: user ? 'account' : 'auth.login' }"><AppIcon name="user" :size="18" /><span>{{ user ? user.name : '登录 / 注册' }}</span></RouterLink>
            </div>
        </header>
        <main class="site-main"><RouterView /></main>
        <footer class="site-footer">© 2026 Framework · 开放、清晰的开发文档</footer>
    </div>
</template>
