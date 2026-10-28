export function optionEntries(options = {}) {
    return Array.isArray(options)
        ? options.map(value => [value, value])
        : Object.entries(options);
}
