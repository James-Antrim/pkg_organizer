# region colors
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_colors` (
    `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name_de` VARCHAR(150)     NOT NULL,
    `name_en` VARCHAR(150)     NOT NULL,
    `color`   VARCHAR(7)       NOT NULL,
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_colors`
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
# endregion

# region degrees
CREATE TABLE IF NOT EXISTS `#__organizer_degrees` (
    `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias`        VARCHAR(255) DEFAULT NULL,
    `abbreviation` VARCHAR(25)      NOT NULL,
    `code`         VARCHAR(60)      NOT NULL,
    `name`         VARCHAR(150)     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `alias` (`alias`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_degrees`
VALUES (2, 'B.Eng.', 'beng', 'BE', 'Bachelor of Engineering'),
       (3, 'B.Sc.', 'bsc', 'BS', 'Bachelor of Science'),
       (4, 'B.A.', 'ba', 'BA', 'Bachelor of Arts'),
       (5, 'M.Eng.', 'meng', 'ME', 'Master of Engineering'),
       (6, 'M.Sc.', 'msc', 'MS', 'Master of Science'),
       (7, 'M.A.', 'ma', 'MA', 'Master of Arts'),
       (8, 'M.B.A.', 'mba', 'MB', 'Master of Business Administration and Engineering'),
       (9, 'M.Ed.', 'med', 'MH', 'Master of Education');
# endregion

# region fields
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_fields` (
    `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias`   VARCHAR(255) DEFAULT NULL,
    `code`    VARCHAR(60)      NOT NULL,
    `name_de` VARCHAR(150)     NOT NULL,
    `name_en` VARCHAR(150)     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `alias` (`alias`),
    UNIQUE INDEX `code` (`code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_fields` (`id`, `code`, `name_de`, `name_en`)
SELECT DISTINCT `id`, `gpuntisID`, `field_de`, `field_en`
FROM `v7ocf_thm_organizer_fields`;
# endregion

# region frequencies
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_frequencies` (
    `id`      INT(1) UNSIGNED NOT NULL,
    `name_de` VARCHAR(150)    NOT NULL,
    `name_en` VARCHAR(150)    NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE `name_de` (`name_de`),
    UNIQUE `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_frequencies`
VALUES (0, 'Nach Termin', 'By Appointment'),
       (1, 'Nur im Sommersemester', 'Only Spring/Summer Term'),
       (2, 'Nur im Wintersemester', 'Only Fall/Winter Term'),
       (3, 'Jedes Semester', 'Semesterly'),
       (4, 'Nach Bedarf', 'As Needed'),
       (5, 'Einmal im Jahr', 'Yearly');
# endregion

# region grids
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_grids` (
    `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `code`      VARCHAR(60)         NOT NULL,
    `name_de`   VARCHAR(150)                 DEFAULT NULL,
    `name_en`   VARCHAR(150)                 DEFAULT NULL,
    `grid`      TEXT,
    `isDefault` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `code` (`code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_grids` (`id`, `code`, `name_de`, `name_en`, `grid`, `isDefault`)
SELECT DISTINCT `id`, `gpuntisID`, `name_de`, `name_en`, `grid`, `defaultGrid`
FROM `v7ocf_thm_organizer_grids`;
# endregion

# region organizations
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_organizations` (
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
    UNIQUE INDEX `alias` (`alias`),
    UNIQUE INDEX `abbreviation_de` (`abbreviation_de`),
    UNIQUE INDEX `abbreviation_en` (`abbreviation_en`),
    UNIQUE INDEX `shortName_de` (`shortName_de`),
    UNIQUE INDEX `shortName_en` (`shortName_en`),
    UNIQUE INDEX `name_de` (`name_de`),
    UNIQUE INDEX `name_en` (`name_en`),
    UNIQUE INDEX `fullName_de` (`fullName_de`),
    UNIQUE INDEX `fullName_en` (`fullName_en`),
    INDEX `contactID` (`contactID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_organizations` (`id`, `abbreviation_de`, `abbreviation_en`, `shortName_de`,
                                                    `shortName_en`, `name_de`, `name_en`, `fullName_de`, `fullName_en`,
                                                    `contactEmail`, `alias`, `URL`)
VALUES (1, 'BAU', 'CE', 'FB 01 BAU', 'CE DEPT 01', 'Fachbereich Bauwesen', 'Civil Engineering Department',
        'Fachbereich 01 Bauwesen', 'Civil Engineering Department 01', 'dekanat@bau.thm.de', 'bau',
        'https://www.thm.de/bau'),
       (2, 'EI', 'EIT', 'FB 02 EI', 'EIT DEPT 02', 'Fachbereich Elektro- und Informationstechnik',
        'Electronics and Information Technology', 'Fachbereich 02 Elektro- und Informationstechnik',
        'Electronics and Information Technology Department 02', 'dekanat@ei.thm.de', 'ei', 'https://www.thm.de/ei'),
       (3, 'ME', 'MEES', 'FB 03 ME', 'MEES DEPT 03', 'Fachbereich Maschinenbau und Energietechnik',
        'Mechanical Engineering and Energy Systems Department', 'Fachbereich 03 Maschinenbau und Energietechnik',
        'Mechanical Engineering and Energy Systems Department 03', 'dekanat@me.thm.de', 'me', 'https://www.thm.de/me'),
       (4, 'LSE', 'LSE', 'FB 04 LSE', 'LSE DEPT 04', 'Fachbereich Life Science Engineering',
        'Life Science Engineering Department', 'Fachbereich 04 Life Science Engineering',
        'Life Science Engineering Department 04', 'dekanat@lse.thm.de', 'lse', 'https://www.thm.de/lse'),
       (6, 'GES', 'H', 'FB 05 GES', 'H DEPT 05', 'Fachbereich Gesundheit', 'Health Department',
        'Fachbereich 05 Gesundheit', 'Health Department 05', 'dekanat@ges.thm.de', 'ges', 'https://www.thm.de/ges'),
       (7, 'WIRTSCHAFT', 'BUSINESS', 'FB 07 WIRTSCHAFT', 'BUSINESS DEPT 07', 'THM Business School',
        'THM Business School', 'Fachbereich 07 THM Business School', 'THM Business School Department 07',
        'dekanat@w.thm.de', 'w', 'https://www.w.thm.de'),
       (8, 'MUK', 'MC', 'FB 21 MuK', 'MC DEPT 21', 'Fachbereich Management und Kommunikation',
        'Management and Communication Department', 'Fachbereich 21 Management und Kommunikation',
        'Management and Communication Department 21', 'dekanat@muk.thm.de', 'muk', 'https://www.thm.de/muk'),
       (11, 'STK', 'STK', 'Studienkolleg', 'Studienkolleg', 'Studienkolleg Mittelhessen', 'Studienkolleg Mittelhessen',
        'Studienkolleg Mittelhessen', 'Studienkolleg Mittelhessen', 'studienkolleg@uni-marburg.de', 'stk',
        'https://www.uni-marburg.de/de/studienkolleg'),
       (12, 'GI', 'GI', 'Campus Gießen', 'Giessen Campus', 'Campus Gießen', 'Giessen Campus', 'Zentralverwaltung Campus Gießen',
        'Central Administration Giessen Campus', 'carmen.momberger@verw.thm.de', 'giessen', 'https://www.thm.de/site/'),
       (13, 'ZDH', 'ZDH', 'Studiumplus', 'Studiumplus', 'Studiumplus', 'Studiumplus',
        'Wissenschaftliches Zentrum Duales Hochschulstudium', 'Wissenschaftliches Zentrum Duales Hochschulstudium',
        'servicepoint@studiumplus.de', 'zdh', 'https://www.studiumplus.de/sp/'),
       (14, 'MNI', 'MNI', 'FB 06 MNI', 'MNI DEPT 06', 'Fachbereich Mathematik, Naturwissenschaften und Informatik',
        'Mathematics, Natural and Information Sciences Department',
        'Fachbereich 06 Mathematik, Naturwissenschaften und Informatik',
        'Mathematics, Natural and Information Sciences Department 06', 'dekanat@mni.thm.de', 'mni',
        'https://www.thm.de/mni'),
       (15, 'IEM', 'IETM', 'FB 11 IEM', 'IETM DEPT 11', 'Fachbereich Informationstechnik-Elektrotechnik-Mechatronik',
        'Information and Electrical Technology, Mechatronics Department',
        'Fachbereich 11 Informationstechnik-Elektrotechnik-Mechatronik',
        'Information and Electrical Technology, MechatronicsDepartment 11', 'dekanat@bau.thm.de', 'bau',
        'https://www.thm.de/iem'),
       (16, 'M', 'M', 'FB 12 M', 'M DEPT 12', 'Fachbereich Maschinenbau, Mechatronik, Materialtechnology',
        'Mechanical Engineering, Mechatronics, Material Technology Department',
        'Fachbereich 12 Maschinenbau, Mechatronik, Materialtechnology',
        'Mechanical Engineering, Mechatronics, Material Technology Department 12', 'dekanat@m.thm.de', 'm',
        'https://www.thm.de/m'),
       (17, 'MND', 'MNDP', 'FB 13 MND', 'MNDP DEPT 13',
        'Fachbereich Mathematik, Naturwissenschaften und Datenverarbeitung',
        'Mathematics, Natural Sciences and Data Processing Department',
        'Fachbereich 13 Mathematik, Naturwissenschaften und Datenverarbeitung',
        'Mathematics, Natural Sciences and Data Processing Department 13', 'dekanat@bau.thm.de', 'bau',
        'https://www.thm.de/mnd'),
       (18, 'WI', 'IE', 'FB 14 WI', 'IE DEPT 14', 'Fachbereich Wirtschaftsingenieurwesen',
        'Industrial Engineering Department', 'Fachbereich 14 Wirtschaftsingenieurwesen',
        'Industrial Engineering  Department 14', 'dekanat@wi.thm.de', 'bau', 'https://www.thm.de/wi'),
       (19, 'FB', 'FB', 'Campus Friedberg', 'Friedberg Campus', 'Campus Friedberg', 'Friedberg Campus', 'Zentralverwaltung Campus Friedberg',
        'Central Administration Friedberg Campus ', 'stundenplaner-fb@thm.de', 'friedberg', 'https://www.thm.de/site');
# endregion

# region field colors
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_field_colors` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `colorID`        INT(11) UNSIGNED NOT NULL,
    `fieldID`        INT(11) UNSIGNED NOT NULL,
    `organizationID` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `entry` (`fieldID`, `organizationID`),
    INDEX `colorID` (`colorID`),
    INDEX `fieldID` (`fieldID`),
    INDEX `organizationID` (`organizationID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

# migrate from associations:
# from groups (plan_pools)
INSERT IGNORE INTO `v7ocf_organizer_field_colors` (`colorID`, `fieldID`, `organizationID`)
SELECT DISTINCT f.`colorID`, f.`id`, dr.`departmentID`
FROM `v7ocf_thm_organizer_fields` AS f
         INNER JOIN `v7ocf_thm_organizer_plan_pools` AS ppo ON ppo.`fieldID` = f.`id`
         INNER JOIN `v7ocf_thm_organizer_department_resources` AS dr ON dr.`programID` = ppo.`programID`
WHERE f.`colorID` IS NOT NULL;

# from events (plan_subjects)
INSERT IGNORE INTO `v7ocf_organizer_field_colors` (`colorID`, `fieldID`, `organizationID`)
SELECT DISTINCT f.`colorID`, f.`id`, l.`departmentID`
FROM `v7ocf_thm_organizer_fields` AS f
         INNER JOIN `v7ocf_thm_organizer_plan_subjects` AS ps ON ps.`fieldID` = f.`id`
         INNER JOIN `v7ocf_thm_organizer_lesson_subjects` AS ls ON ls.`subjectID` = ps.`id`
         INNER JOIN `v7ocf_thm_organizer_lessons` AS l ON l.`id` = ls.`lessonID`
WHERE f.`colorID` IS NOT NULL;

# from pools
INSERT IGNORE INTO `v7ocf_organizer_field_colors` (`colorID`, `fieldID`, `organizationID`)
SELECT DISTINCT f.`colorID`, f.`id`, p.`departmentID`
FROM `v7ocf_thm_organizer_fields` AS f
         INNER JOIN `v7ocf_thm_organizer_pools` AS p ON p.`fieldID` = f.`id`
WHERE f.`colorID` IS NOT NULL;

# from programs
INSERT IGNORE INTO `v7ocf_organizer_field_colors` (`colorID`, `fieldID`, `organizationID`)
SELECT DISTINCT f.`colorID`, f.`id`, p.`departmentID`
FROM `v7ocf_thm_organizer_fields` AS f
         INNER JOIN `v7ocf_thm_organizer_programs` AS p ON p.`fieldID` = f.`id`
WHERE f.`colorID` IS NOT NULL;

# from subjects
INSERT IGNORE INTO `v7ocf_organizer_field_colors` (`colorID`, `fieldID`, `organizationID`)
SELECT DISTINCT f.`colorID`, f.`id`, s.`departmentID`
FROM `v7ocf_thm_organizer_fields` AS f
         INNER JOIN `v7ocf_thm_organizer_subjects` AS s ON s.`fieldID` = f.`id`
WHERE f.`colorID` IS NOT NULL;

ALTER TABLE `v7ocf_organizer_field_colors`
    ADD CONSTRAINT `field_color_colorID_fk` FOREIGN KEY (`colorID`) REFERENCES `v7ocf_organizer_colors` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `field_color_fieldID_fk` FOREIGN KEY (`fieldID`) REFERENCES `v7ocf_organizer_fields` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `field_color_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `v7ocf_organizer_organizations` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion

# region persons
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_persons` (
    `id`       INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`    VARCHAR(255)                 DEFAULT NULL,
    `code`     VARCHAR(60)                  DEFAULT NULL,
    `forename` VARCHAR(255)        NOT NULL DEFAULT '',
    `surname`  VARCHAR(255)        NOT NULL,
    `active`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `suppress` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `title`    VARCHAR(45)         NOT NULL DEFAULT '',
    `username` VARCHAR(150)                 DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `alias` (`alias`),
    UNIQUE INDEX `code` (`code`),
    UNIQUE INDEX `username` (`username`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_persons` (`id`, `code`, `surname`, `forename`, `username`, `title`)
SELECT DISTINCT `id`, `gpuntisID`, `surname`, `forename`, `username`, `title`
FROM `v7ocf_thm_organizer_teachers`;

UPDATE `v7ocf_organizer_persons` AS p
    INNER JOIN `v7ocf_users` AS u ON u.`username` = p.`username`
SET p.`userID` = u.`id`
WHERE p.`username` IS NOT NULL;

ALTER TABLE `v7ocf_organizer_persons`
    ADD CONSTRAINT `person_userID_fk` FOREIGN KEY (`userID`) REFERENCES `v7ocf_users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion

# region associations
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_associations` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `organizationID` INT(11) UNSIGNED NOT NULL,
    `categoryID`     INT(11) UNSIGNED DEFAULT NULL,
    `groupID`        INT(11) UNSIGNED DEFAULT NULL,
    `personID`       INT(11) UNSIGNED DEFAULT NULL,
    `programID`      INT(11) UNSIGNED DEFAULT NULL,
    `poolID`         INT(11) UNSIGNED DEFAULT NULL,
    `subjectID`      INT(11) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `categoryID` (`categoryID`),
    INDEX `eventID` (`eventID`),
    INDEX `groupID` (`groupID`),
    INDEX `organizationID` (`organizationID`),
    INDEX `personID` (`personID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_associations` (`id`, `organizationID`, `personID`)
SELECT (`id`, `departmentID`, `teacherID`)
FROM `v7ocf_thm_organizer_department_resources`
WHERE `teacherID` IS NOT NULL;

ALTER TABLE `v7ocf_organizer_associations`
    ADD CONSTRAINT `association_organizationID_fk` FOREIGN KEY (`organizationID`) REFERENCES `v7ocf_organizer_organizations` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    ADD CONSTRAINT `association_personID_fk` FOREIGN KEY (`personID`) REFERENCES `v7ocf_organizer_persons` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
# endregion