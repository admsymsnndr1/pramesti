<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per project per day, written by `php artisan dashboard:snapshot`.
     * Feeds the trend charts and the "actual" line of the S-curve.
     */
    public function up()
    {
        Schema::create('project_daily_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->date('snapshot_date');

            // tasklist (leaf WBS items) per status
            $table->unsignedInteger('open')->default(0);
            $table->unsignedInteger('on_progress')->default(0);
            $table->unsignedInteger('on_testing')->default(0);
            $table->unsignedInteger('revisi_tester')->default(0);
            $table->unsignedInteger('complete')->default(0);
            $table->unsignedInteger('total')->default(0);

            $table->decimal('progress', 5, 2)->default(0);   // project progress (WBS rollup)
            $table->json('level_progress')->nullable();     // {"1": 33.32, "2": 33.58, ...}
            $table->timestamps();

            $table->unique(['project_id', 'snapshot_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_daily_snapshots');
    }
};
