ALTER TABLE `v7ocf_organizer_degrees`
    MODIFY COLUMN `abbreviation` VARCHAR(50)         NOT NULL,
    ADD COLUMN `statisticCode`   VARCHAR(10);

UPDATE `v7ocf_organizer_degrees` SET `statisticCode` = 84 WHERE `id` IN (1,2,3);
UPDATE `v7ocf_organizer_degrees` SET `statisticCode` = 90 WHERE `id` IN (4,5,6,7);
UPDATE `v7ocf_organizer_degrees` SET `name` = 'Master of Business Administration' WHERE `id` = 7;
UPDATE `v7ocf_organizer_programs` SET `degreeID` = 6 WHERE `degreeID` = 8;
DELETE FROM `v7ocf_organizer_degrees` WHERE `id` = 8;

INSERT IGNORE INTO `v7ocf_organizer_degrees`
VALUES (9, 'zertifikat', 'Zertifikat', '00', 'Zertifikat', 94),
       (10, 'ohne-abschluss', 'ohne Abschluss', '01', 'ohne Abschluss', 97),
       (11, 'bed', 'B.Ed.', 'BU', 'Bachelor of Education', 84),
       (12, 'feststellungspruefung', 'FSP', 'FP', 'Feststellungsprüfung', 17),
       (13, 'hochschulzugangspruefung', 'HZP', 'HZ', 'Hochschulzugangsprüfung', 17),
       (14, 'mbae', 'M.B.A.E.', 'MD', 'Master of Business Administration and Engineering', 90);

ALTER TABLE `v7ocf_organizer_degrees` MODIFY COLUMN `statisticCode`  VARCHAR(10) NOT NULL;

CREATE TABLE IF NOT EXISTS `v7ocf_organizer_nomina`
(
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias_de`        VARCHAR(255)     NOT NULL,
    `alias_en`        VARCHAR(255)     NOT NULL,
    `code`            VARCHAR(60)      NOT NULL,
    `name_de`         VARCHAR(255)     NOT NULL,
    `name_en`         VARCHAR(255)     NOT NULL,
    `statisticCode`   VARCHAR(10)      NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias_de` (`alias_de`),
    UNIQUE KEY `alias_en` (`alias_en`),
    UNIQUE KEY `code` (`code`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_nomina`
VALUES (1, 'architektur', 'architecture', 'A', 'Architektur', 'Architecture', '0013'),
       (2, 'angewandte-physik', 'applied-physics', 'APH', 'Angewandte Physik', 'Applied Physics', '0224'),
       (3, 'angewandte-vakuumtechnik', 'applied-vacuum-technology', 'AV', 'Angewandte Vakuumtechnik', 'Applied Vacuum Technology', '0c69'),
       (4, 'bahningenieurwesen', 'railway-engineering', 'BAI', 'Bahningenieurwesen', 'Railway Engineering', '0c18'),
       (5, 'bauingenieurwesen', 'civil-engineering', 'BAU', 'Bauingenieurwesen', 'Civil Engineering', '0017'),
       (6, 'berufliche-betriebliche-bildung-elektrotechnik', 'vocational-operational-training-electrical-engineering', 'BBE', 'Berufliche und Betriebliche Bildung - Elektrotechnik', 'Vocational and Operational Training - Electrical Engineering', '0624'),
       (7, 'berufliche-betriebliche-bildung-metalltechnik', 'vocational-operational-training-metal-technology', 'BBM', 'Berufliche und Betriebliche Bildung - Metalltechnik', 'Vocational and Operational Training - Metal Technology', '0625'),
       (8, 'bioinformatik', 'bioinformatics', 'BI', 'Bioinformatik', 'Bioinformatics', '0221'),
       (9, 'bioinformatik-systembiologie', 'bioinformatics-systems-biology', 'BIS', 'Bioinformatik und Systembiologie', 'Bioinformatics and Systems Biology', '0b33'),
       (10, 'leitung-bildungsmanagement', 'management-education-management', 'BM', 'Leitung und Bildungsmanagement', 'Management and Education Management', '0639'),
       (11, 'biomechanik-motorik-bewegungsanalyse', 'biomechanics-motor-skills-movement-analysis', 'BMB', 'Biomechanik-Motorik-Bewegungsanalyse', 'Biomechanics, Motor Skills and Movement Analysis', '0a33'),
       (12, 'biomedizinische-technik', 'biomedical-engineering', 'BMT', 'Biomedizinische Technik', 'Biomedical Engineering', '0d42'),
       (13, 'biotechnologie', 'biotechnology', 'BT', 'Biotechnologie', 'Biotechnology', '0802'),
       (14, 'biotechnologie-biopharmazeutische-technologie', 'biotechnology-biopharmaceutical-technology', 'BTP', 'Biotechnologie/Biopharmazeutische Technologie', 'Biotechnology/Biopharmaceutical Technology', '0d43'),
       (15, 'control-computer-communications-engineering', 'control-computer-communications-engineering', 'CCC', 'Control, Computer and Communications Engineering', 'Control, Computer and Communications Engineering', '0e75'),
       (16, 'clinical-engineering', 'clinical-engineering', 'CE', 'Clinical Engineering', 'Clinical Engineering', '0932'),
       (17, 'sustainable-design-construction-management-built-environment', 'sustainable-design-construction-management-built-environment', 'DCM', 'Sustainable Design, Construction and Management of the Built Environment', 'Sustainable Design, Construction and Management of the Built Environment', '0f82'),
       (18, 'digital-business', 'digital-business', 'DIB', 'Digital Business', 'Digital Business', '0f81'),
       (19, 'digital-international-marketing', 'digital-international-marketing', 'DIM', 'Digital and International Marketing', 'Digital and International Marketing', '0d41'),
       (20, 'digitale-medizin', 'digitale-medicine', 'DM', 'Digitale Medizin', 'Digitale Medicine', '0f36'),
       (21, 'digital-media-systems', 'digital-media-systems', 'DMS', 'Digital Media Systems', 'Digital Media Systems', '0d34'),
       (22, 'energiewirtschaft-energiemanagement', 'energy-industry-energy-management', 'EEM', 'Energiewirtschaft und Energiemanagement', 'Energy Industry and Energy Management', '0d11'),
       (23, 'energietechnik', 'energy-technology', 'EG', 'Energietechnik', 'Energy Technology', '0786'),
       (24, 'elektro-informationstechnik', 'electrical-engineering-information-technology', 'ELI', 'Elektro- und Informationstechnik', 'Electrical Engineering and Information Technology', '0c16'),
       (25, 'energieeffizienz-management', 'energy-efficiency-management', 'EM', 'Energieeffizienz Management', 'Energy Efficiency Management', '0c62'),
       (26, 'eventmanagement-technik', 'event-management-technology', 'EMT', 'Eventmanagement und -technik', 'Event Management and Technology', '0c71'),
       (27, 'elektrische-energietechnik-regenerative-energiesysteme', 'electrical-energy-technology-renewable-energy-systems', 'ERE', 'Elektrische Energietechnik für regenerative Energiesysteme', 'Electrical Energy Technology for Renewable Energy Systems', '0c70'),
       (28, 'energiesystemtechnik', 'energy-systems-engineering', 'EST', 'Energiesystemtechnik', 'Energy Systems Engineering', '0786'),
       (29, 'allgemeine-elektrotechnik', 'general-electrical-engineering', 'ET', 'Allgemeine Elektrotechnik', 'General Electrical Engineering', '0048'),
       (30, 'facility-management', 'facility-management', 'FM', 'Facility Management', 'Facility Management', '0464'),
       (31, 'future-skills-innovation', 'future-skills-innovation', 'FSI', 'Future Skills und Innovation', 'Future Skills und Innovation', '0g18'),
       (32, 'hebammenwissenschaft', 'midwifery-science', 'HW', 'Hebammenwissenschaft', 'Midwifery Science', '0g59'),
       (33, 'informatik', 'information-science', 'I', 'Informatik', 'Information Science', '0079'),
       (34, 'insektenbiotechnologie-bioressourcen', 'insect-biotechnology-bioresources', 'IBB', 'Insektenbiotechnologie und Bioressourcen', 'Insect Biotechnology and Bioresources', '0e50'),
       (35, 'information-communications-engineering', 'information-communications-engineering', 'ICE', 'Information and Communications Engineering', 'Information and Communications Engineering', '0721'),
       (36, 'immobilien-facility-management', 'real-estate-facility-management', 'IFM', 'Immobilien- und Facility Management', 'Real Estate and Facility Management', '0h92'),
       (37, 'innovationsmanagement', 'innovation-management', 'IM', 'Innovationsmanagement', 'Innovation Management', '0c73'),
       (38, 'ingenieur-informatik', 'informatics-engineers', 'INI', 'Ingenieur-Informatik', 'Informatics for Engineers', '0123'),
       (39, 'international-marketing', 'International-marketing', 'INM', 'International Marketing', 'International Marketing', '0d41'),
       (40, 'infrastrukturmanagement', 'infrastructure-management', 'ISM', 'Infrastrukturmanagement', 'Infrastructure Management', '0883'),
       (41, 'ingenieurwesen', 'engineering', 'IW', 'Ingenieurwesen', 'Engineering', '0286'),
       (42, 'ingenieurwesen-elektrotechnik', 'engineering-electrical-engineering', 'IWE', 'Ingenieurwesen Elektrotechnik', 'Engineering - Electrical Engineering', '0d39'),
       (43, 'wirtschaftsingenieurwesen-immobilien', 'business-administration-real-estate-engineering', 'IWI', 'Wirtschaftsingenieurwesen - Immobilien', 'Business Administration and Real Estate Engineering', '0c75'),
       (44, 'ingenieurwesen-maschinenbau', 'engineering-mechanical-engineering', 'IWM', 'Ingenieurwesen Maschinenbau', 'Engineering - Mechanical Engineering', '0d40'),
       (45, 'wirtschaftsingenieurwesen-industrie', 'industrial-business-administration', 'IWU', 'Wirtschaftsingenieurwesen - Industrie', 'Industrial Business Administration', '0c76'),
       (46, 'infrastruktur-wasser-verkehr', 'water-transport-infrastructure ', 'IWV', 'Infrastruktur - Wasser und Verkehr', 'Water and Transport Infrastructure ', '0f40'),
       (47, 'krankenhaushygiene', 'hospital-hygiene', 'KH', 'Krankenhaushygiene', 'Hospital Hygiene', '0b11'),
       (48, 'krankenhaus-planung-technik', 'hospital-planning-technology', 'KPT', 'Krankenhaus Planung Technik', 'Hospital Planning and Technology', '0c17'),
       (49, 'krankenhaus-technik-management', 'hospital-technology-management', 'KTM', 'Krankenhaus Technik Management', 'Hospital Technology and Management', '0804'),
       (50, 'klimaschutz-umwelt-sicherheitsingenieurwesen', 'climate-protection-environmental-safety-engineering', 'KUS', 'Klimaschutz, Umwelt- und Sicherheitsingenieurwesen', 'Climate Protection, Environmental and Safety Engineering', '0a73'),
       (51, 'logistik', 'logistics', 'LO', 'Logistik', 'Logistics', '0929'),
       (52, 'logistikmanagement', 'logistics-management', 'LOM', 'Logistikmanagement', 'Logistics Management', '0a70'),
       (53, 'life-science-engineering', 'life-science-engineering', 'LSE', 'Life Science Engineering', 'Life Science Engineering', '0yyy'),
       (54, 'maschinenbau', 'mechanical-engineering', 'M', 'Maschinenbau', 'Mechanical Engineering', '0104'),
       (55, 'management-medizin', 'management-medicine', 'MAM', 'Management in der Medizin', 'Management in Medicine', '0f47'),
       (56, 'methoden-didaktik-angewandten-wissenschaften-higher-education', 'methods-didactics-applied-sciences-higher-education', 'MDH', 'Methoden und Didaktik in angewandten Wissenschaften_Higher Education', 'Methods and Didactics in Applied Sciences_Higher Education', '0e15'),
       (57, 'maschinenbau-energiesysteme', 'mechanical-engineering-energy-systems', 'ME', 'Maschinenbau und Energiesysteme', 'Mechanical Engineering and Energy Systems', '0b13'),
       (58, 'mikroelektronik-elektronikdesign', 'microelectronics-electronic-design', 'MeD', 'Mikroelektronik/Elektronikdesign', 'Microelectronics/Electronic Design', '0157'),
       (59, 'mechatronik', 'mechatronics', 'MET', 'Mechatronik', 'Mechatronics', '0380'),
       (60, 'material-fertigungstechnologie', 'materials-manufacturing-technology', 'MF', 'Material- und Fertigungstechnologie', 'Materials and Manufacturing Technology', '0d13'),
       (61, 'mathematik-finanzen-versicherungen-management', 'mathematics-finance-insurance-management', 'MFF', 'Mathematik für Finanzen, Versicherungen und Management', 'Mathematics for Finance, Insurance and Management', '0105'),
       (62, 'medizinische-informatik', 'medical-informatics', 'MIF', 'Medizinische Informatik', 'Medical Informatics', '0247'),
       (63, 'medieninformatik', 'media-informatics', 'MIN', 'Medieninformatik', 'media-informatics', '0121'),
       (64, 'mathematik', 'mathematics', 'MK', 'Mathematik', 'Mathematics', '0105'),
       (65, 'maschinenbau-mechatronik', 'mechanical-engineering-mechatronics', 'MM', 'Maschinenbau Mechatronik', 'Mechanical Engineering and Mechatronics', '0b09'),
       (66, 'medizinisches-management', 'medical-management', 'MMT', 'Medizinisches Management', 'Medical Management', '0d12'),
       (67, 'mathematik-nachhaltigkeit-wirtschaft-data-science', 'mathematics-sustainability-economics-data-science', 'MNW', 'Mathematik für Nachhaltigkeit, Wirtschaft und Data Science', 'Mathematics for Sustainability, Economics and Data Science', '0h91'),
       (68, 'medizinische-physik', 'medical-physics', 'MPH', 'Medizinische Physik', 'Medical Physics', '0691'),
       (69, 'medizinische-physik-strahlenschutz', 'medical-physics-radiation-protection', 'MPS', 'Medizinische Physik und Strahlenschutz', 'Medical Physics and Radiation Protection', '0c72'),
       (70, 'medizintechnik', 'medical-technology', 'MT', 'Medizintechnik', 'Medical Technology', '0805'),
       (71, 'nachrichtentechnik-computernetze', 'communications-engineering-computer-networks', 'NAC', 'Nachrichtentechnik und Computernetze', 'Communications Engineering and Computer Networks', '0c63'),
       (72, 'betriebswirtschaft-nachhaltigkeitsmanagement', 'business-administration-sustainability-management', 'NM', 'Betriebswirtschaft - Nachhaltigkeitsmanagement', 'Business Administration - Sustainability Management', 'h14'),
       (73, 'optotechnik-bildverarbeitung', 'optotechnology-image-processing', 'OBV', 'Optotechnik und Bildverarbeitung', 'Optotechnology and Image Processing', '0999'),
       (74, 'organisations-it-sicherheit', 'organizational-it-security', 'OIT', 'Organisations- und IT-Sicherheit', 'Organizational and IT-Security', '0h94'),
       (75, 'organisationsmanagement-medizin', 'organizational-management-medicine', 'OMM', 'Organisationsmanagement in der Medizin', 'Organizational Management in Medicine', '0b12'),
       (76, 'optical-system-engineering', 'optical-system-engineering', 'OSE', 'Optical System Engineering', 'Optical System Engineering', '0g70'),
       (77, 'orthopaedie-rehatechnik', 'orthopedic-rehabilitation-technology', 'OT', 'Orthopädie- und Rehatechnik', 'Orthopedic and Rehabilitation Technology', '0928'),
       (78, 'public-health', 'public-health', 'PH', 'Public Health', 'Public Health', '0748'),
       (79, 'physikalische-technik', 'physical-engineering', 'PHY', 'Physikalische Technik', 'Physical Engineering', '0224'),
       (80, 'prozessmanagement', 'process-management', 'PM', 'Prozessmanagement', 'Process Management', '0881'),
       (81, 'personalmanagement', 'human-resources', 'PMG', 'Personalmanagement', 'Human Resources', '0d75'),
       (82, 'physik-technologie-raumfahrtanwendungen', 'physics-technology-space-applications', 'PTR', 'Physik und Technologie für Raumfahrtanwendungen', 'Physics and Technology for Space Applications', '0e49'),
       (83, 'supply-chain-management', 'supply-chain-management', 'SCM', 'Supply Chain Management', 'Supply Chain Management', '0689'),
       (84, 'systems-engineering', 'systems-engineering', 'SE', 'Systems Engineering', 'Systems Engineering', '0a68'),
       (85, 'sustainability-transformation-engineering-management', 'sustainability-transformation-engineering-management', 'SEM', 'Sustainability Transformation in Engineering and Management', 'Sustainability Transformation in Engineering and Management', '0h93'),
       (86, 'kooperation-giessen-marburg', 'cooperation-giessen-marburg', 'SKO', 'Kooperation Gießen/Marburg', 'Cooperation Giessen/Marburg', '0xxx'),
       (87, 'strategische-live-kommunikation', 'strategic-live-communication', 'SLK', 'Strategische Live Kommunikation', 'Strategic Live Communication', '0f37'),
       (88, 'strahlenschutz-messtechnik', 'radiation-protection-measurement-technology', 'SM', 'Strahlenschutz- und -messtechnik', 'Radiation Protection and Measurement Technology', '0926'),
       (89, 'social-media-systems', 'social-media-systems', 'SMS', 'Social Media Systems', 'Social Media Systems', '0d34'),
       (90, 'studienkolleg', 'preparatory-college', 'STK', 'Studienkolleg', 'Preparatory College', '0fsp'),
       (91, 'studienvorbereitungsprogramm', 'study-preparation-program', 'SVP', 'Studienvorbereitungsprogramm (PS)', 'Study Preparation Program', '0fsp'),
       (92, 'softwaretechnologie', 'software-technology', 'SWT', 'Softwaretechnologie', 'Software Technology', '0e16'),
       (93, 'technische-gebaeudeausruestung', 'technical-building-equipment', 'TGA', 'Technische Gebäudeausrüstung', 'Technical Building Equipment', '0787'),
       (94, 'technische-informatik', 'technical-informatics', 'TI', 'Technische Informatik', 'Technical Informatics', '0a72'),
       (95, 'technische-redaktion-multimediale-dokumentation', 'technical-writing-multimedia-documentation', 'TMD', 'Technische Redaktion und multimediale Dokumentation', 'Technical Writing and Multimedia Documentation', '0906'),
       (96, 'technischer-vertrieb', 'technical-sales', 'TV', 'Technischer Vertrieb', 'Technical Sales', '0c74'),
       (97, 'unternehmensfuehrung', 'corporate-governance', 'UF', 'Unternehmensführung', 'Corporate Governance', '0894'),
       (98, 'umwelt-hygiene-sicherheitsingenieurwesen', 'Environmental, hygiene-safety-engineering', 'UHS', 'Umwelt-, Hygiene- und Sicherheitsingenieurwesen', 'Environmental, Hygiene and Safety Engineering', '0a73'),
       (99, 'unternehmenssteuerung', 'corporate-management', 'US', 'Unternehmenssteuerung', 'Corporate Management', '0d76'),
       (100, 'vakuumingenieurwesen', 'vacuum-engineering', 'VI', 'Vakuumingenieurwesen', 'Vacuum Engineering', '0c69'),
       (101, 'betriebswirtschaft', 'business-administration', 'W', 'Betriebswirtschaft', 'Business Administration', '0021'),
       (102, 'wirtschaftsingenieurwesen', 'business-administration-engineering', 'WI', 'Wirtschaftsingenieurwesen', 'Business Administration and Engineering', '0370'),
       (103, 'wirtschaftsinformatik', 'business-informatics', 'WIN', 'Wirtschaftsinformatik', 'Business Informatics', '0277'),
       (104, 'wirtschaftsmathematik', 'business-mathematics', 'WMK', 'Wirtschaftsmathematik', 'Business Mathematics', '0276');

DELETE FROM `v7ocf_organizer_programs` WHERE `code` = 'BBB';

UPDATE `v7ocf_organizer_programs` SET `code` = 'BAU' WHERE `code` = 'B';
UPDATE `v7ocf_organizer_programs` SET `code` = 'BAU' WHERE `code` = 'BAD';
UPDATE `v7ocf_organizer_programs` SET `code` = 'BAU' WHERE `code` = 'BG';
UPDATE `v7ocf_organizer_programs` SET `code` = 'ELI' WHERE `code` = 'EIT';
UPDATE `v7ocf_organizer_programs` SET `code` = 'EG' WHERE `code` = 'ENT';
UPDATE `v7ocf_organizer_programs` SET `code` = 'EG' WHERE `code` = 'ES';
UPDATE `v7ocf_organizer_programs` SET `code` = 'ELI' WHERE `code` = 'ETI';
UPDATE `v7ocf_organizer_programs` SET `code` = 'ELI' WHERE `code` = 'ETT';
UPDATE `v7ocf_organizer_programs` SET `code` = 'FM' WHERE `code` = 'FMF';
UPDATE `v7ocf_organizer_programs` SET `code` = 'KUS' WHERE `code` = 'KUSI';
UPDATE `v7ocf_organizer_programs` SET `code` = 'LO' WHERE `code` = 'LOG';
UPDATE `v7ocf_organizer_programs` SET `code` = 'M' WHERE `code` = 'M1';
UPDATE `v7ocf_organizer_programs` SET `code` = 'M' WHERE `code` = 'M2';
UPDATE `v7ocf_organizer_programs` SET `code` = 'MDH' WHERE `code` = 'MD';
UPDATE `v7ocf_organizer_programs` SET `code` = 'MDH' WHERE `code` = 'MDA';
UPDATE `v7ocf_organizer_programs` SET `code` = 'MET' WHERE `code` = 'MEG';
UPDATE `v7ocf_organizer_programs` SET `code` = 'OBV' WHERE `code` = 'OTBV';
UPDATE `v7ocf_organizer_programs` SET `code` = 'PTR' WHERE `code` = 'RFA';
UPDATE `v7ocf_organizer_programs` SET `code` = 'WI' WHERE `code` = 'WID';
UPDATE `v7ocf_organizer_programs` SET `code` = 'WI' WHERE `code` = 'WIF';
UPDATE `v7ocf_organizer_programs` SET `code` = 'W' WHERE `code` = 'WWD';

ALTER TABLE `v7ocf_organizer_programs`
    ADD COLUMN `nomenID` INT(11) UNSIGNED DEFAULT NULL AFTER `degreeID`,
    ADD KEY `nomenID` (`nomenID`);

UPDATE `v7ocf_organizer_programs` AS `p` INNER JOIN `v7ocf_organizer_nomina` AS `n` ON `n`.`code` = `p`.`code` SET `p`.`nomenID` = `n`.`id`;

ALTER TABLE `v7ocf_organizer_programs`
    ADD CONSTRAINT `program_nomenID_fk` FOREIGN KEY (`nomenID`) REFERENCES `v7ocf_organizer_nomina` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `v7ocf_organizer_programs` DROP COLUMN `code`;

ALTER TABLE `v7ocf_organizer_programs` MODIFY COLUMN `nomenID` INT(11) UNSIGNED NOT NULL;

CREATE TABLE IF NOT EXISTS `v7ocf_organizer_minors`
(
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias_de`        VARCHAR(255)     NOT NULL,
    `alias_en`        VARCHAR(255)     NOT NULL,
    `code`            VARCHAR(60)      NOT NULL,
    `name_de`         VARCHAR(255)     NOT NULL,
    `name_en`         VARCHAR(255)     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias_de` (`alias_de`),
    UNIQUE KEY `alias_en` (`alias_en`),
    UNIQUE KEY `code` (`code`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_minors`
VALUES (1, 'allgemeine-elektrotechnik', 'general-electrical-engineering', 'AET', 'Allgemeine Elektrotechnik', 'General Electrical Engineering'),
       (2, 'allgemeiner-maschinenbau', 'general-mechanical-engineering', 'AMB', 'Allgemeiner Maschinenbau', 'General Mechanical Engineering'),
       (3, 'angewandte-medizinische-wissenschaften', 'applied-medical-science', 'AMW', 'Angewandte Medizinische Wissenschaften', 'Applied Medical Science'),
       (4, 'baumanagement-konstruktion-infrastruktur', 'construction-management-infrastructure', 'BKI', 'Baumanagement, Konstruktion und Infrastruktur', 'Construction Management and Infrastructure'),
       (5, 'controlling', 'controlling', 'CTR', 'Controlling', 'Controlling'),
       (6, 'data-science', 'data-science', 'DS', 'Data Science', 'Data Science'),
       (7, 'elektrotechnik', 'electrical-engineering', 'ELT', 'Elektrotechnik', 'Electrical Engineering'),
       (8, 'hygiene-design', 'hygiene-design', 'HD', 'Hygiene Design', 'Hygiene Design'),
       (9, 'it-security', 'it-security', 'ITS', 'IT-Security', 'IT-Security'),
       (10, 'ingenieurwissenschaft', 'engineering', 'IWI', 'Ingenieurwissenschaft', 'Engineering'),
       (11, 'kaelte-klimatechnik', 'cold-climate-technology', 'KK', 'Kälte- und Klimatechnik', 'Cold and Climate Technology'),
       (12, 'krankenversicherungsmanagement', 'health-insurance-management', 'KM', 'Krankenversicherungsmanagement', 'Health Insurance Management'),
       (13, 'medical-data-science', 'medical-data-science', 'MDS', 'Medical Data Science', 'Medical Data Science'),
       (14, 'mittelstandsmanagement', 'sme-management', 'MM', 'Mittelstandsmanagement', 'SME Management'),
       (15, 'management-marketing', 'management-marketing', 'MMA', 'Management und Marketing', 'Management and Marketing'),
       (16, 'maschinenbau', 'mechanical-engineering', 'MSB', 'Maschinenbau', 'Mechanical Engineering'),
       (17, 'metalltechnik', 'metal-technology', 'MT', 'Metalltechnik', 'Metal Technology'),
       (18, 'regulatory-affairs-management', 'regulatory-affairs-management', 'RAM', 'Regulatory Affairs Management', 'Regulatory Affairs Management'),
       (19, 'softwareentwicklung', 'software-development', 'SE', 'Softwareentwicklung', 'Software Development'),
       (20, 'steuerung-geschaeftsprozessen', 'controlling-business-processes', 'SG', 'Steuerung von Geschäftsprozessen', 'Controlling Business Processes'),
       (21, 'technische-gebaeudeausruestung', 'building-services-engineering', 'TGA', 'Technische Gebäudeausrüstung', 'Building Services Engineering'),
       (22, 'technische-informatik', 'technical-informatics', 'TI', 'Technische Informatik', 'Technical Informatics'),
       (23, 'technische-prozesse', 'technical-processes', 'TP', 'Technische Prozesse', 'Technical Processes'),
       (24, 'veranstaltungstechnik', 'event-technology', 'VTK', 'Veranstaltungstechnik', 'Event Technology'),
       (25, 'vertrieb', 'sales', 'VWU', 'Vertrieb', 'Sales');

ALTER TABLE `v7ocf_organizer_programs`
    ADD COLUMN `minorID` INT(11) UNSIGNED DEFAULT NULL AFTER `degreeID`,
    ADD KEY `minorID` (`minorID`);

ALTER TABLE `v7ocf_organizer_programs`
    ADD CONSTRAINT `program_minorID_fk` FOREIGN KEY (`minorID`) REFERENCES `v7ocf_organizer_minors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `v7ocf_organizer_programs`
    ADD COLUMN `attendance` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER `nomenID`;

CREATE TABLE IF NOT EXISTS `v7ocf_organizer_foci`
(
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias_de`        VARCHAR(255)     NOT NULL,
    `alias_en`        VARCHAR(255)     NOT NULL,
    `code`            VARCHAR(60)      NOT NULL,
    `name_de`         VARCHAR(255)     NOT NULL,
    `name_en`         VARCHAR(255)     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias_de` (`alias_de`),
    UNIQUE KEY `alias_en` (`alias_en`),
    UNIQUE KEY `code` (`code`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_foci`
VALUES (1, 'getting-started', 'getting-started', 'GS', 'GettING Started', 'GettING Started');

ALTER TABLE `v7ocf_organizer_programs`
    ADD COLUMN `focusID` INT(11) UNSIGNED DEFAULT NULL AFTER `degreeID`,
    ADD KEY `focusID` (`focusID`);

UPDATE `v7ocf_organizer_programs` SET `focusID` = 1 WHERE `name_de` LIKE '%getting%';

ALTER TABLE `v7ocf_organizer_programs`
    ADD CONSTRAINT `program_focusID_fk` FOREIGN KEY (`focusID`) REFERENCES `v7ocf_organizer_foci` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `v7ocf_organizer_attendance_types`
(
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias_de`        VARCHAR(255)     NOT NULL,
    `alias_en`        VARCHAR(255)     NOT NULL,
    `code`            VARCHAR(60)      NOT NULL,
    `name_de`         VARCHAR(255)     NOT NULL,
    `name_en`         VARCHAR(255)     NOT NULL,
    `statisticCode`   VARCHAR(10)      NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `alias_de` (`alias_de`),
    UNIQUE KEY `alias_en` (`alias_en`),
    UNIQUE KEY `code` (`code`),
    UNIQUE KEY `name_de` (`name_de`),
    UNIQUE KEY `name_en` (`name_en`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `v7ocf_organizer_attendance_types`
VALUES (1, 'praesenzstudium', 'on-campus-study', 'P', 'Präsenzstudium', 'On-Campus Study', '1'),
       (2, 'fernstudium', 'remote-study', 'F', 'Fernstudium', 'Remote Study', '2');

ALTER TABLE `v7ocf_organizer_programs`
    ADD COLUMN `aTypeID` INT(11) UNSIGNED DEFAULT NULL AFTER `accredited`,
    ADD KEY `aTypeID` (`aTypeID`);

UPDATE `v7ocf_organizer_programs` SET `aTypeID` = 2 WHERE `name_de` LIKE '%Fernstudium%';
UPDATE `v7ocf_organizer_programs` SET `aTypeID` = 1 WHERE `name_de` NOT LIKE '%Fernstudium%';

ALTER TABLE `v7ocf_organizer_programs`
    MODIFY COLUMN `aTypeID` INT(11) UNSIGNED NOT NULL;

ALTER TABLE `v7ocf_organizer_programs`
    ADD CONSTRAINT `program_aTypeID_fk` FOREIGN KEY (`aTypeID`) REFERENCES `v7ocf_organizer_attendance_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
