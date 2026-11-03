export function isFieldDependencySatisfied(value, expectedValue = null) {
    const values = Array.isArray(value) ? value : value && typeof value === 'object' ? Object.values(value) : null;
    if (value === null || value === undefined || value === '' || values && !values.length) return false;
    if (expectedValue === null || expectedValue === undefined || expectedValue === '') {
        return values ? values.some(item => item !== null && item !== undefined && item !== '') : true;
    }
    const comparableValue = item => typeof item === 'boolean' ? (item ? '1' : '0') : String(item);
    const expected = comparableValue(expectedValue);

    return values
        ? values.some(item => comparableValue(item) === expected)
        : comparableValue(value) === expected;
}
