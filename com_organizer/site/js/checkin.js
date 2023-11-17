function addHyphen(e)
{
    const field = document.getElementById('jform_code');
    let code = field.value, hyphenDeleted;

    // Remove hyphens
    code = code.replace(/[^0-9a-f]/g, '');

    // The delete key was pressed and the length is now 4, so the hyphen was deleted.
    hyphenDeleted = e.inputType === 'deleteContentBackward' && field.value.length === 4 && code.length === 4;

    if (hyphenDeleted)
    {
        code = code.substr(0, 3);
    }

    if (code.length >= 4)
    {
        code = code.substr(0, 4) + '-' + code.substr(4, 4);
    }

    field.value = code;
}

window.onload = function () {
    const input = document.getElementById('jform_code');

    if (input !== null)
    {
        input.addEventListener('input', addHyphen);
    }
}