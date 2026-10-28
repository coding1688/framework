<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Refresh, Search } from '@element-plus/icons-vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import StatusTag from '@/components/StatusTag.vue';
import Editor from './components/Editor.vue';
import VipEditor from './components/VipEditor.vue';
import { frontendUserApi } from '@/api/member/user';
import { can } from '@/utils/permission';
import { date } from '@/utils/date';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const rows = ref([]), loading = ref(false), busy = ref(''), editor = ref(), vipEditor = ref(), vipLevels = ref([]);
const filters = reactive({ q: '', active: '', verified: '', vip_level_id: '' });
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
async function toggle(row) { await run('status', row, () => frontendUserApi.status({ id: row.id, data: { active: !row.active } })); }
async function verification(row) { await run('verification', row, () => frontendUserApi.verification({ id: row.id })); }
onMounted(async () => { await Promise.all([loadVipOptions(), load()]); });
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar"><div /><el-form inline @submit.prevent="search"><el-form-item><el-input v-model="filters.q" clearable maxlength="128" :placeholder="t('member.user.search_hint')" /></el-form-item><el-form-item><el-select v-model="filters.active" clearable :placeholder="t('common.status')"><el-option :label="t('common.active')" :value="1" /><el-option :label="t('common.inactive')" :value="0" /></el-select></el-form-item><el-form-item><el-select v-model="filters.verified" clearable :placeholder="t('member.user.verified')"><el-option :label="t('member.user.is_verified')" :value="1" /><el-option :label="t('member.user.unverified')" :value="0" /></el-select></el-form-item><el-form-item v-if="can('frontendUsers','vipOptions')"><el-select v-model="filters.vip_level_id" clearable :placeholder="t('member.user.vip_level')"><el-option v-for="level in vipLevels" :key="level.id" :label="level.name" :value="level.id" /></el-select></el-form-item><el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button></el-form></div>
        <el-table :data="rows" v-loading="loading" row-key="id" border class="management-table" :empty-text="t('common.empty')">
            <el-table-column :label="t('member.user.user')" min-width="230"><template #default="{row}"><div class="person-cell"><el-avatar :size="32" :src="row.avatar" /><div><strong>{{ row.name }}</strong><small>{{ row.email }}</small></div></div></template></el-table-column>
            <el-table-column :label="t('member.user.username')" prop="username" min-width="130" />
            <el-table-column label="VIP" min-width="150"><template #default="{row}"><el-tag :type="row.vip_status === 'expired' ? 'info' : row.vip_level ? 'warning' : 'info'">{{ row.vip_name || `VIP Lv.${row.vip_level}` }}</el-tag><div><small>{{ t(`member.user.vip_status_${row.vip_status}`) }}</small></div></template></el-table-column>
            <el-table-column :label="t('member.user.verified')" width="110" align="center"><template #default="{row}"><el-tag :type="row.email_verified ? 'success' : 'warning'">{{ t(row.email_verified ? 'member.user.is_verified' : 'member.user.unverified') }}</el-tag></template></el-table-column>
            <el-table-column :label="t('member.user.last_login')" width="175" align="center"><template #default="{row}">{{ date(row.last_login_at) }}</template></el-table-column><el-table-column :label="t('common.status')" width="95"><template #default="{row}"><StatusTag :active="row.active" /></template></el-table-column>
            <el-table-column :label="t('common.actions')" fixed="right" min-width="440"><template #default="{row}"><div class="table-actions"><el-button v-if="can('frontendUsers','detail')" link type="primary" @click="editor.open('detail', row)">{{ t('common.details') }}</el-button><el-button v-if="can('frontendUsers','update')" link type="primary" @click="editor.open('edit', row)">{{ t('common.edit') }}</el-button><el-button v-if="can('frontendUsers','vip')" link type="warning" @click="vipEditor.open(row)">{{ t('member.user.vip') }}</el-button><el-button v-if="can('frontendUsers','status')" link type="primary" :loading="busy === `status${row.id}`" @click="toggle(row)">{{ t(row.active ? 'disable_account' : 'enable_account') }}</el-button><el-button v-if="can('frontendUsers','password')" link type="primary" @click="editor.open('password', row)">{{ t('form.reset_password') }}</el-button><el-button v-if="can('frontendUsers','verification') && !row.email_verified" link type="primary" :loading="busy === `verification${row.id}`" @click="verification(row)">{{ t('member.user.resend') }}</el-button></div></template></el-table-column>
        </el-table>
        <div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" layout="total, prev, pager, next" @current-change="load" /></div>
    </BaseCard>
    <Editor ref="editor" @saved="load" />
    <VipEditor ref="vipEditor" :levels="vipLevels" @saved="load" />
</template>
