# region terms
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_terms` (
    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias`       VARCHAR(255) DEFAULT NULL,
    `code`        VARCHAR(60)      NOT NULL,
    `name_de`     VARCHAR(150) DEFAULT '',
    `name_en`     VARCHAR(150) DEFAULT '',
    `endDate`     DATE             NOT NULL,
    `fullName_de` VARCHAR(200) DEFAULT '',
    `fullName_en` VARCHAR(200) DEFAULT '',
    `startDate`   DATE             NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `alias` (`alias`),
    UNIQUE INDEX `entry` (`code`, `startDate`, `endDate`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_terms` (`id`, `code`, `startDate`, `endDate`)
SELECT DISTINCT `id`, `name`, `startDate`, `endDate`
FROM `v7ocf_thm_organizer_planning_periods`;

UPDATE `v7ocf_organizer_terms`
SET `code`        = '19WS',
    `name_de`     = 'WS 2018/19',
    `name_en`     = 'Fall 2018',
    `fullName_de` = 'Wintersemester 2018/19',
    `fullName_en` = 'Fall Term 2018'
WHERE `code` = 'WS19';

UPDATE `v7ocf_organizer_terms`
SET `code`        = '19SS',
    `name_de`     = 'SS 2019',
    `name_en`     = 'Spring 2019',
    `fullName_de` = 'Sommersemester 2019',
    `fullName_en` = 'Spring Term 2019'
WHERE `code` = 'SS19';

UPDATE `v7ocf_organizer_terms`
SET `code`        = '20WS',
    `name_de`     = 'WS 2019/20',
    `name_en`     = 'Fall 2019',
    `fullName_de` = 'Wintersemester 2019/20',
    `fullName_en` = 'Fall Term 2019'
WHERE `code` = 'WS20';

UPDATE `v7ocf_organizer_terms`
SET `code`        = '20SS',
    `name_de`     = 'SS 2020',
    `name_en`     = 'Spring 2020',
    `fullName_de` = 'Sommersemester 2020',
    `fullName_en` = 'Spring Term 2020'
WHERE `code` = 'SS20';

UPDATE `v7ocf_organizer_terms`
SET `code`        = '21WS',
    `name_de`     = 'WS 2020/21',
    `name_en`     = 'Fall 2020',
    `fullName_de` = 'Wintersemester 2020/21',
    `fullName_en` = 'Fall Term 2020'
WHERE `code` = 'WS21';

UPDATE `v7ocf_organizer_terms`
SET `code`        = '21SS',
    `name_de`     = 'SS 2021',
    `name_en`     = 'Spring 2021',
    `fullName_de` = 'Sommersemester 2021',
    `fullName_en` = 'Spring Term 2021'
WHERE `code` = 'SS21';
# endregion

# region schedules
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_schedules` (
    `id`             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `organizationID` INT(11) UNSIGNED    NOT NULL,
    `termID`         INT(11) UNSIGNED    NOT NULL,
    `active`         TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `creationDate`   DATE                         DEFAULT NULL,
    `creationTime`   TIME                         DEFAULT NULL,
    `userID`         INT(11)                      DEFAULT NULL,
    `schedule`       MEDIUMTEXT,
    PRIMARY KEY (`id`),
    INDEX `organizationID` (`organizationID`),
    INDEX `termID` (`termID`),
    INDEX `userID` (`userID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_schedules` (id, organizationID, termID, userID, creationDate, creationTime,
                                                schedule, active)
SELECT `id`,
       `departmentID`,
       `planningPeriodID`,
       `userID`,
       `creationDate`,
       `creationTime`,
       `schedule`,
       `active`
FROM `v7ocf_thm_organizer_schedules`;

ALTER TABLE `v7ocf_organizer_schedules`
    ADD CONSTRAINT `schedule_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `v7ocf_organizer_organizations` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `schedule_termID_fk` FOREIGN KEY (`termID`) REFERENCES `v7ocf_organizer_terms` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `schedule_userID_fk` FOREIGN KEY (`userID`) REFERENCES `v7ocf_users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion