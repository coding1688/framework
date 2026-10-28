export async function copyText(value) {
    if (window.isSecureContext && navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(value);
            return true;
        } catch {
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.readOnly = true;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    const selection = document.getSelection();
    const range = selection?.rangeCount ? selection.getRangeAt(0).cloneRange() : null;
    textarea.focus();
    textarea.select();
    textarea.setSelectionRange(0, value.length);
    const copied = document.execCommand('copy');
    textarea.remove();
    if (range && selection) {
        selection.removeAllRanges();
        selection.addRange(range);
    }

    return copied;
}
