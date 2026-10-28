<script setup>
import BaseDialog from '@/components/ui/BaseDialog.vue';
import { t } from '@/locales';
defineProps({ modelValue: Boolean, title: String, width: { type: String, default: '76vw' }, busy: Boolean, readOnly: Boolean });
const emit = defineEmits(['update:modelValue', 'save']);
</script>
<template>
    <BaseDialog :model-value="modelValue" :title="title" :width="width" :busy="busy" @update:model-value="emit('update:modelValue', $event)">
        <slot />
        <template #footer>
            <el-button :disabled="busy" @click="emit('update:modelValue', false)">{{ t('common.cancel') }}</el-button>
            <el-button v-if="!readOnly" type="primary" :loading="busy" @click="emit('save')">{{ t('common.save') }}</el-button>
        </template>
    </BaseDialog>
</template>
