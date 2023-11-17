document.addEventListener("DOMContentLoaded", function () {

    const parameters = Joomla.getOptions('curriculumParameters', {}),
        baseURL = parameters.rootURL + '?option=com_organizer&format=json',
        id = parameters.id,
        type = parameters.type,
        cInput = document.getElementById('jformcurricula');

    cInput.addEventListener(
        'change',
        function () {

            const
                soInput = document.getElementById('superordinates'),
                oldSOs = getMultipleValues('superordinates'),
                preInput = document.getElementById('prerequisites'),
                oldPres = preInput === null ? [] : getMultipleValues('prerequisites'),
                postInput = document.getElementById('postrequisites'),
                oldPosts = postInput === null ? [] : getMultipleValues('postrequisites');

            let selectedCurricula = getMultipleValues('jformcurricula'), soURL;

            if (selectedCurricula === null)
            {
                selectedCurricula = '';
            }
            else if (Array.isArray(selectedCurricula))
            {
                selectedCurricula = selectedCurricula.join(',');
            }

            if (selectedCurricula.includes('-1') !== false)
            {
                cInput.find('option').removeAttr('selected');
                return false;
            }

            soURL = baseURL + '&view=super_ordinates&id=' + id + '&curricula=' + selectedCurricula + '&type=' + type;

            jQuery.get(soURL, function (options) {
                soInput.innerHTML = options;
                const newSOs = getMultipleValues('superordinates');
                let selectedSOs = [];

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
            });
        }
    );
});