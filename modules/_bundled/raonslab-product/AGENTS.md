# RAON Hub 제품화 모듈 — 에이전트 가이드

## 역할

`raonslab-product`는 G7 코어와 공식 템플릿을 수정하지 않고 브랜드, 공통 제품 UX, 구축 상담 폐루프를 적용합니다.
상담 전용 암호화 데이터만 소유하며 게시판·사용자·검색 기능은 기존 G7 API를 재사용합니다.

## 경계

- 작업 위치는 `modules/_bundled/raonslab-product`입니다. 활성 복사본은 직접 수정하지 않습니다.
- 홈 교체와 공통 UI 주입은 `resources/extensions`의 Layout Extension으로 처리합니다.
- 스타일과 작은 런타임 초기화는 공식 전역 모듈 에셋 수명주기를 사용합니다.
- AI 요청·이벤트·결과 기능은 별도 `raonslab-ai-workspace` 모듈이 소유합니다.
- 상담 PII는 전용 테이블과 read/manage 관리자 권한 안에만 두며 게시판·검색·일반 알림·AI 요청으로 자동 전달하지 않습니다.
- 공개 접수는 승인된 개인정보 설정과 HTTPS 운영 경로가 준비되기 전까지 fail-closed 상태로 둡니다.
- G7, AI_GCS, AgentOpt 데이터베이스를 연결하거나 외부 서비스 secret을 포함하지 않습니다.

## 반영과 검증

```bash
/usr/bin/php8.3 artisan module:build raonslab-product --production
/usr/bin/php8.3 artisan module:update raonslab-product --force
/usr/bin/php8.3 vendor/bin/phpunit modules/_bundled/raonslab-product/tests/Feature/ProductLayerContractTest.php --filter=ProductLayerContractTest
cd modules/_bundled/raonslab-product && npm run test:run
```

브라우저에서는 360px, 390px, 412px와 데스크톱에서 수평 넘침, 다국어 키 노출, 자산 오류를 확인합니다.

## 변경 의무

- 버전을 올리면 `module.json`, `composer.json`, `package.json`, `CHANGELOG.md`를 함께 갱신합니다.
- 사용자 문구는 `resources/lang/ko.json`과 `en.json`에 같이 추가합니다.
- 새 화면 요소는 loading, empty, validation, permission denied, server error, network failure 상태를 검토합니다.
- `DEMO`, `MOCK`, `SANDBOX`, `TEST` 같은 개발 용어를 사용자 문구에 넣지 않습니다.
- 기존 사용자의 light/dark/auto 선택을 덮어쓰지 않습니다.
