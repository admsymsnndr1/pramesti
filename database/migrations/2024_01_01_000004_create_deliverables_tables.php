<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wbs_item_id')->constrained('wbs_items')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->unsignedInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users');
        });

        Schema::create('deliverable_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deliverable_id')->constrained('deliverables')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedInteger('file_size');
            $table->string('file_type', 20);
            $table->unsignedInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deliverable_files');
        Schema::dropIfExists('deliverables');
    }
};
