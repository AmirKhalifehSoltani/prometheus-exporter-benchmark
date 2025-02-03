<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis;

class PrometheusExporterController extends Controller
{
    /**
     * ذخیره و دریافت متریک از Redis
     */
    public function exportWithRedis()
    {
        // تنظیمات Redis Adapter
        $redisAdapter = new Redis([
            'host' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 0.1, // زمان تایم‌اوت کوتاه برای Performance بهتر
        ]);

        // مقداردهی به CollectorRegistry با Redis
        $registry = new CollectorRegistry($redisAdapter);

        // ایجاد متریک برای Prometheus
        $counter = $registry->getOrRegisterCounter('my_app', 'request_count_redis', 'Request Count from Redis');
        $counter->inc();

        // خروجی به فرمت متریک‌های Prometheus
        $renderer = new RenderTextFormat();
        return response($renderer->render($registry->getMetricFamilySamples()))
            ->header('Content-Type', RenderTextFormat::MIME_TYPE);
    }

    /**
     * ذخیره و دریافت متریک از File
     */
    public function exportWithFile()
    {
        $filePath = storage_path('app/metrics/request_count.txt');

        // باز کردن فایل با قفل مشترک
        $file = fopen($filePath, 'c+');
        if (!$file) {
            return response("Error opening file", 500);
        }

        // قفل‌گذاری برای جلوگیری از همزمانی
        flock($file, LOCK_EX);

        // خواندن مقدار قبلی
        $fileValue = (int) stream_get_contents($file);
        $fileValue++;

        // بازنشانی اشاره‌گر و نوشتن مقدار جدید
        ftruncate($file, 0);
        rewind($file);
        fwrite($file, $fileValue);

        // آزادسازی قفل و بستن فایل
        flock($file, LOCK_UN);
        fclose($file);

        // خروجی Prometheus
        $metrics = "# HELP request_count_file Number of requests stored in file\n";
        $metrics .= "# TYPE request_count_file counter\n";
        $metrics .= "request_count_file {$fileValue}\n";

        return response($metrics)->header('Content-Type', 'text/plain');
    }
}