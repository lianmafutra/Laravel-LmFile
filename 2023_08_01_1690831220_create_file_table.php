<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileTable extends Migration
{
    public function up()
    {
        Schema::create('file', function (Blueprint $table) {

		$table->increments(id);
		$table->string('file_id',500)->nullable()->default('NULL');
		$table->integer('model_id',11);
		$table->text('name_origin');
		$table->text('name_hash');
		$table->string('path',100);
		$table->string('mime',15);
		$table->float('size')->nullable()->default('NULL');
		$table->integer('order',11)->nullable()->default('NULL');
		$table->integer('created_by',11)->nullable()->default('NULL');
		$table->timestamp('created_at')->nullable()->default('NULL');
		$table->timestamp('updated_at')->nullable()->default('NULL');
		$table->primary('id');

        });
    }

    public function down()
    {
        Schema::dropIfExists('file');
    }
}
