<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raonslab_product_consultations', function (Blueprint $table): void {
            $table->bigIncrements('id')->comment('상담 내부 식별자');
            $table->string('reference', 40)->unique()->comment('외부 노출용 불투명 접수 번호');
            $table->char('idempotency_key_hash', 64)->unique()->comment('멱등성 키 SHA-256 해시');
            $table->char('payload_hash', 64)->comment('정규화 요청 HMAC-SHA-256');
            $table->text('contact_name')->comment('암호화된 담당자 이름');
            $table->text('email')->comment('암호화된 이메일');
            $table->text('company')->nullable()->comment('암호화된 회사명');
            $table->text('phone')->nullable()->comment('암호화된 전화번호');
            $table->text('service_interest')->nullable()->comment('암호화된 관심 서비스');
            $table->text('message')->comment('암호화된 상담 내용');
            $table->string('privacy_consent_version', 100)->comment('동의한 개인정보 처리 문안 버전');
            $table->timestamp('privacy_consented_at')->comment('개인정보 처리 동의 시각');
            $table->string('status', 32)->default('NEW')->comment('처리 상태 (NEW, CONTACTED, QUALIFIED, CLOSED)');
            $table->text('close_outcome')->nullable()->comment('암호화된 종결 결과');
            $table->string('mail_status', 32)->default('NOT_CONFIGURED')->comment('메일 상태 (NOT_CONFIGURED, PENDING, SENT, FAILED)');
            $table->timestamp('mail_attempted_at')->nullable()->comment('메일 발송 시도 시각');
            $table->timestamp('mail_sent_at')->nullable()->comment('메일 발송 완료 시각');
            $table->timestamps();

            $table->index(['status', 'created_at'], 'raonslab_consultations_status_created_idx');
        });

        Schema::create('raonslab_product_consultation_histories', function (Blueprint $table): void {
            $table->bigIncrements('id')->comment('상담 이력 식별자');
            $table->unsignedBigInteger('consultation_id')->comment('상담 내부 식별자');
            $table->string('event_type', 32)->comment('이력 유형 (CREATED, NOTE_ADDED, STATUS_CHANGED)');
            $table->string('from_status', 32)->nullable()->comment('변경 전 처리 상태');
            $table->string('to_status', 32)->nullable()->comment('변경 후 처리 상태');
            $table->text('note')->nullable()->comment('암호화된 관리자 내부 메모');
            $table->text('close_outcome')->nullable()->comment('암호화된 종결 결과 스냅샷');
            $table->unsignedBigInteger('actor_user_id')->nullable()->comment('변경 관리자 사용자 식별자');
            $table->timestamps();

            $table->foreign('consultation_id', 'raon_consultation_history_consultation_fk')
                ->references('id')->on('raonslab_product_consultations')->cascadeOnDelete();
            $table->foreign('actor_user_id', 'raon_consultation_history_actor_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->index(['consultation_id', 'created_at'], 'raonslab_consultation_history_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raonslab_product_consultation_histories');
        Schema::dropIfExists('raonslab_product_consultations');
    }
};
