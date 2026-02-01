# AccessRank
AccessRank は PHP 8.2+ で動作する軽量な「逆アクセス（IN）ランキング」＋「アクセスカウンター」ツールです。
外部流入（リファラ）を集計してランキング表示でき、あわせて 今日／昨日／総PV と 直近2週間の棒グラフ をサイトに埋め込めます。
カウンターの表示幅は 250px〜500px で変更可能です。

機能

逆アクセス（IN）ランキング（リファラベース）

アクセスカウンター（今日／昨日／総PV）

直近2週間の棒グラフ付きカウンターウィジェット

幅指定 w=250〜500（範囲外は自動補正）

セキュリティ重視（入力検証・プリペアド・安全なHTML出力）

速度対策（カウントと表示を分離でき、表示は短期キャッシュ可能）

動作要件

PHP: 8.2 以上（推奨 8.3）

DB: SQLite（標準構成）
※今後 MySQL 対応を追加する場合は Issues で管理

インストール

accessrank/ フォルダをサーバーに配置
例：https://example.com/accessrank/

書き込み権限（SQLite DB / キャッシュを作る場合）をサーバー側で許可

共有サーバーの場合：accessrank/ 配下の書き込みが必要になる構成があります

設置したいページにスニペットを貼り付け

使い方（推奨構成：高速・二重カウント防止）

AccessRank は 「カウント」 と 「表示」 を分けて運用できます。
この方法が 速く、ページ表示を ブロックしにくく、二重カウントも防げます。

1) カウント（非表示の 1x1 ピクセル推奨）
<img src="/accessrank/counter_ping.php" width="1" height="1" alt="" style="display:none">

2) 表示（ウィジェット：表示のみ / 幅指定）
<div id="ar-counter"></div>
<script async src="/accessrank/counter_widget.php?id=ar-counter&w=320&count=0"></script>


w：250〜500（範囲外は自動で 250/500 に補正）

count=0：表示のみ（DBへの加算を行いません）

async：ページ描画を邪魔しにくくします

逆アクセス（IN）計測の設置
逆アクセス（IN）送信（リファラを送る）
<script>
document.write('<script src="/accessrank/access.php?referrer=' + encodeURIComponent(document.referrer) + '"><\/script>');
</script>

ランキングの表示（例：iframe）
<iframe src="/accessrank/rank.html" style="width:100%;height:600px;border:0;"></iframe>

カウンターの貼り方（別パターン）
ウィジェットを直接表示（document.write）
<script src="/accessrank/counter_widget.php?w=360"></script>

注意：二重カウントについて

counter_ping.php でカウントし、counter_widget.php?count=0 は表示だけ
→ 二重カウントしません（推奨）

counter_widget.php を count=1（または省略）で使うと表示側でも加算されます
→ ping と併用しないでください

パラメータ一覧（ウィジェット）

/accessrank/counter_widget.php

w：幅（250〜500）

id：描画先の DOM id（英数・_・- のみ推奨）

count：0=表示のみ / 1=表示時に加算（非推奨。二重カウント注意）

セキュリティ方針（実装ルール）

AccessRank は以下を前提に実装・運用します。

入力は必ずバリデーション（幅・ID・URL長など上限あり）

SQL は常にプリペアドステートメント

HTML 出力は必ずエスケープ（XSS対策）

例外時は内部ログのみ・画面に詳細を出さない（情報漏えい対策）

レート制限（IP+UA などの短時間連打抑制）を導入（DoS/水増し対策）

パフォーマンスの考え方（遅いと感じたら）

遅さの原因は「ファイル量」よりも DB書き込み/集計/ロック待ちがほとんどです。

推奨：

カウントは counter_ping.php に集約

表示は counter_widget.php?count=0（表示のみ）

表示側は短期キャッシュ（例：30秒）を許容

よくあるトラブル
1) カウントが増えない

counter_ping.php が読み込まれているか（開発者ツールの Network）

画像ブロック/広告ブロッカーが影響していないか

2) 表示が崩れる / 出ない

id が正しいか（DOMに存在するか）

w が数値か（250〜500に補正されます）

3) 反映が遅い

同時アクセスが多い場合、SQLite のロック待ちが起きることがあります
→ 設定（WAL / busy_timeout）や、必要に応じて MySQL 移行を検討

ライセンス

TBD（例：MIT / GPLv2 など、GitHub公開方針に合わせて決めてください）
