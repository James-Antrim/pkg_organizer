# region programs references categories (later), degrees, frequencies and organizations
INSERT INTO `#__organizer_programs` (`id`, `organizationID`, `code`, `degreeID`, `year`, `frequencyID`, `name_de`,
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

# TODO remove the conditions and data which make this statement necessary
UPDATE `#__thm_organizer_pools`
SET `lsfID` = NULL
WHERE `lsfID` = 0;

# TODO remove the conditions and data which make these statements necessary
UPDATE `#__thm_organizer_pools`
SET `abbreviation_de` = ''
WHERE LENGTH(`abbreviation_de`) > 25;

UPDATE `#__thm_organizer_pools`
SET `abbreviation_en` = ''
WHERE LENGTH(`abbreviation_en`) > 25;

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

# region curriculum references curriculum, pools, programs and subjects
INSERT IGNORE INTO `#__organizer_curriculum`
SELECT *
FROM `#__thm_organizer_mappings`;
# endregion

# region prerequisites references curriculum
INSERT IGNORE INTO `#__organizer_prerequisites`
SELECT *
FROM `#__thm_organizer_prerequisites`;
# endregion

#region subject_persons references persons and subjects
INSERT IGNORE INTO `#__organizer_subject_persons` (`id`, `personID`, `role`, `subjectID`)
SELECT `id`, `teacherID`, `teacherResp`, `subjectID`
FROM `#__thm_organizer_subject_teachers`;
# endregion