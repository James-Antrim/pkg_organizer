# Generic where statement added to avoid warnings because the statments affect the whole table

#instance_participants
#participants
#instance_rooms
DELETE
FROM `#__organizer_instance_groups`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_instance_persons`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_instances`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_units`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_schedules`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_event_coordinators`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_subject_events`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_events`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_associations`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_group_publishing`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_groups`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_groups`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_categories`
WHERE `id` IS NOT NULL;