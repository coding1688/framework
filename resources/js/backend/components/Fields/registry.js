import { defineAsyncComponent, shallowRef } from 'vue';

export const fieldComponents = shallowRef({
    checkbox: defineAsyncComponent(() => import('@/components/Fields/CheckboxField.vue')),
    input: defineAsyncComponent(() => import('@/components/Fields/TextField.vue')),
    'input-number': defineAsyncComponent(() => import('@/components/Fields/NumberField.vue')),
    'localized-input': defineAsyncComponent(() => import('@/components/Fields/LocalizedTextField.vue')),
    radio: defineAsyncComponent(() => import('@/components/Fields/RadioField.vue')),
    select: defineAsyncComponent(() => import('@/components/Fields/SelectField.vue')),
    switch: defineAsyncComponent(() => import('@/components/Fields/SwitchField.vue')),
});

export function registerFieldComponent(component, loader) {
    if (!component || typeof loader !== 'function') throw new TypeError('A field component key and async loader are required.');
    fieldComponents.value = { ...fieldComponents.value, [component]: defineAsyncComponent(loader) };
}

export function resolveFieldComponent(component) {
    return fieldComponents.value[component] || null;
}
