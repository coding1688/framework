<script setup>
import { userApi } from '@/api/admin/user';

import { computed, ref } from 'vue';
import { config } from '@/config';
import { t } from '@/locales';
import { state } from '@/store/modules/user';
import { date } from '@/utils/date';
import { useEditorDialog } from '@/composables/useEditorDialog';
import EditorDialog from '@/components/EditorDialog.vue';
import EditForm from '@/pages/admin/user/components/EditForm.vue';
import PasswordForm from '@/pages/admin/user/components/PasswordForm.vue';
import Detail from '@/pages/admin/user/components/Detail.vue';
const emit = defineEmits(['saved']);
const { visible, busy, errors, editing, mode, form, reset, submit } = useEditorDialog(userApi, emit);
const options = ref({});
const title = computed(() => t(mode.value === 'detail' ? 'common.details' : mode.value === 'password' ? 'form.reset_password' : editing.value ? 'common.edit' : 'common.create'));
const detailFields=computed(()=>{const row=editing.value||{};return [['email',row.email],['nickname',row.name],['department',row.department?.name],['status',row.active],['created_ip',row.created_ip],['created_time',date(row.created_at)],['created_by',row.created_by_name],['last_login',date(row.last_login_at)],['login_ip_location',row.last_login_ip?row.last_login_ip+' ('+(row.last_login_location||'—')+')':null],['login_device',row.last_login_device],['password_changed_at',date(row.password_changed_at)],['password_changed_ip',row.password_changed_ip]];});
async function open(action, row) {
    let record = row;
    options.value = {};
    if (action === 'detail') record = (await userApi.detail({ id: row.id })).data;
    if (action === 'edit' && !row) options.value = await userApi.options();
    reset(action, record, { email: record?.email || '', name: record?.name || '', password: '', password_confirmation: '', department_id: state.user.department_id });
    visible.value = true;
}
function save() {
    if (mode.value === 'detail') return;
    if (mode.value === 'password') return submit('password', { password: form.password, password_confirmation: form.password_confirmation });
    const data = { email: form.email, name: form.name };
    if (!editing.value) Object.assign(data, { password: form.password, department_id: form.department_id, locale: config.locale });
    return submit(editing.value ? 'update' : 'create', data);
}
defineExpose({ open });
</script>

<template>
<EditorDialog v-model="visible" :title="title" :width="mode==='detail'?'66vw':'50vw'" :busy="busy" :read-only="mode==='detail'" @save="save">
    <Detail v-if="mode==='detail'" :detail-fields="detailFields" />
    <el-form v-else label-position="right" label-width="144px" class="horizontal-editor-form" :disabled="busy" @submit.prevent="save">
        <PasswordForm v-if="mode==='password'" :form="form" :errors="errors" />
        <EditForm v-else :form="form" :errors="errors" :editing="editing" :options="options" />
        <button type="submit" class="sr-only" tabindex="-1">{{ t('common.save') }}</button>
    </el-form>
</EditorDialog>
</template>
