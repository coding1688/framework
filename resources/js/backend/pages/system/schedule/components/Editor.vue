<script setup>
import { reactive, ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import LocalizedField from '@/components/LocalizedField.vue';
import { scheduleApi } from '@/api/system/schedule';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const props = defineProps({ options: { type: Object, default: () => ({ commands: [], timezones: [] }) } });
const emit = defineEmits(['saved']);
const visible = ref(false), busy = ref(false), errors = ref({});
const form = reactive({ id: null, key: '', name_i18n: {}, description_i18n: {}, command: '', cron: '', timezone: 'UTC', active: true, without_overlapping: true, sort: 0 });
function open(row = null) {
    Object.assign(form, { id: null, key: '', name_i18n: {}, description_i18n: {}, command: '', cron: '', timezone: props.options.default_timezone || 'UTC', active: true, without_overlapping: true, sort: 0 }, row || {});
    errors.value = {};
    visible.value = true;
}
async function submit() {
    if (busy.value) return;
    busy.value = true; errors.value = {};
    const data = { key: form.key, name_i18n: form.name_i18n, description_i18n: form.description_i18n, command: form.command, cron: form.cron, timezone: form.timezone, active: form.active, without_overlapping: form.without_overlapping, sort: form.sort };
    try {
        await (form.id ? scheduleApi.update({ id: form.id, data }) : scheduleApi.create({ data }));
        notify(t('common.saved')); visible.value = false; emit('saved');
    } catch (error) { errors.value = error.fields || {}; fail(error); }
    finally { busy.value = false; }
}
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t('system.schedule.editor')" width="76vw" :close-on-click-modal="false">
        <el-form label-position="top" @submit.prevent="submit">
            <el-row :gutter="20">
                <el-col :xs="24" :md="12"><el-form-item :label="t('system.schedule.key')" required :error="errors.key?.[0]"><el-input v-model="form.key" maxlength="128" placeholder="notifications:prune" required /></el-form-item></el-col>
                <el-col :xs="24" :md="12"><el-form-item :label="t('system.schedule.command')" required :error="errors.command?.[0]"><el-select v-model="form.command" filterable class="w-full" required><el-option v-for="command in options.commands" :key="command.value" :label="`${command.label} (${command.value})`" :value="command.value" /></el-select></el-form-item></el-col>
            </el-row>
            <LocalizedField v-model="form.name_i18n" field="name_i18n" :label="t('common.name')" :errors="errors" :maxlength="128" required />
            <LocalizedField v-model="form.description_i18n" field="description_i18n" :label="t('common.description')" type="textarea" :errors="errors" :maxlength="512" />
            <el-row :gutter="20">
                <el-col :xs="24" :md="12"><el-form-item :label="t('system.schedule.cron')" required :error="errors.cron?.[0]"><el-input v-model="form.cron" maxlength="32" placeholder="0 * * * *" required /><small>{{ t('system.schedule.cron_hint') }}</small></el-form-item></el-col>
                <el-col :xs="24" :md="12"><el-form-item :label="t('system.schedule.timezone')" required :error="errors.timezone?.[0]"><el-select v-model="form.timezone" filterable class="w-full" required><el-option v-for="timezone in options.timezones" :key="timezone" :label="timezone" :value="timezone" /></el-select></el-form-item></el-col>
            </el-row>
            <el-row :gutter="20">
                <el-col :xs="24" :md="8"><el-form-item :label="t('common.status')" :error="errors.active?.[0]"><el-switch v-model="form.active" :active-text="t('common.enable')" :inactive-text="t('common.disable')" /></el-form-item></el-col>
                <el-col :xs="24" :md="8"><el-form-item :label="t('system.schedule.without_overlapping')" :error="errors.without_overlapping?.[0]"><el-switch v-model="form.without_overlapping" /></el-form-item></el-col>
                <el-col :xs="24" :md="8"><el-form-item :label="t('common.sort')" :error="errors.sort?.[0]"><el-input-number v-model="form.sort" :min="0" :max="99999" /></el-form-item></el-col>
            </el-row>
        </el-form>
        <template #footer><el-button @click="visible = false">{{ t('common.cancel') }}</el-button><el-button type="primary" :loading="busy" @click="submit">{{ t('common.save') }}</el-button></template>
    </BaseDialog>
</template>

<style scoped>
.w-full { width: 100%; }
small { display: block; margin-top: 6px; color: var(--el-text-color-secondary); line-height: 1.5; }
</style>
