<script setup>
import { onMounted, ref } from 'vue';
import { notificationApi } from '../api';

const rows = ref([]), unread = ref(0), preferences = ref({}), loading = ref(false), error = ref('');
async function load() {
    loading.value = true; error.value = '';
    try { const result = await notificationApi.list(); rows.value = result.data || []; unread.value = result.unread || 0; preferences.value = result.preferences || {}; }
    catch (exception) { error.value = exception.message; }
    finally { loading.value = false; }
}
async function read(row) { if (!row.read_at) { await notificationApi.read(row.id); row.read_at = new Date().toISOString(); unread.value = Math.max(0, unread.value - 1); } }
async function readAll() { await notificationApi.readAll(); rows.value.forEach(row => { row.read_at ||= new Date().toISOString(); }); unread.value = 0; }
async function toggle(code, channel) {
    const disabled = new Set(preferences.value[code] || []); disabled.has(channel) ? disabled.delete(channel) : disabled.add(channel);
    const next = { ...preferences.value, [code]: [...disabled] };
    await notificationApi.preferences(next); preferences.value = next;
}
onMounted(load);
</script>

<template><section class="account-section"><div class="notification-heading"><h3>通知中心 <span v-if="unread">({{ unread }})</span></h3><button class="link-button" @click="readAll">全部已读</button></div><div v-if="error" class="feedback feedback--error">{{ error }}</div><div><article v-for="row in rows" :key="row.id" class="notification-item" :class="{ unread: !row.read_at }" @click="read(row)"><strong>{{ row.subject }}</strong><p>{{ row.content }}</p><small>{{ row.created_at }}</small><div class="notification-channels"><label><input type="checkbox" :checked="!(preferences[row.code] || []).includes('database')" @change="toggle(row.code, 'database')">站内信</label><label><input type="checkbox" :checked="!(preferences[row.code] || []).includes('mail')" @change="toggle(row.code, 'mail')">邮件</label></div></article><p v-if="!rows.length">暂无通知。</p></div></section></template>
