ALTER TABLE `#__organizer_degrees`
    MODIFY COLUMN `abbreviation` VARCHAR (50) NOT NULL,
    ADD COLUMN `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER `abbreviation`,
    ADD COLUMN `statisticCode` TINYINT(2) UNSIGNED;

INSERT IGNORE INTO `#__organizer_degrees`
VALUES (10, 'dipl-informatik', 'Dipl.-Informatiker (FH)', 0, 'IF', 'Diplom-Informatiker (FH)', 51),
       (11, 'dipl-ingenieur', 'Dipl.-Ingenieur (FH)', 0, 'IN', 'Diplom-Ingenieur (FH)', 51),
       (12, 'dipl-logistik', 'Dipl.-Logistiker (FH)', 0, 'LO', 'Diplom-Logistiker (FH)', 51),
       (13, 'dipl-mathematik', 'Dipl.-Mathematiker (FH)', 0, 'MK', 'Diplom-Mathematiker (FH)', 51),
       (14, 'dipl-projektmanager', 'Dipl.-Projektmanager (FH)', 0, 'PM', 'Diplom-Projektmanager (FH)', 51),
       (15, 'dipl-vertriebsingenieur', 'Dipl.-Vertriebsingenieur (FH)', 0, 'VI', 'Diplom-Vertriebsingenieur (FH)', 51),
       (16, 'dipl-wirtschaftsinformatik', 'Dipl.-Wirtschaftsinformatiker (FH)', 0, 'WF', 'Diplom-Wirtschaftsinformatiker (FH)', 51),
       (17, 'dipl-wirtschaftsingenieur', 'Dipl.-Wirtschaftsingenieur (FH)', 0, 'WI', 'Diplom-Wirtschaftsingenieur (FH)', 51),
       (18, 'feststellungspruefung', 'Feststellungsprüfung', 0, 'FP', 'Feststellungsprüfung', 17),
       (19, 'hochschulzugangspruefung', 'Hochschulzugangsprüfung', 0, 'HZ', 'Hochschulzugangsprüfung', 17),
       (26, 'ohne-abschluss', 'ohne Abschluss', 0, '01', 'ohne Abschluss', 97),
       (27, 'promotion', 'Promotion', 1, 'PR', 'Promotion', 06),
       (28, 'promotion-haw', 'Promotion (HAW)', 1, 'PH', 'Promotion mit HAW Abschluss', 92),
       (29, 'zertifikat', 'Zertifikat', 1, '00', 'Zertifikat', 94);

UPDATE `#__organizer_degrees` SET `id` = 25, `statisticCode` = 90 WHERE `id` = 5;
UPDATE `#__organizer_degrees` SET `id` = 24, `statisticCode` = 90, `name` = 'Master of Higher Education' WHERE `id` = 8;
UPDATE `#__organizer_degrees` SET `id` = 23, `statisticCode` = 90 WHERE `id` = 4;
UPDATE `#__organizer_degrees` SET `id` = 22, `statisticCode` = 90, `alias`         = 'mbae' WHERE `id` = 7;
UPDATE `#__organizer_degrees` SET `id` = 20, `statisticCode` = 90 WHERE `id` = 6;
UPDATE `#__organizer_degrees` SET `id` = 7, `statisticCode` = 84 WHERE `id` = 2;
UPDATE `#__organizer_degrees` SET `id` = 6, `statisticCode` = 84 WHERE `id` = 1;
UPDATE `#__organizer_degrees` SET `id` = 5, `statisticCode` = 84 WHERE `id` = 9;
UPDATE `#__organizer_degrees` SET `id` = 2, `statisticCode` = 84 WHERE `id` = 3;

INSERT IGNORE INTO `#__organizer_degrees`
VALUES (1, 'auslaendischer-abschluss', 'ausländischer Abschluss', 0, 'AD', 'Abschluss ausserhalb Deutschlands', 96),
       (3, 'bba', 'B.B.A.', 1, 'BB', 'Bachelor of Business Administration', 84),
       (4, 'bbae', 'B.B.A.E.', 1, 'BD', 'Bachelor of Business Administration and Engineering', 84),
       (8, 'dipl-bioinformatik', 'Dipl.-Bioinformatiker (FH)', 0, 'BI', 'Diplom-Bioinformatiker (FH)', 51),
       (9, 'dipl-betriebswirt', 'Dipl.-Betriebswirt (FH)', 0, 'BW', 'Diplom-Betriebswirt (FH)', 51),
       (21, 'mba', 'M.B.A.', 1, 'MB', 'Master of Business Administration', 90);

ALTER TABLE `#__organizer_degrees` MODIFY COLUMN `statisticCode` TINYINT(2) UNSIGNED NOT NULL;