/**
 * Submits the form to a new tab.
 *
 * @param  {String}  task      The given task
 *
 * @returns  {void}
 */
function newTab(task) {

    const button = document.createElement('input'),
        form = document.getElementById('adminForm');

    button.style.display = 'none';
    button.formTarget = '_blank'
    button.type = 'submit';
    form.task.value = task;
    form.appendChild(button).click();
    form.removeChild(button);
}