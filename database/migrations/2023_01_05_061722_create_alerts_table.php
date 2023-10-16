<?php

use App\Enums\AlertStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('creator_id')->constrained('admins');
            $table->string('title');
            $table->longText('body');
            $table->string('status')->default(AlertStatus::PENDING->value)->comment('Enum:AlertStatus');
            $table->string('target_user')->comment('Enum:AlertTargetUser');
            $table->unsignedBigInteger('target_user_count')->nullable();
            $table->timestamp('dispatched_at');
            $table->json('channels');
            $table->text('failed_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('alerts');
    }
};
