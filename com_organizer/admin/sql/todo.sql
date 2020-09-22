#region course data migrated & old lessons no longer in use

#drop campusid fk, index & column
ALTER TABLE `#__organizer_units`
    DROP COLUMN `fee`,
    DROP COLUMN `maxParticipants`,
    DROP COLUMN `registrationType`;

#endregion

#region after merging events

ALTER TABLE `#__organizer_events` ADD UNIQUE INDEX `entry` (`code`, `organizationID`);

#endregion

#revisit foreign keys as to which truly need to be deleted on cascade