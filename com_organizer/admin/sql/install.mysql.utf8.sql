CREATE TABLE IF NOT EXISTS `#__organizer_associations` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `organizationID` INT(11) UNSIGNED NOT NULL,
    `categoryID`     INT(11) UNSIGNED DEFAULT NULL,
    `groupID`        INT(11) UNSIGNED DEFAULT NULL,
    `personID`       INT(11) UNSIGNED DEFAULT NULL,
    `programID`      INT(11) UNSIGNED DEFAULT NULL,
    `poolID`         INT(11) UNSIGNED DEFAULT NULL,
    `subjectID`      INT(11) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `categoryID` (`categoryID`),
    KEY `groupID` (`groupID`),
    KEY `organizationID` (`organizationID`),
    KEY `personID` (`personID`),
    KEY `programID` (`programID`),
    KEY `poolID` (`poolID`),
    KEY `subjectID` (`subjectID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_blocks` (
    `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `date`      DATE                NOT NULL,
    `dow`       TINYINT(1) UNSIGNED NOT NULL,
    `endTime`   TIME                NOT NULL,
    `startTime` TIME                NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`date`, `endTime`, `startTime`),
    KEY `date` (`date`),
    KEY `dow` (`dow`),
    KEY `endTime` (`endTime`),
    KEY `startTime` (`startTime`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_bookings` (
    `id`        INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `blockID`   INT(11) UNSIGNED NOT NULL,
    `unitID`    INT(11) UNSIGNED NOT NULL,
    `code`      VARCHAR(60)      NOT NULL,
    `endTime`   TIME DEFAULT NULL,
    `startTime` TIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `entry` UNIQUE (`blockID`, `unitID`),
    KEY `blockID` (`blockID`),
    KEY `unitID` (`unitID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_buildings` (
    `id`           INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `campusID`     INT(11) UNSIGNED             DEFAULT NULL,
    `name`         VARCHAR(150)        NOT NULL,
    `active`       TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `address`      VARCHAR(255)        NOT NULL,
    `location`     VARCHAR(20)         NOT NULL,
    `propertyType` INT(1) UNSIGNED     NOT NULL DEFAULT 0 COMMENT '0 - new/unknown | 1 - owned | 2 - rented/leased',
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`campusID`, `name`),
    KEY `campusID` (`campusID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_campuses` (
    `id`       INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`    VARCHAR(255)                 DEFAULT NULL,
    `parentID` INT(11) UNSIGNED             DEFAULT NULL,
    `name_de`  VARCHAR(150)        NOT NULL,
    `name_en`  VARCHAR(150)        NOT NULL,
    `active`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `address`  VARCHAR(255)        NOT NULL,
    `city`     VARCHAR(60)         NOT NULL,
    `gridID`   INT(11) UNSIGNED             DEFAULT NULL,
    `isCity`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `location` VARCHAR(20)         NOT NULL,
    `zipCode`  VARCHAR(60)         NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `englishName` (`parentID`, `name_en`),
    UNIQUE KEY `germanName` (`parentID`, `name_de`),
    KEY `gridID` (`gridID`),
    KEY `parentID` (`parentID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_categories` (
    `id`       INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`    VARCHAR(255)                 DEFAULT NULL,
    `code`     VARCHAR(60)         NOT NULL,
    `name_de`  VARCHAR(150)        NOT NULL,
    `name_en`  VARCHAR(150)        NOT NULL,
    `active`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `suppress` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `code` (`code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_colors` (
    `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name_de` VARCHAR(150)     NOT NULL,
    `name_en` VARCHAR(150)     NOT NULL,
    `color`   VARCHAR(7)       NOT NULL,
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT INTO `#__organizer_colors`
VALUES (1, 'Hellstgruen', 'Lightest Green', '#dfeec8'),
       (2, 'Hellstgrau', 'Lightest Grey', '#d2d6d9'),
       (3, 'Hellstrot', 'Lightest Red', '#e6c4cb'),
       (4, 'Hellstgelb', 'Lightest Yellow', '#fceabf'),
       (5, 'Hellstcyan', 'Lightest Cyan', '#00ffff'),
       (6, 'Hellstblau', 'Lightest Blue', '#bfc9dd'),
       (7, 'Hellgruen', 'Light Green', '#bfdc91'),
       (8, 'Hellgrau', 'Light Grey', '#a4adb2'),
       (9, 'Hellrot', 'Light Red', '#cd8996'),
       (10, 'Hellgelb', 'Light Yellow', '#f9d47f'),
       (11, 'Hellcyan', 'Light Cyan', '#00e2e2'),
       (12, 'Hellblau', 'Light Blue', '#7f93bb'),
       (13, 'Mittelgruen', 'Middling Green', '#a0cb5b'),
       (14, 'Mittelrot', 'Middling Red', '#b54e62'),
       (15, 'Mittelgelb', 'Middling Yellow', '#f7bf40'),
       (16, 'Cyan', 'Cyan', '#00a8a8'),
       (17, 'Grün', 'Green', '#80ba24'),
       (18, 'Mittelgrau', 'Middling Grey', '#77858c'),
       (19, 'Rot', 'Red', '#9c132e'),
       (20, 'Gelb', 'Yellow', '#f4aa00'),
       (21, 'Mittelcyan', 'Middling Cyan', '#00c5c5'),
       (22, 'Mittelblau', 'Middling Blue', '#405e9a'),
       (23, 'Dunkelgrün', 'Dark Green', '#6ea925'),
       (24, 'Dunkelgrau', 'Dark Grey', '#283640'),
       (25, 'Dunkelrot', 'Dark Red', '#82132e'),
       (26, 'Weiß', 'White', '#ffffff'),
       (27, 'Grau', 'Grey', '#4a5c66'),
       (28, 'Schwarz', 'Black', '#000000'),
       (29, 'Dunkelgelb', 'Dark Yellow', '#f0a400'),
       (30, 'Dunkelcyan', 'Dark Cyan', '#008b8b'),
       (31, 'Blau', 'Blue', '#002878'),
       (32, 'Dunkelblau', 'Dark Blue', '#002856'),
       (33, 'Hellstlila', 'Lightest Purple', '#ddd1e7'),
       (34, 'Helllila', 'Light Purple', '#bba3d0'),
       (35, 'Mittellila', 'Middling Purple', '#9975b9'),
       (36, 'Lila', 'Purple', '#7647a2'),
       (37, 'Dunkellila', 'Dark Purple', '#551A8B');

CREATE TABLE IF NOT EXISTS `#__organizer_course_participants` (
    `id`              INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `courseID`        INT(11) UNSIGNED NOT NULL,
    `participantID`   INT(11)          NOT NULL,
    `attended`        TINYINT(1) UNSIGNED DEFAULT 0,
    `paid`            TINYINT(1) UNSIGNED DEFAULT 0,
    `participantDate` DATETIME            DEFAULT NULL,
    `status`          TINYINT(1) UNSIGNED DEFAULT 0,
    `statusDate`      DATETIME            DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`courseID`, `participantID`),
    KEY `courseID` (`courseID`),
    KEY `participantID` (`participantID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_courses` (
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
    UNIQUE KEY `alias` (`alias`),
    KEY `campusID` (`campusID`),
    KEY `termID` (`termID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_curricula` (
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
    KEY `parentID` (`parentID`),
    KEY `poolID` (`poolID`),
    KEY `programID` (`programID`),
    KEY `subjectID` (`subjectID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_degrees` (
    `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias`        VARCHAR(255) DEFAULT NULL,
    `abbreviation` VARCHAR(25)      NOT NULL,
    `code`         VARCHAR(60)      NOT NULL,
    `name`         VARCHAR(150)     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `code` (`code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT INTO `#__organizer_degrees`
VALUES (1, 'beng', 'B.Eng.', 'BE', 'Bachelor of Engineering'),
       (2, 'bsc', 'B.Sc.', 'BS', 'Bachelor of Science'),
       (3, 'ba', 'B.A.', 'BA', 'Bachelor of Arts'),
       (4, 'meng', 'M.Eng.', 'ME', 'Master of Engineering'),
       (5, 'msc', 'M.Sc.', 'MS', 'Master of Science'),
       (6, 'ma', 'M.A.', 'MA', 'Master of Arts'),
       (7, 'mba', 'M.B.A.', 'MB', 'Master of Business Administration and Engineering'),
       (8, 'med', 'M.Ed.', 'MH', 'Master of Education');

CREATE TABLE IF NOT EXISTS `#__organizer_event_coordinators` (
    `id`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `eventID`  INT(11) UNSIGNED NOT NULL,
    `personID` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`eventID`, `personID`),
    KEY `eventID` (`eventID`),
    KEY `personID` (`personID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_events` (
    `id`               INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`            VARCHAR(255)                 DEFAULT NULL,
    `code`             VARCHAR(60)         NOT NULL COLLATE utf8mb4_bin,
    `name_de`          VARCHAR(150)        NOT NULL,
    `name_en`          VARCHAR(150)        NOT NULL,
    `subjectNo`        VARCHAR(45)         NOT NULL DEFAULT '',
    `campusID`         INT(11) UNSIGNED             DEFAULT NULL,
    `organizationID`   INT(11) UNSIGNED    NOT NULL,
    `active`           TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `contact_de`       TEXT,
    `contact_en`       TEXT,
    `content_de`       TEXT,
    `content_en`       TEXT,
    `courseContact_de` TEXT,
    `courseContact_en` TEXT,
    `deadline`         INT(2) UNSIGNED              DEFAULT 0,
    `description_de`   TEXT,
    `description_en`   TEXT,
    `fee`              INT(3) UNSIGNED              DEFAULT 0,
    `maxParticipants`  INT(4) UNSIGNED              DEFAULT 1000,
    `organization_de`  TEXT,
    `organization_en`  TEXT,
    `pretests_de`      TEXT,
    `pretests_en`      TEXT,
    `preparatory`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `registrationType` INT(1) UNSIGNED              DEFAULT NULL,
    `suppress`         TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    KEY `campusID` (`campusID`),
    KEY `code` (`code`),
    KEY `organizationID` (`organizationID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_field_colors` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `colorID`        INT(11) UNSIGNED NOT NULL,
    `fieldID`        INT(11) UNSIGNED NOT NULL,
    `organizationID` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`fieldID`, `organizationID`),
    KEY `colorID` (`colorID`),
    KEY `fieldID` (`fieldID`),
    KEY `organizationID` (`organizationID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_fields` (
    `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias`   VARCHAR(255) DEFAULT NULL,
    `code`    VARCHAR(60)      NOT NULL COLLATE utf8mb4_bin,
    `name_de` VARCHAR(150)     NOT NULL,
    `name_en` VARCHAR(150)     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `code` (`code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_frequencies` (
    `id`      INT(1) UNSIGNED NOT NULL,
    `name_de` VARCHAR(150)    NOT NULL,
    `name_en` VARCHAR(150)    NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE `name_de` (`name_de`),
    UNIQUE `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT INTO `#__organizer_frequencies`
VALUES (1, 'Nach Termin', 'By Appointment'),
       (2, 'Nur im Sommersemester', 'Only Spring/Summer Term'),
       (3, 'Nur im Wintersemester', 'Only Fall/Winter Term'),
       (4, 'Jedes Semester', 'Semesterly'),
       (5, 'Nach Bedarf', 'As Needed'),
       (6, 'Einmal im Jahr', 'Yearly');

CREATE TABLE IF NOT EXISTS `#__organizer_grids` (
    `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `code`      VARCHAR(60)         NOT NULL,
    `name_de`   VARCHAR(150)                 DEFAULT NULL,
    `name_en`   VARCHAR(150)                 DEFAULT NULL,
    `grid`      TEXT,
    `isDefault` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_group_publishing` (
    `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `groupID`   INT(11) UNSIGNED    NOT NULL,
    `termID`    INT(11) UNSIGNED    NOT NULL,
    `published` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`groupID`, `termID`),
    KEY `groupID` (`groupID`),
    KEY `termID` (`termID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_groups` (
    `id`          INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`       VARCHAR(255)                 DEFAULT NULL,
    `code`        VARCHAR(60)         NOT NULL,
    `name_de`     VARCHAR(150)        NOT NULL,
    `name_en`     VARCHAR(150)        NOT NULL,
    `active`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `categoryID`  INT(11) UNSIGNED    NOT NULL,
    `fullName_de` VARCHAR(200)        NOT NULL,
    `fullName_en` VARCHAR(200)        NOT NULL,
    `gridID`      INT(11) UNSIGNED             DEFAULT 1,
    `suppress`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `code` (`code`),
    UNIQUE KEY `entry` (`code`, `categoryID`),
    KEY `categoryID` (`categoryID`),
    KEY `gridID` (`gridID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_holidays` (
    `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name_de`   VARCHAR(150)        NOT NULL,
    `name_en`   VARCHAR(150)        NOT NULL,
    `startDate` DATE                NOT NULL,
    `endDate`   DATE                NOT NULL,
    `type`      TINYINT(1) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_instance_groups` (
    `id`       INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `assocID`  INT(20) UNSIGNED NOT NULL COMMENT 'The instance to person association id.',
    `groupID`  INT(11) UNSIGNED NOT NULL,
    `delta`    VARCHAR(10)      NOT NULL DEFAULT '',
    `modified` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `entry` UNIQUE (`assocID`, `groupID`),
    KEY `assocID` (`assocID`),
    KEY `groupID` (`groupID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_instance_participants` (
    `id`            INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `instanceID`    INT(20) UNSIGNED NOT NULL,
    `participantID` INT(11)          NOT NULL,
    `roomID`        INT(11) UNSIGNED          DEFAULT NULL,
    `seat`          VARCHAR(60)      NOT NULL DEFAULT '',
    `attended`      TINYINT(1) UNSIGNED       DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`instanceID`, `participantID`),
    KEY `instanceID` (`instanceID`),
    KEY `participantID` (`participantID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_instance_persons` (
    `id`         INT(20) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `instanceID` INT(20) UNSIGNED    NOT NULL,
    `personID`   INT(11) UNSIGNED    NOT NULL,
    `roleID`     TINYINT(2) UNSIGNED NOT NULL DEFAULT 1,
    `delta`      VARCHAR(10)         NOT NULL DEFAULT '',
    `modified`   TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `entry` UNIQUE (`instanceID`, `personID`),
    KEY `instanceID` (`instanceID`),
    KEY `personID` (`personID`),
    KEY `roleID` (`roleID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_instance_rooms` (
    `id`       INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `assocID`  INT(20) UNSIGNED NOT NULL COMMENT 'The instance to person association id.',
    `roomID`   INT(11) UNSIGNED NOT NULL,
    `delta`    VARCHAR(10)      NOT NULL DEFAULT '',
    `modified` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`assocID`, `roomID`),
    KEY `assocID` (`assocID`),
    KEY `roomID` (`roomID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_instances` (
    `id`       INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `blockID`  INT(11) UNSIGNED NOT NULL,
    `eventID`  INT(11) UNSIGNED          DEFAULT NULL,
    `methodID` INT(11) UNSIGNED          DEFAULT NULL,
    `unitID`   INT(11) UNSIGNED NOT NULL,
    `title`    VARCHAR(255)     NOT NULL DEFAULT '',
    `delta`    VARCHAR(10)      NOT NULL DEFAULT '',
    `modified` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `entry` UNIQUE (`eventID`, `blockID`, `unitID`),
    KEY `blockID` (`blockID`),
    KEY `eventID` (`eventID`),
    KEY `methodID` (`methodID`),
    KEY `unitID` (`unitID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_methods` (
    `id`              INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`           VARCHAR(255)                 DEFAULT NULL,
    `code`            VARCHAR(60)         NOT NULL,
    `name_de`         VARCHAR(150)                 DEFAULT NULL,
    `name_en`         VARCHAR(150)                 DEFAULT NULL,
    `abbreviation_de` VARCHAR(25)                  DEFAULT '',
    `abbreviation_en` VARCHAR(25)                  DEFAULT '',
    `plural_de`       VARCHAR(150)                 DEFAULT '',
    `plural_en`       VARCHAR(150)                 DEFAULT '',
    `relevant`        TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    UNIQUE `code` (`code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT INTO `#__organizer_methods`(`id`, `alias`, `code`, `name_de`, `name_en`, `abbreviation_de`, `abbreviation_en`,
                                   `plural_de`, `plural_en`, `relevant`)
VALUES (1, NULL, 'KES', 'Klausureinsicht', 'Final Review', 'KES', 'FRV', 'Klausureinsichten', 'Final Reviews', 0),
       (2, NULL, 'KLA', 'Klausur', 'Final', 'KLA', 'FIN', 'Klausuren', 'Finals', 0),
       (3, NULL, 'KVB', 'Klausurvorbereitung', 'Final Preparation', 'KVB', 'FPR', 'Klausurvorbereitungen',
        'Finals Preparations', 0),
       (4, NULL, 'LAB', 'Labor', 'Lab Exercise', 'LAB', 'LAB', 'Laboren', 'Lab Exercises', 1),
       (5, NULL, 'PRK', 'Praktikum', 'Practice', 'PRK', 'PRC', 'Praktiken', 'Practices', 1),
       (6, NULL, 'PRÜ', 'Prüfung', 'Examination', 'PRÜ', 'EXM', 'Prüfungen', 'Examinations', 0),
       (7, NULL, 'SEM', 'Seminar', 'Seminar', 'SEM', 'SEM', 'Seminaren', 'Seminars', 1),
       (8, NULL, 'SMU', 'Seminaristische Unterricht', 'Guided Discussion', 'SMU', 'GDS', 'Seminaristische Unterrichte',
        'Guided Discussions', 1),
       (9, NULL, 'TUT', 'Tutorium', 'Tutorium', 'TUT', 'TUT', 'Tutorien', 'Tutoria', 0),
       (10, NULL, 'ÜBG', 'Übung', 'Exercise', 'ÜBG', 'EXC', 'Übungen', 'Exercises', 1),
       (11, NULL, 'VRL', 'Vorlesung', 'Lecture', 'VRL', 'LCT', 'Vorlesungen', 'Lectures', 1),
       (12, NULL, 'PAB', 'Projektarbeit', 'Project', 'PAB', 'PRJ', 'Projektarbeiten', 'Projects', 1);

CREATE TABLE IF NOT EXISTS `#__organizer_monitors` (
    `id`              INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `ip`              VARCHAR(15)         NOT NULL,
    `roomID`          INT(11) UNSIGNED             DEFAULT NULL,
    `content`         VARCHAR(256)                 DEFAULT '',
    `contentRefresh`  INT(3) UNSIGNED     NOT NULL DEFAULT 60,
    `display`         INT(1) UNSIGNED     NOT NULL DEFAULT 1,
    `interval`        INT(1) UNSIGNED     NOT NULL DEFAULT 1,
    `scheduleRefresh` INT(3) UNSIGNED     NOT NULL DEFAULT 60,
    `useDefaults`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ip` (`ip`),
    KEY `roomID` (`roomID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_organizations` (
    `id`              INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `asset_id`        INT(11)                      DEFAULT NULL,
    `alias`           VARCHAR(255)                 DEFAULT NULL,
    `name_de`         VARCHAR(150)        NOT NULL,
    `name_en`         VARCHAR(150)        NOT NULL,
    `active`          TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `abbreviation_de` VARCHAR(25)         NOT NULL,
    `abbreviation_en` VARCHAR(25)         NOT NULL,
    `contactID`       INT(11)                      DEFAULT NULL,
    `contactEmail`    VARCHAR(100)                 DEFAULT NULL,
    `fullName_de`     VARCHAR(200)        NOT NULL,
    `fullName_en`     VARCHAR(200)        NOT NULL,
    `shortName_de`    VARCHAR(50)         NOT NULL,
    `shortName_en`    VARCHAR(50)         NOT NULL,
    `URL`             VARCHAR(255)                 DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `abbreviation_de` (`abbreviation_de`),
    UNIQUE KEY `abbreviation_en` (`abbreviation_en`),
    UNIQUE KEY `shortName_de` (`shortName_de`),
    UNIQUE KEY `shortName_en` (`shortName_en`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`),
    UNIQUE KEY `fullName_de` (`fullName_de`),
    UNIQUE KEY `fullName_en` (`fullName_en`),
    KEY `contactID` (`contactID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_participants` (
    `id`        INT(11)             NOT NULL,
    `forename`  VARCHAR(255)        NOT NULL DEFAULT '',
    `surname`   VARCHAR(255)        NOT NULL DEFAULT '',
    `telephone` VARCHAR(60)         NOT NULL DEFAULT '',
    `address`   VARCHAR(255)        NOT NULL DEFAULT '',
    `city`      VARCHAR(60)         NOT NULL DEFAULT '',
    `notify`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `programID` INT(11) UNSIGNED             DEFAULT NULL,
    `zipCode`   VARCHAR(60)         NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    KEY `programID` (`programID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_persons` (
    `id`       INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`    VARCHAR(255)                 DEFAULT NULL,
    `code`     VARCHAR(60)                  DEFAULT NULL COLLATE utf8mb4_bin,
    `forename` VARCHAR(255)        NOT NULL DEFAULT '',
    `surname`  VARCHAR(255)        NOT NULL,
    `active`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `suppress` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `title`    VARCHAR(45)         NOT NULL DEFAULT '',
    `username` VARCHAR(150)                 DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `code` (`code`),
    UNIQUE KEY `username` (`username`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_pools` (
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
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `lsfID` (`lsfID`),
    KEY `fieldID` (`fieldID`),
    KEY `groupID` (`groupID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_prerequisites` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `subjectID`      INT(11) UNSIGNED NOT NULL,
    `prerequisiteID` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`prerequisiteID`, `subjectID`),
    KEY `prerequisiteID` (`prerequisiteID`),
    KEY `subjectID` (`subjectID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_programs` (
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
    `fee`            TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `frequencyID`    INT(1) UNSIGNED              DEFAULT NULL,
    `organizationID` INT(11) UNSIGNED             DEFAULT NULL,
    `nc`             TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `special`        TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    KEY `categoryID` (`categoryID`),
    KEY `degreeID` (`degreeID`),
    KEY `frequencyID` (`frequencyID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_roles` (
    `id`              TINYINT(2) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`            VARCHAR(60)         NOT NULL,
    `abbreviation_de` VARCHAR(25)         NOT NULL,
    `abbreviation_en` VARCHAR(25)         NOT NULL,
    `name_de`         VARCHAR(150)        NOT NULL,
    `name_en`         VARCHAR(150)        NOT NULL,
    `plural_de`       VARCHAR(150)        NOT NULL,
    `plural_en`       VARCHAR(150)        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `abbreviation_de` (`abbreviation_de`),
    UNIQUE KEY `abbreviation_en` (`abbreviation_en`),
    UNIQUE KEY `code` (`code`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__organizer_roles`
VALUES (1, 'DOZ', 'DOZ', 'TCH', 'Lehrende', 'Teacher', 'Lehrende', 'Teachers'),
       (2, 'TUT', 'TUT', 'TUT', 'Betreuende', 'Tutor', 'Betreuenden', 'Tutors'),
       (3, 'AFS', 'AFS', 'SPR', 'Aufsicht', 'Supervisor', 'Aufsichten', 'Supervisors'),
       (4, 'REF', 'REF', 'SPK', 'Referent', 'Speaker', 'Referenten', 'Speakers');

CREATE TABLE IF NOT EXISTS `#__organizer_rooms` (
    `id`         INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`      VARCHAR(255)                 DEFAULT NULL,
    `code`       VARCHAR(60)         NOT NULL COLLATE utf8mb4_bin,
    `name`       VARCHAR(150)        NOT NULL,
    `active`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `buildingID` INT(11) UNSIGNED             DEFAULT NULL,
    `capacity`   INT(4) UNSIGNED              DEFAULT NULL,
    `roomtypeID` INT(11) UNSIGNED             DEFAULT NULL,
    `virtual`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `code` (`code`),
    KEY `buildingID` (`buildingID`),
    KEY `roomtypeID` (`roomtypeID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_roomtypes` (
    `id`             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`          VARCHAR(255)                 DEFAULT NULL,
    `code`           VARCHAR(60)         NOT NULL COLLATE utf8mb4_bin,
    `name_de`        VARCHAR(150)        NOT NULL,
    `name_en`        VARCHAR(150)        NOT NULL,
    `description_de` TEXT,
    `description_en` TEXT,
    `minCapacity`    INT(4) UNSIGNED              DEFAULT NULL,
    `maxCapacity`    INT(4) UNSIGNED              DEFAULT NULL,
    `suppress`       TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `code` (`code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_runs` (
    `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name_de` VARCHAR(150)     NOT NULL,
    `name_en` VARCHAR(150)     NOT NULL,
    `termID`  INT(11) UNSIGNED NOT NULL,
    `endDate` DATE             NOT NULL,
    `run`     TEXT,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry_de` (`name_de`, `termID`),
    UNIQUE KEY `entry_en` (`name_en`, `termID`),
    KEY `termID` (`termID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_schedules` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `organizationID` INT(11) UNSIGNED NOT NULL,
    `termID`         INT(11) UNSIGNED NOT NULL,
    `creationDate`   DATE    DEFAULT NULL,
    `creationTime`   TIME    DEFAULT NULL,
    `userID`         INT(11) DEFAULT NULL,
    `schedule`       MEDIUMTEXT,
    PRIMARY KEY (`id`),
    KEY `organizationID` (`organizationID`),
    KEY `termID` (`termID`),
    KEY `userID` (`userID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_subject_events` (
    `id`        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `eventID`   INT(11) UNSIGNED NOT NULL,
    `subjectID` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`eventID`, `subjectID`),
    KEY `eventID` (`eventID`),
    KEY `subjectID` (`subjectID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_subject_persons` (
    `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `personID`  INT(11) UNSIGNED    NOT NULL,
    `role`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'The person''s role for the given subject. Roles are not mutually exclusive. Possible values: 1 - coordinates, 2 - teaches.',
    `subjectID` INT(11) UNSIGNED    NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`personID`, `role`, `subjectID`),
    KEY `personID` (`personID`),
    KEY `subjectID` (`subjectID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_subjects` (
    `id`                          INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT,
    `alias`                       VARCHAR(255)                   DEFAULT NULL,
    `code`                        VARCHAR(60)                    DEFAULT NULL,
    `fullName_de`                 VARCHAR(200)          NOT NULL,
    `fullName_en`                 VARCHAR(200)          NOT NULL,
    `lsfID`                       INT(11) UNSIGNED               DEFAULT NULL,
    `abbreviation_de`             VARCHAR(25)           NOT NULL DEFAULT '',
    `abbreviation_en`             VARCHAR(25)           NOT NULL DEFAULT '',
    `aids_de`                     TEXT,
    `aids_en`                     TEXT,
    `bonusPoints_de`              TEXT,
    `bonusPoints_en`              TEXT,
    `content_de`                  TEXT,
    `content_en`                  TEXT,
    `creditpoints`                DOUBLE(4, 1) UNSIGNED NOT NULL DEFAULT 0.0,
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
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `lsfID` (`lsfID`),
    KEY `code` (`code`),
    KEY `fieldID` (`fieldID`),
    KEY `frequencyID` (`frequencyID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `#__organizer_terms` (
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
    UNIQUE KEY `alias` (`alias`),
    UNIQUE KEY `code` (`code`),
    UNIQUE KEY `entry` (`code`, `startDate`, `endDate`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_units` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`           VARCHAR(60)      NOT NULL,
    `organizationID` INT(11) UNSIGNED          DEFAULT NULL,
    `termID`         INT(11) UNSIGNED NOT NULL,
    `comment`        VARCHAR(255)              DEFAULT '',
    `courseID`       INT(11) UNSIGNED          DEFAULT NULL,
    `delta`          VARCHAR(10)      NOT NULL DEFAULT '',
    `endDate`        DATE                      DEFAULT NULL,
    `gridID`         INT(11) UNSIGNED          DEFAULT NULL,
    `modified`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `runID`          INT(11) UNSIGNED          DEFAULT NULL,
    `startDate`      DATE                      DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `entry` (`code`, `organizationID`, `termID`),
    KEY `code` (`code`),
    KEY `courseID` (`courseID`),
    KEY `gridID` (`gridID`),
    KEY `organizationID` (`organizationID`),
    KEY `runID` (`runID`),
    KEY `termID` (`termID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `#__organizer_associations`
    ADD CONSTRAINT `association_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `#__organizer_organizations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `association_categoryID_fk` FOREIGN KEY (`categoryID`) REFERENCES `#__organizer_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `association_groupID_fk` FOREIGN KEY (`groupID`) REFERENCES `#__organizer_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `association_personID_fk` FOREIGN KEY (`personID`) REFERENCES `#__organizer_persons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `association_poolID_fk` FOREIGN KEY (`poolID`) REFERENCES `#__organizer_pools` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `association_programID_fk` FOREIGN KEY (`programID`) REFERENCES `#__organizer_programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `association_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `#__organizer_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_bookings`
    ADD CONSTRAINT `booking_blockID_fk` FOREIGN KEY (`blockID`) REFERENCES `#__organizer_blocks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `booking_unitID_fk` FOREIGN KEY (`unitID`) REFERENCES `#__organizer_units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_buildings`
    ADD CONSTRAINT `building_campusID_fk` FOREIGN KEY (`campusID`) REFERENCES `#__organizer_campuses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `#__organizer_campuses`
    ADD CONSTRAINT `campus_gridID_fk` FOREIGN KEY (`gridID`) REFERENCES `#__organizer_grids` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `campus_parentID_fk` FOREIGN KEY (`parentID`) REFERENCES `#__organizer_campuses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `#__organizer_course_participants`
    ADD CONSTRAINT `course_participant_courseID_fk` FOREIGN KEY (`courseID`) REFERENCES `#__organizer_courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `course_participant_participantID_fk` FOREIGN KEY (`participantID`) REFERENCES `#__organizer_participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_courses`
    ADD CONSTRAINT `course_campusID_fk` FOREIGN KEY (`campusID`) REFERENCES `#__organizer_campuses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `course_termID_fk` FOREIGN KEY (`termID`) REFERENCES `#__organizer_terms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_curricula`
    ADD CONSTRAINT `curriculum_parentID_fk` FOREIGN KEY (`parentID`) REFERENCES `#__organizer_curricula` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `curriculum_programID_fk` FOREIGN KEY (`programID`) REFERENCES `#__organizer_programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `curriculum_poolID_fk` FOREIGN KEY (`poolID`) REFERENCES `#__organizer_pools` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `curriculum_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `#__organizer_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_event_coordinators`
    ADD CONSTRAINT `event_coordinator_eventID_fk` FOREIGN KEY (`eventID`) REFERENCES `#__organizer_events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `event_coordinator_personID_fk` FOREIGN KEY (`personID`) REFERENCES `#__organizer_persons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_events`
    ADD CONSTRAINT `event_campusID_fk` FOREIGN KEY (`campusID`) REFERENCES `#__organizer_campuses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `event_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `#__organizer_organizations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_field_colors`
    ADD CONSTRAINT `field_color_colorID_fk` FOREIGN KEY (`colorID`) REFERENCES `#__organizer_colors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `field_color_fieldID_fk` FOREIGN KEY (`fieldID`) REFERENCES `#__organizer_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `field_color_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `#__organizer_organizations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_group_publishing`
    ADD CONSTRAINT `group_publishing_groupID_fk` FOREIGN KEY (`groupID`) REFERENCES `#__organizer_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `group_publishing_termID_fk` FOREIGN KEY (`termID`) REFERENCES `#__organizer_terms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_groups`
    ADD CONSTRAINT `group_categoryID_fk` FOREIGN KEY (`categoryID`) REFERENCES `#__organizer_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `group_gridID_fk` FOREIGN KEY (`gridID`) REFERENCES `#__organizer_grids` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `#__organizer_instance_groups`
    ADD CONSTRAINT `instance_group_assocID_fk` FOREIGN KEY (`assocID`) REFERENCES `#__organizer_instance_persons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_group_groupID_fk` FOREIGN KEY (`groupID`) REFERENCES `#__organizer_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_instance_participants`
    ADD CONSTRAINT `instance_participant_instanceID_fk` FOREIGN KEY (`instanceID`) REFERENCES `#__organizer_instances` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_participant_participantID_fk` FOREIGN KEY (`participantID`) REFERENCES `#__organizer_participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_participant_roomID_fk` FOREIGN KEY (`roomID`) REFERENCES `#__organizer_rooms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `#__organizer_instance_persons`
    ADD CONSTRAINT `instance_person_instanceID_fk` FOREIGN KEY (`instanceID`) REFERENCES `#__organizer_instances` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_person_personID_fk` FOREIGN KEY (`personID`) REFERENCES `#__organizer_persons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_person_roleID_fk` FOREIGN KEY (`roleID`) REFERENCES `#__organizer_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_instance_rooms`
    ADD CONSTRAINT `instance_room_assocID_fk` FOREIGN KEY (`assocID`) REFERENCES `#__organizer_instance_persons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_room_roomID_fk` FOREIGN KEY (`roomID`) REFERENCES `#__organizer_rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_instances`
    ADD CONSTRAINT `instance_blockID_fk` FOREIGN KEY (`blockID`) REFERENCES `#__organizer_blocks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_eventID_fk` FOREIGN KEY (`eventID`) REFERENCES `#__organizer_events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_methodID_fk` FOREIGN KEY (`methodID`) REFERENCES `#__organizer_methods` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `instance_unitID_fk` FOREIGN KEY (`unitID`) REFERENCES `#__organizer_units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_monitors`
    ADD CONSTRAINT `monitor_roomID_fk` FOREIGN KEY (`roomID`) REFERENCES `#__organizer_rooms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

# noinspection SqlResolve
ALTER TABLE `#__organizer_participants`
    ADD CONSTRAINT `participant_programID_fk` FOREIGN KEY (`programID`) REFERENCES `#__organizer_programs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `participant_userID_fk` FOREIGN KEY (`id`) REFERENCES `#__users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_pools`
    ADD CONSTRAINT `pool_fieldID_fk` FOREIGN KEY (`fieldID`) REFERENCES `#__organizer_fields` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `pool_groupID_fk` FOREIGN KEY (`groupID`) REFERENCES `#__organizer_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `#__organizer_prerequisites`
    ADD CONSTRAINT `prerequisite_prerequisiteID_fk` FOREIGN KEY (`prerequisiteID`) REFERENCES `#__organizer_curricula` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `prerequisite_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `#__organizer_curricula` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_programs`
    ADD CONSTRAINT `program_categoryID_fk` FOREIGN KEY (`categoryID`) REFERENCES `#__organizer_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `program_degreeID_fk` FOREIGN KEY (`degreeID`) REFERENCES `#__organizer_degrees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `program_frequencyID_fk` FOREIGN KEY (`frequencyID`) REFERENCES `#__organizer_frequencies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `#__organizer_rooms`
    ADD CONSTRAINT `room_buildingID_fk` FOREIGN KEY (`buildingID`) REFERENCES `#__organizer_buildings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `room_roomtypeID_fk` FOREIGN KEY (`roomtypeID`) REFERENCES `#__organizer_roomtypes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `#__organizer_runs`
    ADD CONSTRAINT `run_termID_fk` FOREIGN KEY (`termID`) REFERENCES `#__organizer_terms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

# noinspection SqlResolve
ALTER TABLE `#__organizer_schedules`
    ADD CONSTRAINT `schedule_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `#__organizer_organizations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `schedule_termID_fk` FOREIGN KEY (`termID`) REFERENCES `#__organizer_terms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `schedule_userID_fk` FOREIGN KEY (`userID`) REFERENCES `#__users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `#__organizer_subject_events`
    ADD CONSTRAINT `subject_event_eventID_fk` FOREIGN KEY (`eventID`) REFERENCES `#__organizer_events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `subject_event_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `#__organizer_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_subject_persons`
    ADD CONSTRAINT `subject_person_personID_fk` FOREIGN KEY (`personID`) REFERENCES `#__organizer_persons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `subject_person_subjectID_fk` FOREIGN KEY (`subjectID`) REFERENCES `#__organizer_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_subjects`
    ADD CONSTRAINT `subject_fieldID_fk` FOREIGN KEY (`fieldID`) REFERENCES `#__organizer_fields` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `subject_frequencyID_fk` FOREIGN KEY (`frequencyID`) REFERENCES `#__organizer_frequencies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `#__organizer_units`
    ADD CONSTRAINT `unit_courseID_fk` FOREIGN KEY (`courseID`) REFERENCES `#__organizer_courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `unit_gridID_fk` FOREIGN KEY (`gridID`) REFERENCES `#__organizer_grids` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `unit_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `#__organizer_organizations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `unit_runID_fk` FOREIGN KEY (`runID`) REFERENCES `#__organizer_runs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `unit_termID_fk` FOREIGN KEY (`termID`) REFERENCES `#__organizer_terms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;