# W04 native 설치 독립 재검증 — 진행 증거

Request `req_caef46f3473043048b5b4bc2ec41eaea`, 비작성자 CODEX attempt3. 고정 제품 SHA `fa5523175ac494cfbd13bbf89bf06b3ec91835a6`, tree `fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c`. 기본6853은 대상이 아니다. 이 문서는 실행 완료 후 실제 결과로 갱신한다. 현재 전체 설치/복구/회귀 PASS를 주장하지 않는다.

전용 TEST lease: `req81_travel_lab_test`, TCP127.0.0.1:3306, `req81_travel@127.0.0.1`. 승인된 부모 `.env.testing`에서 최소 TEST DB 필드만 guarded process 안에서 읽어 자체 ignored0600 환경을 생성했다. 부모 APP `.env`/서비스, Spring/production/타 Request 파일·계정·grants·capacity·설정은 변경하지 않는다. setup.php/APP extensions.php, 공식 자식, push/merge/deploy를 실행하지 않는다. 읽기 전용 native 보조 검토만 사용했다. 이전 Provider 실패 원인은 UNKNOWN이며 인증/쿼터 추측을 하지 않는다.

초기 직접 측정 및 원본 snapshot: **55테이블/104행**, 전체 행·DDL·객체 inventory `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e`. 다른 TEST 연결0, 이전 Request exact cwd/개별 argv/FD/잠금 소유자0, unreadable PID0. 원본 전체 SQL과 후속 safety dump는 자체 `storage/framework/testing/w04f-original/`0700 디렉터리의0600 파일로 보존한다. raw SQL/env/password/token/contact는 Git에 넣지 않는다. **기존128/639 NOT_PROVEN 유지.**

정상 native installed runtime은 APP_ENV=local, CACHE_STORE=database/mysql/cache/cache_locks, mailarray/queuesync/local이다. PHPUnit은 명시 array 및 기존 검토된 TEST `.env` 이름비교 workaround를 사용한다. 정상 가드/제품 소스는 수정하지 않는다. empty core 단계는 코어 bootstrap이 캐시 테이블 생성 전 캐시를 조회하므로, 0테이블·설치 미완료에만 installer-only array로 실제 native migrate를 실행한다. 이는 초기 migrate의 database-cache PASS 또는 공개 recipe 원형 PASS가 아니다.

실패 증거는 보존한다: 초기 native database-cache migrate bootstrap 실패; native 누락 번들 거부는 성공했으나 전체 비교에 cache 메타데이터 delta를 포함해 중단된 harness 실행; 사전 bootstrap 후 purge로 DB cache connection이 stale해져 template transaction을 끊은 harness 실행. 각 중단 이후 TEST 전체를 원본55/104 해시로 복구했다. 비작성자 source trace는 마지막 실패를 harness 원인으로 지목하며 수정 후 실제 native 재실행 결과가 필요하다.

원본 [intake](w04-native-final/evidence/intake/initial.json), [snapshot](w04-native-final/evidence/original/snapshot.json), [fixed source2266파일](w04-native-final/evidence/intake/source.json), [helper](w04-native-final/guard.php)와 고유 실패 실행 evidence는 후속 최종 판정에 결합한다. 공식 Validation/hostedCI는 NOT_RUN이고 내부 검토/로컬 테스트를 대체 상태로 쓰지 않는다.
