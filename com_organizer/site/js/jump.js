let touchStart = null;

function jump(jumpDate) {
    const dateInput = document.getElementById('list_date'),
        form = document.getElementById('adminForm');

    dateInput.value = jumpDate;
    form.submit();
}

window.addEventListener("touchstart", function (event) {
    if (event.touches.length === 1) {
        touchStart = event.touches.item(0).clientX;
    }
});

window.addEventListener("touchend", function (event) {
    const offset = 100, variables = Joomla.getOptions('variables', {});
    let touchEnd = null;

    if (touchStart) {
        //the only finger that hit the screen left it
        touchEnd = event.changedTouches.item(0).clientX;

        if (touchEnd > touchStart + offset) {
            jump(variables.yesterday);
        }
        if (touchEnd < touchStart - offset) {
            jump(variables.tomorrow);
        }
    }
});