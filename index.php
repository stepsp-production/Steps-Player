<?php
/**
 * Steps HLS Player (PHP)
 * ----------------------
 * Defaults to using your Render reverse proxy path:
 *   /hls/live3/playlist.m3u8  -> https://hls-proxy-iphq.onrender.com/hls/live3/playlist.m3u8
 *
 * Query overrides:
 *   ?title=My+Title
 *   ?main=/hls/live3/playlist.m3u8
 *   ?cam1=/hls/live/playlist.m3u8   (etc.)
 *   ?sources={"main":"...","cam1":"..."}  // JSON; individual params take priority
 *
 * Tip: To force direct origin for testing:
 *   ?main=https://46.152.153.249/hls/live3/playlist.m3u8
 */

declare(strict_types=1);

header_remove('X-Powered-By');
header('Content-Type: text/html; charset=UTF-8');

function clean_url(?string $s): ?string {
  if ($s === null) return null;
  $s = trim($s);
  if ($s === '') return null;
  // allow absolute http/https, root-relative, or simple filenames with optional query
  if (!preg_match('~^(https?://|/)[^\s]+$~i', $s)) return null;
  return $s;
}

$title = isset($_GET['title']) && $_GET['title'] !== '' ? $_GET['title'] : 'Steps HLS Player';

// Defaults go through the reverse proxy (relative paths)
$defaultSources = [
  'main' => '/hls/live/playlist.m3u8',
  'cam1' => '/hls/livelastone/playlist.m3u8',
  'cam2' => '/hls/live2/playlist.m3u8',
  'cam3' => '/hls/live3/playlist.m3u8',
  'cam4' => '/hls/live4/playlist.m3u8',
  'cam5' => '/hls/live5/playlist.m3u8',
  'cam6' => '/hls/live6/playlist.m3u8',
  'cam7' => '/hls/live7/playlist.m3u8',
];

$sources = $defaultSources;

// Optional JSON bulk
if (isset($_GET['sources'])) {
  $json = json_decode((string)$_GET['sources'], true);
  if (is_array($json)) {
    foreach ($json as $k => $v) {
      if (isset($sources[$k])) {
        $u = clean_url((string)$v);
        if ($u !== null) $sources[$k] = $u;
      }
    }
  }
}

// Individual overrides win
foreach ($defaultSources as $k => $_) {
  if (isset($_GET[$k])) {
    $u = clean_url((string)$_GET[$k]);
    if ($u !== null) $sources[$k] = $u;
  }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>

  <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
  <style>
    :root { --bg:#000; --panel:#0b0b0fcc; --bd:#ffffff26; --fg:#fff; --btn:#e53935; --btnh:#d32f2f; --ring:rgba(211,47,47,.28); }
    *{box-sizing:border-box} html,body{height:100%} body{margin:0;background:var(--bg);font-family:system-ui,Segoe UI,Roboto,Arial;color:var(--fg)}
    .wrap{display:flex;flex-direction:column;min-height:100vh}
    header{padding:12px 16px;border-bottom:1px solid var(--bd);background:#07070acc}
    h1{margin:0;font-size:16px}
    main{flex:1;display:flex;flex-direction:column;gap:10px;padding:12px}
    .player{position:relative;max-width:1200px;margin-inline:auto;aspect-ratio:16/9;background:#000;border:1px solid var(--bd);border-radius:12px;overflow:hidden}
    video{width:100%;height:100%;display:block;background:#000;object-fit:contain}
    .controls{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin:6px auto 0;max-width:1200px}
    button{appearance:none;border:0;background:var(--btn);color:#fff;font-weight:700;padding:10px 12px;border-radius:10px;cursor:pointer;box-shadow:0 8px 18px #000a,0 0 0 0 var(--ring)}
    button:hover{background:var(--btnh);box-shadow:0 12px 26px #000c,0 0 0 6px var(--ring)}
    .msg{position:absolute;left:50%;top:12px;transform:translateX(-50%);background:var(--panel);border:1px solid var(--bd);padding:8px 10px;border-radius:10px;font-size:12px}
    .err{color:#ffbdbd}
    .small{font-size:12px;opacity:.8;text-align:center;margin-top:4px}
  </style>
</head>
<body>
<div class="wrap">
  <header><h1><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1></header>

  <main>
    <div class="player" id="playerBox">
      <div class="msg" id="status">جاهز… اضغط تشغيل</div>
      <video id="video" playsinline muted controls></video>
    </div>

    <div class="controls" id="cams">
      <button data-src="<?= htmlspecialchars($sources['main']) ?>">الرئيسي</button>
      <?php foreach (['cam1'=>'Cam1','cam2'=>'Cam2','cam3'=>'Cam3','cam4'=>'Cam4','cam5'=>'Cam5','cam6'=>'Sineflex','cam7'=>'Drone'] as $k=>$label): ?>
        <?php if (!empty($sources[$k])): ?>
          <button data-src="<?= htmlspecialchars($sources[$k]) ?>"><?= $label ?></button>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <div class="small">
      الافتراضي يستخدم مسارًا نسبيًا عبر البروكسي لديك (<code>/hls/…</code>).<br>
      للاختبار المباشر على الأصل يمكنك تمرير <code>?main=<?= htmlspecialchars('https://46.152.153.249/hls/live3/playlist.m3u8') ?></code>.
    </div>
  </main>
</div>

<script>
  const video = document.getElementById('video');
  const statusEl = document.getElementById('status');
  const btns = document.querySelectorAll('#cams button');

  // PHP-supplied sources (kept for debugging if you want it)
  window.SOURCES = <?= json_encode($sources, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>;

  const HLS_CONFIG = {
    lowLatencyMode: false,
    liveSyncDuration: 4,
    liveMaxLatencyDuration: 20,
    maxBufferLength: 18,
    backBufferLength: 30,
    maxFragLookUpTolerance: 0.25,
    maxBufferHole: 0.5,
    enableWorker: true,
    startFragPrefetch: false,
    manifestLoadingMaxRetry: 6, manifestLoadingRetryDelay: 800,
    levelLoadingMaxRetry: 5, levelLoadingRetryDelay: 800,
    fragLoadingMaxRetry: 6, fragLoadingRetryDelay: 500,
    xhrSetup: (xhr) => { try { xhr.withCredentials = false; } catch (e) {} }
  };

  let hls = null;

  function setStatus(msg, isErr=false){
    statusEl.textContent = msg;
    statusEl.classList.toggle('err', !!isErr);
  }

  function destroy(){
    try { if (hls) { hls.destroy(); hls = null; } } catch(e){}
    try { video.pause(); video.removeAttribute('src'); video.load(); } catch(e){}
  }

  function playHls(url){
    destroy();
    setStatus('جارٍ التحميل…');

    const isHls = /\.m3u8(\?|$)/i.test(url);
    if (!isHls) {
      video.src = url;
      video.play().catch(()=>{});
      setStatus('تشغيل مباشر (غير HLS)');
      return;
    }

    if (video.canPlayType('application/vnd.apple.mpegURL')) {
      video.src = url;
      video.play().then(()=>setStatus('تشغيل HLS (native)')).catch(()=>setStatus('جاهز — انقر تشغيل', false));
      return;
    }

    if (window.Hls && Hls.isSupported()) {
      hls = new Hls(HLS_CONFIG);
      hls.attachMedia(video);
      hls.on(Hls.Events.MEDIA_ATTACHED, () => {
        hls.loadSource(url);
      });

      hls.on(Hls.Events.MANIFEST_PARSED, () => {
        setStatus('تم التحميل — تشغيل');
        video.play().catch(()=>setStatus('جاهز — انقر تشغيل'));
      });

      hls.on(Hls.Events.ERROR, (_, data) => {
        const t = data?.type || '';
        const d = data?.details || '';
        // Show clean info if we hit a redirect loop or blocked fetch
        if (/network/i.test(t)) {
          setStatus('خطأ شبكة — تأكد من أن الرابط صحيح وأن البروكسي لا يعيد التوجيه.', true);
        }
        if (data?.fatal) {
          try { hls.destroy(); } catch(e){}
          setStatus('خطأ جسيم — إعادة المحاولة…', true);
          setTimeout(()=>playHls(url), 900);
        }
      });

      return;
    }

    // Fallback
    video.src = url;
    video.play().catch(()=>{});
    setStatus('تشغيل (fallback)');
  }

  // Hook up buttons
  btns.forEach(b=>{
    b.addEventListener('click', ()=>{
      btns.forEach(x=>x.disabled=false);
      b.disabled = true;
      const url = b.getAttribute('data-src');
      playHls(url);
    });
  });

  // Autoplay main on load
  document.addEventListener('DOMContentLoaded', ()=>{
    const mainBtn = document.querySelector('#cams button[data-src]');
    if (mainBtn) { mainBtn.click(); }
  });
</script>
</body>
</html>
