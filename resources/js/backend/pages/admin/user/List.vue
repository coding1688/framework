<script setup>
import { userApi } from '@/api/admin/user';

import BasePagination from '@/components/ui/BasePagination.vue';
import BaseAlert from '@/components/ui/BaseAlert.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import { computed, ref, reactive, watch, onMounted, onUnmounted } from 'vue';
import { Plus, Search, Refresh, UserFilled } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import { t } from '@/locales';
import { state } from '@/store/modules/user';
import { can } from '@/utils/permission';
import { date } from '@/utils/date';
import { notify, fail } from '@/utils/feedback';
import StatusTag from '@/components/StatusTag.vue';
import Editor from '@/pages/admin/user/components/Editor.vue';

const props = defineProps({ revision: Number });
const emit = defineEmits(['identity-changed']);
const rows = ref([]), loading = ref(false), error = ref(''), busy = ref(''), editor = ref(null);
const filters = reactive({ q: '', active: '', department_id: '' });
const page = ref(1), total = ref(0), lastPage = ref(1);
const departments = ref([]);
const mayManage = computed(() => state.user?.is_board || state.user?.is_leader);
function protectedUser(row) {
    return row.is_super || row.id === state.user.id || (!state.user.is_board && row.is_leader && row.department_id === state.user.department_id);
}
function editable(row) { return mayManage.value && !protectedUser(row); }
let requestId = 0, controller;
async function load() {
    const id = ++requestId;
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    error.value = '';
    try {
        const result = await userApi.list({ query: { ...filters, page: page.value }, signal: controller.signal });
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
async function removeRow(row) {
    if (busy.value) return;
    busy.value = 'delete' + row.id;
    try {
        await ElMessageBox.confirm(t('common.confirm_delete'), t('common.confirm'), {
            type: 'warning', confirmButtonText: t('common.delete'), cancelButtonText: t('common.cancel'),
            closeOnClickModal: false, customClass: 'delete-confirm',
            beforeClose: async (action, instance, done) => {
                if (action !== 'confirm') { done(); return; }
                if (instance.confirmButtonLoading) return;
                instance.confirmButtonLoading = true;
                try { await userApi.delete({ id: row.id }); done(); }
                catch (exception) { fail(exception); }
                finally { instance.confirmButtonLoading = false; }
            },
        });
        notify(t('common.saved'));
        saved();
    } catch (exception) { if (exception !== 'cancel' && exception !== 'close') fail(exception); }
    finally { busy.value = ''; }
}
function saved() { load(); emit('identity-changed'); }
async function toggleStatus(row) {
    if (busy.value) return;
    busy.value = 'status' + row.id;
    try {
        await userApi.status({ id: row.id, data: { active: !row.active } });
        notify(t('common.saved')); saved();
    } catch (exception) { fail(exception); } finally { busy.value = ''; }
}
watch(() => props.revision, load);
onMounted(async () => {
    load();
    if (!can('administrators', 'options')) return;
    try { departments.value = (await userApi.options()).departments; } catch (exception) { fail(exception); }
});
onUnmounted(() => { requestId++; controller?.abort(); });
</script>

<template>
    <BaseCard class="management-card" shadow="never">
        <div class="table-toolbar">
            <div class="table-toolbar__actions"><el-button v-if="mayManage && can('administrators', 'create') && can('administrators', 'options')" type="primary" :icon="Plus" :loading="busy==='edit'" :disabled="!!busy" @click="openEditor('edit')">{{ t('common.create') }}</el-button></div>
            <el-form inline @submit.prevent="search">
                <el-form-item><el-input v-model="filters.q" :placeholder="t('admin.user.search_hint')" :aria-label="t('common.search')" maxlength="128" clearable /></el-form-item>
                <el-form-item><el-select v-model="filters.department_id" :placeholder="t('admin.department.department')" clearable :aria-label="t('admin.department.department')"><el-option v-for="item in departments" :key="item.id" :label="item.name" :value="item.id" /></el-select></el-form-item>
                <el-form-item><el-select v-model="filters.active" :placeholder="t('common.all')" clearable :aria-label="t('common.status')"><el-option :label="t('common.active')" :value="1"/><el-option :label="t('common.inactive')" :value="0" /></el-select></el-form-item>
                <el-button type="primary" :icon="Search" native-type="submit" :loading="loading">{{ t('common.search') }}</el-button>
                <el-button :icon="Refresh" @click="reset">{{ t('common.reset') }}</el-button>
            </el-form>
        </div>
        <BaseAlert v-if="error" :title="error" type="error" :closable="false" />
        <el-table :data="rows" v-loading="loading" row-key="id" border :empty-text="t('common.empty')" class="management-table">
            <el-table-column :label="t('admin.user.administrators')" min-width="240"><template #default="{row}"><div class="person-cell"><el-avatar :size="30">{{ row.name.slice(0,1) }}</el-avatar><div><strong>{{ row.name }}</strong><small>{{ row.email }}</small></div></div></template></el-table-column>
            <el-table-column :label="t('admin.department.department')" min-width="140"><template #default="{row}">{{ row.department?.name||'—' }} <el-tooltip v-if="row.is_leader" :content="t('admin.department.is_leader')"><el-icon class="leader-icon" :aria-label="t('admin.department.is_leader')"><UserFilled /></el-icon></el-tooltip></template></el-table-column>
            <el-table-column :label="t('admin.user.last_login')" min-width="180" align="center"><template #default="{row}">{{ date(row.last_login_at) }}</template></el-table-column>
            <el-table-column :label="t('common.status')" width="100"><template #default="{row}"><StatusTag :active="row.active"/></template></el-table-column>
            <el-table-column :label="t('common.actions')" fixed="right" min-width="310"><template #default="{row}"><div class="table-actions">
                <el-button v-if="can('administrators','detail')" link type="primary" :disabled="!!busy" :loading="busy==='detail'+row.id" @click="openEditor('detail',row)">{{ t('common.details') }}</el-button>
                <template v-if="editable(row)">
                <el-button v-if="can('administrators','update')" link type="primary" :disabled="!!busy" :loading="busy==='edit'+row.id" @click="openEditor('edit',row)">{{ t('common.edit') }}</el-button>
                <el-button v-if="can('administrators','status')" link type="primary" :disabled="!!busy" :loading="busy==='status'+row.id" @click="toggleStatus(row)">{{ t(row.active?'disable_account':'enable_account') }}</el-button>
                <el-button v-if="can('administrators','password')" link type="primary" :disabled="!!busy" :loading="busy==='password'+row.id" @click="openEditor('password',row)">{{ t('form.reset_password') }}</el-button>
                <el-button v-if="can('administrators','delete') && !row.active" link type="danger" :disabled="!!busy" :loading="busy==='delete'+row.id" @click="removeRow(row)">{{ t('common.delete') }}</el-button>
                </template>
                <span v-else-if="protectedUser(row)" class="muted">{{ t('admin.user.protected') }}</span>
            </div></template></el-table-column>
        </el-table>
        <div class="table-pagination">
            <BasePagination v-model:current-page="page" :page-size="15" :total="total" layout="total, prev, pager, next, jumper" @current-change="load" />
        </div>
    </BaseCard>
    <Editor ref="editor" @saved="saved" />
</template>
