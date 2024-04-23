"use strict";

/**
 * Add a new row to the end of the table.
 * @returns {void}
 */
function addRow()
{
    const table = document.getElementById('so-table'),
        order = table.rows.length,
        prefix = 'sub' + order,
        row = table.insertRow(),
        resource = row.insertCell(),
        ordering = row.insertCell();

    let resourceHTML,
        orderingHTML;

    row.className = 'subRow' + order;
    resource.className = 'sub-name';
    ordering.className = 'sub-order';

    resourceHTML = '<span id="' + prefix + 'Icon" class="far fa-square"></span>';
    resourceHTML += '<span id="' + prefix + 'Name">' + Joomla.JText._('ORGANIZER_EMPTY_PANEL') + '</span>';
    resourceHTML += '<input id="' + prefix + '" type="hidden" value="" name="' + prefix + '">';
    resource.innerHTML = resourceHTML;

    orderingHTML = getButton('setFirst', 'fa fa-fast-backward', order, Joomla.JText._('ORGANIZER_MAKE_FIRST'));
    orderingHTML += getButton('moveUp', 'fa fa-step-backward', order, Joomla.JText._('ORGANIZER_MOVE_UP'));
    orderingHTML += '<input type="text" name="' + prefix + 'Order" ';
    orderingHTML += 'id="' + prefix + 'Order" size="2" value="' + order + '" ';
    orderingHTML += 'onchange="moveTo(' + order + ');">';
    orderingHTML += getButton('insertBlank', 'far fa-plus-square', order, Joomla.JText._('ORGANIZER_ADD_EMPTY_PANEL'));
    orderingHTML += getButton('trash', 'fa fa-times', order, Joomla.JText._('ORGANIZER_DELETE'));
    orderingHTML += getButton('moveDown', 'fa fa-step-forward', order, Joomla.JText._('ORGANIZER_MOVE_DOWN'));
    orderingHTML += getButton('setLast', 'fa fa-fast-forward', order, Joomla.JText._('ORGANIZER_MAKE_LAST'));
    ordering.innerHTML = orderingHTML;
}

/**
 * Replaced the data at the new position with the data from the old position.
 *
 * @param {int} row the position to whose data will be replaced with cloned data
 * @param {Object} subOrdinate the element whose data will be used for cloning
 * @returns {void}
 */
function cloneSubOrdinate(row, subOrdinate)
{
    const so = 'sub' + row;

    document.getElementById(so).value = subOrdinate.id;
    document.getElementById(so + 'Icon').className = subOrdinate.class;
    document.getElementById(so + 'Name').textContent = subOrdinate.name;
    document.getElementById(so + 'Order').value = row;
}

/**
 * Creates a button for a given function.
 *
 * @param {string} fName the name of the button's function
 * @param {string} iconClass the icon to display on the button
 * @param {int} order the row's order value
 * @param {string} tip the tip to display on the button
 *
 * @returns {string}
 */
function getButton(fName, iconClass, order, tip)
{
    const className = ' class="btn btn-primary"',
        icon = '<span class="' + iconClass + '"></span>',
        onClick = 'onclick="' + fName + '(' + order + ');"',
        type = ' type="button"';

    tip = ' title="' + tip + '"';

    return '<button' + className + onClick + tip + type + '>' + icon + '</button>';
}

/**
 * Retrieves an array of subordinate curriculum resources as currently depicted in the form
 *
 * @returns {array}
 */
function getSubOrdinates()
{
    // -1 Because of the header row.
    const count = document.getElementById('so-table').rows.length - 1, subordinates = [];

    let index, so;

    for (index = 1; index <= count; index++)
    {
        so = 'sub' + index;
        subordinates[index] = {};
        subordinates[index].class = document.getElementById(so + 'Icon').className.trim();
        subordinates[index].name = document.getElementById(so + 'Name').textContent.trim();
        subordinates[index].id = document.getElementById(so).value;
        subordinates[index].order = document.getElementById(so + 'Order').value;
    }

    return subordinates;
}

/**
 * Increments the indexing of all subsequent rows, and adds replaces the indexed row with a blank.
 * @param {int} at the number of the row where the blank should be inserted
 * @returns {void}
 */
function insertBlank(at)
{
    let blank, subOrdinates;

    // Add a new blank row to the table.
    addRow();

    // Get the new subordinates collection.
    subOrdinates = getSubOrdinates();

    // Copy the blank
    blank = subOrdinates[subOrdinates.length - 1];

    // Shift up from insertion site until the end
    shiftUp(subOrdinates.length - 1, at, subOrdinates);

    // Clone the copy into the insertion site
    cloneSubOrdinate(at, blank);
}

/**
 * Moves the values of the calling row down one row in the subordinates table
 * @param {int} currentNo the number of the row calling the function
 * @returns {void}
 */
function moveDown(currentNo)
{
    const subOrdinates = getSubOrdinates();
    let current, next;

    currentNo = Number(currentNo);

    // Element is last or blank
    if (currentNo >= subOrdinates.length || (subOrdinates.length === currentNo + 1 && subOrdinates[currentNo - 1].id === ""))
    {
        return;
    }

    current = subOrdinates[currentNo];
    next = subOrdinates[currentNo + 1];

    // Move next up
    cloneSubOrdinate(currentNo, next);

    // Set current to next
    cloneSubOrdinate(currentNo + 1, current);
}

/**
 * Places the calling row at a specific place in the ordering.
 * @param {int} currentNo the number of the row calling the function
 * @returns  {void}
 */
function moveTo(currentNo)
{
    const subOrdinates = getSubOrdinates(),
        current = subOrdinates[currentNo],
        currentOrder = document.getElementById('sub' + currentNo + 'Order'),
        empty = Joomla.JText._('ORGANIZER_EMPTY_PANEL'),
        last = subOrdinates.length - 1,
        requestedPosition = currentOrder.value,
        invalid = isNaN(requestedPosition) === true,
        tooHigh = requestedPosition > last,
        trailingBlank = requestedPosition === last && current.name === empty;

    if (invalid || tooHigh || trailingBlank)
    {
        currentOrder.value = currentNo;
        return;
    }

    if (currentNo < requestedPosition)
    {
        shiftDown(currentNo, requestedPosition, subOrdinates);
    }
    else
    {
        shiftUp(currentNo, requestedPosition, subOrdinates);
    }

    cloneSubOrdinate(requestedPosition, current);
}

/**
 * Moves the values of the calling row up one row in the subOrdinates table
 * @param {int} currentNo the number of the row calling the function
 * @returns {void}
 */
function moveUp(currentNo)
{
    const subOrdinates = getSubOrdinates();

    let current, previous;

    // Last or blank element
    if (currentNo <= 1 || (subOrdinates.length === currentNo && subOrdinates[currentNo - 2].name === ""))
    {
        return;
    }

    previous = subOrdinates[currentNo - 1];
    current = subOrdinates[currentNo];

    // Set current element to previous index
    cloneSubOrdinate(currentNo - 1, current);

    // Set previous element to current index
    cloneSubOrdinate(currentNo, previous);
}

/**
 * Moves the subordinate to the first position in the table. Moves down all subordinates before the subordinate being moved.
 * @param {int} currentNo the number of the row calling the function
 * @returns {void}
 */
function setFirst(currentNo)
{
    const subOrdinates = getSubOrdinates(), subOrdinate = subOrdinates[currentNo];

    if (subOrdinate.name !== "")
    {
        shiftUp(currentNo, 1, subOrdinates);

        cloneSubOrdinate(1, subOrdinate);
    }
}

/**
 * Moves the subordinate to the last position in the table. Moves up all subordinates after the subordinate being moved.
 * @param {int} currentNo the number of the row calling the function
 * @returns {void}
 */
function setLast(currentNo)
{
    const subOrdinates = getSubOrdinates(), last = subOrdinates.length - 1, subOrdinate = subOrdinates[currentNo];

    if (subOrdinate.id !== "")
    {
        shiftDown(currentNo, last, subOrdinates);
        cloneSubOrdinate(last, subOrdinate);
    }
}

/**
 Shifts all subOrdinates after the position to the next lower number row.
 * @param {int} currentNo the number of the row calling the function
 * @param {int} stopNo the number of the row which defines the high end of the shift process
 * @param {array} subOrdinates the map of the subOrdinates
 * @returns {void}
 */
function shiftDown(currentNo, stopNo, subOrdinates)
{
    currentNo = Number(currentNo);

    while (currentNo < stopNo)
    {
        cloneSubOrdinate(currentNo, subOrdinates[currentNo + 1]);
        currentNo++;
    }
}

/**
 * Shifts all subOrdinates after the position to the next higher number row.
 * @param {int} currentNo the number of the row calling the function
 * @param {int} stopNo the number of the row which defines the low end of the shift process
 * @param {array} subOrdinates a container with the unique values of the subordinate items
 * @returns {void}
 */
function shiftUp(currentNo, stopNo, subOrdinates)
{
    let newRow, sourceRow;

    while (currentNo > stopNo)
    {
        newRow = currentNo;
        sourceRow = currentNo - 1;

        cloneSubOrdinate(newRow, subOrdinates[sourceRow]);
        currentNo--;
    }
}

/**
 * Removes a subordinate element from the form.
 * @param {int} currentNo the number of the row calling the function
 * @returns  {void} modifies the dom
 */
function trash(currentNo)
{
    const subOrdinates = getSubOrdinates(),
        length = subOrdinates.length - 1;

    //
    shiftDown(currentNo, length, subOrdinates);

    document.getElementById('subRow' + length).remove();
}