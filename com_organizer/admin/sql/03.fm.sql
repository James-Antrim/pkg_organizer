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
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_buildings` (`id`, `campusID`, `name`, `location`, `address`, `propertyType`)
SELECT DISTINCT `id`, `campusID`, `name`, `location`, `address`, `propertyType`
FROM `v7ocf_thm_organizer_buildings`;

ALTER TABLE `v7ocf_organizer_buildings`
    ADD CONSTRAINT `building_campusID_fk` FOREIGN KEY (`campusID`) REFERENCES `v7ocf_organizer_campuses` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion

# region rooms
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_rooms` (
    `id`         INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `alias`      VARCHAR(255)                 DEFAULT NULL,
    `code`       VARCHAR(60)                  DEFAULT NULL COLLATE utf8mb4_bin,
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
    COLLATE = utf8mb4_unicode_ci;

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
    COLLATE = utf8mb4_unicode_ci;

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