<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('price')->default(0)->after('course_id');
        });

        // Mavjud obunalarga kursning joriy narxini yozib qo'yamiz
        $courses = DB::table('courses')->pluck('price', 'id');
        foreach ($courses as $courseId => $price) {
            DB::table('subscriptions')
                ->where('course_id', $courseId)
                ->where('price', 0)
                ->update(['price' => $price]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
