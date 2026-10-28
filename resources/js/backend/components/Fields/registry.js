import { defineAsyncComponent } from 'vue';

export const fieldComponents = Object.freeze({
    checkbox: defineAsyncComponent(() => import('@/components/Fields/CheckboxField.vue')),
    input: defineAsyncComponent(() => import('@/components/Fields/TextField.vue')),
    'input-number': defineAsyncComponent(() => import('@/components/Fields/NumberField.vue')),
    'localized-input': defineAsyncComponent(() => import('@/components/Fields/LocalizedTextField.vue')),
    radio: defineAsyncComponent(() => import('@/components/Fields/RadioField.vue')),
    select: defineAsyncComponent(() => import('@/components/Fields/SelectField.vue')),
    switch: defineAsyncComponent(() => import('@/components/Fields/SwitchField.vue')),
});

export function resolveFieldComponent(component) {
    return fieldComponents[component] || null;
}
