<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Delete, Edit, Plus, Refresh, Search } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import BaseCard from '@/components/ui/BaseCard.vue';
import Editor from './components/Editor.vue';
import { documentCategoryApi } from '@/api/content/documentCategory';
import { buildCategoryTree } from '../categoryTree';
import { can } from '@/utils/permission';
import { date } from '@/utils/date';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const rows = ref([]), categories = ref([]), loading = ref(false), busy = ref(''), editor = ref();
const filters = reactive({ q: '', type: '', active: '' });
const treeRows = computed(() => buildCategoryTree(rows.value));

async function load() {
    loading.value = true;
    try {
        const result = await documentCategoryApi.list({ query: { ...filters } });
        rows.value = result.data || [];
    } catch (error) { fail(error); }
    finally { loading.value = false; }
}
async function loadOptions() {
    if (!can('documentCategories', 'options')) return;
    try { categories.value = (await documentCategoryApi.options()).data || []; }
    catch (error) { fail(error); }
}
function search() { load(); }
function reset() { Object.keys(filters).forEach(key => { filters[key] = ''; }); load(); }
async function toggleStatus(row) {
    busy.value = `status${row.id}`;
    try {
        await documentCategoryApi.status({ id: row.id, data: { active: !row.active } });
        notify(t('common.saved'));
        await load();
    } catch (error) { fail(error); }
    finally { busy.value = ''; }
}
async function remove(row) {
    try {
        await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), { type: 'warning' });
        busy.value = `delete${row.id}`;
        await documentCategoryApi.delete({ id: row.id });
        notify(t('common.saved'));
        await Promise.all([load(), loadOptions()]);
    } catch (error) { if (error !== 'cancel' && error !== 'close') fail(error); }
    finally { busy.value = ''; }
}
function saved() { Promise.all([load(), loadOptions()]); }
onMounted(() => { loadOptions(); load(); });
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar">
            <div class="table-toolbar__actions"><el-button v-if="can('documentCategories','create')" type="primary" :icon="Plus" @click="editor.open()">{{ t('common.create') }}</el-button></div>
            <el-form inline @submit.prevent="search">
                <el-form-item><el-input v-model="filters.q" clearable maxlength="128" :placeholder="t('documentation.category.search_placeholder')" /></el-form-item>
                <el-form-item><el-select v-model="filters.type" clearable :placeholder="t('documentation.category.type')"><el-option :label="t('documentation.category.development')" value="development" /><el-option :label="t('documentation.category.usage')" value="usage" /></el-select></el-form-item>
                <el-form-item><el-select v-model="filters.active" clearable :placeholder="t('common.all')"><el-option :label="t('common.active')" :value="1" /><el-option :label="t('common.inactive')" :value="0" /></el-select></el-form-item>
                <el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button>
            </el-form>
        </div>
        <el-table :data="treeRows" v-loading="loading" row-key="id" border :tree-props="{children: 'children'}" class="management-table" :empty-text="t('common.empty')">
            <el-table-column :label="t('documentation.category.name')" min-width="240" class-name="category-name-column"><template #default="{row}"><div class="category-name"><strong>{{ row.name }}</strong><small>{{ row.slug }}</small></div></template></el-table-column>
            <el-table-column :label="t('documentation.category.type')" width="130" align="center"><template #default="{row}"><el-tag size="small" effect="plain" :type="row.type === 'development' ? 'primary' : 'success'">{{ t(`documentation.category.${row.type}`) }}</el-tag></template></el-table-column>
            <el-table-column :label="t('documentation.category.description')" min-width="240"><template #default="{row}">{{ row.description || '—' }}</template></el-table-column>
            <el-table-column :label="t('common.sort')" prop="sort" width="90" align="center" />
            <el-table-column :label="t('common.status')" width="100" align="center"><template #default="{row}"><el-switch :model-value="row.active" :loading="busy===`status${row.id}`" :disabled="!can('documentCategories','status') || !!busy" @change="toggleStatus(row)" /></template></el-table-column>
            <el-table-column :label="t('common.created_at')" width="170" align="center"><template #default="{row}">{{ date(row.created_at) }}</template></el-table-column>
            <el-table-column :label="t('common.actions')" width="270" fixed="right"><template #default="{row}"><div class="table-actions">
                <el-button v-if="can('documentCategories','create')" link type="primary" :icon="Plus" @click="editor.open(null, {type: row.type, parent_id: row.id})">{{ t('documentation.category.create_child') }}</el-button>
                <el-button v-if="can('documentCategories','update')" link type="primary" :icon="Edit" @click="editor.open(row)">{{ t('common.edit') }}</el-button>
                <el-button v-if="can('documentCategories','delete')" link type="danger" :icon="Delete" :loading="busy===`delete${row.id}`" @click="remove(row)">{{ t('common.delete') }}</el-button>
            </div></template></el-table-column>
        </el-table>
    </BaseCard>
    <Editor ref="editor" :categories="categories" @saved="saved" />
</template>

<style scoped>
:deep(.category-name-column .cell) { display: flex; align-items: center; }
:deep(.category-name-column .el-table__indent) { flex: 0 0 auto; }
:deep(.category-name-column .el-table__expand-icon) { display: inline-flex; flex: 0 0 24px; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 4px; border-radius: 5px; }
:deep(.category-name-column .el-table__expand-icon:hover) { background: var(--el-fill-color-light); color: var(--el-color-primary); }
.category-name { display: grid; flex: 1; gap: 3px; min-width: 0; }
.category-name small { color: var(--el-text-color-secondary); font-size: 12px; }
</style>
