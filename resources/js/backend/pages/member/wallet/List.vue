<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Plus, Refresh, Search } from '@element-plus/icons-vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import Adjustment from './components/Adjustment.vue';
import History from './components/History.vue';
import { memberWalletApi } from '@/api/member/wallet';
import { can } from '@/utils/permission';
import { date } from '@/utils/date';
import { fail } from '@/utils/feedback';
import { t } from '@/locales';

const rows = ref([]), loading = ref(false), filters = reactive({ q: '', currency: '', is_test: '' }), page = ref(1), total = ref(0), mainCurrency = ref(''), currencies = ref([]), adjustment = ref(), history = ref();

async function load() {
    loading.value = true;
    try {
        const result = await memberWalletApi.list({ query: { ...filters, page: page.value } });
        rows.value = result.data || [];
        total.value = result.total || 0;
    } catch (error) { fail(error); }
    finally { loading.value = false; }
}
async function loadMetadata() {
    try {
        const result = await memberWalletApi.options();
        mainCurrency.value = result.data.main_currency;
        currencies.value = result.data.currencies || [];
    } catch (error) { fail(error); }
}
function search() { page.value = 1; load(); }
function reset() { Object.assign(filters, { q: '', currency: '', is_test: '' }); search(); }
onMounted(async () => { await Promise.all([loadMetadata(), load()]); });
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar"><div class="table-toolbar__actions"><el-button v-if="can('memberWallet','credit')" type="primary" :icon="Plus" @click="adjustment.open('credit')">{{ t('member.wallet.credit') }}</el-button><el-tag type="success">{{ t('member.wallet.main_currency') }}: {{ mainCurrency }}</el-tag></div><el-form inline @submit.prevent="search"><el-form-item><el-input v-model="filters.q" clearable maxlength="128" :placeholder="t('member.wallet.search_hint')" /></el-form-item><el-form-item><el-select v-model="filters.currency" clearable :placeholder="t('member.wallet.currency')"><el-option v-for="currency in currencies" :key="currency" :label="currency" :value="currency" /></el-select></el-form-item><el-form-item><el-select v-model="filters.is_test" clearable :placeholder="t('member.wallet.user_type')"><el-option :label="t('member.wallet.regular_user')" :value="0" /><el-option :label="t('member.wallet.test_user')" :value="1" /></el-select></el-form-item><el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button></el-form></div>
        <el-table :data="rows" v-loading="loading" row-key="id" border class="management-table" :empty-text="t('common.empty')">
            <el-table-column :label="t('member.wallet.user')" min-width="230"><template #default="{row}"><div class="compact-cell"><strong>@{{ row.user.username }}</strong><el-tag size="small" :type="row.user.is_test ? 'warning' : 'success'">{{ t(row.user.is_test ? 'member.wallet.test_user' : 'member.wallet.regular_user') }}</el-tag><small>{{ row.user.name }}</small><small>{{ row.user.email }}</small></div></template></el-table-column>
            <el-table-column :label="t('member.wallet.currency')" min-width="140"><template #default="{row}"><el-tag :type="row.currency === mainCurrency ? 'success' : 'info'">{{ row.currency }}</el-tag><small v-if="row.currency === mainCurrency" class="main-label">{{ t('member.wallet.main') }}</small></template></el-table-column>
            <el-table-column prop="balance" :label="t('member.wallet.balance')" min-width="150" />
            <el-table-column :label="t('member.wallet.updated_at')" width="180"><template #default="{row}">{{ date(row.updated_at) }}</template></el-table-column>
            <el-table-column :label="t('common.actions')" fixed="right" min-width="300"><template #default="{row}"><div class="table-actions"><el-button v-if="can('memberWallet','credit')" link type="primary" @click="adjustment.open('credit', row)">{{ t('member.wallet.credit') }}</el-button><el-button v-if="can('memberWallet','debit')" link type="warning" @click="adjustment.open('debit', row)">{{ t('member.wallet.debit') }}</el-button><el-button v-if="can('memberWallet','history')" link type="primary" @click="history.open(row)">{{ t('member.wallet.history') }}</el-button></div></template></el-table-column>
        </el-table>
        <div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" layout="total, prev, pager, next" @current-change="load" /></div>
    </BaseCard>
    <Adjustment ref="adjustment" @saved="load" />
    <History ref="history" />
</template>

<style scoped>
.compact-cell { display: grid; gap: 5px; }
.compact-cell small, .main-label { color: #88948d; font-size: 12px; }
.main-label { margin-left: 6px; }
</style>
