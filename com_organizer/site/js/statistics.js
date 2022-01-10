/**
 * Updates available options for subordinate resources
 */

function updateCategories() {
    const orgInput = document.getElementById('jform_organizationID'),
        catInput = document.getElementById('jform_categoryID'),
        catRequest = new XMLHttpRequest(),
        value = orgInput.value;
    let url = '../index.php?option=com_organizer&format=json';

    if (value) {
        catRequest.open('GET', url + '&view=CategoryOptions&organizationID=' + value, true);
        catRequest.onreadystatechange = function () {

            if (catRequest.readyState === 4 && catRequest.status === 200) {
                updateOptions('category', JSON.parse(catRequest.responseText));
            }
        };
        catRequest.send();
    } else {
        updateOptions('category', []);
    }
}

/**
 * Fills options of given field with an Ajax request
 * @params {string} resource
 * @params {array} request
 */
function updateOptions(resource, options) {
    const input = document.getElementById('jform_' + resource + 'ID');
    let defaultText, key, option, value;

    // Remove all options other than the placeholder.
    defaultText = input.options[0].text;
    input.options.length = 0;

    input.options[input.options.length] = new Option(defaultText, '');

    for (key in options) {
        if (options.hasOwnProperty(key)) {
            value = options[key].value;
            input.options[input.options.length] = new Option(options[key].text, value);
        }
    }
}
