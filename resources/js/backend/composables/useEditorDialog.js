import { ref, reactive } from 'vue';
import { t } from '@/locales';
import { notify, fail } from '@/utils/feedback';

export function useEditorDialog(moduleApi, emit) {
    const visible = ref(false), busy = ref(false), errors = ref({}), editing = ref(null), mode = ref('edit');
    const form = reactive({});
    function reset(action, row, values = {}) {
        mode.value = action;
        editing.value = row;
        errors.value = {};
        Object.keys(form).forEach(key => delete form[key]);
        Object.assign(form, values);
    }
    async function submit(action, data) {
        if (busy.value) return;
        busy.value = true;
        errors.value = {};
        try {
            await moduleApi[action]({ id: editing.value?.id, data });
            visible.value = false;
            notify(t('common.saved'));
            emit('saved');
        } catch (error) {
            errors.value = error.fields || {};
            fail(error);
        } finally { busy.value = false; }
    }
    return { visible, busy, errors, editing, mode, form, reset, submit };
}
