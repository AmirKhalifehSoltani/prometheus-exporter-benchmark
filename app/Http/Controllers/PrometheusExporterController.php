<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redis as RedisFacade;
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
        // مقدار را در Redis افزایش می‌دهد (اتمیک)
        $count = RedisFacade::incr('request_count');

        // خروجی داده‌ها در فرمت Prometheus
        $metrics = "# HELP request_count_redis Number of requests stored in Redis\n";
        $metrics .= "# TYPE request_count_redis counter\n";
        $metrics .= "request_count_redis {$count}\n";

        return response($metrics)->header('Content-Type', 'text/plain');
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