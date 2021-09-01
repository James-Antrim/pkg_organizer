#region Bugs ###########################################################################################################
#new term was localized to '1970'
#program delete does not work
#/en not being accepted by site & organizer language select displays wrong value when this is enabled
#editing a new program has two 'create' buttons
#endregion

#region Features #######################################################################################################
#finish subject <=> event handling
#unit publishing & untis unit statistic key for organizer unpublished
#further integrate the status wait list into course participant handling
#integrate 'active' attributes for person, pool & program into documentation output
#link instance views from documentation resources
#add relevance output/toggle to the methods view
#create a csv import routine
#online evaluation portal? open to people who have attended at least x times?
#region thm groups
#crawler?
#extend profiles to allow thm groups profile urls
#link groups profiles
#endregion
#region rooms
#add batch processing for rooms (types, equipment, properties,...)
#capacity filters
#endregion
#region routing
#redo url building and parsing
#add breadcrumbs for appropriate views
#endregion
#tagging
#region interfaces to system landscape
#reading access to his-in-one for student information -> not stored!!!
## has the student completed the requirements of this semester/pool...
## gpa
#endregion
#list and item view pdf exports
#system plugin function for participation change detection
#editor-xtd plugin(s) for resources
#content plugin(s) for resource resolution
#region extend delta information
#add new columns created & removed
#adjust handling to avoid hard coded new/removed
#endregion
#region on schedule upload
#makes as many updates to participant schedules as possible
## event/method context dependent substitutions
# participant notifications
# person notifications
#endregion
#region department views
#resources
#endregion
#merge events
#endregion

#region Meta ###########################################################################################################
#standardize tooltip use across component
#replace JComponentTitle use
#error suppression through inheritance
#associate organizations with campuses
#associated programs with organization (ranked)
#component setting for currency
#media queries for individual list views
#rename instance participants to participations
#rename event coordinators to coordinators
#move constants to helpers
#region update
#create update.xml
#implement update mechanisms
#rudimentary...
##increment v# in manifest
##create new zip
##increment v# in update.xml
#endregion
#region message handling
##messages created in models specific to action
##no message - user interaction where nothing was done such as saving without changing any resource attributes
##success - successful user dm interaction
##notice - unsuccessful user dm interaction due to inconsistent data or any other non-critical error where the user is not at fault
##warning - unsuccessful user dm interaction where form manipulation most likely occurred
##error - unsuccessful user dm where hard db errors occurred or form url manipulation definitely occurred
#endregion
#sprachenzentrum as organization & associate persons
#remove roomtypes from untis
#region adapters
#application adapter (organizer helper)
#html adapter
#language adapter (after com_thm_organizer dies)
#input adapter
#endregion
#replace tcpdf use
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
