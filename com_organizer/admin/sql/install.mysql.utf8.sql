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
    `allowScheduling` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
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

#Normed
INSERT INTO `#__organizer_roomkeys` (`id`, `key`, `name_de`, `name_en`, `cleaningID`, `useID`)
VALUES (11, '011', 'Wohnflächen im Freien', 'Outdoor Residential Areas', 1, 0),
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
       (716, '716', 'Reinigungsnassschleusen', 'Sanitization Locks', 10, 7),
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
    `id`             INT(11) UNSIGNED     NOT NULL AUTO_INCREMENT,
    `name_de`        VARCHAR(150)         NOT NULL,
    `name_en`        VARCHAR(150)         NOT NULL,
    `usecode`        SMALLINT(4) UNSIGNED NOT NULL,
    `description_de` TEXT,
    `description_en` TEXT,
    `capacity`       INT(4) UNSIGNED               DEFAULT NULL,
    `suppress`       TINYINT(1) UNSIGNED  NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
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

CREATE TABLE IF NOT EXISTS `#__organizer_use_codes` (
    `id`      SMALLINT(4) UNSIGNED NOT NULL,
    `code`    VARCHAR(4)           NOT NULL,
    `name_de` VARCHAR(150)         NOT NULL,
    `name_en` VARCHAR(150)         NOT NULL,
    `keyID`   SMALLINT(3) UNSIGNED NOT NULL,
    `cat6`    SMALLINT(3) UNSIGNED NOT NULL,
    `cat12`   SMALLINT(3) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

#Normed
INSERT INTO `#__organizer_use_codes` (`id`, `code`, `name_de`, `name_en`, `keyID`, `cat6`, `cat12`)
VALUES (110, '0110', 'Wohnfläche im Freien', 'Outdoor Residential Area', 11, 0, 0),
       (120, '0120', 'Gemeinschaftsfläche im Freien', 'Outdoor Socialization Area', 12, 0, 0),
       (130, '0130', 'Pausenfläche im Freien', 'Outdoor Break Area', 13, 0, 0),
       (140, '0140', 'Wartefläche im Freien', 'Outdoor Waiting Area', 14, 0, 0),
       (150, '0150', 'Speisefläche im Freien', 'Outdoor Dining Area', 15, 0, 0),
       (310, '0310', 'Produktions- / Prüfläche im Freien', 'Outdoor Production or Testing Area', 31, 0, 0),
       (320, '0320', 'Werkstätte im Freien', 'Outdoor Workshop', 31, 0, 0),
       (330, '0330', 'Technologische Experimentierfläche im Freien', 'Outdoor Technical Research Area', 33, 0, 0),
       (340, '0340', 'Physikalische, physikalisch-technische, elektrotechnische Experimentierfläche im Freien', 'Outdoor Physical & Physical-Technical Research Area', 34, 0, 0),
       (360, '0360', 'Tierhaltungsfläche im Freien', 'Outdoor Husbandry Area', 36, 0, 0),
       (370, '0370', 'Pflanzenzuchtfläche im Freien', 'Outdoor Botany Area', 37, 0, 0),
       (380, '0380', 'Küche im Freien', 'Outdoor Cooking Area', 38, 0, 0),
       (390, '0390', 'Sonderarbeitsfläche im Freien', 'Outdoor Special Production Area', 39, 0, 0),
       (410, '0410', 'Lagerfläche im Freien', 'Outdoor Storage Space', 41, 0, 0),
       (440, '0440', 'Annahme- und Ausgabefläche im Freien', 'Outdoor Goods Distribution Area', 44, 0, 0),
       (450, '0450', 'Verkaufsfläche im Freien', 'Outdoor Retail Area', 45, 0, 0),
       (460, '0460', 'Ausstellungsfläche im Freien', 'Outdoor Retail Display Area', 46, 0, 0),
       (510, '0510', 'Hörfläche im Freien', 'Outdoor Lecture Area', 51, 0, 0),
       (520, '0520', 'Unterrichtsfläche im Freien', 'Outdoor Lesson Area', 52, 0, 0),
       (530, '0530', 'Übungsfläche im Freien', 'Outdoor Practice Area', 53, 0, 0),
       (540, '0540', 'Bibliotheksfläche im Freien', 'Outdoor Library Area', 54, 0, 0),
       (550, '0550', 'Sportfläche im Freien', 'Outdoor Sports Area', 55, 0, 0),
       (560, '0560', 'Versammlungsfläche im Freien', 'Outdoor Assembly Area', 56, 0, 0),
       (570, '0570', 'Bühnen- und Studiofläche im Freien', 'Outdoor Event Area', 57, 0, 0),
       (660, '0660', 'Freifläche für Physiotherapie und Rehabilitation', 'Outdoor Therapy Area', 66, 0, 0),
       (740, '0740', 'Fahrzeugabstellfläche im Freien', 'Outdoor Parking Area', 74, 0, 0),
       (910, '0910', 'Flur, Halle im Freien', 'Outdoor Walkway', 91, 0, 0),
       (920, '0920', 'Treppen im Freien', 'Outdoor Stairway', 92, 0, 0),
       (940, '0940', 'Fahrzeugverkehrsfläche im Freien', 'Outdoor Traffic Area', 94, 0, 0),
       (1111, '1111', 'Wohnraum', 'Residential Room', 111, 1, 2),
       (1112, '1112', 'Wohnraum mit besonderen Anforderungen', 'Residential Room, Special Requirements', 111, 2, 3),
       (1120, '1120', 'Wohnküche', 'Kitschen, Residential', 112, 2, 3),
       (1130, '1130', 'Wohndiele', 'Hallway, Residential', 113, 1, 2),
       (1140, '1140', 'Wohnraum in Einzimmerwohnungen', 'Single-Room Apartment', 114, 1, 2),
       (1151, '1151', 'Einzelwohnraum', 'Dormitory, Single', 115, 1, 2),
       (1152, '1152', 'Einzelwohnraum mit besonderen Anforderungen', 'Dormitory, Single, Special Requirements', 115, 2, 3),
       (1160, '1160', 'Gruppenwohnraum', 'Dormitory, Group', 116, 1, 2),
       (1211, '1211', 'Aufenthaltsraum', 'Lounge', 121, 2, 3),
       (1212, '1212', 'Aufenthaltsraum mit Teeküche', 'Lounge w/ Kitchenette', 121, 2, 4),
       (1213, '1213', 'Aufenthaltsraum mit Teeküche und RLT-Anforderungen', 'Lounge w/ Kitchenette, Ventilation', 121, 3, 5),
       (1214, '1214', 'Aufenthaltsraum mit Teeküche und besonderen RLT-Anforderungen', 'Lounge w/ Kitchenette & Special Ventilation', 121, 4, 6),
       (1221, '1221', 'Bereitschaftsraum', 'Ready Room', 122, 2, 3),
       (1222, '1222', 'Bereitschaftsraum mit Waschtisch und RLT-Anforderungen', 'Ready Room w/ Ventilation', 122, 3, 5),
       (1230, '1230', 'Kinderspielraum', 'Playroom', 123, 2, 3),
       (1310, '1310', 'Pausenraum allgemein', 'Break Room', 131, 1, 2),
       (1320, '1320', 'Pausenhalle', 'Break Hall', 132, 1, 2),
       (1330, '1330', 'Pausenfläche', 'Break Area', 133, 1, 2),
       (1340, '1340', 'Wandelhalle', 'Colonnade', 134, 2, 3),
       (1351, '1351', 'Ruheraum', 'Relaxation Room', 135, 2, 3),
       (1352, '1352', 'Ruheraum mit Waschtisch', 'Relaxation Room w/ Wash Table', 135, 2, 4),
       (1360, '1360', 'Patiententruheraum', 'Recovery Room', 136, 3, 5),
       (1410, '1410', 'Warteraum allgemein', 'Waiting Room', 141, 2, 3),
       (1420, '1420', 'Wartehalle', 'Waiting Hall', 142, 2, 3),
       (1430, '1430', 'Wartefläche', 'Waiting Area', 143, 2, 3),
       (1510, '1510', 'Speiseraum allgemein', 'Dining Room', 151, 2, 3),
       (1520, '1520', 'Speisesaal', 'Refectory', 152, 3, 5),
       (1530, '1530', 'Cafeteria', 'Cafeteria', 153, 3, 5),
       (1610, '1610', 'Einzelhaftraum', 'Prison Cell, Individual', 161, 2, 3),
       (1620, '1620', 'Gemeinschaftshaftraum', 'Prison Cell, Shared', 162, 2, 3),
       (1630, '1630', 'Haftsprechraum', 'Prison Visitation Room', 163, 2, 3),
       (1641, '1641', 'Verwahrraum', 'Holding Room', 164, 3, 5),
       (1642, '1642', 'Ausnüchterungszelle', 'Drunk Tank', 164, 4, 6),
       (2112, '2112', 'Büroraum mit DV', 'Office', 211, 2, 4),
       (2113, '2113', 'Büroraum mit DV und RLT-Anforderungen', 'Office w/ Ventilation', 211, 3, 5),
       (2121, '2121', 'Schreibdienst', 'Writing Room', 212, 2, 4),
       (2122, '2122', 'Sekretariat', 'Office, Administrative', 212, 2, 4),
       (2131, '2131', 'Büroraum mit manuellem/experimentellem Arbeitsplatz', 'Office, Technical', 213, 3, 5),
       (2132, '2132', 'Büroraum mit manuellem/experimentellem Arbeitsplatz mit RLT-Anforderungen', 'Office, Technical w/ Ventilation', 213, 4, 6),
       (2142, '2142', 'Büroraum mit Archivfunktion mit DV', 'Office, Archival', 214, 2, 4),
       (2152, '2152', 'Büroraum mit Materialausgabe mit DV', 'Office, Issuance', 215, 2, 4),
       (2162, '2162', 'Einzelarbeitsplatz mit DV', 'Workplace, Individual', 216, 2, 4),
       (2163, '2163', 'Einzelarbeitsplatz mit DV und RTL-Anforderungen', 'Workplace, Individual w/ Ventilation', 216, 3, 5),
       (2211, '2211', 'Großraumbüro', 'Office, Large', 221, 2, 4),
       (2212, '2212', 'Großraumbüro mit RLT-Anforderungen', 'Office, Large w/ Ventilation', 221, 3, 5),
       (2220, '2220', 'Großraumbüro mit Schalter', 'Office, Large w/ Counter', 222, 3, 5),
       (2312, '2312', 'Besprechungsraum mit DV', 'Meeting Room w/ Media', 231, 2, 4),
       (2313, '2313', 'Besprechungsraum mit DV und RLT-Anforderungen', 'Meeting Room w/ Media & Ventilation', 231, 3, 5),
       (2320, '2320', 'Sprechzimmer', 'Office, Consultation', 232, 2, 4),
       (2331, '2331', 'Konferenzraum mit DV', 'Conference Room', 233, 2, 4),
       (2332, '2332', 'Konferenzraum mit DV und besonderer Ausstattung', 'Conference Room w/ Special Equipment', 233, 3, 5),
       (2340, '2340', 'Gerichtssaal', 'Court', 234, 4, 6),
       (2350, '2350', 'Parlamentssaal', 'Parliament', 235, 5, 7),
       (2410, '2410', 'Zeichenraum', 'Office, Drawing', 241, 2, 3),
       (2420, '2420', 'Konstruktionsbüro (mit DV)', 'Office, Construction', 242, 2, 4),
       (2511, '2511', 'Schalterraum', 'Counter Room', 251, 2, 4),
       (2512, '2512', 'Leitstelle Polizeirevier', 'Control Center, Police Station', 251, 3, 5),
       (2513, '2513', 'Leitstelle mit hygienischen Anforderungen', 'Control Center w/ Hygiene', 251, 3, 5),
       (2514, '2514', 'Leitstelle mit hygienischen und RLT-Anforderungen', 'Control Center w/ Hygiene & Ventilation Requirements', 251, 4, 6),
       (2520, '2520', 'Kassenraum', 'Cash Register Room', 252, 3, 5),
       (2530, '2530', 'Kartenschalter', 'Ticket Counter', 253, 3, 5),
       (2610, '2610', 'Fernsprechraum/-kabine', 'Telephone Room', 261, 3, 5),
       (2621, '2621', 'Fernsprechvermittlungsraum', 'Telephone Switching Room', 262, 2, 4),
       (2622, '2622', 'Fernsprechvermittlungsraum mit RLT-Anforderungen', 'Telephone Switching Room w/ Ventilation', 262, 4, 6),
       (2631, '2631', 'Fernschreibraum', 'Telex Room', 263, 2, 4),
       (2632, '2632', 'Fernschreibraum mit Sicherheitsanforderungen', 'Telex Room w/ Security', 263, 3, 5),
       (2640, '2640', 'Funkzentrale', 'Communications Center', 264, 4, 6),
       (2650, '2650', 'Bedienungsraum für Förderanlagen', 'Control Room, Conveyance Equipment', 265, 3, 5),
       (2660, '2660', 'Regieraum', 'Control Room', 266, 4, 6),
       (2670, '2670', 'Projektionsraum', 'Projection Room', 267, 4, 6),
       (2680, '2680', 'Schaltraum für betriebstechnische Anlagen', 'Control Room, Operational Equipment', 268, 5, 7),
       (2691, '2691', 'Schaltraum', 'Control Room, Equipment', 269, 2, 3),
       (2692, '2692', 'Schaltraum mit besonderen Anforderungen', 'Control Room, Specific Requirements', 269, 3, 5),
       (2693, '2693', 'Schaltraum Radiologie', 'Control Room, Radiological', 269, 4, 6),
       (2694, '2694', 'Schaltraum Röntgen mit Filmentwicklung', 'Control Room, X-Ray Equipment', 269, 4, 6),
       (2695, '2695', 'Schaltraum OP', 'Control Room, Surgical', 269, 5, 7),
       (2711, '2711', 'Aufsichtsraum', 'Supervision Room', 271, 2, 3),
       (2712, '2712', 'Aufsichtsraum mit DV und Überfallmeldeanlage', 'Office, Security', 271, 2, 4),
       (2721, '2721', 'Pförtnerraum', 'Gatehouse', 272, 2, 3),
       (2722, '2722', 'Pförtnerraum mit überwachungstechnischen Anlagen', 'Gatehouse w/ Security Systems', 272, 3, 5),
       (2730, '2730', 'Wachraum', 'Guard Rooms', 273, 2, 3),
       (2740, '2740', 'Haftaufsichtsraum', 'Prison Supervision Room', 274, 2, 3),
       (2751, '2751', 'Patientüberwachungsraum', 'Patient Supervision Room', 275, 3, 5),
       (2752, '2752', 'Patientüberwachungsraum mit besonderen Anforderungen', 'Patient Supervision Room w/ Specific Requirements', 275, 4, 6),
       (2811, '2811', 'Fotokopierraum', 'Copy Room', 281, 2, 3),
       (2812, '2812', 'Lichtpausraum', 'Blueprint Room', 281, 3, 5),
       (2813, '2813', 'Fotolithografieraum', 'Photolithography Room', 281, 4, 6),
       (2821, '2821', 'Filmbearbeitung / Schneideraum', 'Film Editing Room', 282, 3, 5),
       (2823, '2823', 'Dunkelkammer', 'Dark Room', 282, 3, 5),
       (2830, '2830', 'ADV-Großrechneranlagenraum', 'Server Room', 283, 5, 7),
       (2850, '2850', 'ADV-Peripheriegeräteraum', 'Periphery Room', 285, 4, 6),
       (3110, '3110', 'Produktionshalle für Grundstoffe', 'Factory Hall, Raw Materials', 311, 1, 2),
       (3120, '3120', 'Produktionshalle für Investitions- und Versorgungsgüter', 'Factory Hall, Investment Goods & Supplies', 312, 1, 2),
       (3130, '3130', 'Produktionshalle für Nahrungs- und Genußmittel', 'Factory Hall, Foodstuffs & Luxury Goods', 313, 2, 3),
       (3140, '3140', 'Instandsetzungs-/Wartungshalle', 'Maintenance Hall', 314, 2, 3),
       (3150, '3150', 'Technologische Versuchshalle', 'Test Hall, Technological', 315, 2, 3),
       (3160, '3160', 'Physikalische Versuchshalle', 'Test Hall, Physical', 316, 3, 5),
       (3171, '3171', 'Halle für chemische Versuche', 'Test Hall, Chemical', 317, 3, 5),
       (3172, '3172', 'Halle für chemische Versuche mit speziellen Einrichtungen', 'Test Hall, Chemical, Special Equipment', 317, 4, 6),
       (3180, '3180', 'Sonderversuchshalle', 'Test Hall, Other', 318, 0, 0),
       (3211, '3211', 'Hausmeisterwerkstatt', 'Workshop, Building Maintenance', 321, 1, 2),
       (3212, '3212', 'Blechbearbeitung, Montage-, Stahlbau', 'Workshop, Metal, Processed', 321, 3, 5),
       (3213, '3213', 'Schlosserei, Härterei, Schmiede', 'Workshop, Blacksmith', 321, 3, 5),
       (3214, '3214', 'Kfz-Werkstatt', 'Workshop, Automotive', 321, 4, 6),
       (3215, '3215', 'Kfz-Waschhalle', 'Car Wash', 321, 4, 6),
       (3216, '3216', 'Gießerei, Schweißerei', 'Workshop, Casting & Welding', 321, 4, 6),
       (3217, '3217', 'Prüfstand', 'Test Hall', 321, 5, 7),
       (3221, '3221', 'Werkstatt Metall (fein)', 'Workshop, Fine Mechanic', 322, 2, 3),
       (3222, '3222', 'Werkstatt Metall (fein) mit fest eingebauten Einrichtungen', 'Workshop, Fine Mechanic w/ Integral Equipment', 322, 2, 4),
       (3231, '3231', 'Werkstatt Elektrotechnik', 'Workshop, Electronic', 323, 2, 3),
       (3232, '3232', 'Werkstatt Elektrotechnik mit fest eingebauten Einrichtungen', 'Workshop, Electronic w/ Integral Equipment', 323, 4, 6),
       (3242, '3242', 'Werkstatt Oberflächenbehandlung mit RLT-Anforderungen', 'Workshop, Surfacing w/ Ventilation', 324, 5, 7),
       (3251, '3251', 'Werkstatt Holz/Kunststoff', 'Workshop, Plastic & Wood', 325, 1, 2),
       (3252, '3252', 'Werkstatt Holz/Kunststoff mit fest eingebauten Einrichtungen', 'Workshop, Plastic & Wood w/ Integral Equipment', 325, 3, 5),
       (3261, '3261', 'Werkstatt Bau/Steine/Erden', 'Workshop, Construction Materials', 326, 1, 2),
       (3262, '3262', 'Werkstatt Bau/Steine/Erden mit Medienversorgung', 'Workshop, Construction Materials w/ Media Support', 326, 2, 4),
       (3263, '3263', 'Werkstatt Bau/Steine/Erden mit Medienversorgung und RLT-Anfordung', 'Workshop, Construction Materials w/ Media Support & Ventilation', 326, 3, 5),
       (3270, '3270', 'Drucktechnikwerkstatt', 'Workshop, Print', 327, 2, 3),
       (3280, '3280', 'Textil-/Lederwerkstatt', 'Workshop, Leather & Textiles', 328, 2, 3),
       (3291, '3291', 'Frisör-/Kosmetikarbeitsraum', 'Beauty Salon', 329, 2, 4),
       (3292, '3292', 'Prothetische/Dental-Werkstatt', 'Workshop, Dental & Prosthetics', 329, 3, 5),
       (3310, '3310', 'Technologisches Labor einfach (ohne Absaugung)', 'Laboratory, Technological', 331, 3, 5),
       (3320, '3320', 'Technologisches Labor (mit Absaugung und/oder Explosionsschutz)', 'Laboratory, Technological w/ Explosion Protection / Ventilation', 332, 5, 7),
       (3330, '3330', 'Labor für stationäre Maschinen', 'Laboratory, Immobile Machines', 333, 4, 6),
       (3340, '3340', 'Lichttechnisches Labor', 'Laboratory, Photonics', 334, 3, 5),
       (3350, '3350', 'Schalltechnisches Labor', 'Laboratory, Acoustics', 335, 3, 5),
       (3360, '3360', 'Technologisches Labor mit erhöhter Deckentragfähigkeit', 'Laboratory, Technological w/ Reinforced Ceiling', 336, 4, 6),
       (3370, '3370', 'Technologisches Labor mit Erschütterungsschutz', 'Laboratory, Technological w/ Vibration Protection', 337, 5, 7),
       (3380, '3380', 'Technologisches Labor mit Berstwänden', 'Laboratory, Technological w/ Burst Walls', 338, 5, 7),
       (3411, '3411', 'Elektroniklabor', 'Laboratory, Electronics', 341, 4, 6),
       (3412, '3412', 'Elektroniklabor mit RLT-Anforderungen und Heliumversorgung', 'Laboratory, Electronics w/ Helium Supply & Ventilation', 341, 5, 7),
       (3421, '3421', 'Physiklabor', 'Laboratory, Physics', 342, 3, 5),
       (3422, '3422', 'Physiklabor mit Strahlenschutz', 'Laboratory, Physics w/ Radiation Protection', 342, 4, 6),
       (3430, '3430', 'Physiklabor mit besonderen RLT Anforderungen', 'Laboratory, Physics w/ Special Ventilation', 343, 4, 6),
       (3442, '3442', 'Physikalischer Mess-und Wägeraum mit DV', 'Laboratory, Measurement', 344, 2, 4),
       (3451, '3451', 'Physikalischer Messraum mit besonderen RLT-Anforderungen', 'Laboratory, Measurement w/ Special Ventilation', 345, 5, 7),
       (3460, '3460', 'Kernphysiklabor mit Dekontamination von Abwasser und Abluft', 'Laboratory, Nuclear Physics w/ Decontamination', 346, 6, 9),
       (3470, '3470', 'Physiklabor und Messraum mit Erschütterungsschutz', 'Laboratory, Measurement w/ Vibration Protection', 347, 5, 7),
       (3480, '3480', 'Physiklabor und Messraum mit elektromagnetischer Abschirmung', 'Laboratory, Measurement w/ Electromagnetic Isolation', 348, 4, 6),
       (3491, '3491', 'Physiklabor und Messraum mit einfachem Strahlenschutz', 'Laboratory, Measurement w/ Simple Radiation Protection', 349, 5, 7),
       (3492, '3492', 'Physiklabor und Messraum mit erhöhtem Strahlenschutz und RLT-Anforderungen', 'Laboratory, Measurement w/ Elevated Radiation Protection & Ventilation', 349, 6, 9),
       (3511, '3511', 'Morphologisches Labor', 'Laboratory, Morphology', 351, 3, 5),
       (3512, '3512', 'Morphologisches Labor mit besonderen RLT-Anforderungen', 'Laboratory, Morphology w/ Special Ventilation', 351, 4, 6),
       (3521, '3521', 'Labor für analytisch-/präparativ-chemische Arbeiten ohne RLT-Anforderungen', 'Laboratory, Chemistry, Analytical/Preparative Work', 352, 3, 5),
       (3522, '3522', 'Labor für analytisch-/präparativ-chemische Arbeiten mit RLT-Anforderungen', 'Laboratory, Chemistry, Analytical/Preparative Work w/ Ventilation', 352, 4, 6),
       (3523, '3523', 'Labor für analytisch-/präparativ-chemische Arbeiten mit besonderen RLT-Anforderungen', 'Laboratory, Chemistry, Analytical/Preparative Work w/ Special Ventilation', 352, 5, 7),
       (3524, '3524', 'Kälte-Labor', 'Laboratory, Chemistry, Analytical/Preparative Work, Cold', 352, 5, 7),
       (3531, '3531', 'Chemisch-technisches Labor mit besonderen RLT-Anforderungen', 'Laboratory, Chemical-Technical w/ Special Ventilation', 353, 5, 7),
       (3532, '3532', 'Chemisch-technisches Labor mit bes. RLT-Anforderungen und einf. Strahlenschutz', 'Laboratory, Chemical-Technical w/ Simple Radiation Protection', 353, 5, 7),
       (3541, '3541', 'Labor mit zusätzlichen Hygieneanforderungen', 'Laboratory w/ Elevated Hygiene Requirements', 354, 4, 6),
       (3542, '3542', 'Labor mit zusätzlichen Hygieneanforderungen und Medienversorgung', 'Laboratory w/ Elevated Hygiene Requirements & Media Support', 354, 4, 6),
       (3550, '3550', 'Labor mit zusätzlichen hygienischen und besonderen RLT-Anforderungen', 'Laboratory w/ Elevated Hygiene Requirements & Special Ventilation', 355, 5, 7),
       (3560, '3560', 'Isotopenlabor mit Dekontamination von Abwasser und Abluft', 'Laboratory, Isotope w/ Decontamination', 356, 4, 6),
       (3570, '3570', 'Isotopenlabor mit Dekontamination von Abwasser u. Abluft u.besonderen RLT-Anforderungen', 'Laboratory, Isotope w/ Decontamination & Special Ventilation', 357, 5, 7),
       (3581, '3581', 'Isotopenlabor mit besonderen baukonstruktiven und RLT-Anforderungen mit Schleuse', 'Laboratory, Isotope w/ Access Lock, Special Construction Requirements & Ventilation', 358, 6, 8),
       (3582, '3582', 'Isotopenlabor mit erhöhten baukonstruktiven und RLT-Anforderungen mit Schleuse', 'Laboratory, Isotope w/ Access Lock, Elevated Construction Requirements & Ventilation', 358, 6, 9),
       (3590, '3590', 'Labor mit besonderen Hygieneanforderungen, Zugang über Schleuse …', 'Laboratory w/ Access Lock & Heightened Hygiene Requirements', 359, 6, 9),
       (3610, '3610', 'Raum für Stallhaltung', 'Husbandry Room, Stalls', 361, 1, 2),
       (3620, '3620', 'Raum für Käfighaltung', 'Husbandry Room, Cages', 362, 1, 2),
       (3631, '3631', 'Tierhaltung experimentell ohne RLT-Anforderungen', 'Husbandry Room, Experimental', 363, 2, 4),
       (3632, '3632', 'Tierhaltung experimentell mit RLT-Anforderungen', 'Husbandry Room, Experimental w/ Ventilation', 363, 4, 6),
       (3641, '3641', 'Käfighaltung experimentell mit RLT-Anforderungen', 'Husbandry Room, Experimental, Cages w/ Ventilation', 364, 4, 6),
       (3642, '3642', 'Käfighaltung experimentell mit RLT-Anforderungen und Laborarbeitsplatz', 'Husbandry Room, Experimental, Cages w/, Laboratory Space & Ventilation', 364, 5, 7),
       (3643, '3643', 'Käfighaltung experimentell mit RLT-Anforderungen und einfachem Strahlenschutz', 'Husbandry Room, Experimental, Cages w/ Radiation Protection & Ventilation', 364, 5, 7),
       (3644, '3644', 'Käfighaltung experimentell SPF mit Schleuse', 'Husbandry Room, Experimental, Cages w/ Access Lock', 364, 6, 8),
       (3650, '3650', 'Raum für Beckenhaltung', 'Husbandry Room, Tanks', 365, 4, 6),
       (3661, '3661', 'Tierpflegeraum', 'Animal Care Room', 366, 2, 3),
       (3662, '3662', 'Tierpflegeraum mit RLT-Anforderungen', 'Animal Care Room w/ Ventilation', 366, 3, 5),
       (3670, '3670', 'Futteraufbereitungsraum', 'Preparation Room, Feed', 367, 3, 5),
       (3680, '3680', 'Milch-/Melkraum', 'Milking Room', 368, 2, 4),
       (3690, '3690', 'Kadaverraum (mit RLT-Anforderungen)', 'Morgue, Animal', 369, 3, 5),
       (3710, '3710', 'Gewächshaus allgemein', 'Greenhouse', 371, 1, 2),
       (3720, '3720', 'Gewächshaus mit besonderen klimatischen Bedingungen', 'Greenhouses w/ Special Acclimatization', 372, 2, 3),
       (3730, '3730', 'Pflanzenzuchtraum experimentell', 'Plant Cultivation Room, Experimental', 373, 2, 3),
       (3740, '3740', 'Pilzzuchtraum', 'Fungus Cultivation Room', 374, 2, 3),
       (3750, '3750', 'Pflanzenzuchtvorbereitungsraum', 'Preparation Room, Plant Cultivation', 375, 2, 3),
       (3820, '3820', 'Teilküche', 'Kitchenette', 382, 2, 4),
       (3830, '3830', 'Großküche', 'Kitchen, Industrial', 383, 4, 6),
       (3840, '3840', 'Spezialküche', 'Kitchen, Special', 384, 4, 6),
       (3850, '3850', 'Küchenvorbereitungsraum', 'Kitchen, Preparation', 385, 4, 6),
       (3860, '3860', 'Backraum', 'Kitchen, Baking', 386, 4, 6),
       (3870, '3870', 'Speiseausgabe', 'Serving Room', 387, 4, 6),
       (3880, '3880', 'Spülküche', 'Dishwashing Room', 388, 4, 6),
       (3911, '3911', 'Hauswirtschaftsraum Wohnung', 'Housekeeping Room, Residential', 391, 2, 3),
       (3912, '3912', 'Hauswirtschaftsraum Schule', 'Housekeeping Room, Scholastic', 391, 2, 4),
       (3921, '3921', 'Wäschereiraum', 'Laundry Room', 392, 2, 3),
       (3922, '3922', 'Wäschereiraum mit Einrichtungen', 'Laundry Room w/ Integral Equipment', 392, 4, 6),
       (3931, '3931', 'Wäschepflegeraum', 'Laundry Care Room', 393, 2, 3),
       (3932, '3932', 'Wäschepflegeraum mit Einrichtungen', 'Laundry Care Room w/ Integral Equipment', 393, 3, 5),
       (3941, '3941', 'Spülraum', 'Rinsing Room', 394, 4, 6),
       (3942, '3942', 'Spülraum mit Strahlenschutz', 'Rinsing Room w/ Radiation Protection', 394, 5, 7),
       (3951, '3951', 'Instrumentenreinigungsraum', 'Equipment Cleaning Room', 395, 4, 6),
       (3952, '3952', 'Aufbereitungsraum für medizintechnisches Gerät', 'Set-Up Room for Medical Equipment', 395, 4, 6),
       (3953, '3953', 'Käfigreinigung manuell', 'Cage Cleaning Room, Manual', 395, 4, 6),
       (3954, '3954', 'Bettenreinigung manuell', 'Bed Cleaning Room, Manual', 395, 4, 6),
       (3961, '3961', 'Bettendesinfektion maschinell', 'Bed Cleaning Room, Automated', 396, 4, 6),
       (3962, '3962', 'Käfigdesinfektion maschinell', 'Cage Cleaning Room, Automated', 396, 4, 6),
       (3970, '3970', 'Sterilisationsraum', 'Sterilization Room', 397, 5, 7),
       (3981, '3981', 'Bettenarbeitsraum', 'Bed Workroom', 398, 4, 6),
       (3982, '3982', 'Pflegearbeitsraum rein', 'Care Giver Workroom, Clean', 398, 4, 6),
       (3983, '3983', 'Pflegearbeitsraum unrein', 'Care Giver Workroom, Unclean', 398, 4, 6),
       (3984, '3984', 'Schwesternstützpunkt', 'Nursing Station', 398, 4, 6),
       (3985, '3985', 'Pflegearbeitsraum rein mit besonderen hygienischen Anforderungen', 'Care Giver Workroom, Clean w/ Heightened Hygiene Requirements', 398, 4, 6),
       (3986, '3986', 'Pflegearbeitsraum unrein mit besonderen RLT-Anforderungen und Strahlenschutz', 'Care Giver Workroom, Unclean w/ Radiation Protection & Special Ventilation', 398, 5, 7),
       (3991, '3991', 'Vorbereitungsraum Geisteswissenschaften', 'Preparation Room, Lecture', 399, 3, 5),
       (3992, '3992', 'Vorbereitungsraum Labor', 'Preparation Room, Laboratory', 399, 4, 6),
       (3993, '3993', 'Vorbereitungsraum Labor mit einfachem Strahlenschutz', 'Preparation Room, Laboratory w/ Simple Radiation Protection', 399, 5, 7),
       (3994, '3994', 'Vorbereitungsraum Labor mit besonderen RLT-Anforderungen', 'Preparation Room, Laboratory w/ Special Ventilation', 399, 5, 7),
       (4110, '4110', 'Lagerraum allgemein', 'Warehouse', 411, 1, 2),
       (4121, '4121', 'Lagerraum be- und entlüftet', 'Warehouse w/ Ventilation', 412, 2, 4),
       (4122, '4122', 'Lagerraum klimatisiert', 'Warehouse w/ Climate Control', 412, 3, 5),
       (4130, '4130', 'Lagerraum mit hygienischen Anforderungen (mit Abluft)', 'Warehouse w/ Hygiene Requirements', 413, 2, 3),
       (4142, '4142', 'Lagerraum mit betriebsspezifischen Einbauten und DV-Arbeitsplatz', 'Warehouse w/ Integral Equipment', 414, 2, 4),
       (4161, '4161', 'Lagerraum mit einfachen Strahlenschutz-Anforderungen', 'Warehouse w/ Simple Radiation Protection Requirements', 416, 4, 6),
       (4162, '4162', 'Lagerraum mit besonderen Strahlenschutz-Anforderungen', 'Warehouse w/ Heightened Radiation Protection Requirements', 416, 5, 7),
       (4163, '4163', 'Lagerraum mit erhöhten Strahlenschutz-Anforderungen und Zugang über Schleuse', 'Warehouse w/ Access Lock & Extensive Radiation Protection Requirements', 416, 5, 7),
       (4170, '4170', 'Tresorraum', 'Vault', 417, 3, 5),
       (4181, '4181', 'Futtermittellager', 'Storage, Feed', 418, 1, 2),
       (4182, '4182', 'Futtermittellager mit Verarbeitung', 'Storage, Feed w/ Processing', 418, 2, 3),
       (4183, '4183', 'Futtermittellager mit besonderen hygienischen und RLT-Anforderungen', 'Storage, Feed w/ Heightened Hygiene & Special Ventilation Requirements', 418, 4, 6),
       (4190, '4190', 'Leichenraum für Anatomie', 'Morgue, Anatomy', 419, 3, 5),
       (4211, '4211', 'Archiv', 'Archive', 421, 1, 2),
       (4212, '4212', 'Archiv mit Abluft', 'Archive w/ Ventilation', 421, 2, 3),
       (4213, '4213', 'Archiv mit DV und RLT-Anforderungen', 'Archive, Electronic w/ Ventilation', 421, 2, 4),
       (4220, '4220', 'Registratur (ohne Arbeitsplatz)', 'Registry', 422, 1, 2),
       (4230, '4230', 'Sammlungsraum', 'Collection Room', 423, 2, 3),
       (4240, '4240', 'Magazin', 'Repository', 424, 1, 2),
       (4250, '4250', 'Magazin mit Klimakonstanz', 'Repository, Climatized', 425, 4, 6),
       (4251, '4251', 'Lagerraum für Explosivstoffe', 'Storage, Explosive', 425, 3, 5),
       (4252, '4252', 'Lagerraum für Chemikalien', 'Storage, Chemical', 425, 4, 6),
       (4310, '4310', 'Lebensmittelkühlraum', 'Storage, Cold, Food', 431, 4, 6),
       (4320, '4320', 'Lebensmitteltiefkühlraum', 'Storage, Frozen, Food', 432, 4, 6),
       (4330, '4330', 'Kühlraum für medizinische Zwecke', 'Storage, Cold, Medical', 433, 5, 7),
       (4341, '4341', 'Kühlraum für wissenschaftliche Zwecke (nur Kühlung)', 'Storage, Cold, Scientific', 434, 5, 7),
       (4342, '4342', 'Kühlraum für wissenschaftliche Zwecke (Tiefkühlung)', 'Storage, Frozen, Scientific', 434, 5, 7),
       (4350, '4350', 'Leichenkühlraum', 'Morgue', 435, 4, 6),
       (4411, '4411', 'Annahme- und Ausgaberaum', 'Goods Distribution Room', 441, 2, 3),
       (4412, '4412', 'Annahme- und Ausgaberaum mit DV', 'Goods Distribution Room w/ Tracking Equipment', 441, 2, 4),
       (4420, '4420', 'Sortierraum', 'Sorting Room', 442, 1, 2),
       (4430, '4430', 'Packraum', 'Packing Room', 443, 1, 2),
       (4440, '4440', 'Versandraum', 'Shipping Room', 444, 1, 2),
       (4451, '4451', 'Versorgungsraum mit Abluft', 'Supply Room w/ Ventilation', 445, 2, 3),
       (4452, '4452', 'Versorgungsraum mit Abluft und Nassarbeitsplatz', 'Supply Room w/ Ventilation & Wet Workstation', 445, 3, 5),
       (4453, '4453', 'Versorgungsraum mit hygienischen und besonderen RLT-Anforderungen', 'Supply Room w/ Hygiene Requirements & Special Ventilation', 445, 5, 7),
       (4461, '4461', 'Entsorgungsraum mit Abluft', 'Disposal Room w/ Ventilation', 446, 2, 3),
       (4462, '4462', 'Entsorgungsraum mit Abluft und Nassarbeitsplatz', 'Disposal Room w/ Ventilation & Wet Workstation', 446, 3, 5),
       (4463, '4463', 'Entsorgungsraum mit hygienischen und RLT-Anforderungen', 'Disposal Room w/ Hygiene & Ventilation Requirements', 446, 4, 6),
       (4464, '4464', 'Entsorgungsraum mit besonderen hygienischen, RLT-Anforderungen und Strahlenschutz', 'w/ Heightened Hygiene Requirements, Radiation Protection & Special Ventilation', 446, 5, 7),
       (4510, '4510', 'Verkaufsstand', 'Retail Booth', 451, 2, 3),
       (4520, '4520', 'Ladenraum', 'Retail Room', 452, 2, 3),
       (4530, '4530', 'Supermarktverkaufsraum', 'Retail Room, Supermarket', 453, 3, 5),
       (4540, '4540', 'Kaufhausverkaufsraum', 'Retail Room, Department Store', 454, 3, 5),
       (4550, '4550', 'Großmarkthallenverkaufsraum', 'Retail Room, Wholesale', 455, 1, 2),
       (4610, '4610', 'Verkaufsausstellungsraum', 'Retail Room, Display', 461, 2, 3),
       (4620, '4620', 'Musterraum', 'Example Room', 462, 2, 3),
       (4630, '4630', 'Messehalle', 'Exhibition Hall', 463, 2, 3),
       (5111, '5111', 'Hör-/Lehrsaal ansteigend mit Experimentierbühne mit RLT-Anforderungen', 'Lecture Hall, Inclined w/ Demonstration Stage', 511, 4, 6),
       (5112, '5112', 'Hör-/Lehrsaal ansteigend mit Experimentierbühne mit Medienversorgung u. bes. RLT-Anf.', 'Lecture Hall, Inclined w/ Demonstration Stage & Media Support', 511, 5, 7),
       (5121, '5121', 'Hör-/Lehrsaal eben mit Experimentierbühne mit RLT-Anforderungen', 'Lecture Hall, Flat, w/ Demonstration Stage', 512, 3, 5),
       (5122, '5122', 'Hör-/Lehrsaal eben mit Experimentierbühne mit Medienversorgung u. bes. RLT-Anf.', 'Lecture Hall, Flat, w/ Demonstration Stage & Media Support', 512, 4, 6),
       (5131, '5131', 'Hör-/Lehrsaal ansteigend ohne Experimentierbühne mit RLT-Anforderungen', 'Lecture Hall, Inclined', 513, 3, 5),
       (5132, '5132', 'Hör-/Lehrsaal ansteigend ohne Experimentierbühne mit Medienversorgung u. bes. RLT-Anf.', 'Lecture Hall, Inclined w/ Media Support', 513, 4, 6),
       (5141, '5141', 'Hör-/Lehrsaal eben ohne Experimentierbühne mit RLT-Anforderungen', 'Lecture Hall, Flat', 514, 3, 5),
       (5142, '5142', 'Hör-/Lehrsaal eben ohne Experimentierbühne mit Medienversorgung u. bes. RLT-Anf.', 'Lecture Hall, Flat w/ Media Support', 514, 4, 6),
       (5210, '5210', 'Unterrichtsraum', 'Classroom', 521, 2, 3),
       (5221, '5221', 'Unterrichtsgroßraum', 'Classroom, Large', 522, 2, 4),
       (5222, '5222', 'Unterrichtsgroßraum mit RLT-Anforderungen', 'Classroom, Large w/ Ventilation', 522, 3, 5),
       (5231, '5231', 'Übungsraum', 'Practice Room', 523, 2, 3),
       (5232, '5232', 'Übungsraum mit DV', 'Practice Room w/ Computers', 523, 2, 4),
       (5233, '5233', 'Übungsraum Naturwissenschaften', 'Practice Room, Natural Sciences', 523, 3, 5),
       (5240, '5240', 'Mehrzweckunterrichtsraum', 'Classroom, Multi-Purpose', 524, 2, 3),
       (5250, '5250', 'Zeichenübungsraum', 'Practice Room, Drawing', 525, 2, 3),
       (5260, '5260', 'Verhaltensbeobachtungsraum', 'Behavioral Observation Room', 526, 2, 3),
       (5270, '5270', 'Übungsraum für darstellende Kunst', 'Practice Room, Performing Arts', 527, 2, 3),
       (5311, '5311', 'Zeichensaal', 'Classroom, Visual Arts', 531, 2, 3),
       (5312, '5312', 'Textilarbeitsraum', 'Classroom, Design', 531, 2, 4),
       (5313, '5313', 'Bildhauerklassenraum', 'Classroom, Sculpture, Stone', 531, 3, 5),
       (5314, '5314', 'Werkraum - Holz', 'Classroom, Sculpture, Wood', 531, 3, 5),
       (5315, '5315', 'Modellierraum - Ton', 'Classroom, Sculpture, Clay', 531, 3, 5),
       (5320, '5320', 'Hauswirtschaftlicher Unterrichtsraum', 'Classroom, Home Economics', 532, 4, 6),
       (5330, '5330', 'Medienunterstützter Unterrichtsraum', 'Classroom, Media', 533, 4, 6),
       (5340, '5340', 'Musik-/Sprechunterrichtsraum', 'Classroom, Music / Speech', 534, 4, 6),
       (5350, '5350', 'Physikalisch-technischer Übungsraum', 'Practice Room, Physical / Technical', 535, 5, 7),
       (5361, '5361', 'Nasspräparativer Übungsraum', 'Practice Room, Wet Chemistry', 536, 4, 6),
       (5362, '5362', 'Nasspräparativer Übungsraum mit besonderen RLT-Anforderungen', 'Practice Room, Wet Chemistry w/ Special Ventilation', 536, 5, 7),
       (5370, '5370', 'Zahnmedizinischer Übungsraum', 'Practice Room, Dental Medicine', 537, 5, 7),
       (5410, '5410', 'Bibliotheksraum allgemein', 'Library Room', 541, 3, 5),
       (5420, '5420', 'Leseraum', 'Reading Room', 542, 3, 5),
       (5430, '5430', 'Freihandstellfläche', 'Stack Room', 543, 3, 5),
       (5440, '5440', 'Katalograum/-fläche', 'Catalog Room', 544, 3, 5),
       (5450, '5450', 'Mediothekraum', 'Media Room', 545, 4, 6),
       (5510, '5510', 'Halle für Turnen und Spiele', 'Gymnasium', 551, 2, 3),
       (5520, '5520', 'Schwimmhalle', 'Swimming Pool, Indoor', 552, 4, 6),
       (5530, '5530', 'Eissporthalle', 'Ice Rink', 553, 3, 5),
       (5540, '5540', 'Radsporthalle', 'Cycling Hall', 554, 3, 5),
       (5550, '5550', 'Reitsporthalle', 'Equestrian Hall', 555, 1, 2),
       (5560, '5560', 'Sportübungsraum', 'Fitness Studio', 556, 2, 3),
       (5570, '5570', 'Kegelbahn', 'Bowling Alley', 557, 2, 3),
       (5581, '5581', 'Schießsportraum', 'Indoor Shooting Gallery', 558, 1, 2),
       (5582, '5582', 'Schießsportraum mit RLT-Anforderungen', 'Indoor Shooting Gallery w/ Ventilation', 558, 3, 5),
       (5583, '5583', 'Schießsportraum mit Medienunterstützung und RLT-Anforderungen', 'Indoor Shooting Gallery w/ Media Support & Ventilation', 558, 4, 6),
       (5590, '5590', 'Sondersporthalle', 'Specialized Sport Room', 559, 2, 3),
       (5611, '5611', 'Tagungsraum mit DV', 'Convention Room', 561, 2, 4),
       (5621, '5621', 'Zuschauerraum in Sport- und Mehrzweckhallen', 'Spectator Room, Multi-Purpose & Sport Halls', 562, 2, 3),
       (5622, '5622', 'Zuschauerraum in Theater- und Konzertsälen', 'Spectator Room, Concert & Theater Halls', 562, 4, 6),
       (5630, '5630', 'Mehrzweckhalle', 'Multi-Purpose Hall', 563, 3, 5),
       (5711, '5711', 'Bühnenraum Sporthalle', 'Stage, Sports', 571, 2, 3),
       (5712, '5712', 'Bühnenraum Theater', 'Stage, Theater', 571, 5, 7),
       (5720, '5720', 'Probebühne', 'Stage, Practice', 572, 2, 3),
       (5730, '5730', 'Orchesterraum', 'Orchestra Room', 573, 3, 5),
       (5740, '5740', 'Orchesterprobenraum', 'Orchestra Room, Practice', 574, 4, 6),
       (5750, '5750', 'Tonstudioraum', 'Studio, Audio', 575, 4, 6),
       (5760, '5760', 'Bildstudioraum', 'Studio, Visual', 576, 4, 6),
       (5770, '5770', 'Künstleratelier', 'Studio, Artistic', 577, 3, 5),
       (5810, '5810', 'Schauraum allgemein', 'Gallery', 581, 2, 3),
       (5821, '5821', 'Museumsraum', 'Exhibition Room, Museum', 582, 3, 5),
       (5822, '5822', 'Museumsraum (Großraum)', 'Exhibition Room, Museum, Large', 582, 4, 6),
       (5823, '5823', 'Museumsraum für besondere Exponate (Halle)', 'Exhibition Room, Museum, Specialized', 582, 5, 7),
       (5830, '5830', 'Lehr- und Schausammlungsraum', 'Exhibition Room', 583, 2, 3),
       (5840, '5840', 'Besucherfläche', 'Vistor Area', 584, 2, 3),
       (5910, '5910', 'Gottesdienstraum', 'Religious Service Room', 591, 3, 5),
       (5920, '5920', 'Andachtsraum', 'Prayer Room', 592, 3, 5),
       (5930, '5930', 'Aussegnungsraum', 'Consecration Room', 593, 2, 3),
       (5940, '5940', 'Aufbahrungsraum', 'Laying On State Room', 594, 3, 5),
       (5950, '5950', 'Sakristei', 'Sacristy', 595, 2, 3),
       (5960, '5960', 'Kreuzgang', 'Cloister', 596, 1, 2),
       (6111, '6111', 'U + B-Raum/Arztsprechzimmer mit einfacher Ausstattung', 'Examination & Treatment Room', 611, 2, 4),
       (6112, '6112', 'U + B-Raum/Arztsprechzimmer mit Waschtisch', 'Examination & Treatment Room w/ Wash Table', 611, 3, 5),
       (6114, '6114', 'U + B Vorbereitungsungsraum', 'Preparation Room, Examination & Treatment', 611, 3, 5),
       (6115, '6115', 'Gipsraum Ambulanz', 'Plaster Room, Ambulant', 611, 3, 5),
       (6121, '6121', 'Erste Hilfe-Raum mit einfacher Ausstattung', 'First-Aid Room, Simple', 612, 2, 4),
       (6122, '6122', 'Erste-Hilfe-Raum im Krankenhaus', 'First-Aid Room, Hospital', 612, 4, 6),
       (6140, '6140', 'Tiermedizinischer U + B-Raum mit einfacher medizinischer Ausstattung', 'Veterinary Examination & Treatment Room', 614, 4, 6),
       (6150, '6150', 'Demonstrationsraum mit einfacher Ausstattung', 'Medical Demonstration Room', 615, 3, 5),
       (6211, '6211', 'U + B-Raum Atemphysiologie', 'Examination & Treatment Room, Respiratory', 621, 4, 6),
       (6212, '6212', 'U + B-Raum Atemphysiologie mit RLT-Anforderungen', 'Examination & Treatment Room, Respiratory w/ Ventilation', 621, 4, 6),
       (6221, '6221', 'U + B-Raum Herz/Kreislaufdiagnostik', 'Examination & Treatment Room, Cardiovascular', 622, 4, 6),
       (6222, '6222', 'U + B-Raum Herz/Kreislaufdiagnostik mit RLT-Anforderungen', 'Examination & Treatment Room, Cardiovascular w/ Ventilation', 622, 4, 6),
       (6231, '6231', 'U + B-Raum Neurophysiologie', 'Examination & Treatment Room, Neural', 623, 4, 6),
       (6232, '6232', 'U + B-Raum Neurophysiologie mit RLT-Anforderungen', 'Examination & Treatment Room, Neural w/ Ventilation', 623, 4, 6),
       (6240, '6240', 'Sinnesphysiologischer U + B-Raum', 'Examination & Treatment Room, Sensory', 624, 4, 6),
       (6250, '6250', 'Augen-U + B-Raum', 'Examination & Treatment Room, Ophthalmological', 625, 4, 6),
       (6260, '6260', 'Zahnmedizinischer U + B-Raum', 'Examination & Treatment Room, Dental', 626, 5, 7),
       (6270, '6270', 'Tiermedizinischer U + B-Raum mit besonderer Ausstattung', 'Examination & Treatment Room, Veterinary w/ Specialized Equipment', 627, 5, 7),
       (6281, '6281', 'Projektions-/Demonstrationsraum', 'Medical Presentation Room', 628, 3, 5),
       (6282, '6282', 'Klinischer Konferenzraum PACS', 'Conference Room, Clinical', 628, 3, 5),
       (6311, '6311', 'Operationsraum', 'Operating Room', 631, 6, 8),
       (6312, '6312', 'Operationsraum mit Strahlenschutz', 'Operating Room w/ Radiation Protection', 631, 6, 8),
       (6321, '6321', 'Operationsraum mit Sonderausstattung', 'Operating Room w/ Special Equipment', 632, 6, 8),
       (6322, '6322', 'Operationsraum mit Sonderausstattung und Strahlenschutz', 'Operating Room w/ Radiation Protection & Special Equipment', 632, 6, 8),
       (6331, '6331', 'Eingriffsraum', 'Procedure Room', 633, 4, 6),
       (6332, '6332', 'Eingriffsraum mit besonderen hygienischen und RLT-Anforderungen', 'Procedure Room w/ Heightened Hygiene & Special Ventilation Requirements', 633, 6, 8),
       (6341, '6341', 'Geburtshilfe-Vorbereitungs- und Ergänzungsraum', 'Delivery Room, Supplemental', 634, 3, 5),
       (6342, '6342', 'Geburtshilferaum', 'Delivery Room', 634, 4, 6),
       (6351, '6351', 'Waschraum Endoskopie', 'Washroom, Endoscopy', 635, 4, 6),
       (6352, '6352', 'Endoskopieraum', 'Endoscopy Room', 635, 5, 7),
       (6353, '6353', 'Endoskopieraum mit Strahlenschutz', 'Endoscopy Room w/ Radiation Protection', 635, 5, 7),
       (6361, '6361', 'Waschraum OP', 'Washroom, Operating Rooms', 636, 5, 7),
       (6362, '6362', 'Patientenvorbereitungsraum OP', 'Preparation Room, Operating Room Patients', 636, 6, 8),
       (6363, '6363', 'Einleitungsraum', 'Preparation Room, Surgical', 636, 6, 8),
       (6364, '6364', 'Einleitungs-/Ausleitungsraum', 'Surgical Preparation & Discharge Room', 636, 6, 8),
       (6365, '6365', 'Ausleitungs-/Entsorgungsraum', 'Surgical Discharge & Disposal Room', 636, 6, 8),
       (6366, '6366', 'Gipsraum OP', 'Plaster Room, Surgical', 636, 6, 8),
       (6367, '6367', 'Medizinische Versorgung OP', 'Surgical Supply Room', 636, 4, 6),
       (6368, '6368', 'Medizinische Entsorgung OP', 'Surgical Disposal Room', 636, 5, 7),
       (6369, '6369', 'Umbettschleuse', 'Bed Transfer Corridor', 636, 5, 7),
       (6371, '6371', 'Tierendoskopieraum', 'Veterinary Endoscopy Room', 637, 5, 7),
       (6372, '6372', 'Tieroperationsraum', 'Veterinary Operating Room', 637, 6, 8),
       (6373, '6373', 'Tieroperationsraum mit Strahlenschutz', 'Veterinary Operating Room w/ Radiation Protection', 637, 6, 8),
       (6411, '6411', 'Durchleuchtungsraum', 'Fluoroscopy Room', 641, 5, 7),
       (6412, '6412', 'Röntgenaufnahmeraum', 'X-Ray Recording Room', 641, 5, 7),
       (6413, '6413', 'Röntgenuntersuchungsraum experimentell', 'X-Ray Examination Room', 641, 4, 6),
       (6414, '6414', 'Röntgenvorbereitungsraum', 'Preparation Room, X-Ray', 641, 4, 6),
       (6421, '6421', 'Spezialaufnahmenraum', 'Special Recording Room', 642, 5, 7),
       (6423, '6423', 'Angiographieraum', 'Angiography Room', 642, 5, 7),
       (6431, '6431', 'Computertomographieraum (CT)', 'Computer Tomography Room', 643, 5, 7),
       (6432, '6432', 'Magnetresonanz-Tomographieraum (NMR)', 'Magnetic Resonance Imaging Room', 643, 6, 8),
       (6440, '6440', 'Zahnmedizinischer Röntgenuntersuchungsraum', 'Dental X-Ray Examination Room', 644, 5, 7),
       (6451, '6451', 'Messraum mit Einkanal-Messplatz', 'Medical Measurement Room w/ ', 645, 5, 7),
       (6452, '6452', 'Messraum mit Kamera', 'Medical Measurement Room w/ Camera', 645, 6, 8),
       (6453, '6453', 'Messraum für Positronen-Emissions-Tomographie (PET)', 'Medical Measurement Room w/ Positron Emission Tomography', 645, 6, 8),
       (6461, '6461', 'Abklingraum Nuklearmedizin', 'Cooling Room, Nuclear Medicine', 646, 5, 7),
       (6462, '6462', 'Vorbereitungsraum nuklearmedizinische Diagnostik', 'Preparation Room, Nuclear Diagnosis', 646, 5, 7),
       (6471, '6471', 'Ultraschalldiagnostikraum', 'Ultrasound Diagnosis Room', 647, 3, 5),
       (6472, '6472', 'Ultraschalldiagnostikraum mit RLT-Anforderungen', 'Ultrasound Diagnosis Room w/ Ventilation', 647, 4, 6),
       (6481, '6481', 'Strahlendiagnostikraum Tiermedizin', 'Veterinary Radiation Diagnosis Room', 648, 5, 7),
       (6482, '6482', 'Nuklearmedizinischer Messraum für Tiere', 'Nuclear-Veterinary Measurement Room', 648, 5, 7),
       (6510, '6510', 'Oberflächenbestrahlung', 'Superficial Irradiation Room', 651, 6, 8),
       (6521, '6521', 'Halbtiefen-/Tiefenbestrahlung', 'Deep Irradiation Room', 652, 6, 8),
       (6522, '6522', 'Linearbeschleuniger', 'Linear Particle Accelerator Room', 652, 6, 8),
       (6530, '6530', 'Bestrahlungsplanung', 'Irradiation Planning Room', 653, 6, 8),
       (6540, '6540', 'Bestrahlung mit offenen radioaktiven Stoffen', 'Room for Irradiation w/ Unsealed Radioactive Substances', 654, 6, 8),
       (6550, '6550', 'Bestrahlung mit umschlossenen radioaktiven Stoffen', 'Room for Irradiation w/ Sealed Radioactive Substances', 655, 6, 8),
       (6560, '6560', 'Bestrahlung mit offenen Isotopen', 'Room for Irradiation w/ Unsealed Isotopes', 656, 6, 8),
       (6571, '6571', 'Bestrahlung mit umschlossenen Isotopen - Vorbereitung', 'Rooms for Irradiation w/ Sealed Isotopes, Preparation', 657, 6, 8),
       (6572, '6572', 'Bestrahlung mit umschlossenen Isotopen - Behandlung', 'Rooms for Irradiation w/ Sealed Isotopes, Treatment', 657, 6, 8),
       (6611, '6611', 'Medizinisches Wannenbad', 'Bathing Room, Medical', 661, 4, 6),
       (6612, '6612', 'Medizinisches Teilbad', 'Bathing Room, Medical, Partial', 661, 4, 6),
       (6613, '6613', 'Unterwasserdruckstrahlmassage', 'Underwater Pressure Stream Massage Room', 661, 4, 6),
       (6614, '6614', 'Kneipp\'sche Anwendungen', 'Bathing Room, Medical, Cold', 661, 4, 6),
       (6622, '6622', 'Schwimmbecken Nasstherapie', 'Swimming Pool, Therapeutic', 662, 4, 6),
       (6631, '6631', 'Schwitzbad', 'Steam Bath', 663, 4, 6),
       (6632, '6632', 'Packungen - Vorbereitung', 'Compress Room, Preparation', 663, 4, 6),
       (6633, '6633', 'Packungen - Behandlung', 'Compress Room, Treatment', 663, 4, 6),
       (6641, '6641', 'Einzelinhalation', 'Inhalation Room, Individual', 664, 4, 6),
       (6642, '6642', 'Rauminhalation', 'Inhalation Room, Group', 664, 4, 6),
       (6651, '6651', 'Laufschule', 'Therapy Room, Walking', 665, 2, 3),
       (6652, '6652', 'Traktionsraum', 'Therapy Room, Traction', 665, 2, 3),
       (6653, '6653', 'Gymnastikraum', 'Therapy Room, Gymnastics', 665, 2, 4),
       (6660, '6660', 'Massageraum', 'Therapy Room, Massage', 666, 3, 5),
       (6671, '6671', 'Bestrahlungen', 'Therapy Room, Radiation', 667, 3, 5),
       (6672, '6672', 'Durchströmung', 'Therapy Room, Perfusion', 667, 3, 5),
       (6673, '6673', 'Vibrationsmassage', 'Therapy Room, Massage, Vibration', 667, 3, 5),
       (6674, '6674', 'Hyperthermietherapie', 'Therapy Room, Hyperthermia', 667, 5, 7),
       (6681, '6681', 'Rehabilitationsraum Spieltherapie', 'Rehabilitation Room, Play Therapy', 668, 2, 3),
       (6682, '6682', 'Rehabilitationsraum Arbeitstherapie', 'Rehabilitation Room, Work Therapy', 668, 2, 4),
       (6710, '6710', 'Normalpflegebettenraum', 'Patient Bedroom', 671, 4, 6),
       (6720, '6720', 'Infektionspflegebettenraum', 'Patient Bedroom, Infectious Care', 672, 4, 6),
       (6731, '6731', 'Bettenraum Psychiatrische Pflege', 'Patient Bedroom, Psychiatric Care', 673, 2, 3),
       (6732, '6732', 'Bettenraum Psychiatrische Pflege mit einfacher medizinischer Ausstattung', 'Patient Bedroom, Psychiatric Care w/ Medical Equipment', 673, 4, 6),
       (6740, '6740', 'Neugeborenenpflegebettenraum', 'Nursery', 674, 4, 6),
       (6750, '6750', 'Säuglingspflegebettenraum', 'Patient Bedroom, Infant Care', 675, 4, 6),
       (6760, '6760', 'Kinderpflegebettenraum', 'Patient Bedroom, Child Care', 676, 4, 6),
       (6770, '6770', 'Langzeitpflegebettenraum', 'Patient Bedroom, Long-Term Care', 677, 3, 5),
       (6780, '6780', 'Leichtpflegebettenraum', 'Patient Bedroom, Outpatient Care', 678, 3, 5),
       (6810, '6810', 'Bettenraum für Intensivüberwachung', 'Patient Bedroom, Intensive Observation', 681, 5, 7),
       (6820, '6820', 'Bettenraum für Intensivbehandlung', 'Patient Bedroom, Intensive Treatment', 682, 6, 8),
       (6830, '6830', 'Bettenraum für die Behandlung Brandverletzter', 'Patient Bedroom, Burn Victim Care', 683, 6, 8),
       (6841, '6841', 'Behandlungsplatz Dialyse', 'Patient Bedroom, Dialysis', 684, 4, 6),
       (6842, '6842', 'Intensivbehandlung Akutdialyse', 'Patient Bedroom, Dialysis, Intensive Treatment', 684, 5, 7),
       (6850, '6850', 'Bettenraum für Reverse Isolation', 'Patient Bedroom, Reverse Isolation', 685, 6, 7),
       (6860, '6860', 'Bettenraum für die Pflege Frühgeborener', 'Patient Bedroom, Premature Infant Care', 686, 5, 7),
       (6871, '6871', 'Bettenraum für Strahlenpatienten, offene Isotope', 'Patient Bedroom, Radiation Therapy, Unsealed Isotopes', 687, 5, 7),
       (6872, '6872', 'Bettenraum für Strahlenpatienten, umschlossene Isotope', 'Patient Bedroom, Radiation Therapy, Sealed Isotopes', 687, 5, 7),
       (6880, '6880', 'Bettenraum für die Pflege Querschnittgelähmter', 'Patient Bedroom, Paraplegic Care', 688, 4, 6),
       (6891, '6891', 'Aufwachraum (postoperativ)', 'Recovery Rooms, Post-Operative', 689, 4, 6),
       (6892, '6892', 'Aufwachraum (postoperativ) mit besonderen RLT-Anforderungen', 'Recovery Rooms, Post-Operative w/ Special Ventilation', 689, 5, 7),
       (7112, '7112', 'Toilette mit Abluft', 'Toilet w/ Ventilation', 711, 3, 5),
       (7113, '7113', 'Toilette behindertengerecht', 'Restroom, Accessible', 711, 4, 6),
       (7114, '7114', 'Patiententoilette', 'Restroom, Patient', 711, 4, 6),
       (7115, '7115', 'Toilette mit Strahlenschutzmassnahmen', 'Restroom w/ Radiation Protections', 711, 5, 7),
       (7122, '7122', 'Waschraum mit Abluft', 'Washroom w/ Ventilation', 712, 3, 5),
       (7123, '7123', 'Waschraum behindertengerecht', 'Washroom, Accessible', 712, 4, 6),
       (7124, '7124', 'Waschraum mit Strahlenschutzmassnahmen', 'Washroom w/ Radiation Protections', 712, 5, 7),
       (7132, '7132', 'Duschraum mit Abluft', 'Shower Room w/ Ventilation', 713, 3, 5),
       (7133, '7133', 'Duschraum behindertengerecht', 'Shower Room, Accessible', 713, 4, 6),
       (7134, '7134', 'Patientendusche', 'Shower Room, Patient', 713, 4, 6),
       (7135, '7135', 'Duschraum mit Strahlenschutzmassnahmen', 'Shower Room w/ Radiation Protections', 713, 5, 7),
       (7142, '7142', 'Baderaum mit Abluft', 'Bath Room w/ Ventilation', 714, 3, 5),
       (7143, '7143', 'Baderaum behindertengerecht', 'Bath Room, Accessible', 714, 4, 6),
       (7144, '7144', 'Patientenbad', 'Bathroom, Patient', 714, 4, 6),
       (7145, '7145', 'Sanitärzelle Patientenzimmer', 'Sanitary Cell, Patient', 714, 4, 6),
       (7146, '7146', 'Sanitärzelle Patientenzimmmer mit besonderen hygienischen und RLT-Anforderungen', 'Sanitary Cell, Patient w/ Heightened Hygiene & Special Ventilation Requirements', 714, 5, 7),
       (7147, '7147', 'Sanitärzelle mit Strahlenschutzmassnahmen', 'Sanitary Cell w/ Radiation Protections', 714, 5, 7),
       (7150, '7150', 'Sauna (Kabine)', 'Sauna', 715, 3, 5),
       (7161, '7161', 'Zwangsdusche mit Abluft', 'Compulsory Shower w/ Ventilation', 716, 4, 6),
       (7162, '7162', 'Zwangsdusche im strahlengeschützten, hygienisch abgeschlossenen Bereich', 'Compulsory Shower, Hygienically Sealed / Radiation Protected Area', 716, 5, 7),
       (7171, '7171', 'Wickelraum', 'Baby Changing Room', 717, 1, 2),
       (7172, '7172', 'Wickelraum mit Waschtisch und Abluft', 'Baby Changing Room w/ Ventilation & Wash Table', 717, 3, 5),
       (7181, '7181', 'Schminkraum', 'Makeup Room', 718, 2, 3),
       (7182, '7182', 'Schminkraum mit Waschtisch und Abluft', 'Makeup Room w/ Ventilation & Wash Table', 718, 3, 5),
       (7191, '7191', 'Putzraum mit Ausguss', 'Cleaning Closet', 719, 2, 3),
       (7192, '7192', 'Putzraum mit Ausguss und Abluft', 'Cleaning Closet w/ Ventilation', 719, 3, 5),
       (7193, '7193', 'Putzraum mit Ausguss, besonderen hygienischen und RLT-Anforderungen', 'Cleaning Closet w/ Heightened Hygiene & Special Ventilation Requirements', 719, 4, 6),
       (7211, '7211', 'Einzelumkleideraum', 'Dressing Room, Individual', 721, 1, 2),
       (7212, '7212', 'Einzelumkleideraum mit Abluft', 'Dressing Room, Individual w/ Ventilation', 721, 2, 4),
       (7213, '7213', 'Einzelumkleideraum mit Waschtisch und Abluft', 'Dressing Room, Individual w/ Ventilation & Wash Table', 721, 3, 5),
       (7221, '7221', 'Gruppenumkleideraum', 'Dressing Room, Group', 722, 1, 2),
       (7222, '7222', 'Gruppenumkleideraum mit Waschtisch und Abluft', 'Dressing Room, Group w/ Ventilation & Wash Table', 722, 3, 5),
       (7231, '7231', 'Umkleideschleuse', 'Dressing Corridor', 723, 2, 3),
       (7232, '7232', 'Umkleideschleuse mit Waschtisch und Abluft', 'Dressing Corridor w/ Ventilation & Wash Table', 723, 3, 5),
       (7233, '7233', 'Umkleideschleuse im OP-Bereich', 'Dressing Corridor, Surgical', 723, 4, 6),
       (7234, '7234', 'Umkleideschleuse im strahlengeschützten Bereich', 'Dressing Corridor, Radiation Protected Area', 723, 4, 6),
       (7235, '7235', 'Personalschleuse im strahlengeschützten Bereich mit Dekontaminationsdusche', 'Personnel Corridor, Radiation Protected Area w/ Decontamination Shower', 723, 5, 7),
       (7236, '7236', 'Personalschleuse mit hygienischen und RLT-Anforderungen', 'Personnel Corridor w/ Hygiene & Ventilation Requirements', 723, 5, 7),
       (7237, '7237', 'Personalschleuse mit bes. baukonstr., hygien. u. RLT-Anforderungen u. Strahlenschutz', '', 723, 6, 8),
       (7241, '7241', 'Künstlergarderobe', 'Dressing Room, Artist', 724, 2, 3),
       (7242, '7242', 'Künstlergarderobe mit Waschtisch und RLT-Anforderungen', 'Dressing Room, Artist w/ Ventilation & Wash Table', 724, 3, 5),
       (7251, '7251', 'Garderobenfläche', 'Wardrobe Area', 725, 1, 2),
       (7252, '7252', 'Garderobenraum/-fläche mit Abluft', 'Wardrobe Area / Room w/ Ventilation', 725, 2, 4),
       (7261, '7261', 'Schrankraum', 'Locker Room', 726, 1, 2),
       (7262, '7262', 'Schrankraum mit Abluft', 'Locker Room w/ Ventilation', 726, 2, 3),
       (7311, '7311', 'Abstellraum', 'Storage', 731, 1, 2),
       (7312, '7312', 'Abstellraum mit Abluft', 'Storage w/ Ventilation', 731, 2, 3),
       (7313, '7313', 'Abstellraum mit besonderen RLT-Anforderungen', 'Storage w/ Special Ventilation', 731, 2, 4),
       (7321, '7321', 'Kellerabstellraum', 'Storage, Basement', 732, 1, 1),
       (7322, '7322', 'Hausmeisterkeller', 'Building Maintenance Basement, ', 732, 1, 2),
       (7323, '7323', 'Kellerabstellraum mit Abluft', 'Storage, Basement w/ Ventilation', 732, 2, 3),
       (7330, '7330', 'Dachabstellraum', 'Storage, Attic', 733, 1, 1),
       (7340, '7340', 'Fahrrad-/Kinderwagenraum', 'Storage, Bike & Buggy', 734, 1, 2),
       (7350, '7350', 'Krankentransportgeräteraum', 'Storage, Patient Transport Devices', 735, 2, 3),
       (7361, '7361', 'Container-Stauraum mit Abluft', 'Storage, Container w/ Ventilation', 736, 2, 3),
       (7362, '7362', 'KFA/AWT-Station und Containerbahnhof', 'Rail Vehicle & Container Station', 736, 3, 5),
       (7371, '7371', 'Müllsammelraum', 'Trash Collection Room', 737, 1, 1),
       (7372, '7372', 'Müllsammelraum mit Abluft', 'Trash Collection Room w/ Ventilation', 737, 3, 5),
       (7411, '7411', 'Abstellfläche für Kfz in eigenem Garagengebäude', 'Parking Space, Garage Building', 741, 1, 1),
       (7412, '7412', 'Abstellfläche für Kfz im Gebäude integriert', 'Parking Space, Integrated', 741, 1, 2),
       (7413, '7413', 'Abstellfläche für Kfz im Gebäude integriert mit RLT-Anforderungen', 'Parking Space, Integrated w/ Ventilation', 741, 2, 3),
       (7420, '7420', 'Großkraftfahrzeugabstellfläche', 'Parking Space, Large Vehicle', 742, 1, 2),
       (7430, '7430', 'Großgeräteabstellfläche', 'Parking Space, Large Equipment', 743, 1, 1),
       (7440, '7440', 'Kettenfahrzeugabstellfläche', 'Parking Space, Tracked Vehicle', 744, 1, 2),
       (7450, '7450', 'Schienenfahrzeugabstellfläche', 'Parking Space, Rail Vehicle', 745, 1, 2),
       (7460, '7460', 'Luftfahrzeugabstellfläche', 'Parking Space, Aircraft', 746, 1, 2),
       (7470, '7470', 'Wasserfahrzeugabstellfläche', 'Parking Space, Aquatic', 747, 1, 2),
       (7510, '7510', 'Bahnsteig', 'Train Platform', 751, 1, 2),
       (7530, '7530', 'Flugsteig', 'Airway Gate', 753, 3, 5),
       (7540, '7540', 'Landesteg', 'Jetty', 754, 1, 2),
       (7610, '7610', 'Raum für Abwasseraufbereitung und -Beseitigung', 'Service Provider Room, Waste Water Preparation & Disposal', 761, 4, 6),
       (7620, '7620', 'Raum für Wasserversorgung', 'Service Provider Room, Water Supply', 762, 4, 6),
       (7630, '7630', 'Raum für Wärmeversorgung', 'Service Provider Room, Heating', 763, 4, 6),
       (7640, '7640', 'Raum für Versorgung mit Gasen und Flüssigkeiten', 'Service Provider Room, Fluids & Gases', 764, 4, 6),
       (7650, '7650', 'Raum für Stromversorgung', 'Service Provider Room, Power', 765, 5, 7),
       (7660, '7660', 'Raum für Fernmeldetechnik', 'Service Provider Room, Communications', 766, 4, 6),
       (7670, '7670', 'Raum für Luft-/Kälteversorgung', 'Service Provider Room, Air Conditioning & Ventilation', 767, 6, 8),
       (7680, '7680', 'Raum für Förderanlagen', 'Service Provider Room, Conveyance', 768, 5, 7),
       (7690, '7690', 'Raum für sonstige Ver- und Entsorgung', 'Service Provider Room, Supply & Disposal', 769, 5, 7),
       (7710, '7710', 'Luftschutzraum', 'Bunker, Air Raid', 771, 1, 1),
       (7720, '7720', 'Strahlenschutzraum', 'Bunker, Nuclear', 772, 5, 7),
       (8100, '8100', 'Abwasseraufbereitung und -beseitigung', 'Wastewater Preparation & Disposal Room', 810, 8, 10),
       (8200, '8200', 'Wasserversorgung', 'Water Supply Room', 820, 8, 10),
       (8300, '8300', 'Heizung und Brauchwassererwärmung', 'Heating Room', 830, 8, 10),
       (8400, '8400', 'Gase und Flüssigkeiten (außer für Heizzwecke)', 'Fluids & Gases Supply Room', 840, 8, 10),
       (8500, '8500', 'Elektrische Stromversorgung', 'Power Supply Room', 850, 8, 10),
       (8600, '8600', 'Fernmeldetechnik', 'Communication Room', 860, 8, 10),
       (8700, '8700', 'Raumlufttechnische Anlagen', 'Ventilation Room', 870, 8, 10),
       (8800, '8800', 'Aufzugs- und Förderanlagen', 'Elevator & Conveyance Equipment Room', 880, 8, 10),
       (8910, '8910', 'Hausanschlussraum', 'House Connection Room', 891, 8, 10),
       (8920, '8920', 'Installationsraum', 'Installation Room', 892, 8, 10),
       (8930, '8930', 'Installationsschacht', 'Installation Shaft', 893, 8, 10),
       (8940, '8940', 'Installationskanal', 'Installation Channel', 894, 8, 10),
       (8950, '8950', 'Abfallverbrennungsraum', 'Waste Incineration Room', 895, 8, 10),
       (9110, '9110', 'Flur allgemein', 'Hallway', 911, 9, 11),
       (9130, '9130', 'Vorraum (vor Hotel-, Krankenzimmer)', 'Anteroom', 913, 9, 11),
       (9140, '9140', 'Schleuse (Garagen-, Hörsaal-, Luftdruck-)', 'Access Room', 914, 9, 11),
       (9150, '9150', 'Windfang', 'Vestibule', 915, 9, 11),
       (9160, '9160', 'Eingangshalle', 'Entryway', 916, 9, 11),
       (9170, '9170', 'Rollsteig', 'Moving Walkway', 917, 9, 11),
       (9180, '9180', 'Fluchtweg (Fluchtbalkon, -tunnel, Wartungsbalkon)', 'Escape Corridor', 918, 9, 11),
       (9210, '9210', 'Treppenraum, -lauf, Rampe', 'Stairwell', 921, 9, 12),
       (9220, '9220', 'Treppe in Wohnungen', 'Residential Stairwell', 922, 9, 12),
       (9230, '9230', 'Rolltreppe, -rampe', 'Escalator', 923, 9, 12),
       (9240, '9240', 'Fluchttreppenraum', 'Escape Stairwell', 924, 9, 12),
       (9310, '9310', 'Schacht für Personenaufzug', 'Elevator Shaft, Personnel', 931, 9, 11),
       (9320, '9320', 'Schacht für Materialförderungsanlagen', 'Elevator Shaft, Freight', 932, 9, 11),
       (9330, '9330', 'Tunnel für Materialförderanlagen', 'Freight Tunnel', 933, 9, 11),
       (9340, '9340', 'Abwurfschacht', 'Chute', 934, 9, 11),
       (9410, '9410', 'Fahrzeugverkehrsfläche horizontal', 'Vehicle Traffic Area, Level', 941, 9, 11),
       (9420, '9420', 'Fahrzeugverkehrsfläche geneigt (Rampe)', 'Vehicle Traffic Area, Inclined', 942, 9, 11);

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

#Normed
INSERT INTO `#__organizer_use_groups` (`id`, `name_de`, `name_en`)
VALUES (1, 'Wohnen und Aufenthalt', 'Residential and Social'),
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
    ADD CONSTRAINT `roomtype_codeID_fk` FOREIGN KEY (`usecode`) REFERENCES `#__organizer_use_codes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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

ALTER TABLE `#__organizer_use_codes` ADD CONSTRAINT `usecode_keyID_fk` FOREIGN KEY (`keyID`) REFERENCES `#__organizer_roomkeys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;