<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Delete, Edit, Plus, Refresh, Search, View } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import Editor from './components/Editor.vue';
import Preview from './components/Preview.vue';
import { documentApi } from '@/api/content/document';
import { documentCategoryApi } from '@/api/content/documentCategory';
import { buildCategoryTree } from '../categoryTree';
import { can } from '@/utils/permission';
import { date } from '@/utils/date';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const rows = ref([]), categories = ref([]), loading = ref(false), busy = ref(''), page = ref(1), total = ref(0);
const filters = reactive({ q: '', type: '', category_id: null, active: '' });
const editor = ref(), preview = ref();
const categoryOptions = computed(() => buildCategoryTree(categories.value, filters.type));

async function load() {
    loading.value = true;
    try {
        const result = await documentApi.list({ query: { ...filters, page: page.value } });
        rows.value = result.data || [];
        total.value = result.total ?? rows.value.length;
    } catch (error) { fail(error); }
    finally { loading.value = false; }
}
async function loadCategories() {
    if (!can('documentCategories', 'options')) return;
    try { categories.value = (await documentCategoryApi.options()).data || []; }
    catch (error) { fail(error); }
}
function search() { page.value = 1; load(); }
function reset() { Object.assign(filters, { q: '', type: '', category_id: null, active: '' }); search(); }
function changeType() { filters.category_id = null; }
async function toggleStatus(row) {
    busy.value = `status${row.id}`;
    try {
        await documentApi.status({ id: row.id, data: { active: !row.active } });
        notify(t('common.saved'));
        await load();
    } catch (error) { fail(error); }
    finally { busy.value = ''; }
}
async function remove(row) {
    try {
        await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), { type: 'warning' });
        busy.value = `delete${row.id}`;
        await documentApi.delete({ id: row.id });
        notify(t('common.saved'));
        await load();
    } catch (error) { if (error !== 'cancel' && error !== 'close') fail(error); }
    finally { busy.value = ''; }
}
onMounted(() => { loadCategories(); load(); });
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar">
            <div class="table-toolbar__actions"><el-button v-if="can('documents','create')" type="primary" :icon="Plus" @click="editor.open()">{{ t('common.create') }}</el-button></div>
            <el-form inline @submit.prevent="search">
                <el-form-item><el-input v-model="filters.q" clearable maxlength="128" :placeholder="t('documentation.document.search_placeholder')" /></el-form-item>
                <el-form-item><el-select v-model="filters.type" clearable :placeholder="t('documentation.document.type')" @change="changeType"><el-option :label="t('documentation.document.development')" value="development" /><el-option :label="t('documentation.document.usage')" value="usage" /></el-select></el-form-item>
                <el-form-item><el-tree-select v-model="filters.category_id" :data="categoryOptions" node-key="value" check-strictly clearable filterable :render-after-expand="false" :placeholder="t('documentation.document.category')" /></el-form-item>
                <el-form-item><el-select v-model="filters.active" clearable :placeholder="t('common.all')"><el-option :label="t('common.active')" :value="1" /><el-option :label="t('common.inactive')" :value="0" /></el-select></el-form-item>
                <el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button>
            </el-form>
        </div>
        <el-table :data="rows" v-loading="loading" row-key="id" border class="management-table" :empty-text="t('common.empty')">
            <el-table-column :label="t('documentation.document.title')" min-width="260"><template #default="{row}"><div class="document-title"><strong>{{ row.title }}</strong><small>{{ row.slug }}</small><span v-if="row.summary">{{ row.summary }}</span></div></template></el-table-column>
            <el-table-column :label="t('documentation.document.type')" width="125" align="center"><template #default="{row}"><el-tag size="small" effect="plain" :type="row.type === 'development' ? 'primary' : 'success'">{{ t(`documentation.document.${row.type}`) }}</el-tag></template></el-table-column>
            <el-table-column :label="t('documentation.document.category')" min-width="150"><template #default="{row}">{{ row.category?.name || '—' }}</template></el-table-column>
            <el-table-column :label="t('documentation.document.published_at')" width="170" align="center"><template #default="{row}">{{ row.published_at ? date(row.published_at) : '—' }}</template></el-table-column>
            <el-table-column :label="t('common.sort')" prop="sort" width="85" align="center" />
            <el-table-column :label="t('common.status')" width="100" align="center"><template #default="{row}"><el-switch :model-value="row.active" :loading="busy===`status${row.id}`" :disabled="!can('documents','status') || !!busy" @change="toggleStatus(row)" /></template></el-table-column>
            <el-table-column :label="t('common.actions')" width="240" fixed="right"><template #default="{row}"><div class="table-actions">
                <el-button v-if="can('documents','detail')" link type="primary" :icon="View" @click="preview.open(row)">{{ t('documentation.document.preview') }}</el-button>
                <el-button v-if="can('documents','update')" link type="primary" :icon="Edit" @click="editor.open(row)">{{ t('common.edit') }}</el-button>
                <el-button v-if="can('documents','delete')" link type="danger" :icon="Delete" :loading="busy===`delete${row.id}`" @click="remove(row)">{{ t('common.delete') }}</el-button>
            </div></template></el-table-column>
        </el-table>
        <div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" layout="total, prev, pager, next" @current-change="load" /></div>
    </BaseCard>
    <Editor ref="editor" :categories="categories" @saved="load" />
    <Preview ref="preview" />
</template>

<style scoped>
.document-title { display: grid; gap: 3px; }
.document-title small { color: var(--el-color-primary); font-size: 12px; }
.document-title span { overflow: hidden; color: var(--el-text-color-secondary); font-size: 12px; line-height: 1.5; text-overflow: ellipsis; white-space: nowrap; }
.table-toolbar :deep(.el-tree-select) { width: 180px; }
</style>
