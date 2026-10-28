<script setup>
import { t } from '@/locales';
import StatusTag from '@/components/StatusTag.vue';
defineProps({ detailFields: Array });
</script>

<template>
<el-descriptions :column="2" border><el-descriptions-item v-for="[key,value] in detailFields" :key="key" :label="t(key)"><StatusTag v-if="key==='status'" :active="value"/><span v-else>{{ value||'—' }}</span></el-descriptions-item></el-descriptions>
</template>
