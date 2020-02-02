# region categories
INSERT IGNORE INTO `#__organizer_categories` (`id`, `code`, `name_de`, `name_en`)
SELECT `id`, `gpuntisID`, `name`, `name`
FROM `#__thm_organizer_plan_programs`;

UPDATE `#__organizer_programs` AS p
    INNER JOIN `#__thm_organizer_plan_programs` AS pp ON pp.`programID` = p.`id`
SET p.`categoryID` = pp.`id`
WHERE pp.`programID` IS NOT NULL;
# endregion

#region groups
INSERT IGNORE INTO `#__organizer_groups` (`id`, `code`, `categoryID`, `gridID`, `name_de`, `name_en`, `fullName_de`,
                                             `fullName_en`)
SELECT `id`,
       `gpuntisID`,
       `programID`,
       `gridID`,
       `name`,
       `name`,
       `full_name`,
       `full_name`
FROM `#__thm_organizer_plan_pools`;
#endregion

#region group_publishing
INSERT IGNORE INTO `#__organizer_group_publishing` (id, groupID, termID, published)
SELECT *
FROM `#__thm_organizer_plan_pool_publishing`;
#endregion

# region associations => categories, organizations, persons (events, groups, rooms added later in a migration function)
INSERT IGNORE INTO `#__organizer_associations` (id, organizationID, categoryID, personID)
SELECT *
FROM `#__thm_organizer_department_resources`;
# endregion

# region events

INSERT IGNORE INTO `#__organizer_events` (`id`, `code`, `name_de`, `name_en`, `subjectNo`)
SELECT DISTINCT `id`, `gpuntisID`, `name`, `name`, `subjectNo`
FROM `#__thm_organizer_plan_subjects`;
# endregion

# region subject_events
# endregion

# region event_coordinators
# endregion

# region units
# endregion

# region instances
# endregion

# region instance_persons
# endregion

# region instance_groups
# endregion

# region instance_rooms
# endregion

# region participants
# endregion

# region instance_participants
# endregion


-schedules

