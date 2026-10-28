<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Bell } from '@element-plus/icons-vue';
import { notificationApi } from '@/api/notifications';
import { date } from '@/utils/date';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const visible = ref(false), loading = ref(false), rows = ref([]), unread = ref(0), preferences = ref({});
const codes = computed(() => [...new Set(rows.value.map(row => row.code).filter(Boolean))]);
let timer;
async function load() {
    loading.value = true;
    try {
        const result = await notificationApi.list();
        rows.value = result.data || [];
        unread.value = result.unread || 0;
        preferences.value = result.preferences || {};
    } catch (error) { fail(error); }
    finally { loading.value = false; }
}
async function open() { visible.value = true; await load(); }
async function read(row) {
    if (row.read_at) return;
    try { await notificationApi.read({ id: row.id }); row.read_at = new Date().toISOString(); unread.value = Math.max(0, unread.value - 1); }
    catch (error) { fail(error); }
}
async function readAll() {
    try { await notificationApi.readAll(); rows.value.forEach(row => { row.read_at ||= new Date().toISOString(); }); unread.value = 0; }
    catch (error) { fail(error); }
}
async function toggle(code, channel) {
    const disabled = new Set(preferences.value[code] || []);
    disabled.has(channel) ? disabled.delete(channel) : disabled.add(channel);
    const next = { ...preferences.value, [code]: [...disabled] };
    try { await notificationApi.preferences({ data: { preferences: next } }); preferences.value = next; notify(t('common.saved')); }
    catch (error) { fail(error); }
}
onMounted(() => { load(); timer = setInterval(load, 60000); });
onUnmounted(() => clearInterval(timer));
</script>

<template>
    <el-badge :value="unread" :hidden="!unread" :max="99"><el-button text :icon="Bell" :aria-label="t('notification.center')" @click="open" /></el-badge>
    <el-drawer v-model="visible" :title="t('notification.center')" size="440px">
        <div class="table-toolbar"><span>{{ t('notification.unread').replace('{count}', unread) }}</span><el-button text type="primary" @click="readAll">{{ t('notification.read_all') }}</el-button></div>
        <div v-loading="loading"><article v-for="row in rows" :key="row.id" class="notification-item" :class="{ unread: !row.read_at }" @click="read(row)"><strong>{{ row.subject }}</strong><p>{{ row.content }}</p><small>{{ date(row.created_at) }}</small></article><el-empty v-if="!rows.length" :description="t('common.empty')" /></div>
        <section v-if="codes.length" class="notification-preferences"><h3>{{ t('notification.preferences') }}</h3><div v-for="code in codes" :key="code"><code>{{ code }}</code><el-checkbox :model-value="!(preferences[code] || []).includes('database')" @change="toggle(code, 'database')">{{ t('notification.database') }}</el-checkbox><el-checkbox :model-value="!(preferences[code] || []).includes('mail')" @change="toggle(code, 'mail')">{{ t('notification.mail') }}</el-checkbox></div></section>
    </el-drawer>
</template>
