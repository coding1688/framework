<script setup>
import { reactive, ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import { SecurityPasswordDialog } from '@/plugins/securityPassword';
import { memberWalletApi } from '@/api/member/wallet';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const emit = defineEmits(['saved']);
const visible = ref(false), busy = ref(false), loading = ref(false), error = ref(''), direction = ref('credit'), users = ref([]), currencies = ref([]), securityDialog = ref();
const form = reactive({ user_id: null, currency: '', amount: '', type: 'adjustment', reference: '', remark: '' });

function reference() { return `admin-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}`; }
async function loadUsers(q = '') {
    loading.value = true;
    try {
        const result = await memberWalletApi.options({ query: { q } });
        users.value = result.data.users || [];
        currencies.value = result.data.currencies || [];
        if (!form.currency) form.currency = result.data.main_currency;
    } catch (exception) { error.value = exception.message; }
    finally { loading.value = false; }
}
async function open(action, row = null) {
    direction.value = action;
    Object.assign(form, { user_id: row?.user_id || null, currency: row?.currency || '', amount: '', type: 'adjustment', reference: reference(), remark: '' });
    error.value = '';
    visible.value = true;
    await loadUsers(row?.user?.username || row?.username || '');
    if (!form.user_id && users.value.length) form.user_id = users.value[0].id;
}
async function submit() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        await securityDialog.value.open((security_password, { signal }) => memberWalletApi[direction.value]({ id: form.user_id, data: { ...form, security_password }, signal }));
        notify(t('common.saved'));
        visible.value = false;
        emit('saved');
    } catch (exception) { error.value = exception.message; fail(exception); }
    finally { busy.value = false; }
}
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t(`member.wallet.${direction}`)" width="50%" :close-on-click-modal="false">
        <el-alert v-if="error" :title="error" type="error" :closable="false" />
        <el-form label-position="top" @submit.prevent="submit">
            <el-form-item :label="t('member.wallet.user')" required><el-select v-model="form.user_id" filterable remote :remote-method="loadUsers" :loading="loading" required><el-option v-for="user in users" :key="user.id" :label="`${user.name} (${user.username})`" :value="user.id" /></el-select></el-form-item>
            <el-form-item :label="t('member.wallet.currency')" required><el-select v-model="form.currency" required><el-option v-for="currency in currencies" :key="currency" :label="currency" :value="currency" /></el-select></el-form-item>
            <el-form-item :label="t('member.wallet.amount')" required><el-input v-model="form.amount" maxlength="64" required /></el-form-item>
            <el-form-item :label="t('member.wallet.type')" required><el-input v-model="form.type" maxlength="32" required /></el-form-item>
            <el-form-item :label="t('member.wallet.reference')" required><el-input v-model="form.reference" maxlength="128" required /></el-form-item>
            <el-form-item :label="t('member.wallet.remark')"><el-input v-model="form.remark" type="textarea" maxlength="512" show-word-limit /></el-form-item>
        </el-form>
        <template #footer><el-button @click="visible = false">{{ t('common.cancel') }}</el-button><el-button :type="direction === 'credit' ? 'primary' : 'warning'" :loading="busy" @click="submit">{{ t('common.confirm') }}</el-button></template>
    </BaseDialog>
    <SecurityPasswordDialog ref="securityDialog" />
</template>

<style scoped>
.el-select { width: 100%; }
.el-alert { margin-bottom: 18px; }
</style>
