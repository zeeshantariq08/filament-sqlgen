<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSqlGenLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sql_gen_logs', function (Blueprint $table) {
            $table->id();
            $table->text('question');  // New column for storing the user's question
            $table->json('sql_query'); // Use json type for storing SQL query
            $table->json('response');  // Use json type for storing response data
            $table->float('response_time_ms')->nullable();
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
        Schema::dropIfExists('sql_gen_logs');
    }
}
