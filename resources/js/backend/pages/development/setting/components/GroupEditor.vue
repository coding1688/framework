<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { config } from '@/config';
import { state as identity } from '@/store/modules/user';
import { settingGroupApi } from '@/api/system/settingGroup';
import { useEditorDialog } from '@/composables/useEditorDialog';
import { SecurityPasswordField, securityPasswordRequirement } from '@/plugins/securityPassword';
import { t } from '@/locales';
import EditorDialog from '@/components/EditorDialog.vue';
import LocalizedField from '@/components/LocalizedField.vue';
import FormStatusField from '@/components/FormStatusField.vue';

const props = defineProps({ groups: { type: Array, default: () => [] }, groupsTree: { type: Array, default: () => [] } });
const emit = defineEmits(['saved']);
const { visible, busy, errors, editing, form, reset, submit } = useEditorDialog(settingGroupApi, emit);
const securityPassword = ref('');
const securityConfigured = computed(() => identity.user?.security_password_set === true);
const parentId = value => Number(value) || null;
const requiresSecurity = computed(() => !!securityPasswordRequirement(`development.settingGroup.${editing.value ? 'update' : 'create'}`, config.routes));
const blank = () => Object.fromEntries(Object.keys(config.locales).map(locale => [locale, '']));
function filterParents(groups) {
    return groups.filter(group => group.id !== editing.value?.id && !group.ancestor_ids?.includes(editing.value?.id))
        .map(group => ({ ...group, disabled: false, children: filterParents(group.children || []) }));
}
const parentTree = computed(() => filterParents(props.groupsTree.length ? props.groupsTree : props.groups));
async function open(row = null, parent = null) {
    const record = row ? (await settingGroupApi.detail({ id: row.id })).data : null;
    reset('edit', record, {
        parent_id: record ? record.parent_id : parent?.id ?? null,
        code: record?.code || '', name_i18n: { ...blank(), ...record?.name_i18n }, description_i18n: { ...blank(), ...record?.description_i18n },
        active: record?.active ?? true, sort: record?.sort ?? 0,
    });
    securityPassword.value = '';
    visible.value = true;
}
function save() {
    if (requiresSecurity.value && !securityConfigured.value) return;
    return submit(editing.value ? 'update' : 'create', { ...form, parent_id: parentId(form.parent_id), ...(requiresSecurity.value ? { security_password: securityPassword.value } : {}) });
}
watch([visible, requiresSecurity], ([isVisible, required]) => { if (!isVisible || !required) securityPassword.value = ''; }, { flush: 'sync' });
onBeforeUnmount(() => { securityPassword.value = ''; });
defineExpose({ open });
</script>

<template>
    <EditorDialog v-model="visible" :title="t(editing ? 'development.setting.edit_group' : 'development.setting.create_group')" width="76vw" :busy="busy" :read-only="requiresSecurity && !securityConfigured" @save="save">
        <el-form label-position="right" label-width="auto" class="horizontal-editor-form" :disabled="busy">
            <div class="field-grid">
                <el-form-item :label="t('development.setting.parent_group')" :error="errors.parent_id?.[0]"><el-tree-select v-model="form.parent_id" :data="parentTree" node-key="id" :props="{ label: 'name', children: 'children' }" clearable check-strictly default-expand-all :render-after-expand="false" :placeholder="t('development.setting.root_group')" /></el-form-item>
                <el-form-item :label="t('development.setting.group_code')" required :error="errors.code?.[0]"><el-input v-model="form.code" maxlength="64" :placeholder="t('development.setting.group_code_placeholder')" /></el-form-item>
            </div>
            <LocalizedField v-model="form.name_i18n" field="name_i18n" :label="t('common.name')" :errors="errors" :maxlength="128" required />
            <LocalizedField v-model="form.description_i18n" field="description_i18n" :label="t('development.setting.description')" :errors="errors" type="textarea" :maxlength="512" />
            <div class="field-grid"><FormStatusField v-model="form.active" /><el-form-item :label="t('common.sort')"><el-input-number v-model="form.sort" :min="0" :max="99999" :precision="0" /></el-form-item></div>
            <SecurityPasswordField v-if="requiresSecurity" v-model="securityPassword" :configured="securityConfigured" :setup-href="config.pages.profile" :errors="errors" :disabled="busy" />
        </el-form>
    </EditorDialog>
</template>
