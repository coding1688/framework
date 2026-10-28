<script setup>
import { computed } from 'vue';
import { resolveFieldComponent } from '@/components/Fields/registry';

const props = defineProps({
    modelValue: { type: Object, required: true },
    definition: { type: Object, required: true },
    fieldType: { type: Object, default: null },
    field: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue']);

const component = computed(() => resolveFieldComponent(props.fieldType?.component));
const dataType = computed(() => props.definition.multiple
    ? props.fieldType?.multiple_data_type
    : props.fieldType?.data_type);
const storageKey = computed(() => ({
    boolean: 'value_boolean',
    localized_text: 'value_i18n',
    multi_select: 'value_json',
    number: 'value_number',
    select: 'value_string',
    text: 'value_string',
})[dataType.value]);
const value = computed({
    get() {
        return storageKey.value ? props.modelValue[storageKey.value] : null;
    },
    set(nextValue) {
        if (storageKey.value) {
            emit('update:modelValue', { ...props.modelValue, [storageKey.value]: nextValue });
        }
    },
});
</script>

<template>
    <component
        :is="component"
        v-if="component && storageKey"
        v-model="value"
        :definition="definition"
        :field="field"
        :errors="errors"
    />
    <el-form-item v-else :label="definition.name">
        <el-tag type="danger">{{ definition.input_type }}</el-tag>
    </el-form-item>
</template>
