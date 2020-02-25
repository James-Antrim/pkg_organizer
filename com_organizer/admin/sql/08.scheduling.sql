# region holidays
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_holidays` (
    `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name_de`   VARCHAR(150)        NOT NULL,
    `name_en`   VARCHAR(150)        NOT NULL,
    `startDate` DATE                NOT NULL,
    `endDate`   DATE                NOT NULL,
    `type`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 3,
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_holidays` (`id`, `name_de`, `name_en`, `startDate`, `endDate`, `type`)
VALUES (1, 'Christi Himmelfahrt', 'Ascension Day', '2019-05-30', '2019-05-30', 3),
       (2, 'Weihnachten', 'Christmas Day ', '2019-12-25', '2019-12-26', 3),
       (3, 'Tag der Deutschen Einheit', 'Day of German Unity', '2019-10-03', '2019-10-03', 3),
       (4, 'Ostermontag', 'Easter Monday', '2019-04-22', '2019-04-22', 3),
       (5, 'Karfreitag', 'Good Friday', '2019-04-19', '2019-04-19', 3),
       (6, 'Tag der Arbeit', 'May Day', '2019-05-01', '2019-05-01', 3),
       (7, 'Neujahrstag', 'New Year''s Day', '2019-01-01', '2019-01-01', 3),
       (8, 'Pfingstmontag', 'Whit Monday', '2019-06-10', '2019-06-10', 3),
       (9, 'Fronleichnam', 'Corpus Christi', '2019-06-20', '2019-06-20', 3),
       (10, 'Neujahrstag', 'New Year''s Day', '2020-01-01', '2020-01-01', 3),
       (11, 'Karfreitag', 'Good Friday', '2020-04-10', '2020-04-10', 3),
       (12, 'Ostermontag', 'Easter Monday', '2020-04-13', '2020-04-13', 3),
       (13, 'Tag der Arbeit', 'May Day', '2020-05-01', '2020-05-01', 3),
       (14, 'Christi Himmelfahrt', 'Ascension Day', '2020-05-21', '2020-05-21', 3),
       (15, 'Pfingstmontag', 'Whit Monday', '2020-06-01', '2020-06-01', 3),
       (16, 'Fronleichnam', 'Corpus Christi', '2020-06-11', '2020-06-11', 3),
       (17, 'Tag der Deutschen Einheit', 'Day of German Unity', '2020-10-03', '2020-10-03', 3),
       (18, 'Weihnachten', 'Christmas Day', '2020-12-25', '2020-12-27', 3),
       (19, 'Neujahrstag', 'New Year''s Day', '2021-01-01', '2021-01-01', 3),
       (20, 'Karfreitag', 'Good Friday', '2021-04-02', '2021-04-02', 3),
       (21, 'Ostermontag', 'Easter Monday', '2021-04-05', '2021-04-05', 3),
       (22, 'Tag der Arbeit', 'May Day', '2021-05-01', '2021-05-01', 3),
       (23, 'Christi Himmelfahrt', 'Ascension Day', '2021-05-13', '2021-05-13', 3),
       (24, 'Pfingstmontag', 'Whit Monday', '2021-05-24', '2021-05-24', 3),
       (25, 'Fronleichnam', 'Corpus Christi', '2021-06-03', '2021-06-03', 3),
       (26, 'Weihnachten', 'Christmas Day', '2021-12-25', '2021-12-26', 3),
       (27, 'Tag der Deutschen Einheit', 'Day of German Unity', '2021-10-03', '2021-10-03', 3),
       (28, 'Tag der Deutschen Einheit', 'Day of German Unity', '2022-10-03', '2022-10-03', 3),
       (29, 'Neujahrstag', 'New Year''s Day', '2022-01-01', '2022-01-01', 3),
       (30, 'Karfreitag', 'Good Friday', '2022-04-15', '2022-04-15', 3),
       (31, 'Ostermontag', 'Easter Monday', '2022-04-18', '2022-04-18', 3),
       (32, 'Tag der Arbeit', 'May Day', '2022-05-01', '2022-05-01', 3),
       (33, 'Christi Himmelfahrt', 'Ascension Day', '2022-05-26', '2022-05-26', 3),
       (34, 'Pfingstmontag', 'Whit Monday', '2022-06-06', '2022-06-06', 3),
       (35, 'Fronleichnam', 'Corpus Christi', '2022-06-16', '2022-06-16', 3),
       (36, 'Weihnachten', 'Christmas Day', '2022-12-25', '2022-12-26', 3),
       (37, 'Neujahrstag', 'New Year''s Day', '2023-01-01', '2023-01-01', 3),
       (38, 'Karfreitag', 'Good Friday', '2023-04-07', '2023-04-07', 3),
       (39, 'Ostermontag', 'Easter Monday', '2023-04-10', '2023-04-10', 3),
       (40, 'Tag der Arbeit', 'May Day', '2023-05-01', '2023-05-01', 3),
       (41, 'Christi Himmelfahrt', 'Ascension Day', '2023-05-18', '2023-05-18', 3),
       (42, 'Pfingstmontag', 'Whit Monday', '2023-05-29', '2023-05-29', 3),
       (43, 'Fronleichnam', 'Corpus Christi', '2023-06-08', '2023-06-08', 3),
       (44, 'Tag der Deutschen Einheit', 'Day of German Unity', '2023-10-03', '2023-10-03', 3),
       (45, 'Weihnachten', 'Christmas Day', '2023-12-25', '2023-12-26', 3),
       (46, 'Neujahrstag', 'New Year''s Day', '2024-01-01', '2024-01-01', 3),
       (47, 'Karfreitag', 'Good Friday', '2024-03-29', '2024-03-29', 3),
       (48, 'Ostermontag', 'Easter Monday', '2024-04-01', '2024-04-01', 3),
       (49, 'Tag der Arbeit', 'May Day', '2024-05-01', '2024-05-01', 3),
       (50, 'Christi Himmelfahrt', 'Ascension Day', '2024-05-09', '2024-05-09', 3),
       (51, 'Pfingstmontag', 'Whit Monday', '2024-05-20', '2024-05-20', 3),
       (52, 'Fronleichnam', 'Corpus Christi', '2024-05-30', '2024-05-30', 3),
       (53, 'Tag der Deutschen Einheit', 'Day of German Unity', '2024-10-03', '2024-10-03', 3),
       (54, 'Weihnachten', 'Christmas Day', '2024-12-25', '2024-12-26', 3);
# endregion

# region runs
CREATE TABLE IF NOT EXISTS `v7ocf_organizer_runs` (
    `id`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name_de` VARCHAR(150)     NOT NULL,
    `name_en` VARCHAR(150)     NOT NULL,
    `run`     TEXT,
    `termID`  INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `termID` (`termID`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_runs` (`id`, `name_de`, `name_en`, `termID`, `run`)
VALUES (1, 'Sommersemester', 'Summer Semester', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-04-06\",\"endDate\":\"2020-04-09\"},\"2\":{\"startDate\":\"2020-04-14\",\"endDate\":\"2020-04-17\"},\"3\":{\"startDate\":\"2020-04-20\",\"endDate\":\"2020-04-24\"},\"4\":{\"startDate\":\"2020-04-27\",\"endDate\":\"2020-04-30\"},\"5\":{\"startDate\":\"2020-05-04\",\"endDate\":\"2020-05-08\"},\"6\":{\"startDate\":\"2020-05-11\",\"endDate\":\"2020-05-15\"},\"7\":{\"startDate\":\"2020-05-18\",\"endDate\":\"2020-05-20\"},\"8\":{\"startDate\":\"2020-05-25\",\"endDate\":\"2020-05-29\"},\"9\":{\"startDate\":\"2020-06-08\",\"endDate\":\"2020-06-10\"},\"10\":{\"startDate\":\"2020-06-15\",\"endDate\":\"2020-06-19\"},\"11\":{\"startDate\":\"2020-06-22\",\"endDate\":\"2020-06-26\"},\"12\":{\"startDate\":\"2020-06-29\",\"endDate\":\"2020-07-03\"},\"13\":{\"startDate\":\"2020-07-06\",\"endDate\":\"2020-07-10\"}}}'),
       (2, 'Blockveranstaltungen 1', 'Block Event 1', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-07-27\",\"endDate\":\"2020-08-01\"},\"2\":{\"startDate\":\"2020-08-03\",\"endDate\":\"2020-08-08\"}}}'),
       (3, 'Blockveranstaltungen 2', 'Block Event 2', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-08-10\",\"endDate\":\"2020-08-15\"},\"2\":{\"startDate\":\"2020-08-17\",\"endDate\":\"2020-08-22\"}}}'),
       (4, 'Blockveranstaltungen 3', 'Block Event 3', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-08-24\",\"endDate\":\"2020-08-29\"},\"2\":{\"startDate\":\"2020-08-31\",\"endDate\":\"2020-09-05\"}}}'),
       (5, 'Blockveranstaltungen 4', 'Block Event 4', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-09-07\",\"endDate\":\"2020-09-12\"}}}'),
       (6, 'Einführungswoche', 'Introduction Week', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-03-30\",\"endDate\":\"2020-04-03\"}}}'),
       (7, 'Klausurwoche 1', 'Examination Week 1', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-07-13\",\"endDate\":\"2020-07-18\"}}}'),
       (8, 'Klausurwoche 2', 'Examination Week 2', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-07-20\",\"endDate\":\"2020-07-25\"}}}'),
       (9, 'Klausurwoche 3', 'Examination Week 3', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-09-14\",\"endDate\":\"2020-09-19\"}}}'),
       (10, 'Klausurwoche 4', 'Examination Week 4', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-09-21\",\"endDate\":\"2020-09-26\"}}}'),
       (11, 'Projektwoche', 'Project Week', 11,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-06-02\",\"endDate\":\"2020-06-06\"},\"2\":{\"startDate\":\"2020-06-12\",\"endDate\":\"2020-06-12\"}}}'),
       (12, 'Sommersemester', 'Summer Semester', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-04-12\",\"endDate\":\"2021-04-16\"},\"2\":{\"startDate\":\"2021-04-19\",\"endDate\":\"2021-04-23\"},\"3\":{\"startDate\":\"2021-04-26\",\"endDate\":\"2021-04-30\"},\"4\":{\"startDate\":\"2021-05-03\",\"endDate\":\"2021-05-07\"},\"5\":{\"startDate\":\"2021-05-10\",\"endDate\":\"2021-05-12\"},\"6\":{\"startDate\":\"2021-05-17\",\"endDate\":\"2021-05-21\"},\"7\":{\"startDate\":\"2021-05-25\",\"endDate\":\"2021-05-28\"},\"8\":{\"startDate\":\"2021-06-07\",\"endDate\":\"2021-06-11\"},\"9\":{\"startDate\":\"2021-06-14\",\"endDate\":\"2021-06-18\"},\"10\":{\"startDate\":\"2021-06-21\",\"endDate\":\"2021-06-25\"},\"11\":{\"startDate\":\"2021-06-28\",\"endDate\":\"2021-07-02\"},\"12\":{\"startDate\":\"2021-07-05\",\"endDate\":\"2021-07-09\"},\"13\":{\"startDate\":\"2021-07-12\",\"endDate\":\"2021-07-16\"}}}'),
       (13, 'Blockveranstaltungen 1', 'Block Event 1', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-08-02\",\"endDate\":\"2021-08-07\"},\"2\":{\"startDate\":\"2021-08-09\",\"endDate\":\"2021-08-14\"}}}'),
       (14, 'Blockveranstaltungen 2', 'Block Event 2', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-08-16\",\"endDate\":\"2021-08-21\"},\"2\":{\"startDate\":\"2021-08-23\",\"endDate\":\"2021-08-28\"}}}'),
       (15, 'Blockveranstaltungen 3', 'Block Event 3', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-08-30\",\"endDate\":\"2021-09-04\"},\"2\":{\"startDate\":\"2021-09-06\",\"endDate\":\"2021-09-11\"}}}'),
       (16, 'Blockveranstaltungen 4', 'Block Event 4', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-09-13\",\"endDate\":\"2021-09-18\"}}}'),
       (17, 'Klausurwoche 1', 'Examination Week 1', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-07-19\",\"endDate\":\"2021-07-24\"}}}'),
       (18, 'Klausurwoche 2', 'Examination Week 2', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-07-26\",\"endDate\":\"2021-07-31\"}}}'),
       (19, 'Klausurwoche 3', 'Examination Week 3', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-09-20\",\"endDate\":\"2021-09-25\"}}}'),
       (20, 'Klausurwoche 4', 'Examination Week 4', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-09-27\",\"endDate\":\"2021-09-30\"}}}'),
       (21, 'Projektwoche', 'Project Week', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-05-31\",\"endDate\":\"2021-06-02\"},\"2\":{\"startDate\":\"2021-06-04\",\"endDate\":\"2021-06-04\"}}}'),
       (22, 'Einführungswoche', 'Introduction Week', 13,
        '{\"runs\":{\"1\":{\"startDate\":\"2021-04-06\",\"endDate\":\"2021-04-09\"}}}'),
       (23, 'Wintersemester', 'Winter Semester', 10,
        '{\"runs\":{\"1\":{\"startDate\":\"2019-10-07\",\"endDate\":\"2019-10-11\"},\"2\":{\"startDate\":\"2019-10-14\",\"endDate\":\"2019-10-18\"},\"3\":{\"startDate\":\"2019-10-21\",\"endDate\":\"2019-10-25\"},\"4\":{\"startDate\":\"2019-10-28\",\"endDate\":\"2019-11-01\"},\"5\":{\"startDate\":\"2019-11-04\",\"endDate\":\"2019-11-08\"},\"6\":{\"startDate\":\"2019-11-11\",\"endDate\":\"2019-11-15\"},\"7\":{\"startDate\":\"2019-11-18\",\"endDate\":\"2019-11-22\"},\"8\":{\"startDate\":\"2019-11-25\",\"endDate\":\"2019-11-29\"},\"9\":{\"startDate\":\"2019-12-02\",\"endDate\":\"2019-12-06\"},\"10\":{\"startDate\":\"2019-12-09\",\"endDate\":\"2019-12-13\"},\"11\":{\"startDate\":\"2019-12-16\",\"endDate\":\"2019-12-20\"},\"12\":{\"startDate\":\"2020-01-13\",\"endDate\":\"2020-01-17\"},\"13\":{\"startDate\":\"2020-01-20\",\"endDate\":\"2020-01-24\"}}}'),
       (24, 'Blockveranstaltungen 1', 'Block Event 1', 10,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-02-10\",\"endDate\":\"2020-02-15\"},\"2\":{\"startDate\":\"2020-02-17\",\"endDate\":\"2020-02-22\"}}}'),
       (25, 'Blockveranstaltungen 2', 'Block Event 2', 10,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-02-24\",\"endDate\":\"2020-02-29\"},\"2\":{\"startDate\":\"2020-03-02\",\"endDate\":\"2020-03-07\"}}}'),
       (26, 'Blockveranstaltungen 3', 'Block Event 3', 10,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-03-09\",\"endDate\":\"2020-03-14\"},\"2\":{\"startDate\":\"2020-03-16\",\"endDate\":\"2020-03-21\"}}}'),
       (27, 'Einführungswoche', 'Introduction Week', 10,
        '{\"runs\":{\"1\":{\"startDate\":\"2019-09-30\",\"endDate\":\"2019-10-02\"},\"2\":{\"startDate\":\"2019-10-04\",\"endDate\":\"2019-10-04\"}}}'),
       (28, 'Klausurwoche 1', 'Examination Week 1', 10,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-01-27\",\"endDate\":\"2020-02-01\"}}}'),
       (29, 'Klausurwoche 2', 'Examination Week 2', 10,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-02-03\",\"endDate\":\"2020-02-08\"}}}'),
       (30, 'Klausurwoche 3', 'Examination Week 3', 10,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-03-23\",\"endDate\":\"2020-03-28\"}}}'),
       (31, 'Projektwoche', 'Project Week', 10,
        '{\"runs\":{\"1\":{\"startDate\":\"2020-01-06\",\"endDate\":\"2020-01-10\"}}}');

ALTER TABLE `v7ocf_organizer_runs`
    ADD CONSTRAINT `run_termID_fk` FOREIGN KEY (`termID`) REFERENCES `v7ocf_organizer_terms` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

ALTER TABLE `v7ocf_organizer_units`
    ADD CONSTRAINT `unit_runID_fk` FOREIGN KEY (`runID`) REFERENCES `v7ocf_organizer_runs` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
# endregion