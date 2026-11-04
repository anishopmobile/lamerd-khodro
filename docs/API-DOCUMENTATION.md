# مستندات API لامرد خودرو

نسخه: `lamerd/v1` — فقط خواندنی و عمومی. مسیر پایه: `/wp-json/lamerd/v1/`

## قواعد مشترک

- خروجی JSON با `Content-Type: application/json; charset=UTF-8`.
- همه قیمت‌ها به **تومان** و از نوع عدد صحیح هستند. قیمت ناموجود `null` است و هرگز `0` نیست.
- صفحه‌بندی با `page` و `per_page` (پیش‌فرض ۲۰، حداکثر ۵۰).
- پاسخ‌ها با هدر `X-LK-Cache` و `Cache-Control: public, max-age=60` قابل کش هستند؛ داده‌های شخصی در این API وجود ندارد.
- محدودیت نرخ: ۶۰ درخواست در دقیقه برای هر IP؛ در صورت عبور `429` با هدر `Retry-After`.
- تاریخ‌ها: `datetime` میلادی ISO-8601 + معادل شمسی در فیلد `*_jalali` برای نمایش.

```json
{
  "data": [ ... ],
  "meta": { "page": 1, "per_page": 20, "total": 115, "pages": 6, "generated_at": "2026-11-02T12:00:00+03:30" }
}
```

## ۱. `GET /vehicles`

پارامترها:

| نام | نوع | توضیح |
|---|---|---|
| page, per_page | int | صفحه‌بندی |
| search | string | جستجو در نام فارسی/لاتین (نرمال‌سازی ی/ک/نیم‌فاصله) |
| brand | string | اسلاگ یا شناسه برند (چند مقدار با کاما) |
| body_type | string | اسلاگ نوع بدنه |
| class | string | اسلاگ کلاس |
| year_from, year_to | int | سال تولید |
| price_min, price_max | int | بازه قیمت بازار (تومان) |
| availability | `available` \| `stopped` | وضعیت تولید |
| sort | `price_asc` `price_desc` `change_desc` `change_asc` `newest` `name` `popular` | مرتب‌سازی |
| with_prices | 0\|1 | افزودن بلوک قیمت به هر آیتم |

نمونه یک آیتم:

```json
{
  "id": 123,
  "title": "سورن XU7P برقی",
  "slug": "soren-xu7p-electric",
  "url": "https://lamerdkhodro.ir/vehicles/soren-xu7p-electric/",
  "brand": { "id": 5, "name": "ایران خودرو", "slug": "iran-khodro" },
  "year": 1403,
  "production_stopped": false,
  "prices": {
    "market_price": 2500000000,
    "factory_price": 1725606700,
    "change_amount": -20000000,
    "change_percent": -0.79,
    "fetched_at": "2026-11-02T12:40:00+03:30",
    "fetched_at_jalali": "۱۱ آبان ۱۴۰۵ - ۱۲:۴۰",
    "declared_at": null,
    "data_status": "valid",
    "is_stale": false,
    "source": "cafekhodro"
  }
}
```

## ۲. `GET /vehicles/{id}`

شناسه می‌تواند ID عددی یا اسلاگ باشد. خروجی شامل `specs` (گروه‌بندی‌شده)، `gallery`، `prices`، `similar` (تا ۴ خودروی مشابه) و `meta` است.

```json
{
  "id": 123,
  "title": "سورن XU7P برقی",
  "specs": {
    "engine": [ { "key": "displacement", "label": "حجم موتور", "value": 1600, "display": "۱۶۰۰ سی‌سی" } ],
    "transmission": [], "performance": [], "dimensions": [], "features": []
  }
}
```

## ۳. `GET /vehicles/{id}/history`

| پارامتر | مقدار |
|---|---|
| range | `7` `30` `90` `180` `365` `all` (پیش‌فرض ۳۰) |
| from, to | بازه دلخواه `YYYY-MM-DD` (بر بازه اولویت دارد) |

```json
{
  "vehicle_id": 123,
  "currency": "toman",
  "range": { "from": "2026-10-03", "to": "2026-11-02" },
  "labels": ["۳ مهر", "۴ مهر"],
  "series": {
    "market":  [2500000000, null],
    "factory": [1725606700, 1725606700]
  },
  "points": [ { "date": "2026-10-03", "jalali": "۳ مهر", "market": 2500000000, "factory": 1725606700 } ],
  "insufficient": true
}
```

`insufficient` وقتی `true` است که کمتر از ۲ نقطه داده معتبر وجود داشته باشد؛ رابط کاربری به‌جای نمودار خالی پیام راهنما نمایش می‌دهد.

## ۴. `GET /brands`

فهرست برندها با `id`, `name`, `name_en`, `slug`, `country`, `logo`, `count`, `url`.

## ۵. `GET /prices`

فهرست قیمت‌های جاری با همان فیلترهای `/vehicles` به‌علاوه `only_with_price=1` و `sort=updated`.

## ۶. `GET /compare?ids=1,2,3`

حداقل ۲ و حداکثر ۴ شناسه. خروجی:

```json
{
  "vehicles": [ { "id": 1, "title": "…", "url": "…" } ],
  "rows": [
    { "key": "market_price", "label": "قیمت بازار", "values": ["۲,۵۰۰,۰۰۰,۰۰۰", null],
      "best_index": 0, "differs": true, "type": "price" }
  ]
}
```

## ۷. `GET /news`

`page`, `per_page`, `category`, `search`. خروجی خلاصه نوشته‌ها با `id`, `title`, `excerpt`, `url`, `date`, `date_jalali`, `thumbnail`, `categories`.

## ۸. `GET /search?q=`

جستجوی ترکیبی در خودروها و نوشته‌ها. `types` قابل محدودسازی است (`vehicles,posts`).

## ۹. `GET /meta`

اطلاعات عمومی سایت، آخرین همگام‌سازی موفق و وضعیت تازگی داده (برای نمایش «قیمت‌ها مربوط به …» و مصرف آینده اپلیکیشن).

## کنترل دسترسی

- همه مسیرهای بالا عمومی و فقط خواندنی هستند.
- مسیرهای مدیریتی (همگام‌سازی، گزارش، خروجی) در REST عمومی نیستند و از پیشخوان با `nonce` و `capability` اجرا می‌شوند.
- افزودن امکانات کاربری در آینده با `application password` وردپرس یا OAuth و namespace جدید (`lamerd/v1/me/...`) انجام می‌شود؛ قرارداد موجود تغییر نمی‌کند.
