<?php

return [
    'messages' => [
        'notices_loaded' => 'Notices loaded.',
        'faqs_loaded' => 'FAQs loaded.',
        'post_loaded' => 'Post loaded.',
        'questions_loaded' => 'Questions loaded.',
        'question_loaded' => 'Question loaded.',
        'question_created' => 'Your question was submitted. (LAB test intake; no notifications are sent.)',
        'question_updated' => 'Your question was updated.',
    ],
    'errors' => [
        'not_ready' => 'The support boards are not ready yet.',
        'question_not_found' => 'Question not found.',
        'post_not_found' => 'Post not found.',
        'provisioning_not_allowed' => 'LAB provisioning is not allowed. Check the configuration and --lab-confirm.',
        'board_misconfigured' => 'The :slug board does not match the support safety baseline and was left unchanged.',
        'search_engine_unsafe' => 'The search driver (:driver) can send private questions to an external index, so the questions board was not prepared. Only the mysql-fulltext configuration is allowed.',
    ],
    'attributes' => [
        'title' => 'title',
        'content' => 'content',
    ],
];
