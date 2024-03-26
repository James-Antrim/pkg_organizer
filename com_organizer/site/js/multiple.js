/**
 * Small library of functions for multiple select boxes.
 */

/**
 * Returns the selected values of a multiple select box.
 *
 * @param {string} elementID the id of the multiple select box
 *
 * @returns {[]} the selected values as an array
 */
function getMultipleValues(elementID)
{
    const selectedOptions = document.getElementById(elementID).selectedOptions;
    let index = 0, selectedValue, selectedValues = [];

    for (index; index < selectedOptions.length; index++)
    {
        if (selectedOptions[index].selected)
        {
            selectedValue = selectedOptions[index].value;

            if (selectedValue === -1)
            {
                selectedValues = [-1];
                break;
            }

            selectedValues.push(selectedOptions[index].value);
        }
    }

    return selectedValues;
}

/**
 * Merges multiple arrays into a single array with unique values.
 *
 * @param {[]} arguments the arguments used in calling the function
 *
 * @return {[]} the unique selected values
 */
function mergeMultipleUnique()
{
    const uniqueValues = [];
    let argIndex = 0, itemIndex = 0;

    for (argIndex; argIndex < arguments.length; argIndex++)
    {
        if (Array.isArray(arguments[argIndex]))
        {
            for (itemIndex; itemIndex < arguments[argIndex].length; itemIndex++)
            {
                if (uniqueValues.indexOf(arguments[argIndex][itemIndex]) === -1)
                {
                    uniqueValues.push(arguments[argIndex][itemIndex]);
                }
            }
        }
    }

    return uniqueValues;
}

/**
 * Sets the selected values of a multiple select box.
 *
 * @param {string} elementID the id of the multiple select box
 * @param {[]}    values     the values to set the multiple select box with
 *
 * @return void modifies the DOM Element with the given ID
 */
function setMultipleValues(elementID, values)
{
    const options = document.getElementById(elementID).options;
    let index = 0;

    for (index; index < options.length; index++)
    {
        if (values.indexOf(options[index].value) !== -1)
        {
            options[index].setAttribute('selected', 'selected');
        }
    }
}