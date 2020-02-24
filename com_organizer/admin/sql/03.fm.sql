# region campuses
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_campuses` (
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
    UNIQUE INDEX `alias` (`alias`),
    INDEX `gridID` (`gridID`),
    INDEX `parentID` (`parentID`),
    UNIQUE INDEX `englishName` (`parentID`, `name_en`),
    UNIQUE INDEX `germanName` (`parentID`, `name_de`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_campuses` (`id`, `parentID`, `name_de`, `name_en`, `isCity`, `location`, `address`,
                                               `city`, `zipCode`, `gridID`)
SELECT DISTINCT `id`,
                `parentID`,
                `name_de`,
                `name_en`,
                `isCity`,
                `location`,
                `address`,
                `city`,
                `zipCode`,
                `gridID`
FROM `v7ocf_thm_organizer_campuses`;

ALTER TABLE `v7ocf_organizer_campuses`
    ADD CONSTRAINT `campus_gridID_fk` FOREIGN KEY (`gridID`) REFERENCES `v7ocf_organizer_grids` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `campus_parentID_fk` FOREIGN KEY (`parentID`) REFERENCES `v7ocf_organizer_campuses` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion

# region buildings
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_buildings` (
    `id`           INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `campusID`     INT(11) UNSIGNED             DEFAULT NULL,
    `name`         VARCHAR(150)        NOT NULL,
    `active`       TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `address`      VARCHAR(255)        NOT NULL,
    `location`     VARCHAR(20)         NOT NULL,
    `propertyType` INT(1) UNSIGNED     NOT NULL DEFAULT 0
        COMMENT '0 - new/unknown | 1 - owned | 2 - rented/leased',
    PRIMARY KEY (`id`),
    INDEX `campusID` (`campusID`),
    UNIQUE INDEX `entry` (`campusID`, `name`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_buildings` (`id`, `campusID`, `name`, `location`, `address`, `propertyType`)
SELECT DISTINCT `id`, `campusID`, `name`, `location`, `address`, `propertyType`
FROM `v7ocf_thm_organizer_buildings`;

ALTER TABLE `v7ocf_organizer_buildings`
    ADD CONSTRAINT `building_campusID_fk` FOREIGN KEY (`campusID`) REFERENCES `v7ocf_organizer_campuses` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion

# region roomtypes
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_roomtypes` (
    `id`             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`          VARCHAR(255)                 DEFAULT NULL,
    `code`           VARCHAR(60)         NOT NULL,
    `name_de`        VARCHAR(150)        NOT NULL,
    `name_en`        VARCHAR(150)        NOT NULL,
    `description_de` TEXT,
    `description_en` TEXT,
    `minCapacity`    INT(4) UNSIGNED              DEFAULT NULL,
    `maxCapacity`    INT(4) UNSIGNED              DEFAULT NULL,
    `suppress`       TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `alias` (`alias`),
    UNIQUE INDEX `code` (`code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_roomtypes` (`id`, `code`, `name_de`, `name_en`, `description_de`, `description_en`,
                                                `minCapacity`, `maxCapacity`)
SELECT DISTINCT `id`,
                `gpuntisID`,
                `name_de`,
                `name_en`,
                `description_de`,
                `description_en`,
                `min_capacity`,
                `max_capacity`
FROM `v7ocf_thm_organizer_room_types`;

UPDATE `v7ocf_organizer_roomtypes`
SET `suppress` = 1
WHERE `code` = 'BR';
# endregion

# region rooms
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_rooms` (
    `id`         INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`      VARCHAR(255)                 DEFAULT NULL,
    `code`       VARCHAR(60)                  DEFAULT NULL,
    `name`       VARCHAR(150)        NOT NULL,
    `active`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `buildingID` INT(11) UNSIGNED             DEFAULT NULL,
    `capacity`   INT(4) UNSIGNED              DEFAULT NULL,
    `roomtypeID` INT(11) UNSIGNED             DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `alias` (`alias`),
    UNIQUE INDEX `code` (`code`),
    INDEX `buildingID` (`buildingID`),
    INDEX `roomtypeID` (`roomtypeID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_rooms` (`id`, `buildingID`, `code`, `name`, `roomtypeID`, `capacity`)
SELECT DISTINCT `id`, `buildingID`, `gpuntisID`, `name`, `typeID`, `capacity`
FROM `v7ocf_thm_organizer_rooms`;

ALTER TABLE `v7ocf_organizer_rooms`
    ADD CONSTRAINT `room_buildingID_fk` FOREIGN KEY (`buildingID`) REFERENCES `v7ocf_organizer_buildings` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT `room_roomtypeID_fk` FOREIGN KEY (`roomtypeID`) REFERENCES `v7ocf_organizer_roomtypes` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion

# region monitors
# roomID defaults to null so the entry does not get deleted with the room
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_monitors` (
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
    INDEX `roomID` (`roomID`),
    UNIQUE INDEX `ip` (`ip`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_bin;

INSERT IGNORE INTO `v7ocf_organizer_monitors` (`id`, `ip`, `roomID`, `useDefaults`, `display`, `scheduleRefresh`,
                                               `contentRefresh`, `interval`, `content`)
SELECT DISTINCT `id`,
                `ip`,
                `roomID`,
                `useDefaults`,
                `display`,
                `schedule_refresh`,
                `content_refresh`,
                `interval`,
                `content`
FROM `v7ocf_thm_organizer_monitors`;

ALTER TABLE `v7ocf_organizer_monitors`
    ADD CONSTRAINT `monitor_roomID_fk` FOREIGN KEY (`roomID`) REFERENCES `v7ocf_organizer_rooms` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion