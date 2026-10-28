<script setup>
import { onMounted, ref } from 'vue';
import { Delete, Edit, Plus, Refresh, Search } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import Editor from './components/Editor.vue';
import { announcementApi } from '@/api/content/announcement';
import { can } from '@/utils/permission';
import { date } from '@/utils/date';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const rows = ref([]), loading = ref(false), busy = ref(''), page = ref(1), total = ref(0);
const q = ref(''), active = ref(''), editor = ref();

async function load() {
    loading.value = true;
    try {
        const result = await announcementApi.list({ query: { q: q.value, active: active.value, page: page.value } });
        rows.value = result.data || [];
        total.value = result.total ?? rows.value.length;
    } catch (error) { fail(error); }
    finally { loading.value = false; }
}
function search() { page.value = 1; load(); }
function reset() { q.value = ''; active.value = ''; search(); }
async function toggleStatus(row) {
    busy.value = `status${row.id}`;
    try {
        await announcementApi.status({ id: row.id, data: { active: !row.active } });
        notify(t('common.saved'));
        await load();
    } catch (error) { fail(error); }
    finally { busy.value = ''; }
}
async function remove(row) {
    try {
        await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), { type: 'warning' });
        busy.value = `delete${row.id}`;
        await announcementApi.delete({ id: row.id });
        notify(t('common.saved'));
        await load();
    } catch (error) { if (error !== 'cancel' && error !== 'close') fail(error); }
    finally { busy.value = ''; }
}
onMounted(load);
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar">
            <div class="table-toolbar__actions">
                <el-button v-if="can('announcements','create')" type="primary" :icon="Plus" @click="editor.open()">{{ t('common.create') }}</el-button>
            </div>
            <el-form inline @submit.prevent="search">
                <el-form-item><el-input v-model="q" clearable maxlength="128" :placeholder="t('content.announcement.search_placeholder')" /></el-form-item>
                <el-form-item><el-select v-model="active" clearable :placeholder="t('common.all')"><el-option :label="t('common.active')" :value="1" /><el-option :label="t('common.inactive')" :value="0" /></el-select></el-form-item>
                <el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button>
                <el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button>
            </el-form>
        </div>
        <el-table :data="rows" v-loading="loading" row-key="id" border class="management-table" :empty-text="t('common.empty')">
            <el-table-column :label="t('content.announcement.title')" min-width="260">
                <template #default="{row}"><strong>{{ row.title }}</strong></template>
            </el-table-column>
            <el-table-column :label="t('content.announcement.published_at')" width="180" align="center">
                <template #default="{row}">{{ row.published_at ? date(row.published_at) : '—' }}</template>
            </el-table-column>
            <el-table-column :label="t('common.sort')" prop="sort" width="90" align="center" />
            <el-table-column :label="t('common.status')" width="100" align="center">
                <template #default="{row}"><el-switch :model-value="row.active" :loading="busy===`status${row.id}`" :disabled="!can('announcements','status') || !!busy" @change="toggleStatus(row)" /></template>
            </el-table-column>
            <el-table-column :label="t('common.created_at')" width="180" align="center">
                <template #default="{row}">{{ date(row.created_at) }}</template>
            </el-table-column>
            <el-table-column :label="t('common.actions')" width="170" fixed="right" align="center">
                <template #default="{row}"><div class="table-actions">
                    <el-button v-if="can('announcements','update')" link type="primary" :icon="Edit" @click="editor.open(row)">{{ t('common.edit') }}</el-button>
                    <el-button v-if="can('announcements','delete')" link type="danger" :icon="Delete" :loading="busy===`delete${row.id}`" @click="remove(row)">{{ t('common.delete') }}</el-button>
                </div></template>
            </el-table-column>
        </el-table>
        <div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" layout="total, prev, pager, next" @current-change="load" /></div>
    </BaseCard>
    <Editor ref="editor" @saved="load" />
</template>
