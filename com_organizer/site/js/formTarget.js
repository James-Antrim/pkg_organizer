// noinspection JSUnusedGlobalSymbols
/**
 * Submits the form to a new tab.
 *
 * @param  {String}  task      The given task
 *
 * @returns  {void}
 */
function formTarget(task)
{
    const button = document.createElement('input'),
        form = document.getElementById('adminForm');

    button.classList.add('hidden');
    button.formTarget = '_blank'
    button.type = 'submit';
    // noinspection JSUnresolvedReference
    form.task.value = task;
    form.appendChild(button).click();
    form.removeChild(button);
    window.location.reload();
}