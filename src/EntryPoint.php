<?php

namespace App;

/**
 * Taint Analysis検証用のエントリーポイント
 * Entry point for Taint Analysis verification
 * 
 * このファイルは、Psalm Taint Analysisがセキュリティ脆弱性を
 * 検出できることを実証するためのものです。
 */
class EntryPoint
{
    /**
     * SQLインジェクションのデモ
     * SQL Injection demo - TaintedSql
     */
    public function handleUserRequest(): void
    {
        $db = new DatabaseVulnerability(new \PDO('sqlite::memory:'));
        
        // $_GET からの入力を直接SQLクエリに渡す (危険)
        $userId = $_GET['id'];
        $db->getUserById($userId);
        
        // $_POST からの入力を検索に使用 (危険)
        $searchName = $_POST['name'];
        $db->searchUsers($searchName);
    }

    /**
     * XSSのデモ
     * XSS demo - TaintedHtml
     */
    public function handleXssRequest(): void
    {
        $xss = new XssVulnerability();
        
        // $_GET からの入力をエスケープせずに出力 (危険)
        $userInput = $_GET['input'];
        $xss->displayUserInput($userInput);
        
        // $_POST からのコメントをそのまま表示 (危険)
        $comment = $_POST['comment'];
        echo $xss->renderComment($comment);
        
        // $_COOKIE からの値をJavaScriptに埋め込む (危険)
        $message = $_COOKIE['msg'];
        $xss->showMessage($message);
    }

    /**
     * コマンドインジェクションのデモ
     * Command Injection demo - TaintedShell
     */
    public function handleCommandRequest(): void
    {
        $security = new SecurityVulnerability();
        
        // $_GET からの入力をシェルコマンドに渡す (危険)
        $host = $_GET['host'];
        $security->pingHost($host);
        
        // $_POST からのコードをevalで実行 (極めて危険)
        $code = $_POST['code'];
        $security->executeCode($code);
    }

    /**
     * ファイル操作の脆弱性デモ
     * File operation vulnerability demo - TaintedFile
     */
    public function handleFileRequest(): void
    {
        $file = new FileVulnerability();
        
        // $_GET からのファイルパスを直接使用 (危険)
        $filename = $_GET['file'];
        $file->readFile($filename);
        
        // $_POST からのパスとコンテンツを使用 (危険)
        $path = $_POST['path'];
        $content = $_POST['content'];
        $file->saveFile($path, $content);
    }

    /**
     * 安全でないデシリアライゼーションのデモ
     * Unsafe deserialization demo - TaintedUnserialize
     */
    public function handleDataRequest(): void
    {
        $security = new SecurityVulnerability();
        
        // $_COOKIE からのシリアライズデータをデシリアライズ (危険)
        $data = $_COOKIE['data'];
        $security->loadData($data);
    }
}
