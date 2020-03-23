#region Stage 02: Curriculum############################################################################################
#Resources/Tables: Curricula, Pools, Prerequisites, Programs, Subject Persons and Subjects

#todo Integrate 'active' attributes for person, pool & program into documentation output
#todo Find out if any pool 'name' attributes are not being used and remove them
#todo make the frequencies field reference the table
#todo ensure coordinators have documentation access
#todo ensure the constants used in the component configuration settings work
#todo add passive synchronization of persons
#todo create a standard fix for default multiple select handling and include it per default, remove chosen library use

#region Pool Edit View--------------------------------------------------------------------------------------------------
#todo check apply
#todo check save
#todo check cancel
#todo check curriculum construction
#endregion--------------------------------------------------------------------------------------------------------------

#region Pools View------------------------------------------------------------------------------------------------------
#todo the program display should also be able to display multiple
#todo check new
#todo check edit
#todo check delete
#endregion--------------------------------------------------------------------------------------------------------------

#region Program Edit View-----------------------------------------------------------------------------------------------
#todo check apply
#todo check save
#todo check cancel
#todo check curriculum construction
#endregion--------------------------------------------------------------------------------------------------------------

#region Programs View---------------------------------------------------------------------------------------------------
#todo check new
#todo check edit
#todo check import
#todo check update
#todo check delete
#region front end
#todo style the links better
#endregion
#endregion--------------------------------------------------------------------------------------------------------------

#region Subject Edit View-----------------------------------------------------------------------------------------------
#todo check apply
#todo check save
#todo check cancel
#todo check curriculum construction
#endregion--------------------------------------------------------------------------------------------------------------

#region Subjects View---------------------------------------------------------------------------------------------------
#todo check new
#todo check edit
#todo check update
#todo check delete
#endregion--------------------------------------------------------------------------------------------------------------

#endregion##############################################################################################################

#region Stage 03: Facility Management###################################################################################
#Resources/Tables: Buildings, Campuses, Monitors, Room Types, Rooms

#todo Relink curriculum and subjects menu items
#todo Link new subject views from the old schedule view
#todo Set the documentation rubric in the THM Organizer menu to administrator access
#todo Set the documentation views in the THM Organizer component to administrator access
#todo Check the functionality of fm views and correct any problems found

#region Building Edit View----------------------------------------------------------------------------------------------
#todo check apply
#todo check save
#todo check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Buildings View--------------------------------------------------------------------------------------------------
#todo check search
#todo check filtering
#todo check limiting
#todo check sorting
#todo check new
#todo check edit
#todo check delete
#endregion--------------------------------------------------------------------------------------------------------------

#region Campus Edit View------------------------------------------------------------------------------------------------
#todo check apply
#todo check save
#todo check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Campuses View---------------------------------------------------------------------------------------------------
#todo check search
#todo check filtering
#todo check limiting
#todo check sorting
#todo check new
#todo check edit
#todo check delete
#endregion--------------------------------------------------------------------------------------------------------------

#region Monitor Edit View-----------------------------------------------------------------------------------------------
#todo check apply
#todo check save
#todo check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Monitors View---------------------------------------------------------------------------------------------------
#todo check search
#todo check filtering
#todo check limiting
#todo check sorting
#todo check new
#todo check edit
#todo check delete
#endregion--------------------------------------------------------------------------------------------------------------

#region Room Edit View--------------------------------------------------------------------------------------------------
#todo check apply
#todo check save
#todo check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Rooms View------------------------------------------------------------------------------------------------------
#todo check search
#todo check filtering
#todo check limiting
#todo check sorting
#todo check new
#todo check edit
#todo check delete
#endregion--------------------------------------------------------------------------------------------------------------

#region Roomtype Edit View----------------------------------------------------------------------------------------------
#todo check apply
#todo check save
#todo check cancel
#endregion--------------------------------------------------------------------------------------------------------------

#region Roomtypes View--------------------------------------------------------------------------------------------------
#todo check search
#todo check filtering
#todo check limiting
#todo check sorting
#todo check new
#todo check edit
#todo check delete
#endregion--------------------------------------------------------------------------------------------------------------

#endregion##############################################################################################################

#region Stage 04: Methods, Schedules & Terms############################################################################

#todo Revamp the schedule migration functions for the new circumstances and structure
#todo Conceptualize and implement a schedule synchronization strategy
#todo methods => kapvo mapping
#todo adjust both old organizer, new organizer and untis according to any kapvo resolution
#todo create terms model?

#region Method Edit View------------------------------------------------------------------------------------------------
#todo check apply
#todo check save
#todo check cancel
#endregion

#region Methods View----------------------------------------------------------------------------------------------------
#todo check search
#todo check filtering
#todo check limiting
#todo check sorting
#todo check new
#todo check edit
#todo check delete
#endregion

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

#endregion##############################################################################################################

#region Stage 05: Participants##########################################################################################

#todo Revamp the schedule migration functions for the new circumstances and structure
#todo Conceptualize and implement a schedule synchronization strategy
#todo should participants be moved up to p4 or p5?

#endregion##############################################################################################################

#region Stage 06: Static Planning Resources#############################################################################
#Resources/Tables: Categories, Event Coordinators, Events, Group Publishing, Groups & Subject Events

#todo Implement migration/synchronization of categories, groups, group publishing and events
#todo Conceptualize and implement a planning synchronization strategy
#todo add group association support
## xml import
## edit views
## options fields
## remove events table organizationID column
#todo move the event => organization associations from the events table to the associations table
#todo reintegrate subject => events associations in the subject edit view

#endregion##############################################################################################################

#region Stage 07: Dynamic Planning Resources############################################################################
#Resources/Tables: Blocks, I-Groups, I-Participants, I-Persons, I-Rooms, Instances, Units

#region remove roles table use
#todo create class constants if not existent
#todo create role language constants
#todo keep roles synchronous with those used by subject persons 1 => coordinator, 2 => teacher, 3 => ...
#endregion

#todo Revamp the configuration migration functions for the new circumstances and structure

#endregion##############################################################################################################

#region Stage 08: Merge Views###########################################################################################

#todo add passive synchronization for schedule data from old organizer
#todo merge functionality for categories, events, groups, persons and rooms
#todo Uncomment buttons

#endregion##############################################################################################################

#region Stage 09: Front End Schedule Related Views######################################################################

#todo Move the icalcreator directory back & uncomment it in the install manifest
#todo Revisit access filters for organization options in the frontend context
#todo Link schedules from curricula, programs, subjects & subject views

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