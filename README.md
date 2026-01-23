# PHP Static Analysis Sample

PHPの静的解析ツール（Psalm／PHPStan）の検証を行うリポジトリです。

このリポジトリには、意図的に脆弱性を含むPHPコードが含まれています。これらの脆弱性は、PsalmやPHPStanなどの静的解析ツールで検出できます。

**⚠️ 警告: このコードは教育目的のみです。本番環境では絶対に使用しないでください。**

## セットアップ

```bash
# 依存関係のインストール
composer install

# Psalmで静的解析を実行
composer run psalm

# PHPStanで静的解析を実行
composer run phpstan

# Psalm Taint Analysis でセキュリティ検査を実行
composer run psalm-security
# または
composer run security

# すべての解析を実行
composer run analyse
```

## レポート生成

各種フォーマットでレポートを生成できます。生成されたレポートは `/reports` ディレクトリに出力されます。

```bash
# Psalmのレポートを全形式で生成
composer run report:psalm

# Psalm Taint Analysisのレポートを全形式で生成
composer run report:psalm-taint

# PHPStanのレポートを全形式で生成
composer run report:phpstan

# すべてのレポートを一括生成
composer run report:all
```

### 生成されるレポート形式

| ツール | 形式 |
|--------|------|
| Psalm | json, xml, sarif, txt, console, emacs, pylint, checkstyle.xml, junit.xml, codeclimate.json, sonarqube.json, summary.json, count.txt |
| Psalm Taint | 同上 |
| PHPStan | json, sarif, pretty.json, checkstyle.xml, junit.xml, gitlab.json, github.txt, teamcity.txt, table.txt, raw.txt |

## 含まれる脆弱性の例

### 1. SQLインジェクション (`DatabaseVulnerability.php`)
- プリペアドステートメントを使わずに、直接SQL文に値を埋め込んでいる
- 文字列連結でSQL文を構築している
- 型が不明確なパラメータの使用

### 2. XSS (クロスサイトスクリプティング) (`XssVulnerability.php`)
- ユーザー入力をエスケープせずに出力
- HTMLタグがそのまま出力される
- JavaScript実行の可能性
- 属性値のエスケープがない

### 3. 型安全性の問題 (`TypeSafetyVulnerability.php`)
- 未定義変数の使用
- Nullポインタ参照の可能性
- 戻り値の型の不整合
- 配列キーの存在チェックなし
- Nullableな値のチェック不足

### 4. ファイル操作の脆弱性 (`FileVulnerability.php`)
- パストラバーサル攻撃の可能性
- 任意のファイルへの書き込み
- ファイルインクルージョンの脆弱性
- アップロードファイルの検証不足

### 5. セキュリティの脆弱性 (`SecurityVulnerability.php`)
- 弱い乱数生成（`rand()`の使用）
- 弱いハッシュアルゴリズム（MD5）
- `eval()`の使用
- コマンドインジェクション
- 安全でないデシリアライゼーション
- セッション固定化の可能性

### 6. デッドコードと到達不可能コード (`DeadCodeVulnerability.php`)
- 到達不可能なコード
- 使用されないプライベートメソッド
- 使用されない変数
- 常に真になる条件
- 不適切なループ構造

### 7. Taint Analysis検証用エントリーポイント (`EntryPoint.php`)
- `$_GET`、`$_POST`、`$_COOKIE` からの入力を脆弱なメソッドに渡す
- Psalm Taint Analysisでセキュリティ脆弱性を検出するためのデモコード

## ソースファイル一覧

| ファイル | 説明 | 検出対象 |
|---------|------|----------|
| `DatabaseVulnerability.php` | SQLインジェクション | Taint Analysis |
| `XssVulnerability.php` | XSS（クロスサイトスクリプティング） | Taint Analysis |
| `TypeSafetyVulnerability.php` | 型安全性の問題 | Psalm / PHPStan |
| `FileVulnerability.php` | ファイル操作の脆弱性 | Taint Analysis |
| `SecurityVulnerability.php` | セキュリティ全般の脆弱性 | Taint Analysis |
| `DeadCodeVulnerability.php` | デッドコード・到達不可能コード | Psalm / PHPStan |
| `EntryPoint.php` | Taint Analysis用エントリーポイント | Taint Analysis |

## 静的解析ツールの設定

### Psalm
- 設定ファイル: `psalm.xml`
- エラーレベル: 3
- 未使用コードの検出: 有効
- **Taint Analysis（セキュリティ検査）**: `composer run psalm-security` または `./vendor/bin/psalm --taint-analysis` で実行
  - SQLインジェクション検出
  - XSS（クロスサイトスクリプティング）検出
  - コマンドインジェクション検出
  - パストラバーサル検出
  - その他のユーザー入力に起因する脆弱性の検出

### PHPStan
- 設定ファイル: `phpstan.neon`
- ルールレベル: 9（最も厳格）
- SARIF出力: `jbelien/phpstan-sarif-formatter` を使用

## GitHub Actions / CI

このリポジトリにはGitHub Actionsワークフローが含まれており、プッシュ時に自動的に静的解析を実行します。

### ワークフロー

| ファイル | 説明 | トリガー |
|---------|------|----------|
| `all-checks.yml` | Psalm + PHPStan + Taint Analysis を一括実行 | push / PR |
| `psalm.yml` | Psalm単体実行 | 手動 |
| `phpstan.yml` | PHPStan単体実行 | 手動 |
| `psalm-taint-analysis.yml` | Psalm Taint Analysis単体実行 | 手動 |

### GitHub Security タブ連携

Psalm Taint AnalysisとPHPStanの結果はSARIF形式でGitHub Code Scanningにアップロードされ、**Security** タブで確認できます。

## 学習目的

このリポジトリは、以下を学ぶために使用できます：

1. 一般的なPHPの脆弱性パターンの理解
2. 静的解析ツールがどのように問題を検出するか
3. セキュアなコーディングプラクティスの重要性
4. 型安全性とコード品質の向上方法

## 詳細ドキュメント

- **[QUICKREF.md](QUICKREF.md)** - クイックリファレンス（最初にここから！）
- **[USAGE.md](USAGE.md)** - 使用方法の詳細ガイド
- **[ANALYSIS.md](ANALYSIS.md)** - 各脆弱性の詳細分析と検出可能性
- **[EXAMPLE_OUTPUT.md](EXAMPLE_OUTPUT.md)** - 静的解析ツールの実行例と出力サンプル

## ライセンス

MIT License
