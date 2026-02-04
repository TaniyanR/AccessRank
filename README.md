# AccessRank（アクセスランク）

AccessRank は **PHP 8.2+ / SQLite** で動作する軽量な「逆アクセス（IN）ランキング」＋「アクセスカウンター」ツールです。
外部流入（リファラ）を集計してランキング表示し、あわせて **今日／昨日／総PV** と **直近2週間の棒グラフ** をサイトに埋め込めます。
ウィジェットの幅は **250〜500px** で変更可能です。

**GitHub Description（日本語）**: 逆アクセス（IN）ランキング＋アクセスカウンター（今日/昨日/総PV・14日棒グラフ）を提供する軽量PHPツール。

---

## 主な機能

* 逆アクセス（IN）ランキング（外部リファラ集計）
* アクセスカウンター（今日／昨日／総PV）
* 直近14日棒グラフ付きカウンターウィジェット
* 幅指定 `w=250〜500`（範囲外は自動補正）
* 速度対策：カウントと表示を分離でき、表示側は短期キャッシュ
* セキュリティ：入力検証、プリペアドステートメント、安全な出力エスケープ

---

## 動作要件

* PHP: **8.2 以上（推奨 8.3）**
* SQLite: PDO SQLite が利用できること
* Webサーバー: Apache / nginx など

---

## ディレクトリ構成

```
accessrank/
  access.php                # 逆アクセス（IN）計測
  rank.html                 # ランキング出力（自動生成/更新）
  counter_ping.php          # カウント専用（軽い）
  counter_widget.php        # 表示専用（棒グラフ+今日/昨日/総合）
  admin/                    # 管理画面（ログイン/パスワード変更）
  config.php                # 設定
  lib.php                   # 共通処理
  data/                     # SQLite DB / キャッシュ等（書込み必要）
```

---

## インストール

1. `accessrank/` をサイトに配置
   例：`https://example.com/accessrank/`
2. `accessrank/data/` に書き込み権限を付与（SQLite DB/キャッシュ用）
3. `accessrank/config.php` の内部ホスト設定を必要に応じて変更
4. 設置したいページにスニペットを貼り付け

---

## 使い方（推奨：高速・二重カウント防止）

AccessRank は **「カウント」** と **「表示」** を分けるのが最も安定して速いです。

### 1) カウント（非表示 1x1 ピクセル）

```html
<img src="/accessrank/counter_ping.php" width="1" height="1" alt="" style="display:none">
```

### 2) 表示（ウィジェット：表示のみ / 幅指定 250〜500）

```html
<div id="ar-counter"></div>
<script async src="/accessrank/counter_widget.php?id=ar-counter&w=320&count=0"></script>
```

* `w`：250〜500（範囲外は自動補正）
* `id`：描画先のDOM id（英数/`_`/`-`）
* `count=0`：表示のみ（ここではカウントしない）
* `async`：ページ描画を邪魔しにくくします

> 注意：`counter_widget.php` を `count=1`（または省略）で使うと**表示側でも加算**します。
> `counter_ping.php` と併用すると **二重カウント**になるため、推奨構成では `count=0` 固定です。

---

## 逆アクセス（IN）ランキングの設置

### 逆アクセス（IN）計測（リファラ送信）

```html
<script>
document.write('<script src="/accessrank/access.php?referrer=' + encodeURIComponent(document.referrer) + '"><\/script>');
</script>
```

### ランキング表示（例：iframe）

```html
<iframe src="/accessrank/rank.html" style="width:100%;height:600px;border:0;"></iframe>
```

`rank.html` はアクセス時に一定間隔（既定60秒）を超えていれば自動再生成されます。

---

## 管理画面（/accessrank/admin/）

* URL: `/accessrank/admin/`
* 初期ログイン: `admin` / `pass`
* 初回ログイン後はパスワード変更が完了するまで強制的に変更画面へ移動します。

---

## パラメータ

### `counter_widget.php`

* `w`：幅（250〜500）
* `id`：描画先のDOM id（英数/`_`/`-`）
* `count`：`0`=表示のみ / `1`=表示時も加算（非推奨、省略時は0）

---

## 設定（config.php）

* `AR_EXCLUDE_INTERNAL`: 自サイトのリファラを除外するか
* `AR_INTERNAL_HOSTS`: 自サイトのホスト名（例：`example.com`）
* `AR_WIDGET_CACHE_TTL`: ウィジェット表示のキャッシュ秒数（既定30秒）
* `AR_RANK_CACHE_TTL`: ランキングHTMLの再生成間隔（既定60秒）

---

## パフォーマンスの考え方

遅くなる原因は「ファイル数」よりも、**毎回集計を走らせること**や **SQLiteのロック待ち**です。
推奨構成（カウント分離 + 表示短期キャッシュ）で改善します。

SQLiteの最適化（接続直後に設定済み）：

* `journal_mode=WAL`
* `synchronous=NORMAL`
* `busy_timeout=3000`

---

## セキュリティ方針（実装ルール）

* 入力は必ず検証（幅、ID、URL長など上限あり）
* SQL は必ずプリペアドステートメント
* HTML出力は必ず `htmlspecialchars` でエスケープ
* 例外詳細は画面に出さず、内部ログのみ
* レート制限（IP+UAの短時間連打抑制）と簡易bot除外

---

## パーミッション（推奨）

* dir: 755（書込み必要なら775、最終手段777）
* file: 644（書込み必要なら664、最終手段666）
* accessrank/data/: 775（最終手段777）
* accessrank/data/accessrank.sqlite: 664（最終手段666）
* accessrank/rank.html（生成する場合）: 664（最終手段666）

## よくあるトラブル

### カウントが増えない

* `counter_ping.php` が読み込まれているか（開発者ツール→Network）
* 広告ブロッカー等で画像がブロックされていないか

### 表示が出ない/崩れる

* `id` が存在するか
* `w` が数値か（自動補正されます）

---

## 更新履歴

* 変更点は [CHANGELOG.md](CHANGELOG.md) を参照してください。

---

## 運用メモ（Issuesタイトル案）

1. ウィジェットのキャッシュTTL見直し
2. 逆アクセスの除外ホスト設定追加
3. 管理機能（リセット/除外/期間切替）
4. リファラ正規化の強化
