# Generic where statement added to avoid warnings because the statments affect the whole table

DELETE
FROM `#__organizer_subject_persons`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_prerequisites`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_curricula`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_subjects`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_pools`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_programs`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_field_colors`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_field_colors`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_runs`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_monitors`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_rooms`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_buildings`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_campuses`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_terms`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_roomtypes`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_persons`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_organizations`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_methods`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_holidays`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_grids`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_fields`
WHERE `id` IS NOT NULL;
DELETE
FROM `#__organizer_blocks`
WHERE `id` IS NOT NULL;