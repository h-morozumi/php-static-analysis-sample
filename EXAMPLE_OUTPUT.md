# 静的解析の実行例 / Static Analysis Example Output

このファイルには、PsalmとPHPStanを実行したときに期待される出力の例を示します。

## Psalmの実行例

```bash
$ ./vendor/bin/psalm
```

### 期待される出力:

```
Scanning files...
Analyzing files...

ERROR: UndefinedVariable - src/TypeSafetyVulnerability.php:18:13
    Cannot find referenced variable $result
    
        if ($result > 0) {

ERROR: PossiblyNullReference - src/TypeSafetyVulnerability.php:28:16
    Cannot call method getName on possibly null value
    
        return $user->getName();

ERROR: MixedReturnStatement - src/TypeSafetyVulnerability.php:39:20
    Could not infer a return type
    
            return "string data";

ERROR: MixedReturnStatement - src/TypeSafetyVulnerability.php:41:16
    Could not infer a return type
    
        return 123;

ERROR: PossiblyUndefinedArrayOffset - src/TypeSafetyVulnerability.php:51:16
    Possibly undefined array offset
    
        return $config[$key];

ERROR: PossiblyNullArgument - src/TypeSafetyVulnerability.php:67:23
    Argument 1 of strlen cannot be null, possibly null value provided
    
        return strlen($data);

ERROR: UnusedMethod - src/DeadCodeVulnerability.php:28:21
    Method App\DeadCodeVulnerability::unusedMethod is never used
    
    private function unusedMethod()

ERROR: UnusedVariable - src/DeadCodeVulnerability.php:37:9
    $unused is never referenced in this method
    
        $unused = "This is never used";

ERROR: UnreachableStatement - src/DeadCodeVulnerability.php:20:9
    Statement is unreachable
    
        echo "This will never execute";

------------------------------
9 errors found
------------------------------

Checks took 0.50 seconds and used 50.000MB of memory
Psalm was able to infer types for 95% of the codebase
```

## PHPStanの実行例

```bash
$ ./vendor/bin/phpstan analyse
```

### 期待される出力:

```
Note: Using configuration file phpstan.neon.
 1/6 [▓░░░░░░░░░░░░░░░░░░░░░░░░░░░]  16%
 2/6 [▓▓▓▓▓▓▓▓▓░░░░░░░░░░░░░░░░░░░]  33%
 3/6 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░░░░░░░]  50%
 4/6 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░░]  66%
 5/6 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░]  83%
 6/6 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

 ------ ------------------------------------------------------------------------
  Line   src/TypeSafetyVulnerability.php
 ------ ------------------------------------------------------------------------
  18     Undefined variable: $result
  28     Cannot call method getName() on mixed
  39     Method App\TypeSafetyVulnerability::getData() has no return type
  41     Method App\TypeSafetyVulnerability::getData() has no return type
  51     Cannot access offset mixed on mixed
  67     Parameter #1 $string of function strlen expects string, string|null given
 ------ ------------------------------------------------------------------------

 ------ ------------------------------------------------------------------------
  Line   src/DeadCodeVulnerability.php
 ------ ------------------------------------------------------------------------
  20     Unreachable statement - code above always terminates
  28     Method App\DeadCodeVulnerability::unusedMethod() is unused
  37     Variable $unused is never read
 ------ ------------------------------------------------------------------------

 ------ ------------------------------------------------------------------------
  Line   src/DatabaseVulnerability.php
 ------ ------------------------------------------------------------------------
  29     Method App\DatabaseVulnerability::getUserById() has no return type
  38     Method App\DatabaseVulnerability::searchUsers() has no return type
 ------ ------------------------------------------------------------------------

 ------ ------------------------------------------------------------------------
  Line   src/FileVulnerability.php
 ------ ------------------------------------------------------------------------
  16     Function file_get_contents invoked with 1 parameter
  25     Function file_put_contents invoked with 2 parameters
 ------ ------------------------------------------------------------------------

 [ERROR] Found 15 errors

```

## 解析結果の解釈

### 重要度の分類

#### 🔴 高: 即座に修正が必要
- **UndefinedVariable**: 未定義変数の使用
- **PossiblyNullReference**: Null参照の可能性
- **PossiblyNullArgument**: Nullが渡される可能性

これらのエラーは、実行時にエラーやクラッシュを引き起こす可能性が高いです。

#### 🟡 中: 修正を推奨
- **MixedReturnStatement**: 型が不明確な戻り値
- **PossiblyUndefinedArrayOffset**: 配列キーが存在しない可能性
- **UnreachableStatement**: 到達不可能なコード

これらのエラーは、コードの品質や保守性に影響します。

#### 🟢 低: 改善の余地
- **UnusedMethod**: 使用されていないメソッド
- **UnusedVariable**: 使用されていない変数

これらは直接的な問題ではありませんが、コードを整理する機会です。

## 静的解析で検出されない脆弱性

以下の脆弱性は、PsalmやPHPStanの基本設定では検出されません：

❌ **SQLインジェクション**
- 専門的なセキュリティツールが必要
- Psalm Security Pluginなどの追加プラグインが推奨

❌ **XSS (クロスサイトスクリプティング)**
- HTMLエスケープの検証には専門ツールが必要

❌ **コマンドインジェクション**
- セキュリティスキャナーの使用が推奨

❌ **ファイルパストラバーサル**
- セキュリティレビューやペネトレーションテストが必要

## 推奨アクション

1. **まず型安全性の問題を修正**: Psalm/PHPStanが検出する型関連のエラーを修正
2. **セキュリティツールを追加**: Psalm Security Pluginやセキュリティスキャナーを導入
3. **定期的な実行**: CI/CDパイプラインに静的解析を組み込む
4. **継続的な改善**: 新しいコードを追加する際は、静的解析をパスすることを確認

## 参考リンク

- [Psalm Documentation](https://psalm.dev/docs/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [Psalm Security Plugin](https://github.com/psalm/psalm-plugin-symfony)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
