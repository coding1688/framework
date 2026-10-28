<script setup>
import { onMounted, ref } from 'vue';
import { Refresh } from '@element-plus/icons-vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import { queueApi } from '@/api/system/queue';
import { can } from '@/utils/permission';
import { date } from '@/utils/date';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const tab = ref('failed'), overview = ref([]), rows = ref([]), loading = ref(false), page = ref(1), total = ref(0), selected = ref([]), detail = ref(null), busy = ref('');
async function load() {
    loading.value = true;
    try {
        overview.value = (await queueApi.overview()).data || [];
        const result = await (tab.value === 'jobs' ? queueApi.jobs({ query: { page: page.value } }) : queueApi.failed({ query: { page: page.value } }));
        rows.value = result.data || []; total.value = result.total || 0;
    } catch (error) { fail(error); }
    finally { loading.value = false; }
}
function changeTab() { page.value = 1; selected.value = []; load(); }
async function retry(uuid) { busy.value = uuid; try { await queueApi.retry({ id: uuid }); notify(t('common.saved')); await load(); } catch (error) { fail(error); } finally { busy.value = ''; } }
async function retryBatch() { if (!selected.value.length) return; try { await queueApi.retryBatch({ data: { ids: selected.value.map(row => row.uuid) } }); notify(t('common.saved')); await load(); } catch (error) { fail(error); } }
async function remove(row) { try { await queueApi.delete({ id: row.uuid }); notify(t('common.saved')); await load(); } catch (error) { fail(error); } }
async function showDetail(row) { try { detail.value = (await queueApi.detail({ id: row.id })).data; } catch (error) { fail(error); } }
onMounted(load);
</script>

<template><BaseCard class="management-card" shadow="never"><div class="queue-overview"><el-card v-for="item in overview" :key="item.queue" shadow="never"><strong>{{ item.queue }}</strong><span>{{ t('system.queue.pending') }}: {{ item.pending }}</span><span>{{ t('system.queue.reserved') }}: {{ item.reserved }}</span><span>{{ t('system.queue.failed') }}: {{ item.failed }}</span></el-card></div><el-tabs v-model="tab" @tab-change="changeTab"><el-tab-pane :label="t('system.queue.failed')" name="failed" /><el-tab-pane :label="t('system.queue.jobs')" name="jobs" /></el-tabs><div class="table-toolbar"><el-button v-if="tab === 'failed' && can('queues','retryBatch')" type="primary" :disabled="!selected.length" @click="retryBatch">{{ t('system.queue.retry_batch') }}</el-button><el-button :icon="Refresh" @click="load">{{ t('common.refresh') }}</el-button></div><el-table :data="rows" v-loading="loading" border @selection-change="selected = $event"><el-table-column v-if="tab === 'failed'" type="selection" width="48" /><el-table-column label="ID" prop="id" width="80" /><el-table-column :label="t('system.queue.name')" prop="name" min-width="230" /><el-table-column :label="t('system.queue.queue')" prop="queue" width="130" /><el-table-column v-if="tab === 'jobs'" :label="t('system.queue.attempts')" prop="attempts" width="90" /><el-table-column v-else :label="t('system.queue.exception')" prop="exception" min-width="250" show-overflow-tooltip /><el-table-column :label="t('common.created_at')" width="175"><template #default="{row}">{{ date(tab === 'jobs' ? row.created_at : row.failed_at) }}</template></el-table-column><el-table-column v-if="tab === 'failed'" :label="t('common.actions')" width="250" fixed="right"><template #default="{row}"><el-button v-if="can('queues','detail')" link type="primary" @click="showDetail(row)">{{ t('common.details') }}</el-button><el-button v-if="can('queues','retry')" link type="primary" :loading="busy === row.uuid" @click="retry(row.uuid)">{{ t('system.queue.retry') }}</el-button><el-button v-if="can('queues','delete')" link type="danger" @click="remove(row)">{{ t('common.delete') }}</el-button></template></el-table-column></el-table><div class="table-pagination"><BasePagination v-model:current-page="page" :page-size="20" :total="total" @current-change="load" /></div></BaseCard><el-drawer v-model="detail" :title="t('system.queue.failure_detail')" size="55%"><pre v-if="detail" class="exception-detail">{{ detail.exception }}</pre></el-drawer></template>
