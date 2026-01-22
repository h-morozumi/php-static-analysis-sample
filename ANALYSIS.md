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

| 脆弱性タイプ | Psalm（通常） | Psalm（Taint） | PHPStan | 推奨ツール |
|------------|-------------|---------------|---------|-----------|
| SQLインジェクション | ❌ | ✅ | ❌ | Psalm Taint Analysis |
| XSS | ❌ | ✅ | ❌ | Psalm Taint Analysis |
| 未定義変数 | ✅ | ✅ | ✅ | - |
| Null参照 | ✅ | ✅ | ✅ | - |
| 型の不整合 | ✅ | ✅ | ✅ | - |
| 配列キー存在チェック | ✅ | ✅ | ✅ | - |
| パストラバーサル | ❌ | ✅ | ❌ | Psalm Taint Analysis |
| 弱い暗号化 | ❌ | ❌ | ❌ | Security Plugin |
| eval使用 | ⚠️ | ✅ | ❌ | Psalm Taint Analysis |
| コマンドインジェクション | ❌ | ✅ | ❌ | Psalm Taint Analysis |
| 到達不可能コード | ✅ | ✅ | ✅ | - |
| 未使用メソッド/変数 | ✅ | ✅ | ⚠️ | - |

---

## Psalm Taint Analysis の期待される出力例

```bash
$ ./vendor/bin/psalm --taint-analysis
```

### 期待される出力:

```
ERROR: TaintedSql - src/DatabaseVulnerability.php:18:16
    Detected tainted SQL
    $sql = "SELECT * FROM users WHERE id = " . $id;

ERROR: TaintedHtml - src/XssVulnerability.php:15:14
    Detected tainted HTML
    echo "<div>" . $userInput . "</div>";

ERROR: TaintedShell - src/SecurityVulnerability.php:32:14
    Detected tainted shell command
    $output = shell_exec("ping -c 1 " . $host);

ERROR: TaintedFile - src/FileVulnerability.php:15:16
    Detected tainted file path
    return file_get_contents($filename);

ERROR: TaintedEval - src/SecurityVulnerability.php:22:5
    Detected tainted code execution
    eval($code);
```

---

## 結論

PsalmとPHPStanは、主に以下の問題を検出するのに優れています：

✅ **得意な分野**:
- 型安全性の問題
- Null安全性
- 未定義変数
- デッドコード
- 到達不可能コード

⚠️ **Psalm Taint Analysis で検出可能**:
- SQLインジェクション
- XSS
- コマンドインジェクション
- ファイルパストラバーサル
- evalによるコード実行

❌ **検出が困難な分野**:
- 弱い暗号化アルゴリズム
- セッション固定化
- その他のビジネスロジックの脆弱性

セキュリティの脆弱性を包括的に検出するには、以下のツールの組み合わせが推奨されます：
- **Psalm** / **PHPStan**（型安全性）
- **Psalm Taint Analysis**（セキュリティ脆弱性）
- **Snyk Code**（追加のセキュリティスキャン）
- **SonarQube**（総合的なコード品質）

## 参考リンク

- [Psalm Security Analysis Documentation](https://psalm.dev/docs/security_analysis/)
- [Detect PHP Security Vulnerabilities with Psalm](https://psalm.dev/articles/detect-security-vulnerabilities-with-psalm)