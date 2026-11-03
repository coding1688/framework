<script setup>
import { computed, watch } from 'vue';
import { ElMessageBox } from 'element-plus';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { Placeholder } from '@tiptap/extensions';
import { useFormItem } from 'element-plus';
import { t } from '@/locales';

const props = defineProps({ modelValue: { type: String, default: '' }, placeholder: { type: String, default: '' } });
const emit = defineEmits(['update:modelValue']);
const { form } = useFormItem();
const disabled = computed(() => Boolean(form?.disabled));
const editor = useEditor({
    content: props.modelValue || '',
    editable: !disabled.value,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
            link: { openOnClick: false, HTMLAttributes: { target: null, rel: 'noopener noreferrer' } },
        }),
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    onUpdate({ editor: instance }) {
        if (!disabled.value) emit('update:modelValue', instance.isEmpty ? '' : instance.getHTML());
    },
});

const toolbar = [
    { name: 'bold', icon: 'bold', action: () => editor.value?.chain().focus().toggleBold().run(), active: () => editor.value?.isActive('bold') },
    { name: 'italic', icon: 'italic', action: () => editor.value?.chain().focus().toggleItalic().run(), active: () => editor.value?.isActive('italic') },
    { name: 'underline', icon: 'underline', action: () => editor.value?.chain().focus().toggleUnderline().run(), active: () => editor.value?.isActive('underline') },
    { name: 'strike', icon: 'strikethrough', action: () => editor.value?.chain().focus().toggleStrike().run(), active: () => editor.value?.isActive('strike') },
    { separator: true },
    { name: 'heading_2', icon: 'h-2', action: () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run(), active: () => editor.value?.isActive('heading', { level: 2 }) },
    { name: 'heading_3', icon: 'h-3', action: () => editor.value?.chain().focus().toggleHeading({ level: 3 }).run(), active: () => editor.value?.isActive('heading', { level: 3 }) },
    { name: 'bullet_list', icon: 'list-unordered', action: () => editor.value?.chain().focus().toggleBulletList().run(), active: () => editor.value?.isActive('bulletList') },
    { name: 'ordered_list', icon: 'list-ordered', action: () => editor.value?.chain().focus().toggleOrderedList().run(), active: () => editor.value?.isActive('orderedList') },
    { name: 'blockquote', icon: 'double-quotes-l', action: () => editor.value?.chain().focus().toggleBlockquote().run(), active: () => editor.value?.isActive('blockquote') },
    { name: 'code', icon: 'code-line', action: () => editor.value?.chain().focus().toggleCode().run(), active: () => editor.value?.isActive('code') },
    { name: 'code_block', icon: 'code-box-line', action: () => editor.value?.chain().focus().toggleCodeBlock().run(), active: () => editor.value?.isActive('codeBlock') },
    { separator: true },
    { name: 'link', icon: 'link', action: () => editLink(), active: () => editor.value?.isActive('link') },
    { name: 'unlink', icon: 'link-unlink', action: () => editor.value?.chain().focus().unsetLink().run(), available: () => editor.value?.isActive('link') },
    { name: 'clear_format', icon: 'format-clear', action: () => editor.value?.chain().focus().unsetAllMarks().clearNodes().run() },
    { separator: true },
    { name: 'undo', icon: 'arrow-go-back-line', action: () => editor.value?.chain().focus().undo().run(), available: () => editor.value?.can().undo() },
    { name: 'redo', icon: 'arrow-go-forward-line', action: () => editor.value?.chain().focus().redo().run(), available: () => editor.value?.can().redo() },
];

async function editLink() {
    const instance = editor.value;
    if (!instance) return;
    const previous = instance.getAttributes('link').href || '';
    try {
        const { value } = await ElMessageBox.prompt(t('editor.link_prompt'), t('editor.link'), {
            inputValue: previous,
            confirmButtonText: t('common.confirm'),
            cancelButtonText: t('common.cancel'),
            inputPlaceholder: 'https://example.com',
            inputValidator: value => {
                if (!value) return true;
                try {
                    const url = new URL(value, window.location.origin);
                    return ['http:', 'https:', 'mailto:'].includes(url.protocol) || t('editor.link_invalid');
                } catch {
                    return t('editor.link_invalid');
                }
            },
        });
        const href = value.trim();
        if (href) instance.chain().focus().extendMarkRange('link').setLink({ href }).run();
        else instance.chain().focus().extendMarkRange('link').unsetLink().run();
    } catch (error) {
        if (error !== 'cancel' && error !== 'close') throw error;
    }
}

watch(() => props.modelValue, value => {
    const instance = editor.value;
    if (!instance) return;
    const html = value || '';
    if (html !== instance.getHTML()) instance.commands.setContent(html, { emitUpdate: false });
});
watch(disabled, value => editor.value?.setEditable(!value));
</script>

<template>
    <div class="rich-editor" :class="{ 'rich-editor--disabled': disabled }">
        <div v-if="!disabled" class="rich-editor__toolbar" role="toolbar" :aria-label="t('editor.toolbar')">
            <template v-for="(item, index) in toolbar" :key="item.name || index">
                <span v-if="item.separator" class="rich-editor__separator" aria-hidden="true" />
                <button
                    v-else
                    type="button"
                    class="rich-editor__button"
                    :class="{ 'is-active': item.active?.() }"
                    :title="t(`editor.${item.name}`)"
                    :aria-label="t(`editor.${item.name}`)"
                    :aria-pressed="item.active ? Boolean(item.active()) : undefined"
                    :disabled="item.available && !item.available()"
                    @click="item.action"
                ><AppIcon :icon="item.icon" /></button>
            </template>
        </div>
        <EditorContent :editor="editor" class="rich-editor__content" />
    </div>
</template>

<style scoped>
.rich-editor { width: 100%; min-width: 0; line-height: 1.65; border: 1px solid var(--el-border-color); border-radius: var(--el-border-radius-base); background: var(--el-fill-color-blank); }
.rich-editor:focus-within { border-color: var(--el-color-primary); }
.rich-editor__toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 3px; padding: 7px; border-bottom: 1px solid var(--el-border-color-light); background: var(--el-fill-color-light); border-radius: var(--el-border-radius-base) var(--el-border-radius-base) 0 0; }
.rich-editor__button { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 30px; padding: 0; border: 0; border-radius: 4px; color: var(--el-text-color-regular); background: transparent; cursor: pointer; }
.rich-editor__button:hover:not(:disabled), .rich-editor__button.is-active { color: var(--el-color-primary); background: var(--el-color-primary-light-9); }
.rich-editor__button:focus-visible { outline: 2px solid var(--el-color-primary); outline-offset: 1px; }
.rich-editor__button:disabled { opacity: .4; cursor: not-allowed; }
.rich-editor__separator { width: 1px; height: 20px; margin: 0 4px; background: var(--el-border-color); }
.rich-editor__content { height: clamp(240px, 36vh, 420px); overflow-y: auto; }
.rich-editor--disabled { background: var(--el-fill-color-light); }
.rich-editor :deep(.tiptap) { min-height: 100%; padding: 12px 16px; outline: none; box-sizing: border-box; }
.rich-editor :deep(.tiptap > *:first-child) { margin-top: 0; }
.rich-editor :deep(.tiptap > *:last-child) { margin-bottom: 0; }
.rich-editor :deep(.tiptap h2), .rich-editor :deep(.tiptap h3) { line-height: 1.35; }
.rich-editor :deep(.tiptap ul), .rich-editor :deep(.tiptap ol) { padding-left: 24px; }
.rich-editor :deep(.tiptap blockquote) { margin-left: 0; margin-right: 0; padding-left: 12px; border-left: 3px solid var(--el-border-color); color: var(--el-text-color-secondary); }
.rich-editor :deep(.tiptap a) { color: var(--el-color-primary); text-decoration: underline; }
.rich-editor :deep(.tiptap code) { padding: 2px 5px; border-radius: 4px; background: var(--el-fill-color); font-family: "SFMono-Regular", Consolas, monospace; font-size: .9em; }
.rich-editor :deep(.tiptap pre) { padding: 14px 16px; overflow-x: auto; border-radius: 6px; background: #17251f; color: #e1eee8; }
.rich-editor :deep(.tiptap pre code) { padding: 0; background: transparent; }
.rich-editor :deep(.tiptap p.is-editor-empty:first-child::before) { content: attr(data-placeholder); float: left; height: 0; color: var(--el-text-color-placeholder); pointer-events: none; }
</style>
