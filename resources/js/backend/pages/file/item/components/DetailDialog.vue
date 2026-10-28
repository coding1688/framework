<script setup>
import { ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import { fileApi } from '@/api/file/file';
import { date } from '@/utils/date';
import { fail } from '@/utils/feedback';
import { t } from '@/locales';

const visible = ref(false);
const loading = ref(false);
const item = ref({});
async function open(row) {
    visible.value = true;
    loading.value = true;
    try { item.value = (await fileApi.detail({ id: row.id })).data; }
    catch (error) { fail(error); visible.value = false; }
    finally { loading.value = false; }
}
function size(value) {
    if (value == null) return '—';
    const units = ['B', 'KB', 'MB', 'GB'];
    let result = Number(value), index = 0;
    while (result >= 1024 && index < units.length - 1) { result /= 1024; index++; }
    return `${result.toFixed(index ? 2 : 0)} ${units[index]}`;
}
function provider(value) { return value === 'local_private' ? `${t('file.item.provider_local')} (${t('file.item.private')})` : t(`file.item.provider_${value}`); }
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t('file.item.file_details')" width="76vw" :loading="loading">
        <div v-if="item.is_image && item.url" class="file-detail-preview"><img :src="item.url" :alt="item.original_name"></div>
        <el-descriptions :column="2" border>
            <el-descriptions-item :label="t('file.item.original_name')">{{ item.original_name || '—' }}</el-descriptions-item>
            <el-descriptions-item :label="t('file.item.storage_provider')">{{ provider(item.provider) }}</el-descriptions-item>
            <el-descriptions-item :label="t('file.item.type')">{{ item.type_label || '—' }}</el-descriptions-item>
            <el-descriptions-item :label="t('file.item.mime_type')">{{ item.mime_type || '—' }}</el-descriptions-item>
            <el-descriptions-item :label="t('file.item.size')">{{ size(item.size) }}</el-descriptions-item>
            <el-descriptions-item :label="t('file.item.references_count')">{{ item.references_count || 0 }}</el-descriptions-item>
            <el-descriptions-item :label="t('file.item.dimensions')">{{ item.width ? `${item.width} × ${item.height}` : '—' }}</el-descriptions-item>
            <el-descriptions-item :label="t('file.item.visibility')">{{ t(`file.item.${item.visibility}`) }}</el-descriptions-item>
            <el-descriptions-item :label="t('file.item.uploader')">{{ item.uploader?.name || '—' }}</el-descriptions-item>
            <el-descriptions-item :label="t('common.created_at')">{{ date(item.created_at) }}</el-descriptions-item>
            <el-descriptions-item :label="t('file.item.path')" :span="2">{{ item.path || '—' }}</el-descriptions-item>
            <el-descriptions-item :label="t('file.item.checksum')" :span="2">{{ item.checksum || '—' }}</el-descriptions-item>
        </el-descriptions>
        <el-table v-if="item.references?.length" :data="item.references" border size="small" style="margin-top:12px">
            <el-table-column prop="reference_type" :label="t('file.item.reference_type')" />
            <el-table-column prop="reference_id" :label="t('file.item.reference_id')" width="100" />
            <el-table-column prop="field" :label="t('file.item.reference_field')" />
        </el-table>
        <el-table v-if="item.replicas?.length" :data="item.replicas" border size="small" style="margin-top:12px">
            <el-table-column prop="disk" :label="t('file.storage.target')" width="150" />
            <el-table-column prop="path" :label="t('file.item.path')" />
            <el-table-column :label="t('file.storage.synced_at')" width="180" align="center"><template #default="{row}">{{ date(row.synced_at) }}</template></el-table-column>
        </el-table>
        <template #footer><el-button @click="visible=false">{{ t('common.close') }}</el-button></template>
    </BaseDialog>
</template>
