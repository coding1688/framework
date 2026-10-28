<script setup>
import { onMounted, ref } from 'vue';
import { Delete, Edit, Plus, Refresh } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import StatusTag from '@/components/StatusTag.vue';
import Editor from './components/Editor.vue';
import { vipLevelApi } from '@/api/member/vipLevel';
import { can } from '@/utils/permission';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const rows = ref([]), loading = ref(false), busy = ref(''), active = ref(''), page = ref(1), total = ref(0), editor = ref();
async function load() {
    loading.value = true;
    try { const result = await vipLevelApi.list({ query: { active: active.value, page: page.value } }); rows.value = result.data || []; total.value = result.total || 0; }
    catch (error) { fail(error); }
    finally { loading.value = false; }
}
function search() { page.value = 1; load(); }
async function toggle(row) {
    busy.value = row.id;
    try { await vipLevelApi.status({ id: row.id, data: { active: !row.active } }); notify(t('common.saved')); await load(); }
    catch (error) { fail(error); }
    finally { busy.value = ''; }
}
async function remove(row) {
    try { await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), { type: 'warning' }); await vipLevelApi.delete({ id: row.id }); notify(t('common.saved')); await load(); }
    catch (error) { if (!['cancel', 'close'].includes(error)) fail(error); }
}
onMounted(load);
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar"><el-button v-if="can('vipLevels','create')" type="primary" :icon="Plus" @click="editor.open()">{{ t('common.create') }}</el-button><el-form inline @submit.prevent="search"><el-form-item><el-select v-model="active" clearable :placeholder="t('common.status')"><el-option :label="t('common.active')" :value="1" /><el-option :label="t('common.inactive')" :value="0" /></el-select></el-form-item><el-button native-type="submit" type="primary">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="active = ''; search()">{{ t('common.reset') }}</el-button></el-form></div>
        <el-table :data="rows" v-loading="loading" row-key="id" border :empty-text="t('common.empty')">
            <el-table-column :label="t('member.vipLevel.level')" prop="level" width="100" align="center" />
            <el-table-column :label="t('common.name')" prop="name" min-width="180" />
            <el-table-column :label="t('member.vipLevel.benefits')" prop="benefits" min-width="260" show-overflow-tooltip />
            <el-table-column :label="t('member.vipLevel.duration')" width="140"><template #default="{row}">{{ row.duration_days ? `${row.duration_days} ${t('member.vipLevel.days')}` : t('member.vipLevel.permanent') }}</template></el-table-column>
            <el-table-column :label="t('member.vipLevel.users')" prop="users_count" width="110" align="center" />
            <el-table-column :label="t('common.status')" width="100"><template #default="{row}"><el-switch v-if="can('vipLevels','status')" :model-value="row.active" :loading="busy === row.id" @change="toggle(row)" /><StatusTag v-else :active="row.active" /></template></el-table-column>
            <el-table-column :label="t('common.actions')" width="180" fixed="right"><template #default="{row}"><el-button v-if="can('vipLevels','update')" link type="primary" :icon="Edit" @click="editor.open(row)">{{ t('common.edit') }}</el-button><el-button v-if="can('vipLevels','delete')" link type="danger" :icon="Delete" @click="remove(row)">{{ t('common.delete') }}</el-button></template></el-table-column>
        </el-table>
        <div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" @current-change="load" /></div>
    </BaseCard>
    <Editor ref="editor" @saved="load" />
</template>
