# クイックリファレンス / Quick Reference

## セットアップ (1分)

```bash
composer install
```

## 基本コマンド

```bash
# Psalmを実行（型チェック）
composer run psalm

# PHPStanを実行
composer run phpstan

# Psalm Taint Analysisを実行（セキュリティ検査）
composer run psalm-security
# または
composer run security

# 両方を実行（PsalmとPHPStan）
composer run analyse
```

## 各ファイルの概要

| ファイル | 脆弱性の種類 | Psalm | PHPStan | Taint Analysis |
|---------|-------------|-------|---------|----------------|
| DatabaseVulnerability.php | SQLインジェクション | ❌ | ❌ | ✅ |
| XssVulnerability.php | XSS | ❌ | ❌ | ✅ |
| TypeSafetyVulnerability.php | 型安全性 | ✅ | ✅ | - |
| FileVulnerability.php | ファイル操作 | ⚠️ | ⚠️ | ✅ |
| SecurityVulnerability.php | セキュリティ全般 | ⚠️ | ⚠️ | ✅ |
| DeadCodeVulnerability.php | デッドコード | ✅ | ✅ | - |

✅ = 検出可能 / ⚠️ = 部分的に検出可能 / ❌ = 検出困難

## よく検出されるエラー

### Psalm（通常モード）
- `UndefinedVariable` - 未定義変数
- `PossiblyNullReference` - Null参照の可能性
- `MixedReturnStatement` - 型不明の戻り値
- `UnusedMethod` - 未使用メソッド
- `UnreachableStatement` - 到達不可能コード

### Psalm Taint Analysis（セキュリティモード）
- `TaintedSql` - SQLインジェクション
- `TaintedHtml` - XSS（クロスサイトスクリプティング）
- `TaintedShell` - コマンドインジェクション
- `TaintedFile` - ファイルパストラバーサル
- `TaintedInclude` - ファイルインクルージョン
- `TaintedUnserialize` - 安全でないデシリアライゼーション

### PHPStan (レベル9)
- "Undefined variable" - 未定義変数
- "Cannot call method on mixed" - 型不明での メソッド呼び出し
- "has no return type" - 戻り値の型指定なし
- "Unreachable statement" - 到達不可能コード

## 重要な学び

1. **型安全性が重要**: 静的解析ツールは型の問題を見つけるのが得意
2. **セキュリティには専用ツール**: SQLインジェクションやXSSには **Psalm Taint Analysis** が必要
3. **Taint Analysisは必須**: セキュリティ脆弱性の検出に有効
4. **早期発見**: コーディング段階で問題を見つけることが重要
5. **複数ツール**: 異なるツールを組み合わせることで、より多くの問題を発見できる

## 次のステップ

1. 各ファイルのコードを読む
2. 静的解析を実行して出力を確認
3. ANALYSIS.mdで詳細を学ぶ
4. 自分のプロジェクトに静的解析を導入

## 詳細ドキュメント

- [USAGE.md](USAGE.md) - 詳しい使い方
- [ANALYSIS.md](ANALYSIS.md) - 脆弱性の詳細分析
- [EXAMPLE_OUTPUT.md](EXAMPLE_OUTPUT.md) - 実行例
