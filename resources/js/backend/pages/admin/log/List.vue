<script setup>
import { logApi } from '@/api/admin/log';

import BasePagination from '@/components/ui/BasePagination.vue';
import BaseAlert from '@/components/ui/BaseAlert.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import { ref, reactive, watch, onMounted, onUnmounted } from 'vue';
import { Search, Refresh } from '@element-plus/icons-vue';
import { t } from '@/locales';
import { date } from '@/utils/date';
import { fail } from '@/utils/feedback';
import Editor from '@/pages/admin/log/components/Detail.vue';

const props = defineProps({ revision: Number });
const emit = defineEmits(['identity-changed']);
const rows = ref([]), loading = ref(false), error = ref(''), busy = ref(''), editor = ref(null);
const filters = reactive({ q: '' });
const page = ref(1), total = ref(0), lastPage = ref(1);
let requestId = 0, controller;
async function load() {
    const id = ++requestId;
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    error.value = '';
    try {
        const result = await logApi.list({ query: { ...filters, page: page.value }, signal: controller.signal });
        if (id !== requestId) return;
        rows.value = result.data;
        total.value = result.total ?? rows.value.length;
        lastPage.value = result.last_page || 1;
        if (page.value > lastPage.value) { page.value = lastPage.value; await load(); }
    } catch (exception) {
        if (id === requestId && exception.name !== 'AbortError' && !exception.redirected) error.value = exception.message;
    } finally { if (id === requestId) loading.value = false; }
}
function search() { page.value = 1; load(); }
function reset() { Object.keys(filters).forEach(key => { filters[key] = ''; }); search(); }
async function openEditor(action, row = null) {
    if (busy.value) return;
    busy.value = action + (row?.id || '');
    try { await editor.value.open(action, row); } catch (exception) { fail(exception); } finally { busy.value = ''; }
}
watch(() => props.revision, load);
onMounted(load);
onUnmounted(() => { requestId++; controller?.abort(); });
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar">
            <div class="table-toolbar__actions"></div>
            <el-form inline @submit.prevent="search">
                <el-form-item><el-input v-model="filters.q" :placeholder="t('common.search')" :aria-label="t('common.search')" maxlength="128" clearable /></el-form-item>
                <el-button type="primary" :icon="Search" native-type="submit" :loading="loading">{{ t('common.search') }}</el-button>
                <el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button>
            </el-form>
        </div>
        <BaseAlert v-if="error" :title="error" type="error" :closable="false" />
        <el-table :data="rows" v-loading="loading" row-key="id" border :empty-text="t('common.empty')" class="management-table">
            <el-table-column prop="actor" :label="t('admin.log.actor')" min-width="140"/><el-table-column prop="action" :label="t('admin.log.action')" min-width="230"/><el-table-column prop="subject" :label="t('admin.log.subject')" min-width="100"/><el-table-column prop="ip" label="IP" min-width="135"/><el-table-column :label="t('admin.log.time')" min-width="180" align="center"><template #default="{row}">{{ date(row.created_at) }}</template></el-table-column>
            <el-table-column :label="t('common.actions')" fixed="right" min-width="100"><template #default="{row}"><div class="table-actions">
                <el-button link type="primary" :disabled="!!busy" :loading="busy==='detail'+row.id" @click="openEditor('detail',row)">{{ t('common.details') }}</el-button>
            </div></template></el-table-column>
        </el-table>
        <div class="table-pagination">
            <BasePagination v-model:current-page="page" :page-size="20" :total="total" layout="total, prev, pager, next, jumper" @current-change="load" />
        </div>
    </BaseCard>
    <Editor ref="editor" />
</template>
