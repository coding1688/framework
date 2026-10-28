<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { ElMessageBox } from 'element-plus';
import { Plus, Refresh, Search, Edit, Delete } from '@element-plus/icons-vue';
import { settingDefinitionApi } from '@/api/system/settingDefinition';
import { settingGroupApi } from '@/api/system/settingGroup';
import { can } from '@/utils/permission';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import StatusTag from '@/components/StatusTag.vue';
import Editor from './components/Editor.vue';
import GroupEditor from './components/GroupEditor.vue';

const rows = ref([]);
const options = ref({ groups: [], input_types: [] });
const loading = ref(false);
const optionsLoading = ref(false);
const busy = ref('');
const q = ref('');
const groupId = ref('');
const active = ref('');
const page = ref(1);
const total = ref(0);
const editor = ref(), groupEditor = ref();
function selectableTree(groups) { return groups.map(group => ({ ...group, disabled: false, children: selectableTree(group.children || []) })); }
const allGroupsKey = 'all-groups';
const groupsTree = computed(() => [
    { id: allGroupsKey, name: t('development.setting.all_groups'), children: [] },
    ...selectableTree(options.value.groups_tree?.length ? options.value.groups_tree : options.value.groups),
]);
const selectedGroup = computed(() => options.value.groups.find(group => group.id === groupId.value));
const currentGroupKey = computed(() => groupId.value || allGroupsKey);
let listController;
let optionsController;
let disposed = false;

async function load() {
    listController?.abort();
    const controller = new AbortController();
    listController = controller;
    loading.value = true;
    try {
        const response = await settingDefinitionApi.list({ query: { q: q.value, group_id: groupId.value, active: active.value, page: page.value }, signal: controller.signal });
        if (disposed || listController !== controller) return;
        rows.value = Array.isArray(response.data) ? response.data : [];
        total.value = response.total ?? rows.value.length;
    } catch (error) { if (!disposed && listController === controller) fail(error); }
    finally { if (!disposed && listController === controller) loading.value = false; }
}
async function loadOptions() {
    if (!can('settingDefinitions', 'options')) return;
    optionsController?.abort();
    const controller = new AbortController();
    optionsController = controller;
    optionsLoading.value = true;
    try {
        const response = await settingDefinitionApi.options({ signal: controller.signal });
        if (!disposed && optionsController === controller) {
            options.value = { groups: [], groups_tree: [], input_types: [], ...response.data };
            if (groupId.value && !options.value.groups.some(group => group.id === groupId.value)) { groupId.value = ''; page.value = 1; }
        }
    } catch (error) { if (!disposed && optionsController === controller) fail(error); }
    finally { if (!disposed && optionsController === controller) optionsLoading.value = false; }
}
async function groupsChanged() { await loadOptions(); await load(); }
function search() { page.value = 1; load(); }
function selectGroup(group) {
    const nextGroupId = group.id === allGroupsKey ? '' : group.id;
    if (nextGroupId === groupId.value) return;
    groupId.value = nextGroupId;
    search();
}
function reset() { q.value = ''; active.value = ''; search(); }
async function openEditor(row = null) {
    if (busy.value) return;
    busy.value = row ? 'edit' + row.id : 'create';
    try { await editor.value.open(row); } catch (error) { fail(error); } finally { busy.value = ''; }
}
async function openGroupEditor(row = null) {
    if (busy.value || optionsLoading.value) return;
    if (row ? !can('settingGroups', 'update') || !can('settingGroups', 'detail') : !can('settingGroups', 'create')) return;
    busy.value = row ? 'editGroup' + row.id : 'createGroup';
    try { await groupEditor.value.open(row, row ? null : selectedGroup.value || null); }
    catch (error) { if (!disposed) fail(error); }
    finally { if (!disposed) busy.value = ''; }
}
async function removeGroup(group) {
    if (busy.value || optionsLoading.value || group.id === allGroupsKey || !can('settingGroups', 'delete')) return;
    busy.value = 'deleteGroup' + group.id;
    try {
        await ElMessageBox.confirm(t('development.setting.confirm_delete_group'), t('common.confirm'), { type: 'warning' });
        if (disposed) return;
        await settingGroupApi.delete({ id: group.id });
        if (disposed) return;
        notify(t('common.saved'));
        await groupsChanged();
    } catch (error) { if (!disposed && error !== 'cancel' && error !== 'close') fail(error); }
    finally { if (!disposed) busy.value = ''; }
}
async function remove(row) {
    if (busy.value) return;
    busy.value = 'delete' + row.id;
    try {
        await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), { type: 'warning' });
        await settingDefinitionApi.delete({ id: row.id });
        notify(t('common.saved'));
        if (rows.value.length === 1 && page.value > 1) page.value--;
        await load();
    } catch (error) { if (error !== 'cancel' && error !== 'close') fail(error); }
    finally { busy.value = ''; }
}
onMounted(() => { load(); loadOptions(); });
onBeforeUnmount(() => { disposed = true; listController?.abort(); optionsController?.abort(); });
</script>

<template>
    <BaseCard class="management-card setting-definition-page" shadow="never">
        <div class="setting-definition-layout" :class="{ 'has-groups': can('settingDefinitions', 'options') }">
            <aside v-if="can('settingDefinitions', 'options')" class="setting-definition-groups" v-loading="optionsLoading" :aria-label="t('development.setting.group')">
                <div class="setting-definition-groups__heading"><el-button v-if="can('settingGroups', 'create')" class="setting-definition-groups__add" type="primary" plain :icon="Plus" :loading="busy === 'createGroup'" :disabled="!!busy || optionsLoading" @click="openGroupEditor()">{{ t('development.setting.create_group') }}</el-button><h2 v-else>{{ t('development.setting.group') }}</h2></div>
                <el-tree :data="groupsTree" node-key="id" :props="{ label: 'name', children: 'children' }" :current-node-key="currentGroupKey" :indent="12" default-expand-all highlight-current :expand-on-click-node="false" @node-click="selectGroup">
                    <template #default="{ data }">
                        <div class="setting-definition-group">
                            <span class="setting-definition-group-name" :class="{ 'is-inactive': data.effective_active === false }" :title="(data.path || [data.name]).join(' / ')">{{ data.name }}</span>
                            <span v-if="data.id !== allGroupsKey" class="setting-definition-group-actions" :class="{ 'is-busy': busy === 'editGroup' + data.id || busy === 'deleteGroup' + data.id }" @click.stop>
                                <el-button v-if="can('settingGroups', 'update') && can('settingGroups', 'detail')" link type="primary" :icon="Edit" :loading="busy === 'editGroup' + data.id" :disabled="!!busy || optionsLoading" :aria-label="`${t('common.edit')} ${data.name}`" :title="t('common.edit')" @click.stop="openGroupEditor(data)" />
                                <el-button v-if="can('settingGroups', 'delete')" link type="danger" :icon="Delete" :loading="busy === 'deleteGroup' + data.id" :disabled="!!busy || optionsLoading" :aria-label="`${t('common.delete')} ${data.name}`" :title="t('common.delete')" @click.stop="removeGroup(data)" />
                            </span>
                        </div>
                    </template>
                </el-tree>
            </aside>
            <section class="setting-definition-content">
                <div class="setting-definition-content__heading"><h2>{{ selectedGroup?.name || t('development.setting.all_groups') }}</h2></div>
                <div class="table-toolbar setting-definition-toolbar">
                    <div class="table-toolbar__actions"><el-button v-if="can('settingDefinitions', 'create') && can('settingDefinitions', 'options')" type="primary" :icon="Plus" :loading="busy === 'create' || optionsLoading" :disabled="!!busy || !options.groups.length" @click="openEditor()">{{ t('common.create') }}</el-button></div>
                    <el-form inline @submit.prevent="search">
                        <el-form-item><el-input v-model="q" clearable maxlength="128" :placeholder="t('common.search')" /></el-form-item>
                        <el-form-item><el-select v-model="active" clearable :placeholder="t('common.status')"><el-option :value="1" :label="t('common.active')" /><el-option :value="0" :label="t('common.inactive')" /></el-select></el-form-item>
                        <el-button type="primary" :icon="Search" :loading="loading" native-type="submit">{{ t('common.search') }}</el-button>
                        <el-button :icon="Refresh" :disabled="loading" @click="reset">{{ t('common.reset') }}</el-button>
                    </el-form>
                </div>
                <el-table :data="rows" v-loading="loading" border row-key="id" class="management-table">
                    <el-table-column :label="t('common.name')" min-width="200">
                        <template #default="{ row }">
                            <div class="setting-definition-name">
                                <el-tooltip :content="row.description" :disabled="!row.description" placement="top" :show-after="200" :popper-style="{ maxWidth: '360px', whiteSpace: 'pre-wrap', overflowWrap: 'anywhere' }">
                                    <strong>{{ row.name }}</strong>
                                </el-tooltip>
                                <small>{{ row.key }}</small>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column :label="t('development.setting.group')" width="160"><template #default="{ row }">{{ row.group_name || row.group?.name || '—' }}</template></el-table-column>
                    <el-table-column :label="t('development.setting.input_type')" width="140"><template #default="{ row }">{{ t(`development.setting.type_${row.input_type}`) }}</template></el-table-column>
                    <el-table-column :label="t('development.setting.secret')" width="110" align="center"><template #default="{ row }"><el-tag v-if="row.secret" type="info">{{ t('development.setting.secret') }}</el-tag><span v-else>—</span></template></el-table-column>
                    <el-table-column :label="t('common.status')" width="100"><template #default="{ row }"><StatusTag :active="row.active" /></template></el-table-column>
                    <el-table-column prop="sort" :label="t('common.sort')" width="90" />
                    <el-table-column :label="t('common.actions')" width="170" align="center"><template #default="{ row }"><div class="table-actions setting-definition-actions">
                        <el-button v-if="can('settingDefinitions', 'update') && can('settingDefinitions', 'detail') && can('settingDefinitions', 'options')" link type="primary" :loading="busy === 'edit' + row.id" :disabled="!!busy || optionsLoading" @click="openEditor(row)">{{ t('common.edit') }}</el-button>
                        <el-button v-if="can('settingDefinitions', 'delete')" link type="danger" :loading="busy === 'delete' + row.id" :disabled="!!busy" @click="remove(row)">{{ t('common.delete') }}</el-button>
                    </div></template></el-table-column>
                </el-table>
                <div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" @current-change="load" /></div>
            </section>
        </div>
    </BaseCard>
    <GroupEditor ref="groupEditor" :groups="options.groups" :groups-tree="options.groups_tree || []" @saved="groupsChanged" />
    <Editor ref="editor" :options="options" :group-id="groupId || null" @saved="load" />
</template>

<style scoped>
.setting-definition-layout { display: grid; grid-template-columns: minmax(0,1fr); min-height: 460px; }
.setting-definition-layout.has-groups { grid-template-columns: 200px minmax(0,1fr); }
.setting-definition-groups { min-width: 0; border-right: 1px solid var(--el-border-color-lighter); padding-right: 12px; }
.setting-definition-groups__heading h2, .setting-definition-content__heading h2 { margin: 0; font-size: 14px; font-weight: 600; line-height: 24px; }
.setting-definition-groups__heading, .setting-definition-content__heading { margin-bottom: 12px; }
.setting-definition-groups :deep(.el-tree-node__content) { height: 36px; border-radius: 4px; }
.setting-definition-group { display: flex; flex: 1; min-width: 0; align-items: center; gap: 4px; padding-right: 3px; }
.setting-definition-group-name { flex: 1; min-width: 0; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; font-size: 13px; }
.setting-definition-group-name.is-inactive { color: var(--el-text-color-placeholder); }
.setting-definition-groups__add { width: 100%; }
.setting-definition-group-actions { display: inline-flex; flex-shrink: 0; gap: 2px; opacity: 0; pointer-events: none; }
.setting-definition-group-actions :deep(.el-button) { width: 22px; height: 24px; padding: 0; margin: 0; }
.setting-definition-groups :deep(.el-tree-node__content:hover) .setting-definition-group-actions,
.setting-definition-groups :deep(.el-tree-node__content:focus-within) .setting-definition-group-actions,
.setting-definition-group-actions.is-busy { opacity: 1; pointer-events: auto; }
@media(hover:none) { .setting-definition-group-actions { opacity: 1; pointer-events: auto; } }
.setting-definition-content { min-width: 0; }
.has-groups .setting-definition-content { padding-left: 18px; }
.setting-definition-toolbar { flex-wrap: wrap; gap: 8px; }
.setting-definition-toolbar .el-form { margin-left: auto; }
.setting-definition-toolbar :deep(.el-input) { width: 176px; }
.setting-definition-toolbar :deep(.el-select) { width: 116px; }
@media(max-width:1200px) { .setting-definition-layout.has-groups { grid-template-columns: 176px minmax(0,1fr); } .has-groups .setting-definition-content { padding-left: 14px; } }

.setting-definition-name { display: grid; gap: 3px; line-height: 20px; }
.setting-definition-name strong { width: fit-content; max-width: 100%; font-weight: 500; }
.setting-definition-name small { color: var(--el-text-color-secondary); font-size: 12px; }
.setting-definition-actions { justify-content: center; }
</style>
