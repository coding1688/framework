<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Refresh, Search } from '@element-plus/icons-vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import StatusTag from '@/components/StatusTag.vue';
import Editor from './components/Editor.vue';
import Settings from './components/Settings.vue';
import StatusEditor from './components/StatusEditor.vue';
import Logs from './components/Logs.vue';
import Adjustment from '@/pages/member/wallet/components/Adjustment.vue';
import { frontendUserApi } from '@/api/member/user';
import { can } from '@/utils/permission';
import { date } from '@/utils/date';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';
import { deviceTypeLabel } from './constants';

const rows = ref([]), loading = ref(false), busy = ref(''), editor = ref(), settings = ref(), statusEditor = ref(), logs = ref(), adjustment = ref(), vipLevels = ref([]);
const filters = reactive({ q: '', active: '', is_test: '', verified: '', vip_level_id: '' });
const page = ref(1), total = ref(0);

async function load() {
    loading.value = true;
    try {
        const result = await frontendUserApi.list({ query: { ...filters, page: page.value } });
        rows.value = result.data || [];
        total.value = result.total || 0;
    } catch (error) { fail(error); }
    finally { loading.value = false; }
}
async function loadVipOptions() {
    if (!can('frontendUsers', 'vipOptions')) return;
    try { vipLevels.value = (await frontendUserApi.vipOptions()).data || []; }
    catch (error) { fail(error); }
}
function search() { page.value = 1; load(); }
function reset() { Object.keys(filters).forEach(key => { filters[key] = ''; }); search(); }
async function run(action, row, operation) {
    busy.value = action + row.id;
    try { await operation(); notify(t('common.saved')); await load(); }
    catch (error) { fail(error); }
    finally { busy.value = ''; }
}
async function verification(row) { await run('verification', row, () => frontendUserApi.verification({ id: row.id })); }
onMounted(async () => { await Promise.all([loadVipOptions(), load()]); });
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar"><el-form inline @submit.prevent="search"><el-form-item><el-input v-model="filters.q" clearable maxlength="128" :placeholder="t('member.user.search_hint')" /></el-form-item><el-form-item><el-select v-model="filters.active" clearable :placeholder="t('common.status')"><el-option :label="t('common.active')" :value="1" /><el-option :label="t('common.inactive')" :value="0" /></el-select></el-form-item><el-form-item><el-select v-model="filters.is_test" clearable :placeholder="t('member.user.user_type')"><el-option :label="t('member.user.regular_user')" :value="0" /><el-option :label="t('member.user.test_user')" :value="1" /></el-select></el-form-item><el-form-item><el-select v-model="filters.verified" clearable :placeholder="t('member.user.verified')"><el-option :label="t('member.user.is_verified')" :value="1" /><el-option :label="t('member.user.unverified')" :value="0" /></el-select></el-form-item><el-form-item v-if="can('frontendUsers','vipOptions')"><el-select v-model="filters.vip_level_id" clearable :placeholder="t('member.user.vip_level')"><el-option v-for="level in vipLevels" :key="level.id" :label="level.name" :value="level.id" /></el-select></el-form-item><el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button></el-form></div>
        <el-table :data="rows" v-loading="loading" row-key="id" border class="management-table" :empty-text="t('common.empty')">
            <el-table-column width="64" align="center"><template #default="{row}"><el-avatar :size="32" :src="row.avatar" /></template></el-table-column>
            <el-table-column min-width="220"><template #header><div class="three-line-header"><span>{{ t('member.user.username') }}</span><span>{{ t('member.user.nickname') }}</span><span>{{ t('form.email') }}</span></div></template><template #default="{row}"><div class="compact-cell"><strong>@{{ row.username }}</strong><small>{{ row.name }}</small><small>{{ row.email }}</small></div></template></el-table-column>
            <el-table-column min-width="180"><template #header><div class="three-line-header"><span>{{ t('member.user.default_currency') }}</span><span>{{ t('member.user.total_balance') }}</span><span>{{ t('member.user.available_balance') }}</span></div></template><template #default="{row}"><div class="compact-cell"><strong>{{ row.default_currency }}</strong><small>{{ row.total_balance }}</small><small>{{ row.available_balance }}</small></div></template></el-table-column>
            <el-table-column min-width="180"><template #header><div class="three-line-header"><span>{{ t('member.user.user_type') }}</span><span>{{ t('member.user.vip_level') }}</span><span>{{ t('member.user.is_test') }}</span></div></template><template #default="{row}"><div class="compact-cell"><el-tag size="small" :type="row.is_test ? 'warning' : 'success'">{{ t(row.is_test ? 'member.user.test_user' : 'member.user.regular_user') }}</el-tag><small>{{ row.vip_name || t('member.user.no_vip') }}</small><small>{{ t(row.is_test ? 'member.user.yes' : 'member.user.no') }}</small></div></template></el-table-column>
            <el-table-column min-width="220"><template #header><div class="three-line-header"><span>{{ t('member.user.last_login') }}</span><span>IP</span><span>{{ t('member.user.device_type') }}</span></div></template><template #default="{row}"><div class="compact-cell"><span>{{ date(row.last_login_at) }}</span><small>{{ row.last_login_ip || '—' }}</small><small>{{ deviceTypeLabel(row.last_login_device_type) }}</small></div></template></el-table-column>
            <el-table-column min-width="180"><template #header><div class="three-line-header"><span>{{ t('common.status') }}</span><span>{{ t('member.user.freeze') }}</span><span>{{ t('member.user.verified') }}</span></div></template><template #default="{row}"><div class="compact-cell"><StatusTag :active="row.active" /><small>{{ t(row.frozen_at ? 'member.user.yes' : 'member.user.no') }}</small><small>{{ t(row.email_verified ? 'member.user.is_verified' : 'member.user.unverified') }}</small></div></template></el-table-column>
            <el-table-column :label="t('common.actions')" fixed="right" min-width="260"><template #default="{row}"><div class="table-actions user-actions"><div class="action-row"><el-button v-if="can('frontendUsers','detail')" link type="primary" @click="editor.open(row)">{{ t('common.details') }}</el-button><el-button v-if="can('frontendUsers','update') || can('frontendUsers','password')" link type="warning" @click="settings.open(row)">{{ t('member.user.settings') }}</el-button></div><div class="action-row"><el-button v-if="can('frontendUsers','logs')" link type="primary" @click="logs.open(row)">{{ t('member.user.logs') }}</el-button><el-button v-if="can('frontendUsers','status')" link :type="row.frozen_at ? 'success' : 'warning'" @click="statusEditor.open(row)">{{ t(row.frozen_at ? 'member.user.unfreeze' : 'member.user.freeze') }}</el-button></div><div v-if="can('memberWallet','credit') || can('memberWallet','debit')" class="action-row"><span class="action-label">{{ t('member.user.wallet') }}</span><el-button v-if="can('memberWallet','credit')" link type="primary" @click="adjustment.open('credit', { ...row, user_id: row.id, currency: row.default_currency })">{{ t('member.wallet.credit') }}</el-button><el-button v-if="can('memberWallet','debit')" link type="warning" @click="adjustment.open('debit', { ...row, user_id: row.id, currency: row.default_currency })">{{ t('member.wallet.debit') }}</el-button></div><div class="action-row"><el-button v-if="can('frontendUsers','verification') && !row.email_verified" link type="primary" :loading="busy === `verification${row.id}`" @click="verification(row)">{{ t('member.user.resend') }}</el-button></div></div></template></el-table-column>
        </el-table>
        <div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" layout="total, prev, pager, next" @current-change="load" /></div>
    </BaseCard>
    <Editor ref="editor" />
    <Settings ref="settings" @saved="load" />
    <StatusEditor ref="statusEditor" @saved="load" />
    <Logs ref="logs" />
    <Adjustment ref="adjustment" @saved="load" />
</template>

<style scoped>
:deep(.management-table .el-table__cell) { padding: 6px 0; }
:deep(.management-table .cell) { padding: 0 8px; line-height: 20px; }
.three-line-header { display: grid; gap: 3px; line-height: 1.4; white-space: normal; }
.compact-cell { display: grid; gap: 3px; justify-items: start; }
.compact-cell span { font-size: 13px; }
.compact-cell small { color: #88948d; font-size: 12px; line-height: 1.4; }
.table-actions.user-actions { display: grid; gap: 4px; justify-content: start; }
.user-actions .action-row { display: flex; flex-wrap: wrap; gap: 4px 14px; align-items: center; }
.user-actions .action-label { color: #606266; font-size: 13px; }
.user-actions .el-button { height: auto; min-height: 22px; margin: 0; padding: 2px 0; justify-content: flex-start; font-size: 13px; line-height: 18px; }
.user-actions .el-button.is-link { --el-button-text-color: var(--el-color-primary); --el-button-hover-text-color: var(--el-color-primary-dark-2); --el-button-active-text-color: var(--el-color-primary-dark-2); }
</style>
