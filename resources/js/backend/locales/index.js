const sharedFiles = import.meta.glob('./*/*.json', { import: 'default' });
const moduleFiles = import.meta.glob('../pages/**/locales/*.json', { import: 'default' });
const messages = {};
let aliases = {};
function register(locale, namespace, dictionary) {
    messages[locale] ??= {};
    messages[locale][namespace] = dictionary;
}
async function load(locale) {
    const loaders = [];
    for (const [path, loadDictionary] of Object.entries(sharedFiles)) {
        const [, fileLocale, file] = path.split('/');
        if (fileLocale === locale) {
            loaders.push(loadDictionary().then(dictionary => register(locale, file.replace('.json', ''), dictionary)));
        }
    }
    for (const [path, loadDictionary] of Object.entries(moduleFiles)) {
        const relative = path.replace('../pages/', '');
        const [module, file] = relative.split('/locales/');
        if (file.replace('.json', '') === locale) {
            loaders.push(loadDictionary().then(dictionary => register(locale, module.replaceAll('/', '.'), dictionary)));
        }
    }
    await Promise.all(loaders);
}
export async function initializeLocales() {
    await Promise.all([...new Set(['en', window.backendConfig.locale])].map(load));
    // Preserve dynamic field-label lookup in shared table/detail components.
    const namespaces = ['auth', 'admin.user', 'admin.department', 'permission', 'admin.menu', 'admin.log', 'layout', 'editor', 'form', 'common'];
    aliases = Object.fromEntries(namespaces.flatMap(namespace =>
        Object.keys(messages.en[namespace] || {}).map(key => [key, `${namespace}.${key}`])
    ));
}
export function t(key) {
    const qualified = aliases[key] || key;
    const separator = qualified.lastIndexOf('.');
    const namespace = qualified.slice(0, separator);
    const name = qualified.slice(separator + 1);
    return messages[window.backendConfig.locale]?.[namespace]?.[name]
        ?? messages.en?.[namespace]?.[name]
        ?? key;
}
