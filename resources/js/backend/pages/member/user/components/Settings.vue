<script setup>
import { reactive, ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import { SecurityPasswordDialog } from '@/plugins/securityPassword';
import { frontendUserApi } from '@/api/member/user';
import { can } from '@/utils/permission';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const emit = defineEmits(['saved']);
const visible = ref(false), loading = ref(false), busy = ref(false), error = ref(''), activeTab = ref('basic'), securityDialog = ref();
const form = reactive({ id: null, username: '', name: '', email: '', is_test: false, remark: '', locale: '', default_currency: '', locale_options: {}, currency_options: [], password: '', password_confirmation: '' });

async function open(row) {
    activeTab.value = can('frontendUsers', 'update') ? 'basic' : 'password';
    error.value = '';
    visible.value = true;
    loading.value = true;
    try { Object.assign(form, (await frontendUserApi.detail({ id: row.id })).data, { password: '', password_confirmation: '' }); }
    catch (exception) { error.value = exception.message; }
    finally { loading.value = false; }
}
async function submitBasic() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        await frontendUserApi.update({ id: form.id, data: { username: form.username, name: form.name, email: form.email, is_test: form.is_test, remark: form.remark, locale: form.locale, default_currency: form.default_currency } });
        notify(t('common.saved'));
        visible.value = false;
        emit('saved');
    } catch (exception) { error.value = exception.message; fail(exception); }
    finally { busy.value = false; }
}
async function submitPassword() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        await securityDialog.value.open((security_password, { signal }) => frontendUserApi.password({ id: form.id, data: { password: form.password, password_confirmation: form.password_confirmation, security_password }, signal }));
        notify(t('common.saved'));
        visible.value = false;
        emit('saved');
    } catch (exception) { error.value = exception.message; fail(exception); }
    finally { busy.value = false; }
}
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t('member.user.settings')" width="50%" :close-on-click-modal="false">
        <div v-loading="loading">
            <el-alert v-if="error" :title="error" type="error" :closable="false" />
            <el-tabs v-model="activeTab">
                <el-tab-pane v-if="can('frontendUsers','update')" :label="t('member.user.basic_info')" name="basic">
                    <el-form label-position="left" label-width="120px" @submit.prevent="submitBasic">
                        <el-form-item :label="t('member.user.username')" required><el-input v-model="form.username" maxlength="32" required /></el-form-item>
                        <el-form-item :label="t('form.email')" required><el-input v-model="form.email" type="email" maxlength="256" required /></el-form-item>
                        <el-form-item :label="t('member.user.nickname')" required><el-input v-model="form.name" maxlength="128" required /></el-form-item>
                        <el-form-item :label="t('member.user.user_type')"><el-select v-model="form.is_test"><el-option :label="t('member.user.regular_user')" :value="false" /><el-option :label="t('member.user.test_user')" :value="true" /></el-select></el-form-item>
                        <el-form-item :label="t('member.user.remark')"><el-input v-model="form.remark" type="textarea" maxlength="512" show-word-limit /></el-form-item>
                        <el-form-item :label="t('member.user.default_locale')" required><el-select v-model="form.locale" required><el-option v-for="(label, locale) in form.locale_options" :key="locale" :label="label" :value="locale" /></el-select></el-form-item>
                        <el-form-item :label="t('member.user.default_currency')" required><el-select v-model="form.default_currency" required><el-option v-for="currency in form.currency_options" :key="currency" :label="currency" :value="currency" /></el-select></el-form-item>
                        <el-button type="primary" :loading="busy" native-type="submit">{{ t('common.save') }}</el-button>
                    </el-form>
                </el-tab-pane>
                <el-tab-pane v-if="can('frontendUsers','password')" :label="t('form.password')" name="password">
                    <el-form label-position="top" @submit.prevent="submitPassword">
                        <el-form-item :label="t('form.password')" required><el-input v-model="form.password" type="password" show-password autocomplete="new-password" required /></el-form-item>
                        <el-form-item :label="t('auth.confirm_password')" required><el-input v-model="form.password_confirmation" type="password" show-password autocomplete="new-password" required /></el-form-item>
                        <el-button type="primary" :loading="busy" native-type="submit">{{ t('common.save') }}</el-button>
                    </el-form>
                </el-tab-pane>
            </el-tabs>
        </div>
        <template #footer><el-button @click="visible = false">{{ t('common.close') }}</el-button></template>
    </BaseDialog>
    <SecurityPasswordDialog ref="securityDialog" />
</template>

<style scoped>
.el-select { width: 100%; }
.el-alert { margin-bottom: 16px; }
</style>
