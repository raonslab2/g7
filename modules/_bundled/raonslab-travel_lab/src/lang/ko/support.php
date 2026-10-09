<?php

return [
    'messages' => [
        'notices_loaded' => '공지사항을 불러왔습니다.',
        'faqs_loaded' => '자주 묻는 질문을 불러왔습니다.',
        'post_loaded' => '게시글을 불러왔습니다.',
        'questions_loaded' => '문의 목록을 불러왔습니다.',
        'question_loaded' => '문의를 불러왔습니다.',
        'question_created' => '문의가 등록되었습니다. (LAB 테스트 접수, 알림은 발송되지 않습니다.)',
        'question_updated' => '문의가 수정되었습니다.',
    ],
    'errors' => [
        'not_ready' => '고객지원 게시판이 아직 준비되지 않았습니다.',
        'question_not_found' => '문의를 찾을 수 없습니다.',
        'post_not_found' => '게시글을 찾을 수 없습니다.',
        'provisioning_not_allowed' => 'LAB 프로비저닝이 허용되지 않았습니다. 설정과 --lab-confirm 을 확인하세요.',
        'board_misconfigured' => ':slug 게시판이 고객지원 보안 기준과 다르게 설정되어 있어 변경하지 않았습니다.',
        'search_engine_unsafe' => '검색 드라이버(:driver)가 비공개 문의를 외부 색인으로 보낼 수 있어 문의 게시판을 준비하지 않았습니다. mysql-fulltext 구성에서만 허용됩니다.',
    ],
    'attributes' => [
        'title' => '제목',
        'content' => '내용',
    ],
];
