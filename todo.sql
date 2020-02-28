#region Stage 02: Curriculum############################################################################################
#Resources/Tables: Curricula, Pools, Prerequisites, Programs, Subject Persons and Subjects

#TODO Integrate 'active' attributes for person, pool & program into documentation output
#TODO Find out if any pool 'name' attributes are not being used and remove them
#TODO make the frequencies field reference the table
#TODO ensure coordinators have documentation access
#TODO ensure the constants used in the component configuration settings work
#TODO add passive synchronization of persons

#region Curriculum View-------------------------------------------------------------------------------------------------
#TODO stuff
#endregion--------------------------------------------------------------------------------------------------------------

#region Pool Edit View--------------------------------------------------------------------------------------------------
#TODO check apply
#TODO check save
#TODO check cancel
#TODO check curriculum construction
#endregion--------------------------------------------------------------------------------------------------------------

#region Pools View------------------------------------------------------------------------------------------------------
#TODO the program display should also be able to display multiple
#TODO check search
#TODO check filtering
#TODO check limiting
#TODO check sorting
#TODO check new
#TODO check edit
#TODO check delete
#endregion--------------------------------------------------------------------------------------------------------------

#region Program Edit View-----------------------------------------------------------------------------------------------
#TODO check apply
#TODO check save
#TODO check cancel
#TODO check curriculum construction
#endregion--------------------------------------------------------------------------------------------------------------

#region Programs View---------------------------------------------------------------------------------------------------
#TODO the organization display should also be able to display multiple
#TODO check search
#TODO check filtering
#TODO check limiting
#TODO check sorting
#TODO check new
#TODO check edit
#TODO check import
#TODO check update
#TODO check delete
#region back end
#TODO prefilter for programs associated with organizations for which documentation authorization exists
#endregion
#region front end
#TODO provide links to curriculum and subjects views
#endregion
#endregion--------------------------------------------------------------------------------------------------------------

#region Subject Edit View-----------------------------------------------------------------------------------------------
#TODO check apply
#TODO check save
#TODO check cancel
#TODO check curriculum construction
#endregion--------------------------------------------------------------------------------------------------------------

#region Subject Item View-----------------------------------------------------------------------------------------------
#TODO ???
#endregion--------------------------------------------------------------------------------------------------------------

#region Subjects View---------------------------------------------------------------------------------------------------
#TODO check search
#TODO check filtering
#TODO Add instruction language filter
#TODO check limiting
#TODO check sorting
#TODO check new
#TODO check edit
#TODO check update
#TODO check delete
#region back end
#TODO ???
#endregion
#region front end
#TODO ???
#endregion
#endregion--------------------------------------------------------------------------------------------------------------

#endregion##############################################################################################################

#region Stage 03: Facility Management###################################################################################
#Resources/Tables: Buildings, Campuses, Monitors, Room Types, Rooms

#TODO Relink curriculum and subjects menu items
#TODO Link new subject views from the old schedule view
#TODO Set the documentation rubric in the THM Organizer menu to administrator access
#TODO Set the documentation views in the THM Organizer component to administrator access
#TODO Check the functionality of fm views and correct any problems found

#region Building Edit View----------------------------------------------------------------------------------------------
#TODO check apply
#TODO check save
#TODO check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Buildings View--------------------------------------------------------------------------------------------------
#TODO check search
#TODO check filtering
#TODO check limiting
#TODO check sorting
#TODO check new
#TODO check edit
#TODO check delete
#endregion--------------------------------------------------------------------------------------------------------------

#region Campus Edit View------------------------------------------------------------------------------------------------
#TODO check apply
#TODO check save
#TODO check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Campuses View---------------------------------------------------------------------------------------------------
#TODO check search
#TODO check filtering
#TODO check limiting
#TODO check sorting
#TODO check new
#TODO check edit
#TODO check delete
#endregion--------------------------------------------------------------------------------------------------------------

#region Monitor Edit View-----------------------------------------------------------------------------------------------
#TODO check apply
#TODO check save
#TODO check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Monitors View---------------------------------------------------------------------------------------------------
#TODO check search
#TODO check filtering
#TODO check limiting
#TODO check sorting
#TODO check new
#TODO check edit
#TODO check delete
#endregion--------------------------------------------------------------------------------------------------------------

#region Room Edit View--------------------------------------------------------------------------------------------------
#TODO check apply
#TODO check save
#TODO check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Rooms View------------------------------------------------------------------------------------------------------
#TODO check search
#TODO check filtering
#TODO check limiting
#TODO check sorting
#TODO check new
#TODO check edit
#TODO check delete
#endregion--------------------------------------------------------------------------------------------------------------

#region Roomtype Edit View----------------------------------------------------------------------------------------------
#TODO check apply
#TODO check save
#TODO check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Roomtypes View--------------------------------------------------------------------------------------------------
#TODO check search
#TODO check filtering
#TODO check limiting
#TODO check sorting
#TODO check new
#TODO check edit
#TODO check delete
#endregion--------------------------------------------------------------------------------------------------------------

#endregion##############################################################################################################

#region Stage 04: Methods, Schedules & Terms############################################################################

#TODO Revamp the schedule migration functions for the new circumstances and structure
#TODO Conceptualize and implement a schedule synchronization strategy
#TODO methods => kapvo mapping
#TODO adjust both old organizer, new organizer and untis according to any kapvo resolution
#TODO create terms model?

#region Method Edit View------------------------------------------------------------------------------------------------
#TODO check apply
#TODO check save
#TODO check cancel
#endregion

#region Methods View----------------------------------------------------------------------------------------------------
#TODO check search
#TODO check filtering
#TODO check limiting
#TODO check sorting
#TODO check new
#TODO check edit
#TODO check delete
#endregion

#region Schedule Edit (Upload) View-------------------------------------------------------------------------------------
#TODO check upload
#TODO check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Schedules View--------------------------------------------------------------------------------------------------
#TODO check search
#TODO check filtering
#TODO check limiting
#TODO check sorting
#TODO check upload(new)
#TODO check activate
#TODO check delta
#TODO check delete
#endregion--------------------------------------------------------------------------------------------------------------

#endregion##############################################################################################################

#region Stage 05: Participants##########################################################################################

#TODO Revamp the schedule migration functions for the new circumstances and structure
#TODO Conceptualize and implement a schedule synchronization strategy
#TODO should participants be moved up to p4 or p5?

#endregion##############################################################################################################

#region Stage 06: Static Planning Resources#############################################################################
#Resources/Tables: Categories, Event Coordinators, Events, Group Publishing, Groups & Subject Events

#TODO Implement migration/synchronization of categories, groups, group publishing and events
#TODO Conceptualize and implement a planning synchronization strategy
#TODO add group association support
## xml import
## edit views
## options fields
## remove events table organizationID column
#TODO move the event => organization associations from the events table to the associations table

#endregion##############################################################################################################

#region Stage 07: Dynamic Planning Resources############################################################################
#Resources/Tables: Blocks, I-Groups, I-Participants, I-Persons, I-Rooms, Instances, Units

#region remove roles table use
#TODO create class constants if not existent
#TODO create role language constants
#TODO keep roles synchronous with those used by subject persons 1 => coordinator, 2 => teacher, 3 => ...
#endregion

#TODO Revamp the configuration migration functions for the new circumstances and structure

#endregion##############################################################################################################

#region Stage 08: Merge Views###########################################################################################

#TODO add passive synchronization for schedule data from old organizer
#TODO merge functionality for categories, events, groups, persons and rooms
#TODO Uncomment buttons

#endregion##############################################################################################################

#region Stage 09: Front End Schedule Related Views######################################################################

#TODO Move the icalcreator directory back & uncomment it in the install manifest

#region Deputat View
#TODO Finish implementation
#endregion

#region Planned Events View (Event List)
#TODO adjust to the new structure for existing menu items
#TODO add filters to dynamically adjust content
#TODO add menu parameters for campus, method, organization, ....
#endregion

#region Options Views
#TODO Integrate active attributes into selectable options
#endregion

#region Room Overview View
#TODO Fix any problems which have arisen with the implementation
#endregion

#region Schedule Item View
#TODO come up with a coherent strategy for all the differing perspectives on this
#TODO ???
#endregion

#endregion##############################################################################################################

#region Stage 10: Courses###############################################################################################
#Resources/Tables: Course Participants, Courses

#TODO Merge functionality: Participants

#endregion##############################################################################################################

#region Afterword#######################################################################################################

#TODO add degree management views
#TODO create a strategy around required image files: logo, signature, ...
#TODO clean up the FILTER and the SELECT text constants
#TODO decide what to do with inactive campuses and buildings

#region Frequencies
#TODO change the frequency ids from 0-5 to 1-6
#TODO update frequencies, programs, subjects
#TODO adjust lsf import to compensate
#endregion

#region Organizations
#TODO add organization view
#TODO add descriptive texts from organization websites or ask the dean's offices
#TODO create a coherent strategy for the structured output of organization related resources
#endregion

#region Programs
#TODO migrate program data from the live site
#TODO create program views
#endregion

#endregion##############################################################################################################

#region Feature: Internal Planning######################################################################################

#TODO bind grids to runs?
#TODO complete the implementation of internal scheduling
#TODO allow organizations to add optional/manual holidays that are only valid for themselves

#region Blocks & Terms Strategy
#TODO come up with a longer term and more inclusive strategy for blocks
# direct migration leaves 'missing' blocks
# create during planning process or after the creation of new terms
#TODO create terms views
#endregion

#endregion##############################################################################################################

#region Feature: Tagging################################################################################################

#TODO add resource_field tables to associate resources with multiple fields in addition to the existing reference
#TODO rename the existing reference for pools and subjects to display field with appropriate texts
#TODO field associations with curriculum resources should carry over to the scheduling resources they are associated with

#endregion##############################################################################################################

#region Feature: Search#################################################################################################

#TODO add resource_field tables to associate resources with multiple fields in addition to the existing reference
#TODO rename the existing reference for pools and subjects to display field with appropriate texts
#TODO field associations with curriculum resources should carry over to the scheduling resources they are associated with

#endregion##############################################################################################################

#region Templates#######################################################################################################

#region Edit View-------------------------------------------------------------------------------------------------------
#TODO check apply
#TODO check save
#TODO check cancel
#endregion

#region List View-------------------------------------------------------------------------------------------------------
#TODO check search
#TODO check filtering
#TODO check limiting
#TODO check sorting
#TODO check new
#TODO check edit
#TODO check delete
#endregion

#endregion