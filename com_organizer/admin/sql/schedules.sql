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
    COLLATE = utf8mb4_unicode_ci;

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