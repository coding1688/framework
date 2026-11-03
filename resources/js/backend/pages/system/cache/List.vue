<script setup>
import { computed, onMounted, ref } from 'vue';
import { ElMessageBox } from 'element-plus';
import { Refresh, Search } from '@element-plus/icons-vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import { cacheApi } from '@/api/system/cache';
import { can } from '@/utils/permission';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';
import { config } from '@/config';
import { state as identity } from '@/store/modules/user';
import { SecurityPasswordDialog, securityPasswordRequirement } from '@/plugins/securityPassword';

const groups = ref([]), loading = ref(false), busy = ref(''), securityDialog = ref();
const keyDrawer = ref(false), selectedEntry = ref(null), keyRows = ref([]), keyTotal = ref(0), keyPage = ref(1), keySearch = ref(''), keyLoading = ref(false);
const activePermission = ref('system.cache.clear');
const securityConfigured = computed(() => identity.user?.security_password_set === true);
const requiresSecurity = computed(() => !!securityPasswordRequirement(activePermission.value, config.routes));

async function load() {
    loading.value = true;
    try { groups.value = (await cacheApi.list()).data || []; }
    catch (error) { fail(error); }
    finally { loading.value = false; }
}
async function openKeys(entry) {
    selectedEntry.value = entry;
    keyDrawer.value = true;
    keyPage.value = 1;
    keySearch.value = '';
    await loadKeys();
}
async function loadKeys() {
    if (!selectedEntry.value) return;
    keyLoading.value = true;
    try {
        const result = await cacheApi.keys({ query: { key: selectedEntry.value.key, search: keySearch.value || undefined, page: keyPage.value } });
        keyRows.value = result.data || [];
        keyTotal.value = result.total || 0;
    } catch (error) { fail(error); }
    finally { keyLoading.value = false; }
}
function searchKeys() {
    keyPage.value = 1;
    loadKeys();
}
function operationKey(action, entry, cacheRow = null) {
    return `${action}:${entry.key}:${cacheRow?.key || ''}`;
}
async function mutate(action, entry, cacheRow = null) {
    try {
        const name = cacheRow?.label || entry.name;
        const description = t(`system.cache.confirm_${action}`).replace(':name', name);
        await ElMessageBox.confirm(description, t('common.confirm'), { type: 'warning' });
        activePermission.value = `system.cache.${action}`;
        busy.value = operationKey(action, entry, cacheRow);
        const data = { key: entry.key, ...(cacheRow ? { cache_key: cacheRow.key } : {}) };
        if (requiresSecurity.value) {
            const confirmation = await securityDialog.value.open((password, context) => cacheApi[action]({ data: { ...data, security_password: password }, signal: context?.signal }), {
                title: t(`system.cache.${{ clear: 'invalidate', refresh: 'refresh_key', rebuild: 'rebuild_key' }[action]}`), description,
            });
            if (!confirmation.confirmed) return;
        } else {
            await cacheApi[action]({ data });
        }
        notify(t(`system.cache.${{ clear: 'invalidated', refresh: 'refreshed', rebuild: 'rebuilt' }[action]}`));
        if (action === 'clear') {
            await load();
        } else {
            if (action === 'refresh' && keyRows.value.length === 1 && keyPage.value > 1) keyPage.value--;
            await loadKeys();
        }
    } catch (error) { if (!['cancel', 'close'].includes(error)) fail(error); }
    finally { busy.value = ''; }
}
onMounted(load);
</script>

<template>
    <BaseCard class="management-card cache-page" shadow="never">
        <div class="table-toolbar"><div><strong>{{ t('system.cache.title') }}</strong><p>{{ t('system.cache.hint') }}</p></div><el-button :icon="Refresh" :loading="loading" @click="load">{{ t('common.refresh') }}</el-button></div>
        <section v-for="group in groups" :key="group.key" class="cache-group">
            <h2>{{ group.name }}</h2>
            <el-table :data="group.entries" row-key="key" border>
                <el-table-column :label="t('system.cache.cache_item')" min-width="240"><template #default="{ row }"><strong>{{ row.name }}</strong><small>{{ row.key }}</small></template></el-table-column>
                <el-table-column :label="t('system.cache.description')" prop="description" min-width="300" show-overflow-tooltip />
                <el-table-column :label="t('system.cache.store')" prop="store" width="130" />
                <el-table-column :label="t('system.cache.ttl')" width="130"><template #default="{ row }">{{ row.ttl === 0 ? t('system.cache.disabled') : `${row.ttl}s` }}</template></el-table-column>
                <el-table-column :label="t('common.actions')" width="250" fixed="right"><template #default="{ row }">
                    <el-button v-if="can('caches','keys')" link type="primary" @click="openKeys(row)">{{ t('system.cache.manage_keys') }}</el-button>
                    <el-button v-if="can('caches','clear')" link type="danger" :loading="busy === operationKey('clear', row)" :disabled="!!busy" @click="mutate('clear', row)">{{ t('system.cache.invalidate') }}</el-button>
                </template></el-table-column>
            </el-table>
        </section>
        <el-empty v-if="!loading && !groups.length" :description="t('common.empty')" />
    </BaseCard>

    <el-drawer v-model="keyDrawer" class="cache-key-drawer" :title="selectedEntry?.name || t('system.cache.manage_keys')" size="70%">
        <div class="key-toolbar">
            <el-input v-model="keySearch" :placeholder="t('system.cache.search_keys')" clearable @keyup.enter="searchKeys" @clear="searchKeys" />
            <el-button type="primary" :icon="Search" @click="searchKeys">{{ t('common.search') }}</el-button>
            <el-button :icon="Refresh" :loading="keyLoading" @click="loadKeys">{{ t('common.refresh') }}</el-button>
        </div>
        <el-table :data="keyRows" v-loading="keyLoading" row-key="key" border :empty-text="t('system.cache.empty_keys')">
            <el-table-column :label="t('system.cache.cache_key')" min-width="320"><template #default="{ row }"><strong>{{ row.label }}</strong><small>{{ row.key }}</small></template></el-table-column>
            <el-table-column :label="t('common.status')" width="110"><template #default="{ row }"><el-tag :type="row.exists ? 'success' : 'info'">{{ t(row.exists ? 'system.cache.cached' : 'system.cache.expired') }}</el-tag></template></el-table-column>
            <el-table-column :label="t('common.actions')" width="210" fixed="right"><template #default="{ row }">
                <el-button v-if="can('caches','refresh')" link type="primary" :loading="busy === operationKey('refresh', selectedEntry, row)" :disabled="!!busy" @click="mutate('refresh', selectedEntry, row)">{{ t('system.cache.refresh_key') }}</el-button>
                <el-button v-if="can('caches','rebuild')" link type="warning" :loading="busy === operationKey('rebuild', selectedEntry, row)" :disabled="!!busy" @click="mutate('rebuild', selectedEntry, row)">{{ t('system.cache.rebuild_key') }}</el-button>
            </template></el-table-column>
        </el-table>
        <div class="table-pagination"><BasePagination v-model:current-page="keyPage" :page-size="20" :total="keyTotal" @current-change="loadKeys" /></div>
    </el-drawer>
    <SecurityPasswordDialog ref="securityDialog" :configured="securityConfigured" :setup-href="config.pages.profile" />
</template>

<style scoped>
.cache-page .table-toolbar { align-items: flex-start; }
.cache-page .table-toolbar p { margin: 6px 0 0; color: var(--el-text-color-secondary); font-size: 12px; }
.cache-group + .cache-group { margin-top: 26px; }
.cache-group h2 { margin: 0 0 12px; font-size: 16px; }
.cache-group strong, .cache-group small, .el-table strong, .el-table small { display: block; }
.cache-group small, .el-table small { margin-top: 4px; color: var(--el-text-color-secondary); overflow-wrap: anywhere; }
.key-toolbar { display: flex; gap: 10px; margin-bottom: 16px; }
.key-toolbar .el-input { max-width: 420px; }
.table-pagination { display: flex; justify-content: flex-end; margin-top: 16px; }
@media (max-width: 768px) {
    :global(.cache-key-drawer) { width: 100% !important; }
    .key-toolbar { flex-wrap: wrap; }
}
</style>
