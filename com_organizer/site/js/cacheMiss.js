// Suppresses cache miss errors.
if (window.history.replaceState)
{
    window.history.replaceState(null, null, window.location.href);
}