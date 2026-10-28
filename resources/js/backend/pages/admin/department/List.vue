<script setup>
import { departmentApi } from '@/api/admin/department';

import BaseAlert from '@/components/ui/BaseAlert.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import { computed, ref, reactive, watch, onMounted, onUnmounted } from 'vue';
import { Plus, Search, Refresh } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import { t } from '@/locales';
import { state } from '@/store/modules/user';
import { config } from '@/config';
import { can } from '@/utils/permission';
import { notify, fail } from '@/utils/feedback';
import { flatten } from '@/utils/navigation';
import { SecurityPasswordDialog } from '@/plugins/securityPassword';
import Editor from '@/pages/admin/department/components/Editor.vue';

const props = defineProps({ revision: Number });
const emit = defineEmits(['identity-changed']);
const rows = ref([]), loading = ref(false), error = ref(''), busy = ref(''), editor = ref(null), securityDialog = ref(null);
const filters = reactive({ q: '', active: '' });
const mayManage = computed(() => state.user?.is_board || state.user?.is_leader);
const securityConfigured = computed(() => state.user?.security_password_set === true);
function editable(row) { return mayManage.value && row.can_manage; }
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
            const matches = (!query || node.name.toLowerCase().includes(query) || node.code?.toLowerCase().includes(query)) &&
                (filters.active === '' || filters.active == null || Number(node.active) === Number(filters.active));
            return matches || children.length ? [{ ...node, children }] : [];
        });
    }
    return filterTree(roots);
});
const displayedRows = computed(() => flatten(treeRows.value));
const expanded = ref([]);
let initializedExpansion = false;
const expandedKeys = computed(() => filtering.value ? displayedRows.value.filter(row => row.children.length).map(row => String(row.id)) : expanded.value);
function expandRow(row, open) {
    if (open) expanded.value = [...new Set([...expanded.value, String(row.id)])];
    else expanded.value = expanded.value.filter(id => id !== String(row.id));
}
function expandAll() { expanded.value = rows.value.filter(row => row.parent_id === null).map(row => String(row.id)); }
function collapseAll() { expanded.value = []; }
function canCreateChild(row) { return mayManage.value && can('departments', 'create') && (state.user?.is_board || row.id === state.user?.department_id); }
function parentName(row) { return rows.value.find(item => item.id === row.parent_id)?.name || t('common.none'); }
let requestId = 0, controller;
async function load() {
    const id = ++requestId;
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    error.value = '';
    try {
        const result = await departmentApi.list({ signal: controller.signal });
        if (id !== requestId) return;
        rows.value = result.data;
        if (!initializedExpansion) {
            expandAll();
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
        const result = await securityDialog.value.open((security_password, { signal }) => departmentApi.status({ id: row.id, data: { active, security_password }, signal }));
        if (!result.confirmed) return;
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
                try { await departmentApi.delete({ id: row.id }); done(); }
                catch (exception) { fail(exception); }
                finally { instance.confirmButtonLoading = false; }
            },
        });
        notify(t('common.saved'));
        saved();
    } catch (exception) { if (exception !== 'cancel' && exception !== 'close') fail(exception); }
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
                <el-button v-if="mayManage && can('departments', 'create')" type="primary" :icon="Plus" :loading="busy==='create'" :disabled="!!busy" @click="openEditor('edit')">{{ t('common.create') }}</el-button>
                <el-button :disabled="filtering" @click="expandAll">{{ t('admin.menu.expand_all') }}</el-button><el-button :disabled="filtering" @click="collapseAll">{{ t('admin.menu.collapse_all') }}</el-button>
            </div>
            <el-form inline @submit.prevent>
                <el-form-item><el-input v-model="filters.q" :prefix-icon="Search" :placeholder="t('common.search')" :aria-label="t('common.search')" maxlength="128" clearable /></el-form-item>
                <el-form-item><el-select v-model="filters.active" :placeholder="t('common.all')" clearable :aria-label="t('common.status')"><el-option :label="t('common.active')" :value="1"/><el-option :label="t('common.inactive')" :value="0" /></el-select></el-form-item>
                <el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button>
            </el-form>
        </div>
        <BaseAlert v-if="error" :title="error" type="error" :closable="false" />
        <el-table :data="treeRows" :tree-props="{children: 'children'}" :expand-row-keys="expandedKeys" :indent="20" @expand-change="expandRow" v-loading="loading" row-key="id" border :empty-text="t('common.empty')" class="management-table">
            <el-table-column :label="t('common.name')" min-width="220"><template #default="{row}">{{ row.name }}</template></el-table-column>
            <el-table-column :label="t('common.parent')" min-width="130"><template #default="{row}">{{ parentName(row) }}</template></el-table-column>
            <el-table-column :label="t('admin.department.leaders')" min-width="140"><template #default="{row}">{{ row.code==='board'?t('admin.department.board'):(row.leaders||[]).map(item=>item.name).join(' / ')||'—' }}</template></el-table-column>
            <el-table-column :label="t('admin.department.assign_permissions')" width="115" align="center"><template #default="{row}">{{ row.code==='board'?t('permission.all_permissions'):row.effective_menu_ids?.length }}</template></el-table-column>
            <el-table-column :label="t('common.status')" width="100" align="center"><template #default="{row}"><el-switch :model-value="row.active" :loading="busy==='status'+row.id" :disabled="!editable(row) || !can('departments','status') || !!busy" :aria-label="t('common.status')" @change="active => changeStatus(row, active)" /></template></el-table-column>
            <el-table-column :label="t('common.actions')" min-width="360"><template #default="{row}"><div class="table-actions">
                <el-button v-if="mayManage && can('departments','employees')" link type="primary" :disabled="!!busy" :loading="busy==='employees'+row.id" @click="openEditor('employees',row)">{{ t('admin.department.employee_management') }}</el-button>
                <el-button v-if="canCreateChild(row)" link type="primary" :disabled="!!busy" @click="openEditor('edit', null, {parent_id: row.id})">{{ t('common.create') }}</el-button>
                <template v-if="editable(row)">
                <el-button v-if="can('departments','permissions') && can('departments','options')" link type="primary" :disabled="!!busy" :loading="busy==='permissions'+row.id" @click="openEditor('permissions',row)">{{ t('admin.department.assign_permissions') }}</el-button>
                <el-button v-if="can('departments','update')" link type="primary" :disabled="!!busy" :loading="busy==='edit'+row.id" @click="openEditor('edit',row)">{{ t('common.edit') }}</el-button>
                <el-button v-if="can('departments','delete')" link type="danger" :disabled="!!busy" :loading="busy==='delete'+row.id" @click="removeRow(row)">{{ t('common.delete') }}</el-button>
                </template>
            </div></template></el-table-column>
        </el-table>
        <div class="table-pagination">
            <span>{{ displayedRows.length }} {{ t('common.total') }}</span>
        </div>
    </BaseCard>
    <Editor ref="editor" :rows="rows" @saved="saved" />
    <SecurityPasswordDialog ref="securityDialog" :configured="securityConfigured" :setup-href="config.pages.profile" />
</template>
