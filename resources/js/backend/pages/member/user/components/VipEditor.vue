<script setup>
import { reactive, ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import { frontendUserApi } from '@/api/member/user';
import { date } from '@/utils/date';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const props = defineProps({ levels: { type: Array, default: () => [] } });
const emit = defineEmits(['saved']);
const visible = ref(false), loading = ref(false), busy = ref(false), error = ref(''), records = ref([]);
const form = reactive({ id: null, vip_level_id: null, started_at: '', expires_at: null, reason: '' });
function timestamp() { return new Date().toISOString().slice(0, 19).replace('T', ' '); }
async function open(row) {
    Object.assign(form, { id: row.id, vip_level_id: null, started_at: timestamp(), expires_at: null, reason: '' });
    visible.value = true; loading.value = true; error.value = '';
    try {
        const [detail, history] = await Promise.all([frontendUserApi.detail({ id: row.id }), frontendUserApi.vipHistory({ id: row.id })]);
        Object.assign(form, detail.data, { reason: '' });
        form.started_at = form.vip_started_at ? form.vip_started_at.slice(0, 19).replace('T', ' ') : timestamp();
        form.expires_at = form.vip_expires_at ? form.vip_expires_at.slice(0, 19).replace('T', ' ') : null;
        records.value = history.data || [];
    } catch (exception) { error.value = exception.message; fail(exception); }
    finally { loading.value = false; }
}
async function submit() {
    if (busy.value) return;
    busy.value = true; error.value = '';
    try {
        await frontendUserApi.vip({ id: form.id, data: { vip_level_id: form.vip_level_id, started_at: form.started_at, expires_at: form.expires_at, reason: form.reason } });
        notify(t('common.saved')); visible.value = false; emit('saved');
    } catch (exception) { error.value = exception.message; fail(exception); }
    finally { busy.value = false; }
}
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t('member.user.vip')" width="76vw" :close-on-click-modal="false">
        <div v-loading="loading">
            <el-alert v-if="error" :title="error" type="error" :closable="false" />
            <el-form label-position="top" @submit.prevent="submit">
                <el-row :gutter="20"><el-col :xs="24" :md="8"><el-form-item :label="t('member.user.vip_level')"><el-select v-model="form.vip_level_id" clearable :placeholder="t('member.user.no_vip')"><el-option v-for="level in props.levels" :key="level.id" :label="level.name" :value="level.id" /></el-select></el-form-item></el-col><el-col :xs="24" :md="8"><el-form-item :label="t('member.user.vip_started_at')"><el-date-picker v-model="form.started_at" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" /></el-form-item></el-col><el-col :xs="24" :md="8"><el-form-item :label="t('member.user.vip_expires_at')"><el-date-picker v-model="form.expires_at" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" :placeholder="t('member.user.permanent')" /></el-form-item></el-col></el-row>
                <el-form-item :label="t('member.user.reason')"><el-input v-model="form.reason" type="textarea" maxlength="512" show-word-limit /></el-form-item>
                <el-button type="primary" :loading="busy" native-type="submit">{{ t('common.save') }}</el-button>
            </el-form>
            <h3>{{ t('member.user.vip_history') }}</h3>
            <el-table :data="records" border :empty-text="t('common.empty')">
                <el-table-column :label="t('member.user.change')" min-width="180"><template #default="{row}">{{ row.from_level?.name || t('member.user.no_vip') }} → {{ row.to_level?.name || t('member.user.no_vip') }}</template></el-table-column>
                <el-table-column :label="t('member.user.vip_expires_at')" width="175"><template #default="{row}">{{ row.to_level ? (row.to_expires_at ? date(row.to_expires_at) : t('member.user.permanent')) : '—' }}</template></el-table-column>
                <el-table-column :label="t('member.user.reason')" prop="reason" min-width="180" show-overflow-tooltip />
                <el-table-column :label="t('member.user.operator')" width="130"><template #default="{row}">{{ row.administrator?.name || '—' }}</template></el-table-column>
                <el-table-column :label="t('common.created_at')" width="175"><template #default="{row}">{{ date(row.created_at) }}</template></el-table-column>
            </el-table>
        </div>
        <template #footer><el-button @click="visible = false">{{ t('common.close') }}</el-button></template>
    </BaseDialog>
</template>

<style scoped>
.el-select, .el-date-editor { width: 100%; }
h3 { margin-top: 26px; }
</style>
