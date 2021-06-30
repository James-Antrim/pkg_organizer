function makeLink() {
    const variables = Joomla.getOptions('variables', {}),
        cmInput = document.getElementById('filter_campusID'),
        ctInput = document.getElementById('filter_categoryID'),
        gInput = document.getElementById('filter_groupID'),
        mInput = document.getElementById('filter_methodID'),
        lInput = document.getElementById('list_languageTag'),
        oInput = document.getElementById('filter_organizationID'),
        pInput = document.getElementById('filter_personID'),
        rInput = document.getElementById('filter_roomID');
    let url = variables.ICS_URL, campusID, methodID, my, organizationID, roomID;

    if (typeof variables.my !== "undefined") {
        my = 1;

        /*if (typeof variables.username !== 'undefined' && typeof variables.auth !== 'undefined') {
            url += '&username=' + variables.username + '&auth=' + variables.auth;
        }
    */
        //window.prompt('503', url);
        return;
    } else {
        if (typeof variables.campusID !== "undefined") {
            campusID = variables.campusID;
        } else if (cmInput !== null && cmInput.value) {
            campusID = cmInput.value;
        }

        if (typeof campusID !== "undefined") {
            url += '&campusID=' + campusID;
        }

        if (ctInput !== null && ctInput.value) {
            url += '&categoryID=' + ctInput.value;
        }

        if (typeof variables.dow !== "undefined") {
            url += '&dow=' + variables.dow;
        }

        if (gInput !== null && gInput.value) {
            url += '&groupID=' + gInput.value;
        }

        if (lInput !== null && lInput.value) {
            url += '&languageTag=' + lInput.value;
        }

        if (typeof variables.methodID !== "undefined") {
            methodID = variables.methodID;
        } else if (mInput !== null && mInput.value) {
            methodID = mInput.value;
        }

        if (typeof methodID !== "undefined") {
            url += '&methodID=' + methodID;
        }

        if (typeof variables.organizationID !== "undefined") {
            organizationID = variables.organizationID;
        } else if (oInput !== null && oInput.value) {
            organizationID = oInput.value;
        }

        if (typeof organizationID !== "undefined") {
            url += '&organizationID=' + organizationID;
        }

        if (pInput !== null && pInput.value) {
            url += '&personID=' + pInput.value;
        }

        if (rInput !== null && rInput.value) {
            url += '&roomID=' + rInput.value;
        }
    }
    ;

    window.prompt(Joomla.JText._('ORGANIZER_GENERATE_LINK'), url);
    return;
};