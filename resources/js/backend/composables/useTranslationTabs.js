import { computed, ref, watch } from 'vue';
import { config } from '@/config';

export function useTranslationTabs(allowedLocales = () => null, preferredLocale = () => null) {
    const locales = computed(() => {
        const allowed = allowedLocales();
        const priority = locale => locale === (preferredLocale() || config.locale) ? 0 : locale === 'en' ? 1 : 2;
        return Object.entries(config.translationLocales || config.locales || {})
            .filter(([locale]) => !allowed || allowed.includes(locale))
            .sort(([first], [second]) => priority(first) - priority(second));
    });
    const active = ref(locales.value[0]?.[0] || 'en');

    watch(() => config.locale, () => { active.value = locales.value[0]?.[0] || 'en'; });
    watch(locales, value => {
        if (!value.some(([locale]) => locale === active.value)) active.value = value[0]?.[0] || 'en';
    });

    return { locales, active };
}
