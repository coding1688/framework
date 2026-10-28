<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { t } from '@/locales';

const props = defineProps({
    sourceType: { type: String, default: 'manual' }, sourceKey: { type: String, default: null },
    sourceTypes: { type: Array, default: () => [] }, sources: { type: Array, default: () => [] }, categories: { type: Array, default: () => [] },
    loadOptions: { type: Function, default: null }, active: { type: Boolean, default: true }, disabled: Boolean,
    errors: { type: Object, default: () => ({}) }, typeField: { type: String, default: 'option_source_type' }, keyField: { type: String, default: 'option_source_key' },
});
const emit = defineEmits(['update:sourceType', 'update:sourceKey', 'change', 'preview', 'loading']);
const loading = ref(false), previewError = ref(''), resolvedOptions = ref({});
const groupedSources = computed(() => {
    const groups = new Map(props.categories.map(category => [category.key, { key: category.key, label: category.label, sources: [] }]));
    for (const source of props.sources.filter(source => source.type === props.sourceType)) {
        const category = source.category || '';
        if (!groups.has(category)) groups.set(category, { key: category, label: source.category_label || t('fields.other_sources'), sources: [] });
        groups.get(category).sources.push(source);
    }
    return [...groups.values()].filter(group => group.sources.length);
});
const previewLabels = computed(() => Object.values(resolvedOptions.value).slice(0, 4));
const remainingCount = computed(() => Math.max(0, Object.keys(resolvedOptions.value).length - previewLabels.value.length));
let controller;
let disposed = false;
function setLoading(value) { loading.value = value; emit('loading', value); }
function clearPreview() { resolvedOptions.value = {}; previewError.value = ''; emit('preview', {}); }
function changeType(value) {
    emit('update:sourceType', value);
    emit('update:sourceKey', null);
    emit('change', { type: value, key: null });
}
function changeKey(value) {
    emit('update:sourceKey', value || null);
    emit('change', { type: props.sourceType, key: value || null });
}
async function loadPreview() {
    controller?.abort();
    controller = null;
    setLoading(false);
    clearPreview();
    if (disposed || !props.active || props.sourceType === 'manual' || !props.sourceKey) return;
    if (!props.sources.some(source => source.type === props.sourceType && source.key === props.sourceKey) || !props.loadOptions) {
        previewError.value = t('fields.source_unavailable');
        return;
    }
    const current = new AbortController();
    controller = current;
    setLoading(true);
    try {
        const response = await props.loadOptions({ type: props.sourceType, key: props.sourceKey, signal: current.signal });
        if (disposed || controller !== current || !props.active) return;
        const options = response.data?.options ?? response.options ?? {};
        resolvedOptions.value = options && typeof options === 'object' ? Object.fromEntries(Object.entries(options)) : {};
        emit('preview', resolvedOptions.value);
    } catch (error) {
        if (!disposed && controller === current && error.name !== 'AbortError') previewError.value = error.message || t('fields.preview_failed');
    } finally { if (!disposed && controller === current) setLoading(false); }
}
watch(() => [props.sourceType, props.sourceKey, props.active], loadPreview, { immediate: true });
onBeforeUnmount(() => { disposed = true; controller?.abort(); emit('loading', false); });
</script>

<template>
    <div class="field-option-source">
        <div class="field-grid">
            <el-form-item :label="t('fields.option_source')" required :error="errors[typeField]?.[0]">
                <el-select :model-value="sourceType" :disabled="disabled" @update:model-value="changeType"><el-option v-for="type in sourceTypes" :key="type" :label="t(`fields.source_${type}`)" :value="type" /></el-select>
            </el-form-item>
            <el-form-item v-if="sourceType !== 'manual'" :label="t('fields.registered_source')" required :error="errors[keyField]?.[0]">
                <el-select :model-value="sourceKey" filterable clearable :disabled="disabled" :loading="loading" @update:model-value="changeKey">
                    <el-option-group v-for="group in groupedSources" :key="group.key" :label="group.label"><el-option v-for="source in group.sources" :key="source.key" :label="source.label" :value="source.key" /></el-option-group>
                </el-select>
            </el-form-item>
        </div>
        <div v-if="sourceType !== 'manual' && sourceKey" class="field-option-source__preview" aria-live="polite">
            <span v-if="previewError" role="alert" class="field-option-source__error">{{ previewError }}</span>
            <span v-else-if="loading">{{ t('fields.loading_options') }}</span>
            <template v-else-if="previewLabels.length"><el-tag v-for="(label, index) in previewLabels" :key="index" type="info" size="small" :title="label">{{ label }}</el-tag><span v-if="remainingCount">+{{ remainingCount }}</span></template>
            <span v-else>{{ t('fields.no_options') }}</span>
        </div>
        <slot v-if="sourceType === 'manual'" />
    </div>
</template>

<style scoped>
.field-option-source__preview { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin: -6px 0 16px; color: var(--el-text-color-secondary); font-size: 12px; }
.field-option-source__preview :deep(.el-tag) { max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
.field-option-source__error { color: var(--el-color-danger); }
</style>
