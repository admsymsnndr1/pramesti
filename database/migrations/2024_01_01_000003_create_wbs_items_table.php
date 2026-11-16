<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wbs_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('wbs_items')->cascadeOnDelete();
            $table->unsignedInteger('pic_id')->nullable();
            $table->text('description')->nullable();
            $table->string('code', 50)->nullable();
            $table->text('name');
            $table->enum('type', ['wbs', 'task'])->default('wbs');
            // tasklist status of leaf items
            $table->enum('status', ['open', 'on_progress', 'on_testing', 'revisi_tester', 'complete'])->default('open');
            // leaf: entered manually; parent: weighted average of its children (rollup)
            $table->decimal('progress', 5, 2)->default(0);
            $table->timestamp('progress_updated_at')->nullable();
            // relative to the siblings on the same level
            $table->decimal('weight', 5, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('created_by');
            $table->timestamps();

            // the tree is always queried per project / per PIC
            $table->index(['project_id', 'parent_id', 'sort_order'], 'wbs_items_project_tree_index');
            $table->foreign('pic_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wbs_items');
    }
};
