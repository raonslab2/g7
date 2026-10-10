<?php

return [
    'messages' => [
        'list_loaded' => 'Campaigns loaded.',
        'loaded' => 'Campaign loaded.',
    ],
    'errors' => [
        'not_found' => 'Campaign not found.',
        'slug_invalid' => 'The campaign address format is invalid.',
        'selector_prohibited' => 'Campaigns are limited to two fixed slots; the target cannot be selected.',
        'provisioning_not_allowed' => 'Campaign LAB provisioning is not allowed. Check TRAVEL_LAB_ISOLATED, TRAVEL_LAB_CAMPAIGN_PROVISIONING and --lab-confirm.',
        'unsafe_environment' => ':setting is :actual. Campaign provisioning is only allowed with :expected.',
        'actor_invalid' => 'Pass an existing administrator user ID with --actor.',
        'actor_not_permitted' => 'User :actor lacks admin page read/create permissions; nothing was created.',
        'slug_conflict' => 'The :slug page appeared during provisioning; it was not overwritten.',
    ],
];
