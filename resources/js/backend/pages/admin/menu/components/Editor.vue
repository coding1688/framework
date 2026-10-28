<script setup>
import { menuApi } from '@/api/admin/menu';
import { computed, ref } from 'vue';
import { config } from '@/config';
import { t } from '@/locales';
import { state } from '@/store/modules/user';
import { date } from '@/utils/date';
import { useEditorDialog } from '@/composables/useEditorDialog';
import EditorDialog from '@/components/EditorDialog.vue';
import EditForm from '@/pages/admin/menu/components/EditForm.vue';
const props = defineProps({ rows: Array });
const emit = defineEmits(['saved']);
const { visible, busy, errors, editing, form, reset, submit } = useEditorDialog(menuApi, emit);
const system = computed(() => Boolean(editing.value?.is_system));
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
    return props.rows.filter(row => !excluded.has(row.id) && (form.type === 'button' ? row.type === 'page' : row.type !== 'button'))
        .map(row => ({ ...row, label: label(row) }));
});
function open(action, row, presets = {}) {
    reset(action, row, {
        name_i18n: Object.fromEntries(Object.keys(config.locales).map(locale => [locale, row?.name_i18n?.[locale] || ''])),
        type: presets.type ?? row?.type ?? 'page', permission: row?.permission || '', path: row?.path || '',
        icon: row?.icon || 'Folder', component: row?.component || '', parent_id: presets.parent_id ?? row?.parent_id ?? null, sort: row?.sort || 0, active: row?.active ?? true,
    });
    visible.value = true;
}
function save() {
    return submit(editing.value ? 'update' : 'create', {
        name_i18n: form.name_i18n, parent_id: form.parent_id, sort: form.sort, active: form.active, type: form.type,
        permission: form.permission, path: form.type === 'directory' ? null : form.path || null,
        icon: form.type === 'button' ? null : form.icon, component: form.type === 'page' ? form.component : null,
    });
}
defineExpose({ open });
</script>

<template>
<EditorDialog v-model="visible" :title="t(editing?'common.edit':'common.create')" :busy="busy" @save="save">
    <el-form label-position="right" label-width="144px" class="horizontal-editor-form" :disabled="busy" @submit.prevent="save">
        <EditForm :form="form" :errors="errors" :system="system" :parent-choices="parentChoices" />
        <button type="submit" class="sr-only" tabindex="-1">{{ t('common.save') }}</button>
    </el-form>
</EditorDialog>
</template>
