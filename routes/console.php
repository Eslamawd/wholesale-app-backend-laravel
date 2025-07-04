<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // هذا السطر يجب إضافته

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// *******************************************************************
// أضف جدولة أوامر ZDDK هنا:
// *******************************************************************

// جدولة أمر مزامنة المنتجات والفئات (يمكنك اختيار يوميًا أو كل ساعة حسب الحاجة)
// يستخدم الكلاس الكامل للأمر
Schedule::command(\App\Console\Commands\SyncZddkProducts::class)->daily();
// إذا أردت تشغيله كل ساعة: Schedule::command(\App\Console\Commands\SyncZddkProducts::class)->hourly();

// جدولة أمر التحقق من حالة الطلبات (يجب أن يتم تشغيله بشكل متكرر، مثلاً كل 5 دقائق)
// يستخدم الكلاس الكامل للأمر
Schedule::command(\App\Console\Commands\CheckZddkOrderStatus::class)->everyFiveMinutes();

// *******************************************************************