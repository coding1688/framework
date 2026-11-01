<script setup>
import { reactive, ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import { frontendUserApi } from '@/api/member/user';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const emit = defineEmits(['saved']);
const visible = ref(false), busy = ref(false), error = ref('');
const form = reactive({ id: null, active: true, reason: '' });

function open(row) {
    Object.assign(form, { id: row.id, active: Boolean(row.frozen_at), reason: '' });
    error.value = '';
    visible.value = true;
}
async function submit() {
    if (busy.value) return;
    if (!form.active && !form.reason.trim()) {
        error.value = t('member.user.freeze_reason_required');
        return;
    }
    busy.value = true;
    error.value = '';
    try {
        await frontendUserApi.status({ id: form.id, data: { active: form.active, reason: form.reason } });
        notify(t('common.saved'));
        visible.value = false;
        emit('saved');
    } catch (exception) { error.value = exception.message; fail(exception); }
    finally { busy.value = false; }
}
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t(form.active ? 'member.user.unfreeze_user' : 'member.user.freeze_user')" width="50%" :close-on-click-modal="false">
        <el-alert :title="t(form.active ? 'member.user.unfreeze_warning' : 'member.user.freeze_warning')" :type="form.active ? 'success' : 'warning'" :closable="false" />
        <el-form label-position="top" @submit.prevent="submit">
            <el-form-item :label="t(form.active ? 'member.user.unfreeze_reason' : 'member.user.freeze_reason')" :required="!form.active">
                <el-input v-model="form.reason" type="textarea" maxlength="512" show-word-limit />
            </el-form-item>
            <el-alert v-if="error" :title="error" type="error" :closable="false" />
        </el-form>
        <template #footer><el-button @click="visible = false">{{ t('common.cancel') }}</el-button><el-button :type="form.active ? 'primary' : 'warning'" :loading="busy" @click="submit">{{ t('common.confirm') }}</el-button></template>
    </BaseDialog>
</template>

<style scoped>
.el-form { margin-top: 20px; }
</style>
