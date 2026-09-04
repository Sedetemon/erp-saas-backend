<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');

            // REMPLACER ceci :
            // $table->nullableMorphs('subject', 'subject');
            // $table->nullableMorphs('causer', 'causer');

            // PAR ceci (support des UUID et des Int) :
            $table->nullableUuidMorphs('subject', 'subject');
            $table->nullableUuidMorphs('causer', 'causer');

            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
}
