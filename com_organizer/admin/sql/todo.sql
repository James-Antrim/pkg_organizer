#region course data migrated

#drop campusid fk, index & column
ALTER TABLE `v7ocf_organizer_units`
    DROP COLUMN `fee`,
    DROP COLUMN `maxParticipants`,
    DROP COLUMN `registrationType`;

#endregion

#region after merging events

ALTER TABLE `v7ocf_organizer_events` ADD UNIQUE INDEX `entry` (`code`, `organizationID`);

#endregion

#revisit foreign keys as to which truly need to be deleted on cascade