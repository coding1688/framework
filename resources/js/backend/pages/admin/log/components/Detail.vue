<script setup>
import { ref } from 'vue';
import { t } from '@/locales';
import EditorDialog from '@/components/EditorDialog.vue';
const visible = ref(false), record = ref(null);
function open(action, row) { record.value = row; visible.value = true; }
defineExpose({ open });
</script>

<template>
<EditorDialog v-model="visible" :title="t('common.details')" read-only><pre class="log-detail">{{ JSON.stringify(record,null,2) }}</pre></EditorDialog>
</template>
