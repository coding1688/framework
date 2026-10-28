export function isFieldDependencySatisfied(value, expectedValue = null) {
    if (value === null || value === undefined || value === '' || (Array.isArray(value) && !value.length)) return false;
    if (expectedValue === null || expectedValue === undefined) return true;
    const comparableValue = item => typeof item === 'boolean' ? (item ? '1' : '0') : String(item);
    const expected = comparableValue(expectedValue);

    return Array.isArray(value)
        ? value.some(item => comparableValue(item) === expected)
        : comparableValue(value) === expected;
}
