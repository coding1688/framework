<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { ElButton, ElForm } from 'element-plus';
import BaseDialog from '../../components/ui/BaseDialog.vue';
import SecurityPasswordField from './SecurityPasswordField.vue';
import { createSecurityPasswordPrompt, createSecurityPasswordState } from './prompt';
import { useSecurityPasswordMessages } from './context';

defineOptions({ name: 'SecurityPasswordDialog' });
const props = defineProps({
    configured: { type: Boolean, default: false },
    setupHref: { type: String, default: '' },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    confirmLabel: { type: String, default: '' },
    width: { type: [String, Number], default: '50vw' },
    translate: Function,
    messages: Object,
});
const emit = defineEmits(['setup-request']);
const { message } = useSecurityPasswordMessages(props);
const state = reactive(createSecurityPasswordState());
const prompt = createSecurityPasswordPrompt({ state, isConfigured: () => props.configured, translate: key => message(key.replace('security.', '')) });
const field = ref(null);
const dialogTitle = computed(() => state.title || props.title || message('password_title'));
const dialogDescription = computed(() => state.description || props.description);
function setup(event) {
    prompt.cancel('setup');
    emit('setup-request', event);
}
function visibility(value) { if (!value) prompt.cancel(); }
function focus() { field.value?.focus(); }
onBeforeUnmount(prompt.dispose);
defineExpose({ open: prompt.open, cancel: prompt.cancel });
</script>

<template>
    <BaseDialog :model-value="state.visible" :title="dialogTitle" :width="width" :busy="state.pending" :close-on-click-modal="false" @update:model-value="visibility" @opened="focus">
        <p v-if="dialogDescription" class="security-password-dialog__description">{{ dialogDescription }}</p>
        <p v-if="state.error" class="security-password-dialog__error" role="alert">{{ state.error }}</p>
        <ElForm label-position="right" label-width="144px" class="horizontal-editor-form" :aria-busy="state.pending" @submit.prevent="prompt.confirm">
            <SecurityPasswordField ref="field" :grouped="false" :model-value="state.password" :configured="configured" :disabled="state.pending" :errors="state.fieldErrors" :setup-href="setupHref" :translate="translate" :messages="messages" @update:model-value="prompt.updatePassword" @setup-request="setup" />
        </ElForm>
        <template #footer>
            <ElButton :disabled="state.pending" @click="prompt.cancel()">{{ message('password_cancel') }}</ElButton>
            <ElButton type="primary" :loading="state.pending" :disabled="!configured || state.pending" @click="prompt.confirm">{{ state.confirmLabel || confirmLabel || message('password_confirm') }}</ElButton>
        </template>
    </BaseDialog>
</template>

<style scoped>
.security-password-dialog__description { margin: 0 0 16px; color: var(--el-text-color-regular, #4b5563); }
.security-password-dialog__error { margin: 0 0 12px; color: var(--el-color-danger, #dc2626); font-size: 13px; }
</style>
