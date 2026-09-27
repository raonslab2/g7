<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raonslab_ai_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->comment('G7 사용자 내부 식별자');
            $table->string('user_uuid', 36)->comment('감사 및 외부 identity mapping용 UUID');
            $table->string('request_id', 160)->unique()->comment('AI_GCS V2 canonical request ID');
            $table->string('project_id', 160)->comment('AgentOpt V2 프로젝트 ID');
            $table->string('provider', 32);
            $table->string('profile', 80)->default('default');
            $table->string('state', 32)->default('ACCEPTED');
            $table->string('title', 180)->nullable();
            $table->timestamp('last_observed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'created_at'], 'raonslab_ai_requests_user_created_index');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('raonslab_ai_requests', function (Blueprint $table) {
                $table->comment('G7 사용자와 AI_GCS V2 canonical request의 소유권 매핑');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raonslab_ai_requests');
    }
};
