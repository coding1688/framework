<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { ElMessageBox } from 'element-plus';
import { Plus, Refresh } from '@element-plus/icons-vue';
import { settingGroupApi } from '@/api/system/settingGroup';
import { can } from '@/utils/permission';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import StatusTag from '@/components/StatusTag.vue';
import GroupEditor from './GroupEditor.vue';

const emit = defineEmits(['changed']);
const visible = ref(false), loading = ref(false), busy = ref('');
const groups = ref([]), groupsTree = ref([]), editor = ref();
let controller;
let disposed = false;
async function load() {
    if (disposed || !visible.value || !can('settingGroups', 'list')) return;
    controller?.abort();
    const current = new AbortController();
    controller = current;
    loading.value = true;
    try {
        const response = await settingGroupApi.list({ signal: current.signal });
        if (disposed || !visible.value || controller !== current) return;
        groups.value = response.data?.groups || [];
        groupsTree.value = response.data?.groups_tree || groups.value;
    } catch (error) { if (!disposed && visible.value && controller === current) fail(error); }
    finally { if (!disposed && controller === current) loading.value = false; }
}
function open() { visible.value = true; return load(); }
async function openEditor(row = null, parent = null) {
    if (busy.value || loading.value) return;
    busy.value = row ? 'edit' + row.id : 'create' + (parent?.id ?? '');
    try { await editor.value.open(row, parent); } catch (error) { fail(error); } finally { if (!disposed) busy.value = ''; }
}
async function saved() { emit('changed'); await load(); }
async function remove(row) {
    if (busy.value) return;
    busy.value = 'delete' + row.id;
    try {
        await ElMessageBox.confirm(t('development.setting.confirm_delete_group'), t('common.confirm'), { type: 'warning' });
        if (disposed || !visible.value) return;
        await settingGroupApi.delete({ id: row.id });
        if (disposed) return;
        notify(t('common.saved'));
        await saved();
    } catch (error) { if (!disposed && error !== 'cancel' && error !== 'close') fail(error); }
    finally { if (!disposed) busy.value = ''; }
}
watch(visible, next => { if (!next) { controller?.abort(); controller = null; loading.value = false; } });
onBeforeUnmount(() => { disposed = true; controller?.abort(); });
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t('development.setting.manage_groups')" width="82vw" :busy="!!busy">
        <div class="table-toolbar"><div class="table-toolbar__actions"><el-button v-if="can('settingGroups', 'create')" type="primary" :icon="Plus" :loading="busy === 'create'" :disabled="!!busy || loading" @click="openEditor()">{{ t('development.setting.create_group') }}</el-button><el-button :icon="Refresh" :loading="loading" :disabled="!!busy" @click="load">{{ t('common.refresh') }}</el-button></div></div>
        <el-table :data="groupsTree" v-loading="loading" border row-key="id" default-expand-all class="management-table" :tree-props="{ children: 'children' }">
            <el-table-column :label="t('common.name')" min-width="220"><template #default="{ row }"><span :title="(row.path || [row.name]).join(' / ')">{{ row.name }}</span></template></el-table-column>
            <el-table-column prop="code" :label="t('development.setting.group_code')" min-width="140" />
            <el-table-column :label="t('development.setting.description')" min-width="220" show-overflow-tooltip><template #default="{ row }">{{ row.description || '—' }}</template></el-table-column>
            <el-table-column :label="t('common.status')" width="110" align="center"><template #default="{ row }"><StatusTag :active="row.active" /></template></el-table-column>
            <el-table-column prop="sort" :label="t('common.sort')" width="80" align="center" />
            <el-table-column :label="t('common.actions')" width="270" align="center"><template #default="{ row }"><div class="table-actions">
                <el-button v-if="can('settingGroups', 'create')" link type="primary" :disabled="!!busy || loading" @click="openEditor(null, row)">{{ t('development.setting.add_child_group') }}</el-button>
                <el-button v-if="can('settingGroups', 'update') && can('settingGroups', 'detail')" link type="primary" :loading="busy === 'edit' + row.id" :disabled="!!busy || loading" @click="openEditor(row)">{{ t('common.edit') }}</el-button>
                <el-button v-if="can('settingGroups', 'delete')" link type="danger" :loading="busy === 'delete' + row.id" :disabled="!!busy || loading" @click="remove(row)">{{ t('common.delete') }}</el-button>
            </div></template></el-table-column>
        </el-table>
    </BaseDialog>
    <GroupEditor ref="editor" :groups="groups" :groups-tree="groupsTree" @saved="saved" />
</template>
