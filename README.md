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

# 両方の解析を実行
composer run analyse
```

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

## 静的解析ツールの設定

### Psalm
- 設定ファイル: `psalm.xml`
- エラーレベル: 3
- 未使用コードの検出: 有効

### PHPStan
- 設定ファイル: `phpstan.neon`
- ルールレベル: 9（最も厳格）

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
