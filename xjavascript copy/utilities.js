
export function createOptionElement(value, text) {
    console.log('Utility Function. Creating option element:', value, text);
    const option = document.createElement('option');
    option.value = value;
    option.text = text;
    return option;
}