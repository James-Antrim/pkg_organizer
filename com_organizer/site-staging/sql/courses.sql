# region courses
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_courses` (
    `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias`            VARCHAR(255)              DEFAULT NULL,
    `campusID`         INT(11) UNSIGNED          DEFAULT NULL,
    `name_de`          VARCHAR(150)              DEFAULT NULL,
    `name_en`          VARCHAR(150)              DEFAULT NULL,
    `termID`           INT(11) UNSIGNED NOT NULL,
    `deadline`         INT(2) UNSIGNED           DEFAULT 0,
    `description_de`   TEXT,
    `description_en`   TEXT,
    `fee`              INT(3) UNSIGNED           DEFAULT 0,
    `groups`           VARCHAR(100)     NOT NULL DEFAULT '',
    `maxParticipants`  INT(4) UNSIGNED           DEFAULT 1000,
    `registrationType` INT(1) UNSIGNED           DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `alias` (`alias`),
    INDEX `campusID` (`campusID`),
    INDEX `termID` (`termID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `v7ocf_organizer_courses`
    ADD CONSTRAINT `course_campusID_fk` FOREIGN KEY (`campusID`) REFERENCES `v7ocf_organizer_campuses` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `course_termID_fk` FOREIGN KEY (`termID`) REFERENCES `v7ocf_organizer_terms` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

ALTER TABLE `v7ocf_organizer_units`
    ADD CONSTRAINT `unit_courseID_fk` FOREIGN KEY (`courseID`) REFERENCES `v7ocf_organizer_courses` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion

# region participants
# programID defaults to null so the entry does not get deleted with the program
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_participants` (
    `id`        INT(11)             NOT NULL,
    `forename`  VARCHAR(255)        NOT NULL DEFAULT '',
    `surname`   VARCHAR(255)        NOT NULL DEFAULT '',
    `address`   VARCHAR(255)        NOT NULL DEFAULT '',
    `city`      VARCHAR(60)         NOT NULL DEFAULT '',
    `notify`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `programID` INT(11) UNSIGNED             DEFAULT NULL,
    `zipCode`   VARCHAR(60)         NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    INDEX `programID` (`programID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

# Data has to be migrated in through software

ALTER TABLE `v7ocf_organizer_participants`
    ADD CONSTRAINT `participant_programID_fk` FOREIGN KEY (`programID`) REFERENCES `v7ocf_organizer_programs` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `participant_userID_fk` FOREIGN KEY (`id`) REFERENCES `v7ocf_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

# region course participants
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_course_participants` (
    `id`              INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `courseID`        INT(11) UNSIGNED NOT NULL,
    `participantID`   INT(11)          NOT NULL,
    `attended`        TINYINT(1) UNSIGNED DEFAULT 0,
    `paid`            TINYINT(1) UNSIGNED DEFAULT 0,
    `participantDate` DATETIME            DEFAULT NULL,
    `status`          TINYINT(1) UNSIGNED DEFAULT 0,
    `statusDate`      DATETIME            DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `courseID` (`courseID`),
    INDEX `participantID` (`participantID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

# Data has to be migrated in through software

ALTER TABLE `v7ocf_organizer_course_participants`
    ADD CONSTRAINT `course_participant_courseID_fk` FOREIGN KEY (`courseID`) REFERENCES `v7ocf_organizer_courses` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `course_participant_participantID_fk` FOREIGN KEY (`participantID`) REFERENCES `v7ocf_organizer_participants` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

# region instance participants
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_instance_participants` (
    `id`            INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `instanceID`    INT(20) UNSIGNED NOT NULL,
    `participantID` INT(11)          NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `instanceID` (`instanceID`),
    INDEX `participantID` (`participantID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

# Data has to be migrated in through software

ALTER TABLE `v7ocf_organizer_instance_participants`
    ADD CONSTRAINT `instance_participant_instanceID_fk` FOREIGN KEY (`instanceID`) REFERENCES `v7ocf_organizer_instances` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_participant_participantID_fk` FOREIGN KEY (`participantID`) REFERENCES `v7ocf_organizer_participants` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion


