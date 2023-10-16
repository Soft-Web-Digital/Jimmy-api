<?php

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
        Schema::create('system_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('datatype_id')->constrained();
            $table->string('code')->unique();
            $table->string('title');
            $table->longText('content');
            $table->string('hint');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_data');
    }
};
