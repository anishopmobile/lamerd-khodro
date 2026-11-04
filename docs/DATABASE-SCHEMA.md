# ساختار دیتابیس

پیشوند نصب: `ouskhodro_` (از `$wpdb->prefix`). نام جداول اختصاصی همیشه به‌صورت `$wpdb->prefix . 'lkp_...'` ساخته می‌شود.

## ۱. جداول اختصاصی

### `lkp_vehicles` — نگاشت خودروها به منبع

| ستون | نوع | توضیح |
|---|---|---|
| id | BIGINT UNSIGNED AI | شناسه داخلی |
| source | VARCHAR(32) | کد منبع (`cafekhodro`) |
| source_vehicle_id | VARCHAR(64) | شناسه خودرو در API |
| post_id | BIGINT UNSIGNED NULL | شناسه CPT `lk_vehicle` |
| name | VARCHAR(191) | نام خودرو از منبع |
| name_normalized | VARCHAR(191) | نام نرمال‌شده برای تطبیق/جستجو |
| brand | VARCHAR(100) NULL | برند استخراج‌شده |
| source_group | VARCHAR(100) NULL | گروه اصلی منبع (ایران خودرو، سایپا، …) |
| production_stopped | TINYINT(1) | وضعیت توقف تولید |
| created_at / updated_at | DATETIME | زمان ایجاد و آخرین تغییر |

کلیدها: `UNIQUE(source, source_vehicle_id)`، ایندکس `name_normalized`، `brand`، `post_id`.

### `lkp_current_prices` — آخرین قیمت معتبر

| ستون | نوع | توضیح |
|---|---|---|
| id | BIGINT UNSIGNED AI | |
| vehicle_id | BIGINT UNSIGNED | FK منطقی به `lkp_vehicles.id` |
| factory_price | BIGINT NULL | تومان |
| market_price | BIGINT NULL | تومان |
| change_amount | BIGINT NULL | تغییر اعلام‌شده منبع |
| change_percent | DECIMAL(8,2) NULL | درصد تغییر |
| previous_market_price | BIGINT NULL | قیمت بازار پیش از این به‌روزرسانی |
| declared_at | DATETIME NULL | زمان اعلام منبع |
| fetched_at | DATETIME | زمان دریافت (همیشه مقدار دارد) |
| data_status | VARCHAR(20) | `valid` / `incomplete` / `suspect` |
| source | VARCHAR(32) | |

کلیدها: `UNIQUE(vehicle_id, source)`، ایندکس `fetched_at`، `data_status`.

### `lkp_price_history` — تاریخچه دائمی

| ستون | نوع | توضیح |
|---|---|---|
| id | BIGINT UNSIGNED AI | |
| vehicle_id | BIGINT UNSIGNED | |
| factory_price / market_price | BIGINT NULL | وضعیت قیمت در لحظه ثبت |
| change_amount / change_percent | BIGINT / DECIMAL NULL | |
| recorded_at | DATETIME | زمان ثبت در لامرد خودرو |
| source | VARCHAR(32) | |
| record_type | VARCHAR(20) | `initial` / `market_change` / `factory_change` / `snapshot` |
| data_status | VARCHAR(20) | `valid` / `incomplete` / `suspect` |
| source_declared_at | DATETIME NULL | زمان اعلام منبع |

کلیدها: `KEY(vehicle_id, recorded_at)` و `KEY(recorded_at)`. رکورد تاریخچه هرگز UPDATE یا DELETE نمی‌شود.

### `lkp_price_sources` — منابع قیمت

`id`, `slug` (UNIQUE), `name`, `api_url`, `is_active`, `settings` (LONGTEXT/JSON), `last_success_at`, `last_error_at`, `last_error` (TEXT), `created_at`, `updated_at`.

### `lkp_sync_logs` — گزارش عملیات همگام‌سازی

`id`, `started_at`, `finished_at`, `duration` (ثانیه), `vehicles_fetched`, `vehicles_new`, `prices_changed`, `prices_unchanged`, `errors_count`, `status` (`running|success|partial|failed`), `trigger_type` (`manual|cron|cli`), `message` (TEXT), `details` (LONGTEXT JSON).

### `lkp_price_snapshots` — عکس لحظه‌ای روزانه

`id`, `snapshot_date` DATE, `vehicle_id`, `factory_price`, `market_price`, `change_amount`, `change_percent`, `data_status`, `created_at`.
کلید یکتا: `UNIQUE(snapshot_date, vehicle_id)` — اجرای مجدد همان روز رکورد تکراری نمی‌سازد.

## ۲. ساختار وردپرس مورد استفاده

| قلم | نام | توضیح |
|---|---|---|
| CPT | `lk_vehicle` | خودرو؛ اسلاگ `vehicles` |
| Taxonomy | `lk_brand` | برند؛ سلسله‌مراتبی؛ اسلاگ `brand` |
| Taxonomy | `lk_body_type` | نوع بدنه |
| Taxonomy | `lk_class` | کلاس خودرو |
| Post meta | `_lk_*` | مشخصات ساختاریافته + منبع |
| Term meta | `lk_brand_*` | نام لاتین، کشور، لوگو، سایت رسمی، توضیح |

### کلیدهای متای خودرو

عمومی: `_lk_name_en`, `_lk_model`, `_lk_year`, `_lk_country`, `_lk_supplier`, `_lk_production_status`, `_lk_supply_status`, `_lk_views`
موتور: `_lk_engine_type`, `_lk_displacement`, `_lk_cylinders`, `_lk_valves`, `_lk_power_hp`, `_lk_power_rpm`, `_lk_torque_nm`, `_lk_torque_rpm`, `_lk_fuel_type`, `_lk_emission_standard`
گیربکس: `_lk_gearbox_type`, `_lk_gears`, `_lk_drivetrain`
عملکرد: `_lk_accel`, `_lk_top_speed`, `_lk_fuel_city`, `_lk_fuel_road`, `_lk_fuel_combined`, `_lk_tank_capacity`
ابعاد: `_lk_length`, `_lk_width`, `_lk_height`, `_lk_wheelbase`, `_lk_weight`, `_lk_trunk`, `_lk_seats`
امکانات: `_lk_safety_features`, `_lk_comfort_features`, `_lk_brake_system`, `_lk_airbags`, `_lk_adas`, `_lk_infotainment`, `_lk_audio`, `_lk_camera`, `_lk_sensors`, `_lk_interior`
گالری: `_lk_gallery` (آرایه شناسه پیوست)
منبع: `_lk_source`, `_lk_source_id`, `_lk_last_synced`

مشخصات مشترک بین همه خودروها با یک تعریف واحد (`LK_Fields::groups()`) تولید می‌شوند؛ فرم مدیریت، اعتبارسنجی و رندر مشخصات از همان تعریف تغذیه می‌شوند تا تکرار کد نداشته باشیم.

### کلیدهای متای آگهی فروش (`lk_listing`)

`_lk_listing_price`, `_lk_listing_vehicle`, `_lk_listing_year`, `_lk_listing_km`, `_lk_listing_color`, `_lk_listing_gearbox`, `_lk_listing_condition`, `_lk_listing_city`, `_lk_listing_seller`, `_lk_listing_phone`, `_lk_listing_featured`, `_lk_listing_featured_days`, `_lk_listing_featured_until`

آگهی‌ها هیچ جدول اختصاصی ندارند و در `wp_posts` با نوع `lk_listing` ذخیره می‌شوند؛ `featured_until` یک timestamp یونیکس است و مقایسه آن با `time()` وضعیت ویژه را مشخص می‌کند.

## ۳. مهاجرت

`LKPE_Installer::maybe_upgrade()` با `dbDelta()` روی نسخه ذخیره‌شده در `lkpe_db_version` اجرا می‌شود. افزودن ستون جدید فقط با افزایش نسخه و دستور `ALTER` کنترل‌شده انجام می‌شود. حذف افزونه هیچ‌گاه جداول را پاک نمی‌کند (فقط با فایل `uninstall.php` اختیاری و تأیید صریح).
