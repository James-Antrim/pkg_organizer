# region blocks
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_blocks` (
    `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `date`      DATE                NOT NULL,
    `dow`       TINYINT(1) UNSIGNED NOT NULL,
    `endTime`   TIME                NOT NULL,
    `startTime` TIME                NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `date` (`date`),
    INDEX `dow` (`dow`),
    INDEX `endTime` (`endTime`),
    INDEX `startTime` (`startTime`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_blocks` (`date`, `dow`, `startTime`, `endTime`)
SELECT DISTINCT `schedule_date`, WEEKDAY(`schedule_date`) + 1, `startTime`, `endTime`
FROM `v7ocf_thm_organizer_calendar`;
# endregion

# region units
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_units` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`           INT(11) UNSIGNED NOT NULL,
    `organizationID` INT(11) UNSIGNED NOT NULL,
    `termID`         INT(11) UNSIGNED NOT NULL,
    `comment`        VARCHAR(255)              DEFAULT '',
    `courseID`       INT(11) UNSIGNED          DEFAULT NULL,
    `delta`          VARCHAR(10)      NOT NULL DEFAULT '',
    `endDate`        DATE                      DEFAULT NULL,
    `gridID`         INT(11) UNSIGNED          DEFAULT NULL,
    `modified`       TIMESTAMP                 DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `runID`          INT(11) UNSIGNED          DEFAULT NULL,
    `startDate`      DATE                      DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `entry` (`code`, `organizationID`, `termID`),
    INDEX `code` (`code`),
    INDEX `courseID` (`courseID`),
    INDEX `gridID` (`gridID`),
    INDEX `organizationID` (`organizationID`),
    INDEX `runID` (`runID`),
    INDEX `termID` (`termID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_units` (`id`, `organizationID`, `termID`, `code`, `comment`, `delta`, `modified`)
SELECT `id`, `departmentID`, `planningPeriodID`, `gpuntisID`, `comment`, `delta`, `modified`
FROM `v7ocf_thm_organizer_lessons`;

UPDATE `v7ocf_organizer_units` AS u
    INNER JOIN `v7ocf_thm_organizer_lesson_subjects` AS ls ON ls.`lessonID` = u.`id`
    INNER JOIN `v7ocf_thm_organizer_lesson_pools` AS lp ON lp.`subjectID` = ls.`id`
    INNER JOIN `v7ocf_organizer_groups` AS g ON g.`id` = lp.`poolID`
SET u.`gridID` = g.`gridID`
WHERE u.`id` IS NOT NULL;

ALTER TABLE `v7ocf_organizer_units`
    ADD CONSTRAINT `unit_gridID_fk` FOREIGN KEY (`gridID`) REFERENCES `v7ocf_organizer_grids` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `unit_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `v7ocf_organizer_organizations` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `unit_termID_fk` FOREIGN KEY (`termID`) REFERENCES `v7ocf_organizer_terms` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

# region instances
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_instances` (
    `id`       INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `blockID`  INT(11) UNSIGNED NOT NULL,
    `eventID`  INT(11) UNSIGNED NOT NULL,
    `methodID` INT(11) UNSIGNED          DEFAULT NULL,
    `unitID`   INT(11) UNSIGNED NOT NULL,
    `delta`    VARCHAR(10)      NOT NULL DEFAULT '',
    `modified` TIMESTAMP                 DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `entry` UNIQUE (`eventID`, `blockID`, `unitID`),
    INDEX `blockID` (`blockID`),
    INDEX `eventID` (`eventID`),
    INDEX `methodID` (`methodID`),
    INDEX `unitID` (`unitID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

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

# re-associate prep course event associations from gi to mni
UPDATE `v7ocf_organizer_instances`
SET `eventID` = (SELECT `id`
                 FROM `v7ocf_organizer_events`
                 WHERE `organizationID` = 14
                   AND `code` = 'BKC')
WHERE `eventID` = (SELECT `id`
                   FROM `v7ocf_organizer_events`
                   WHERE `organizationID` = 12
                     AND `code` = 'BKC');

UPDATE `v7ocf_organizer_instances`
SET `eventID` = (SELECT `id`
                 FROM `v7ocf_organizer_events`
                 WHERE `organizationID` = 14
                   AND `code` = 'BKM')
WHERE `eventID` = (SELECT `id`
                   FROM `v7ocf_organizer_events`
                   WHERE `organizationID` = 12
                     AND `code` = 'BKM');

UPDATE `v7ocf_organizer_instances`
SET `eventID` = (SELECT `id`
                 FROM `v7ocf_organizer_events`
                 WHERE `organizationID` = 14
                   AND `code` = 'BKPh')
WHERE `eventID` = (SELECT `id`
                   FROM `v7ocf_organizer_events`
                   WHERE `organizationID` = 12
                     AND `code` = 'BKPh');

UPDATE `v7ocf_organizer_instances`
SET `eventID` = (SELECT `id`
                 FROM `v7ocf_organizer_events`
                 WHERE `organizationID` = 14
                   AND `code` = 'BKPr')
WHERE `eventID` = (SELECT `id`
                   FROM `v7ocf_organizer_events`
                   WHERE `organizationID` = 12
                     AND `code` = 'BKPr');

# remove events which are not associated with instances (set to inactive?)
DELETE
FROM `v7ocf_organizer_events`
WHERE `id` NOT IN (SELECT DISTINCT `eventID`
                   FROM `v7ocf_organizer_instances`);

# update units with their respective a start and end dates
UPDATE `v7ocf_organizer_units` AS u
SET `startDate` = (SELECT MIN(date)
                   FROM `v7ocf_organizer_blocks` AS b
                            INNER JOIN `v7ocf_organizer_instances` AS i ON i.`blockID` = b.`id`
                   WHERE i.`unitID` = u.`id`),
    `endDate`   = (SELECT MAX(date)
                   FROM `v7ocf_organizer_blocks` AS b
                            INNER JOIN `v7ocf_organizer_instances` AS i ON i.`blockID` = b.`id`
                   WHERE i.`unitID` = u.`id`);

ALTER TABLE `v7ocf_organizer_instances`
    ADD CONSTRAINT `instance_blockID_fk` FOREIGN KEY (`blockID`) REFERENCES `v7ocf_organizer_blocks` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_eventID_fk` FOREIGN KEY (`eventID`) REFERENCES `v7ocf_organizer_events` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_methodID_fk` FOREIGN KEY (`methodID`) REFERENCES `v7ocf_organizer_methods` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_unitID_fk` FOREIGN KEY (`unitID`) REFERENCES `v7ocf_organizer_units` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

# region roles
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_roles` (
    `id`              TINYINT(2) UNSIGNED NOT NULL AUTO_INCREMENT,
    `abbreviation_de` VARCHAR(25)         NOT NULL,
    `abbreviation_en` VARCHAR(25)         NOT NULL,
    `name_de`         VARCHAR(150)        NOT NULL,
    `name_en`         VARCHAR(150)        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `name_de` (`name_de`),
    UNIQUE INDEX `name_en` (`name_en`),
    UNIQUE INDEX `abbreviation_de` (`abbreviation_de`),
    UNIQUE INDEX `abbreviation_en` (`abbreviation_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_roles`
VALUES (1, 'DOZ', 'TCH', 'Dozent', 'Teacher'),
       (2, 'TUT', 'TUT', 'Tutor', 'Tutor'),
       (3, 'AFS', 'SPR', 'Aufsicht', 'Supervisor'),
       (4, 'REF', 'SPK', 'Referent', 'Speaker');
# endregion

# region instance persons
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_instance_persons` (
    `id`         INT(20) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `instanceID` INT(20) UNSIGNED    NOT NULL,
    `personID`   INT(11) UNSIGNED    NOT NULL,
    `roleID`     TINYINT(2) UNSIGNED NOT NULL DEFAULT 1,
    `delta`      VARCHAR(10)         NOT NULL DEFAULT '',
    `modified`   TIMESTAMP                    DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `entry` UNIQUE (`instanceID`, `personID`),
    INDEX `instanceID` (`instanceID`),
    INDEX `personID` (`personID`),
    INDEX `roleID` (`roleID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_instance_persons`(`instanceID`, `personID`, `delta`, `modified`)
SELECT DISTINCT i.`id`, lt.`teacherID`, lt.`delta`, lt.`modified`
FROM `v7ocf_thm_organizer_lesson_teachers` AS lt
         INNER JOIN `v7ocf_thm_organizer_lesson_subjects` AS ls ON ls.`id` = lt.`subjectID`
         INNER JOIN `v7ocf_organizer_instances` AS i ON i.`eventID` = ls.`subjectID` AND i.`unitID` = ls.`lessonID`
GROUP BY i.`id`, lt.`teacherID`;

ALTER TABLE `v7ocf_organizer_instance_persons`
    ADD CONSTRAINT `instance_person_instanceID_fk` FOREIGN KEY (`instanceID`) REFERENCES `v7ocf_organizer_instances` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_person_personID_fk` FOREIGN KEY (`personID`) REFERENCES `v7ocf_organizer_persons` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_person_roleID_fk` FOREIGN KEY (`roleID`) REFERENCES `v7ocf_organizer_roles` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

# region instance groups
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_instance_groups` (
    `id`       INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `assocID`  INT(20) UNSIGNED NOT NULL COMMENT 'The instance to person association id.',
    `groupID`  INT(11) UNSIGNED NOT NULL,
    `delta`    VARCHAR(10)      NOT NULL DEFAULT '',
    `modified` TIMESTAMP                 DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `assocID` (`assocID`),
    INDEX `groupID` (`groupID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_instance_groups`(`assocID`, `groupID`, `delta`, `modified`)
SELECT DISTINCT ip.`id`, lp.`poolID`, lp.`delta`, lp.`modified`
FROM `v7ocf_thm_organizer_lesson_pools` AS lp
         INNER JOIN `v7ocf_thm_organizer_lesson_subjects` AS ls ON ls.`id` = lp.`subjectID`
         INNER JOIN `v7ocf_organizer_instances` AS i ON i.`eventID` = ls.`subjectID` AND i.`unitID` = ls.`lessonID`
         INNER JOIN `v7ocf_organizer_instance_persons` AS ip ON ip.`instanceID` = i.`id`
GROUP BY ip.`id`, lp.`poolID`;

ALTER TABLE `v7ocf_organizer_instance_groups`
    ADD CONSTRAINT `instance_group_assocID_fk` FOREIGN KEY (`assocID`) REFERENCES `v7ocf_organizer_instance_persons` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_group_groupID_fk` FOREIGN KEY (`groupID`) REFERENCES `v7ocf_organizer_groups` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

# region instance rooms
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_instance_rooms` (
    `id`       INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `assocID`  INT(20) UNSIGNED NOT NULL COMMENT 'The instance to person association id.',
    `roomID`   INT(11) UNSIGNED NOT NULL,
    `delta`    VARCHAR(10)      NOT NULL DEFAULT '',
    `modified` TIMESTAMP                 DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `assocID` (`assocID`),
    INDEX `roomID` (`roomID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

# Data has to be migrated in through software

ALTER TABLE `v7ocf_organizer_instance_rooms`
    ADD CONSTRAINT `instance_room_assocID_fk` FOREIGN KEY (`assocID`) REFERENCES `v7ocf_organizer_instance_persons` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_room_roomID_fk` FOREIGN KEY (`roomID`) REFERENCES `v7ocf_organizer_rooms` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion


