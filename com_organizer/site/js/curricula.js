document.addEventListener("DOMContentLoaded", function () {

    const cInput = document.getElementById('programIDs'),
        parameters = Joomla.getOptions('curriculumParameters', {}),
        id = parameters.id,
        type = parameters.type,
        url = parameters.url + '?option=com_organizer&tmpl=component&' + parameters.token + '=1';

    cInput.addEventListener(
        'change',
        async function () {

            const
                soInput = document.getElementById('superordinates'),
                oldSOs = getMultipleValues('superordinates'),
                preInput = document.getElementById('prerequisites'),
                oldPres = preInput === null ? [] : getMultipleValues('prerequisites'),
                postInput = document.getElementById('postrequisites'),
                oldPosts = postInput === null ? [] : getMultipleValues('postrequisites');

            let newSOs, options, programIDs = getMultipleValues('programIDs'), response, selectedSOs = [], soURL;

            soURL = url + '&task=' + type + '.superOrdinatesAjax&id=' + id + '&programIDs=' + programIDs;

            if (programIDs === null || programIDs.includes('-1') !== false)
            {
                cInput.find('option').removeAttribute('selected');
                return false;
            }

            response = await fetch(soURL);
            options = await response.text();
            soInput.innerHTML = options;
            newSOs = getMultipleValues('superordinates');

            if (newSOs !== null && newSOs.length)
            {
                if (oldSOs !== null && oldSOs.length)
                {
                    selectedSOs = mergeMultipleUnique(newSOs, oldSOs);
                }
                else
                {
                    selectedSOs = newSOs;
                }
            }
            else if (oldSOs !== null && oldSOs.length)
            {
                selectedSOs = oldSOs;
            }

            setMultipleValues('superordinates', selectedSOs);
        }
    );
});