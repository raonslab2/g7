<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_lab_products', function (Blueprint $table) {
            $table->id()->comment('여행 메타데이터 ID');
            $table->foreignId('product_id')->unique()->comment('이커머스 상품 ID')->constrained('ecommerce_products')->restrictOnDelete();
            $table->string('region', 50)->comment('지역 분류');
            $table->string('theme', 50)->comment('여행 테마');
            $table->unsignedInteger('duration_days')->comment('여행 일수');
            $table->json('summary')->comment('다국어 요약');
            $table->json('itinerary')->comment('여행 일정');
            $table->boolean('published')->default(false)->comment('공개 여부 (1: 공개, 0: 비공개)');
            $table->timestamps();
            $table->index(['published', 'region', 'theme']);
        });
        Schema::create('travel_lab_departures', function (Blueprint $table) {
            $table->id()->comment('테스트 출발 일정 ID');
            $table->foreignId('product_id')->comment('이커머스 상품 ID')->constrained('ecommerce_products')->restrictOnDelete();
            $table->foreignId('product_option_id')->unique()->comment('이커머스 출발 옵션 ID')->constrained('ecommerce_product_options')->restrictOnDelete();
            $table->date('departure_date')->comment('출발 날짜');
            $table->date('return_date')->comment('귀환 날짜');
            $table->unsignedInteger('capacity')->comment('테스트 정원');
            $table->unsignedInteger('reserved')->default(0)->comment('테스트 문의로 확보한 인원 (실제 예약 아님)');
            $table->boolean('is_active')->default(true)->comment('활성 여부 (1: 활성, 0: 비활성)');
            $table->timestamps();
            $table->index(['product_id', 'is_active', 'departure_date'], 'tl_departures_product_active_date');
        });
        Schema::create('travel_lab_inquiries', function (Blueprint $table) {
            $table->id()->comment('테스트 문의 ID');
            $table->foreignId('user_id')->comment('문의 회원 ID')->constrained('users')->restrictOnDelete();
            $table->string('idempotency_key', 100)->comment('회원별 재요청 식별키');
            $table->char('payload_hash', 64)->comment('요청 내용 SHA256');
            $table->string('status', 30)->default('TEST_INQUIRY')->comment('TEST_INQUIRY/UNDER_REVIEW/TEST_ACCEPTED/DECLINED/CANCELLED (실제 예약 아님)');
            $table->decimal('total_amount', 20, 2)->comment('문의 시점 테스트 합계');
            $table->string('currency_code', 3)->default('KRW')->comment('문의 시점 통화');
            $table->json('contact')->nullable()->comment('문의 연락 정보');
            $table->text('admin_note')->nullable()->comment('관리자 메모');
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key'], 'travel_lab_inquiries_user_key_unique');
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
        Schema::create('travel_lab_inquiry_items', function (Blueprint $table) {
            $table->id()->comment('테스트 문의 항목 ID');
            $table->foreignId('inquiry_id')->comment('테스트 문의 ID')->constrained('travel_lab_inquiries')->restrictOnDelete();
            $table->foreignId('departure_id')->comment('테스트 출발 일정 ID')->constrained('travel_lab_departures')->restrictOnDelete();
            $table->foreignId('product_id')->comment('이커머스 상품 ID')->constrained('ecommerce_products')->restrictOnDelete();
            $table->foreignId('product_option_id')->comment('이커머스 옵션 ID')->constrained('ecommerce_product_options')->restrictOnDelete();
            $table->unsignedInteger('quantity')->comment('인원수 (장바구니 quantity와 동일)');
            $table->decimal('unit_price', 20, 2)->comment('문의 시점 이커머스 단가');
            $table->decimal('line_total', 20, 2)->comment('문의 시점 항목 합계');
            $table->json('product_name')->comment('문의 시점 다국어 상품명');
            $table->date('departure_date')->comment('문의 시점 출발 날짜');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_lab_inquiry_items');
        Schema::dropIfExists('travel_lab_inquiries');
        Schema::dropIfExists('travel_lab_departures');
        Schema::dropIfExists('travel_lab_products');
    }
};
