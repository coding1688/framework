<script setup>
import { menuApi } from '@/api/admin/menu';

import BaseAlert from '@/components/ui/BaseAlert.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import { computed, ref, reactive, watch, onMounted, onUnmounted } from 'vue';
import { Plus, Search, Refresh } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import { t } from '@/locales';
import { state } from '@/store/modules/user';
import { can } from '@/utils/permission';
import { notify, fail } from '@/utils/feedback';
import { flatten } from '@/utils/navigation';
import Editor from '@/pages/admin/menu/components/Editor.vue';

const props = defineProps({ revision: Number });
const emit = defineEmits(['identity-changed']);
const rows = ref([]), loading = ref(false), error = ref(''), busy = ref(''), editor = ref(null);
const filters = reactive({ q: '', active: '' });
const mayManage = computed(() => state.user?.is_board);
const expanded = ref([]);
let initializedExpansion = false;
const filtering = computed(() => Boolean(filters.q.trim()) || (filters.active !== '' && filters.active != null));
const treeRows = computed(() => {
    const nodes = new Map(rows.value.map(row => [row.id, { ...row, children: [] }]));
    const roots = [];
    for (const node of nodes.values()) {
        const parent = nodes.get(node.parent_id);
        if (parent && parent !== node) parent.children.push(node);
        else roots.push(node);
    }
    const query = filters.q.trim().toLowerCase();
    function filterTree(nodes) {
        return nodes.flatMap(node => {
            const children = filterTree(node.children);
            const matches = (!query || node.name.toLowerCase().includes(query) || node.permission?.toLowerCase().includes(query)) &&
                (filters.active === '' || filters.active == null || Number(node.active) === Number(filters.active));
            return matches || children.length ? [{ ...node, children }] : [];
        });
    }
    return filterTree(roots);
});
const displayedRows = computed(() => flatten(treeRows.value));
const expandedKeys = computed(() => filtering.value ? displayedRows.value.filter(row => row.children.length).map(row => String(row.id)) : expanded.value);
function expandRow(row, open) {
    if (open) expanded.value = [...new Set([...expanded.value, String(row.id)])];
    else expanded.value = expanded.value.filter(id => id !== String(row.id));
}
function expandAll() { expanded.value = rows.value.filter(row => row.parent_id === null && row.type !== 'button').map(row => String(row.id)); }
function collapseAll() { expanded.value = []; }
function parentName(row) { return rows.value.find(item => item.id === row.parent_id)?.name || t('common.none'); }
let requestId = 0, controller;
async function load() {
    const id = ++requestId;
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    error.value = '';
    try {
        const result = await menuApi.list({ signal: controller.signal });
        if (id !== requestId) return;
        rows.value = result.data;
        if (!initializedExpansion) {
            expanded.value = rows.value.filter(row => row.parent_id === null && row.type !== 'button').map(row => String(row.id));
            initializedExpansion = true;
        } else {
            const ids = new Set(rows.value.map(row => String(row.id)));
            expanded.value = expanded.value.filter(id => ids.has(id));
        }
    } catch (exception) {
        if (id === requestId && exception.name !== 'AbortError' && !exception.redirected) error.value = exception.message;
    } finally { if (id === requestId) loading.value = false; }
}
function reset() { Object.keys(filters).forEach(key => { filters[key] = ''; }); }
async function openEditor(action, row = null, presets = {}) {
    if (busy.value) return;
    busy.value = row ? action + row.id : 'create';
    try { await editor.value.open(action, row, presets); } catch (exception) { fail(exception); } finally { busy.value = ''; }
}
async function changeStatus(row, active) {
    if (busy.value) return;
    busy.value = 'status' + row.id;
    try {
        await menuApi.update({ id: row.id, data: {
            name_i18n: row.name_i18n, parent_id: row.parent_id, sort: row.sort, active, type: row.type,
            permission: row.permission, path: row.type === 'directory' ? null : row.path || null,
            icon: row.type === 'button' ? null : row.icon, component: row.type === 'page' ? row.component : null,
        } });
        const record = rows.value.find(item => item.id === row.id);
        if (record) record.active = active;
        notify(t('common.saved'));
        emit('identity-changed');
    } catch (exception) { fail(exception); }
    finally { busy.value = ''; }
}
async function removeRow(row) {
    if (busy.value) return;
    busy.value = 'delete' + row.id;
    try {
        await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), {
            type: 'warning', confirmButtonText: t('common.delete'), cancelButtonText: t('common.cancel'),
            closeOnClickModal: false, customClass: 'delete-confirm',
            beforeClose: async (action, instance, done) => {
                if (action !== 'confirm') { done(); return; }
                if (instance.confirmButtonLoading) return;
                instance.confirmButtonLoading = true;
                try { await menuApi.delete({ id: row.id }); done(); }
                catch (exception) { fail(exception); }
                finally { instance.confirmButtonLoading = false; }
            },
        });
        notify(t('common.saved'));
        saved();
    } catch (exception) { if (exception !== 'cancel' && exception !== 'close') fail(exception); }
    finally { busy.value = ''; }
}
async function syncMenus(row = null) {
    if (busy.value) return;
    busy.value = 'sync' + (row?.id || '');
    try {
        const result = await menuApi.sync({ data: row ? { permission: row.permission } : {} });
        notify(`${t('admin.menu.sync_created')}: ${result.data.created} / ${t('admin.menu.sync_updated')}: ${result.data.updated}`);
        await load();
        emit('identity-changed');
    } catch (exception) { fail(exception); }
    finally { busy.value = ''; }
}
function saved() { load(); emit('identity-changed'); }
watch(() => props.revision, load);
onMounted(load);
onUnmounted(() => { requestId++; controller?.abort(); });
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar">
            <div class="table-toolbar__actions">
                <el-dropdown v-if="mayManage && can('menus', 'create')" :disabled="!!busy" @command="type => openEditor('edit', null, {type})">
                    <el-button type="primary" :icon="Plus" :loading="busy==='create'" :disabled="!!busy">{{ t('common.create') }}</el-button>
                    <template #dropdown><el-dropdown-menu><el-dropdown-item v-for="type in ['directory', 'page', 'button']" :key="type" :command="type">{{ t(type) }}</el-dropdown-item></el-dropdown-menu></template>
                </el-dropdown>
                <el-button v-if="mayManage" :icon="Refresh" :loading="busy==='sync'" :disabled="!!busy" @click="syncMenus()">{{ t('admin.menu.sync') }}</el-button><el-button :disabled="filtering" @click="expandAll">{{ t('admin.menu.expand_all') }}</el-button><el-button :disabled="filtering" @click="collapseAll">{{ t('admin.menu.collapse_all') }}</el-button>
            </div>
            <el-form inline @submit.prevent>
                <el-form-item><el-input v-model="filters.q" :prefix-icon="Search" :placeholder="t('common.search')" :aria-label="t('common.search')" maxlength="128" clearable /></el-form-item>
                <el-form-item><el-select v-model="filters.active" :placeholder="t('common.all')" clearable :aria-label="t('common.status')"><el-option :label="t('common.active')" :value="1"/><el-option :label="t('common.inactive')" :value="0" /></el-select></el-form-item>
                <el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button>
            </el-form>
        </div>
        <BaseAlert v-if="error" :title="error" type="error" :closable="false" />
        <el-table :data="treeRows" :tree-props="{children: 'children'}" :expand-row-keys="expandedKeys" :indent="20" @expand-change="expandRow" v-loading="loading" row-key="id" border :empty-text="t('common.empty')" class="management-table">
            <el-table-column :label="t('common.name')" min-width="220"><template #default="{row}"><span>{{ row.name }}</span></template></el-table-column>
            <el-table-column :label="t('common.parent')" min-width="130"><template #default="{row}">{{ parentName(row) }}</template></el-table-column>
            <el-table-column :label="t('admin.menu.type')" width="100" align="center"><template #default="{row}"><el-tag size="small" :type="{directory:'warning',page:'primary',button:'info'}[row.type] || 'info'">{{ t(row.type) }}</el-tag></template></el-table-column>
            <el-table-column prop="permission" :label="t('admin.menu.permission')" min-width="200"/><el-table-column prop="sort" :label="t('common.sort')" width="90" align="center"/>
            <el-table-column :label="t('common.status')" width="100" align="center"><template #default="{row}"><el-switch :model-value="row.active" :loading="busy==='status'+row.id" :disabled="!mayManage || !can('menus','update') || !!busy" :aria-label="t('common.status')" @change="active => changeStatus(row, active)" /></template></el-table-column>
            <el-table-column :label="t('common.actions')" fixed="right" min-width="250"><template #default="{row}"><div class="table-actions">
                <template v-if="mayManage">
                <el-button v-if="row.type==='page'" link type="primary" :icon="Refresh" :disabled="!!busy" :loading="busy==='sync'+row.id" @click="syncMenus(row)">{{ t('admin.menu.sync_page') }}</el-button>
                <el-button v-if="can('menus','create') && row.type!=='button'" link type="primary" :disabled="!!busy" @click="openEditor('edit', null, {parent_id: row.id, type: row.type==='page'?'button':'page'})">{{ t('common.create') }}</el-button>
                <el-button v-if="can('menus','update')" link type="primary" :disabled="!!busy" :loading="busy==='edit'+row.id" @click="openEditor('edit',row)">{{ t('common.edit') }}</el-button>
                <el-button v-if="can('menus','delete') && !row.is_system" link type="danger" :disabled="!!busy" :loading="busy==='delete'+row.id" @click="removeRow(row)">{{ t('common.delete') }}</el-button>
                </template>
            </div></template></el-table-column>
        </el-table>
        <div class="table-pagination">
            <span>{{ displayedRows.length }} {{ t('common.total') }}</span>
        </div>
    </BaseCard>
    <Editor ref="editor" :rows="rows" @saved="saved" />
</template>
