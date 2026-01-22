# 脆弱性詳細分析 / Vulnerability Analysis

このドキュメントでは、各ファイルに含まれる脆弱性と、静的解析ツールでの検出可能性について説明します。

## 1. DatabaseVulnerability.php - SQLインジェクション

### 脆弱性の内容
```php
// 危険な例
$sql = "SELECT * FROM users WHERE id = " . $id;
$query = "SELECT * FROM users WHERE name LIKE '%" . $name . "%'";
```

### 問題点
- プリペアドステートメントを使用していない
- ユーザー入力を直接SQL文に連結している
- SQLインジェクション攻撃が可能

### 静的解析での検出
- **Psalm**: 基本的には検出困難（文字列連結の解析には限界がある）
- **PHPStan**: 基本的には検出困難
- **専門ツール必要**: Psalm Security Plugin、Snyk Code などのセキュリティ特化ツールが必要

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
- **Psalm**: 基本設定では検出困難（文字列操作は追跡が難しい）
- **PHPStan**: 基本設定では検出困難
- **専門ツール必要**: セキュリティプラグインが必要

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
- **Psalm**: 部分的に検出可能（型チェックのみ）
- **PHPStan**: 部分的に検出可能（型チェックのみ）
- **専門ツール必要**: セキュリティスキャナーが推奨

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
- **Psalm**: ⚠️ 部分的に検出可能（設定による）- `ForbiddenCode`
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
- **Psalm**: ❌ 基本設定では検出困難
- **PHPStan**: ❌ 基本設定では検出困難
- **セキュリティスキャナー**: 検出可能

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

| 脆弱性タイプ | Psalm | PHPStan | 推奨ツール |
|------------|-------|---------|-----------|
| SQLインジェクション | ❌ | ❌ | Psalm Security Plugin |
| XSS | ❌ | ❌ | Security Scanner |
| 未定義変数 | ✅ | ✅ | - |
| Null参照 | ✅ | ✅ | - |
| 型の不整合 | ✅ | ✅ | - |
| 配列キー存在チェック | ✅ | ✅ | - |
| パストラバーサル | ❌ | ❌ | Security Scanner |
| 弱い暗号化 | ❌ | ❌ | Security Plugin |
| eval使用 | ⚠️ | ❌ | Security Plugin |
| コマンドインジェクション | ❌ | ❌ | Security Scanner |
| 到達不可能コード | ✅ | ✅ | - |
| 未使用メソッド/変数 | ✅ | ⚠️ | - |

## 結論

PsalmとPHPStanは、主に以下の問題を検出するのに優れています：

✅ **得意な分野**:
- 型安全性の問題
- Null安全性
- 未定義変数
- デッドコード
- 到達不可能コード

❌ **検出が困難な分野**:
- SQLインジェクション
- XSS
- コマンドインジェクション
- ファイルパストラバーサル
- 暗号化の脆弱性

セキュリティの脆弱性を包括的に検出するには、以下のツールの組み合わせが推奨されます：
- Psalm / PHPStan（型安全性）
- Psalm Security Plugin（セキュリティ）
- Snyk Code（セキュリティスキャン）
- SonarQube（総合的なコード品質）
