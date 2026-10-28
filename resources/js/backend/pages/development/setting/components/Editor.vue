<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Plus, Delete } from '@element-plus/icons-vue';
import { config } from '@/config';
import { state as identity } from '@/store/modules/user';
import { SecurityPasswordField, securityPasswordRequirement } from '@/plugins/securityPassword';
import { t } from '@/locales';
import { settingDefinitionApi } from '@/api/system/settingDefinition';
import { useEditorDialog } from '@/composables/useEditorDialog';
import EditorDialog from '@/components/EditorDialog.vue';
import LocalizedField from '@/components/LocalizedField.vue';
import FormStatusField from '@/components/FormStatusField.vue';
import FieldRenderer from '@/components/Fields/FieldRenderer.vue';
import ValidationSettings from '@/components/Fields/ValidationSettings.vue';
import OptionSourceSelector from '@/components/Fields/OptionSourceSelector.vue';

const props = defineProps({ options: { type: Object, default: () => ({ groups: [], input_types: [] }) }, groupId: { type: [Number, String], default: null } });
const emit = defineEmits(['saved']);
const { visible, busy, errors, editing, form, reset, submit } = useEditorDialog(settingDefinitionApi, emit);
const optionRows = ref([]), securityPassword = ref(''), externalOptions = ref({}), previewLoading = ref(false);
const securityConfigured = computed(() => identity.user?.security_password_set === true);
const requiresSecurity = computed(() => !!securityPasswordRequirement(`development.setting.${editing.value ? 'update' : 'create'}`, config.routes));
function selectableTree(groups) { return groups.map(group => ({ ...group, disabled: false, children: selectableTree(group.children || []) })); }
const groupsTree = computed(() => selectableTree(props.options.groups_tree?.length ? props.options.groups_tree : props.options.groups || []));
const keyPrefix = computed(() => {
    const groups = new Map((props.options.groups || []).map(group => [String(group.id), group]));
    const codes = [], visited = new Set();
    let group = groups.get(String(form.group_id));
    while (group && !visited.has(group.id)) {
        visited.add(group.id);
        if (group.code) codes.unshift(group.code);
        group = group.parent_id == null ? null : groups.get(String(group.parent_id));
    }
    return codes.length ? `${codes.join('.')}.` : '';
});
const keySuffixMaxLength = computed(() => Math.max(0, 128 - keyPrefix.value.length));
const keySuffix = computed({
    get() {
        const key = String(form.key || '');
        return keyPrefix.value && key.startsWith(keyPrefix.value) ? key.slice(keyPrefix.value.length) : key;
    },
    set(value) {
        const key = String(value ?? '');
        const suffix = keyPrefix.value && key.startsWith(keyPrefix.value) ? key.slice(keyPrefix.value.length) : key;
        form.key = keyPrefix.value + suffix.slice(0, keySuffixMaxLength.value);
    },
});
function pasteKey(event) {
    const value = event.clipboardData?.getData('text');
    if (keyPrefix.value && value?.startsWith(keyPrefix.value)) {
        event.preventDefault();
        keySuffix.value = value;
    }
}
watch(keyPrefix, (prefix, previous) => {
    if (editing.value || !visible.value) return;
    const key = String(form.key || '');
    const suffix = previous && key.startsWith(previous) ? key.slice(previous.length) : key;
    form.key = prefix + suffix;
}, { flush: 'sync' });
const blank = () => Object.fromEntries(Object.keys(config.locales).map(locale => [locale, '']));
const blankValue = () => ({ value_string: null, value_number: null, value_boolean: null, value_json: [], value_i18n: {} });
const selectedType = computed(() => props.options.input_types?.find(type => type.key === form.input_type));
const supportsMultiple = computed(() => selectedType.value?.supports_multiple ?? false);
const supportsOptions = computed(() => selectedType.value?.options_mode === 'always' || (selectedType.value?.options_mode === 'multiple' && form.multiple));
const validationSchema = computed(() => form.multiple ? selectedType.value?.multiple_validation_schema : selectedType.value?.validation_schema);
const previewOptions = computed(() => form.option_source_type !== 'manual' ? externalOptions.value : Object.fromEntries(optionRows.value.filter(option => option.value).map(option => [option.value, option.label_i18n.en || option.value])));
const defaultDefinition = computed(() => ({ name: t('development.setting.default_value'), input_type: form.input_type, multiple: form.multiple, validation: form.validation, options: previewOptions.value, required: false }));
const defaultErrors = computed(() => Object.fromEntries(Object.entries(errors.value).map(([key, value]) => [key.startsWith('default_value.value_i18n.') ? key.replace('default_value.value_i18n.', 'default_value.') : key.startsWith('default_value.') ? 'default_value' : key, value])));
let optionSequence = 0;

function validationDefaults(inputType, multiple, current = {}) {
    const fieldType = props.options.input_types?.find(type => type.key === inputType);
    return { ...(multiple ? fieldType?.multiple_validation_defaults : fieldType?.validation_defaults), ...current };
}
function changeInputType(inputType) {
    const fieldType = props.options.input_types?.find(type => type.key === inputType);
    if (!fieldType?.supports_multiple) form.multiple = fieldType?.default_multiple ?? false;
    form.validation = validationDefaults(inputType, form.multiple);
    form.default_value = blankValue();
    if (inputType !== 'text') form.secret = false;
    if (!supportsOptions.value) { form.option_source_type = 'manual'; form.option_source_key = null; externalOptions.value = {}; }
}
function changeMultiple() {
    form.validation = validationDefaults(form.input_type, form.multiple);
    form.default_value = blankValue();
}
function changeSecret(value) {
    if (value) form.default_value = blankValue();
}
function changeOptionSource() { form.default_value = blankValue(); externalOptions.value = {}; }
function loadSourceOptions({ type, key, signal }) { return settingDefinitionApi.optionValues({ query: { option_source_type: type, option_source_key: key }, signal }); }
function addOption() { optionRows.value.push({ id: ++optionSequence, value: '', label_i18n: blank() }); }
async function open(row = null) {
    const record = row ? (await settingDefinitionApi.detail({ id: row.id })).data : null;
    const inputType = record?.input_type || 'text';
    const fieldType = props.options.input_types?.find(type => type.key === inputType);
    const multiple = record?.multiple ?? fieldType?.default_multiple ?? false;
    reset('edit', record, {
        group_id: record?.group_id ?? (props.groupId || props.options.groups?.[0]?.id) ?? null,
        key: record?.key || '', name_i18n: { ...blank(), ...record?.name_i18n },
        description_i18n: { ...blank(), ...record?.description_i18n },
        input_type: inputType, multiple, option_source_type: record?.option_source_type || 'manual', option_source_key: record?.option_source_key || null, validation: validationDefaults(inputType, multiple, record?.validation || {}),
        default_value: { ...blankValue(), ...record?.default_value }, secret: record?.secret ?? false,
        active: record?.active ?? true, sort: record?.sort ?? 0,
    });
    optionRows.value = Object.keys(record?.options_i18n?.en || {}).map(value => ({
        id: ++optionSequence, value,
        label_i18n: Object.fromEntries(Object.keys(config.locales).map(locale => [locale, record.options_i18n?.[locale]?.[value] || ''])),
    }));
    if (!record) form.key = keyPrefix.value;
    externalOptions.value = record?.option_source_type !== 'manual' ? record?.options || {} : {};
    previewLoading.value = false;
    securityPassword.value = '';
    visible.value = true;
}
function hasDefaultValue(value) {
    return value.value_number !== null && value.value_number !== undefined
        || value.value_boolean !== null && value.value_boolean !== undefined
        || typeof value.value_string === 'string' && value.value_string !== ''
        || value.value_json?.length > 0
        || Object.values(value.value_i18n || {}).some(item => item !== null && item !== '');
}
function save() {
    if (previewLoading.value || requiresSecurity.value && !securityConfigured.value) return;
    if (!editing.value) {
        const suffix = keySuffix.value.trim();
        if (!suffix) { errors.value = { ...errors.value, key: [t('development.setting.key_suffix_required')] }; return; }
        form.key = keyPrefix.value + suffix;
    }
    if (String(form.key || '').length > 128) { errors.value = { ...errors.value, key: [t('development.setting.key_length_invalid')] }; return; }
    if (supportsOptions.value && form.option_source_type === 'manual') {
        const keys = optionRows.value.map(option => option.value.trim());
        if (!keys.length || keys.some(value => !value) || new Set(keys).size !== keys.length || optionRows.value.some(option => !option.label_i18n.en?.trim())) {
            errors.value = { ...errors.value, options_i18n: [t('development.setting.options_invalid')] };
            return;
        }
    }
    const options_i18n = Object.fromEntries(Object.keys(config.locales).map(locale => [locale, Object.fromEntries(
        supportsOptions.value && form.option_source_type === 'manual' ? optionRows.value.filter(option => option.label_i18n[locale]?.trim()).map(option => [option.value.trim(), option.label_i18n[locale].trim()]) : [],
    )]));
    return submit(editing.value ? 'update' : 'create', { ...form, option_source_type: supportsOptions.value ? form.option_source_type : 'manual', option_source_key: supportsOptions.value && form.option_source_type !== 'manual' ? form.option_source_key : null, options_i18n, default_value: form.secret || !hasDefaultValue(form.default_value) ? null : form.default_value, ...(requiresSecurity.value ? { security_password: securityPassword.value } : {}) });
}
watch([visible, requiresSecurity], ([isVisible, required]) => { if (!isVisible || !required) securityPassword.value = ''; }, { flush: 'sync' });
onBeforeUnmount(() => { securityPassword.value = ''; });
defineExpose({ open });
</script>

<template>
    <EditorDialog v-model="visible" :title="t(editing ? 'common.edit' : 'common.create')" width="82vw" :busy="busy" :read-only="previewLoading || requiresSecurity && !securityConfigured" @save="save">
        <el-form label-position="right" label-width="auto" class="horizontal-editor-form">
            <div class="field-grid">
                <el-form-item :label="t('development.setting.group')" required :error="errors.group_id?.[0]"><el-tree-select v-model="form.group_id" :data="groupsTree" node-key="id" :props="{ label: 'name', children: 'children' }" check-strictly default-expand-all :render-after-expand="false" /></el-form-item>
                <el-form-item :label="t('development.setting.key')" required :error="errors.key?.[0]">
                    <el-input v-if="editing" v-model="form.key" maxlength="128" :placeholder="t('development.setting.key_placeholder')" />
                    <el-input v-else v-model="keySuffix" :maxlength="keySuffixMaxLength" :disabled="keySuffixMaxLength === 0" :placeholder="t('development.setting.key_suffix_placeholder')" @paste="pasteKey"><template v-if="keyPrefix" #prepend><span class="setting-key-prefix" :title="keyPrefix">{{ keyPrefix }}</span></template></el-input>
                </el-form-item>
            </div>
            <LocalizedField v-model="form.name_i18n" field="name_i18n" :label="t('common.name')" :errors="errors" :maxlength="128" required />
            <LocalizedField v-model="form.description_i18n" field="description_i18n" :label="t('development.setting.description')" :errors="errors" type="textarea" :maxlength="512" />
            <div class="field-grid">
                <el-form-item :label="t('development.setting.input_type')" required :error="errors.input_type?.[0]"><el-select v-model="form.input_type" @change="changeInputType"><el-option v-for="type in options.input_types || []" :key="type.key" :label="t(`development.setting.type_${type.key}`)" :value="type.key" /></el-select></el-form-item>
                <el-form-item v-if="supportsMultiple" :label="t('development.setting.multiple')"><el-switch v-model="form.multiple" @change="changeMultiple" /></el-form-item>
            </div>
            <OptionSourceSelector v-if="supportsOptions" v-model:source-type="form.option_source_type" v-model:source-key="form.option_source_key" :source-types="options.option_source_types || []" :sources="options.option_sources || []" :categories="options.option_source_categories || []" :load-options="loadSourceOptions" :active="visible" :disabled="busy" :errors="errors" @change="changeOptionSource" @preview="externalOptions = $event" @loading="previewLoading = $event">
            <section class="setting-definition-section">
                <div class="setting-definition-heading"><h3>{{ t('development.setting.options') }}</h3><el-button :icon="Plus" @click="addOption">{{ t('development.setting.add_option') }}</el-button></div>
                <el-alert v-if="errors.options_i18n" :title="errors.options_i18n[0]" type="error" :closable="false" />
                <el-table :data="optionRows" border class="management-table setting-option-table">
                    <el-table-column :label="t('development.setting.option_value')" width="220"><template #default="{ row }"><el-input v-model="row.value" maxlength="128" /></template></el-table-column>
                    <el-table-column :label="t('development.setting.option_label')" min-width="380"><template #default="{ row }"><LocalizedField v-model="row.label_i18n" :field="`option-${row.id}`" label="" :errors="{}" :maxlength="128" required /></template></el-table-column>
                    <el-table-column :label="t('common.actions')" width="70" align="center"><template #default="{ $index }"><el-button link type="danger" :icon="Delete" :aria-label="t('common.delete')" @click="optionRows.splice($index, 1)" /></template></el-table-column>
                </el-table>
            </section>
            </OptionSourceSelector>
            <section class="setting-definition-section">
                <h3>{{ t('development.setting.validation') }}</h3>
                <ValidationSettings v-model="form.validation" :schema="validationSchema || []" :errors="errors" label-namespace="development.setting" />
            </section>
            <div class="field-grid">
                <el-form-item v-if="form.input_type === 'text'" :label="t('development.setting.secret')"><el-switch v-model="form.secret" @change="changeSecret" /></el-form-item>
                <FormStatusField v-model="form.active" />
                <el-form-item :label="t('common.sort')"><el-input-number v-model="form.sort" :min="0" :max="99999" :precision="0" /></el-form-item>
            </div>
            <FieldRenderer v-if="!form.secret && selectedType" v-model="form.default_value" :definition="defaultDefinition" :field-type="selectedType" field="default_value" :errors="defaultErrors" />
            <SecurityPasswordField v-if="requiresSecurity" v-model="securityPassword" :configured="securityConfigured" :setup-href="config.pages.profile" :errors="errors" :disabled="busy" />
            <el-alert v-if="form.secret" :title="t('development.setting.secret_hint')" type="info" :closable="false" show-icon />
        </el-form>
    </EditorDialog>
</template>

<style scoped>
.setting-key-prefix { display: block; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.setting-definition-section { margin: 18px 0; }
.setting-definition-section h3 { margin: 0 0 14px; font-size: 14px; font-weight: 600; }
.setting-definition-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.setting-definition-heading h3 { margin: 0; }
.setting-option-table :deep(.el-form-item) { margin-bottom: 0; }
.setting-option-table :deep(.el-form-item__label) { display: none; }
</style>
