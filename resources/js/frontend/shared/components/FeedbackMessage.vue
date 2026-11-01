<script setup>
defineProps({ message: { type: String, default: '' }, type: { type: String, default: 'error' } });
</script>

<template>
    <div v-if="message" class="feedback" :class="`feedback--${type}`" :role="type === 'error' ? 'alert' : 'status'">{{ message }}</div>
</template>
