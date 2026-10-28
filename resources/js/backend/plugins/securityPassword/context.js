import { inject } from 'vue';
import english from './locales/en.json';

export const securityPasswordOptionsKey = Symbol('securityPasswordOptions');

export function useSecurityPasswordMessages(props) {
    const options = inject(securityPasswordOptionsKey, {});
    function message(name) {
        const key = `security.${name}`;
        const translate = props.translate || options.translate;
        const translated = translate?.(key);
        return props.messages?.[name] ?? (translated && translated !== key ? translated : english[name] || key);
    }
    function requestSetup(event, href) {
        if (options.onSetup) {
            event?.preventDefault?.();
            options.onSetup(href);
        }
    }
    return { message, requestSetup };
}
