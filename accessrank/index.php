<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AccessRank 動作確認ページ</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f7f7f9;color:#222;margin:0;padding:24px;}
main{max-width:720px;margin:0 auto;background:#fff;border-radius:12px;padding:20px;box-shadow:0 8px 24px rgba(0,0,0,0.08);}
h1{font-size:20px;margin:0 0 12px;}
ul{padding-left:20px;margin:12px 0;}
li{margin:6px 0;}
.code{background:#f2f3f5;border-radius:8px;padding:12px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;font-size:13px;}
</style>
</head>
<body>
<main>
  <h1>AccessRank 動作確認ページ</h1>
  <p>以下のリンクから各ページへ移動できます。</p>
  <ul>
    <li><a href="/accessrank/admin/">/accessrank/admin/ （管理画面）</a></li>
    <li><a href="/accessrank/rank.html">/accessrank/rank.html （ランキング）</a></li>
  </ul>

  <p>表示サンプル:</p>
  <div class="code">
    <div id="ar-counter"></div>
    <script async src="/accessrank/counter_widget.php?id=ar-counter&w=320&count=0"></script>
    <img src="/accessrank/counter_ping.php" width="1" height="1" alt="" style="display:none">
  </div>
</main>
</body>
</html>
