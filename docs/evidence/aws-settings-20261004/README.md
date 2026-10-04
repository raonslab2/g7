# G7 AWS 설정 반영 — 2026-10-04

사용자가 제공한 이미지의 `9bed867e` 커밋을 확인하고 전체 백업을 복호화했다. archive 내부 체크섬은 모두 일치했다. 원본 SQL의 ngram 정의 14개를 별도 변환본에서만 제거하여 격리 DB import에 성공했다. 이 검증은 전체 데이터 운영 이관을 뜻하지 않는다.

요청의 **G7 세팅** 범위로 `SettingsService::setSetting()`을 사용해 사이트 이름, 설명, 관리자 이메일, 메일 발신 주소/이름, 코어 업데이트 GitHub URL 6개를 반영했다. 값이나 키 비밀정보는 이 증거에 출력하지 않는다. AWS URL과 DB/APP_KEY/인프라 설정, 생성된 sitemap 시각을 유지했다. 나머지 코어 설정과 이미 설치된 모듈/플러그인 설정은 원본과 같았다. 원본에만 있는 미설치 CKEditor5 설정은 반영하지 않았다.

운영 변경 전 `deploy/aws/backup.sh`로 기존 DB·storage/app·운영 설정을 root 전용 백업 경로에 보존했다. 환경설정 쓰기 후 FPM과 큐를 재시작했다. 재실행 시 변경 키 0개, 관리자 API 모두 200, 기존 private lead 2 보존과 공개 접수 제한 유지, 공개 사이트 HTML 200을 확인했다. 회원·게시글·Page 데이터는 변경하지 않았다. 공개 business Page API는 기존과 같이 404이며 콘텐츠 이관은 완료되지 않았다.

검증: `php -l deploy/aws/import-core-settings.php`, dry-run, 공식 Service 저장, 재실행 멱등성, `verify-runtime.php`, `verify-persistence.php` 통과. 실제 결과는 이 디렉터리의 JSON에 기록했다.

재실행은 검증된 복호화 설정 디렉터리를 사용한다. 기본은 읽기 전용이며 `--apply`가 있을 때만 저장한다.

```bash
php deploy/aws/import-core-settings.php /secure/restore/storage/app/settings
php deploy/aws/import-core-settings.php /secure/restore/storage/app/settings --apply
```

공개 코어/확장 API는 바꾸지 않은 AWS 운영 도구이므로 버전 제약 동기화 대상은 없다. 복호화 자료와 격리 DB는 검증 후 제거했다.
