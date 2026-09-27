# RAON Hub 제품화 모듈

G7 코어와 공식 `sirsoft-basic` 템플릿을 수정하지 않고 제품 브랜드와 홈 UX를 적용합니다.
홈 교체는 Layout Extension, 스타일과 다크 우선 기본값은 공식 모듈 에셋 수명주기를 사용합니다.

```bash
/usr/bin/php8.3 artisan module:build raonslab-product --production
/usr/bin/php8.3 artisan module:install raonslab-product
/usr/bin/php8.3 artisan module:activate raonslab-product
```
