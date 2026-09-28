# RAON Agent Factory 제품 모듈

G7 코어와 공식 `sirsoft-basic` 템플릿을 수정하지 않고 사업 홈, 제품 UX, 구축 상담 폐루프를 제공합니다.
홈 교체와 공개 정보·정책 화면 7종은 Layout Extension과 모듈 route로 제공하고, 스타일과 런타임은
공식 모듈 에셋 수명주기를 사용합니다. 신규 상담은 공개 비활성·항상 비밀·관리자 전용 G7 board에
공식 `PostService`로 저장하며 검색·알림·AI 작업공간으로 자동 전달하지 않습니다. 기존 product 상담
테이블은 migration을 바꾸지 않고 legacy 감사 경계로만 남으며 데이터가 있으면 신규 접수를 닫습니다.

기존 `questions` 게시판에는 source-controlled Q&A 콘텐츠 명령으로 합성 운영 안내 8개와 depth-1 답변
8개를 적용할 수 있습니다. 이 명령은 독립 QA의 rollback·notification blocker가 해결되고 다시 승인되기
전에는 production에서 실행하지 않습니다.

온라인 접수는 기본적으로 닫혀 있습니다. 승인된 개인정보 동의 문안·정책 URL·보관 안내·담당 정보와
HTTPS 공개 설정을 운영자가 모두 제공한 뒤에만 `RAON_CONSULTATION_INTAKE_ENABLED=true`로 엽니다.

```bash
/usr/bin/php8.3 artisan module:build raonslab-product --production
/usr/bin/php8.3 artisan module:install raonslab-product
/usr/bin/php8.3 artisan module:activate raonslab-product
```

제품 정의와 공개 전 조건은 [docs/README.md](docs/README.md)를 참고합니다.
