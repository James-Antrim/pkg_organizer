function addHyphen() {
    const field = document.getElementById('jform_code')
    let code = field.value;

    // Remove hyphens
    code = code.replace(/[^0-9a-f]/g, '');

    if (code.length >= 4)
    {
        code = code.substr(0,4) + '-' + code.substr(4,4);
    }

    field.value = code;
}

window.onload = function () {
    document.getElementById('jform_code').addEventListener('input', addHyphen);
}