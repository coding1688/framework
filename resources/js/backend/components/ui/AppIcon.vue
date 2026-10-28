<script setup>
import { computed } from 'vue';
import { resolveIcon } from '@/icons';
import '@styles/icon.css';

const props = defineProps({
    icon: { type: String, default: 'folder-line' },
    isCustomSvg: Boolean,
    size: [Number, String],
    color: String,
    spin: Boolean,
    label: String,
});
const iconClass = computed(() => `ri-${resolveIcon(props.icon)}`);
const symbol = computed(() => /^[a-zA-Z0-9_-]+$/.test(props.icon) ? `#icon-${props.icon}` : undefined);
const style = computed(() => ({
    fontSize: typeof props.size === 'number' ? `${props.size}px` : props.size,
    color: props.color,
}));
</script>

<template>
    <span class="app-icon" :class="{ 'app-icon-spin': spin }" :style="style" :role="label ? 'img' : undefined" :aria-label="label" :aria-hidden="label ? undefined : true">
        <svg v-if="isCustomSvg" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><use :href="symbol" /></svg>
        <i v-else :class="iconClass" aria-hidden="true" />
    </span>
</template>
