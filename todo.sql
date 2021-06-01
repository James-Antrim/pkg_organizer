#ical


#region Bugs ###########################################################################################################
#new term was localized to '1970'
#program delete does not work
#unformatted grid output in old schedule output not displaying as intended
#/en not being accepted by site & organizer language select displays wrong value when this is enabled
#mobile output of list views is terrible => especially courses
#editing a new program has two 'create' buttons
#endregion

#region Features #######################################################################################################
#finish subject <=> event handling
#unit publishing & untis unit statistic key for organizer unpublished
#component setting for currency
#further integrate the status wait list into course participant handling
#integrate 'active' attributes for person, pool & program into documentation output
#link instance views from documentation resources
#add relevance output/toggle to the methods view
#endregion

#region Meta ###########################################################################################################
#check 403 calls and think about if there should be a 401 check in front of it
#consistent message handling
##no message - user interaction where nothing was done such as saving without changing any resource attributes
##success - successful user dm interaction
##notice - unsuccessful user dm interaction due to inconsistent data or any other non-critical error where the user is not at fault
##warning - unsuccessful user dm interaction where form manipulation most likely occurred
##error - unsuccessful user dm where hard db errors occurred or form url manipulation definitely occurred
#sprachenzentrum as organization & associate persons
#make rooms primary in organizer & error if not existent
#remove roomtypes from untis
#make an adapters namespace for classes/files which alter joomla core functionality
##database adapter (organizer helper)
##application adapter (organizer helper)
##html adapter
##language adapter
##input adapter
##toolbar adapter
##base mvc files??
#endregion

#region Quality ########################################################################################################
#avoid re-writing the past when a new schedule is uploaded
##if a unit is deleted, do not mark the unit itself as removed, but any instances associated with it in the future
##if an instance from the past is deleted, ignore it
##...
#ensure associations are unique despite the dumb shit there
#write wrappers for core function calls which throw errors
#reformat text constant word order so that they are written as spoken: organizer_resource_edit => organizer_edit_resource
#move date formatting to the languages helper
#change the delta column to a signed int -1 'remove', 0 'no change from previous schedule', 1 'new'
#strict parameter typing
#sql binding
#shorten the OEs of lengthy instances view titles
#minimize helper use of input functions
#minimize helper data manipulation => only read as far as possible
#move standard data manipulation functions to the base table
#if authentication is required, but not completed perform a redirect to a login view
#clean up text constants
#add descriptive texts to organizations from organization websites or ask the dean's offices
#endregion

#region Projects #######################################################################################################

#degrees views
#lesson_statistics view
#programs view for frontend
#room_display / upcoming view
#terms views

#region Deputat ########################################################################################################
#deputat view
#methods => KapVO mapping
#adjust untis according to any KapVO resolution
#endregion

#region Documentation Export ###########################################################################################
#todo export the documentation of an individual subject as pdf
#todo export the documentation of a program's subjects as pdf (catalog)
#todo catalogs should have navigation
#todo catalogs should have requisite links
#todo catalogs should have curriculum related texts
#endregion

#region Frequencies ####################################################################################################
#change the frequency ids from 0-5 to 1-6
#update frequencies, programs, subjects
#adjust lsf import to compensate
#make the frequencies field reference the table
#endregion

#region Internal Planning###############################################################################################

#bind grids to runs?
#complete implementation
#allow organizations to add optional/manual holidays that are only valid for themselves

#region Blocks & Terms Strategy
#come up with a longer term and more inclusive strategy for blocks
#direct migration leaves 'missing' blocks
#create during planning process or after the creation of new terms
#endregion

#endregion##############################################################################################################

#region Merging ########################################################################################################
#Categories, Events & Participants

#delete unused events
#automatically merge events with the same entry key items
#add unique key for event entry key items
#reactivate new functions as appropriate

#endregion

#region MultiSelect ####################################################################################################
#create a standard fix for default multiple select handling and include it per default, especially for curriculum resources
#endregion

#region Notification ###################################################################################################
#create a notification function for the delta between two schedules
#create a notification function for schedule changes made which impact personal schedules
#expand participants profiles as necessary for notification and other feature opt-ins
#endregion

#region Participants ###################################################################################################
#anonymize 'external' participants
#migrate participant schedules
#migrate personal schedule handling
#endregion

#region Programs #######################################################################################################
#adjust/expand inclusive structural adjustments to organizer tables for programs
#migrate program data from the main page
#create program views
#endregion

#region PDF/XLS Output #################################################################################################
#instances
#automatic generation for list views
#endregion

#region Tagging ########################################################################################################
#add resource_field tables to associate resources with multiple fields in addition to the existing reference
#rename the existing reference for pools and subjects to display field with appropriate texts
#field associations with curriculum resources should carry over to the scheduling resources they are associated with
#endregion##############################################################################################################

#region Toolbar#########################################################################################################
#create an extensible override for toolbar
#create new buttons which submit the form in a new tab
#endregion

#endregion

#region Miscellaneous ##################################################################################################

#create a strategy around required image files: logo, signature, ...
#decide what to do with inactive campuses and buildings
#replace jquery use with plain js
#add a addScript/addStyleSheet wrapper function to the html helper which automatically loads the de-/mined files
#create a coherent strategy for the structured output of organization related resources

#endregion##############################################################################################################
