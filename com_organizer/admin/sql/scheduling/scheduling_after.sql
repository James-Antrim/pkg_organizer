#add the automatic update of modified times to instances, instance_persons, instance_groups, instance_rooms...

ALTER TABLE `v7ocf_organizer_instances`
    MODIFY `modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `v7ocf_organizer_instance_persons`
    MODIFY `modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `v7ocf_organizer_instance_groups`
    MODIFY `modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `v7ocf_organizer_instance_rooms`
    MODIFY `modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `v7ocf_organizer_units`
    DROP FOREIGN KEY `unit_methodID_fk`,
    DROP COLUMN `methodID`;