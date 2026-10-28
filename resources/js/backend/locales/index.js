const sharedFiles = import.meta.glob('./*/*.json', { eager: true, import: 'default' });
const moduleFiles = import.meta.glob('../pages/**/locales/*.json', { eager: true, import: 'default' });
const messages = {};
function register(locale, namespace, dictionary) {
    messages[locale] ??= {};
    messages[locale][namespace] = dictionary;
}
for (const [path, dictionary] of Object.entries(sharedFiles)) {
    const [, locale, file] = path.split('/');
    register(locale, file.replace('.json', ''), dictionary);
}
for (const [path, dictionary] of Object.entries(moduleFiles)) {
    const relative = path.replace('../pages/', '');
    const [module, file] = relative.split('/locales/');
    register(file.replace('.json', ''), module.replaceAll('/', '.'), dictionary);
}

// Preserve dynamic field-label lookup in shared table/detail components.
const namespaces = ['auth', 'admin.user', 'admin.department', 'permission', 'admin.menu', 'admin.log', 'layout', 'editor', 'form', 'common'];
const aliases = Object.fromEntries(namespaces.flatMap(namespace =>
    Object.keys(messages.en[namespace] || {}).map(key => [key, `${namespace}.${key}`])
));
export function t(key) {
    const qualified = aliases[key] || key;
    const separator = qualified.lastIndexOf('.');
    const namespace = qualified.slice(0, separator);
    const name = qualified.slice(separator + 1);
    return messages[window.backendConfig.locale]?.[namespace]?.[name]
        ?? messages.en?.[namespace]?.[name]
        ?? key;
}
