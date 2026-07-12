-- Sync existing hosted databases with the current TSF dashboard/content model.
-- Run inside the selected TSF database in phpMyAdmin.

INSERT INTO site_settings (setting_key, setting_value) VALUES
('impact_hero_body', 'Numbers tell part of the story. Behind every target is a child TSF is preparing to reach with care, opportunity, and practical support.'),
('impact_numbers_title', 'TSF Targets'),
('impact_numbers_body', 'Expected reach and program goals TSF is working toward with donors, volunteers, schools, and community partners.'),
('team_group_image', 'images/page/team-group-generated.jpg'),
('team_group_image_2', 'images/page/team-group-generated-2.jpg'),
('team_group_title', 'Together for Every Child'),
('team_group_body', 'Meet the young leaders, volunteers, and builders working together to support children, strengthen communities, and grow the TSF mission across Ghana.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

UPDATE content_items
SET title = 'Children To Support'
WHERE item_type = 'impact_stat' AND title = 'Children Supported';

UPDATE content_items
SET title = 'Projects Planned'
WHERE item_type = 'impact_stat' AND title = 'Projects Completed';

UPDATE content_items
SET title = 'School Partners Targeted'
WHERE item_type = 'impact_stat' AND title = 'Schools Partnered';

UPDATE content_items
SET title = 'Youth To Train'
WHERE item_type = 'impact_stat' AND title = 'Digital Skills Trained';

UPDATE content_items
SET body = 'We sponsor school fees, uniforms, books, and stationery for children from families who cannot afford them, with a target of supporting 600+ children in school annually.',
    meta_value = '600+ children targeted per year'
WHERE item_type = 'program' AND title = 'School Support Program';

UPDATE content_items
SET meta_value = '380+ youth targeted for training'
WHERE item_type = 'program' AND title = 'Digital Skills Academy';

UPDATE content_items
SET meta_value = '250+ mentor-mentee pairs targeted'
WHERE item_type = 'program' AND title = 'Youth Mentorship Program';
