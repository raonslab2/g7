# RAON Agent Factory 제품 모듈

G7 코어와 공식 `sirsoft-basic` 템플릿을 수정하지 않고 사업 홈, 제품 UX, 구축 상담 폐루프를 제공합니다.
홈은 Layout Extension으로, 공개 정보 6종·정책 5종 문서는 공식 `sirsoft-page`와 `/page/{slug}`로 제공합니다.
이 모듈은 문서 분류(`resources/taxonomy/info-policy.json`), native Page 화면의 RAON navigation·presentation과
기존 URL 301 호환만 소유하며 본문·SEO·버전은 Page 관리자와 DB가 소유합니다. 스타일과 런타임은 공식 모듈 에셋 수명주기를 사용합니다. 신규 상담은 공개 비활성·항상 비밀·관리자 전용 G7 board에
공식 `PostService`로 저장하며 검색·알림·AI 작업공간으로 자동 전달하지 않습니다. 기존 product 상담
테이블은 migration을 바꾸지 않고 legacy 감사 경계로만 남으며 데이터가 있으면 신규 접수를 닫습니다.

기존 `questions` 게시판에는 source-controlled Q&A 콘텐츠 명령으로 합성 운영 안내 8개와 depth-1 답변
8개를 적용할 수 있습니다. rollback 소유권·알림 억제·topology·동시 실행 보강과 focused regression은
PASS했으며, fixed candidate 독립 QA와 delivery preflight 승인 전에는 production에서 실행하지 않습니다.

온라인 접수는 기본적으로 닫혀 있습니다. 승인된 개인정보 동의 문안·정책 URL·보관 안내·담당 정보와
HTTPS 공개 설정을 운영자가 모두 제공한 뒤에만 `RAON_CONSULTATION_INTAKE_ENABLED=true`로 엽니다.

```bash
/usr/bin/php8.3 artisan module:build raonslab-product --production
/usr/bin/php8.3 artisan module:install raonslab-product
/usr/bin/php8.3 artisan module:activate raonslab-product
```

승인된 native Page export를 새 환경에 준비할 때는 먼저 dry-run하고, 같은 파일로 bootstrap합니다. 이 명령은
기존 slug를 수정하지 않습니다.

```bash
/usr/bin/php8.3 artisan raonslab-product:bootstrap-pages /secure/approved-raon-pages.json --actor=SUPER_ADMIN_ID --dry-run
/usr/bin/php8.3 artisan raonslab-product:bootstrap-pages /secure/approved-raon-pages.json --actor=SUPER_ADMIN_ID
```

그대로 남은 G7 샘플 Page 4종(about/faq/contact/refund)은 백업 뒤 운영자가 승인된 외부 payload로 한 번 교체합니다.
사전 상태가 하나라도 다르면 아무것도 쓰지 않으며 module install/update는 이 명령을 호출하지 않습니다.

```bash
/usr/bin/php8.3 artisan raonslab-product:remediate-info-pages /secure/approved-info-pages.json --actor=SUPER_ADMIN_ID --dry-run
/usr/bin/php8.3 artisan raonslab-product:remediate-info-pages /secure/approved-info-pages.json --actor=SUPER_ADMIN_ID
```

문서 분류를 바꾸면 `npm run taxonomy:sync`로 extension JSON을 다시 만들고 `npm run taxonomy:check`로 확인합니다.

제품 정의, native Page 운영 계약과 공개 전 조건은 [docs/README.md](docs/README.md)를 참고합니다.
