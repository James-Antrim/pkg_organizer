# region no organizer reference

# region blocks
INSERT IGNORE INTO `#__organizer_blocks` (`date`, `dow`, `startTime`, `endTime`)
SELECT DISTINCT `schedule_date`, WEEKDAY(`schedule_date`) + 1, `startTime`, `endTime`
FROM `#__thm_organizer_calendar`;
# endregion

# region fields
INSERT IGNORE INTO `#__organizer_fields` (`id`, `code`, `name_de`, `name_en`)
SELECT DISTINCT `id`, `gpuntisID`, `field_de`, `field_en`
FROM `#__thm_organizer_fields`;
# endregion

# region grids
INSERT IGNORE INTO `#__organizer_grids` (`id`, `code`, `name_de`, `name_en`, `grid`, `isDefault`)
SELECT DISTINCT `id`, `gpuntisID`, `name_de`, `name_en`, `grid`, `defaultGrid`
FROM `#__thm_organizer_grids`;
# endregion

# region holidays, new data
INSERT IGNORE INTO `#__organizer_holidays` (`id`, `name_de`, `name_en`, `startDate`, `endDate`, `type`)
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

# region methods
INSERT IGNORE INTO `#__organizer_methods` (`id`, `code`, `abbreviation_de`, `abbreviation_en`, `name_de`, `name_en`)
SELECT DISTINCT `id`, `gpuntisID`, `abbreviation_de`, `abbreviation_en`, `name_de`, `name_en`
FROM `#__thm_organizer_methods`;
# endregion

# region organizations
INSERT IGNORE INTO `#__organizer_organizations` (`id`, `abbreviation_de`, `abbreviation_en`, `shortName_de`,
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
       (11, 'STK', 'STK', 'STK', 'STK', 'Studienkolleg Mittelhessen', 'Studienkolleg Mittelhessen',
        'Studienkolleg Mittelhessen', 'Studienkolleg Mittelhessen', 'studienkolleg@uni-marburg.de', 'stk',
        'https://www.uni-marburg.de/de/studienkolleg'),
       (12, 'GI', 'GI', 'GI', 'GI', 'Campus Gießen', 'Giessen Campus', 'Zentralverwaltung Campus Gießen',
        'Central Administration Giessen Campus', 'carmen.momberger@verw.thm.de', 'giessen', 'https://www.thm.de/site/'),
       (13, 'ZDH', 'ZDH', 'ZDH', 'ZDH', 'Studiumplus', 'Studiumplus',
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
       (19, 'FB', 'FB', 'FB', 'FB', 'Campus Friedberg', 'Friedberg Campus', 'Zentralverwaltung Campus Friedberg',
        'Central Administration Friedberg Campus ', 'stundenplaner-fb@thm.de', 'friedberg', 'https://www.thm.de/site');
# endregion

# region teachers external reference to users problems with duplicate values in unique columns
INSERT IGNORE INTO `#__organizer_persons` (`id`, `code`, `surname`, `forename`, `username`, `title`)
SELECT DISTINCT `id`, `gpuntisID`, `surname`, `forename`, `username`, `title`
FROM `#__thm_organizer_teachers`;

UPDATE `#__organizer_persons` AS p
    INNER JOIN `#__users` AS u ON u.`username` = p.`username`
SET p.`userID` = u.`id`
WHERE p.`username` IS NOT NULL;
# endregion

# region roomtypes
INSERT IGNORE INTO `#__organizer_roomtypes` (`id`, `code`, `name_de`, `name_en`, `description_de`, `description_en`,
                                             `minCapacity`, `maxCapacity`)
SELECT DISTINCT `id`,
                `gpuntisID`,
                `name_de`,
                `name_en`,
                `description_de`,
                `description_en`,
                `min_capacity`,
                `max_capacity`
FROM `#__thm_organizer_room_types`;

UPDATE `#__organizer_roomtypes`
SET `suppress` = 1
WHERE `code` = 'BR';
# endregion

# region terms
INSERT IGNORE INTO `#__organizer_terms` (`id`, `code`, `startDate`, `endDate`)
SELECT DISTINCT `id`, `name`, `startDate`, `endDate`
FROM `#__thm_organizer_planning_periods`;

UPDATE `#__organizer_terms`
SET `code`        = '19WS',
    `name_de`     = 'WS 2018/19',
    `name_en`     = 'Fall 2018',
    `fullName_de` = 'Wintersemester 2018/19',
    `fullName_en` = 'Fall Term 2018'
WHERE `code` = 'WS19';

UPDATE `#__organizer_terms`
SET `code`        = '19SS',
    `name_de`     = 'SS 2019',
    `name_en`     = 'Spring 2019',
    `fullName_de` = 'Sommersemester 2019',
    `fullName_en` = 'Spring Term 2019'
WHERE `code` = 'SS19';

UPDATE `#__organizer_terms`
SET `code`        = '20WS',
    `name_de`     = 'WS 2019/20',
    `name_en`     = 'Fall 2019',
    `fullName_de` = 'Wintersemester 2019/20',
    `fullName_en` = 'Fall Term 2019'
WHERE `code` = 'WS20';

UPDATE `#__organizer_terms`
SET `code`        = '20SS',
    `name_de`     = 'SS 2020',
    `name_en`     = 'Spring 2020',
    `fullName_de` = 'Sommersemester 2020',
    `fullName_en` = 'Spring Term 2020'
WHERE `code` = 'SS20';

UPDATE `#__organizer_terms`
SET `code`        = '21WS',
    `name_de`     = 'WS 2020/21',
    `name_en`     = 'Fall 2020',
    `fullName_de` = 'Wintersemester 2020/21',
    `fullName_en` = 'Fall Term 2020'
WHERE `code` = 'WS21';

UPDATE `#__organizer_terms`
SET `code`        = '21SS',
    `name_de`     = 'SS 2021',
    `name_en`     = 'Spring 2021',
    `fullName_de` = 'Sommersemester 2021',
    `fullName_en` = 'Spring Term 2021'
WHERE `code` = 'SS21';
# endregion

# endregion

# region single reference

# region campuses references grids
INSERT IGNORE INTO `#__organizer_campuses` (`id`, `parentID`, `name_de`, `name_en`, `isCity`, `location`, `address`,
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
FROM `#__thm_organizer_campuses`;
# endregion

# region buildings references campuses
INSERT IGNORE INTO `#__organizer_buildings` (`id`, `campusID`, `name`, `location`, `address`, `propertyType`)
SELECT DISTINCT `id`, `campusID`, `name`, `location`, `address`, `propertyType`
FROM `#__thm_organizer_buildings`;
# endregion

# region rooms references roomtypes
INSERT IGNORE INTO `#__organizer_rooms` (`id`, `buildingID`, `code`, `name`, `roomtypeID`, `capacity`)
SELECT DISTINCT `id`, `buildingID`, `gpuntisID`, `name`, `typeID`, `capacity`
FROM `#__thm_organizer_rooms`;
# endregion

# region monitors references rooms
INSERT IGNORE INTO `#__organizer_monitors` (`id`, `ip`, `roomID`, `useDefaults`, `display`, `scheduleRefresh`,
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
FROM `#__thm_organizer_monitors`;
# endregion

# region runs, references terms
INSERT IGNORE INTO `#__organizer_runs` (`id`, `name_de`, `name_en`, `termID`, `run`)
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
# endregion

# endregion

# region triple reference

# pools, programs, subjects, teachers
INSERT IGNORE INTO `#__organizer_field_colors` (`colorID`, `fieldID`, `organizationID`)
SELECT DISTINCT f.`colorID`, f.`id`, dr.`departmentID`
FROM `#__thm_organizer_fields` AS f
         INNER JOIN `#__thm_organizer_plan_pools` AS ppo ON ppo.`fieldID` = f.`id`
         INNER JOIN `#__thm_organizer_department_resources` AS dr ON dr.`programID` = ppo.`programID`
WHERE f.`colorID` IS NOT NULL;

INSERT IGNORE INTO `#__organizer_field_colors` (`colorID`, `fieldID`, `organizationID`)
SELECT DISTINCT f.`colorID`, f.`id`, l.`departmentID`
FROM `#__thm_organizer_fields` AS f
         INNER JOIN `#__thm_organizer_plan_subjects` AS ps ON ps.`fieldID` = f.`id`
         INNER JOIN `#__thm_organizer_lesson_subjects` AS ls ON ls.`subjectID` = ps.`id`
         INNER JOIN `#__thm_organizer_lessons` AS l ON l.`id` = ls.`lessonID`
WHERE f.`colorID` IS NOT NULL;

INSERT IGNORE INTO `#__organizer_field_colors` (`colorID`, `fieldID`, `organizationID`)
SELECT DISTINCT f.`colorID`, f.`id`, p.`departmentID`
FROM `#__thm_organizer_fields` AS f
         INNER JOIN `#__thm_organizer_pools` AS p ON p.`fieldID` = f.`id`
WHERE f.`colorID` IS NOT NULL;

INSERT IGNORE INTO `#__organizer_field_colors` (`colorID`, `fieldID`, `organizationID`)
SELECT DISTINCT f.`colorID`, f.`id`, p.`departmentID`
FROM `#__thm_organizer_fields` AS f
         INNER JOIN `#__thm_organizer_programs` AS p ON p.`fieldID` = f.`id`
WHERE f.`colorID` IS NOT NULL;

INSERT IGNORE INTO `#__organizer_field_colors` (`colorID`, `fieldID`, `organizationID`)
SELECT DISTINCT f.`colorID`, f.`id`, s.`departmentID`
FROM `#__thm_organizer_fields` AS f
         INNER JOIN `#__thm_organizer_subjects` AS s ON s.`fieldID` = f.`id`
WHERE f.`colorID` IS NOT NULL;

# endregion

# region curricula resources

# region programs references categories (later), degrees, frequencies and organizations
INSERT IGNORE INTO `#__organizer_programs` (`id`, `organizationID`, `code`, `degreeID`, `accredited`, `frequencyID`, `name_de`,
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
FROM `#__thm_organizer_programs`;
# endregion

# region pools references fields, organizations
INSERT IGNORE INTO `#__organizer_pools` (`id`, `organizationID`, `fieldID`, `lsfID`, `abbreviation_de`,
                                         `abbreviation_en`, `shortName_de`, `shortName_en`, `fullName_de`,
                                         `fullName_en`, `description_de`, `description_en`, `minCrP`, `maxCrP`)
SELECT DISTINCT `id`,
                `departmentID`,
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
FROM `#__thm_organizer_pools`;
# endregion

# region subjects references fields, frequencies and organizations
INSERT IGNORE INTO `#__organizer_subjects` (`id`, `organizationID`, `lsfID`, `code`, `abbreviation_de`,
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
FROM `#__thm_organizer_subjects`;
# endregion

# region curricula references curricula, pools, programs and subjects
INSERT IGNORE INTO `#__organizer_curricula` (`id`, `parentID`, `programID`, `poolID`, `subjectID`, `level`, `lft`, `ordering`, `rgt`)
SELECT `id`, `parentID`, `programID`, `poolID`, `subjectID`, `level`, `lft`, `ordering`, `rgt`
FROM `#__thm_organizer_mappings`;
# endregion

# region prerequisites references curricula
INSERT IGNORE INTO `#__organizer_prerequisites`
SELECT *
FROM `#__thm_organizer_prerequisites`;
# endregion

#region subject_persons references persons and subjects
INSERT IGNORE INTO `#__organizer_subject_persons` (`id`, `personID`, `role`, `subjectID`)
SELECT `id`, `teacherID`, `teacherResp`, `subjectID`
FROM `#__thm_organizer_subject_teachers`;
# endregion

# endregion