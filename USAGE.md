# 使用方法 / Usage Guide

このリポジトリの静的解析を実行するには、以下の手順に従ってください。

## 前提条件 / Prerequisites

- PHP 7.4以上
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

### 両方のツールを実行
```bash
composer run analyse
```

## 検出される脆弱性の例 / Expected Vulnerabilities

### Psalmで検出されるもの
- **UndefinedVariable**: 未定義変数の使用
- **NullReference**: Null参照の可能性
- **MixedReturnStatement**: 型が不明確な戻り値
- **UnusedVariable**: 使用されていない変数
- **UnusedMethod**: 使用されていないメソッド
- **UnreachableStatement**: 到達不可能なコード

### PHPStanで検出されるもの (レベル9)
- 型の不一致
- Null安全性の問題
- 配列キーの存在チェック漏れ
- 戻り値の型の不整合
- 未定義変数の使用
- 使用されていないコード

### 両方のツールで検出されにくい（一般的な静的解析の限界）
- SQLインジェクション（動的な文字列連結）
- XSS（エスケープ処理の不足）
- コマンドインジェクション
- ファイルパストラバーサル
- 弱い暗号化アルゴリズム

**注意**: SQLインジェクションやXSSなどのセキュリティ脆弱性を検出するには、より専門的なセキュリティ解析ツール（例: Psalm Security Plugin, PHPStan Security Rules）が必要です。

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
