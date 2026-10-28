<script setup>
import { ElPagination } from 'element-plus';
defineOptions({ inheritAttrs: false });
const props = defineProps({
    ...ElPagination.props,
    background: { type: Boolean, default: true },
    layout: { type: String, default: 'total, prev, pager, next, jumper' },
});
const emit = defineEmits(['update:currentPage', 'update:pageSize']);
</script>
<template>
    <el-pagination v-bind="{ ...$attrs, ...props }" @update:current-page="emit('update:currentPage', $event)" @update:page-size="emit('update:pageSize', $event)" />
</template>
