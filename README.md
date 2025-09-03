# Steps Player (PHP on Render via Docker)

مشغّل متعدد الكاميرات (HLS/DASH/YouTube/Vimeo) بلغة PHP + Apache داخل Docker، جاهز للنشر على Render من خلال GitHub.

## ما في داخل الريبو
- `player.php` — نسخة PHP من المشغّل مع إمكانيات تهيئة عبر GET.
- `Dockerfile` — يبني حاوية php:8.3-apache.
- `render.yaml` — خيار Blueprint للنشر التلقائي.
- `.gitignore` — ملفات مستثناة شائعة.

## تشغيل محلي (اختياري)
```bash
docker build -t steps-player .
docker run -p 8080:80 steps-player
# افتح: http://localhost:8080/player.php
```

## نشر على Render (يدويًا عبر Web Service)
1) ارفع الملفات إلى GitHub.
2) من لوحة Render: New -> Web Service -> اختر المستودع.
3) Render سيستخدم Dockerfile تلقائياً.
4) بعد إنشاء الخدمة، افتح: `https://<service>.onrender.com/player.php`.

## نشر عبر Blueprint
1) ارفع `render.yaml` مع باقي الملفات.
2) في Render: New -> Blueprint.
3) فعّل Auto Deploy.

## تهيئة الروابط أثناء التشغيل
- `/player.php?title=My+Title`
- `/player.php?main=/hls/live/playlist.m3u8&cam1=/hls/cam1.m3u8`
- `/player.php?sources={"main":"/hls/live.m3u8","cam1":"/hls/c1.m3u8"}`

> لو غيّرت اسم الملف إلى `index.php`، سيفتح على المسار `/` مباشرة.
