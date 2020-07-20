INSERT IGNORE INTO `v7ocf_organizer_blocks` (`date`, `dow`, `startTime`, `endTime`)
SELECT DISTINCT `schedule_date`, WEEKDAY(`schedule_date`) + 1, `startTime`, `endTime`
FROM `v7ocf_thm_organizer_calendar`;

# method id from units
# region instances
INSERT IGNORE INTO `v7ocf_organizer_instances`(`eventID`, `blockID`, `unitID`, `methodID`, `delta`, `modified`)
SELECT ls.`subjectID` AS eventID,
       b.`id`         AS blockID,
       l.`id`         AS unitID,
       l.`methodID`,
       c.`delta`,
       c.`modified`
FROM `v7ocf_thm_organizer_lesson_subjects` AS ls
         INNER JOIN `v7ocf_thm_organizer_lessons` AS l ON l.`id` = ls.`lessonID`
         INNER JOIN `v7ocf_thm_organizer_calendar` AS c ON c.`lessonID` = ls.`lessonID`
         INNER JOIN `v7ocf_organizer_blocks` AS b
                    ON b.`date` = c.`schedule_date` AND b.`startTime` = c.`startTime` AND b.`endTime` = c.`endTime`
GROUP BY eventID, blockID, unitID;

#remove methodid from units
#drop fk
#drop index
#drop column
# endregion

INSERT IGNORE INTO `v7ocf_organizer_instance_persons`(`instanceID`, `personID`, `delta`, `modified`)
SELECT DISTINCT i.`id`, lt.`teacherID`, lt.`delta`, lt.`modified`
FROM `v7ocf_thm_organizer_lesson_teachers` AS lt
         INNER JOIN `v7ocf_thm_organizer_lesson_subjects` AS ls ON ls.`id` = lt.`subjectID`
         INNER JOIN `v7ocf_organizer_instances` AS i ON i.`eventID` = ls.`subjectID` AND i.`unitID` = ls.`lessonID`
GROUP BY i.`id`, lt.`teacherID`;

INSERT IGNORE INTO `v7ocf_organizer_instance_groups`(`assocID`, `groupID`, `delta`, `modified`)
SELECT DISTINCT ip.`id`, lp.`poolID`, lp.`delta`, lp.`modified`
FROM `v7ocf_thm_organizer_lesson_pools` AS lp
         INNER JOIN `v7ocf_thm_organizer_lesson_subjects` AS ls ON ls.`id` = lp.`subjectID`
         INNER JOIN `v7ocf_organizer_instances` AS i ON i.`eventID` = ls.`subjectID` AND i.`unitID` = ls.`lessonID`
         INNER JOIN `v7ocf_organizer_instance_persons` AS ip ON ip.`instanceID` = i.`id`
GROUP BY ip.`id`, lp.`poolID`;

#instance rooms data has to be migrated in through software
#courses data has to be migrated in through software
#participants data has to be migrated in through software
#course participants data has to be migrated in through software
#instance participants data has to be migrated in through software