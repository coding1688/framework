<script setup>
import BaseCard from '@/components/ui/BaseCard.vue';
import {computed} from 'vue';
import {useRouter} from 'vue-router';
import {ArrowRight} from '@element-plus/icons-vue';
import { config } from '@/config';
import { t } from '@/locales';
import { state } from '@/store/modules/user';
import { flatten } from '@/utils/navigation';
const router=useRouter(),links=computed(()=>flatten(state.navigation).filter(node=>node.path));

</script>
<template><section class="welcome-card"><div><span class="eyebrow">FRAMEWORK / WORKSPACE</span><h1>{{ t('layout.home') }}</h1><p>{{ t('layout.welcome') }}</p></div><el-icon><AppIcon icon="dashboard-line" /></el-icon></section><div class="shortcut-grid"><BaseCard v-for="link in links" :key="link.id" shadow="hover"><el-icon class="shortcut-icon"><AppIcon :icon="link.icon" /></el-icon><h3>{{ link.name }}</h3><el-button text type="primary" :icon="ArrowRight" @click="router.push(link.path)">{{ t('layout.open') }}</el-button></BaseCard></div><el-empty v-if="!links.length" :description="t('layout.no_access')" /></template>
