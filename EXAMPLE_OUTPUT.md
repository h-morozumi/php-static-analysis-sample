# 静的解析の実行例 / Static Analysis Example Output

このファイルには、PsalmとPHPStanを実行したときに期待される出力の例を示します。

## Psalmの実行例

```bash
$ composer run psalm
```

### 期待される出力:

```
Target PHP version: 8.2 (inferred from composer.json).
Scanning files...
Analyzing files...

ERROR: PossiblyUnusedMethod - src/DatabaseVulnerability.php:44:21
    Cannot find any calls to method App\DatabaseVulnerability::deleteUser

ERROR: UnusedClass - src/DeadCodeVulnerability.php:9:7
    Class App\DeadCodeVulnerability is never used

ERROR: UnevaluatedCode - src/DeadCodeVulnerability.php:20:9
    Expressions after return/throw/continue

ERROR: UnusedVariable - src/DeadCodeVulnerability.php:40:9
    $unused is never referenced or the value is not used

ERROR: RedundantCondition - src/DeadCodeVulnerability.php:52:13
    Type 10 for $x is always >= 5

ERROR: UnusedClass - src/EntryPoint.php:12:7
    Class App\EntryPoint is never used

ERROR: InvalidScalarArgument - src/SecurityVulnerability.php:18:20
    Argument 1 of md5 expects string, but int provided

ERROR: ForbiddenCode - src/SecurityVulnerability.php:48:19
    Unsafe shell_exec

ERROR: UndefinedVariable - src/TypeSafetyVulnerability.php:18:13
    Cannot find referenced variable $result

ERROR: PossiblyNullArgument - src/TypeSafetyVulnerability.php:73:23
    Argument 1 of strlen cannot be null, possibly null value provided

------------------------------
20 errors found
------------------------------
57 other issues found.
You can display them with --show-info=true
------------------------------

Checks took 0.98 seconds and used 30.447MB of memory
Psalm was able to infer types for 68.7500% of the codebase
```

## PHPStanの実行例

```bash
$ composer run phpstan
```

### 期待される出力:

```
Note: Using configuration file phpstan.neon.
 7/7 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

 ------ -------------------------------------------------------------------
  Line   src/DatabaseVulnerability.php
 ------ -------------------------------------------------------------------
  12     Property App\DatabaseVulnerability::$pdo has no type specified.
  29     Method App\DatabaseVulnerability::getUserById() has no return type
  38     Method App\DatabaseVulnerability::searchUsers() has no return type
  44     Method App\DatabaseVulnerability::deleteUser() has no return type
 ------ -------------------------------------------------------------------

 ------ -------------------------------------------------------------------
  Line   src/TypeSafetyVulnerability.php
 ------ -------------------------------------------------------------------
  18     Undefined variable: $result
  73     Parameter #1 $string of function strlen expects string, string|null given
 ------ -------------------------------------------------------------------

 ------ -------------------------------------------------------------------
  Line   src/DeadCodeVulnerability.php
 ------ -------------------------------------------------------------------
  20     Unreachable statement - code above always terminates
  21     Unreachable statement - code above always terminates
 ------ -------------------------------------------------------------------

 [ERROR] Found 63 errors

```
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

#### 🔴 高（セキュリティ）: 即座に修正が必要
- **TaintedSql**: SQLインジェクション
- **TaintedHtml**: XSS（クロスサイトスクリプティング）
- **TaintedShell**: コマンドインジェクション
- **TaintedEval**: 任意コード実行
- **TaintedUnserialize**: 安全でないデシリアライゼーション

これらのエラーは、セキュリティ脆弱性を示しており、攻撃者に悪用される可能性があります。

#### 🔴 高（型安全性）: 即座に修正が必要
- **UndefinedVariable**: 未定義変数の使用
- **PossiblyNullReference**: Null参照の可能性
- **PossiblyNullArgument**: Nullが渡される可能性

これらのエラーは、実行時にエラーやクラッシュを引き起こす可能性が高いです。

#### 🟡 中: 修正を推奨
- **MixedReturnStatement**: 型が不明確な戻り値
- **PossiblyUndefinedArrayOffset**: 配列キーが存在しない可能性
- **UnreachableStatement**: 到達不可能なコード
- **ForbiddenCode**: 危険な関数の使用

これらのエラーは、コードの品質や保守性に影響します。

#### 🟢 低: 改善の余地
- **UnusedMethod**: 使用されていないメソッド
- **UnusedVariable**: 使用されていない変数
- **UnusedClass**: 使用されていないクラス

これらは直接的な問題ではありませんが、コードを整理する機会です。

## 静的解析で検出されない脆弱性

以下の脆弱性は、PsalmやPHPStanの基本設定では検出されません：

❌ **基本モードでは検出困難**
- SQLインジェクション
- XSS (クロスサイトスクリプティング)
- コマンドインジェクション
- ファイルパストラバーサル

これらのセキュリティ脆弱性を検出するには、**Psalm Taint Analysis** を使用する必要があります。

## Taint Analysisの仕組み

Psalm Taint Analysisは、通常の静的解析とは異なり、**データフローを追跡**します：

1. **汚染源（Source）の特定**: ユーザー入力（`$_GET`、`$_POST`、`$_COOKIE`など）
2. **データの流れを追跡**: 変数への代入、関数の引数、戻り値など
3. **汚染シンク（Sink）での検出**: 危険な操作（SQL実行、HTML出力、ファイル操作など）

これにより、従来の静的解析では検出が困難だったセキュリティ脆弱性を発見できます。

### EntryPoint.php の役割

このリポジトリには `EntryPoint.php` が含まれており、`$_GET`、`$_POST`、`$_COOKIE` からの入力を脆弱なメソッドに渡すことで、Taint Analysisが脆弱性を検出できるようになっています。

## Psalm Taint Analysisの実行例

```bash
$ composer run psalm-security
```

### 期待される出力:

```
Target PHP version: 8.2 (inferred from composer.json).
Scanning files...
Analyzing files...

ERROR: TaintedHtml - src/EntryPoint.php:45:14
    Detected tainted HTML
    
    $_POST['comment'] -> $comment -> XssVulnerability::renderComment -> echo

ERROR: TaintedTextWithQuotes - src/EntryPoint.php:45:14
    Detected tainted text with possible quotes

ERROR: TaintedFile - src/FileVulnerability.php:19:34
    Detected tainted file handling
    
    $_GET['file'] -> $filename -> file_get_contents

ERROR: TaintedFile - src/FileVulnerability.php:29:27
    Detected tainted file handling
    
    $_POST['path'] -> $path -> file_put_contents

ERROR: TaintedEval - src/SecurityVulnerability.php:38:14
    Detected tainted code passed to eval or similar
    
    $_POST['code'] -> $code -> eval

ERROR: TaintedShell - src/SecurityVulnerability.php:48:30
    Detected tainted shell code
    
    $_GET['host'] -> $host -> shell_exec

ERROR: TaintedUnserialize - src/SecurityVulnerability.php:59:28
    Detected tainted code passed to unserialize or similar
    
    $_COOKIE['data'] -> $serializedData -> unserialize

ERROR: TaintedHtml - src/XssVulnerability.php:18:14
    Detected tainted HTML
    
    $_GET['input'] -> $userInput -> echo

ERROR: TaintedTextWithQuotes - src/XssVulnerability.php:18:14
    Detected tainted text with possible quotes

ERROR: TaintedHtml - src/XssVulnerability.php:36:14
    Detected tainted HTML
    
    $_COOKIE['msg'] -> $message -> echo

ERROR: TaintedTextWithQuotes - src/XssVulnerability.php:36:14
    Detected tainted text with possible quotes

------------------------------
11 errors found
------------------------------

Checks took 1.01 seconds and used 30.446MB of memory
Psalm was able to infer types for 68.7500% of the codebase
```

### Taint Analysisで検出される脆弱性タイプ

| エラータイプ | 説明 | 検出数 |
|-------------|------|--------|
| TaintedHtml | XSS（クロスサイトスクリプティング） | 3件 |
| TaintedTextWithQuotes | クォート付きテキストの危険な出力 | 3件 |
| TaintedFile | ファイルパストラバーサル | 2件 |
| TaintedShell | コマンドインジェクション | 1件 |
| TaintedEval | 任意コード実行 | 1件 |
| TaintedUnserialize | 安全でないデシリアライゼーション | 1件 |

## レポート生成の実行例

```bash
$ composer run report:all
```

### 生成されるファイル（/reportsディレクトリ）:

**Psalm (13ファイル):**
- psalm.json, psalm.xml, psalm.sarif, psalm.txt
- psalm.console, psalm.emacs, psalm.pylint
- psalm-checkstyle.xml, psalm-junit.xml
- psalm-codeclimate.json, psalm-sonarqube.json
- psalm-summary.json, psalm-count.txt

**Psalm Taint Analysis (13ファイル):**
- psalm-taint.json, psalm-taint.xml, psalm-taint.sarif, psalm-taint.txt
- psalm-taint.console, psalm-taint.emacs, psalm-taint.pylint
- psalm-taint-checkstyle.xml, psalm-taint-junit.xml
- psalm-taint-codeclimate.json, psalm-taint-sonarqube.json
- psalm-taint-summary.json, psalm-taint-count.txt

**PHPStan (10ファイル):**
- phpstan.json, phpstan.sarif, phpstan-pretty.json
- phpstan-checkstyle.xml, phpstan-junit.xml
- phpstan-gitlab.json, phpstan-github.txt
- phpstan-teamcity.txt, phpstan-table.txt, phpstan-raw.txt

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
