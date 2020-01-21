SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `#__organizer_colors` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `organizationID` INT(11) UNSIGNED NOT NULL,
    `name_de`        VARCHAR(60)      NOT NULL,
    `name_en`        VARCHAR(60)      NOT NULL,
    `color`          VARCHAR(7)       NOT NULL,
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_degrees` (
    `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `abbreviation` VARCHAR(25)      NOT NULL,
    `code`         VARCHAR(10)      NOT NULL,
    `name`         VARCHAR(150)     NOT NULL,
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT INTO `#__organizer_degrees` (`id`, `abbreviation`, `code`, `name`)
VALUES (1, 'B.A.', 'BA', 'Bachelor of Arts'),
       (2, 'B.Eng.', 'BE', 'Bachelor of Engineering'),
       (3, 'B.Sc.', 'BS', 'Bachelor of Science'),
       (4, 'M.A.', 'MA', 'Master of Arts'),
       (5, 'M.B.A.', 'MB', 'Master of Business Administration and Engineering'),
       (6, 'M.Ed.', 'MH', 'Master of Education'),
       (7, 'M.Eng.', 'ME', 'Master of Engineering'),
       (8, 'M.Sc.', 'MS', 'Master of Science');

CREATE TABLE IF NOT EXISTS `#__organizer_organizations` (
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `asset_id`        INT(11)          NOT NULL,
    `abbreviation_de` VARCHAR(25)      NOT NULL,
    `abbreviation_en` VARCHAR(25)      NOT NULL,
    `shortName_de`    VARCHAR(50)      NOT NULL,
    `shortName_en`    VARCHAR(50)      NOT NULL,
    `name_de`         VARCHAR(150)     NOT NULL,
    `name_en`         VARCHAR(150)     NOT NULL,
    `fullName_de`     VARCHAR(200)     NOT NULL,
    `fullName_en`     VARCHAR(200)     NOT NULL,
    `contactType`     TINYINT(1) UNSIGNED DEFAULT 0,
    `contactID`       INT(11)             DEFAULT NULL,
    `contactEmail`    VARCHAR(100)        DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `abbreviation_de` (`abbreviation_de`),
    UNIQUE INDEX `abbreviation_en` (`abbreviation_en`),
    UNIQUE INDEX `shortName_de` (`shortName_de`),
    UNIQUE INDEX `shortName_en` (`shortName_en`),
    UNIQUE INDEX `name_de` (`name_de`),
    UNIQUE INDEX `name_en` (`name_en`),
    UNIQUE INDEX `fullName_de` (`name_de`),
    UNIQUE INDEX `fullName_en` (`name_en`),
    INDEX `contactID` (`contactID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_department_resources` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `organizationID` INT(11) UNSIGNED NOT NULL,
    `categoryID`     INT(11) UNSIGNED DEFAULT NULL,
    `personID`       INT(11)          DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `categoryID` (`categoryID`),
    INDEX `organizationID` (`organizationID`),
    INDEX `personID` (`personID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_fields` (
    `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `untisID` VARCHAR(60)      DEFAULT NULL,
    `colorID` INT(11) UNSIGNED DEFAULT NULL,
    `name_de` VARCHAR(60)      NOT NULL,
    `name_en` VARCHAR(60)      NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `colorID` (`colorID`),
    UNIQUE INDEX `untisID` (`untisID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_frequencies` (
    `id`      INT(1) UNSIGNED NOT NULL,
    `name_de` VARCHAR(45)     NOT NULL,
    `name_en` VARCHAR(45)     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE `name_de` (`name_de`),
    UNIQUE `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_curriculum` (
    `id`        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `programID` INT(11) UNSIGNED DEFAULT NULL,
    `parentID`  INT(11) UNSIGNED DEFAULT NULL,
    `poolID`    INT(11) UNSIGNED DEFAULT NULL,
    `subjectID` INT(11) UNSIGNED DEFAULT NULL,
    `lft`       INT(11) UNSIGNED DEFAULT NULL,
    `rgt`       INT(11) UNSIGNED DEFAULT NULL,
    `level`     INT(11) UNSIGNED DEFAULT NULL,
    `ordering`  INT(11) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `parentID` (`parentID`),
    INDEX `poolID` (`poolID`),
    INDEX `programID` (`programID`),
    INDEX `subjectID` (`subjectID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_persons` (
    `id`       INT(11)             NOT NULL AUTO_INCREMENT,
    `untisID`  VARCHAR(60)                  DEFAULT NULL,
    `surname`  VARCHAR(255)        NOT NULL,
    `forename` VARCHAR(255)        NOT NULL DEFAULT '',
    `username` VARCHAR(150)                 DEFAULT NULL,
    `fieldID`  INT(11) UNSIGNED             DEFAULT NULL,
    `title`    VARCHAR(45)         NOT NULL DEFAULT '',
    `active`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `untisID` (`untisID`),
    INDEX `username` (`username`),
    INDEX `fieldID` (`fieldID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_pools` (
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `asset_id`        INT(11)          NOT NULL DEFAULT 0,
    `organizationID`  INT(11) UNSIGNED          DEFAULT NULL,
    `fieldID`         INT(11) UNSIGNED          DEFAULT NULL,
    `groupID`         INT(11) UNSIGNED          DEFAULT NULL,
    `lsfID`           INT(11) UNSIGNED          DEFAULT NULL,
    `abbreviation_de` VARCHAR(45)               DEFAULT '',
    `abbreviation_en` VARCHAR(45)               DEFAULT '',
    `shortName_de`    VARCHAR(45)               DEFAULT '',
    `shortName_en`    VARCHAR(45)               DEFAULT '',
    `name_de`         VARCHAR(255)              DEFAULT NULL,
    `name_en`         VARCHAR(255)              DEFAULT NULL,
    `description_de`  TEXT,
    `description_en`  TEXT,
    `minCrP`          INT(3) UNSIGNED           DEFAULT 0,
    `maxCrP`          INT(3) UNSIGNED           DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `organizationID` (`organizationID`),
    INDEX `fieldID` (`fieldID`),
    INDEX `groupID` (`groupID`),
    UNIQUE `lsfID` (`lsfID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_prerequisites` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `subjectID`      INT(11) UNSIGNED NOT NULL,
    `prerequisiteID` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `entry` (`prerequisiteID`, `subjectID`),
    INDEX `prerequisiteID` (`prerequisiteID`),
    INDEX `subjectID` (`subjectID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_programs` (
    `id`             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `asset_id`       INT(11)             NOT NULL DEFAULT 0,
    `categoryID`     INT(11) UNSIGNED             DEFAULT NULL,
    `degreeID`       INT(11) UNSIGNED             DEFAULT NULL,
    `organizationID` INT(11) UNSIGNED             DEFAULT NULL,
    `fieldID`        INT(11) UNSIGNED             DEFAULT NULL,
    `frequencyID`    INT(1) UNSIGNED              DEFAULT NULL,
    `code`           VARCHAR(20)                  DEFAULT '',
    `version`        YEAR(4)                      DEFAULT NULL,
    `name_de`        VARCHAR(60)         NOT NULL,
    `name_en`        VARCHAR(60)         NOT NULL,
    `description_de` TEXT,
    `description_en` TEXT,
    `active`         TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `entry` (`code`, `degreeID`, `version`),
    INDEX `categoryID` (`categoryID`),
    INDEX `degreeID` (`degreeID`),
    INDEX `organizationID` (`organizationID`),
    INDEX `fieldID` (`fieldID`),
    INDEX `frequencyID` (`frequencyID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_subject_persons` (
    `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `subjectID` INT(11) UNSIGNED    NOT NULL,
    `personID`  INT(11)             NOT NULL,
    `role`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'The person''s role for the given subject. Roles are not mutually exclusive. Possible values: 1 - coordinates, 2 - teaches.',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `entry` (`personID`, `subjectID`, `role`),
    INDEX `subjectID` (`subjectID`),
    INDEX `personID` (`personID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_subjects` (
    `id`                          INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT,
    `asset_id`                    INT(11)               NOT NULL DEFAULT 0,
    `organizationID`              INT(11) UNSIGNED               DEFAULT NULL,
    `lsfID`                       INT(11) UNSIGNED               DEFAULT NULL,
    `code`                        VARCHAR(45)           NOT NULL DEFAULT '',
    `abbreviation_de`             VARCHAR(45)           NOT NULL DEFAULT '',
    `abbreviation_en`             VARCHAR(45)           NOT NULL DEFAULT '',
    `shortName_de`                VARCHAR(45)           NOT NULL DEFAULT '',
    `shortName_en`                VARCHAR(45)           NOT NULL DEFAULT '',
    `name_de`                     VARCHAR(255)          NOT NULL,
    `name_en`                     VARCHAR(255)          NOT NULL,
    `description_de`              TEXT                  NOT NULL,
    `description_en`              TEXT                  NOT NULL,
    `objective_de`                TEXT                  NOT NULL,
    `objective_en`                TEXT                  NOT NULL,
    `content_de`                  TEXT                  NOT NULL,
    `content_en`                  TEXT                  NOT NULL,
    `prerequisites_de`            TEXT                  NOT NULL,
    `prerequisites_en`            TEXT                  NOT NULL,
    `preliminaryWork_de`          TEXT,
    `preliminaryWork_en`          TEXT,
    `instructionLanguage`         VARCHAR(2)            NOT NULL DEFAULT 'D',
    `literature`                  TEXT                  NOT NULL,
    `creditpoints`                DOUBLE(4, 1) UNSIGNED NOT NULL DEFAULT 0,
    `expenditure`                 INT(4) UNSIGNED       NOT NULL DEFAULT 0,
    `present`                     INT(4) UNSIGNED       NOT NULL DEFAULT 0,
    `independent`                 INT(4) UNSIGNED       NOT NULL DEFAULT 0,
    `proof_de`                    TEXT                  NOT NULL,
    `proof_en`                    TEXT                  NOT NULL,
    `frequencyID`                 INT(1) UNSIGNED                DEFAULT NULL,
    `method_de`                   TEXT,
    `method_en`                   TEXT,
    `fieldID`                     INT(11) UNSIGNED               DEFAULT NULL,
    `sws`                         INT(2) UNSIGNED       NOT NULL DEFAULT 0,
    `aids_de`                     TEXT,
    `aids_en`                     TEXT,
    `evaluation_de`               TEXT,
    `evaluation_en`               TEXT,
    `expertise`                   TINYINT(1) UNSIGNED            DEFAULT NULL,
    `selfCompetence`              TINYINT(1) UNSIGNED            DEFAULT NULL,
    `methodCompetence`            TINYINT(1) UNSIGNED            DEFAULT NULL,
    `socialCompetence`            TINYINT(1) UNSIGNED            DEFAULT NULL,
    `recommendedPrerequisites_de` TEXT,
    `recommendedPrerequisites_en` TEXT,
    `usedFor_de`                  TEXT,
    `usedFor_en`                  TEXT,
    `duration`                    INT(2) UNSIGNED                DEFAULT 1,
    `bonusPoints_de`              TEXT,
    `bonusPoints_en`              TEXT,
    PRIMARY KEY (`id`),
    INDEX `organizationID` (`organizationID`),
    INDEX `fieldID` (`fieldID`),
    INDEX `frequencyID` (`frequencyID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `#__organizer_department_resources`
    ADD CONSTRAINT `department_resources_categoryID_fk` FOREIGN KEY (`categoryID`) REFERENCES `#__organizer_categories` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `department_resources_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `#__organizer_departments` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `department_resources_personID_fk` FOREIGN KEY (`personID`) REFERENCES `#__organizer_persons` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

ALTER TABLE `#__organizer_departments`
    ADD CONSTRAINT `departments_contactID_fk` FOREIGN KEY (`contactID`) REFERENCES `#__users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;

ALTER TABLE `#__organizer_fields`
    ADD CONSTRAINT `fields_colorID_fk` FOREIGN KEY (`colorID`) REFERENCES `#__organizer_colors` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `fields_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `#__organizer_departments` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,;

ALTER TABLE `#__organizer_curriculum`
    ADD CONSTRAINT `curriculum_parentID_fk` FOREIGN KEY (`parentID`) REFERENCES `#__organizer_mappings` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `curriculum_poolID_fk` FOREIGN KEY (`poolID`) REFERENCES `#__organizer_pools` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `curriculum_programID_fk` FOREIGN KEY (`programID`) REFERENCES `#__organizer_programs` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `curriculum_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `#__organizer_subjects` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

ALTER TABLE `#__organizer_persons`
    ADD CONSTRAINT `persons_fieldID_fk` FOREIGN KEY (`fieldID`) REFERENCES `#__organizer_fields` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;

ALTER TABLE `#__organizer_pools`
    ADD CONSTRAINT `pools_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `#__organizer_departments` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `pools_groupID_fk` FOREIGN KEY (`groupID`) REFERENCES `#__organizer_groups` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;

ALTER TABLE `#__organizer_prerequisites`
    ADD CONSTRAINT `prerequisites_prerequisiteID_fk` FOREIGN KEY (`prerequisiteID`) REFERENCES `#__organizer_mappings` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `prerequisites_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `#__organizer_mappings` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

ALTER TABLE `#__organizer_programs`
    ADD CONSTRAINT `programs_degreeID_fk` FOREIGN KEY (`degreeID`) REFERENCES `#__organizer_degrees` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `programs_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `#__organizer_departments` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `programs_frequencyID_fk` FOREIGN KEY (`frequencyID`) REFERENCES `#__organizer_frequencies` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;

ALTER TABLE `#__organizer_subject_persons`
    ADD CONSTRAINT `subject_persons_personID_fk` FOREIGN KEY (`personID`) REFERENCES `#__organizer_persons` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `subject_persons_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `#__organizer_subjects` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

ALTER TABLE `#__organizer_subjects`
    ADD CONSTRAINT `subjects_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `#__organizer_departments` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `subjects_fieldID_fk` FOREIGN KEY (`fieldID`) REFERENCES `#__organizer_fields` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `subjects_frequencyID_fk` FOREIGN KEY (`frequencyID`) REFERENCES `#__organizer_frequencies` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
