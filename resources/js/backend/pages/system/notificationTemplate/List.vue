<script setup>
import { onMounted, ref } from 'vue';
import { Delete, Edit, Plus, Refresh, Search } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import StatusTag from '@/components/StatusTag.vue';
import Editor from './components/Editor.vue';
import { notificationTemplateApi } from '@/api/system/notificationTemplate';
import { can } from '@/utils/permission';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const rows = ref([]), loading = ref(false), q = ref(''), page = ref(1), total = ref(0), editor = ref();
async function load() {
    loading.value = true;
    try { const result = await notificationTemplateApi.list({ query: { q: q.value, page: page.value } }); rows.value = result.data || []; total.value = result.total || 0; }
    catch (error) { fail(error); }
    finally { loading.value = false; }
}
function search() { page.value = 1; load(); }
async function remove(row) {
    try { await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), { type: 'warning' }); await notificationTemplateApi.delete({ id: row.id }); notify(t('common.saved')); await load(); }
    catch (error) { if (!['cancel', 'close'].includes(error)) fail(error); }
}
onMounted(load);
</script>

<template><BaseCard class="management-card" shadow="never"><div class="table-toolbar"><el-button v-if="can('notificationTemplates','create')" type="primary" :icon="Plus" @click="editor.open()">{{ t('common.create') }}</el-button><el-form inline @submit.prevent="search"><el-input v-model="q" maxlength="128" :placeholder="t('system.notificationTemplate.code')" clearable /><el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="q = ''; search()">{{ t('common.reset') }}</el-button></el-form></div><el-table :data="rows" v-loading="loading" border row-key="id"><el-table-column :label="t('system.notificationTemplate.code')" prop="code" min-width="220" /><el-table-column :label="t('common.name')" prop="name" min-width="200" /><el-table-column :label="t('system.notificationTemplate.channels')" min-width="160"><template #default="{row}">{{ row.channels.join(', ') }}</template></el-table-column><el-table-column :label="t('common.status')" width="100"><template #default="{row}"><StatusTag :active="row.active" /></template></el-table-column><el-table-column :label="t('common.actions')" width="170" fixed="right"><template #default="{row}"><el-button v-if="can('notificationTemplates','update')" link type="primary" :icon="Edit" @click="editor.open(row)">{{ t('common.edit') }}</el-button><el-button v-if="can('notificationTemplates','delete')" link type="danger" :icon="Delete" @click="remove(row)">{{ t('common.delete') }}</el-button></template></el-table-column></el-table><div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" @current-change="load" /></div></BaseCard><Editor ref="editor" @saved="load" /></template>
