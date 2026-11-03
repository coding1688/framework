<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { config } from '@/config';
import { state as identity } from '@/store/modules/user';
import { settingApi } from '@/api/system/setting';
import { can } from '@/utils/permission';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';
import EditorDialog from '@/components/EditorDialog.vue';
import { SecurityPasswordField, securityPasswordRequirement } from '@/plugins/securityPassword';
import ValueField from './ValueField.vue';
import { validateFieldValue } from '@/components/Fields/value';

const props = defineProps({ fieldTypes: { type: Object, default: () => ({}) } });
const emit = defineEmits(['saved']);
const visible = ref(false), busy = ref(false), errors = ref({});
const definition = ref(null), groupId = ref(null), value = ref(blankValue()), initial = ref(blankValue());
const clear = ref(false), securityPassword = ref('');
const securityConfigured = computed(() => identity.user?.security_password_set === true);
const requiresSecurity = computed(() => !!securityPasswordRequirement('system.setting.update', config.routes));
const changed = computed(() => clear.value || (definition.value?.secret
    ? typeof value.value.value_string === 'string' && value.value.value_string !== ''
    : JSON.stringify(value.value) !== JSON.stringify(initial.value)));
let controller;
let disposed = false;
function blankValue() { return { value_string: null, value_number: null, value_boolean: null, value_json: [], value_i18n: {} }; }
function copy(item) { return JSON.parse(JSON.stringify(item)); }
function open(row, selectedGroup) {
    if (busy.value || !row.active || !can('settings', 'update')) return;
    definition.value = copy(row);
    groupId.value = selectedGroup;
    value.value = row.secret ? blankValue() : { ...blankValue(), ...copy(row.value ?? row.default_value ?? {}) };
    initial.value = copy(value.value);
    clear.value = false;
    securityPassword.value = '';
    errors.value = {};
    visible.value = true;
}
function toggleClear(next) {
    clear.value = next;
    value.value = next ? (definition.value.secret ? blankValue() : { ...blankValue(), ...copy(definition.value.default_value ?? {}) }) : copy(initial.value);
}
function updateValue(next) { value.value = next; clear.value = false; }
async function save() {
    if (busy.value || !visible.value || !changed.value || requiresSecurity.value && !securityConfigured.value || !can('settings', 'update')) return;
    errors.value = {};
    if (!clear.value && !definition.value.secret) {
        const message = validateFieldValue(definition.value, props.fieldTypes[definition.value.input_type], value.value);
        if (message) { errors.value = { 'values.0.value': [message] }; return; }
    }
    busy.value = true;
    controller = new AbortController();
    try {
        const response = await settingApi.update({ data: {
            group_id: groupId.value,
            values: [{ definition_id: definition.value.id, value: clear.value ? null : definition.value.secret ? { value_string: value.value.value_string } : copy(value.value), clear: clear.value }],
            ...(requiresSecurity.value ? { security_password: securityPassword.value } : {}),
        }, signal: controller.signal });
        if (disposed) return;
        if (response?.data?.translation_locales) {
            config.translationLocales = response.data.translation_locales;
        }
        visible.value = false;
        notify(t('common.saved'));
        emit('saved');
    } catch (error) {
        if (!disposed) { errors.value = error.fields || {}; fail(error); }
    } finally { if (!disposed) busy.value = false; }
}
function discardSensitive() { securityPassword.value = ''; if (definition.value?.secret) value.value = blankValue(); }
watch(visible, next => { if (!next) discardSensitive(); }, { flush: 'sync' });
onBeforeUnmount(() => { disposed = true; controller?.abort(); discardSensitive(); });
defineExpose({ open });
</script>

<template>
    <EditorDialog v-model="visible" :title="t('system.setting.configure')" width="76vw" :busy="busy" :read-only="!changed || requiresSecurity && !securityConfigured" @save="save">
        <el-form v-if="definition" label-position="right" label-width="auto" class="horizontal-editor-form" :disabled="busy">
            <ValueField :model-value="value" :definition="definition" :field-type="fieldTypes[definition.input_type]" field="values.0" :errors="errors" :clear="clear" :disabled="busy" @update:model-value="updateValue" @update:clear="toggleClear" />
            <SecurityPasswordField v-if="requiresSecurity" v-model="securityPassword" :configured="securityConfigured" :setup-href="config.pages.profile" :errors="errors" :disabled="busy" />
        </el-form>
    </EditorDialog>
</template>
