<script setup>
import { ref } from 'vue';
import { t } from '@/locales';

const visible = ref(false), current = ref({});
function open(row) {
    current.value = row;
    visible.value = true;
}
defineExpose({ open });
</script>

<template>
    <el-dialog v-model="visible" :title="t('documentation.document.preview')" width="76vw" class="document-preview-dialog">
        <article class="document-preview">
            <div class="document-preview__meta"><el-tag size="small" effect="plain">{{ t(`documentation.document.${current.type || 'development'}`) }}</el-tag><span>{{ current.category?.name }}</span><code>{{ current.slug }}</code></div>
            <h1>{{ current.title }}</h1>
            <p v-if="current.summary" class="document-preview__summary">{{ current.summary }}</p>
            <div class="document-preview__content" v-html="current.content" />
        </article>
    </el-dialog>
</template>

<style scoped>
.document-preview { max-width: 860px; margin: 0 auto; color: var(--el-text-color-primary); }
.document-preview__meta { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; color: var(--el-text-color-secondary); font-size: 12px; }
.document-preview__meta code { margin-left: auto; color: var(--el-text-color-secondary); }
.document-preview h1 { margin: 20px 0 12px; font-size: 32px; line-height: 1.25; letter-spacing: -.6px; }
.document-preview__summary { margin: 0 0 28px; padding-bottom: 24px; border-bottom: 1px solid var(--el-border-color-light); color: var(--el-text-color-secondary); font-size: 15px; line-height: 1.8; }
.document-preview__content { font-size: 14px; line-height: 1.85; }
.document-preview__content :deep(h2) { margin: 32px 0 14px; font-size: 23px; line-height: 1.35; }
.document-preview__content :deep(h3) { margin: 26px 0 12px; font-size: 18px; }
.document-preview__content :deep(a) { color: var(--el-color-primary); text-decoration: underline; }
.document-preview__content :deep(blockquote) { margin: 20px 0; padding: 10px 16px; border-left: 3px solid var(--el-color-primary); background: var(--el-color-primary-light-9); color: var(--el-text-color-regular); }
.document-preview__content :deep(pre) { padding: 16px; overflow-x: auto; border-radius: 7px; background: #17251f; color: #e1eee8; line-height: 1.7; }
.document-preview__content :deep(code) { padding: 2px 5px; border-radius: 4px; background: var(--el-fill-color-light); font-family: "SFMono-Regular", Consolas, monospace; font-size: .9em; }
.document-preview__content :deep(pre code) { padding: 0; background: transparent; }
@media (max-width: 640px) { .document-preview h1 { font-size: 26px; } .document-preview__meta code { width: 100%; margin-left: 0; } }
</style>
