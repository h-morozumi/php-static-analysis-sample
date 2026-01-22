# クイックリファレンス / Quick Reference

## セットアップ (1分)

```bash
composer install
```

## 基本コマンド

```bash
# Psalmを実行
composer run psalm

# PHPStanを実行
composer run phpstan

# 両方を実行
composer run analyse
```

## 各ファイルの概要

| ファイル | 脆弱性の種類 | Psalm | PHPStan |
|---------|-------------|-------|---------|
| DatabaseVulnerability.php | SQLインジェクション | ❌ | ❌ |
| XssVulnerability.php | XSS | ❌ | ❌ |
| TypeSafetyVulnerability.php | 型安全性 | ✅ | ✅ |
| FileVulnerability.php | ファイル操作 | ⚠️ | ⚠️ |
| SecurityVulnerability.php | セキュリティ全般 | ⚠️ | ⚠️ |
| DeadCodeVulnerability.php | デッドコード | ✅ | ✅ |

✅ = 検出可能 / ⚠️ = 部分的に検出可能 / ❌ = 検出困難

## よく検出されるエラー

### Psalm
- `UndefinedVariable` - 未定義変数
- `PossiblyNullReference` - Null参照の可能性
- `MixedReturnStatement` - 型不明の戻り値
- `UnusedMethod` - 未使用メソッド
- `UnreachableStatement` - 到達不可能コード

### PHPStan (レベル9)
- "Undefined variable" - 未定義変数
- "Cannot call method on mixed" - 型不明での メソッド呼び出し
- "has no return type" - 戻り値の型指定なし
- "Unreachable statement" - 到達不可能コード

## 重要な学び

1. **型安全性が重要**: 静的解析ツールは型の問題を見つけるのが得意
2. **セキュリティは別物**: SQLインジェクションやXSSには専門ツールが必要
3. **早期発見**: コーディング段階で問題を見つけることが重要
4. **複数ツール**: 異なるツールを組み合わせることで、より多くの問題を発見できる

## 次のステップ

1. 各ファイルのコードを読む
2. 静的解析を実行して出力を確認
3. ANALYSIS.mdで詳細を学ぶ
4. 自分のプロジェクトに静的解析を導入

## 詳細ドキュメント

- [USAGE.md](USAGE.md) - 詳しい使い方
- [ANALYSIS.md](ANALYSIS.md) - 脆弱性の詳細分析
- [EXAMPLE_OUTPUT.md](EXAMPLE_OUTPUT.md) - 実行例
