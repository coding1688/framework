<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Refresh } from '@element-plus/icons-vue';
import { config } from '@/config';
import { state as identity } from '@/store/modules/user';
import { settingApi } from '@/api/system/setting';
import { can } from '@/utils/permission';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';
import BaseCard from '@/components/ui/BaseCard.vue';
import { SecurityPasswordDialog, securityPasswordRequirement } from '@/plugins/securityPassword';
import ValueEditor from './components/ValueEditor.vue';

const options = ref({ groups: [], groups_tree: [], field_types: [] });
const groupId = ref(null), definitions = ref([]), loading = ref(false), optionsLoading = ref(false), statusBusy = ref(null);
const editor = ref(), securityDialog = ref();
const selectedGroup = computed(() => options.value.groups.find(group => group.id === groupId.value));
const fieldTypes = computed(() => Object.fromEntries(options.value.field_types.map(fieldType => [fieldType.key, fieldType])));
const securityConfigured = computed(() => identity.user?.security_password_set === true);
const canUpdate = computed(() => can('settings', 'update'));
const statusRequiresSecurity = computed(() => !!securityPasswordRequirement('system.setting.status', config.routes));
const groupsTree = computed(() => options.value.groups_tree.length ? options.value.groups_tree : options.value.groups);
let listController, optionsController;
let disposed = false;
function readGroupPreference() { try { return window.sessionStorage?.getItem('backend:settings:group') || ''; } catch { return ''; } }
function rememberGroup(code) { try { window.sessionStorage?.setItem('backend:settings:group', code); } catch { /* Storage may be unavailable. */ } }
function groupDisabled(group) { return group.disabled === true || group.effective_active === false || group.active === false; }
function currentValue(definition) {
    if (definition.secret) return (definition.has_effective_value ?? definition.has_value) ? t('system.setting.secret_configured') : t('system.setting.not_configured');
    const value = definition.value ?? definition.default_value ?? {};
    const metadata = fieldTypes.value[definition.input_type];
    const dataType = definition.multiple ? metadata?.multiple_data_type : metadata?.data_type;
    if (dataType === 'localized_text') return definition.display_value || '—';
    if (dataType === 'boolean') return value.value_boolean == null ? '—' : t(value.value_boolean ? 'system.setting.yes' : 'system.setting.no');
    if (dataType === 'number') return value.value_number ?? '—';
    if (dataType === 'multi_select') return Array.isArray(value.value_json) && value.value_json.length ? value.value_json.map(key => definition.options?.[key] ?? key).join(', ') : '—';
    if (dataType === 'select' || definition.options?.[value.value_string] !== undefined) return definition.options?.[value.value_string] ?? value.value_string ?? '—';
    return value.value_string === '' || value.value_string == null ? '—' : value.value_string;
}
function booleanValue(definition) {
    const metadata = fieldTypes.value[definition.input_type];
    return (definition.multiple ? metadata?.multiple_data_type : metadata?.data_type) === 'boolean' && (definition.value ?? definition.default_value)?.value_boolean != null;
}
async function load() {
    if (!groupId.value || disposed) return;
    listController?.abort();
    const controller = new AbortController();
    listController = controller;
    loading.value = true;
    definitions.value = [];
    try {
        const response = await settingApi.list({ query: { group_id: groupId.value }, signal: controller.signal });
        if (disposed || listController !== controller) return;
        const data = response.data || response;
        definitions.value = Array.isArray(data.definitions) ? data.definitions : [];
    } catch (error) { if (!disposed && listController === controller) fail(error); }
    finally { if (!disposed && listController === controller) loading.value = false; }
}
async function loadOptions() {
    if (!can('settings', 'options')) return;
    optionsController?.abort();
    const controller = new AbortController();
    optionsController = controller;
    optionsLoading.value = true;
    try {
        const response = await settingApi.options({ signal: controller.signal });
        if (disposed || optionsController !== controller) return;
        options.value = { groups: [], groups_tree: [], field_types: [], ...response.data };
        const available = options.value.groups.filter(group => !groupDisabled(group));
        groupId.value = available.find(group => group.code === readGroupPreference())?.id ?? available[0]?.id ?? null;
        if (groupId.value) await load();
    } catch (error) { if (!disposed && optionsController === controller) fail(error); }
    finally { if (!disposed && optionsController === controller) optionsLoading.value = false; }
}
async function selectGroup(group) {
    if (statusBusy.value || groupDisabled(group) || group.id === groupId.value) return;
    groupId.value = group.id;
    rememberGroup(group.code);
    await load();
}
function openEditor(row) { if (!loading.value && row.active && canUpdate.value) editor.value?.open(row, groupId.value); }
async function toggleStatus(row) {
    if (statusBusy.value || loading.value || !can('settings', 'status') || statusRequiresSecurity.value && !securityDialog.value) return false;
    const nextActive = !row.active;
    const selectedId = groupId.value;
    statusBusy.value = row.id;
    try {
        const confirmation = statusRequiresSecurity.value
            ? await securityDialog.value.open((password, context) => settingApi.status({ id: row.id, data: { active: nextActive, security_password: password }, signal: context?.signal }), {
                title: t('system.setting.change_status'), description: t('system.setting.status_confirmation').replace(':name', row.name),
            })
            : { confirmed: true, result: await settingApi.status({ id: row.id, data: { active: nextActive } }) };
        if (!disposed && confirmation.confirmed && groupId.value === selectedId && definitions.value.includes(row)) {
            if (confirmation.result?.data?.translation_locales) {
                config.translationLocales = confirmation.result.data.translation_locales;
            }
            row.active = nextActive;
            notify(t('common.saved'));
        }
    } catch (error) { if (!disposed) fail(error); }
    finally { if (!disposed) statusBusy.value = null; }
    return false;
}
onMounted(loadOptions);
onBeforeUnmount(() => { disposed = true; listController?.abort(); optionsController?.abort(); });
</script>

<template>
    <BaseCard class="management-card setting-page" shadow="never">
        <div class="setting-layout" v-loading="optionsLoading">
            <aside class="setting-groups" :aria-label="t('system.setting.groups')">
                <div class="setting-groups__heading"><strong>{{ t('system.setting.groups') }}</strong><span>{{ options.groups.length }}</span></div>
                <div class="setting-groups__tree">
                    <el-tree :data="groupsTree" node-key="id" :props="{ label: 'name', children: 'children', disabled: 'disabled' }" :current-node-key="groupId" :indent="12" default-expand-all highlight-current :expand-on-click-node="false" @node-click="selectGroup">
                        <template #default="{ data }"><span class="setting-group-name" :class="{ 'is-disabled': groupDisabled(data) }" :title="(data.path || [data.name]).join(' / ')">{{ data.name }}</span></template>
                    </el-tree>
                </div>
            </aside>
            <section class="setting-content">
                <div class="setting-content__heading">
                    <div class="setting-content__title"><small>{{ (selectedGroup?.path || [t('system.setting.groups')]).join(' / ') }}</small><h2>{{ selectedGroup?.name || t('system.setting.title') }}</h2></div>
                    <div class="setting-content__actions"><span>{{ definitions.length }} {{ t('common.total') }}</span><el-button :icon="Refresh" :loading="loading" :disabled="!!statusBusy || !groupId" @click="load">{{ t('common.refresh') }}</el-button></div>
                </div>
                <div class="setting-table-wrap">
                    <el-table :data="definitions" v-loading="loading" row-key="id" class="management-table setting-table">
                        <el-table-column :label="t('common.name')" min-width="220"><template #default="{ row }"><div class="setting-name"><strong>{{ row.name }}</strong><small>{{ row.key }}</small></div></template></el-table-column>
                        <el-table-column :label="t('system.setting.current_value')" min-width="260" show-overflow-tooltip><template #default="{ row }"><el-tag v-if="row.secret" type="info" effect="plain">{{ currentValue(row) }}</el-tag><el-tag v-else-if="booleanValue(row)" :type="(row.value ?? row.default_value)?.value_boolean ? 'success' : 'info'" effect="plain">{{ currentValue(row) }}</el-tag><span v-else class="setting-value">{{ currentValue(row) }}</span></template></el-table-column>
                        <el-table-column :label="t('development.setting.input_type')" width="150"><template #default="{ row }"><span class="setting-type">{{ t(`development.setting.type_${row.input_type}`) }}</span></template></el-table-column>
                        <el-table-column :label="t('common.status')" width="110" align="center"><template #default="{ row }"><el-switch :model-value="row.active" :before-change="() => toggleStatus(row)" :loading="statusBusy === row.id" :disabled="!can('settings', 'status') || !!statusBusy || loading" :aria-label="t('system.setting.change_status')" /></template></el-table-column>
                        <el-table-column :label="t('common.actions')" width="120" align="center"><template #default="{ row }"><el-button v-if="canUpdate" link type="primary" :disabled="!row.active || loading || !!statusBusy" @click="openEditor(row)">{{ t('system.setting.configure') }}</el-button></template></el-table-column>
                        <template #empty><el-empty :description="t('system.setting.empty')" /></template>
                    </el-table>
                </div>
            </section>
        </div>
    </BaseCard>
    <ValueEditor ref="editor" :field-types="fieldTypes" @saved="load" />
    <SecurityPasswordDialog ref="securityDialog" :configured="securityConfigured" :setup-href="config.pages.profile" />
</template>

<style scoped>
.setting-page { overflow: hidden; border-color: #e2e9e5; }
.setting-page>.el-card__body { padding: 0; }
.setting-layout { display: grid; grid-template-columns: 220px minmax(0,1fr); min-height: 560px; }
.setting-groups { min-width: 0; background: #f6f8f7; border-right: 1px solid #e3e9e5; }
.setting-groups__heading { display: flex; align-items: center; justify-content: space-between; height: 64px; padding: 0 18px; border-bottom: 1px solid #e3e9e5; color: #33473d; }
.setting-groups__heading strong { font-size: 14px; font-weight: 650; }
.setting-groups__heading span { min-width: 24px; padding: 1px 7px; border-radius: 10px; background: #e7eeea; color: #708078; font-size: 12px; text-align: center; font-variant-numeric: tabular-nums; }
.setting-groups__tree { padding: 12px 9px 16px; overflow: auto; }
.setting-groups :deep(.el-tree) { background: transparent; }
.setting-groups :deep(.el-tree-node__content) { height: 40px; margin-bottom: 3px; border-radius: 6px; color: #53665c; transition: background-color .15s,color .15s; }
.setting-groups :deep(.el-tree-node__content:hover) { background: #ebf1ed; }
.setting-groups :deep(.el-tree-node.is-current > .el-tree-node__content) { background: #dfeee5; color: #147553; font-weight: 600; }
.setting-groups :deep(.el-tree-node__expand-icon) { color: #8a9991; }
.setting-group-name { min-width: 0; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; font-size: 13px; }
.setting-group-name.is-disabled { color: #a6b0aa; cursor: not-allowed; }
.setting-content { min-width: 0; padding: 24px; background: #fff; }
.setting-content__heading { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 22px; }
.setting-content__title { min-width: 0; }
.setting-content__title small { display: block; margin-bottom: 5px; overflow: hidden; color: #8a9790; font-size: 12px; line-height: 18px; text-overflow: ellipsis; white-space: nowrap; }
.setting-content__heading h2 { margin: 0; color: #263b31; font-size: 20px; font-weight: 650; line-height: 28px; }
.setting-content__actions { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }
.setting-content__actions>span { color: #8a9790; font-size: 12px; white-space: nowrap; font-variant-numeric: tabular-nums; }
.setting-table-wrap { overflow: hidden; border: 1px solid #e4eae6; border-radius: 8px; }
.setting-table { min-height: 0; --el-table-border-color: #edf1ee; --el-table-header-bg-color: #f7f9f8; --el-table-row-hover-bg-color: #f8fbf9; }
.setting-table :deep(th.el-table__cell) { height: 44px; padding: 8px 0; background: #f7f9f8; color: #77877f; font-size: 12px; font-weight: 600; }
.setting-table :deep(td.el-table__cell) { padding: 8px 0; }
.setting-table :deep(.el-table__inner-wrapper::before) { display: none; }
.setting-name { display: grid; gap: 3px; line-height: 20px; }
.setting-name strong { color: #30463b; font-size: 13px; font-weight: 600; }
.setting-name small { color: #91a097; font-family: ui-monospace,SFMono-Regular,Consolas,monospace; font-size: 11px; }
.setting-value { display: block; overflow: hidden; color: #465b50; line-height: 22px; text-overflow: ellipsis; white-space: nowrap; }
.setting-type { color: #718078; font-size: 12px; }
@media(max-width:900px) { .setting-layout { grid-template-columns: 190px minmax(0,1fr); } .setting-content { padding: 18px; } }
@media(max-width:760px) { .setting-layout { grid-template-columns: minmax(0,1fr); } .setting-groups { border-right: 0; border-bottom: 1px solid #e3e9e5; } .setting-groups__tree { max-height: 220px; } .setting-content { padding: 16px; } .setting-content__heading { align-items: flex-start; } .setting-content__actions { gap: 8px; } .setting-content__actions>span { display: none; } }
</style>
