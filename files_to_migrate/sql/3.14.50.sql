

UPDATE `#__organizer_events` AS e
SET `courseContact_de` = '<ul><li><a href="mailto:hans.c.arlt@mnd.thm.de" target="_top"><span class="icon-mail"></span>Hans Christian Arlt</a></li></ul>',
    `courseContact_en` = '<ul><li><a href="mailto:hans.c.arlt@mnd.thm.de" target="_top"><span class="icon-mail"></span>Hans Christian Arlt</a></li></ul>'
WHERE `untisID` = 'BKPh' AND `departmentID` = 50;

UPDATE `#__organizer_events` AS e
SET `campusID` = (SELECT DISTINCT `campusID`
                  FROM `#__organizer_subjects` AS s
                           INNER JOIN `#__organizer_subject_mappings` AS sm ON sm.`subjectID` = s.`id`
                  WHERE sm.`plan_subjectID` = e.`id`);

INSERT INTO `#__organizer_courses`(`campusID`, `termID`, `maxParticipants`, `unitID`)
SELECT u.`campusID`, u.`termID`, u.`max_participants`, u.`id`
FROM `#__organizer_units` AS u
WHERE `max_participants` IS NOT NULL;

UPDATE `#__organizer_units` AS u
    INNER JOIN `#__organizer_courses` AS c ON c.`unitID` = u.`id`
SET u.`courseID` = c.`id`;

UPDATE `#__organizer_courses` AS c
    INNER JOIN `#__organizer_units` AS u ON u.`courseID` = c.`id`
SET `deadline` = 14, `fee` = 0
WHERE `maxParticipants` IS NOT NULL AND `departmentID` = 22;

UPDATE `#__organizer_courses` AS c
    INNER JOIN `#__organizer_units` AS u ON u.`courseID` = c.`id`
SET `deadline` = 5, `fee` = 50
WHERE `maxParticipants` IS NOT NULL AND `departmentID` != 22;

UPDATE `#__organizer_courses`
SET `registrationType` = 1
WHERE `maxParticipants` IS NOT NULL;

UPDATE `#__organizer_units` AS u
    INNER JOIN `#__organizer_lesson_subjects` AS ls ON ls.`lessonID` = u.`id`
    INNER JOIN `#__organizer_lesson_pools` AS lp ON lp.`subjectID` = ls.`id`
    INNER JOIN `#__organizer_groups` AS g ON g.`id` = lp.`poolID`
SET u.`gridID` = g.`gridID`;

INSERT INTO `#__organizer_instances`(`eventID`, `blockID`, `unitID`, `methodID`, `delta`, `modified`)
SELECT ls.`subjectID` AS eventID,
       b.`id`         AS blockID,
       u.`id`         AS unitID,
       u.`methodID`,
       c.`delta`,
       c.`modified`
FROM `#__organizer_lesson_subjects` AS ls
         INNER JOIN `#__organizer_units` AS u ON u.`id` = ls.`lessonID`
         INNER JOIN `#__organizer_calendar` AS c ON c.`lessonID` = ls.`lessonID`
         INNER JOIN `#__organizer_blocks` AS b
                    ON b.`date` = c.`schedule_date` AND b.`startTime` = c.`startTime` AND b.`endTime` = c.`endTime`
GROUP BY eventID, blockID, unitID;

UPDATE `#__organizer_instances`
SET `eventID` = (SELECT `id`
                 FROM `#__organizer_events`
                 WHERE `departmentID` = 6 AND `untisID` = 'BKC')
WHERE `eventID` = (SELECT `id`
                   FROM `#__organizer_events`
                   WHERE `departmentID` = 51 AND `untisID` = 'BKC');

UPDATE `#__organizer_instances`
SET `eventID` = (SELECT `id`
                 FROM `#__organizer_events`
                 WHERE `departmentID` = 6 AND `untisID` = 'BKM')
WHERE `eventID` = (SELECT `id`
                   FROM `#__organizer_events`
                   WHERE `departmentID` = 51 AND `untisID` = 'BKM');

UPDATE `#__organizer_instances`
SET `eventID` = (SELECT `id`
                 FROM `#__organizer_events`
                 WHERE `departmentID` = 6 AND `untisID` = 'BKPh')
WHERE `eventID` = (SELECT `id`
                   FROM `#__organizer_events`
                   WHERE `departmentID` = 51 AND `untisID` = 'BKPh');

UPDATE `#__organizer_instances`
SET `eventID` = (SELECT `id`
                 FROM `#__organizer_events`
                 WHERE `departmentID` = 6 AND `untisID` = 'BKPr')
WHERE `eventID` = (SELECT `id`
                   FROM `#__organizer_events`
                   WHERE `departmentID` = 51 AND `untisID` = 'BKPr');

DELETE
FROM `#__organizer_events`
WHERE `id` NOT IN (SELECT DISTINCT `eventID`
                   FROM `#__organizer_instances`);

INSERT INTO `#__organizer_instance_persons`(`instanceID`, `personID`, `delta`, `modified`)
SELECT DISTINCT i.`id`, lt.`teacherID`, lt.`delta`, lt.`modified`
FROM `#__organizer_lesson_teachers` AS lt
         INNER JOIN `#__organizer_lesson_subjects` AS ls ON ls.`id` = lt.`subjectID`
         INNER JOIN `#__organizer_instances` AS i ON i.`eventID` = ls.`subjectID` AND i.`unitID` = ls.`lessonID`
GROUP BY i.`id`, lt.`teacherID`;

INSERT INTO `#__organizer_instance_groups`(`assocID`, `groupID`, `delta`, `modified`)
SELECT DISTINCT ip.`id`, lp.`poolID`, lp.`delta`, lp.`modified`
FROM `#__organizer_lesson_pools` AS lp
         INNER JOIN `#__organizer_lesson_subjects` AS ls ON ls.`id` = lp.`subjectID`
         INNER JOIN `#__organizer_instances` AS i ON i.`eventID` = ls.`subjectID` AND i.`unitID` = ls.`lessonID`
         INNER JOIN `#__organizer_instance_persons` AS ip ON ip.`instanceID` = i.`id`
GROUP BY ip.`id`, lp.`poolID`;
#endregion

DROP TABLE `#__organizer_lesson_pools`;

DROP TABLE `#__organizer_lesson_teachers`;