<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_lab_inquiries', function (Blueprint $table) {
            $table->json('calculation_snapshot')->nullable()->comment('접수 시점 공식 커머스 계산 전체 기록');
        });
        Schema::create('travel_lab_inquiry_events', function (Blueprint $table) {
            $table->id()->comment('시험 문의 상태 처리 이력 ID');
            $table->foreignId('inquiry_id')->comment('시험 문의 ID')->constrained('travel_lab_inquiries')->restrictOnDelete();
            $table->foreignId('actor_id')->comment('처리 회원 ID')->constrained('users')->restrictOnDelete();
            $table->string('from_status', 30)->nullable()->comment('이전 시험 상태 (최초 접수 null)');
            $table->string('to_status', 30)->comment('처리 후 시험 상태');
            $table->text('note')->nullable()->comment('처리 메모');
            $table->timestamp('created_at')->comment('처리 일시');
            $table->index(['inquiry_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_lab_inquiry_events');
        Schema::table('travel_lab_inquiries', fn (Blueprint $table) => $table->dropColumn('calculation_snapshot'));
    }
};
