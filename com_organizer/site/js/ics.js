function makeLink()
{
    const variables = Joomla.getOptions('variables', {}), url = variables.ICS_URL;

    window.prompt(Joomla.JText._('ORGANIZER_GENERATE_LINK'), url);
}