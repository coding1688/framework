<script setup>
import { onMounted, ref } from 'vue';
import { notificationApi } from '../api';
import { formatDate } from '@/shared/utils/date';
import AppIcon from '@/shared/components/AppIcon.vue';
import FeedbackMessage from '@/shared/components/FeedbackMessage.vue';
import LoadingState from '@/shared/components/LoadingState.vue';

const rows = ref([]), unread = ref(0), preferences = ref({}), loading = ref(false), busy = ref(false), error = ref('');

async function load() {
    loading.value = true;
    error.value = '';
    try {
        const result = await notificationApi.list();
        rows.value = result.data || [];
        unread.value = result.unread || 0;
        preferences.value = result.preferences || {};
    } catch (exception) { error.value = exception.message; }
    finally { loading.value = false; }
}
async function run(callback) {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try { await callback(); }
    catch (exception) { error.value = exception.message; }
    finally { busy.value = false; }
}
async function read(row) {
    if (row.read_at) return;
    await run(async () => {
        await notificationApi.read(row.id);
        row.read_at = new Date().toISOString();
        unread.value = Math.max(0, unread.value - 1);
    });
}
async function readAll() {
    await run(async () => {
        await notificationApi.readAll();
        rows.value.forEach(row => { row.read_at ||= new Date().toISOString(); });
        unread.value = 0;
    });
}
async function toggle(code, channel) {
    await run(async () => {
        const disabled = new Set(preferences.value[code] || []);
        disabled.has(channel) ? disabled.delete(channel) : disabled.add(channel);
        const next = { ...preferences.value, [code]: [...disabled] };
        await notificationApi.preferences(next);
        preferences.value = next;
    });
}
onMounted(load);
</script>

<template>
    <section class="content-card notification-view">
        <header class="card-heading card-heading--row"><div><h3>消息列表 <span v-if="unread" class="unread-badge">{{ unread }} 条未读</span></h3><p>点击消息可标记为已读，并设置每类消息的接收渠道。</p></div><button class="button button--secondary button--small" :disabled="busy || !unread" @click="readAll">全部已读</button></header>
        <FeedbackMessage :message="error" />
        <LoadingState v-if="loading" label="正在加载通知…" />
        <div v-else-if="rows.length" class="notification-list"><article v-for="row in rows" :key="row.id" class="notification-item" :class="{ unread: !row.read_at }" tabindex="0" @click="read(row)" @keydown.enter="read(row)"><span class="notification-icon"><AppIcon name="notification" :size="19" /></span><div class="notification-body"><div class="notification-title"><strong>{{ row.subject }}</strong><span v-if="!row.read_at" class="unread-dot" /><time>{{ formatDate(row.created_at) }}</time></div><p>{{ row.content }}</p><div class="notification-channels" @click.stop><label><input type="checkbox" :checked="!(preferences[row.code] || []).includes('database')" :disabled="busy" @change="toggle(row.code, 'database')">站内信</label><label><input type="checkbox" :checked="!(preferences[row.code] || []).includes('mail')" :disabled="busy" @change="toggle(row.code, 'mail')">邮件</label></div></div></article></div>
        <div v-else class="empty-state"><span class="empty-state-icon"><AppIcon name="notification" :size="28" /></span><h3>暂无通知</h3><p>新的账户动态和服务消息会在这里展示。</p></div>
    </section>
</template>
