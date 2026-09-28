# RAON Agent Factory 제품 모듈

G7 코어와 공식 `sirsoft-basic` 템플릿을 수정하지 않고 사업 홈, 제품 UX, 구축 상담 폐루프를 제공합니다.
홈 교체는 Layout Extension, 스타일과 런타임은 공식 모듈 에셋 수명주기를 사용합니다. 상담 데이터는
이 모듈의 전용 암호화 테이블과 관리자 권한 경계 안에서만 처리하며 게시판·검색·알림·AI 작업공간으로
자동 전달하지 않습니다.

온라인 접수는 기본적으로 닫혀 있습니다. 승인된 개인정보 동의 문안·정책 URL·보관 안내·담당 정보와
HTTPS 공개 설정을 운영자가 모두 제공한 뒤에만 `RAON_CONSULTATION_INTAKE_ENABLED=true`로 엽니다.

```bash
/usr/bin/php8.3 artisan module:build raonslab-product --production
/usr/bin/php8.3 artisan module:install raonslab-product
/usr/bin/php8.3 artisan module:activate raonslab-product
```

제품 정의와 공개 전 조건은 [docs/README.md](docs/README.md)를 참고합니다.
