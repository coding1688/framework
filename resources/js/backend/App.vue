<script setup>
import { authApi } from '@/api/auth';

import { tabs, visitTab, closeTab as removeTab, pruneTabs, resetTabs } from '@/store/modules/tabs';
import { collapsed, preferences } from '@/store/modules/settings';
import { pageTitle } from '@/utils/pageTitle';
import BaseAlert from '@/components/ui/BaseAlert.vue';
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Fold, Expand, Refresh, Lock, SwitchButton, ArrowDown, Search } from '@element-plus/icons-vue';
import { config } from '@/config';
import { t } from '@/locales';
import { state, userinfo, clearIdentity } from '@/store/modules/user';
import { flatten } from '@/utils/navigation';
import { language } from '@/utils/language';
import { fail } from '@/utils/feedback';
import locale from '@/locales/element-plus';
import MenuBranch from '@/components/MenuBranch.vue';
import NotificationCenter from '@/components/NotificationCenter.vue';
const route = useRoute(), router = useRouter(), busy = ref(false), revision = ref(0), bootError = ref('');
const menuQuery = ref(''), menuSearch = ref(null), navigationMenu = ref(null);
const links = computed(() => flatten(state.navigation).filter(node => node.path));
const visibleNavigation = computed(() => {
    const query = menuQuery.value.trim().toLowerCase();
    if (!query) return state.navigation;
    function filter(nodes) {
        return nodes.flatMap(node => {
            const children = filter(node.children || []);
            const matches = [node.name, node.title, node.route_name, node.canonical_permission, node.path]
                .filter(Boolean).some(value => value.toLowerCase().includes(query));
            return matches || children.length ? [{ ...node, children }] : [];
        });
    }
    return filter(state.navigation);
});
const showHome = computed(() => {
    const query = menuQuery.value.trim().toLowerCase();
    return !query || t('layout.home').toLowerCase().includes(query) || config.pages.home.includes(query);
});
const title = path => links.value.find(node => node.path === path)?.title || t(Object.keys(config.pages).find(key => config.pages[key] === path) || 'home');
const tabIcon = path => path === config.pages.home ? 'dashboard-3-line' : path === config.pages.profile ? 'lock-line' : links.value.find(node => node.path === path)?.icon;
const tabTitle = computed(() => title(route.path));
function navigationTrail(nodes, ancestors = []) {
    for (const node of nodes) {
        const trail = [...ancestors, node];
        if (node.path === route.path) return trail;
        const childTrail = navigationTrail(node.children || [], trail);
        if (childTrail.length) return childTrail;
    }
    return [];
}
const breadcrumbs = computed(() => {
    const items = [{ title: t('layout.home'), path: config.pages.home }];
    if (route.path === config.pages.home) return items;
    const trail = navigationTrail(state.navigation);
    trail.forEach(node => items.push({ title: node.title, path: node.path || '' }));
    if (!trail.length) items.push({ title: tabTitle.value, path: route.path });
    return items;
});
const allowed = path => [config.pages.home, config.pages.profile].includes(path) || links.value.some(node => node.path === path);
function reload() { location.reload(); }
function navigate(path) {
    if (!allowed(path)) return;
    menuQuery.value = '';
    router.push(path).catch(fail);
}
function openFirstResult() {
    if (!menuQuery.value.trim()) return;
    if (showHome.value) { navigate(config.pages.home); return; }
    const first = flatten(visibleNavigation.value).find(node => node.path);
    if (first) navigate(first.path);
}
function focusMenuSearch() {
    if (route.name === 'login') return;
    collapsed.value = false;
    nextTick(() => menuSearch.value?.focus());
}
function menuShortcut(event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        focusMenuSearch();
    }
}
function activeGroups() {
    const groups = [];
    function visit(nodes) {
        return nodes.some(node => {
            if (node.path === route.path) {
                if (node.children?.length) groups.push('group-' + node.id);
                return true;
            }
            if (!visit(node.children || [])) return false;
            groups.push('group-' + node.id);
            return true;
        });
    }
    visit(state.navigation);
    return groups;
}
watch([() => route.path, () => state.navigation, menuQuery, collapsed], () => {
    if (collapsed.value) return;
    const filteredGroups = menuQuery.value.trim() ? flatten(visibleNavigation.value).filter(node => node.children?.length).map(node => 'group-' + node.id) : [];
    nextTick(() => [...new Set([...activeGroups(), ...filteredGroups])].forEach(index => navigationMenu.value?.open(index)));
}, { immediate: true, flush: 'post' });
watch(() => route.path, path => { if (path === config.pages.login || !allowed(path)) return; visitTab(path); document.title = pageTitle(title(path)); });
function closeTab(path) { const next = removeTab(path); if (route.path === path) navigate(next); }
async function refreshIdentity() { await userinfo(true); pruneTabs(allowed); if (!allowed(route.path)) await router.replace(config.pages.home); }
let recovering = false;
async function forbidden() { if (recovering) return; recovering = true; try { await refreshIdentity(); await router.replace(config.pages.home); } catch (error) { fail(error); } finally { recovering = false; } }
function unauthorized() { resetTabs(); router.replace(config.pages.login).catch(fail); }
async function logout() { if (busy.value) return; busy.value = true; try { await authApi.logout(); clearIdentity(); unauthorized(); } catch (error) { fail(error); } finally { busy.value = false; } }
window.addEventListener('backend:forbidden', forbidden);
window.addEventListener('backend:unauthorized', unauthorized);
window.addEventListener('keydown', menuShortcut);
onUnmounted(() => {
    window.removeEventListener('backend:forbidden', forbidden);
    window.removeEventListener('backend:unauthorized', unauthorized);
    window.removeEventListener('keydown', menuShortcut);
});
router.isReady().catch(error => { bootError.value = error.message; });
</script>
<template>
<el-config-provider :locale="locale" :size="preferences.density==='compact'?'small':'default'">
<BaseAlert v-if="bootError" class="bootstrap-error" :title="bootError" type="error" :closable="false"><el-button @click="reload()">{{ t('common.retry') }}</el-button></BaseAlert>
<router-view v-else-if="route.name==='login'" />
<div v-else class="spa-workspace" :class="{'is-collapsed':collapsed}" v-loading="!state.user">
  <aside class="spa-sidebar">
    <a class="spa-brand" :href="config.pages.home" @click.prevent="navigate(config.pages.home)"><b>F</b><span v-if="!collapsed">Framework</span></a>
    <div v-if="!collapsed" class="spa-menu-search"><el-input ref="menuSearch" v-model="menuQuery" :placeholder="t('common.search')" :aria-label="t('common.search')" :prefix-icon="Search" clearable @keyup.enter="openFirstResult" /></div>
    <el-menu ref="navigationMenu" popper-class="spa-menu-popper" :default-active="route.path" :collapse="collapsed" :collapse-transition="false" @select="navigate">
      <el-menu-item v-if="showHome" :index="config.pages.home"><el-icon><AppIcon icon="dashboard-3-line" /></el-icon><template #title><span class="spa-menu-label" :title="t('layout.home')">{{ t('layout.home') }}</span></template></el-menu-item>
      <MenuBranch v-for="node in visibleNavigation" :key="node.id" :node="node" />
    </el-menu>
    <el-empty v-if="menuQuery && !showHome && !visibleNavigation.length" class="spa-menu-empty" :image-size="48" :description="t('common.empty')" />
  </aside>
  <section class="spa-main"><header class="spa-topbar"><el-button text :aria-label="t('common.toggle_menu')" @click="collapsed=!collapsed"><el-icon><component :is="collapsed?Expand:Fold" /></el-icon></el-button><el-breadcrumb separator="/"><el-breadcrumb-item v-for="(item, index) in breadcrumbs" :key="`${item.path || item.title}-${index}`"><a v-if="item.path && index < breadcrumbs.length - 1" :href="item.path" @click.prevent="navigate(item.path)">{{ item.title }}</a><span v-else>{{ item.title }}</span></el-breadcrumb-item></el-breadcrumb><div class="spa-top-actions"><NotificationCenter /><el-button text :icon="Refresh" :aria-label="t('common.refresh')" @click="revision++" /><el-select class="spa-language" :model-value="config.locale" :aria-label="t('common.locale')" @change="language"><el-option v-for="(label,key) in (config.interfaceLocales || config.locales)" :key="key" :label="label" :value="key" /></el-select><el-dropdown @command="command=>command==='profile'?navigate(config.pages.profile):logout()"><button class="spa-user"><el-avatar :size="28">{{ state.user?.name?.slice(0,1) }}</el-avatar><span>{{ state.user?.name }}</span><el-icon><ArrowDown /></el-icon></button><template #dropdown><el-dropdown-menu><el-dropdown-item command="profile" :icon="Lock">{{ t('auth.profile') }}</el-dropdown-item><el-dropdown-item command="logout" :icon="SwitchButton" :disabled="busy">{{ t('auth.logout') }}</el-dropdown-item></el-dropdown-menu></template></el-dropdown></div></header>
  <el-tabs class="spa-page-tabs" :model-value="route.path" type="card" @tab-change="navigate" @tab-remove="closeTab"><el-tab-pane v-for="path in tabs" :key="path" :label="title(path)" :name="path" :closable="path!==config.pages.home"><template #label><span class="spa-tab-label"><el-icon><AppIcon :icon="tabIcon(path)" /></el-icon><span>{{ title(path) }}</span></span></template></el-tab-pane></el-tabs>
  <main class="spa-content"><router-view v-slot="{Component}"><component :is="Component" v-if="state.user" :key="route.name" :revision="revision" @identity-changed="refreshIdentity().catch(fail)" /></router-view></main><footer class="spa-footer">Framework · {{ t('layout.brand') }}</footer></section>
</div>
</el-config-provider>
</template>
