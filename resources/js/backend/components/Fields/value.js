import { t } from '@/locales';

export function fieldStorageKey(fieldType, multiple = false) {
    const dataType = multiple ? fieldType?.multiple_data_type : fieldType?.data_type;
    return ({
        boolean: 'value_boolean',
        localized_text: 'value_i18n',
        multi_select: 'value_json',
        number: 'value_number',
        select: 'value_string',
        text: 'value_string',
    })[dataType] || null;
}

export function fieldPayloadValue(definition, fieldType, payload = null) {
    const key = fieldStorageKey(fieldType, definition?.multiple);
    return key ? payload?.[key] : null;
}

function translate(key, replacements = {}) {
    return Object.entries(replacements).reduce((message, [name, value]) => message.replaceAll(`:${name}`, String(value)), t(key));
}

function empty(value) {
    return value === null || value === undefined || typeof value === 'string' && value.trim() === ''
        || Array.isArray(value) && value.length === 0;
}

export function validateFieldValue(definition, fieldType, payload) {
    const key = fieldStorageKey(fieldType, definition.multiple);
    const dataType = definition.multiple ? fieldType?.multiple_data_type : fieldType?.data_type;
    const value = key ? payload?.[key] : null;
    const validation = definition.validation || {};
    if (dataType === 'localized_text') {
        const translations = value && typeof value === 'object' && !Array.isArray(value) ? value : {};
        if (definition.required) {
            for (const locale of validation.required_locales || ['en']) {
                if (empty(translations[locale])) return translate('fields.validation_required_locale', { locale });
            }
        }
        for (const translation of Object.values(translations)) {
            if (empty(translation)) continue;
            const length = String(translation).trim().length;
            if (length < (validation.min_length ?? 0)) return translate('fields.validation_min_length', { min: validation.min_length });
            if (length > (validation.max_length ?? 512)) return translate('fields.validation_max_length', { max: validation.max_length });
        }
        return null;
    }
    if (empty(value)) return definition.required ? t('fields.validation_required') : null;
    if (dataType === 'text') {
        const length = String(value).trim().length;
        if (length < (validation.min_length ?? 0)) return translate('fields.validation_min_length', { min: validation.min_length });
        if (length > (validation.max_length ?? 512)) return translate('fields.validation_max_length', { max: validation.max_length });
    }
    if (dataType === 'number') {
        const number = Number(value);
        if (!Number.isFinite(number)) return t('fields.validation_number');
        if (validation.min_value !== null && validation.min_value !== undefined && number < validation.min_value) return translate('fields.validation_min_value', { min: validation.min_value });
        if (validation.max_value !== null && validation.max_value !== undefined && number > validation.max_value) return translate('fields.validation_max_value', { max: validation.max_value });
        const precision = validation.precision ?? 6;
        if (Number(number.toFixed(precision)) !== number) return translate('fields.validation_precision', { precision });
    }
    if (dataType === 'multi_select') {
        if (value.length < (validation.min_selected ?? 0)) return translate('fields.validation_min_selected', { min: validation.min_selected });
        if (value.length > (validation.max_selected ?? 64)) return translate('fields.validation_max_selected', { max: validation.max_selected });
    }
    if (['select', 'multi_select'].includes(dataType)) {
        const selected = Array.isArray(value) ? value : [value];
        if (selected.some(item => !Object.prototype.hasOwnProperty.call(definition.options || {}, item))) return t('fields.validation_option');
    }
    return null;
}

export function validateValidationSettings(schema, values) {
    for (const [minimum, maximum] of [['min_length', 'max_length'], ['min_selected', 'max_selected'], ['min_value', 'max_value']]) {
        if (values[minimum] !== null && values[minimum] !== undefined && values[maximum] !== null && values[maximum] !== undefined && values[minimum] > values[maximum]) {
            return t('fields.validation_range');
        }
    }
    const supported = values.supported_locales || [];
    const required = values.required_locales || [];
    if (required.some(locale => !supported.includes(locale))) return t('fields.validation_locales');
    return null;
}
