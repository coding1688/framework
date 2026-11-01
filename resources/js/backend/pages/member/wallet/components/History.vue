<script setup>
import { reactive, ref } from 'vue';
import { Refresh, Search } from '@element-plus/icons-vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import { memberWalletApi } from '@/api/member/wallet';
import { date } from '@/utils/date';
import { fail } from '@/utils/feedback';
import { t } from '@/locales';

const visible = ref(false), loading = ref(false), error = ref(''), rows = ref([]), total = ref(0), page = ref(1), userId = ref(null), username = ref('');
const filters = reactive({ currency: '', direction: '', type: '', date_from: '', date_to: '' });

function open(row) {
    userId.value = row.user_id;
    username.value = row.user.username;
    Object.assign(filters, { currency: row.currency, direction: '', type: '', date_from: '', date_to: '' });
    page.value = 1;
    rows.value = [];
    error.value = '';
    visible.value = true;
    load();
}
async function load() {
    loading.value = true;
    error.value = '';
    try {
        const result = await memberWalletApi.history({ id: userId.value, query: { ...filters, page: page.value } });
        rows.value = result.data || [];
        total.value = result.total || 0;
    } catch (exception) { error.value = exception.message; fail(exception); }
    finally { loading.value = false; }
}
function search() { page.value = 1; load(); }
function reset() { Object.assign(filters, { currency: '', direction: '', type: '', date_from: '', date_to: '' }); search(); }
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="`${t('member.wallet.history')} · ${username}`" width="76vw" :close-on-click-modal="false">
        <el-form inline @submit.prevent="search"><el-form-item><el-select v-model="filters.direction" clearable :placeholder="t('member.wallet.direction')"><el-option :label="t('member.wallet.credit')" value="credit" /><el-option :label="t('member.wallet.debit')" value="debit" /></el-select></el-form-item><el-form-item><el-input v-model="filters.type" clearable maxlength="32" :placeholder="t('member.wallet.type')" /></el-form-item><el-form-item><el-date-picker v-model="filters.date_from" type="date" value-format="YYYY-MM-DD" :placeholder="t('member.wallet.date_from')" /></el-form-item><el-form-item><el-date-picker v-model="filters.date_to" type="date" value-format="YYYY-MM-DD" :placeholder="t('member.wallet.date_to')" /></el-form-item><el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button></el-form>
        <el-alert v-if="error" :title="error" type="error" :closable="false" />
        <el-table :data="rows" v-loading="loading" row-key="id" border :empty-text="t('common.empty')">
            <el-table-column :label="t('member.wallet.direction')" width="100"><template #default="{row}"><el-tag :type="row.direction === 'credit' ? 'success' : 'warning'">{{ t(`member.wallet.${row.direction}`) }}</el-tag></template></el-table-column>
            <el-table-column prop="currency" :label="t('member.wallet.currency')" width="90" />
            <el-table-column prop="type" :label="t('member.wallet.type')" min-width="120" />
            <el-table-column prop="amount" :label="t('member.wallet.amount')" min-width="110" />
            <el-table-column :label="t('member.wallet.balance_change')" min-width="190"><template #default="{row}">{{ row.balance_before }} → {{ row.balance_after }}</template></el-table-column>
            <el-table-column prop="reference" :label="t('member.wallet.reference')" min-width="190" show-overflow-tooltip />
            <el-table-column prop="actor" :label="t('member.wallet.operator')" min-width="120" />
            <el-table-column :label="t('common.created_at')" width="175"><template #default="{row}">{{ date(row.created_at) }}</template></el-table-column>
        </el-table>
        <div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" layout="total, prev, pager, next" @current-change="load" /></div>
        <template #footer><el-button @click="visible = false">{{ t('common.close') }}</el-button></template>
    </BaseDialog>
</template>

<style scoped>
.table-pagination { margin-top: 16px; }
</style>
