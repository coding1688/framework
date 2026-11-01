<script setup>
defineProps({ label: { type: String, default: '正在加载…' } });
</script>

<template>
    <div class="loading-state" role="status"><span class="loading-spinner" />{{ label }}</div>
</template>
