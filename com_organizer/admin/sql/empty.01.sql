# References colors, fields and organizations
DELETE
FROM `v7ocf_organizer_field_colors`
WHERE `id` IS NOT NULL;

DELETE
FROM `v7ocf_organizer_colors`
WHERE `id` IS NOT NULL;

DELETE
FROM `v7ocf_organizer_degrees`
WHERE `id` IS NOT NULL;

DELETE
FROM `v7ocf_organizer_fields`
WHERE `id` IS NOT NULL;

DELETE
FROM `v7ocf_organizer_frequencies`
WHERE `id` IS NOT NULL;

DELETE
FROM `v7ocf_organizer_grids`
WHERE `id` IS NOT NULL;

# References organizations and persons at this stage
DELETE
FROM `v7ocf_organizer_associations`
WHERE `id` IS NOT NULL;

DELETE
FROM `v7ocf_organizer_organizations`
WHERE `id` IS NOT NULL;

DELETE
FROM `v7ocf_organizer_persons`
WHERE `id` IS NOT NULL;