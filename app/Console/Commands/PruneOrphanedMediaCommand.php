<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneOrphanedMediaCommand extends Command
{
    protected $signature = 'media:prune-orphans {--dry-run : Faqat o\'chiriladigan yozuvlar sonini ko\'rsatadi}';

    protected $description = 'Hech qaysi jadvalda ishlatilmayotgan eski stored_media zaxira rasmlarini tozalaydi';

    public function handle(): int
    {
        if (! Schema::hasTable('stored_media')) {
            $this->info('stored_media jadvali mavjud emas.');

            return self::SUCCESS;
        }

        $usedPaths = collect()
            ->merge(DB::table('courses')->whereNotNull('thumbnail')->pluck('thumbnail'))
            ->merge(DB::table('teachers')->whereNotNull('photo')->pluck('photo'))
            ->merge(DB::table('teacher_results')->whereNotNull('student_photo')->pluck('student_photo'))
            ->merge(DB::table('teacher_results')->whereNotNull('certificate_image')->pluck('certificate_image'))
            ->merge(DB::table('announcements')->whereNotNull('image_path')->pluck('image_path'))
            ->merge(DB::table('chat_messages')->whereNotNull('image_path')->pluck('image_path'))
            ->merge(DB::table('subscriptions')->whereNotNull('receipt_image')->pluck('receipt_image'))
            ->map(fn ($p) => ltrim((string) $p, '/'))
            ->filter()
            ->unique()
            ->values();

        $query = DB::table('stored_media');
        if ($usedPaths->isNotEmpty()) {
            $query->whereNotIn('path', $usedPaths->all());
        }

        $orphanCount = $query->count();

        if ($this->option('dry-run')) {
            $this->info("Topilgan yetim (ishlatilmayotgan) media yozuvlari: {$orphanCount} ta.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Tozalandi: {$deleted} ta ishlatilmayotgan media yozuvi bazadan o'chirildi.");

        return self::SUCCESS;
    }
}
