#region after merging events

ALTER TABLE `#__organizer_events`
    ADD UNIQUE INDEX `entry` (`code`, `organizationID`);

#endregion

#revisit foreign keys as to which truly need to be deleted on cascade