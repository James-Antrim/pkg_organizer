DROP TABLE IF EXISTS `v7ocf_organizer_prerequisites`;
DROP TABLE IF EXISTS `v7ocf_organizer_curricula`;
ALTER TABLE `v7ocf_organizer_associations` DROP FOREIGN KEY `association_poolID_fk`;
DROP TABLE IF EXISTS `v7ocf_organizer_pools`;
ALTER TABLE `v7ocf_organizer_associations` DROP FOREIGN KEY `association_programID_fk`;
DROP TABLE IF EXISTS `v7ocf_organizer_programs`;
DROP TABLE IF EXISTS `v7ocf_organizer_subject_persons`;
ALTER TABLE `v7ocf_organizer_associations` DROP FOREIGN KEY `association_subjectID_fk`;
DROP TABLE IF EXISTS `v7ocf_organizer_subjects`;