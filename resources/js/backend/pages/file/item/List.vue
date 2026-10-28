<script setup>
import { onMounted, ref } from 'vue';
import { Delete, Document, Link, Plus, Refresh, Search, View } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import UploadDialog from './components/UploadDialog.vue';
import DetailDialog from './components/DetailDialog.vue';
import { fileApi } from '@/api/file/file';
import { can } from '@/utils/permission';
import { date } from '@/utils/date';
import { copyText } from '@/utils/clipboard';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const rows = ref([]), loading = ref(false), busy = ref(''), page = ref(1), total = ref(0);
const q = ref(''), disk = ref(''), type = ref(''), mime = ref('');
const options = ref({ providers: { public: { provider: 'local', enabled: true } }, types: [], default_disk: 'public', max_files: 16, max_size_kb: 32768 });
const uploader = ref(), details = ref();

async function load() {
    loading.value = true;
    try {
        const result = await fileApi.list({ query: { q: q.value, disk: disk.value, type: type.value, mime: mime.value, page: page.value } });
        rows.value = result.data || [];
        total.value = result.total ?? rows.value.length;
    } catch (error) { fail(error); }
    finally { loading.value = false; }
}
async function loadOptions() {
    if (!can('files', 'options')) return;
    try { options.value = (await fileApi.options()).data; }
    catch (error) { fail(error); }
}
function reset() { q.value = ''; disk.value = ''; type.value = ''; mime.value = ''; page.value = 1; load(); }
function provider(row) { return row.provider === 'local_private' ? `${t('file.item.provider_local')} (${t('file.item.private')})` : t(`file.item.provider_${row.provider}`); }
function size(value) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let result = Number(value || 0), index = 0;
    while (result >= 1024 && index < units.length - 1) { result /= 1024; index++; }
    return `${result.toFixed(index ? 2 : 0)} ${units[index]}`;
}
async function copy(row) {
    if (!row.url) return;
    try {
        if (!await copyText(row.url)) throw new Error(t('common.error'));
        notify(t('file.item.url_copied'));
    } catch (error) { fail(error); }
}
async function remove(row) {
    try {
        await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), { type: 'warning' });
        busy.value = `delete${row.id}`;
        await fileApi.delete({ id: row.id });
        notify(t('common.saved'));
        load();
    } catch (error) { if (error !== 'cancel' && error !== 'close') fail(error); }
    finally { busy.value = ''; }
}
onMounted(() => { load(); loadOptions(); });
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar">
            <div class="table-toolbar__actions"><el-button v-if="can('files','upload') && can('files','options')" type="primary" :icon="Plus" @click="uploader.open()">{{ t('file.item.upload_files') }}</el-button></div>
            <el-form inline @submit.prevent="load">
                <el-form-item><el-input v-model="q" clearable :placeholder="t('file.item.search_placeholder')" /></el-form-item>
                <el-form-item><el-select v-model="disk" clearable :placeholder="t('file.item.storage_provider')"><el-option v-for="(item, key) in options.providers" :key="key" :value="key" :label="provider(item)" /></el-select></el-form-item>
                <el-form-item><el-select v-model="type" clearable :placeholder="t('file.item.type')"><el-option v-for="item in options.types" :key="item.value" :value="item.value" :label="item.label" /></el-select></el-form-item>
                <el-form-item><el-select v-model="mime" clearable :placeholder="t('file.item.file_type')"><el-option value="image/" :label="t('file.item.images')" /><el-option value="video/" :label="t('file.item.videos')" /><el-option value="audio/" :label="t('file.item.audio')" /><el-option value="application/" :label="t('file.item.documents')" /></el-select></el-form-item>
                <el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button>
            </el-form>
        </div>
        <el-table :data="rows" v-loading="loading" border class="management-table">
            <el-table-column :label="t('file.item.preview')" width="80" align="center"><template #default="{row}"><a v-if="row.url" :href="row.url" target="_blank" class="file-preview"><img v-if="row.is_image" :src="row.url" :alt="row.original_name"><el-icon v-else><Document /></el-icon></a><el-icon v-else class="file-preview file-preview--private"><Document /></el-icon></template></el-table-column>
            <el-table-column :label="t('file.item.original_name') + ' / ' + t('file.item.mime_type')" min-width="220"><template #default="{row}"><div class="file-list-primary" :title="row.original_name">{{ row.original_name }}</div><div class="file-list-secondary">{{ row.mime_type || '—' }}</div></template></el-table-column>
            <el-table-column :label="t('file.item.storage_provider') + ' / ' + t('file.item.type')" min-width="160"><template #default="{row}"><el-tag effect="plain">{{ provider(row) }}</el-tag><div class="file-list-secondary">{{ row.type_label || '—' }}</div></template></el-table-column>
            <el-table-column :label="t('file.item.size') + ' / ' + t('file.item.references_count')" min-width="140"><template #default="{row}"><div>{{ size(row.size) }}</div><div class="file-list-secondary">{{ t('file.item.references_count') }}: <el-button v-if="can('files','detail')" link type="primary" @click="details.open(row)">{{ row.references_count || 0 }}</el-button><span v-else>{{ row.references_count || 0 }}</span></div></template></el-table-column>
            <el-table-column :label="t('file.item.uploader') + ' / ' + t('common.created_at')" min-width="190" align="center"><template #default="{row}"><div>{{ row.uploader?.name || '—' }}</div><div class="file-list-secondary">{{ date(row.created_at) }}</div></template></el-table-column>
            <el-table-column :label="t('common.actions')" width="220" fixed="right"><template #default="{row}"><div class="table-actions"><el-button v-if="can('files','detail')" link type="primary" :icon="View" @click="details.open(row)">{{ t('common.details') }}</el-button><el-button v-if="row.url" link type="primary" :icon="Link" @click="copy(row)">{{ t('file.item.copy_url') }}</el-button><el-button v-if="can('files','delete')" link type="danger" :icon="Delete" :disabled="row.references_count > 0" :loading="busy===`delete${row.id}`" @click="remove(row)">{{ t('common.delete') }}</el-button></div></template></el-table-column>
        </el-table>
        <div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" @current-change="load" /></div>
    </BaseCard>
    <UploadDialog ref="uploader" :options="options" @saved="load" />
    <DetailDialog ref="details" />
</template>
