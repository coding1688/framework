<script setup>
import { reactive, ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import { frontendUserApi } from '@/api/member/user';
import { date } from '@/utils/date';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const emit = defineEmits(['saved']);
const visible = ref(false), mode = ref('edit'), loading = ref(false), busy = ref(false), error = ref('');
const form = reactive({ id: null, username: '', name: '', email: '', vip_level: 0, password: '', password_confirmation: '', providers: [], active: true, email_verified: false, last_login_at: null, last_login_ip: null, last_login_device: null, created_at: null });

async function open(action, row) {
    mode.value = action;
    error.value = '';
    visible.value = true;
    loading.value = true;
    try { Object.assign(form, (await frontendUserApi.detail({ id: row.id })).data, { password: '', password_confirmation: '' }); }
    catch (exception) { error.value = exception.message; }
    finally { loading.value = false; }
}
async function submit() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        if (mode.value === 'password') {
            await frontendUserApi.password({ id: form.id, data: { password: form.password, password_confirmation: form.password_confirmation } });
        } else {
            await frontendUserApi.update({ id: form.id, data: { username: form.username, name: form.name, email: form.email } });
        }
        notify(t('common.saved'));
        visible.value = false;
        emit('saved');
    } catch (exception) { error.value = exception.message; fail(exception); }
    finally { busy.value = false; }
}
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t(`member.user.${mode}`)" width="50%" :close-on-click-modal="false">
        <div v-loading="loading">
            <el-alert v-if="error" :title="error" type="error" :closable="false" />
            <el-descriptions v-if="mode === 'detail'" :column="1" border>
                <el-descriptions-item :label="t('member.user.username')">{{ form.username }}</el-descriptions-item><el-descriptions-item :label="t('form.email')">{{ form.email }}</el-descriptions-item><el-descriptions-item :label="t('member.user.providers')">{{ form.providers.join(', ') || '—' }}</el-descriptions-item><el-descriptions-item :label="t('member.user.last_login')">{{ date(form.last_login_at) }}</el-descriptions-item><el-descriptions-item label="IP">{{ form.last_login_ip || '—' }}</el-descriptions-item><el-descriptions-item :label="t('member.user.device')">{{ form.last_login_device || '—' }}</el-descriptions-item>
            </el-descriptions>
            <el-form v-else label-position="top" @submit.prevent="submit">
                <template v-if="mode === 'edit'"><el-form-item :label="t('member.user.username')" required><el-input v-model="form.username" maxlength="32" required /></el-form-item><el-form-item :label="t('member.user.nickname')" required><el-input v-model="form.name" maxlength="128" required /></el-form-item><el-form-item :label="t('form.email')" required><el-input v-model="form.email" type="email" maxlength="256" required /></el-form-item></template>
                <template v-else><el-form-item :label="t('form.password')" required><el-input v-model="form.password" type="password" show-password autocomplete="new-password" required /></el-form-item><el-form-item :label="t('auth.confirm_password')" required><el-input v-model="form.password_confirmation" type="password" show-password autocomplete="new-password" required /></el-form-item></template>
            </el-form>
        </div>
        <template #footer><el-button @click="visible = false">{{ t('common.close') }}</el-button><el-button v-if="mode !== 'detail'" type="primary" :loading="busy" @click="submit">{{ t('common.save') }}</el-button></template>
    </BaseDialog>
</template>
