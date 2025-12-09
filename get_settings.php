<?php
// Fetch site settings from database
function getSettings($con)
{
    static $settings = null;

    if ($settings === null) {
        // Fetch from contact_info table ONCE
        $query = "SELECT * FROM contact_info LIMIT 1";
        $result = $con->query($query);

        if ($result && $result->num_rows > 0) {
            $contact_info = $result->fetch_assoc();

            // Build settings array from contact_info
            $settings = [
                // Site branding
                'site_name' => $contact_info['company_name'] ?? 'MobileStore',
                'site_tagline' => $contact_info['tagline'] ?? 'Premium mobiles, golden prices.',

                // Hero section defaults (not in contact_info)
                'hero_title' => 'Premium Mobile Sale',
                'hero_subtitle' => 'Luxury Smartphones at Golden Prices.',
                'hero_button_text' => 'Shop Now',
                'hero_button_link' => 'products.php',

                // Theme colors (not in contact_info)
                'primary_color' => '#0d9488',
                'secondary_color' => '#facc15',

                // Contact information
                'contact_email' => $contact_info['email'] ?? 'support@mobilestore.com',
                'contact_phone' => $contact_info['phone'] ?? '+91 98765 43210',
                'alternate_phone' => $contact_info['alternate_phone'] ?? '',
                'whatsapp_number' => $contact_info['whatsapp_number'] ?? '',

                // Address details
                'contact_address' => $contact_info['address'] ?? 'Tech City, India',
                'city' => $contact_info['city'] ?? '',
                'state' => $contact_info['state'] ?? '',
                'country' => $contact_info['country'] ?? '',
                'postal_code' => $contact_info['postal_code'] ?? '',

                // Social media links
                'facebook_url' => $contact_info['facebook_url'] ?? '',
                'twitter_url' => $contact_info['twitter_url'] ?? '',
                'instagram_url' => $contact_info['instagram_url'] ?? '',
                'linkedin_url' => $contact_info['linkedin_url'] ?? '',
                'youtube_url' => $contact_info['youtube_url'] ?? '',

                // Map
                'map_embed_url' => $contact_info['map_embed_url'] ?? ''
            ];

            $result->free();
        } else {
            // Default fallback if no data in database
            $settings = [
                'site_name' => 'MobileStore',
                'site_tagline' => 'Premium mobiles, golden prices.',
                'hero_title' => 'Premium Mobile Sale',
                'hero_subtitle' => 'Luxury Smartphones at Golden Prices.',
                'hero_button_text' => 'Shop Now',
                'hero_button_link' => 'products.php',
                'primary_color' => '#0d9488',
                'secondary_color' => '#facc15',
                'contact_email' => 'support@mobilestore.com',
                'contact_phone' => '+91 98765 43210',
                'alternate_phone' => '',
                'whatsapp_number' => '',
                'contact_address' => 'Tech City, India',
                'city' => '',
                'state' => '',
                'country' => '',
                'postal_code' => '',
                'facebook_url' => '',
                'twitter_url' => '',
                'instagram_url' => '',
                'linkedin_url' => '',
                'youtube_url' => '',
                'map_embed_url' => ''
            ];
        }
    }

    return $settings;
}

$site_settings = getSettings($con);
