# Generic where statement added to avoid warnings because the statments affect the whole table

#instance_participants
#participants
#instance_rooms
DELETE
FROM `v7ocf_organizer_instance_groups`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_instance_persons`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_instances`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_units`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_schedules`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_event_coordinators`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_subject_events`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_events`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_associations`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_group_publishing`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_groups`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_groups`
WHERE `id` IS NOT NULL;
DELETE
FROM `v7ocf_organizer_categories`
WHERE `id` IS NOT NULL;