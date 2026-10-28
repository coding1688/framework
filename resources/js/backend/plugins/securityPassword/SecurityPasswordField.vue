<script setup>
import { computed, onBeforeUnmount, ref, useId, watch } from 'vue';
import { ElButton, ElFormItem, ElInput } from 'element-plus';
import { useSecurityPasswordMessages } from './context';

defineOptions({ name: 'SecurityPasswordField' });
const props = defineProps({
    modelValue: { type: String, default: '' },
    configured: { type: Boolean, default: false },
    disabled: Boolean,
    grouped: { type: Boolean, default: true },
    required: { type: Boolean, default: true },
    errors: { type: Object, default: () => ({}) },
    field: { type: String, default: 'security_password' },
    error: { type: String, default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    setupHref: { type: String, default: '' },
    translate: Function,
    messages: Object,
});
const emit = defineEmits(['update:modelValue', 'setup-request']);
const { message, requestSetup } = useSecurityPasswordMessages(props);
const input = ref(null);
const id = `security-password-${useId()}`;
const fieldError = computed(() => {
    const errors = props.errors[props.field];
    return props.error || (Array.isArray(errors) ? errors[0] : errors) || '';
});
function update(value) { emit('update:modelValue', typeof value === 'string' ? value : ''); }
function clear() { emit('update:modelValue', ''); }
function focus() { input.value?.focus(); }
function setup(event) {
    if (props.disabled) { event?.preventDefault?.(); return; }
    emit('setup-request', event);
    if (!event?.defaultPrevented) requestSetup(event, props.setupHref);
}
watch(() => props.configured, configured => { if (!configured) clear(); }, { immediate: true });
onBeforeUnmount(clear);
defineExpose({ clear, focus });
</script>

<template>
    <component :is="grouped ? 'fieldset' : 'div'" class="security-password-field" :class="{ 'security-password-field--grouped': grouped }">
        <legend v-if="grouped" class="security-password-field__legend">{{ message('password_title') }}</legend>
        <ElFormItem :label="label || message('password_label')" :for="id" :required="required && configured" class="security-password-field__item">
            <div class="security-password-field__content">
                <template v-if="configured">
                    <ElInput :id="id" ref="input" :model-value="modelValue" type="password" show-password :name="field" :maxlength="128" autocomplete="off" :disabled="disabled" :required="required" :placeholder="placeholder || message('password_placeholder')" :aria-invalid="Boolean(fieldError)" :aria-describedby="`${id}-hint${fieldError ? ` ${id}-error` : ''}`" @update:model-value="update" />
                    <p :id="`${id}-hint`" class="security-password-field__hint">{{ message('password_hint') }}</p>
                </template>
                <template v-else>
                    <p :id="`${id}-hint`" class="security-password-field__hint">{{ message('password_not_configured') }}</p>
                    <a v-if="setupHref" :href="setupHref" class="security-password-field__setup" :aria-disabled="disabled" @click="setup">{{ message('password_setup') }}</a>
                    <ElButton v-else link type="primary" :disabled="disabled" @click="setup">{{ message('password_setup') }}</ElButton>
                </template>
                <p v-if="fieldError" :id="`${id}-error`" class="security-password-field__error" role="alert">{{ fieldError }}</p>
            </div>
        </ElFormItem>
    </component>
</template>

<style scoped>
.security-password-field { min-width: 0; }
.security-password-field--grouped { box-sizing: border-box; width: 100%; margin: 18px 0; padding: 14px 16px 16px; border: 1px solid var(--el-border-color, #dcdfe6); border-radius: 4px; }
.security-password-field__legend { margin-left: 8px; padding: 0 8px; color: var(--el-text-color-regular, #4b5563); font-size: 13px; font-weight: 500; line-height: 20px; }
.security-password-field--grouped .security-password-field__item { margin-bottom: 0; }
.security-password-field__content { width: 100%; min-width: 0; }
.security-password-field__hint { margin: 6px 0 0; color: var(--el-text-color-secondary, #6b7280); font-size: 12px; line-height: 1.5; }
.security-password-field__error { margin: 4px 0 0; color: var(--el-color-danger, #dc2626); font-size: 12px; line-height: 1.5; }
.security-password-field__setup { display: inline-block; margin-top: 4px; color: var(--el-color-primary, #409eff); font-size: 13px; }
</style>
