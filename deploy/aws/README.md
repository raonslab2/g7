# AWS G7 운영 배포

로컬 원본 DB의 데이터 없는 테이블 구조와 빈 검토용 DB import 방법은 [DB 구조 스냅샷](../database/README.md)을 참고한다. 기존 AWS 운영 DB에 덮어쓰는 용도로 사용하지 않는다.

2026-10-03 요청의 AWS 전용 배포 설정이다. 런타임은 `/srv/g7/current`, 영속 파일은 `/var/lib/g7/storage`에 둔다. G7 코어와 제품 모듈을 바꾸지 않는다.

- 실제 URL: `http://g7.3.34.73.254.sslip.io/` (`/g7/`는 이 주소로 이동).
- 기본 Web은 AI_GCS에 유지한다. G7 엔진의 root API·router 계약 때문에 subpath를 억지로 바꾸지 않았다.
- PHP-FPM Unix socket, 제품 Nginx `127.0.0.1:18770`, MariaDB `127.0.0.1:3306`만 사용한다.
- 운영 설정과 초기 관리자·검증 token은 `/etc/g7-product/`에만 둔다. Git에는 포함하지 않는다.
- native `migrate`, 설치 기본 시더(샘플 옵션 없음), extension install/activate를 사용했다.
- AWS에 기존 DB가 없어서 새 격리 `g7_product`를 만들었다. 사용자가 기존 원본 DB 백업·이관을 후속 작업으로 보류했다. 기존 서버 데이터는 수정하지 않았다.
- 기존 native Page 본문은 Git에 없다. 승인 export와 기존 DB 이관 전에는 누락 Page를 만들어내지 않는다. 새 설치 기본 회사/정책 샘플 Page는 원본을 보존한 채 비공개로 전환했다.
- 공개 상담 접수는 기존 HTTPS·개인정보 설정 게이트를 그대로 유지한다. HTTP 공개 접수 PASS로 주장하지 않는다.
- 비식별 운영 검증 lead `2`는 private board에 영구 보존한다. 관리자 조회와 네 상태 전환을 공식 Service/API로 검증했다.

## 설치된 운영 유닛

`g7-product-fpm`, `g7-product-web`, `g7-product-queue`, `g7-product-scheduler.timer`, `g7-product-backup.timer`.

`public-proxy.conf`만 공용 Nginx의 host vhost로 연결한다. 기존 AI_GCS vhost는 총괄 lane에서 보존한다.
`backup.sh`는 DB·storage·운영 설정을 `/var/backups/g7-product`에 0700/0600으로 보관한다. `logrotate.conf`와 Laravel daily 로그를 사용한다.

## 재부팅 이후 읽기 검증

```bash
php deploy/aws/verify-persistence.php
systemctl is-active g7-product-fpm g7-product-web g7-product-queue mariadb
curl -f http://127.0.0.1:18770/
curl -f http://g7.3.34.73.254.sslip.io/
```

공개 business Page 전체 및 공개 Contact의 E2E 완료는 기존 콘텐츠 이관·운영 HTTPS·승인된 개인정보 설정이 필요하다.

부팅 후 새로운 비식별 내부 검증 lead 한 건 생성·관리자 조회:

```bash
php /srv/g7/current/deploy/aws/create-boot-verification-lead.php
```

부팅 ID에 따른 멱등키를 사용하므로 같은 부팅에서 재실행해도 새 행을 늘리지 않는다. 기존 lead 2는 그대로 유지한다. 공식 FormRequest 입력 규칙과 ConsultationService를 재사용하며 합성 동의 버전은 CLI 메모리에만 적용한다. 공개 HTTP 접수 게이트와 운영 개인정보 설정은 바꾸지 않는다. 공개 Contact E2E 성공으로 해석하지 않는다.
