# 使用方法 / Usage Guide

このリポジトリの静的解析を実行するには、以下の手順に従ってください。

## 前提条件 / Prerequisites

- PHP 8.2以上
- Composer

## セットアップ手順 / Setup Steps

### 1. リポジトリのクローン
```bash
git clone https://github.com/h-morozumi/php-static-analysis-sample.git
cd php-static-analysis-sample
```

### 2. 依存関係のインストール
```bash
composer install
```

## 静的解析の実行 / Running Static Analysis

### Psalmの実行
```bash
# すべてのファイルを解析
./vendor/bin/psalm

# または composer スクリプトで実行
composer run psalm
```

### PHPStanの実行
```bash
# すべてのファイルを解析
./vendor/bin/phpstan analyse

# または composer スクリプトで実行
composer run phpstan
```

### すべてのツールを実行
```bash
composer run analyse
```

### Psalm Taint Analysisの実行（セキュリティ検査）
```bash
# Taint Analysisでセキュリティ脆弱性を検出
./vendor/bin/psalm --taint-analysis

# または composer スクリプトで実行
composer run psalm-security

# 短縮コマンド
composer run security
```

**Psalm Taint Analysisとは？**
通常のPsalmとは異なり、Taint Analysisはユーザー入力（$_GET、$_POST、$_COOKIEなど）から危険な操作（SQL実行、HTML出力、ファイル操作など）までのデータフローを追跡します。これにより、SQLインジェクション、XSS、コマンドインジェクションなどのセキュリティ脆弱性を検出できます。

## レポート生成 / Generating Reports

各種フォーマットでレポートを生成し、`/reports` ディレクトリに出力できます。

```bash
# Psalmのレポートを全形式で生成（13ファイル）
composer run report:psalm

# Psalm Taint Analysisのレポートを全形式で生成（13ファイル）
composer run report:psalm-taint

# PHPStanのレポートを全形式で生成（10ファイル）
composer run report:phpstan

# すべてのレポートを一括生成（36ファイル）
composer run report:all
```

### 生成されるレポート形式

**Psalm / Psalm Taint Analysis:**
- JSON: `psalm.json`, `psalm-codeclimate.json`, `psalm-sonarqube.json`, `psalm-summary.json`
- XML: `psalm.xml`, `psalm-checkstyle.xml`, `psalm-junit.xml`
- SARIF: `psalm.sarif`（GitHub Code Scanning対応）
- テキスト: `psalm.txt`, `psalm.console`, `psalm.emacs`, `psalm.pylint`, `psalm-count.txt`

**PHPStan:**
- JSON: `phpstan.json`, `phpstan-pretty.json`, `phpstan-gitlab.json`
- XML: `phpstan-checkstyle.xml`, `phpstan-junit.xml`
- SARIF: `phpstan.sarif`（GitHub Code Scanning対応）
- テキスト: `phpstan-table.txt`, `phpstan-raw.txt`, `phpstan-github.txt`, `phpstan-teamcity.txt`

## 検出される脆弱性の例 / Expected Vulnerabilities

### Psalmで検出されるもの（通常モード）
- **UndefinedVariable**: 未定義変数の使用
- **NullReference**: Null参照の可能性
- **MixedReturnStatement**: 型が不明確な戻り値
- **UnusedVariable**: 使用されていない変数
- **UnusedMethod**: 使用されていないメソッド
- **UnreachableStatement**: 到達不可能なコード

### Psalm Taint Analysisで検出されるもの（セキュリティモード）
- **TaintedSql**: SQLインジェクション
- **TaintedHtml**: XSS（クロスサイトスクリプティング）
- **TaintedTextWithQuotes**: クォート付きテキストの危険な出力
- **TaintedShell**: コマンドインジェクション
- **TaintedFile**: ファイルパストラバーサル
- **TaintedInclude**: ファイルインクルージョン
- **TaintedUnserialize**: 安全でないデシリアライゼーション
- **TaintedEval**: 任意コード実行

💡 **重要**: このリポジトリには `EntryPoint.php` が含まれており、`$_GET`、`$_POST`、`$_COOKIE` からの入力を脆弱なメソッドに渡すことで、Taint Analysisが脆弱性を検出できるようになっています。

### PHPStanで検出されるもの (レベル9)
- 型の不一致
- Null安全性の問題
- 配列キーの存在チェック漏れ
- 戻り値の型の不整合
- 未定義変数の使用
- 使用されていないコード

### 両方のツールで検出されにくい（基本モードの限界）
- SQLインジェクション（動的な文字列連結）
- XSS（エスケープ処理の不足）
- コマンドインジェクション
- ファイルパストラバーサル
- 弱い暗号化アルゴリズム

**注意**: これらのセキュリティ脆弱性を検出するには、**Psalm Taint Analysis**（`composer run psalm-security`）を使用してください。

## GitHub Actions / CI

このリポジトリにはGitHub Actionsワークフローが含まれています。

### ワークフロー一覧

| ワークフロー | 説明 | トリガー |
|-------------|------|----------|
| `all-checks.yml` | Psalm + PHPStan + Taint Analysis | push / PR / 手動 |
| `psalm.yml` | Psalm単体 | 手動のみ |
| `phpstan.yml` | PHPStan単体 | 手動のみ |
| `psalm-taint-analysis.yml` | Psalm Taint Analysis単体 | 手動のみ |

### SARIF連携

Psalm Taint AnalysisとPHPStanの結果は、SARIF形式でGitHub Code Scanningにアップロードされます。結果はGitHubリポジトリの **Security** タブで確認できます。

## 解析結果の確認 / Checking Results

解析を実行すると、以下のような出力が表示されます：

```
ERROR: UndefinedVariable - src/TypeSafetyVulnerability.php:18:13
    Cannot find referenced variable $result

ERROR: NullReference - src/TypeSafetyVulnerability.php:28:16
    Cannot call method getName on possibly null value
```

各エラーには以下の情報が含まれます：
- エラータイプ
- ファイルパスと行番号
- エラーの詳細説明

## トラブルシューティング / Troubleshooting

### composer installが失敗する場合
```bash
# キャッシュをクリア
composer clear-cache

# 再試行
composer install
```

### メモリ不足エラーが出る場合
```bash
# メモリ制限を増やす
php -d memory_limit=-1 vendor/bin/psalm
php -d memory_limit=-1 vendor/bin/phpstan analyse
```
