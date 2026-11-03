<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Delete, Edit, Plus, Refresh, Search, VideoPlay } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import StatusTag from '@/components/StatusTag.vue';
import Editor from './components/Editor.vue';
import { scheduleApi } from '@/api/system/schedule';
import { can } from '@/utils/permission';
import { date } from '@/utils/date';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const tab = ref('tasks'), tasks = ref([]), runs = ref([]), options = ref({ commands: [], tasks: [], timezones: [] });
const loading = ref(false), busy = ref(''), editor = ref(), taskPage = ref(1), taskTotal = ref(0), runPage = ref(1), runTotal = ref(0);
const taskFilters = reactive({ q: '', active: '' }), runFilters = reactive({ system_schedule_task_id: '', status: '', trigger: '' });
const logVisible = ref(false), log = ref(null), settingsBusy = ref(false);
const settings = reactive({ enabled: true, timezone: 'UTC', log_retention_days: 90 });

async function loadOptions() {
    try { options.value = (await scheduleApi.options()).data || options.value; }
    catch (error) { fail(error); }
}
async function loadTasks() {
    loading.value = true;
    try { const result = await scheduleApi.list({ query: { ...taskFilters, page: taskPage.value } }); tasks.value = result.data || []; taskTotal.value = result.total || 0; }
    catch (error) { fail(error); }
    finally { loading.value = false; }
}
async function loadRuns() {
    loading.value = true;
    try { const result = await scheduleApi.runs({ query: { ...runFilters, page: runPage.value } }); runs.value = result.data || []; runTotal.value = result.total || 0; }
    catch (error) { fail(error); }
    finally { loading.value = false; }
}
async function loadSettings() {
    loading.value = true;
    try { Object.assign(settings, (await scheduleApi.settings()).data || {}); }
    catch (error) { fail(error); }
    finally { loading.value = false; }
}
function loadCurrent() { return tab.value === 'runs' ? loadRuns() : tab.value === 'settings' ? loadSettings() : loadTasks(); }
function changeTab() { loadCurrent(); }
function searchTasks() { taskPage.value = 1; loadTasks(); }
function resetTasks() { Object.assign(taskFilters, { q: '', active: '' }); searchTasks(); }
function searchRuns() { runPage.value = 1; loadRuns(); }
function resetRuns() { Object.assign(runFilters, { system_schedule_task_id: '', status: '', trigger: '' }); searchRuns(); }
async function saved() { await Promise.all([loadOptions(), loadTasks()]); }
async function run(row) {
    busy.value = `run-${row.id}`;
    try { await scheduleApi.run({ id: row.id }); notify(t('system.schedule.queued')); await loadTasks(); }
    catch (error) { fail(error); }
    finally { busy.value = ''; }
}
async function toggle(row) {
    busy.value = `status-${row.id}`;
    try { await scheduleApi.status({ id: row.id, data: { active: !row.active } }); notify(t('common.saved')); await loadTasks(); }
    catch (error) { fail(error); }
    finally { busy.value = ''; }
}
async function remove(row) {
    try {
        await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), { type: 'warning' });
        await scheduleApi.delete({ id: row.id }); notify(t('common.saved')); await saved();
    } catch (error) { if (!['cancel', 'close'].includes(error)) fail(error); }
}
async function showLog(row) {
    try { log.value = (await scheduleApi.runDetail({ id: row.id })).data; logVisible.value = true; }
    catch (error) { fail(error); }
}
async function saveSettings() {
    settingsBusy.value = true;
    try { Object.assign(settings, (await scheduleApi.updateSettings({ data: { ...settings } })).data || {}); notify(t('common.saved')); }
    catch (error) { fail(error); }
    finally { settingsBusy.value = false; }
}
function statusType(status) { return status === 'success' ? 'success' : status === 'failed' ? 'danger' : status === 'running' ? 'warning' : 'info'; }
function duration(milliseconds) { return milliseconds === null || milliseconds === undefined ? '—' : milliseconds < 1000 ? `${milliseconds} ms` : `${(milliseconds / 1000).toFixed(2)} s`; }
onMounted(async () => { await loadOptions(); await loadTasks(); });
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <el-tabs v-model="tab" @tab-change="changeTab">
            <el-tab-pane :label="t('system.schedule.tasks')" name="tasks" />
            <el-tab-pane v-if="can('schedules','runs')" :label="t('system.schedule.history')" name="runs" />
            <el-tab-pane v-if="can('schedules','settings')" :label="t('system.schedule.settings')" name="settings" />
        </el-tabs>

        <template v-if="tab === 'tasks'">
            <div class="table-toolbar">
                <el-button v-if="can('schedules','create')" type="primary" :icon="Plus" @click="editor.open()">{{ t('common.create') }}</el-button>
                <el-form inline @submit.prevent="searchTasks"><el-form-item><el-input v-model="taskFilters.q" clearable maxlength="128" :placeholder="t('system.schedule.search_hint')" /></el-form-item><el-form-item><el-select v-model="taskFilters.active" clearable :placeholder="t('common.status')"><el-option :label="t('common.active')" :value="1" /><el-option :label="t('common.inactive')" :value="0" /></el-select></el-form-item><el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="resetTasks">{{ t('common.reset') }}</el-button></el-form>
            </div>
            <el-table :data="tasks" v-loading="loading" row-key="id" border :empty-text="t('common.empty')">
                <el-table-column :label="t('system.schedule.task')" min-width="220"><template #default="{row}"><strong>{{ row.name }}</strong><div><small>{{ row.key }}</small></div></template></el-table-column>
                <el-table-column :label="`${t('system.schedule.command')} / ${t('system.schedule.cron')}`" min-width="240"><template #default="{row}"><div>{{ row.command }}</div><div><small>{{ row.cron }}</small></div></template></el-table-column>
                <el-table-column :label="t('system.schedule.timezone')" prop="timezone" width="130" />
                <el-table-column :label="t('system.schedule.next_run')" width="175"><template #default="{row}">{{ row.next_run_at ? date(row.next_run_at) : '—' }}</template></el-table-column>
                <el-table-column :label="t('system.schedule.last_status')" width="115"><template #default="{row}"><el-tag v-if="row.last_run" :type="statusType(row.last_run.status)">{{ t(`system.schedule.${row.last_run.status}`) }}</el-tag><span v-else>—</span></template></el-table-column>
                <el-table-column :label="t('common.status')" width="100"><template #default="{row}"><el-switch v-if="can('schedules','status')" :model-value="row.active" :loading="busy === `status-${row.id}`" @change="toggle(row)" /><StatusTag v-else :active="row.active" /></template></el-table-column>
                <el-table-column :label="t('common.actions')" fixed="right" width="270"><template #default="{row}"><div class="table-actions"><el-button v-if="can('schedules','run')" link type="primary" :icon="VideoPlay" :disabled="row.running && row.without_overlapping" :loading="busy === `run-${row.id}`" @click="run(row)">{{ t('system.schedule.run') }}</el-button><el-button v-if="can('schedules','update')" link type="primary" :icon="Edit" @click="editor.open(row)">{{ t('common.edit') }}</el-button><el-button v-if="can('schedules','delete')" link type="danger" :icon="Delete" @click="remove(row)">{{ t('common.delete') }}</el-button></div></template></el-table-column>
            </el-table>
            <div class="table-pagination"><BasePagination v-model:current-page="taskPage" :page-size="20" :total="taskTotal" @current-change="loadTasks" /></div>
        </template>

        <template v-else-if="tab === 'runs'">
            <div class="table-toolbar"><div /><el-form inline @submit.prevent="searchRuns"><el-form-item><el-select v-model="runFilters.system_schedule_task_id" clearable filterable :placeholder="t('system.schedule.task')"><el-option v-for="task in options.tasks" :key="task.value" :label="task.label" :value="task.value" /></el-select></el-form-item><el-form-item><el-select v-model="runFilters.status" clearable :placeholder="t('common.status')"><el-option v-for="status in ['pending','running','success','failed']" :key="status" :label="t(`system.schedule.${status}`)" :value="status" /></el-select></el-form-item><el-form-item><el-select v-model="runFilters.trigger" clearable :placeholder="t('system.schedule.trigger')"><el-option :label="t('system.schedule.schedule')" value="schedule" /><el-option :label="t('system.schedule.manual')" value="manual" /></el-select></el-form-item><el-button type="primary" :icon="Search" native-type="submit">{{ t('common.search') }}</el-button><el-button :icon="Refresh" @click="resetRuns">{{ t('common.reset') }}</el-button></el-form></div>
            <el-table :data="runs" v-loading="loading" row-key="id" border :empty-text="t('common.empty')">
                <el-table-column :label="t('system.schedule.task')" prop="task_name" min-width="200" />
                <el-table-column :label="t('system.schedule.command')" prop="command" min-width="180" show-overflow-tooltip />
                <el-table-column :label="t('system.schedule.trigger')" width="105"><template #default="{row}">{{ t(`system.schedule.${row.trigger}`) }}</template></el-table-column>
                <el-table-column :label="t('common.status')" width="105"><template #default="{row}"><el-tag :type="statusType(row.status)">{{ t(`system.schedule.${row.status}`) }}</el-tag></template></el-table-column>
                <el-table-column :label="t('system.schedule.started_at')" width="175"><template #default="{row}">{{ date(row.started_at) }}</template></el-table-column>
                <el-table-column :label="t('system.schedule.finished_at')" width="175"><template #default="{row}">{{ date(row.finished_at) }}</template></el-table-column>
                <el-table-column :label="t('system.schedule.duration')" width="110"><template #default="{row}">{{ duration(row.duration_ms) }}</template></el-table-column>
                <el-table-column :label="t('common.actions')" width="100" fixed="right"><template #default="{row}"><el-button v-if="can('schedules','runDetail')" link type="primary" @click="showLog(row)">{{ t('common.details') }}</el-button></template></el-table-column>
            </el-table>
            <div class="table-pagination"><BasePagination v-model:current-page="runPage" :page-size="20" :total="runTotal" @current-change="loadRuns" /></div>
        </template>

        <el-form v-else v-loading="loading" label-position="top" class="schedule-settings" @submit.prevent="saveSettings">
            <el-form-item :label="t('system.schedule.automatic_enabled')"><el-switch v-model="settings.enabled" /></el-form-item>
            <el-form-item :label="t('system.schedule.timezone')" required><el-select v-model="settings.timezone" filterable><el-option v-for="timezone in options.timezones" :key="timezone" :label="timezone" :value="timezone" /></el-select></el-form-item>
            <el-form-item :label="t('system.schedule.retention_days')" required><el-input-number v-model="settings.log_retention_days" :min="1" :max="3650" /></el-form-item>
            <el-button v-if="can('schedules','updateSettings')" type="primary" :loading="settingsBusy" native-type="submit">{{ t('common.save') }}</el-button>
        </el-form>
    </BaseCard>
    <Editor ref="editor" :options="options" @saved="saved" />
    <BaseDialog v-model="logVisible" :title="t('system.schedule.log_detail')" width="76vw">
        <template v-if="log"><el-descriptions :column="2" border><el-descriptions-item :label="t('system.schedule.task')">{{ log.task_name }}</el-descriptions-item><el-descriptions-item :label="t('system.schedule.command')">{{ log.command }}</el-descriptions-item><el-descriptions-item :label="t('system.schedule.trigger')">{{ t(`system.schedule.${log.trigger}`) }}</el-descriptions-item><el-descriptions-item :label="t('system.schedule.administrator')">{{ log.administrator?.name || '—' }}</el-descriptions-item><el-descriptions-item :label="t('system.schedule.exit_code')">{{ log.exit_code ?? '—' }}</el-descriptions-item><el-descriptions-item :label="t('system.schedule.duration')">{{ duration(log.duration_ms) }}</el-descriptions-item></el-descriptions><el-alert v-if="log.error" class="log-error" type="error" :title="log.error" :closable="false" /><h3>{{ t('system.schedule.output') }}</h3><pre class="schedule-log-output">{{ log.output || t('system.schedule.no_output') }}</pre></template>
        <template #footer><el-button @click="logVisible = false">{{ t('common.close') }}</el-button></template>
    </BaseDialog>
</template>

<style scoped>
.schedule-settings { max-width: 560px; }
.schedule-settings .el-select { width: 100%; }
.log-error { margin-top: 18px; }
.schedule-log-output { max-height: 460px; overflow: auto; padding: 16px; border-radius: 6px; background: var(--el-fill-color-darker); color: var(--el-text-color-primary); white-space: pre-wrap; word-break: break-word; }
</style>
