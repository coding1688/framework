<script setup>
import { ElCard, ElSkeleton } from 'element-plus';
defineOptions({ inheritAttrs: false });
defineProps({
    ...ElCard.props,
    shadow: { type: String, default: 'never' },
    title: { type: String, default: '' },
    loading: Boolean,
    skeleton: Boolean,
    skeletonRows: { type: Number, default: 5 },
});
</script>
<template>
    <el-card v-bind="$attrs" :body-style="bodyStyle" :body-class="bodyClass" :header-class="headerClass" :footer-class="footerClass" :shadow="shadow" class="base-card" :aria-busy="loading">
        <template v-if="$slots.header || title || header" #header><slot name="header">{{ title || header }}</slot></template>
        <el-skeleton v-if="skeleton" :loading="loading" :rows="skeletonRows" animated><template #default><slot /></template></el-skeleton>
        <div v-else v-loading="loading"><slot /></div>
        <template v-if="$slots.footer || footer" #footer><slot name="footer">{{ footer }}</slot></template>
    </el-card>
</template>
