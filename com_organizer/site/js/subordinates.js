"use strict";

/**
 * Calls function getCheckedItems() and calls the close button click event to close the iFrame
 *
 * @param divID the id of the div
 * @param type the type of the source
 */
function closeModal(divID, type)
{
    getCheckedItems(divID, type);
    jQuery('button.close').trigger('click');
}

/**
 *  Deactivates chosen forms and adds buttons for the selection of subOrdinates to the form.
 */
window.onload = function () {

    const forms = document.getElementsByTagName('form'),
        subToolbar = jQuery('#subOrdinates-toolbar'),
        poolButton = jQuery('#toolbar-popup-list').detach(),
        subjectButton = jQuery('#toolbar-popup-book').detach();

    poolButton.appendTo(subToolbar);

    if (subjectButton.length)
    {
        subjectButton.appendTo(subToolbar);
    }

    for (var i = 0; i < forms.length; i++)
    {
        forms[i].onsubmit = function () {
            return false
        };
    }
};

/**
 * Increments the indexing of all subsequent rows, and adds replaces the indexed row with a blank.
 *
 * @param {int} position the index at which a blank row should be added
 * @returns {void} modifies the dom
 */
function insertBlank(position)
{
    let subOrdinates = getSubOrdinates(),
        length = subOrdinates.length,
        newOrder,
        oldIndex;

    // Add a new row to buffer the run off.
    addRow(length);

    // Increments existing rows starting from the last one.
    while (position <= length)
    {
        newOrder = length + 1;
        oldIndex = length - 1;

        cloneSubOrdinate(newOrder, subOrdinates[oldIndex]);
        length--;
    }

    // Empties the information from the current row.
    clearSubOrdinates(position);
}

/**
 * Add a new row to the end of the table.
 *
 * @param {int} lastPosition the index of the last subordinate table element
 * @param {string} tableID   the html id attribute of the table
 * @param {string} resourceID
 * @param {string} resourceName
 * @param {string} resourceType
 *
 * @returns {void}  adds a new row to the end of the table
 */
function addRow(lastPosition, resourceID = '', resourceName = '', resourceType = '')
{
    let mID = 0,
        name = '',
        icon = '',
        rawID,
        link,
        nextRowNumber,
        html,
        resourceHTML,
        orderingHTML;

    if (resourceID !== '')
    {
        mID = resourceID;
    }
    if (resourceName !== '')
    {
        name = resourceName;
    }

    rawID = resourceID.substring(0, resourceID.length - 1);

    if (resourceType !== '')
    {
        switch (resourceType)
        {
            case 'p':
                link = 'index.php?option=com_organizer&view=pool_edit&id=' + rawID;
                icon = 'icon-list';
                break;
            case 's':
                link = 'index.php?option=com_organizer&view=subject_edit&id=' + rawID;
                icon = 'icon-book';
                break;
        }
    }

    nextRowNumber = parseInt(lastPosition, 10) + 1;

    html = '<tr id="subRow' + nextRowNumber + '">';

    resourceHTML = '<td class="sub-name">';
    resourceHTML += '<a id="sub' + nextRowNumber + 'Link" href="' + link + '">';
    resourceHTML += '<span id="sub' + nextRowNumber + 'Icon" class="' + icon + '"></span>';
    resourceHTML += '<span id="sub' + nextRowNumber + 'Name">' + name + '</span>';
    resourceHTML += '</a>';
    resourceHTML += '<input id="sub' + nextRowNumber + '" type="hidden" value="' + mID + '" name="sub' + nextRowNumber + '">';
    resourceHTML += '</td>';

    orderingHTML = '<td class="sub-order">';

    orderingHTML += getButton('setFirst', 'icon-first', nextRowNumber, Joomla.JText._('ORGANIZER_MAKE_FIRST'));
    orderingHTML += getButton('moveUp', 'icon-previous', nextRowNumber, Joomla.JText._('ORGANIZER_MOVE_UP'));

    orderingHTML += '<input type="text" name="sub' + nextRowNumber + 'Order" ';
    orderingHTML += 'id="sub' + nextRowNumber + 'Order" size="2" value="' + nextRowNumber + '" ';
    orderingHTML += 'onchange="moveTo(' + nextRowNumber + ');">';

    orderingHTML += getButton('insertBlank', 'icon-download', nextRowNumber, Joomla.JText._('ORGANIZER_ADD_EMPTY'));
    orderingHTML += getButton('trash', 'icon-trash', nextRowNumber, Joomla.JText._('ORGANIZER_DELETE'));
    orderingHTML += getButton('moveDown', 'icon-next', nextRowNumber, Joomla.JText._('ORGANIZER_MOVE_DOWN'));
    orderingHTML += getButton('setLast', 'icon-last', nextRowNumber, Joomla.JText._('ORGANIZER_MAKE_LAST'));

    orderingHTML += '</td>';

    html += resourceHTML + orderingHTML + '</tr>';
    jQuery(html).appendTo(document.getElementsByClassName('subOrdinates')[0].tBodies[0]);
}

/**
 * Replaces data with empty values for the row at the given position.
 *
 * @param {int} position the row position to clear
 * @returns {void} modifies the dom
 */
function clearSubOrdinates(position)
{
    jQuery('#sub' + position + 'Icon').attr('class', '');
    jQuery('#sub' + position + 'Name').text('');
    jQuery('#sub' + position).val('');
    jQuery('#sub' + position + 'Link').attr('href', "");
    jQuery('#sub' + position + 'Order').val(position);
}

/**
 * Replaced the data at the new position with the data from the old position.
 *
 * @param {int} position the position to whose data will be replaced with cloned data
 * @param {Object} subOrdinate the element whose data will be used for cloning
 * @returns {void} modifies the dom
 */
function cloneSubOrdinate(position, subOrdinate)
{
    jQuery('#sub' + position + 'Icon').attr('class', (subOrdinate.class));
    jQuery('#sub' + position + 'Name').text(subOrdinate.name);
    jQuery('#sub' + position).val(subOrdinate.id);
    jQuery('#sub' + position + 'Link').attr('href', subOrdinate.link);
    jQuery('#sub' + position + 'Order').val(position);
}

/**
 * Creates a button for a given function.
 *
 * @param fName
 * @param icon
 * @param order
 * @param tip
 * @returns {string}
 */
function getButton(fName, icon, order, tip)
{
    let button = '';

    button += '<button onclick="' + fName + '(' + order + ');" title="' + tip + '">';
    button += '<span class="' + icon + '"></span></button>';

    return button
}

/**
 * Gets the selected items from the list and adds them to the subOrdinates table.
 *
 * @param {string} divID the id of the div
 * @param {string} type the type of the source
 * @return {void} modifies the dom
 */
function getCheckedItems(divID, type)
{
    const iFrame = jQuery('iframe');
    let subOrdinates, id, name;

    jQuery(divID + ' input:checked', iFrame.contents()).each(function () {
        subOrdinates = getSubOrdinates();
        id = jQuery(this).val() + type;
        name = jQuery(jQuery(this).parent().parent().subOrdinates()[1]).html();
        addRow(subOrdinates.length, id, name, type);
    });
}

/**
 * Retrieves an array of subordinate curriculum resources as currently depicted in the form
 *
 * @returns {array} the map of the current subOrdinates and their values
 */
function getSubOrdinates()
{
    // -1 Because of the header row.
    const count = document.getElementsByClassName('subOrdinates')[0].rows.length - 1;
    let current = [],
        index,
        order;

    for (index = 0; index < count; index++)
    {
        order = index + 1;
        current[index] = {};
        current[index].class = jQuery('#sub' + order + 'Icon').attr('class').trim();
        current[index].name = jQuery('#sub' + order + 'Name').text().trim();
        current[index].id = jQuery('#sub' + order).val();
        current[index].link = jQuery('#sub' + order + 'Link').attr('href');
        current[index].order = jQuery('#sub' + order + 'Order').val();
    }
    return current;
}

/**
 * Moves the values of the calling row down one row in the subordinates table
 *
 * @param {int} position
 *
 * @returns {void}
 */
function moveDown(position)
{
    let subOrdinates = getSubOrdinates(), currentOrder = parseInt(position, 10), current, next;

    // Element is last or blank
    if (currentOrder >= subOrdinates.length || (subOrdinates.length === currentOrder + 1 && subOrdinates[currentOrder - 1].name === ""))
    {
        return;
    }

    current = subOrdinates[currentOrder - 1];
    next = subOrdinates[currentOrder];

    // Move next up
    cloneSubOrdinate(currentOrder, next);

    // Set current to next
    cloneSubOrdinate(currentOrder + 1, current);
}

/**
 * Places the element at an explicitly defined position.
 *
 * @param {int} currentPosition
 *
 * @returns  {void}
 */
function moveTo(currentPosition)
{
    let subOrdinates = getSubOrdinates(),
        length = subOrdinates.length,
        subOrdinate = subOrdinates[currentPosition - 1],
        secondPosOrder = jQuery('#sub' + currentPosition + 'Order'),
        requestedPosition = secondPosOrder.val();

    requestedPosition = parseInt(requestedPosition, 10);

    if (isNaN(requestedPosition) === true || requestedPosition > length || (Number(requestedPosition) === length && subOrdinate.name === ""))
    {
        secondPosOrder.val(currentPosition);
        return;
    }

    if (currentPosition < requestedPosition)
    {
        shiftUp(currentPosition, requestedPosition, subOrdinates);
    }
    else
    {
        shiftDown(currentPosition, requestedPosition, subOrdinates);
    }

    cloneSubOrdinate(requestedPosition, subOrdinate);
}

/**
 * Moves the values of the calling row up one row in the subOrdinates table
 *
 * @param {int} position
 *
 * @returns {void}
 */
function moveUp(position)
{
    let subOrdinates = getSubOrdinates(), currentOrder = Number(position), current, previous;

    // Last or blank element
    if (currentOrder <= 1 || (subOrdinates.length === currentOrder && subOrdinates[currentOrder - 2].name === ""))
    {
        return;
    }

    previous = subOrdinates[currentOrder - 2];
    current = subOrdinates[currentOrder - 1];

    // Set current element to previous index
    cloneSubOrdinate(currentOrder - 1, current);

    // Set previous element to current index
    cloneSubOrdinate(currentOrder, previous);

}

/**
 * Moves the subordinate to the first position in the table. Moves down all subordinates which previously were ordered
 *
 * before it.
 *
 * @param {int} position the position of the subordinate to be moved
 * @returns {void} modifies the dom
 */
function setFirst(position)
{
    const subOrdinates = getSubOrdinates(), subOrdinate = subOrdinates[position - 1];

    if (subOrdinate.name !== "")
    {
        shiftDown(position, 1, subOrdinates);

        cloneSubOrdinate(1, subOrdinate);
    }
}

/**
 * Moves the subordinate to the last position in the table. Moves up all subordinates subsequent to the subordinate
 * being moved.
 *
 * @param {int} position the position of the subordinate to be moved
 * @returns {void} modifies the dom
 */
function setLast(position)
{
    const subOrdinates = getSubOrdinates(), subOrdinate = subOrdinates[position - 1];

    if (subOrdinate.name !== "")
    {
        shiftUp(position, subOrdinates.length, subOrdinates);

        cloneSubOrdinate(subOrdinates.length, subOrdinate);
    }
}

/**
 * Shifts all subOrdinates subsequent to the position down.
 *
 * @param {int} position the highest subordinate position which will be replaced
 * @param {int} stopPosition the position which defines the end of the shift process
 * @param {array} subOrdinates the map of the subOrdinates
 * @returns {void} modifies the dom
 */
function shiftDown(position, stopPosition, subOrdinates)
{
    let newPosition, sourcePosition;

    while (position > stopPosition)
    {
        newPosition = position;
        sourcePosition = position - 2;

        cloneSubOrdinate(newPosition, subOrdinates[sourcePosition]);
        position--;
    }
}

/**
 * Shift all subOrdinates subsequent to the position up one.
 *
 * @param {int} position the lowest subordinate position which will be replaced
 * @param {int} stopPosition the position which defines the end of the shift process
 * @param {array} subOrdinates the map of the subOrdinates
 * @returns {void} modifies the dom
 */
function shiftUp(position, stopPosition, subOrdinates)
{
    while (position < stopPosition)
    {
        cloneSubOrdinate(position, subOrdinates[position]);
        position++;
    }
}

/**
 * Removes a subordinate element from the form.
 *
 * @param {int} position the current position of the subordinate to be removed
 * @returns  {void} modifies the dom
 */
function trash(position)
{
    let subOrdinates = getSubOrdinates(),
        length = subOrdinates.length;

    shiftUp(position, length, subOrdinates);

    jQuery('#subRow' + length).remove();
}