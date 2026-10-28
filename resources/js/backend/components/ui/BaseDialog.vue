<script setup>
import { computed, ref, watch } from 'vue';
import { ElDialog } from 'element-plus';
import { FullScreen, ScaleToOriginal } from '@element-plus/icons-vue';
import { t } from '@/locales';

defineOptions({ inheritAttrs: false });
const props = defineProps({
    ...ElDialog.props,
    width: { type: [String, Number], default: '76vw' },
    alignCenter: { type: Boolean, default: true },
    destroyOnClose: { type: Boolean, default: true },
    closeOnClickModal: { type: Boolean, default: false },
    draggable: { type: Boolean, default: true },
    showFullscreen: { type: Boolean, default: true },
    busy: Boolean,
    loading: Boolean,
});
const emit = defineEmits(['update:modelValue']);
const fullscreen = ref(props.fullscreen);
watch(() => props.fullscreen, value => { fullscreen.value = value; });
watch(() => props.modelValue, value => { if (!value) fullscreen.value = props.fullscreen; });
const dialogProps = computed(() => {
    const { busy, loading, showFullscreen, ...native } = props;
    return {
        ...native, fullscreen: fullscreen.value,
        showClose: !busy && props.showClose,
        closeOnPressEscape: !busy && props.closeOnPressEscape,
        beforeClose: done => {
            if (busy) return;
            if (props.beforeClose) props.beforeClose(done);
            else done();
        },
    };
});
function toggleFullscreen() { if (!props.busy && props.showFullscreen) fullscreen.value = !fullscreen.value; }
</script>

<template>
    <el-dialog v-bind="{ ...$attrs, ...dialogProps }" class="spa-dialog" @update:model-value="emit('update:modelValue', $event)">
        <template #header="scope">
            <div class="base-dialog-heading" @dblclick="toggleFullscreen">
                <slot name="header" v-bind="scope"><span :id="scope.titleId" class="el-dialog__title">{{ title }}</span></slot>
            </div>
            <button v-if="showFullscreen" type="button" class="base-dialog-fullscreen" :disabled="busy" :aria-label="t(fullscreen ? 'common.exit_fullscreen' : 'common.fullscreen')" :title="t(fullscreen ? 'common.exit_fullscreen' : 'common.fullscreen')" @click="toggleFullscreen">
                <el-icon><component :is="fullscreen ? ScaleToOriginal : FullScreen" /></el-icon>
            </button>
        </template>
        <div v-loading="loading" :aria-busy="loading"><slot /></div>
        <template v-if="$slots.footer" #footer><slot name="footer" /></template>
    </el-dialog>
</template>
