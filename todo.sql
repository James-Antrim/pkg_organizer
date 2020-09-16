#region General#########################################################################################################

#todo create a standard fix for default multiple select handling and include it per default, remove chosen library use
#todo add terms management
#todo create terms model?
#todo redo search
#todo methods => kapvo mapping
#todo adjust both old organizer, new organizer and untis according to any kapvo resolution
#todo deputat view
#todo room_display view
#todo lesson_statistics view
#todo remove room types from untis & stop validating in organizer
#todo make rooms primary in organizer & error if not existent
#todo component setting for currency
#todo sql binding
#todo strict typing
#todo fix unformatted grid output in old schedule output
#todo sprachenzentrum as organization & associate persons
#todo unit specific publishing
#todo untis unit statistic key for organizer unpublished
#todo add  pdf/xml support for list views
#todo change the delta column to a signed int -1 'remove', 0 'no change from previous schedule', 1 'new'
#todo finish and publish subject <-> events processing
#todo move date formatting to the languages helper
#todo reformat texts to be built in a speaking order organizer_resource_edit => organizer_edit_resource
#todo redo exception handling with redirects (editmodel)
#todo ensure associations are unique despite the dumb shit there

#endregion

#region Courses / Participants##########################################################################################

#todo integrate the status wait list into course participant handling
#todo make lack of dates blocking for registration

#endregion

#region instances ######################################################################################################

#todo add pdf/xml formats
#todo add custom interval for 'Veranstaltungen in der Vorlesungsfreie Zeit'
#todo shorten the OEs of lengthy titles
#todo add 'termine' to the german title output as appropriate

#endregion

#region Curriculum######################################################################################################

#todo Integrate 'active' attributes for person, pool & program into documentation output
#todo make the frequencies field reference the table
#todo ensure coordinators have documentation access
#todo create default selection handling for department, curriculum and superordinate selection boxes
#todo program delete
#todo program apply & import
#todo program save & update
#todo improve programs frontend view
#todo migrate progam data from the main page

#endregion

#region Planning########################################################################################################

#todo finish event editing
#todo migrate old schedule ajax calls to new helpers
#todo migrate schedule upload
#todo migrate schedule activate
#todo migrate schedule delta
#todo add schedule delta notification

#region Schedule Edit (Upload) View-------------------------------------------------------------------------------------
#todo check upload
#todo check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Schedules View--------------------------------------------------------------------------------------------------
#todo check search
#todo check filtering
#todo check limiting
#todo check sorting
#todo check upload(new)
#todo check activate
#todo check delta
#todo check delete
#endregion--------------------------------------------------------------------------------------------------------------

#endregion


#region Participants####################################################################################################

#todo instance participants

#endregion

#region Merging#########################################################################################################
#Categories, Groups, Events, Persons & Rooms

#todo delete unused events
#todo automatically merge events with the same entry key items
#todo add unique key for event entry key items
#todo reactivate new functions as appropriate
#todo add update to modified columns

#endregion

#region Stage 09: Front End Schedule Related Views######################################################################

#todo Move the icalcreator directory back & uncomment it in the install manifest
#todo Revisit access filters for organization options in the frontend context
#todo Link schedules from curricula, programs, subjects & subject views
#todo Diff the live and dev sites to get iterim component changes

#region Deputat View
#todo Finish implementation
#endregion

#region Planned Events View (Event List)
#todo adjust to the new structure for existing menu items
#todo add filters to dynamically adjust content
#todo add menu parameters for campus, method, organization, ....
#endregion

#region Options Views
#todo Integrate active attributes into selectable options
#endregion

#region Room Overview View
#todo Fix any problems which have arisen with the implementation
#endregion

#region Schedule Item View
#todo come up with a coherent strategy for all the differing perspectives on this
#todo ???
#endregion

#endregion##############################################################################################################

#region Stage 10: Courses###############################################################################################
#Resources/Tables: Course Participants, Courses

#todo Merge functionality: Participants

#endregion##############################################################################################################

#region Afterword#######################################################################################################

#todo add degree management views
#todo create a strategy around required image files: logo, signature, ...
#todo clean up the FILTER and the SELECT text constants
#todo decide what to do with inactive campuses and buildings
#todo replace jquery use with plain js
#todo add a addScript/addStyleSheet wrapper function to the html helper which automatically loads the de-/mined files

#region Frequencies
#todo change the frequency ids from 0-5 to 1-6
#todo update frequencies, programs, subjects
#todo adjust lsf import to compensate
#endregion

#region Organizations
#todo add organization view
#todo add descriptive texts from organization websites or ask the dean's offices
#todo create a coherent strategy for the structured output of organization related resources
#endregion

#region Programs
#todo migrate program data from the live site
#todo create program views
#endregion

#endregion##############################################################################################################

#region Feature: Documentation Export###################################################################################
#todo export the documentation of an individual subject as pdf
#todo export the documentation of a program's subjects as pdf (catalog)
#todo catalogs should have navigation
#todo catalogs should have requisite links
#todo catalogs should have curriculum related texts
#endregion

#region Feature: Internal Planning######################################################################################

#todo bind grids to runs?
#todo complete the implementation of internal scheduling
#todo allow organizations to add optional/manual holidays that are only valid for themselves

#region Blocks & Terms Strategy
#todo come up with a longer term and more inclusive strategy for blocks
# direct migration leaves 'missing' blocks
# create during planning process or after the creation of new terms
#todo create terms views
#endregion

#endregion##############################################################################################################

#region Feature: Tagging################################################################################################

#todo add resource_field tables to associate resources with multiple fields in addition to the existing reference
#todo rename the existing reference for pools and subjects to display field with appropriate texts
#todo field associations with curriculum resources should carry over to the scheduling resources they are associated with

#endregion##############################################################################################################

#region Feature: Search#################################################################################################

#todo add resource_field tables to associate resources with multiple fields in addition to the existing reference
#todo rename the existing reference for pools and subjects to display field with appropriate texts
#todo field associations with curriculum resources should carry over to the scheduling resources they are associated with

#endregion##############################################################################################################

#region Migrate Programs################################################################################################
#todo Migrate extended program date from the homepage to organizer inclusive structural adjustments to organizer tables
#endregion

#region Templates#######################################################################################################

#region Edit View-------------------------------------------------------------------------------------------------------
#todo check apply
#todo check save
#todo check cancel
#endregion

#region List View-------------------------------------------------------------------------------------------------------
#todo check search
#todo check filtering
#todo check limiting
#todo check sorting
#todo check new
#todo check edit
#todo check delete
#endregion

#endregion