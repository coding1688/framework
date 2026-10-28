<script setup>
import { computed, onActivated, onBeforeUnmount, onDeactivated, onMounted, ref } from 'vue';
import { Delete, Edit, Plus, Refresh } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import LocalizedField from '@/components/LocalizedField.vue';
import { config } from '@/config';
import { state as identity } from '@/store/modules/user';
import { SecurityPasswordDialog } from '@/plugins/securityPassword';
import { storageApi } from '@/api/file/storage';
import { can } from '@/utils/permission';
import { fail, notify } from '@/utils/feedback';
import { date } from '@/utils/date';
import { t } from '@/locales';
import '@styles/file-storage.css';

const tab = ref('storages'), rows = ref([]), tasks = ref([]), loading = ref(false), busy = ref(false), visible = ref(false), editId = ref(null), errors = ref({});
const providers = ref([]), localProviders = ref([]);
const settings = ref({ default_disk: 'public', max_upload_mb: 32, hard_max_upload_mb: 1024, allowed_extensions: [], extension_options: [] });
const extraExtension = ref(''), source = ref(''), target = ref(''), form = ref({});
const builtin = computed(() => ['public', 'local'].includes(form.value.disk) && editId.value != null);
const local = computed(() => localProviders.value.includes(form.value.provider));
const enabled = computed(() => rows.value.filter(row => row.enabled && row.configured));
const extensionOptions = computed(() => [...new Set([...settings.value.extension_options, ...settings.value.allowed_extensions])]);
const securityDialog = ref();
const securityConfigured = computed(() => identity.user?.security_password_set === true);
let timer, active = true;
function label(provider) { return t(`file.item.provider_${provider}`); }
function fieldError(key) { return errors.value[key]?.[0]; }
function stopPolling() { clearTimeout(timer); timer = null; }
function poll() {
    stopPolling();
    if (active && tasks.value.some(task => ['pending', 'running'].includes(task.status))) timer = setTimeout(loadTasks, 5000);
}
async function loadTasks() {
    if (!can('fileStorages', 'tasks')) return;
    try { tasks.value = (await storageApi.tasks()).data || []; }
    catch (error) { stopPolling(); fail(error); return; }
    poll();
}
async function load() {
    loading.value = true;
    try {
        await Promise.all([
            can('fileStorages', 'list') ? storageApi.list().then(result => { rows.value = result.data || []; providers.value = result.provider_options || []; localProviders.value = result.local_provider_options || []; }) : Promise.resolve(),
            can('fileStorages', 'settings') ? storageApi.settings().then(result => { settings.value = result.data; }) : Promise.resolve(),
            loadTasks(),
        ]);
    } catch (error) { fail(error); }
    finally { loading.value = false; }
}
function open(row) {
    editId.value = row?.id ?? null;
    form.value = row ? JSON.parse(JSON.stringify(row)) : {
        disk: '', name_i18n: { en: '', zh_CN: '', zh_TW: '', tl: '' }, provider: 'cloudflare',
        enabled: false, visibility: 'private', configuration: { directory: '', bucket: '', region: 'auto', endpoint: '', url: '', use_path_style_endpoint: false },
    };
    form.value.access_key = ''; form.value.secret_key = '';
    errors.value = {}; visible.value = true;
}
async function save() {
    busy.value = true; errors.value = {};
    try {
        const data = Object.fromEntries(['disk', 'name_i18n', 'provider', 'enabled', 'visibility', 'configuration', 'access_key', 'secret_key'].map(key => [key, form.value[key]]));
        const result = await securityDialog.value.open((security_password, { signal }) => storageApi[editId.value ? 'update' : 'create']({ id: editId.value, data: { ...data, security_password }, signal }));
        if (!result.confirmed) return;
        visible.value = false; notify(t('common.saved')); await load();
    } catch (error) { errors.value = error.fields || {}; if (errors.value['name_i18n.en']) fail(error); }
    finally { busy.value = false; }
}
async function remove(row) {
    try {
        await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), { type: 'warning' });
        const result = await securityDialog.value.open((security_password, { signal }) => storageApi.delete({ id: row.id, data: { security_password }, signal }));
        if (!result.confirmed) return;
        notify(t('common.saved')); await load();
    } catch (error) { if (error !== 'cancel' && error !== 'close') fail(error); }
}
function addExtension() {
    const value = extraExtension.value.trim().replace(/^\./, '').toLowerCase();
    if (!/^[a-z0-9]{1,16}$/.test(value)) return;
    settings.value.allowed_extensions = [...new Set([...settings.value.allowed_extensions, value])]; extraExtension.value = '';
}
async function saveSettings() {
    busy.value = true;
    try {
        const data = { default_disk: settings.value.default_disk || null, max_upload_mb: settings.value.max_upload_mb, allowed_extensions: [...settings.value.allowed_extensions] };
        const result = await securityDialog.value.open((security_password, { signal }) => storageApi.saveSettings({ data: { ...data, security_password }, signal }));
        if (!result.confirmed) return;
        settings.value = result.result.data;
        notify(t('common.saved')); await load();
    } catch (error) { fail(error); }
    finally { busy.value = false; }
}
async function synchronize() {
    try {
        await ElMessageBox.confirm(t('file.storage.sync_confirm'), t('common.confirm'), { type: 'warning' });
        busy.value = true;
        await storageApi.sync({ data: { source_disk: source.value, target_disk: target.value } });
        notify(t('file.storage.queued')); await loadTasks();
    } catch (error) { if (error !== 'cancel' && error !== 'close') fail(error); }
    finally { busy.value = false; }
}
async function retry(task) {
    busy.value = true;
    try { await storageApi.retry({ id: task.id }); notify(t('file.storage.queued')); await loadTasks(); }
    catch (error) { fail(error); }
    finally { busy.value = false; }
}
function percentage(task) { return task.status === 'completed' ? 100 : Math.min(100, Math.round(100 * (Number(task.copied) + Number(task.skipped) + Number(task.failed)) / Math.max(1, Number(task.total)))); }
onMounted(load);
onActivated(() => { active = true; poll(); });
onDeactivated(() => { active = false; stopPolling(); });
onBeforeUnmount(() => { active = false; stopPolling(); });
</script>

<template>
    <BaseCard class="management-card file-storage-page" shadow="never" v-loading="loading">
        <el-tabs v-model="tab">
            <el-tab-pane :label="t('file.storage.storages')" name="storages">
                <div class="table-toolbar"><div class="table-toolbar__actions"><el-button v-if="can('fileStorages','create')" type="primary" :icon="Plus" @click="open()">{{ t('common.create') }}</el-button></div><el-button :icon="Refresh" @click="load">{{ t('common.refresh') }}</el-button></div>
                <el-alert :title="t('file.storage.storage_hint')" type="info" :closable="false" show-icon />
                <el-table :data="rows" border size="small" class="file-storage-table">
                    <el-table-column :label="t('file.storage.name')" min-width="180"><template #default="{row}"><strong>{{ row.name }}</strong><div class="file-storage-muted">{{ row.disk }}</div></template></el-table-column>
                    <el-table-column :label="t('file.item.storage_provider')" min-width="170"><template #default="{row}">{{ label(row.provider) }}</template></el-table-column>
                    <el-table-column :label="t('file.storage.location')" min-width="220"><template #default="{row}"><div>{{ row.configuration.bucket || `storage/app/${row.configuration.directory || ''}` }}</div><div class="file-storage-muted">{{ row.configuration.url || '—' }}</div></template></el-table-column>
                    <el-table-column :label="t('file.item.visibility')" width="110"><template #default="{row}">{{ t(`file.item.${row.visibility}`) }}</template></el-table-column>
                    <el-table-column :label="t('common.status')" width="180"><template #default="{row}"><el-tag :type="row.enabled && row.configured ? 'success' : 'info'" size="small">{{ t(`file.storage.${row.enabled && row.configured ? 'enabled' : 'disabled'}`) }}</el-tag><el-tag v-if="row.is_default" size="small" class="file-storage-default">{{ t('file.storage.default') }}</el-tag></template></el-table-column>
                    <el-table-column :label="t('common.actions')" width="150" fixed="right"><template #default="{row}"><el-button v-if="can('fileStorages','update')" link type="primary" :icon="Edit" @click="open(row)">{{ t('common.edit') }}</el-button><el-button v-if="!row.builtin && !row.is_default && can('fileStorages','delete')" link type="danger" :icon="Delete" @click="remove(row)">{{ t('common.delete') }}</el-button></template></el-table-column>
                </el-table>
            </el-tab-pane>
            <el-tab-pane v-if="can('fileStorages','settings')" :label="t('file.storage.upload_rules')" name="rules">
                <el-form label-position="right" label-width="128px" :disabled="!can('fileStorages','saveSettings') || busy" class="horizontal-editor-form file-storage-settings" @submit.prevent="saveSettings">
                    <div class="file-storage-grid"><el-form-item :label="t('file.storage.default')"><el-select v-model="settings.default_disk" clearable><el-option v-for="row in enabled" :key="row.disk" :value="row.disk" :label="`${row.name} (${row.disk})`" /></el-select></el-form-item><el-form-item :label="t('file.storage.max_upload_mb')"><el-input-number v-model="settings.max_upload_mb" :min="0" :max="settings.hard_max_upload_mb" :precision="0" /><div class="file-storage-muted">{{ t('file.storage.size_hint') }}</div></el-form-item></div>
                    <el-form-item :label="t('file.storage.extensions')"><el-checkbox-group v-model="settings.allowed_extensions"><el-checkbox v-for="extension in extensionOptions" :key="extension" :value="extension">{{ extension.toUpperCase() }}</el-checkbox></el-checkbox-group></el-form-item>
                    <el-form-item :label="t('file.storage.add_extension')"><div class="file-storage-add-extension"><el-input v-model="extraExtension" maxlength="16" :placeholder="t('file.storage.add_extension')" @keydown.enter.prevent="addExtension" /><el-button :icon="Plus" :aria-label="t('file.storage.add_extension')" @click="addExtension" /></div></el-form-item>
                    <el-button v-if="can('fileStorages','saveSettings')" type="primary" native-type="submit" :loading="busy">{{ t('common.save') }}</el-button>
                </el-form>
            </el-tab-pane>
            <el-tab-pane v-if="can('fileStorages','tasks') || can('fileStorages','sync')" :label="t('file.storage.synchronization')" name="sync">
                <el-alert :title="t('file.storage.sync_hint')" type="info" :closable="false" show-icon />
                <el-form v-if="can('fileStorages','sync')" inline class="file-storage-sync-form" @submit.prevent="synchronize"><el-form-item :label="t('file.storage.source')"><el-select v-model="source"><el-option v-for="row in enabled" :key="row.disk" :value="row.disk" :label="row.name" /></el-select></el-form-item><el-form-item :label="t('file.storage.target')"><el-select v-model="target"><el-option v-for="row in enabled.filter(row => row.disk !== source)" :key="row.disk" :value="row.disk" :label="row.name" /></el-select></el-form-item><el-button type="primary" native-type="submit" :disabled="!source || !target || source === target" :loading="busy">{{ t('file.storage.start_sync') }}</el-button></el-form>
                <p class="file-storage-muted">{{ t('file.storage.worker_hint') }} <code>php artisan queue:work file-sync --queue=file-sync --timeout=180 --tries=3</code></p>
                <el-button :icon="Refresh" @click="loadTasks">{{ t('common.refresh') }}</el-button>
                <el-table :data="tasks" border size="small" class="file-storage-table"><el-table-column prop="id" label="ID" width="70" /><el-table-column :label="t('file.storage.direction')" min-width="180"><template #default="{row}">{{ row.source_disk }} → {{ row.target_disk }}</template></el-table-column><el-table-column :label="t('common.status')" width="140"><template #default="{row}"><el-tag :type="row.status === 'failed' ? 'danger' : row.status === 'completed' ? 'success' : 'info'">{{ t(`file.storage.${row.status}`) }}</el-tag></template></el-table-column><el-table-column :label="t('file.storage.progress')" min-width="160"><template #default="{row}"><el-progress :percentage="percentage(row)" :status="row.status === 'failed' ? 'exception' : row.status === 'completed' ? 'success' : undefined" /></template></el-table-column><el-table-column :label="t('file.storage.counts')" min-width="180"><template #default="{row}">{{ row.copied }} / {{ row.skipped }} / {{ row.failed }}<div class="file-storage-muted">{{ t('file.storage.total') }}: {{ row.total }}</div></template></el-table-column><el-table-column :label="t('common.created_at')" width="180" align="center"><template #default="{row}">{{ date(row.created_at) }}</template></el-table-column><el-table-column :label="t('common.actions')" width="110"><template #default="{row}"><el-button v-if="row.status === 'failed' && can('fileStorages','retry')" link type="primary" :loading="busy" @click="retry(row)">{{ t('file.storage.retry') }}</el-button></template></el-table-column><el-table-column type="expand"><template #default="{row}"><div class="file-storage-errors"><p v-for="(error,index) in row.errors || []" :key="index">{{ error.file_id ? `#${error.file_id}: ` : '' }}{{ t(`file.storage.${error.message}`) }}</p><span v-if="!row.errors?.length">—</span></div></template></el-table-column></el-table>
            </el-tab-pane>
        </el-tabs>
    </BaseCard>
    <SecurityPasswordDialog ref="securityDialog" :configured="securityConfigured" :setup-href="config.pages.profile" />
    <BaseDialog v-model="visible" :title="t(editId ? 'common.edit' : 'common.create')" width="76vw" class="file-storage-editor-dialog" :busy="busy">
        <el-form label-position="right" label-width="128px" class="horizontal-editor-form" :disabled="busy" @submit.prevent="save">
            <div class="file-storage-grid"><el-form-item :label="t('file.storage.disk')" :error="fieldError('disk')"><el-input v-model="form.disk" :disabled="editId != null" maxlength="32" /></el-form-item><el-form-item :label="t('file.item.storage_provider')" :error="fieldError('provider')"><el-select v-model="form.provider" :disabled="editId != null"><el-option v-for="provider in [...new Set([...providers,form.provider])]" :key="provider" :value="provider" :label="label(provider)" /></el-select></el-form-item></div>
            <LocalizedField v-model="form.name_i18n" field="name_i18n" :label="t('file.storage.name')" :errors="errors" :maxlength="128" required />
            <div class="file-storage-grid"><el-form-item :label="t('file.item.visibility')" :error="fieldError('visibility')"><el-radio-group v-model="form.visibility" :disabled="builtin"><el-radio value="private">{{ t('file.item.private') }}</el-radio><el-radio value="public">{{ t('file.item.public') }}</el-radio></el-radio-group></el-form-item><el-form-item :label="t('file.storage.enabled')" :error="fieldError('enabled')"><el-switch v-model="form.enabled" /></el-form-item></div>
            <el-form-item v-if="local" :label="t('file.storage.directory')" :error="fieldError('configuration.directory')"><el-input v-model="form.configuration.directory" :disabled="builtin" maxlength="128"><template #prepend>storage/app/</template></el-input><p class="file-storage-muted">{{ t('file.storage.directory_hint') }}</p></el-form-item>
            <template v-else><div class="file-storage-grid"><el-form-item :label="t('file.storage.bucket')" :error="fieldError('configuration.bucket')"><el-input v-model="form.configuration.bucket" maxlength="128" /></el-form-item><el-form-item :label="t('file.storage.region')" :error="fieldError('configuration.region')"><el-input v-model="form.configuration.region" maxlength="64" /></el-form-item></div><el-form-item :label="t('file.storage.endpoint')" :error="fieldError('configuration.endpoint')"><el-input v-model="form.configuration.endpoint" maxlength="512" placeholder="https://account-id.r2.cloudflarestorage.com" /></el-form-item><div class="file-storage-grid"><el-form-item label="Access Key ID" :error="fieldError('access_key')"><el-input v-model="form.access_key" type="password" show-password autocomplete="new-password" maxlength="512" :placeholder="form.has_access_key ? t('file.storage.keep_secret') : ''" /></el-form-item><el-form-item label="Secret Access Key" :error="fieldError('secret_key')"><el-input v-model="form.secret_key" type="password" show-password autocomplete="new-password" maxlength="512" :placeholder="form.has_secret_key ? t('file.storage.keep_secret') : ''" /></el-form-item></div><el-checkbox v-model="form.configuration.use_path_style_endpoint">{{ t('file.storage.path_style') }}</el-checkbox></template>
            <el-form-item :label="t('file.storage.public_url')" :error="fieldError('configuration.url')"><el-input v-model="form.configuration.url" maxlength="512" placeholder="https://assets.example.com" /></el-form-item>
            <el-alert v-if="form.provider === 'cloudflare'" :title="t('file.storage.r2_hint')" type="info" :closable="false" />
            <p v-if="fieldError('configuration')" class="file-storage-error">{{ fieldError('configuration') }}</p>
        </el-form>
        <template #footer><el-button :disabled="busy" @click="visible = false">{{ t('common.cancel') }}</el-button><el-button type="primary" :loading="busy" @click="save">{{ t('common.save') }}</el-button></template>
    </BaseDialog>
</template>
