# region programs
# degreeID, frequencyID default to null so the entry does not get deleted with the organization
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_programs` (
    `id`             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`          VARCHAR(255)                 DEFAULT NULL,
    `accredited`     YEAR(4)             NOT NULL,
    `code`           VARCHAR(60)         NOT NULL,
    `degreeID`       INT(11) UNSIGNED             DEFAULT NULL,
    `name_de`        VARCHAR(150)        NOT NULL,
    `name_en`        VARCHAR(150)        NOT NULL,
    `active`         TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `categoryID`     INT(11) UNSIGNED             DEFAULT NULL,
    `description_de` TEXT,
    `description_en` TEXT,
    `frequencyID`    INT(1) UNSIGNED              DEFAULT NULL,
    `organizationID` INT(11) UNSIGNED             DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `alias` (`alias`),
    UNIQUE INDEX `entry` (`code`, `degreeID`, `accredited`),
    INDEX `categoryID` (`categoryID`),
    INDEX `degreeID` (`degreeID`),
    INDEX `frequencyID` (`frequencyID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_programs` (`id`, `organizationID`, `code`, `degreeID`, `accredited`, `frequencyID`, `name_de`,
                                               `name_en`, `description_de`, `description_en`)
SELECT DISTINCT `id`,
                `departmentID`,
                `code`,
                `degreeID`,
                `version`,
                `frequencyID`,
                `name_de`,
                `name_en`,
                `description_de`,
                `description_en`
FROM `v7ocf_thm_organizer_programs`;

INSERT IGNORE INTO `v7ocf_organizer_associations` (`programID`, `organizationID`)
SELECT DISTINCT `id`,
                `departmentID`
FROM `v7ocf_thm_organizer_programs`;

ALTER TABLE `v7ocf_organizer_associations`
    ADD CONSTRAINT `association_programID_fk` FOREIGN KEY (`programID`) REFERENCES `v7ocf_organizer_programs` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

ALTER TABLE `v7ocf_organizer_programs`
    ADD CONSTRAINT `program_degreeID_fk` FOREIGN KEY (`degreeID`) REFERENCES `v7ocf_organizer_degrees` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `program_frequencyID_fk` FOREIGN KEY (`frequencyID`) REFERENCES `v7ocf_organizer_frequencies` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion

# region pools
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_pools` (
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias`           VARCHAR(255)     DEFAULT NULL,
    `shortName_de`    VARCHAR(50)      DEFAULT '',
    `shortName_en`    VARCHAR(50)      DEFAULT '',
    `lsfID`           INT(11) UNSIGNED DEFAULT NULL,
    `abbreviation_de` VARCHAR(25)      DEFAULT '',
    `abbreviation_en` VARCHAR(25)      DEFAULT '',
    `description_de`  TEXT,
    `description_en`  TEXT,
    `fieldID`         INT(11) UNSIGNED DEFAULT NULL,
    `fullName_de`     VARCHAR(200)     DEFAULT NULL,
    `fullName_en`     VARCHAR(200)     DEFAULT NULL,
    `groupID`         INT(11) UNSIGNED DEFAULT NULL,
    `minCrP`          INT(3) UNSIGNED  DEFAULT 0,
    `maxCrP`          INT(3) UNSIGNED  DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `alias` (`alias`),
    INDEX `fieldID` (`fieldID`),
    INDEX `groupID` (`groupID`),
    UNIQUE `lsfID` (`lsfID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_pools` (`id`, `fieldID`, `lsfID`, `abbreviation_de`,
                                            `abbreviation_en`, `shortName_de`, `shortName_en`, `fullName_de`,
                                            `fullName_en`, `description_de`, `description_en`, `minCrP`, `maxCrP`)
SELECT DISTINCT `id`,
                `fieldID`,
                `lsfID`,
                `abbreviation_de`,
                `abbreviation_en`,
                `short_name_de`,
                `short_name_en`,
                `name_de`,
                `name_en`,
                `description_de`,
                `description_en`,
                `minCrP`,
                `maxCrP`
FROM `v7ocf_thm_organizer_pools`;

INSERT IGNORE INTO `v7ocf_organizer_associations` (`poolID`, `organizationID`)
SELECT DISTINCT `id`,
                `departmentID`
FROM `v7ocf_thm_organizer_pools`;

ALTER TABLE `v7ocf_organizer_associations`
    ADD CONSTRAINT `association_poolID_fk` FOREIGN KEY (`poolID`) REFERENCES `v7ocf_organizer_pools` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

ALTER TABLE `v7ocf_organizer_pools`
    ADD CONSTRAINT `pool_fieldID_fk` FOREIGN KEY (`fieldID`) REFERENCES `v7ocf_organizer_fields` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion

# region subjects
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_subjects` (
    `id`                          INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT,
    `alias`                       VARCHAR(255)                   DEFAULT NULL,
    `code`                        VARCHAR(60)                    DEFAULT NULL COLLATE utf8mb4_bin,
    `fullName_de`                 VARCHAR(200)          NOT NULL,
    `fullName_en`                 VARCHAR(200)          NOT NULL,
    `organizationID`              INT(11) UNSIGNED               DEFAULT NULL,
    `lsfID`                       INT(11) UNSIGNED      NOT NULL,
    `abbreviation_de`             VARCHAR(25)           NOT NULL DEFAULT '',
    `abbreviation_en`             VARCHAR(25)           NOT NULL DEFAULT '',
    `aids_de`                     TEXT,
    `aids_en`                     TEXT,
    `bonusPoints_de`              TEXT,
    `bonusPoints_en`              TEXT,
    `content_de`                  TEXT,
    `content_en`                  TEXT,
    `creditpoints`                DOUBLE(4, 1) UNSIGNED NOT NULL DEFAULT 0,
    `description_de`              TEXT,
    `description_en`              TEXT,
    `duration`                    INT(2) UNSIGNED                DEFAULT 1,
    `evaluation_de`               TEXT,
    `evaluation_en`               TEXT,
    `expenditure`                 INT(4) UNSIGNED       NOT NULL DEFAULT 0,
    `expertise`                   TINYINT(1) UNSIGNED            DEFAULT NULL,
    `fieldID`                     INT(11) UNSIGNED               DEFAULT NULL,
    `frequencyID`                 INT(1) UNSIGNED                DEFAULT NULL,
    `independent`                 INT(4) UNSIGNED       NOT NULL DEFAULT 0,
    `instructionLanguage`         VARCHAR(2)            NOT NULL DEFAULT 'D',
    `literature`                  TEXT,
    `method_de`                   TEXT,
    `method_en`                   TEXT,
    `methodCompetence`            TINYINT(1) UNSIGNED            DEFAULT NULL,
    `objective_de`                TEXT,
    `objective_en`                TEXT,
    `preliminaryWork_de`          TEXT,
    `preliminaryWork_en`          TEXT,
    `prerequisites_de`            TEXT,
    `prerequisites_en`            TEXT,
    `present`                     INT(4) UNSIGNED       NOT NULL DEFAULT 0,
    `proof_de`                    TEXT,
    `proof_en`                    TEXT,
    `recommendedPrerequisites_de` TEXT,
    `recommendedPrerequisites_en` TEXT,
    `selfCompetence`              TINYINT(1) UNSIGNED            DEFAULT NULL,
    `shortName_de`                VARCHAR(50)           NOT NULL DEFAULT '',
    `shortName_en`                VARCHAR(50)           NOT NULL DEFAULT '',
    `socialCompetence`            TINYINT(1) UNSIGNED            DEFAULT NULL,
    `sws`                         INT(2) UNSIGNED       NOT NULL DEFAULT 0,
    `usedFor_de`                  TEXT,
    `usedFor_en`                  TEXT,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `alias` (`alias`),
    INDEX `fieldID` (`fieldID`),
    INDEX `frequencyID` (`frequencyID`),
    UNIQUE INDEX `lsfID` (`lsfID`),
    INDEX `organizationID` (`organizationID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_subjects` (`id`, `organizationID`, `lsfID`, `code`, `abbreviation_de`,
                                               `abbreviation_en`, `shortName_de`, `shortName_en`, `fullName_de`,
                                               `fullName_en`, `description_de`, `description_en`, `objective_de`,
                                               `objective_en`, `content_de`, `content_en`, `prerequisites_de`,
                                               `prerequisites_en`, `preliminaryWork_de`, `preliminaryWork_en`,
                                               `instructionLanguage`, `literature`, `creditpoints`, `expenditure`,
                                               `present`, `independent`, `proof_de`, `proof_en`, `frequencyID`,
                                               `method_de`, `method_en`, `fieldID`, `sws`, `aids_de`, `aids_en`,
                                               `evaluation_de`, `evaluation_en`, `expertise`, `selfCompetence`,
                                               `methodCompetence`, `socialCompetence`, `recommendedPrerequisites_de`,
                                               `recommendedPrerequisites_en`, `usedFor_de`, `usedFor_en`, `duration`,
                                               `bonusPoints_de`, `bonusPoints_en`)
SELECT DISTINCT `id`,
                `departmentID`,
                `lsfID`,
                `externalID`,
                `abbreviation_de`,
                `abbreviation_en`,
                `short_name_de`,#
                `short_name_en`,
                `name_de`,#
                `name_en`,
                `description_de`,
                `description_en`,
                `objective_de`,
                `objective_en`,
                `content_de`,
                `content_en`,
                `prerequisites_de`,
                `prerequisites_en`,
                `preliminary_work_de`,
                `preliminary_work_en`,
                `instructionLanguage`,
                `literature`,
                `creditpoints`,
                `expenditure`,
                `present`,
                `independent`,
                `proof_de`,
                `proof_en`,
                `frequencyID`,
                `method_de`,
                `method_en`,
                `fieldID`,
                `sws`,
                `aids_de`,
                `aids_en`,
                `evaluation_de`,
                `evaluation_en`,
                `expertise`,
                `self_competence`,
                `method_competence`,
                `social_competence`,
                `recommended_prerequisites_de`,
                `recommended_prerequisites_en`,
                `used_for_de`,
                `used_for_en`,
                `duration`,
                `bonus_points_de`,
                `bonus_points_en`
FROM `v7ocf_thm_organizer_subjects`;

# add associations table insert statement
INSERT x;

ALTER TABLE `v7ocf_organizer_subjects`
    ADD CONSTRAINT `subject_fieldID_fk` FOREIGN KEY (`fieldID`) REFERENCES `v7ocf_organizer_fields` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `subject_frequencyID_fk` FOREIGN KEY (`frequencyID`) REFERENCES `v7ocf_organizer_frequencies` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion

# region associations
ALTER TABLE `v7ocf_organizer_associations`
    ADD CONSTRAINT `association_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `v7ocf_organizer_subjects` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

# region curricula
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_curricula` (
    `id`        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `parentID`  INT(11) UNSIGNED DEFAULT NULL,
    `programID` INT(11) UNSIGNED DEFAULT NULL,
    `poolID`    INT(11) UNSIGNED DEFAULT NULL,
    `subjectID` INT(11) UNSIGNED DEFAULT NULL,
    `level`     INT(11) UNSIGNED DEFAULT NULL,
    `lft`       INT(11) UNSIGNED DEFAULT NULL,
    `ordering`  INT(11) UNSIGNED DEFAULT NULL,
    `rgt`       INT(11) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `parentID` (`parentID`),
    INDEX `poolID` (`poolID`),
    INDEX `programID` (`programID`),
    INDEX `subjectID` (`subjectID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_curricula` (`id`, `parentID`, `programID`, `poolID`, `subjectID`, `level`, `lft`, `ordering`, `rgt`)
SELECT `id`, `parentID`, `programID`, `poolID`, `subjectID`, `level`, `lft`, `ordering`, `rgt`
FROM `v7ocf_thm_organizer_mappings`;

ALTER TABLE `v7ocf_organizer_curricula`
    ADD CONSTRAINT `curriculum_parentID_fk` FOREIGN KEY (`parentID`) REFERENCES `v7ocf_organizer_curricula` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `curriculum_programID_fk` FOREIGN KEY (`programID`) REFERENCES `v7ocf_organizer_programs` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `curriculum_poolID_fk` FOREIGN KEY (`poolID`) REFERENCES `v7ocf_organizer_pools` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `curriculum_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `v7ocf_organizer_subjects` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

# region prerequisites
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_prerequisites` (
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

INSERT IGNORE INTO `v7ocf_organizer_prerequisites` (`id`, `subjectID`, `prerequisiteID`)
SELECT *
FROM `v7ocf_thm_organizer_prerequisites`;

ALTER TABLE `v7ocf_organizer_prerequisites`
    ADD CONSTRAINT `prerequisite_prerequisiteID_fk` FOREIGN KEY (`prerequisiteID`) REFERENCES `v7ocf_organizer_curricula` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `prerequisite_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `v7ocf_organizer_curricula` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

# region subject persons
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_subject_persons` (
    `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `personID`  INT(11) UNSIGNED    NOT NULL,
    `role`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'The person''s role for the given subject. Roles are not mutually exclusive. Possible values: 1 - coordinates, 2 - teaches.',
    `subjectID` INT(11) UNSIGNED    NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `entry` (`personID`, `role`, `subjectID`),
    INDEX `personID` (`personID`),
    INDEX `subjectID` (`subjectID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_subject_persons` (`id`, `personID`, `role`, `subjectID`)
SELECT `id`, `teacherID`, `teacherResp`, `subjectID`
FROM `v7ocf_thm_organizer_subject_teachers`;

ALTER TABLE `v7ocf_organizer_subject_persons`
    ADD CONSTRAINT `subject_person_personID_fk` FOREIGN KEY (`personID`) REFERENCES `v7ocf_organizer_persons` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `subject_person_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `v7ocf_organizer_subjects` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

