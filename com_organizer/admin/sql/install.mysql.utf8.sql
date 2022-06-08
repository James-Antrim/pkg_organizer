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
    `location`     VARCHAR(22)         NOT NULL,
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
    `location` VARCHAR(22)         NOT NULL,
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

CREATE TABLE IF NOT EXISTS `#__organizer_cleaning_groups` (
    `id`           TINYINT(2) UNSIGNED   NOT NULL AUTO_INCREMENT,
    `name_de`      VARCHAR(150)          NOT NULL,
    `name_en`      VARCHAR(150)          NOT NULL,
    `days`         DOUBLE(6, 2) UNSIGNED NOT NULL,
    `maxValuation` SMALLINT(3) UNSIGNED  NOT NULL,
    `relevant`     TINYINT(1) UNSIGNED   NOT NULL DEFAULT 1,
    `valuation`    DOUBLE(6, 2) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT INTO `#__organizer_cleaning_groups` (`id`, `name_de`, `name_en`, `days`, `maxValuation`, `relevant`, `valuation`)
VALUES (1, 'Keine Reinigung  / nach Bedarf', 'No Cleaning / On Demand', 0, 0, 1, 0.00),
       (2, 'Besprechungsräume', 'Meeting Rooms', 4.35, 200, 1, 162.20),
       (3, 'Büroräume', 'Offices', 4.35, 160, 1, 129.76),
       (4, 'Eingangshallen', 'Entrances', 21, 200, 1, 162.20),
       (5, 'Hörsäle', 'Lecture Halls', 10.51, 150, 1, 121.65),
       (6, 'Funktionsräume', 'Function Rooms', 4.35, 150, 1, 121.65),
       (7, 'Labore', 'Laboratories', 10.51, 140, 1, 113.54),
       (8, 'Garderoben & Umkleideräume', 'Changing & Cloak Rooms', 21, 200, 1, 162.20),
       (9, 'Aufenthaltsräume', 'Lounges', 21, 110, 1, 89.21),
       (10, 'Sanitärräume', 'Sanitary Rooms', 21, 60, 1, 48.66),
       (11, 'Teeküchen', 'Kitchenettes', 21, 60, 1, 48.66),
       (12, 'Treppenhäuser', 'Stairwells', 4.35, 130, 1, 105.43),
       (13, 'Übungsräume', 'Training Rooms', 10.51, 150, 1, 121.65),
       (14, 'Verkehrsflächen, frequentiert', 'Frequented Conveyance Areas', 4.35, 250, 1, 202.75),
       (15, 'Werkstätten', 'Workshops', 10.51, 140, 1, 113.54),
       (16, 'Externe Verwaltung', 'External Management', 0, 0, 0, 0.00);

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

CREATE TABLE IF NOT EXISTS `#__organizer_equipment` (
    `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`    VARCHAR(60)  DEFAULT NULL,
    `name_de` VARCHAR(150) DEFAULT NULL,
    `name_en` VARCHAR(150) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE `code` (`code`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT INTO `#__organizer_equipment`
VALUES (1, 'B', 'Beamer', 'Projectors'),
       (2, 'D', 'Dokumentenkameras', 'Document Cameras'),
       (3, 'M', 'Mediaboxen', 'Mediabox'),
       (4, 'O', 'Overhead-Projektoren', 'Overhead Projector'),
       (5, 'T', 'Tafeln', 'Blackboard'),
       (6, 'W', 'Whiteboards', 'Whiteboard'),
       (7, NULL, 'Desinfektionsmittelspender', 'Disinfectant Dispensers'),
       (8, NULL, 'Handtuch-Papierspender', 'Paper Towel Dispensers'),
       (9, NULL, 'Hygienebehälter', 'Feminine Hygiene Disposal Containers'),
       (10, NULL, 'Leinwände', 'Projection Screens'),
       (11, NULL, 'Mülleimer', 'Trash Cans'),
       (12, NULL, 'Pulte', 'Podiums'),
       (13, NULL, 'Seifenspender', 'Soap Dispenser'),
       (14, NULL, 'Stuhle', 'Chairs'),
       (15, NULL, 'Tische', 'Tables'),
       (16, NULL, 'Toiletten', 'Toilets'),
       (17, NULL, 'Urinale', 'Urinals'),
       (18, NULL, 'Waschbecken', 'Sink'),
       (19, NULL, 'Zeichenmaterial', 'Blackboard Teaching Aids');

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
    UNIQUE KEY `entry` (`code`, `organizationID`),
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

CREATE TABLE IF NOT EXISTS `#__organizer_flooring` (
    `id`      SMALLINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name_de` VARCHAR(150)         NOT NULL,
    `name_en` VARCHAR(150)         NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT INTO `#__organizer_flooring` (`id`, `name_de`, `name_en`)
VALUES (1, 'PVC', 'PVC'),
       (2, 'Betonwerkstein', 'Cast Stone'),
       (3, 'Bodenanstrich', 'Paint'),
       (4, 'Dielen', 'Planks'),
       (5, 'Doppelboden', 'Raised Floor'),
       (6, 'Estrich', 'Screed'),
       (7, 'Fliesen', 'Tiles'),
       (8, 'Gitterrost', 'Grating'),
       (9, 'Holz', 'Wood'),
       (10, 'Kautschuk', 'Rubber'),
       (11, 'Linoleum', 'Linoleum'),
       (12, 'Parkett', 'Parquet'),
       (13, 'Riffelblech', 'Tread Plate'),
       (14, 'Sauberlaufmatte', 'Protective Mats'),
       (15, 'Stein', 'Stone'),
       (16, 'Teppich', 'Carpet'),
       (17, 'Teppich/Parkett', 'Carpet/Parquet');

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
    `attended`      TINYINT(1) UNSIGNED DEFAULT 0,
    `registered`    TINYINT(1) UNSIGNED DEFAULT 0,
    `roomID`        INT(11) UNSIGNED    DEFAULT NULL,
    `seat`          VARCHAR(60)         DEFAULT NULL,
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
    `id`         INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `blockID`    INT(11) UNSIGNED NOT NULL,
    `eventID`    INT(11) UNSIGNED          DEFAULT NULL,
    `methodID`   INT(11) UNSIGNED          DEFAULT NULL,
    `unitID`     INT(11) UNSIGNED NOT NULL,
    `title`      VARCHAR(255)     NOT NULL DEFAULT '',
    `delta`      VARCHAR(10)      NOT NULL DEFAULT '',
    `modified`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `attended`   INT(4) UNSIGNED  NOT NULL DEFAULT 0,
    `bookmarked` INT(4) UNSIGNED  NOT NULL DEFAULT 0,
    `registered` INT(4) UNSIGNED  NOT NULL DEFAULT 0,
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
    `public`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
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

CREATE TABLE IF NOT EXISTS `#__organizer_room_equipment` (
    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `equipmentID` INT(11) UNSIGNED          DEFAULT NULL,
    `roomID`      INT(11) UNSIGNED          DEFAULT NULL,
    `description` VARCHAR(255)     NOT NULL DEFAULT '',
    `quantity`    INT(4) UNSIGNED           DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `entry` (`equipmentID`, `roomID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__organizer_roomkeys` (
    `id`         SMALLINT(3) UNSIGNED NOT NULL,
    `key`        VARCHAR(3)           NOT NULL,
    `name_de`    VARCHAR(150)         NOT NULL,
    `name_en`    VARCHAR(150)         NOT NULL,
    `cleaningID` TINYINT(2) UNSIGNED DEFAULT NULL,
    `useID`      TINYINT(1) UNSIGNED  NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key` (`key`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT INTO `#__organizer_roomkeys` (`id`, `key`, `name_de`, `name_en`, `cleaningID`, `useID`)
VALUES (0, '000', 'Organisatiorische Flächen', 'Organizational Areas', 1, 0),
       (11, '011', 'Wohnflächen im Freien', 'Outdoor Residential Areas', 1, 0),
       (12, '012', 'Gemeinschaftsflächen im Freien', 'Outdoor Socialization Areas', 1, 0),
       (13, '013', 'Pausenflächen im Freien', 'Outdoor Break Areas', 1, 0),
       (14, '014', 'Warteflächen im Freien', 'Outdoor Waiting Areas', 1, 0),
       (15, '015', 'Speiseflächen im Freien', 'Outdoor Dining Areas', 1, 0),
       (31, '031', 'Produktions- & Prüflächen im Freien', 'Outdoor Production & Testing Areas', 1, 0),
       (32, '032', 'Werkstätten im Freien', 'Outdoor Workshops', 1, 0),
       (33, '033', 'Technologische Experimentierflächen im Freien', 'Outdoor Technical Research Areas', 1, 0),
       (34, '034', 'Physikalische, physikalisch-technische, elektrotechnische Experimentierflächen im Freien', 'Outdoor Physical & Physical-Technical Research Areas', 1, 0),
       (36, '036', 'Tierhaltungsflächen im Freien', 'Outdoor Husbandry Areas', 1, 0),
       (37, '037', 'Pflanzenzuchtflächen im Freien', 'Outdoor Botany Areas', 1, 0),
       (38, '038', 'Küchen im Freien', 'Outdoor Cooking Areas', 1, 0),
       (39, '039', 'Sonderarbeitsflächen im Freien', 'Outdoor Special Production Areas', 1, 0),
       (41, '041', 'Lagerflächen im Freien', 'Outdoor Storage Spaces', 1, 0),
       (44, '044', 'Annahme- und Ausgabeflächen im Freien', 'Outdoor Goods Distribution Areas', 1, 0),
       (45, '045', 'Verkaufsflächen im Freien', 'Outdoor Retail Areas', 1, 0),
       (46, '046', 'Ausstellungsflächen im Freien', 'Outdoor Retail Display Areas', 1, 0),
       (51, '051', 'Hörflächen im Freien', 'Outdoor Lecture Areas', 1, 0),
       (52, '052', 'Unterrichtsflächen im Freien', 'Outdoor Lesson Areas', 1, 0),
       (53, '053', 'Übungsflächen im Freien', 'Outdoor Practice Areas', 1, 0),
       (54, '054', 'Bibliotheksflächen im Freien', 'Outdoor Library Areas', 1, 0),
       (55, '055', 'Sportflächen im Freien', 'Outdoor Sports Areas', 1, 0),
       (56, '056', 'Versammlungsflächen im Freien', 'Outdoor Assembly Areas', 1, 0),
       (57, '057', 'Bühnen- und Studioflächen im Freien', 'Outdoor Event Areas', 1, 0),
       (66, '066', 'Freilufttherapieflächen', 'Outdoor Therapy Areas', 1, 0),
       (74, '074', 'Fahrzeugabstellflächen im Freien', 'Outdoor Parking Areas', 1, 0),
       (91, '091', 'Flure, Hallen im Freien', 'Outdoor Walkways', 1, 0),
       (92, '092', 'Treppen im Freien', 'Outdoor Stairways', 1, 0),
       (94, '094', 'Fahrzeugverkehrsflächen im Freien', 'Outdoor Traffic Areas', 1, 0),
       (111, '111', 'Wohnräume in Mehrzimmerwohnungen', 'Residential Rooms', 9, 1),
       (112, '112', 'Wohnküche', 'Residential Kitchens', 9, 1),
       (113, '113', 'Wohndiele', 'Residential Hallways', 9, 1),
       (114, '114', 'Wohnraum in Einzimmerwohnungen', 'Apartments', 9, 1),
       (115, '115', 'Einzelwohnräume', 'Dormitories, Single', 9, 1),
       (116, '116', 'Gruppenwohnraum', 'Dormitories, Group', 9, 1),
       (121, '121', 'Aufenthaltsräume allgemein', 'Lounges', 9, 1),
       (122, '122', 'Bereitschaftsräume', 'Ready Rooms', 9, 1),
       (123, '123', 'Kinderspielraum', 'Playrooms', 9, 1),
       (131, '131', 'Pausenraum allgemein', 'Break Rooms', 9, 1),
       (132, '132', 'Pausenhalle', 'Break Halls', 9, 1),
       (133, '133', 'Pausenfläche', 'Break Areas', 9, 1),
       (134, '134', 'Wandelhalle', 'Colonnade', 1, 1),
       (135, '135', 'Ruheräume allgemein', 'Relaxation Rooms', 6, 1),
       (136, '136', 'Patiententruheraum', 'Recovery Rooms', 6, 1),
       (141, '141', 'Warteraum allgemein', 'Waiting Rooms', 4, 1),
       (142, '142', 'Wartehalle', 'Waiting Halls', 4, 1),
       (143, '143', 'Wartefläche', 'Waiting Areas', 4, 1),
       (151, '151', 'Speiseraum allgemein', 'Dining Rooms', 6, 1),
       (152, '152', 'Speisesaal', 'Refectories', 16, 1),
       (153, '153', 'Cafeteria', 'Cafeterias', 16, 1),
       (161, '161', 'Einzelhaftraum', 'Solitary Cells', 1, 1),
       (162, '162', 'Gemeinschaftshaftraum', 'Shared Cells', 1, 1),
       (163, '163', 'Haftsprechraum', 'Prison Visitation Rooms', 2, 1),
       (164, '164', 'Besondere Hafträume', 'Specialized Prison Rooms', 6, 1),
       (211, '211', 'Büroräume allgemein', 'Offices', 3, 2),
       (212, '212', 'Schreibräume', 'Writing Rooms', 3, 2),
       (213, '213', 'Büroräume mit manuellem/experimentellem Arbeitsplatz', 'Offices of a Manual/Experimental Nature', 3, 2),
       (214, '214', 'Büroräume mit Archivfunktion', 'Archival Offices', 3, 2),
       (215, '215', 'Büroräume mit Materialausgabe', 'Issuance Offices', 3, 2),
       (216, '216', 'Einzelarbeitsplätze', 'Cubicles', 3, 2),
       (221, '221', 'Großraumbüro allgemein', 'Large Offices', 3, 2),
       (222, '222', 'Großraumbüro mit Schalter', 'Large Offices with Counters', 3, 2),
       (231, '231', 'Besprechungsräume allgemein', 'Meeting Rooms', 2, 2),
       (232, '232', 'Sprechzimmer', 'Consultation Rooms', 2, 2),
       (233, '233', 'Sitzungssäle', 'Conference Halls', 2, 2),
       (234, '234', 'Gerichtssaal', 'Court Rooms', 2, 2),
       (235, '235', 'Parlamentssaal', 'Parliament', 2, 2),
       (241, '241', 'Zeichenraum', 'Drawing Offices', 3, 2),
       (242, '242', 'Konstruktionsbüro (mit DV)', 'Construction Offices', 3, 2),
       (251, '251', 'Schalterräume allgemein', 'Counter Rooms', 6, 2),
       (252, '252', 'Kassenraum', 'Cash Register Rooms', 6, 2),
       (253, '253', 'Kartenschalter', 'Ticket Counters', 6, 2),
       (261, '261', 'Fernsprechraum/-kabine', 'Telephone Rooms', 6, 2),
       (262, '262', 'Fernsprechvermittlungsräume', 'Telephone Switching Rooms', 3, 2),
       (263, '263', 'Fernschreibräume', 'Telex Rooms', 6, 2),
       (264, '264', 'Funkzentrale', 'Communications Centers', 6, 2),
       (265, '265', 'Bedienungsraum für Förderanlagen', 'Control Rooms for Conveyance Machines', 6, 2),
       (266, '266', 'Regieraum', 'Control Rooms', 6, 2),
       (267, '267', 'Projektionsraum', 'Projection Rooms', 6, 2),
       (268, '268', 'Schaltraum für betriebstechnische Anlagen', 'Control Rooms for Utilities', 6, 2),
       (269, '269', 'Schalträume für betriebliche Einbauten', 'Control Rooms for Fixtures', 6, 2),
       (271, '271', 'Aufsichtsräume allgemein', 'Supervisory Rooms', 6, 2),
       (272, '272', 'Pförtnerräume', 'Gatehouses', 6, 2),
       (273, '273', 'Wachraum', 'Guard Rooms', 6, 2),
       (274, '274', 'Haftaufsichtsraum', 'Prison Supervision Rooms', 6, 2),
       (275, '275', 'Patientenüberwachungsräume', 'Patient Supervision Rooms', 6, 2),
       (281, '281', 'Vervielfältigungsräume', 'Replication Rooms', 6, 2),
       (282, '282', 'Filmbearbeitungsräume', 'Film Editing Rooms', 7, 2),
       (283, '283', 'ADV-Großrechneranlagenraum', 'Server Rooms', 1, 2),
       (285, '285', 'ADV-Peripheriegeräteraum', 'Peripherals Rooms', 6, 2),
       (286, '286', 'Schreibautomatenraum', 'Typewriter Rooms', 3, 2),
       (311, '311', 'Produktionshalle für Grundstoffe', 'Factory Halls for Raw Materials', 15, 3),
       (312, '312', 'Produktionshalle für Investitions- und Versorgungsgüter', 'Factory Halls for Investment Goods & Supplies', 15, 3),
       (313, '313', 'Produktionshalle für Nahrungs- und Genußmittel', 'Factory Halls for Foodstuffs & Luxury Goods', 15, 3),
       (314, '314', 'Instandsetzungs-/Wartungshalle', 'Maintenance Halls', 15, 3),
       (315, '315', 'Technologische Versuchshalle', 'Technological Test Halls', 7, 3),
       (316, '316', 'Physikalische Versuchshalle', 'Physical Test Halls', 7, 3),
       (317, '317', 'Chemie-Versuchshallen', 'Chemical Test Halls', 7, 3),
       (318, '318', 'Sonderversuchshalle', 'Special Test Halls', 7, 3),
       (321, '321', 'Metallwerkstätten (grob)', 'Workshops, Metal, Crude', 15, 3),
       (322, '322', 'Metallwerkstätten (fein)', 'Workshops, Metal, Fine', 15, 3),
       (323, '323', 'Elektrotechnikwerkstätten', 'Workshops, Electrical', 15, 3),
       (324, '324', 'Oberflächenbehandlungswerkstätten', 'Workshops, Surfacing', 15, 3),
       (325, '325', 'Holz-/Kunststoffwerkstätten', 'Workshops, Wood & Plastic', 15, 3),
       (326, '326', 'Bau-/Steine-/Erden-Werkstätten', 'Workshops, Construction & Earthen Materials', 15, 3),
       (327, '327', 'Drucktechnikwerkstatt', 'Workshops, Print', 15, 3),
       (328, '328', 'Textil-/Lederwerkstatt', 'Workshops, Leather & Textiles', 15, 3),
       (329, '329', 'Werkstätten für Gesundheits- und Körperpflege', 'Workshops, Beauty & Health', 15, 3),
       (331, '331', 'Technologisches Labor einfach (ohne Absaugung)', 'Technological Laboratories, w/o Exhaust', 7, 3),
       (332, '332', 'Technologisches Labor (mit Absaugung und/oder Explosionsschutz)', 'Technological Laboratories, w/ Exhaust or Explosion Protection', 7, 3),
       (333, '333', 'Labor für stationäre Maschinen', 'Laboratories for Stationary Machines', 7, 3),
       (334, '334', 'Lichttechnisches Labor', 'Photonics Laboratories', 7, 3),
       (335, '335', 'Schalltechnisches Labor', 'Acoustic Laboratories', 7, 3),
       (336, '336', 'Technologisches Labor mit erhöhter Deckentragfähigkeit', 'Technological Laboratories w/ Increased Ceiling Load-Bearing Capacity', 7, 3),
       (337, '337', 'Technologisches Labor mit Erschütterungsschutz', 'Technological Laboratories w/ Vibration Protection', 7, 3),
       (338, '338', 'Technologisches Labor mit Berstwänden', 'Technological Laboratories w/ Bursting Walls', 7, 3),
       (341, '341', 'Elektroniklabors (Verwendung elektronischer Bauelemente)', 'Electronics Laboratories', 7, 3),
       (342, '342', 'Physiklabors einfach', 'Physics Laboratories', 7, 3),
       (343, '343', 'Physiklabor mit besonderen RLT Anforderungen', 'Physics Laboratories w/ Special Ventilation', 7, 3),
       (344, '344', 'Physikalische Messräume und Räume für instrumentelle Analytik', 'Physical Measurement & Instrumental Analysis Rooms', 7, 3),
       (345, '345', 'Physikalische Messräume und Räume für instrumentelle Analytik m. bes. RLT-Anforderungen', 'Physical Measurement & Instrumental Analysis Rooms w/ Ventilation', 7, 3),
       (346, '346', 'Kernphysiklabor mit Dekontamination von Abwasser und Abluft', 'Nuclear Physics Laboratories w/ Waste Air & Water', 7, 3),
       (347, '347', 'Physiklabor und Messraum mit Erschütterungsschutz', 'Physics Measurement Laboratories w/ Vibration Protection', 7, 3),
       (348, '348', 'Physiklabor und Messraum mit elektromagnetischer Abschirmung', 'Physics Measurement Laboratories w/ EM-Shielding', 7, 3),
       (349, '349', 'Physiklabors und Messräume mit Strahlenschutz', 'Physics Measurement Laboratories w/ Radiation Protection', 7, 3),
       (351, '351', 'Morphologische Labors (ohne Hygieneanforderungen)', 'Morphological Laboratories', 7, 3),
       (352, '352', 'Labors für analytisch- und präparativ-chemische Arbeitsweisen', 'Chemistry Laboratories for Analytical & Preparative Techniques', 7, 3),
       (353, '353', 'Chemisch-technische Labors', 'Technical Chemistry Laboratories', 7, 3),
       (354, '354', 'Labors mit zusätzlichen Hygieneanforderungen', 'Laboratories w/ Hygiene Requirements', 7, 3),
       (355, '355', 'Labor mit zusätzlichen hygienischen und besonderen RLT-Anforderungen', 'Laboratories w/ Hygiene Requirements & Special Ventilation', 7, 3),
       (356, '356', 'Isotopenlabor mit Dekontamination von Abwasser und Abluft', 'Isotope Laboratories w/ Decontamination', 7, 3),
       (357, '357', 'Isotopenlabor mit Dekontamination von Abwasser u. Abluft u.besonderen RLT-Anforderungen', 'Isotope Laboratories w/ Decontamination & Special Ventilation', 7, 3),
       (358, '358', 'Isotopenlabors m. Dekontamin. v. Abwasser u. Abluft, hygien. u.bes. RLT-Anf. (m. Schleuse)', 'Isotope Laboratories w/ Decontamination, Lock-Access & Ventilation', 7, 3),
       (359, '359', 'Labor mit besonderen Hygieneanforderungen, Zugang über Schleuse...', 'Laboratories w/ Heightened Hygiene Requirements & Lock-Access', 7, 3),
       (361, '361', 'Raum für Stallhaltung', 'Rooms for Stabling', 7, 3),
       (362, '362', 'Raum für Käfighaltung', 'Rooms for Caging', 7, 3),
       (363, '363', 'Räume für Tierhaltung experimentell', 'Rooms for Experimental Husbandry', 7, 3),
       (364, '364', 'Räume für Käfighaltung experimentell', 'Rooms for Experimental Caging', 7, 3),
       (365, '365', 'Raum für Beckenhaltung', 'Rooms for Aquatic Husbandry', 7, 3),
       (366, '366', 'Tierpflegeräume', 'Animal Care Rooms', 7, 3),
       (367, '367', 'Futteraufbereitungsraum', 'Preparation Rooms, Feed', 7, 3),
       (368, '368', 'Milch-/Melkraum', 'Milking Rooms', 7, 3),
       (369, '369', 'Kadaverraum (mit RLT-Anforderungen)', 'Animal Morgues', 1, 3),
       (371, '371', 'Gewächshaus allgemein', 'Greenhouses', 7, 3),
       (372, '372', 'Gewächshaus mit besonderen klimatischen Bedingungen', 'Greenhouses w/ Specific Climate Conditions', 7, 3),
       (373, '373', 'Pflanzenzuchtraum experimentell', 'Plant Cultivation Rooms, Experimental', 7, 3),
       (374, '374', 'Pilzzuchtraum', 'Fungus Cultivation Rooms', 7, 3),
       (375, '375', 'Pflanzenzuchtvorbereitungsraum', 'Preparation Rooms, Plant Cultivation', 7, 3),
       (382, '382', 'Teilküche', 'Kitchenettes', 11, 3),
       (383, '383', 'Großküche', 'Kitchens, Industral', 16, 3),
       (384, '384', 'Spezialküche', 'Kitchens, Specialized', 16, 3),
       (385, '385', 'Küchenvorbereitungsraum', 'Kitchens, Food Preparation', 16, 3),
       (386, '386', 'Backraum', 'Baking Rooms', 16, 3),
       (387, '387', 'Speiseausgabe', 'Serving Rooms', 16, 3),
       (388, '388', 'Spülküche', 'Dishwashing Rooms', 16, 3),
       (391, '391', 'Hauswirtschaftsräume', 'Housekeeping Rooms', 6, 3),
       (392, '392', 'Wäschereiräume', 'Laundry Rooms', 6, 3),
       (393, '393', 'Wäschepflegeräume', 'Laundry Care Roms', 6, 3),
       (394, '394', 'Spülräume', 'Rinsing Rooms', 6, 3),
       (395, '395', 'Gerätereinigungsräume', 'Equipment Cleaning Rooms', 6, 3),
       (396, '396', 'Desinfektionsräume', 'Disinfection Rooms', 6, 3),
       (397, '397', 'Sterilisationsraum', 'Sterilization Rooms', 6, 3),
       (398, '398', 'Pflegearbeitsräume', 'Care Giving Rooms', 6, 3),
       (399, '399', 'Vorbereitungsräume', 'Preparation Rooms', 6, 3),
       (411, '411', 'Lagerraum allgemein', 'Storage Rooms', 1, 4),
       (412, '412', 'Lagerräume mit RLT-Anforderungen', 'Storage Rooms w/ Ventilation', 1, 4),
       (413, '413', 'Lagerraum mit hygienischen Anforderungen (mit Abluft)', 'Hygienic Storage Rooms w/ Ventilation', 1, 4),
       (414, '414', 'Lagerräume mit betriebsspezifischen Einbauten', 'Storage Rooms w/ Operaional Fixtures', 1, 4),
       (415, '415', 'Lagerräume mit Explosions-/Brandschutz', 'Storage Rooms w/ Fire & Explosive Protections', 1, 4),
       (416, '416', 'Lagerräume mit Strahlenschutz', 'Storage Rooms w/ Radiation Protections', 1, 4),
       (417, '417', 'Tresorraum', 'Vaults', 1, 4),
       (418, '418', 'Futtermittellager', 'Feed Storage Rooms', 1, 4),
       (419, '419', 'Leichenraum für Anatomie', 'Anatomy Morgues', 1, 4),
       (421, '421', 'Archive', 'Archives', 1, 4),
       (422, '422', 'Registratur (ohne Arbeitsplatz)', 'Registries', 1, 4),
       (423, '423', 'Sammlungsraum', 'Collection Rooms', 1, 4),
       (424, '424', 'Magazin', 'Repositories', 1, 4),
       (425, '425', 'Magazin mit Klimakonstanz', 'Repositories, Climatized', 1, 4),
       (431, '431', 'Lebensmittelkühlraum', 'Food Cold Storage Rooms', 1, 4),
       (432, '432', 'Lebensmitteltiefkühlraum', 'Food Frozen Storage Rooms', 1, 4),
       (433, '433', 'Kühlraum für medizinische Zwecke', 'Medical Cold Storage Rooms', 1, 4),
       (434, '434', 'Kühlräume für wissenschaftlich/technische Zwecke', 'Scientific Cold Storage Rooms', 1, 4),
       (435, '435', 'Leichenkühlraum', 'Morgues', 1, 4),
       (441, '441', 'Annahme- /Ausgaberäume allgemein', 'Goods Distribution Rooms', 1, 4),
       (442, '442', 'Sortierraum', 'Sorting Rooms', 1, 4),
       (443, '443', 'Packraum', 'Packing Rooms', 1, 4),
       (444, '444', 'Versandraum', 'Shipping Rooms', 1, 4),
       (445, '445', 'Versorgungsstützpunkte', 'Supply Rooms', 1, 4),
       (446, '446', 'Entsorgungsstützpunkte', 'Disposal Rooms', 1, 4),
       (451, '451', 'Verkaufsstand', 'Sales Stands', 6, 4),
       (452, '452', 'Ladenraum', 'Shops', 6, 4),
       (453, '453', 'Supermarktverkaufsraum', 'Supermarket Halls', 6, 4),
       (454, '454', 'Kaufhausverkaufsraum', 'Department Store Halls', 6, 4),
       (455, '455', 'Großmarkthallenverkaufsraum', 'Market Halls', 6, 4),
       (461, '461', 'Verkaufsausstellungsraum', 'Sales Exhibition Rooms', 6, 4),
       (462, '462', 'Musterraum', 'Example Rooms', 6, 4),
       (463, '463', 'Messehalle', 'Exhibition Halls', 6, 4),
       (511, '511', 'Hör-/Lehrsäle ansteigend mit Experimentierbühne', 'Lecture Halls w/ Stage, Ascending', 5, 5),
       (512, '512', 'Hör-/Lehrsäle eben mit Experimentierbühne', 'Lecture Rooms w/ Stage, Level', 5, 5),
       (513, '513', 'Hör-/Lehrsäle ansteigend ohne Experimentierbühne', 'Lecture Halls w/o Stage, Ascending', 5, 5),
       (514, '514', 'Hör-/Lehrsäle eben ohne Experimentierbühne', 'Lecture Rooms w/o Stage, Level', 5, 5),
       (521, '521', 'Unterrichtsraum', 'Seminar Rooms', 13, 5),
       (522, '522', 'Unterrichtsgroßräume', 'Seminar Rooms, Large', 13, 5),
       (523, '523', 'Übungsräume', 'Practice Rooms', 13, 5),
       (524, '524', 'Mehrzweckunterrichtsraum', 'Multipurpose Classrooms', 13, 5),
       (525, '525', 'Zeichenübungsraum', 'Drawing Training Rooms', 13, 5),
       (526, '526', 'Verhaltensbeobachtungsraum', 'Behavioral Observation Rooms', 13, 5),
       (527, '527', 'Übungsraum für darstellende Kunst', 'Performing Arts Training Rooms', 13, 5),
       (531, '531', 'Musisch-technische Unterrichtsräume', 'Music-Technical Classrooms', 13, 5),
       (532, '532', 'Hauswirtschaftlicher Unterrichtsraum', 'Housekeeping Classrooms', 13, 5),
       (533, '533', 'Medienunterstützter Unterrichtsraum', 'Media-Supported Classrooms', 13, 5),
       (534, '534', 'Musik-/Sprechunterrichtsraum', 'Music & Speech Classrooms', 13, 5),
       (535, '535', 'Physikalisch-technischer Übungsraum', 'Physical & Technical Classrooms', 13, 5),
       (536, '536', 'Nasspräparative Übungsräume', 'Wet Chemistry Training Rooms', 13, 5),
       (537, '537', 'Zahnmedizinischer Übungsraum', 'Dental Training Rooms', 13, 5),
       (541, '541', 'Bibliotheksraum allgemein', 'Library Offices', 6, 5),
       (542, '542', 'Leseraum', 'Reading Rooms', 6, 5),
       (543, '543', 'Freihandstellfläche', 'Stack Rooms', 6, 5),
       (544, '544', 'Katalograum/-fläche', 'Catalog Rooms', 6, 5),
       (545, '545', 'Mediothekraum', 'Media Rooms', 6, 5),
       (551, '551', 'Halle für Turnen und Spiele', 'Gymnasiums', 6, 5),
       (552, '552', 'Schwimmhalle', 'Indoor Swimming Pools', 6, 5),
       (553, '553', 'Eissporthalle', 'Ice Rinks', 6, 5),
       (554, '554', 'Radsporthalle', 'Cycling Halls', 6, 5),
       (555, '555', 'Reitsporthalle', 'Equestrian Halls', 6, 5),
       (556, '556', 'Sportübungsraum', 'Fitness Studios', 6, 5),
       (557, '557', 'Kegelbahn', 'Bowling Alleys', 6, 5),
       (558, '558', 'Schießsporträume', 'Indoor Shooting Galleries', 6, 5),
       (559, '559', 'Sondersporthalle', 'Specialized Sport Rooms', 6, 5),
       (561, '561', 'Versammlungsräume allgemein', 'Assembly Rooms', 6, 5),
       (562, '562', 'Zuschauerräume', 'Spectator Rooms', 14, 5),
       (563, '563', 'Mehrzweckhalle', 'Multipurpose Halls', 16, 5),
       (571, '571', 'Bühnenräume', 'Stages', 6, 5),
       (572, '572', 'Probebühne', 'Practice Stages', 13, 5),
       (573, '573', 'Orchesterraum', 'Orchestra Rooms', 6, 5),
       (574, '574', 'Orchesterprobenraum', 'Orchestra Practice Rooms', 13, 5),
       (575, '575', 'Tonstudioraum', 'Recording Studios', 6, 5),
       (576, '576', 'Bildstudioraum', 'Picture Studios', 6, 5),
       (577, '577', 'Künstleratelier', 'Artist Studios', 13, 5),
       (581, '581', 'Schauraum allgemein', 'Galleries', 6, 5),
       (582, '582', 'Museumsräume', 'Museum Offices', 6, 5),
       (583, '583', 'Lehr- und Schausammlungsraum', 'Exhibit Rooms', 6, 5),
       (584, '584', 'Besucherfläche', 'Visitor Areas', 6, 5),
       (591, '591', 'Gottesdienstraum', 'Religious Services Rooms', 6, 5),
       (592, '592', 'Andachtsraum', 'Prayer Rooms', 6, 5),
       (593, '593', 'Aussegnungsraum', 'Consecration Rooms', 6, 5),
       (594, '594', 'Aufbahrungsraum', 'Laying On State Rooms', 6, 5),
       (595, '595', 'Sakristei', 'Sacristies', 6, 5),
       (596, '596', 'Kreuzgang', 'Cloisters', 1, 5),
       (611, '611', 'Untersuchungs- und Behandlungs- (U + B-) Räume mit einfacher medizinischer Ausstattung', 'Examination & Treatment Rooms', 7, 6),
       (612, '612', 'Erste-Hilfe-Räume', 'First Aid Rooms', 6, 6),
       (614, '614', 'Tiermedizinischer U + B-Raum mit einfacher medizinischer Ausstattung', 'Veterinary Examination & Treatment Rooms', 7, 6),
       (615, '615', 'Demonstrationsraum mit einfacher Ausstattung', 'Medical Demonstration Rooms', 7, 6),
       (621, '621', 'Atemphysiologische U + B-Räume', 'Respiratory Examination & Treatment Rooms', 7, 6),
       (622, '622', 'Herz-und Kreislaufdiagnostische U + B-Räume', 'Cardiovascular Examination & Treatment Rooms', 7, 6),
       (623, '623', 'Neurophysiologische U + B-Räume', 'Neural Examination & Treatment Rooms', 7, 6),
       (624, '624', 'Sinnesphysiologischer U + B-Raum', 'Sensory Examination & Treatment Rooms', 7, 6),
       (625, '625', 'Augen-U + B-Raum', 'Ophthalmological Examination & Treatment Rooms', 7, 6),
       (626, '626', 'Zahnmedizinischer U + B-Raum', 'Dental Examination & Treatment Rooms', 7, 6),
       (627, '627', 'Tiermedizinischer U + B-Raum mit besonderer Ausstattung', 'Veterinary Examination & Treatment Rooms w/ Special Equipment', 7, 6),
       (628, '628', 'Demonstrationsräume mit besonderer Ausstattung', 'Medical Presentation Rooms w/ Special Equipment', 7, 6),
       (631, '631', 'Operationsräume', 'Operating Rooms', 7, 6),
       (632, '632', 'Operationsräume mit Sonderausstattung', 'Operating Rooms w/ Special Equipment', 7, 6),
       (633, '633', 'Reanimations-/Eingriffsräume', 'Procedure Rooms', 7, 6),
       (634, '634', 'Geburtshilferäume', 'Delivery Rooms', 7, 6),
       (635, '635', 'Endoskopieräume', 'Endoscopy Rooms', 7, 6),
       (636, '636', 'Operationsergänzungsräume', 'Supplemental Rooms for Operations', 7, 6),
       (637, '637', 'Tiermedizinische Operationsräume', 'Veterinary Operations Rooms', 7, 6),
       (641, '641', 'Röntgenuntersuchungsräume allgemein', 'X-Ray Examination Rooms', 7, 6),
       (642, '642', 'Spezielle Röntgenuntersuchungsräume', 'Special X-Ray Examination Rooms', 7, 6),
       (643, '643', 'Tomographieräume', 'Tomography Rooms', 7, 6),
       (644, '644', 'Zahnmedizinischer Röntgenuntersuchungsraum', 'Dental X-Ray Examination Rooms', 7, 6),
       (645, '645', 'Räume der nuklearmedizinische Diagnostik', 'Nuclear Medicine Diagnosis Rooms', 7, 6),
       (646, '646', 'Ergänzungsräume der nuklearmedizinischen Diagnostik', 'Supplemental Rooms for Nuclear Medicine Diagnosis', 7, 6),
       (647, '647', 'Ultraschalldiagnostikräume', 'Ultrasound Diagnosis Rooms', 7, 6),
       (648, '648', 'Tiermedizinische Räume für Strahlendiagnostik', 'Veterinary Radiation Diagnosis Rooms', 7, 6),
       (651, '651', 'Oberflächenbestrahlung', 'Superficial Irradiation Rooms', 7, 6),
       (652, '652', 'Halbtiefen-/Tiefenbestrahlung', 'Deep Irradiation Rooms', 7, 6),
       (653, '653', 'Bestrahlungsplanung', 'Irradiation Planning Rooms', 3, 6),
       (654, '654', 'Bestrahlung mit offenen radioaktiven Stoffen', 'Rooms for Irradiation w/ Unsealed Radioactive Substances', 7, 6),
       (655, '655', 'Bestrahlung mit umschlossenen radioaktiven Stoffen', 'Rooms for Irradiation w/ Sealed Radioactive Substances', 7, 6),
       (656, '656', 'Bestrahlung mit offenen Isotopen', 'Rooms for Irradiation w/ Unsealed Isotopes', 7, 6),
       (657, '657', 'Bestrahlung mit umschlossenen Isotopen', 'Rooms for Irradiation w/ Sealed Isotopes', 7, 6),
       (661, '661', 'Medizinische Bäder/Duschen', 'Medical Bath & Shower Rooms', 10, 6),
       (662, '662', 'Bewegungsbäder', 'Exercise Pools', 6, 6),
       (663, '663', 'Schwitzbäder/Packungen', 'Steam Rooms', 6, 6),
       (664, '664', 'Inhalationsräume', 'Inhalation Rooms', 6, 6),
       (665, '665', 'Bewegungstherapieräume', 'Motion Therapy Rooms', 6, 6),
       (666, '666', 'Massageraum', 'Massage Rooms', 6, 6),
       (667, '667', 'Elektrotherapieräume', 'Electroshock Therapy Rooms', 6, 6),
       (668, '668', 'Rehabilitationsräume', 'Rehabilitation Rooms', 6, 6),
       (671, '671', 'Normalpflegebettenraum', 'Patient Bedrooms', 6, 6),
       (672, '672', 'Infektionspflegebettenraum', 'Patient Bedrooms, Infectious', 6, 6),
       (673, '673', 'Psychiatrische Pflegebettenräume', 'Patient Bedrooms, Psychiatric Care', 6, 6),
       (674, '674', 'Neugeborenenpflegebettenraum', 'Nurseries', 6, 6),
       (675, '675', 'Säuglingspflegebettenraum', 'Patient Bedrooms, Infant Care', 6, 6),
       (676, '676', 'Kinderpflegebettenraum', 'Patient Bedrooms, Child Care', 6, 6),
       (677, '677', 'Langzeitpflegebettenraum', 'Patient Bedrooms, Long-Term Care', 6, 6),
       (678, '678', 'Leichtpflegebettenraum', 'Patient Bedrooms, Outpatient Care', 6, 6),
       (681, '681', 'Bettenraum für Intensivüberwachung', 'Patient Bed Rooms, Intensive Supervision', 6, 6),
       (682, '682', 'Bettenraum für Intensivbehandlung', 'Patient Bedrooms, Intensive Care', 6, 6),
       (683, '683', 'Bettenraum für die Behandlung Brandverletzter', 'Patient Bedrooms, Burn Victim Care', 6, 6),
       (684, '684', 'Bettenräume für Dialyse', 'Patient Bedrooms, Dialysis', 6, 6),
       (685, '685', 'Bettenraum für Reverse Isolation', 'Patient Bedrooms, Reverse Isolation', 6, 6),
       (686, '686', 'Bettenraum für die Pflege Frühgeborener', 'Patient Bedrooms, Premature Infant Care', 6, 6),
       (687, '687', 'Bettenräume für die Pflege von Strahlenpatienten', 'Patient Bedrooms, Radiation Therapy', 6, 6),
       (688, '688', 'Bettenraum für die Pflege Querschnittgelähmter', 'Patient Bedrooms, Paraplegic Care', 6, 6),
       (689, '689', 'Aufwachräume (postoperativ)', 'Recovery Rooms, Post-Operative', 6, 6),
       (711, '711', 'Toiletten', 'Restrooms', 10, 7),
       (712, '712', 'Waschräume', 'Washrooms', 10, 7),
       (713, '713', 'Duschräume', 'Shower Rooms', 10, 7),
       (714, '714', 'Baderäume', 'Bathing Rooms', 10, 7),
       (715, '715', 'Sauna (Kabine)', 'Saunas', 10, 7),
       (717, '717', 'Wickelräume', 'Baby Changing Rooms', 10, 7),
       (718, '718', 'Schminkräume', 'Makeup Rooms', 10, 7),
       (719, '719', 'Putzräume', 'Cleaning Closets', 1, 7),
       (721, '721', 'Einzelumkleideräume', 'Dressing Rooms, Individual', 8, 7),
       (722, '722', 'Gruppenumkleideräume', 'Dressing Rooms, Group', 8, 7),
       (723, '723', 'Umkleideschleusen', 'Dressing Passages', 8, 7),
       (724, '724', 'Künstlergarderoben', 'Artist Wardrobes', 8, 7),
       (725, '725', 'Garderobenflächen', 'Wardrobe Areas', 8, 7),
       (726, '726', 'Schrankräume', 'Locker Rooms', 8, 7),
       (731, '731', 'Abstellräume allgemein', 'Storerooms', 1, 7),
       (732, '732', 'Kellerabstellräume', 'Basement Storerooms', 1, 7),
       (733, '733', 'Dachabstellraum', 'Attic Storerooms', 1, 7),
       (734, '734', 'Fahrrad-/Kinderwagenraum', 'Bike / Stroller Rooms', 1, 7),
       (735, '735', 'Krankentransportgeräteraum', 'Patient Transport Device Rooms', 1, 7),
       (736, '736', 'Gütertransportgeräteräume', 'Material Transport Device Rooms', 1, 7),
       (737, '737', 'Müllsammelräume', 'Trash Collection Rooms', 1, 7),
       (741, '741', 'Kraftfahrzeugabstellflächen allgemein', 'Parking Spaces', 1, 7),
       (742, '742', 'Großkraftfahrzeugabstellfläche', 'Parking Spaces, Large Vehicle', 1, 7),
       (743, '743', 'Großgeräteabstellfläche', 'Parking Spaces, Large Equipment', 1, 7),
       (744, '744', 'Kettenfahrzeugabstellfläche', 'Parking Spaces, Tracked Vehicles', 1, 7),
       (745, '745', 'Schienenfahrzeugabstellfläche', 'Parking Spaces, Rail Vehicles', 1, 7),
       (746, '746', 'Luftfahrzeugabstellfläche', 'Parking Spaces, Aircraft', 1, 7),
       (747, '747', 'Wasserfahrzeugabstellfläche', 'Parking Spaces, Aquatic', 1, 7),
       (751, '751', 'Bahnsteig', 'Train Platforms', 1, 7),
       (753, '753', 'Flugsteig', 'Airway Gates', 1, 7),
       (754, '754', 'Landesteg', 'Jetties', 1, 7),
       (761, '761', 'Raum für Abwasseraufbereitung und -Beseitigung', 'Service Provider Rooms, Waste Water Preparation & Disposal', 1, 7),
       (762, '762', 'Raum für Wasserversorgung', 'Service Provider Rooms, Water Supply', 1, 7),
       (763, '763', 'Raum für Wärmeversorgung', 'Service Provider Rooms, Heating', 1, 7),
       (764, '764', 'Raum für Versorgung mit Gasen und Flüssigkeiten', 'Service Provider Rooms, Fluids & Gases', 1, 7),
       (765, '765', 'Raum für Stromversorgung', 'Service Provider Rooms, Power', 1, 7),
       (766, '766', 'Raum für Fernmeldetechnik', 'Service Provider Rooms, Communications', 1, 7),
       (767, '767', 'Raum für Luft-/Kälteversorgung', 'Service Provider Rooms, Air Conditioning & Ventilation', 1, 7),
       (768, '768', 'Raum für Förderanlagen', 'Service Provider Rooms, Conveyance', 1, 7),
       (769, '769', 'Raum für sonstige Ver- und Entsorgung', 'Service Provider Rooms, Other', 1, 7),
       (771, '771', 'Luftschutzraum', 'Air Raid Shelter', 1, 7),
       (772, '772', 'Strahlenschutzraum', 'Nuclear Bunker', 1, 7),
       (810, '810', 'Abwasseraufbereitung und -beseitigung', 'Wastewater Preparation & Disposal Rooms', 1, 8),
       (820, '820', 'Wasserversorgung', 'Water Supply Rooms', 1, 8),
       (830, '830', 'Heizung und Brauchwassererwärmung', 'Heating Rooms', 1, 8),
       (840, '840', 'Gase und Flüssigkeiten (außer für Heizzwecke)', 'Fluids & Gases Service Rooms', 1, 8),
       (850, '850', 'Elektrische Stromversorgung', 'Power Supply Rooms', 1, 8),
       (860, '860', 'Fernmeldetechnik', 'Communications Equipment Rooms', 1, 8),
       (870, '870', 'Raumlufttechnische Anlagen', 'Ventilation Rooms', 1, 8),
       (880, '880', 'Aufzugs- und Förderanlagen', 'Elevator & Conveyance Equipment Rooms', 1, 8),
       (891, '891', 'Hausanschlussraum', 'House Connection Rooms', 1, 8),
       (892, '892', 'Installationsraum', 'Installation Rooms', 1, 8),
       (893, '893', 'Installationsschacht', 'Installation Shafts', 1, 8),
       (894, '894', 'Installationskanal', 'Installation Channels', 1, 8),
       (895, '895', 'Abfallverbrennungsraum', 'Waste Incineration Rooms', 1, 8),
       (911, '911', 'Flur allgemein', 'Hallways', 14, 9),
       (913, '913', 'Vorraum (vor Hotel-, Krankenzimmer)', 'Anterooms (in Hospital & Hotel Rooms)', 4, 9),
       (914, '914', 'Schleuse (Garagen-, Hörsaal-, Luftdruck-)', 'Access Rooms', 14, 9),
       (915, '915', 'Windfang', 'Vestibules', 4, 9),
       (916, '916', 'Eingangshalle', 'Entryways', 4, 9),
       (917, '917', 'Rollsteig', 'Moving Walkways', 1, 9),
       (918, '918', 'Fluchtweg (Fluchtbalkon, -tunnel, Wartungsbalkon)', 'Escape Corridors', 6, 9),
       (921, '921', 'Treppenraum, -lauf, Rampe', 'Stairwells', 12, 9),
       (922, '922', 'Treppe in Wohnungen', 'Residential Stairwells', 12, 9),
       (923, '923', 'Rolltreppe, -rampe', 'Escalators', 1, 9),
       (924, '924', 'Fluchttreppenraum', 'Escape Stairwells', 6, 9),
       (931, '931', 'Schacht für Personenaufzug', 'Elevator Shafts, Personnel', 1, 9),
       (932, '932', 'Schacht für Materialförderungsanlagen', 'Elevator Shafts, Freight', 1, 9),
       (933, '933', 'Tunnel für Materialförderanlagen', 'Freight Tunnel', 1, 9),
       (934, '934', 'Abwurfschacht', 'Chute', 1, 9),
       (941, '941', 'Fahrzeugverkehrsfläche horizontal', 'Vehicle Traffic Area, Level', 1, 9),
       (942, '942', 'Fahrzeugverkehrsfläche geneigt (Rampe)', 'Vehicle Traffic Area, Inclined', 1, 9);

CREATE TABLE IF NOT EXISTS `#__organizer_rooms` (
    `id`          INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT,
    `alias`       VARCHAR(255)                   DEFAULT NULL,
    `code`        VARCHAR(60)           NOT NULL COLLATE utf8mb4_bin,
    `name`        VARCHAR(150)          NOT NULL,
    `active`      TINYINT(1) UNSIGNED   NOT NULL DEFAULT 1,
    `area`        DOUBLE(6, 2) UNSIGNED NOT NULL DEFAULT 0.00,
    `buildingID`  INT(11) UNSIGNED               DEFAULT NULL,
    `maxCapacity` INT(4) UNSIGNED       NOT NULL DEFAULT 0,
    `flooringID`  SMALLINT(3) UNSIGNED           DEFAULT 1,
    `effCapacity` INT(4) UNSIGNED       NOT NULL DEFAULT 0,
    `roomtypeID`  INT(11) UNSIGNED               DEFAULT NULL,
    `virtual`     TINYINT(1) UNSIGNED   NOT NULL DEFAULT 0,
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
    `code`           VARCHAR(60)         NOT NULL COLLATE utf8mb4_bin,
    `name_de`        VARCHAR(150)        NOT NULL,
    `name_en`        VARCHAR(150)        NOT NULL,
    `description_de` TEXT,
    `description_en` TEXT,
    `capacity`       INT(4) UNSIGNED              DEFAULT NULL,
    `suppress`       TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `surfaceID`      INT(5) UNSIGNED     NOT NULL DEFAULT 1000,
    PRIMARY KEY (`id`),
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
    `id`                          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias`                       VARCHAR(255)              DEFAULT NULL,
    `code`                        VARCHAR(60)               DEFAULT NULL,
    `fullName_de`                 VARCHAR(200)     NOT NULL,
    `fullName_en`                 VARCHAR(200)     NOT NULL,
    `lsfID`                       INT(11) UNSIGNED          DEFAULT NULL,
    `abbreviation_de`             VARCHAR(25)      NOT NULL DEFAULT '',
    `abbreviation_en`             VARCHAR(25)      NOT NULL DEFAULT '',
    `bonusPoints`                 TINYINT(1) UNSIGNED       DEFAULT 0,
    `content_de`                  TEXT,
    `content_en`                  TEXT,
    `creditPoints`                INT(3) UNSIGNED  NOT NULL DEFAULT 0,
    `description_de`              TEXT,
    `description_en`              TEXT,
    `duration`                    TINYINT(1) UNSIGNED       DEFAULT 1,
    `expenditure`                 INT(4) UNSIGNED  NOT NULL DEFAULT 0,
    `expertise`                   TINYINT(1) UNSIGNED       DEFAULT NULL,
    `expertise_de`                TEXT,
    `expertise_en`                TEXT,
    `fieldID`                     INT(11) UNSIGNED          DEFAULT NULL,
    `frequencyID`                 INT(1) UNSIGNED           DEFAULT NULL,
    `independent`                 INT(4) UNSIGNED  NOT NULL DEFAULT 0,
    `language`                    VARCHAR(2)       NOT NULL DEFAULT 'D',
    `literature`                  TEXT,
    `method_de`                   TEXT,
    `method_en`                   TEXT,
    `methodCompetence`            TINYINT(1) UNSIGNED       DEFAULT NULL,
    `methodCompetence_de`         TEXT,
    `methodCompetence_en`         TEXT,
    `objective_de`                TEXT,
    `objective_en`                TEXT,
    `preliminaryWork_de`          TEXT,
    `preliminaryWork_en`          TEXT,
    `prerequisites_de`            TEXT,
    `prerequisites_en`            TEXT,
    `present`                     INT(4) UNSIGNED  NOT NULL DEFAULT 0,
    `proof_de`                    TEXT,
    `proof_en`                    TEXT,
    `recommendedPrerequisites_de` TEXT,
    `recommendedPrerequisites_en` TEXT,
    `selfCompetence`              TINYINT(1) UNSIGNED       DEFAULT NULL,
    `selfCompetence_de`           TEXT,
    `selfCompetence_en`           TEXT,
    `socialCompetence`            TINYINT(1) UNSIGNED       DEFAULT NULL,
    `socialCompetence_de`         TEXT,
    `socialCompetence_en`         TEXT,
    `sws`                         INT(2) UNSIGNED  NOT NULL DEFAULT 0,
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

CREATE TABLE IF NOT EXISTS `#__organizer_surfaces` (
    `id`      INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`    VARCHAR(3)      NOT NULL,
    `name_de` VARCHAR(255)    NOT NULL,
    `name_en` VARCHAR(255)    NOT NULL,
    `typeID`  INT(2) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `typeID` (`typeID`),
    UNIQUE KEY `code` (`code`)
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

CREATE TABLE IF NOT EXISTS `#__organizer_use_groups` (
    `id`      TINYINT(1) UNSIGNED NOT NULL,
    `name_de` VARCHAR(150)        NOT NULL,
    `name_en` VARCHAR(150)        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT INTO `#__organizer_use_groups` (`id`, `name_de`, `name_en`)
VALUES (0, 'Flächen in nicht allseits umschlossenen Räumen', 'Outdoor and partially exposed areas'),
       (1, 'Wohnen und Aufenthalt', 'Residential and Social'),
       (2, 'Büroarbeit', 'Office Work'),
       (3, 'Produktion, Hand- und Maschinenarbeit, Experimente', 'Production, Manual or Machine Labor, Experiments'),
       (4, 'Lagern, Verteilen, Verkaufen', 'Storage, Distribution, Sales'),
       (5, 'Bildung, Unterricht, Kultur', 'Education and Culture'),
       (6, 'Heilen und Pflegen', 'Medical and Caregiving'),
       (7, 'Sonstige Nutzungen', 'Other Usages'),
       (8, 'Betriebstechnische Anlagen', 'Building Utilities'),
       (9, 'Verkehrserschließung und -sicherung', 'Conveyance');

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

ALTER TABLE `#__organizer_room_equipment`
    ADD CONSTRAINT `room_equipment_equipmentID_fk` FOREIGN KEY (`equipmentID`) REFERENCES `#__organizer_equipment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `room_equipment_roomID_fk` FOREIGN KEY (`roomID`) REFERENCES `#__organizer_rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_roomkeys`
    ADD CONSTRAINT `roomkey_cleaningID_fk` FOREIGN KEY (`cleaningID`) REFERENCES `#__organizer_cleaning_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `roomkey_useID_fk` FOREIGN KEY (`useID`) REFERENCES `#__organizer_use_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `#__organizer_rooms`
    ADD CONSTRAINT `room_buildingID_fk` FOREIGN KEY (`buildingID`) REFERENCES `#__organizer_buildings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `room_flooringID_fk` FOREIGN KEY (`flooringID`) REFERENCES `#__organizer_flooring` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `room_roomtypeID_fk` FOREIGN KEY (`roomtypeID`) REFERENCES `#__organizer_roomtypes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `#__organizer_roomtypes`
    ADD CONSTRAINT `roomtype_surfaceID_fk` FOREIGN KEY (`surfaceID`) REFERENCES `#__organizer_surfaces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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