<script setup>
import { reactive, ref } from 'vue';
import { Search, Refresh } from '@element-plus/icons-vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import { frontendUserApi } from '@/api/member/user';
import { date } from '@/utils/date';
import { fail } from '@/utils/feedback';
import { t } from '@/locales';
import { deviceTypeLabel } from '../constants';

const visible = ref(false), loading = ref(false), error = ref(''), rows = ref([]), total = ref(0), page = ref(1), userId = ref(null), username = ref('');
const filters = reactive({ action: '', date_from: '', date_to: '' });

function open(row) {
    userId.value = row.id;
    username.value = row.username;
    Object.assign(filters, { action: '', date_from: '', date_to: '' });
    page.value = 1;
    rows.value = [];
    total.value = 0;
    error.value = '';
    visible.value = true;
    load();
}
async function load() {
    loading.value = true;
    error.value = '';
    try {
        const result = await frontendUserApi.logs({ id: userId.value, query: { ...filters, page: page.value } });
        rows.value = result.data || [];
        total.value = result.total || 0;
    } catch (exception) { error.value = exception.message; fail(exception); }
    finally { loading.value = false; }
}
function search() { page.value = 1; load(); }
function reset() { Object.assign(filters, { action: '', date_from: '', date_to: '' }); search(); }
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="`${t('member.user.logs')} · ${username}`" width="76vw" :close-on-click-modal="false">
        <el-form inline @submit.prevent="search">
            <el-form-item><el-input v-model="filters.action" clearable maxlength="128" :placeholder="t('member.user.log_action_hint')" /></el-form-item>
            <el-form-item><el-date-picker v-model="filters.date_from" type="date" value-format="YYYY-MM-DD" :placeholder="t('member.user.date_from')" /></el-form-item>
            <el-form-item><el-date-picker v-model="filters.date_to" type="date" value-format="YYYY-MM-DD" :placeholder="t('member.user.date_to')" /></el-form-item>
            <el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button>
        </el-form>
        <el-alert v-if="error" :title="error" type="error" :closable="false" />
        <el-table :data="rows" v-loading="loading" row-key="id" border :empty-text="t('common.empty')">
            <el-table-column type="expand"><template #default="{row}"><pre class="log-context">{{ JSON.stringify(row.context || {}, null, 2) }}</pre></template></el-table-column>
            <el-table-column prop="action" :label="t('member.user.action')" min-width="190" />
            <el-table-column prop="actor" :label="t('member.user.actor')" min-width="130" />
            <el-table-column prop="ip" label="IP" min-width="125"><template #default="{row}">{{ row.ip || '—' }}</template></el-table-column>
            <el-table-column prop="device_type" :label="t('member.user.device_type')" width="110"><template #default="{row}">{{ deviceTypeLabel(row.device_type) }}</template></el-table-column>
            <el-table-column prop="user_agent" :label="t('member.user.device')" min-width="220" show-overflow-tooltip><template #default="{row}">{{ row.user_agent || '—' }}</template></el-table-column>
            <el-table-column :label="t('common.created_at')" width="175"><template #default="{row}">{{ date(row.created_at) }}</template></el-table-column>
        </el-table>
        <div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" layout="total, prev, pager, next" @current-change="load" /></div>
        <template #footer><el-button @click="visible = false">{{ t('common.close') }}</el-button></template>
    </BaseDialog>
</template>

<style scoped>
.log-context { margin: 0; padding: 12px 18px; white-space: pre-wrap; word-break: break-word; font-size: 12px; }
.table-pagination { margin-top: 16px; }
</style>
