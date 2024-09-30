<?php

use HXM\DatabaseSystemConfig\Models\SystemConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InstallDatabaseSystemConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('index')->default('default');
            $table->string('value_type');
            $table->timestamps();

            $table->index(['group', 'index']);
            $table->unique(['group', 'index']);
        });

        Schema::create('system_config_bool_values', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id');
            $table->boolean('value');
            $table->foreign('parent_id')->references('id')->on('system_configs')->cascadeOnDelete();
        });

        Schema::create('system_config_int_values', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id');
            $table->bigInteger('value');
            $table->foreign('parent_id')->references('id')->on('system_configs')->cascadeOnDelete();
        });

        Schema::create('system_config_float_values', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id');
            $table->float('value');
            $table->foreign('parent_id')->references('id')->on('system_configs')->cascadeOnDelete();
        });
        Schema::create('system_config_datetime_values', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id');
            $table->dateTime('value');
            $table->foreign('parent_id')->references('id')->on('system_configs')->cascadeOnDelete();
        });
        Schema::create('system_config_string_values', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id');
            $table->string('value');
            $table->foreign('parent_id')->references('id')->on('system_configs')->cascadeOnDelete();
        });
        Schema::create('system_config_text_values', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id');
            $table->text('value');
            $table->foreign('parent_id')->references('id')->on('system_configs')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_config_text_values');
        Schema::dropIfExists('system_config_string_values');
        Schema::dropIfExists('system_config_float_values');
        Schema::dropIfExists('system_config_int_values');
        Schema::dropIfExists('system_config_bool_values');
        Schema::dropIfExists('system_config_datetime_values');
        Schema::dropIfExists('system_configs');
    }
}
