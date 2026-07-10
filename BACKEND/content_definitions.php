<?php
if (!defined('TSF_LOADED')) {
    http_response_code(403);
    die('Direct access not permitted.');
}

function getContentDefinitions(): array {
    return [
        'Global' => [
            'site_name' => ['label' => 'Site Name', 'type' => 'text', 'default' => 'Trenchkid Support Foundation'],
            'site_tagline' => ['label' => 'Tagline', 'type' => 'text', 'default' => 'Empowering Children, Building Futures'],
            'mission_statement' => ['label' => 'Mission Statement', 'type' => 'textarea', 'default' => 'Trenchkid Support Foundation exists to empower underprivileged children with education, digital skills, and mentorship, enabling them to build independent and impactful futures.'],
        ],
        'Home Page' => [
            'home_hero_image' => ['label' => 'Hero Background Image', 'type' => 'image', 'default' => ''],
            'home_hero_title' => ['label' => 'Hero Title', 'type' => 'text', 'default' => 'Every Child Deserves a Bright Future'],
            'home_hero_body' => ['label' => 'Hero Text', 'type' => 'textarea', 'default' => 'Trenchkid Support Foundation bridges the gap between poverty and opportunity - providing education, digital skills, and mentorship to underprivileged children across Ghana.'],
            'home_intro_title' => ['label' => 'Intro Title', 'type' => 'text', 'default' => 'Breaking Cycles, Building Futures'],
            'home_intro_body' => ['label' => 'Intro Brief', 'type' => 'textarea', 'default' => 'Trenchkid Support Foundation was born out of a deep belief: that every child, regardless of where they come from, deserves access to quality education, digital skills, and the mentorship to succeed.'],
            'home_intro_body_2' => ['label' => 'Intro Second Paragraph', 'type' => 'textarea', 'default' => "We don't give handouts - we give futures. Through targeted programs, community partnerships, and passionate volunteers, TSF transforms lives one child at a time."],
            'home_pillars_title' => ['label' => 'Pillars Title', 'type' => 'text', 'default' => 'What We Stand For'],
            'home_pillars_body' => ['label' => 'Pillars Brief', 'type' => 'textarea', 'default' => 'Four pillars guide every decision we make and every life we touch.'],
            'home_cta_title' => ['label' => 'CTA Title', 'type' => 'text', 'default' => "Ready to Change a Child's Life?"],
            'home_cta_body' => ['label' => 'CTA Text', 'type' => 'textarea', 'default' => "Your donation - no matter the size - directly funds education, health care, and mentorship for Ghana's underprivileged children. Every cedi counts."],
        ],
        'About Page' => [
            'about_hero_image' => ['label' => 'Hero Background Image', 'type' => 'image', 'default' => ''],
            'about_hero_title' => ['label' => 'Hero Title', 'type' => 'text', 'default' => 'About TSF'],
            'about_hero_body' => ['label' => 'Hero Text', 'type' => 'textarea', 'default' => "Learn our story, our mission, and the values that drive everything we do for Ghana's underprivileged children."],
            'about_story_title' => ['label' => 'Story Title', 'type' => 'text', 'default' => 'Founded on Hope & Purpose'],
            'about_brief' => ['label' => 'About Briefing', 'type' => 'textarea', 'default' => "Trenchkid Support Foundation (TSF) was established by passionate young Ghanaians who believe that no child's future should be limited by poverty. TSF creates pathways through education, digital skills, mentorship, and community support."],
            'about_story_body_2' => ['label' => 'Story Paragraph 2', 'type' => 'textarea', 'default' => "TSF was born as a direct response - a community-driven movement to ensure that no child's future is determined by the circumstances of their birth."],
            'about_story_body_3' => ['label' => 'Story Paragraph 3', 'type' => 'textarea', 'default' => 'Today, TSF empowers underprivileged children with education, digital skills, and mentorship, enabling them to build independent and impactful futures.'],
            'about_journey_title' => ['label' => 'Journey Title', 'type' => 'text', 'default' => 'How TSF Grew'],
            'about_journey_body' => ['label' => 'Journey Brief', 'type' => 'textarea', 'default' => 'From a small idea to a movement touching hundreds of lives across Ghana.'],
            'about_cta_title' => ['label' => 'CTA Title', 'type' => 'text', 'default' => 'Be Part of the Story'],
            'about_cta_body' => ['label' => 'CTA Text', 'type' => 'textarea', 'default' => 'Your donation today writes the next chapter for a child in need. Together, we can ensure no talent goes wasted.'],
        ],
        'Impact Page' => [
            'impact_hero_image' => ['label' => 'Hero Background Image', 'type' => 'image', 'default' => ''],
            'impact_hero_title' => ['label' => 'Hero Title', 'type' => 'text', 'default' => 'Our Impact'],
            'impact_hero_body' => ['label' => 'Hero Text', 'type' => 'textarea', 'default' => 'Numbers tell part of the story. But behind every number is a child whose life has been transformed.'],
            'impact_numbers_title' => ['label' => 'Numbers Title', 'type' => 'text', 'default' => 'TSF in Numbers'],
            'impact_numbers_body' => ['label' => 'Numbers Brief', 'type' => 'textarea', 'default' => 'Real results, real lives changed - tracked and verified.'],
            'impact_programs_title' => ['label' => 'Programs Title', 'type' => 'text', 'default' => 'What We Do'],
            'impact_testimonials_title' => ['label' => 'Testimonials Title', 'type' => 'text', 'default' => 'Voices of Change'],
            'impact_testimonials_body' => ['label' => 'Testimonials Brief', 'type' => 'textarea', 'default' => 'Hear directly from the children, families, and volunteers whose lives TSF has touched.'],
            'impact_reach_title' => ['label' => 'Reach Title', 'type' => 'text', 'default' => 'Spreading Across Ghana'],
            'impact_reach_body' => ['label' => 'Reach Text', 'type' => 'textarea', 'default' => 'What started in Bolga has grown into a nationwide movement. TSF now operates across multiple regions of Ghana, partnering with local communities, schools, and organisations.'],
        ],
        'Team Page' => [
            'team_hero_image' => ['label' => 'Hero Background Image', 'type' => 'image', 'default' => ''],
            'team_hero_title' => ['label' => 'Hero Title', 'type' => 'text', 'default' => 'Our Team'],
            'team_hero_body' => ['label' => 'Hero Text', 'type' => 'textarea', 'default' => "Meet the passionate individuals who dedicate their time and talent to empowering Ghana's children."],
            'team_group_image' => ['label' => 'Large Team Group Photo', 'type' => 'image', 'default' => ''],
            'team_group_title' => ['label' => 'Group Photo Title', 'type' => 'text', 'default' => 'Together for Every Child'],
            'team_group_body' => ['label' => 'Group Photo Brief', 'type' => 'textarea', 'default' => 'Upload a full team photo here when TSF has a group picture ready. This image appears before the leadership briefing on the public Team page.'],
            'team_leadership_title' => ['label' => 'Leadership Title', 'type' => 'text', 'default' => 'The People Behind TSF'],
            'team_leadership_body' => ['label' => 'Leadership Brief', 'type' => 'textarea', 'default' => 'Our team is built on passion, experience, and an unwavering belief in the potential of every child.'],
            'team_advisors_title' => ['label' => 'Advisors Title', 'type' => 'text', 'default' => 'Our Advisors'],
            'team_advisors_body' => ['label' => 'Advisors Brief', 'type' => 'textarea', 'default' => "Experienced professionals who guide and support TSF's strategic direction."],
            'team_cta_title' => ['label' => 'CTA Title', 'type' => 'text', 'default' => 'Join Our Team'],
            'team_cta_body' => ['label' => 'CTA Text', 'type' => 'textarea', 'default' => 'Are you passionate about empowering children and building futures? TSF is always looking for dedicated volunteers, mentors, and partners to join our movement.'],
        ],
        'News Page' => [
            'news_hero_image' => ['label' => 'Hero Background Image', 'type' => 'image', 'default' => ''],
            'news_hero_title' => ['label' => 'Hero Title', 'type' => 'text', 'default' => 'News & Articles'],
            'news_hero_body' => ['label' => 'Hero Text', 'type' => 'textarea', 'default' => 'Stay updated with the latest stories, updates, and announcements from Trenchkid Support Foundation.'],
            'news_sidebar_cta' => ['label' => 'Sidebar Donation Text', 'type' => 'textarea', 'default' => "Every story you read represents a real child's life. Help us write more stories like these."],
        ],
        'Donate Page' => [
            'donate_hero_image' => ['label' => 'Hero Background Image', 'type' => 'image', 'default' => ''],
            'donate_hero_title' => ['label' => 'Hero Title', 'type' => 'text', 'default' => 'Make a Donation'],
            'donate_hero_body' => ['label' => 'Hero Text', 'type' => 'textarea', 'default' => 'Your generosity transforms lives. Every donation goes directly to supporting underprivileged children in Ghana.'],
            'donate_form_title' => ['label' => 'Form Title', 'type' => 'text', 'default' => 'Your Donation Details'],
            'donate_form_body' => ['label' => 'Form Brief', 'type' => 'textarea', 'default' => 'Fill in your information below to complete your donation. All transactions are secure.'],
            'donate_security_note' => ['label' => 'Security Note', 'type' => 'textarea', 'default' => 'Your payment is secured and encrypted. TSF will never share your details.'],
            'donate_thanks_body' => ['label' => 'Thank You Message', 'type' => 'textarea', 'default' => "Paystack will handle your payment details securely. If you added an email, you may also receive a confirmation there. Your contribution directly funds education, healthcare, and mentorship for underprivileged children across Ghana."],
        ],
        'Contact Page' => [
            'contact_hero_image' => ['label' => 'Hero Background Image', 'type' => 'image', 'default' => ''],
            'contact_hero_title' => ['label' => 'Hero Title', 'type' => 'text', 'default' => 'Get in Touch'],
            'contact_hero_body' => ['label' => 'Hero Text', 'type' => 'textarea', 'default' => "Whether you want to donate, volunteer, partner, or simply learn more - we'd love to hear from you."],
            'contact_form_title' => ['label' => 'Form Title', 'type' => 'text', 'default' => 'Send Us a Message'],
            'contact_form_body' => ['label' => 'Form Brief', 'type' => 'textarea', 'default' => 'Fill out the form and our team will get back to you within 24 hours.'],
            'contact_info_title' => ['label' => 'Info Card Title', 'type' => 'text', 'default' => 'Contact Information'],
            'contact_address' => ['label' => 'Contact Address', 'type' => 'text', 'default' => 'Bolga, Upper East Region, Ghana'],
            'contact_phone' => ['label' => 'Contact Phone', 'type' => 'text', 'default' => '+233 XX XXX XXXX'],
            'contact_email' => ['label' => 'Contact Email', 'type' => 'text', 'default' => 'info@tsfghana.org'],
            'office_hours' => ['label' => 'Office Hours', 'type' => 'text', 'default' => 'Mon - Fri: 8:00 AM - 5:00 PM'],
            'contact_volunteer_body' => ['label' => 'Volunteer Card Text', 'type' => 'textarea', 'default' => 'TSF welcomes mentors, educators, developers, healthcare workers, and anyone who shares our passion for empowering children. Select "Volunteer" above and tell us your skills!'],
        ],
        'Gallery Page' => [
            'gallery_hero_image' => ['label' => 'Hero Background Image', 'type' => 'image', 'default' => ''],
            'gallery_hero_title' => ['label' => 'Hero Title', 'type' => 'text', 'default' => 'Gallery'],
            'gallery_hero_body' => ['label' => 'Hero Text', 'type' => 'textarea', 'default' => 'Moments from TSF programs, outreach, mentorship, and community support work.'],
            'gallery_section_title' => ['label' => 'Section Title', 'type' => 'text', 'default' => 'TSF Gallery'],
            'gallery_section_body' => ['label' => 'Section Brief', 'type' => 'textarea', 'default' => 'Browse snapshots from the communities, children, and volunteers behind the mission.'],
        ],
        'Social Links' => [
            'facebook_url' => ['label' => 'Facebook URL', 'type' => 'text', 'default' => 'contact.html'],
            'twitter_url' => ['label' => 'Twitter/X URL', 'type' => 'text', 'default' => 'contact.html'],
            'instagram_url' => ['label' => 'Instagram URL', 'type' => 'text', 'default' => 'contact.html'],
            'linkedin_url' => ['label' => 'LinkedIn URL', 'type' => 'text', 'default' => 'contact.html'],
            'whatsapp_url' => ['label' => 'WhatsApp URL', 'type' => 'text', 'default' => 'contact.html'],
        ],
    ];
}

function getContentSettingKeys(): array {
    $keys = [];
    foreach (getContentDefinitions() as $fields) {
        $keys = array_merge($keys, array_keys($fields));
    }
    return array_values(array_unique($keys));
}

function getDefaultContentSettings(): array {
    $defaults = [];
    foreach (getContentDefinitions() as $fields) {
        foreach ($fields as $key => $field) {
            $defaults[$key] = $field['default'] ?? '';
        }
    }
    return $defaults;
}
