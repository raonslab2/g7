# G7 Travel Lab 0.1.0

이커머스 상품과 옵션으로 여행을 탐색하는 테스트 모듈입니다. 실제 예약·주문·결제·환불·메일·SMS·외부 제공자 연결을 만들지 않습니다. 스케줄러도 없습니다.

필요 버전: 코어 `>=7.0.11`, `sirsoft-ecommerce >=1.2.1`, `sirsoft-board >=1.1.2`, `sirsoft-page >=1.1.2`. 네임스페이스는 `Modules\Raonslab\TravelLab`입니다. 사용자 템플릿은 별도 작업 범위입니다.

[도메인 계약](docs/domain.md), [API 계약·예시](docs/domain-api.md), [생성된 API 레퍼런스](docs/api/README.md), [구현 근거·통합 체크](docs/domain-evidence.md), [테스트](tests/README.md)를 참고하십시오.

모듈 설치 시 샘플을 만들지 않습니다. 별도 격리 실험 환경에 설치·활성화한 뒤에만 아래 명령으로 고정 SKU 8개와 출발 옵션 24개를 생성할 수 있습니다. 이커머스 설치 시 상품 채번 시퀀스가 준비되어 있어야 합니다.

```sh
php artisan module:seed raonslab-travel_lab --sample
```

재실행은 기존 상품·옵션·메타데이터·출발 일정·테스트 확보 인원을 덮어쓰지 않습니다. 기존 샘플에 대한 삭제나 수정은 운영자 의사로 보존합니다. 초기 출발일은 `config/catalog.php`의 `sample_departure_anchor`(기본 `2026-11-01`)에 고정되어 있으므로 시간이 지나면 공개 목록에서 자연스럽게 빠집니다.

이 커밋의 `src/routes/catalog.php`는 리드가 소유한 `src/routes/api.php`에서 `require __DIR__.'/catalog.php';`로 연결해야 합니다. 이 범위에는 설치·활성화·배포 실행이 포함되지 않습니다.
