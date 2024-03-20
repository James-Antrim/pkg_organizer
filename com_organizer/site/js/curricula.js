document.addEventListener("DOMContentLoaded", function () {

    const parameters = Joomla.getOptions('curriculumParameters', {}),
        baseURL = parameters.rootURL + '?option=com_organizer&tmpl=component&' + parameters.token + '=1',
        id = parameters.id,
        type = parameters.type,
        cInput = document.getElementById('programIDs');

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

            let options, programIDs = getMultipleValues('programIDs'), response, soURL;
            soURL = baseURL + '&task=' + type + '.superOrdinatesAjax&id=' + id + '&programIDs=' + programIDs;

            if (programIDs === null || programIDs.includes('-1') !== false)
            {
                cInput.find('option').removeAttribute('selected');
                return false;
            }

            response = await fetch(soURL);
            options = await response.text();

            alert(options);
            // fetch(soURL).then(data => {
            //
            // });
            // jQuery.get(soURL, function (options) {
            //     soInput.innerHTML = options;
            //     const newSOs = getMultipleValues('superordinates');
            //     let selectedSOs = [];
            //
            //     if (newSOs !== null && newSOs.length)
            //     {
            //         if (oldSOs !== null && oldSOs.length)
            //         {
            //             selectedSOs = mergeMultipleUnique(newSOs, oldSOs);
            //         }
            //         else
            //         {
            //             selectedSOs = newSOs;
            //         }
            //     }
            //     else if (oldSOs !== null && oldSOs.length)
            //     {
            //         selectedSOs = oldSOs;
            //     }
            //
            //     setMultipleValues('superordinates', selectedSOs);
            // });
        }
    );
});