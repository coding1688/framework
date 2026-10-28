<script setup>
import { ElAlert } from 'element-plus';
defineOptions({ inheritAttrs: false });
defineProps({
    ...ElAlert.props,
    closable: { type: Boolean, default: false },
    showIcon: { type: Boolean, default: true },
    type: { type: String, default: 'info' },
});
</script>
<template>
    <el-alert v-bind="$attrs" :title="title" :description="description" :type="type" :closable="closable" :close-text="closeText" :center="center" :show-icon="showIcon" :effect="effect">
        <template v-if="title || $slots.title" #title><slot name="title">{{ title }}</slot></template>
        <template v-if="description || $slots.default" #default><slot>{{ description }}</slot></template>
    </el-alert>
</template>
