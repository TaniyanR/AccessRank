# AccessRank（アクセスランク）

AccessRank は **PHP 8.2+ / SQLite** で動作する軽量な「逆アクセス（IN）ランキング」＋「アクセスカウンター」ツールです。
外部流入（リファラ）を集計してランキング表示し、あわせて **今日／昨日／総PV** と **直近2週間の棒グラフ** をサイトに埋め込めます。
ウィジェットの幅は **250〜500px** で変更可能です。

---

## 主な機能

* 逆アクセス（IN）ランキング（外部リファラ集計）
* アクセスカウンター（今日／昨日／総PV）
* 直近14日棒グラフ付きカウンターウィジェット
* 幅指定 `w=250〜500`（範囲外は自動補正）
* 速度対策：カウントと表示を分離でき、表示側は短期キャッシュ可能
* セキュリティ：入力検証、プリペアドステートメント、安全な出力エスケープ

---

## 動作要件

* PHP: **8.2 以上（推奨 8.3）**
* SQLite: PDO SQLite が利用できること
* Webサーバー: Apache / nginx など

---

## ディレクトリ構成（想定）

```
accessrank/
  access.php                # 逆アクセス（IN）計測
  rank.html                 # ランキング出力（生成/更新される）
  counter_ping.php          # カウント専用（軽い）
  counter_widget.php        # 表示専用（棒グラフ+今日/昨日/総合）
  data/                     # SQLite DB / キャッシュ等（書込み必要）
  admin/                    # 管理（ある場合）
```

---

## インストール

1. `accessrank/` をサイトに配置
   例：`https://example.com/accessrank/`
2. `accessrank/data/` に書き込み権限を付与（SQLite DB/キャッシュ用）
3. 設置したいページにスニペットを貼り付け

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

---

## パラメータ

### `counter_widget.php`

* `w`：幅（250〜500）
* `id`：描画先のDOM id（英数/`_`/`-`推奨）
* `count`：`0`=表示のみ / `1`=表示時も加算（非推奨）

---

## パフォーマンスの考え方

遅くなる原因は「ファイル数」よりも、**毎回集計を走らせること**や **SQLiteのロック待ち**です。
推奨構成（カウント分離 + 表示短期キャッシュ）で改善します。

SQLiteの標準最適化（推奨）：

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

## よくあるトラブル

### カウントが増えない

* `counter_ping.php` が読み込まれているか（開発者ツール→Network）
* 広告ブロッカー等で画像がブロックされていないか

### 表示が出ない/崩れる

* `id` が存在するか
* `w` が数値か（自動補正されます）

---

## Roadmap

* URL正規化（http/https/www/クエリ）強化
* “直打ち/ブックマーク” の扱いを設定化（referrer空の扱い）
* 管理機能（リセット、除外、期間切替、表示件数）
* さらなる負荷対策（キャッシュ改善、集計クエリ最適化）


---
TBD（例：MIT / GPLv2 など、GitHub公開方針に合わせて決めてください）
