<script setup>
import { departmentApi } from '@/api/admin/department';

import { computed, ref } from 'vue';
import { config } from '@/config';
import { t } from '@/locales';
import { state } from '@/store/modules/user';
import { preferences } from '@/store/modules/settings';
import { useEditorDialog } from '@/composables/useEditorDialog';
import EditorDialog from '@/components/EditorDialog.vue';
import EditForm from '@/pages/admin/department/components/EditForm.vue';
import Employees from '@/pages/admin/department/components/Employees.vue';
import Permissions from '@/pages/admin/department/components/Permissions.vue';
import { withDefaultPermissionGroup } from '@/pages/admin/department/permissionDefaults';
const props = defineProps({ rows: Array });
const emit = defineEmits(['saved']);
const { visible, busy, errors, editing, mode, form, reset, submit } = useEditorDialog(departmentApi, emit);
const employees = ref([]), canSetLeaders = ref(false), permissionItems = ref([]);
const title = computed(() => t(mode.value === 'employees' ? 'admin.department.employee_management' : mode.value === 'permissions' ? 'admin.department.assign_permissions' : editing.value ? 'common.edit' : 'common.create'));
const readOnly = computed(() => mode.value === 'employees' && !canSetLeaders.value);
const parentChoices = computed(() => {
    const excluded = new Set(editing.value ? [editing.value.id] : []);
    for (let i = 0; i < props.rows.length; i++) props.rows.forEach(row => { if (excluded.has(row.parent_id)) excluded.add(row.id); });
    const byId = new Map(props.rows.map(row => [row.id, row]));
    function label(row) {
        const names = [row.name], seen = new Set([row.id]);
        let parent = byId.get(row.parent_id);
        while (parent && !seen.has(parent.id)) {
            names.unshift(parent.name);
            seen.add(parent.id);
            parent = byId.get(parent.parent_id);
        }
        return names.join(' / ');
    }
    return props.rows.filter(row => !excluded.has(row.id) && (state.user.is_board || row.id === state.user.department_id))
        .map(row => ({ ...row, label: label(row) }));
});
async function open(action, row, presets = {}) {
    let record = row ? JSON.parse(JSON.stringify(row)) : null;
    employees.value = []; canSetLeaders.value = false; permissionItems.value = [];
    if (action === 'permissions') {
        const options = await departmentApi.options();
        const parent = props.rows.find(item => item.id === row.parent_id);
        const allowed = new Set(parent?.effective_menu_ids || []);
        permissionItems.value = options.permissions.filter(item => allowed.has(item.id));
    }
    if (row && action === 'employees') {
        const result = await departmentApi.employees({ id: row.id });
        employees.value = result.data; canSetLeaders.value = result.can_set_leaders;
        record.leaders = result.data.filter(user => user.is_leader);
    }
    reset(action, record, {
        name_i18n: Object.fromEntries(Object.keys(config.locales).map(locale => [locale, record?.name_i18n?.[locale] || ''])),
        parent_id: presets.parent_id ?? record?.parent_id ?? state.user.department_id, sort: record?.sort || 0, active: record?.active ?? true,
        create_leader: true, leader: { email: '', name: '', password: '', locale: config.locale },
        leader_ids: record?.leaders?.map(user => user.id) || [], menu_ids: (record?.menus || []).map(menu => menu.id),
    });
    if (action === 'permissions') form.menu_ids = withDefaultPermissionGroup(permissionItems.value, form.menu_ids, 'service');
    visible.value = true;
}
function save() {
    if (readOnly.value) return;
    if (mode.value === 'employees') return submit('leaders', { leader_ids: form.leader_ids });
    if (mode.value === 'permissions') return submit('permissions', { menu_ids: form.menu_ids });
    const data = { name_i18n: form.name_i18n, parent_id: form.parent_id, sort: form.sort, active: form.active, menu_ids: editing.value?.menus?.map(menu => menu.id) || [] };
    if (editing.value) data.leader_ids = form.leader_ids;
    else { data.create_leader = form.create_leader; if (form.create_leader) data.leader = form.leader; else data.leader_ids = []; }
    return submit(editing.value ? 'update' : 'create', data);
}
defineExpose({ open });
</script>

<template>
<EditorDialog v-model="visible" :title="title" width="60%" :busy="busy" :read-only="readOnly" @save="save">
    <Employees v-if="mode==='employees'" :form="form" :errors="errors" :employees="employees" :can-set-leaders="canSetLeaders" :busy="busy" />
    <Permissions v-else-if="mode==='permissions'" :form="form" :errors="errors" :permission-items="permissionItems" />
    <el-form v-else label-position="right" label-width="144px" :size="preferences.density==='compact'?'small':'default'" class="horizontal-editor-form" :disabled="busy" @submit.prevent="save">
        <EditForm :form="form" :errors="errors" :editing="editing" :parent-choices="parentChoices" />
        <button type="submit" class="sr-only" tabindex="-1">{{ t('common.save') }}</button>
    </el-form>
</EditorDialog>
</template>
