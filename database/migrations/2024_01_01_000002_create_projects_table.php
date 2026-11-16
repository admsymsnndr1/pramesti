<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'ongoing', 'completed', 'on_hold'])->default('draft');
            // cached result of the WBS rollup (WbsProgressService), so lists do not need to recurse
            $table->decimal('progress', 5, 2)->default(0);
            $table->unsignedInteger('pm_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('pm_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
};
