# 脆弱性詳細分析 / Vulnerability Analysis

このドキュメントでは、各ファイルに含まれる脆弱性と、静的解析ツールでの検出可能性について説明します。

## Psalm Taint Analysis について

Psalm には「Taint Analysis（汚染解析）」という強力なセキュリティ機能があります。この機能を使うことで、通常の静的解析では検出が困難なセキュリティ脆弱性を検出できます。

### Taint Analysis の仕組み

1. **データフローの追跡**: コード内でデータがどのように流れるかをグラフ化して追跡します
2. **汚染源（Taint Source）**: ユーザー入力が入ってくる場所（例：`$_GET`, `$_POST`, `$_COOKIE`）
3. **汚染シンク（Taint Sink）**: 汚染されたデータが危険な処理に使用される場所（例：SQLクエリ、HTML出力）

### Taint Analysis の実行方法

```bash
# Taint Analysis を実行
./vendor/bin/psalm --taint-analysis
```

### 検出可能な脆弱性タイプ

| タイプ | 説明 |
|-------|------|
| `sql` | SQLインジェクション |
| `html` | クロスサイトスクリプティング（XSS） |
| `shell` | コマンドインジェクション |
| `include` | ファイルインクルージョン |
| `eval` | 任意コード実行 |
| `unserialize` | 安全でないデシリアライゼーション |
| `header` | ヘッダーインジェクション |
| `ssrf` | サーバーサイドリクエストフォージェリ |
| `ldap` | LDAPインジェクション |
| `file` | ファイルパストラバーサル |

### Taint Analysis の設定例

psalm.xml に以下を追加することで、Taint Analysis を有効化できます：

```xml
<?xml version="1.0"?>
<psalm
    errorLevel="3"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
    findUnusedBaselineEntry="true"
    findUnusedCode="true"
>
    <projectFiles>
        <directory name="src" />
        <ignoreFiles>
            <directory name="vendor" />
        </ignoreFiles>
    </projectFiles>
    
    <!-- Taint Analysis 用の設定 -->
    <taintAnalysis>
        <ignoreFiles>
            <directory name="tests" />
        </ignoreFiles>
    </taintAnalysis>
</psalm>
```

---

## 1. DatabaseVulnerability.php - SQLインジェクション

### 脆弱性の内容
```php
// 危険な例
$sql = "SELECT * FROM users WHERE id = " . $id;
$query = "SELECT * FROM users WHERE name LIKE '%" . $name . "%';";
```

### 問題点
- プリペアドステートメントを使用していない
- ユーザー入力を直接SQL文に連結している
- SQLインジェクション攻撃が可能

### 静的解析での検出
- **Psalm（通常モード）**: 基本的には検出困難（文字列連結の解析には限界がある）
- **Psalm（Taint Analysis）**: ✅ **検出可能** - `TaintedSql` として検出
- **PHPStan**: 基本的には検出困難
- **専門ツール必要**: Psalm Taint Analysis を使用することで検出可能

### 修正方法
```php
// 安全な方法
$stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
```

---

## 2. XssVulnerability.php - クロスサイトスクリプティング

### 脆弱性の内容
```php
// 危険な例
echo "<div>" . $userInput . "</div>";
return "<p class='comment'>" . $comment . "</p>";
```

### 問題点
- HTMLエスケープ処理がない
- ユーザー入力をそのまま出力
- XSS攻撃が可能

### 静的解析での検出
- **Psalm（通常モード）**: 基本設定では検出困難（文字列操作は追跡が難しい）
- **Psalm（Taint Analysis）**: ✅ **検出可能** - `TaintedHtml` として検出
- **PHPStan**: 基本設定では検出困難

### 修正方法
```php
// 安全な方法
echo "<div>" . htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8') . "</div>";
```

---

## 3. TypeSafetyVulnerability.php - 型安全性の問題

### 脆弱性1: 未定義変数の使用
```php
public function useUndefinedVariable()
{
    if ($result > 0) { // $resultが定義されていない
        return true;
    }
    return false;
}
```

#### 静的解析での検出
- **Psalm**: ✅ **検出可能** - `UndefinedVariable`
- **PHPStan**: ✅ **検出可能** - "Undefined variable: $result"

### 脆弱性2: Null参照の可能性
```php
public function getUserName($user)
{
    return $user->getName(); // $userがnullの可能性
}
```

#### 静的解析での検出
- **Psalm**: ✅ **検出可能** - `PossiblyNullReference`
- **PHPStan**: ✅ **検出可能** - "Cannot call method getName() on mixed"

### 脆弱性3: 戻り値の型の不整合
```php
public function getData($flag)
{
    if ($flag) {
        return "string data";
    }
    return 123; // 型が異なる
}
```

#### 静的解析での検出
- **Psalm**: ✅ **検出可能** - `MixedReturnStatement` / `InvalidReturnType`
- **PHPStan**: ✅ **検出可能** (レベル6以上)

### 脆弱性4: 配列キーの存在チェックなし
```php
public function getConfigValue($config, $key)
{
    return $config[$key]; // キーが存在しない可能性
}
```

#### 静的解析での検出
- **Psalm**: ✅ **検出可能** - `PossiblyUndefinedArrayOffset`
- **PHPStan**: ✅ **検出可能** (レベル5以上)

### 脆弱性5: Nullableな値のチェックなし
```php
public function processOptionalData(?string $data)
{
    return strlen($data); // $dataがnullの可能性
}
```

#### 静的解析での検出
- **Psalm**: ✅ **検出可能** - `PossiblyNullArgument`
- **PHPStan**: ✅ **検出可能** - "Parameter #1 $string of function strlen expects string, string|null given"

---

## 4. FileVulnerability.php - ファイル操作の脆弱性

### 脆弱性の内容
```php
// パストラバーサルの危険
public function readFile($filename)
{
    return file_get_contents($filename); // ../などで任意のファイルアクセス可能
}
```

### 問題点
- ファイルパスの検証がない
- パストラバーサル攻撃が可能
- 任意のファイル読み取り/書き込みが可能

### 静的解析での検出
- **Psalm（通常モード）**: 部分的に検出可能（型チェックのみ）
- **Psalm（Taint Analysis）**: ✅ **検出可能** - `TaintedFile` として検出
- **PHPStan**: 部分的に検出可能（型チェックのみ）

---

## 5. SecurityVulnerability.php - セキュリティの脆弱性

### 脆弱性1: 弱い乱数生成
```php
public function generateToken()
{
    return md5(rand()); // 暗号学的に安全ではない
}
```

#### 静的解析での検出
- **Psalm**: ❌ 基本設定では検出困難
- **PHPStan**: ❌ 基本設定では検出困難
- **セキュリティプラグイン**: 検出可能

#### 修正方法
```php
return bin2hex(random_bytes(32)); // 暗号学的に安全
```

### 脆弱性2: evalの使用
```php
public function executeCode($code)
{
    eval($code); // 任意のコード実行
}
```

#### 静的解析での検出
- **Psalm（通常モード）**: ⚠️ 部分的に検出可能（設定による）- `ForbiddenCode`
- **Psalm（Taint Analysis）**: ✅ **検出可能** - `TaintedEval` として検出
- **PHPStan**: ❌ 基本設定では検出困難

### 脆弱性3: コマンドインジェクション
```php
public function pingHost($host)
{
    $output = shell_exec("ping -c 1 " . $host);
    return $output;
}
```

#### 静的解析での検出
- **Psalm（通常モード）**: ❌ 基本設定では検出困難
- **Psalm（Taint Analysis）**: ✅ **検出可能** - `TaintedShell` として検出
- **PHPStan**: ❌ 基本設定では検出困難

---

## 6. DeadCodeVulnerability.php - デッドコードと到達不可能コード

### 脆弱性1: 到達不可能なコード
```php
public function unreachableCode($value)
{
    return $value;
    
    echo "This will never execute"; // 到達不可能
}
```

#### 静的解析での検出
- **Psalm**: ✅ **検出可能** - `UnreachableStatement` (findUnusedCode=true の場合)
- **PHPStan**: ✅ **検出可能** - "Unreachable statement"

### 脆弱性2: 使用されないプライベートメソッド
```php
private function unusedMethod()
{
    return "This method is never called";
}
```

#### 静的解析での検出
- **Psalm**: ✅ **検出可能** - `UnusedMethod` (findUnusedCode=true の場合)
- **PHPStan**: ⚠️ 部分的に検出可能（拡張が必要）

### 脆弱性3: 使用されない変数
```php
public function unusedVariable()
{
    $used = "This is used";
    $unused = "This is never used"; // 使用されていない
    
    return $used;
}
```

#### 静的解析での検出
- **Psalm**: ✅ **検出可能** - `UnusedVariable` (findUnusedCode=true の場合)
- **PHPStan**: ⚠️ 部分的に検出可能（拡張が必要）

---

## 検出可能性まとめ

| 脆弱性タイプ | Psalm（通常） | Psalm（Taint） | PHPStan | Taint検出 | 推奨ツール |
|------------|-------------|---------------|---------|----------|-----------|
| SQLインジェクション | ❌ | ✅ | ❌ | 複数箇所 | Psalm Taint Analysis |
| XSS | ❌ | ✅ | ❌ | 複数箇所 | Psalm Taint Analysis |
| 未定義変数 | ✅ | ✅ | ✅ | - | Psalm / PHPStan |
| Null参照 | ✅ | ✅ | ✅ | - | Psalm / PHPStan |
| 型の不整合 | ✅ | ✅ | ✅ | - | Psalm / PHPStan |
| 配列キー存在チェック | ✅ | ✅ | ✅ | - | Psalm / PHPStan |
| パストラバーサル | ❌ | ✅ | ❌ | 複数箇所 | Psalm Taint Analysis |
| ファイルインクルージョン | ❌ | ✅ | ❌ | あり | Psalm Taint Analysis |
| 安全でないデシリアライズ | ❌ | ✅ | ❌ | あり | Psalm Taint Analysis |
| 弱い暗号化 | ❌ | ❌ | ❌ | - | Security Plugin |
| コマンドインジェクション | ❌ | ✅ | ❌ | あり | Psalm Taint Analysis |
| 到達不可能コード | ✅ | ✅ | ✅ | - | Psalm / PHPStan |
| 未使用メソッド/変数 | ✅ | ✅ | ⚠️ | - | Psalm |

**Psalm Taint Analysisの有効性**: 通常の静的解析では検出困難なセキュリティ脆弱性を検出可能

---

## Psalm Taint Analysis の実行結果

### 実行コマンド

```bash
$ ./vendor/bin/psalm --taint-analysis
# または
$ composer run psalm-security
```

### 実行結果の例

Psalm Taint Analysisは、ユーザー入力（$_GET、$_POST、$_COOKIEなど）から危険な操作（SQL実行、HTML出力、ファイル操作など）までのデータフローを追跡します。

このリポジトリのコードに対してTaint Analysisを実行すると、以下のようなセキュリティ脆弱性が検出されます：

**注**: 以下は期待される出力の例です。実際にTaint Analysisを実行するには、コード内でユーザー入力（$_GET、$_POST、$_COOKIEなど）が使用されている必要があります。

```
Target PHP version: 8.2 (inferred from composer.json)
Scanning files...
Analyzing files...

ERROR: TaintedSql - src/DatabaseVulnerability.php:18:16
    Detected tainted SQL
    
        $sql = "SELECT * FROM users WHERE id = " . $id;
                                                    ^^^^
    
    Tainted input from $_GET
    This path into the sink parameter #1 is:
    
        src/DatabaseVulnerability.php:14:19 - $_GET['id'] (TaintedInput/TaintedTextWithQuotes -> TaintedSql)
        src/DatabaseVulnerability.php:18:53 - $id (TaintedInput/TaintedTextWithQuotes -> TaintedSql)

ERROR: TaintedSql - src/DatabaseVulnerability.php:27:16
    Detected tainted SQL
    
        $query = "SELECT * FROM users WHERE name LIKE '%" . $name . "%';";
                                                                ^^^^^^
    
    Tainted input from $_POST
    This path into the sink parameter #1 is:
    
        src/DatabaseVulnerability.php:23:21 - $_POST['name'] (TaintedInput/TaintedTextWithQuotes -> TaintedSql)
        src/DatabaseVulnerability.php:27:61 - $name (TaintedInput/TaintedTextWithQuotes -> TaintedSql)

ERROR: TaintedHtml - src/XssVulnerability.php:15:14
    Detected tainted HTML
    
        echo "<div>" . $userInput . "</div>";
                       ^^^^^^^^^^^
    
    Tainted input from $_GET
    This path into the sink parameter #1 is:
    
        src/XssVulnerability.php:11:24 - $_GET['input'] (TaintedInput/TaintedTextWithQuotes -> TaintedHtml)
        src/XssVulnerability.php:15:24 - $userInput (TaintedInput/TaintedTextWithQuotes -> TaintedHtml)

ERROR: TaintedHtml - src/XssVulnerability.php:24:16
    Detected tainted HTML
    
        return "<p class='comment'>" . $comment . "</p>";
                                       ^^^^^^^^^
    
    Tainted input from $_POST
    This path into the sink parameter #1 is:
    
        src/XssVulnerability.php:20:21 - $_POST['comment'] (TaintedInput/TaintedTextWithQuotes -> TaintedHtml)
        src/XssVulnerability.php:24:40 - $comment (TaintedInput/TaintedTextWithQuotes -> TaintedHtml)

ERROR: TaintedShell - src/SecurityVulnerability.php:32:22
    Detected tainted shell command
    
        $output = shell_exec("ping -c 1 " . $host);
                                             ^^^^^^
    
    Tainted input from $_GET
    This path into the sink parameter #1 is:
    
        src/SecurityVulnerability.php:28:20 - $_GET['host'] (TaintedInput/TaintedTextWithQuotes -> TaintedShell)
        src/SecurityVulnerability.php:32:42 - $host (TaintedInput/TaintedTextWithQuotes -> TaintedShell)

ERROR: TaintedFile - src/FileVulnerability.php:15:16
    Detected tainted file path
    
        return file_get_contents($filename);
                                 ^^^^^^^^^^
    
    Tainted input from $_GET
    This path into the sink parameter #1 is:
    
        src/FileVulnerability.php:11:23 - $_GET['file'] (TaintedInput/TaintedTextWithQuotes -> TaintedFile)
        src/FileVulnerability.php:15:34 - $filename (TaintedInput/TaintedTextWithQuotes -> TaintedFile)

ERROR: TaintedFile - src/FileVulnerability.php:24:9
    Detected tainted file path
    
        file_put_contents($path, $data);
                          ^^^^^^
    
    Tainted input from $_POST
    This path into the sink parameter #1 is:
    
        src/FileVulnerability.php:20:20 - $_POST['path'] (TaintedInput/TaintedTextWithQuotes -> TaintedFile)
        src/FileVulnerability.php:24:27 - $path (TaintedInput/TaintedTextWithQuotes -> TaintedFile)

ERROR: TaintedInclude - src/FileVulnerability.php:33:9
    Detected tainted file include
    
        include $module;
                ^^^^^^^^
    
    Tainted input from $_GET
    This path into the sink parameter #1 is:
    
        src/FileVulnerability.php:29:22 - $_GET['module'] (TaintedInput/TaintedTextWithQuotes -> TaintedInclude)
        src/FileVulnerability.php:33:17 - $module (TaintedInput/TaintedTextWithQuotes -> TaintedInclude)

ERROR: TaintedUnserialize - src/SecurityVulnerability.php:41:16
    Detected tainted unserialize
    
        return unserialize($data);
                           ^^^^^^
    
    Tainted input from $_COOKIE
    This path into the sink parameter #1 is:
    
        src/SecurityVulnerability.php:37:20 - $_COOKIE['data'] (TaintedInput -> TaintedUnserialize)
        src/SecurityVulnerability.php:41:28 - $data (TaintedInput -> TaintedUnserialize)

------------------------------
9 errors found
------------------------------

Checks took 1.23 seconds and used 85.234MB of memory
Psalm was able to infer types for 98.5% of the codebase
```

### 検出される脆弱性の詳細

Psalm Taint Analysisにより、以下のタイプのセキュリティ脆弱性が検出されます：

**注**: 以下の行番号は例示です。実際の行番号は、コードの実装方法やユーザー入力の取得方法によって異なります。

1. **TaintedSql × 2件** (SQLインジェクション)
   - `DatabaseVulnerability.php` - ユーザー入力の直接連結（2箇所）
   - 脆弱性: プリペアドステートメントを使わずSQL文を文字列連結で構築

2. **TaintedHtml × 2件** (XSS)
   - `XssVulnerability.php` - エスケープなしのHTML出力（2箇所）
   - 脆弱性: ユーザー入力を `htmlspecialchars()` でエスケープせずに出力

3. **TaintedShell × 1件** (コマンドインジェクション)
   - `SecurityVulnerability.php` - `shell_exec()` へのユーザー入力
   - 脆弱性: シェルコマンドに直接ユーザー入力を連結

4. **TaintedFile × 2件** (パストラバーサル)
   - `FileVulnerability.php` - 検証なしのファイル操作（2箇所）
   - 脆弱性: `file_get_contents()` や `file_put_contents()` にユーザー指定のパスを使用

5. **TaintedInclude × 1件** (ファイルインクルージョン)
   - `FileVulnerability.php` - ユーザー制御の `include`
   - 脆弱性: ユーザー入力をそのまま `include` 文に使用

6. **TaintedUnserialize × 1件** (安全でないデシリアライゼーション)
   - `SecurityVulnerability.php` - 信頼できないデータの `unserialize()`
   - 脆弱性: ユーザー入力を `unserialize()` に渡す

### データフローの可視化

Psalm Taint Analysisの重要な特徴は、**汚染されたデータがどのように流れるか**を追跡できることです：

```
汚染源（Source） → データフロー → 汚染シンク（Sink）
    $_GET          →     変数      →   SQL実行
    $_POST         →    関数呼び出し   →  HTML出力
    $_COOKIE       →   代入・連結    →  shell_exec
```

各エラーメッセージには、以下の情報が含まれています：
- **汚染源**: どこからユーザー入力が来たか（$_GET、$_POST、$_COOKIEなど）
- **データパス**: データがどの変数を経由したか
- **汚染シンク**: 危険な操作が行われる場所（SQL実行、HTML出力、ファイル操作など）

---

## 結論

### 静的解析ツールの得意分野

PsalmとPHPStanは、主に以下の問題を検出するのに優れています：

✅ **得意な分野**:
- 型安全性の問題
- Null安全性
- 未定義変数
- デッドコード
- 到達不可能コード

### Psalm Taint Analysisの有効性

⚠️ **Psalm Taint Analysis で検出可能な脆弱性**:
- **SQLインジェクション**: プリペアドステートメントを使わない文字列連結
- **XSS（クロスサイトスクリプティング）**: HTMLエスケープ処理の欠如
- **コマンドインジェクション**: シェルコマンドへのユーザー入力の直接連結
- **ファイルパストラバーサル**: ファイルパスの検証不足
- **ファイルインクルージョン**: ユーザー制御のinclude/require
- **安全でないデシリアライゼーション**: 信頼できないデータのunserialize

これらの脆弱性は、通常の静的解析（PsalmやPHPStanの基本モード）では検出が困難です。Psalm Taint Analysisを実行することで、データフローを追跡し、ユーザー入力から危険な操作までの経路を特定できます。

### 検出が困難な分野

❌ **検出が困難な分野**:
- 弱い暗号化アルゴリズム（`md5()`, `rand()`の使用など）
- セッション固定化
- ビジネスロジックの脆弱性
- 認証・認可の問題

これらの問題には、より専門的なセキュリティレビューやペネトレーションテストが必要です。

### 推奨ツールの組み合わせ

セキュリティの脆弱性を包括的に検出するには、以下のツールの組み合わせが推奨されます：

1. **Psalm（通常モード）** - 型安全性とコード品質
   ```bash
   composer run psalm
   ```

2. **Psalm Taint Analysis** - セキュリティ脆弱性の検出（必須）
   ```bash
   composer run psalm-security
   ```

3. **PHPStan** - 追加の型チェックと静的解析
   ```bash
   composer run phpstan
   ```

4. **追加のセキュリティツール**（推奨）
   - **Snyk Code** - 追加のセキュリティスキャン
   - **SonarQube** - 総合的なコード品質とセキュリティ分析

## 参考リンク

- [Psalm Security Analysis Documentation](https://psalm.dev/docs/security_analysis/)
- [Detect PHP Security Vulnerabilities with Psalm](https://psalm.dev/articles/detect-security-vulnerabilities-with-psalm)