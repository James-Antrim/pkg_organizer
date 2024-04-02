document.addEventListener("DOMContentLoaded", function () {

    const cInput = document.getElementById('programIDs'),
        parameters = Joomla.getOptions('curriculumParameters', {}),
        id = parameters.id,
        type = parameters.type,
        url = parameters.url + '?option=com_organizer&tmpl=component&' + parameters.token + '=1';

    cInput.addEventListener(
        'change',
        async function () {

            const oldSOs = getMultipleValues('superordinates'),
                preInput = document.getElementById('prerequisites'),
                programIDs = '&programIDs=' + getMultipleValues('programIDs'),
                soInput = document.getElementById('superordinates');

            let newSOs, newPres, oldPres, preResponse, selectedSOs, selectedPres, soResponse;

            if (programIDs === null || programIDs.includes('-1') !== false)
            {
                cInput.find('option').removeAttribute('selected');
                return false;
            }

            soResponse = await fetch(url + '&task=' + type + '.superOrdinatesAjax&id=' + id + programIDs);
            soInput.innerHTML = await soResponse.text();
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

            if (preInput !== null)
            {
                oldPres = getMultipleValues('prerequisites');

                preResponse = await fetch(url + '&task=subject.prerequisitesAjax&id=' + id + programIDs);
                preInput.innerHTML = await preResponse.text();
                newPres = getMultipleValues('prerequisites');

                if (newPres !== null && newPres.length)
                {
                    if (oldPres !== null && oldPres.length)
                    {
                        selectedPres = mergeMultipleUnique(newPres, oldPres);
                    }
                    else
                    {
                        selectedPres = newPres;
                    }
                }
                else if (oldPres !== null && oldPres.length)
                {
                    selectedPres = oldPres;
                }

                setMultipleValues('prerequisites', selectedPres);
            }
        }
    );
});