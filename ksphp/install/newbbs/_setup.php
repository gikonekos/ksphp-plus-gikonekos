<?php

/*
擬古猫+ ksphp-plus / Admin secrets setup tool
2026-07-20 Gikoneko

管理パスワード（ADMINPOST）・管理モード移行キーワード（ADMINKEY）を
conf.php・install.phpの外（このスクリプトが書き込む local.php）で
設定・変更するための、独立した単機能ツールです。

【方針（利用者との合意事項）】
- install.php・conf.phpには一切依存しない（インストール機構とパスワード
  機構は分離する）。
- local.php が存在しない間は誰でも新規設定できる（先着一名が設定を
  完了させれば、それ以降は今のパスワードを知る人しか変更できなくなる、
  という簡易な仕組み。設置直後に速やかに設定を済ませることが前提）。
- local.php が存在する場合、変更には現在のパスワードでのログインが必須。
- 設定完了後、このツール自身のファイル名を設置者が自由に決めた名前へ
  変更する（rename）。デフォルト名は date('YmdHi') と下の $SETUP_SEED
  を元にsha256で生成した推測困難な文字列（$SETUP_SEEDはこのファイルを
  直接編集することで変更可能）。
*/

// 推測困難なデフォルトファイル名を作る際の種文字列。
// 好みで書き換えて構いません（rename前に変更してから初回アクセスして
// ください。renameは初回設定完了時に一度だけ行われます）。
$SETUP_SEED = 'gikonekos';

$secretsFile = __DIR__ . '/local.php';

function ksphp_setup_default_name(string $seed): string
{
    $hash = hash('sha256', date('YmdHi') . $seed);
    return substr($hash, 0, 12) . '.php';
}

function ksphp_setup_sanitize_filename(string $name, string $fallback): string
{
    $name = basename(trim($name));
    // 拡張子.php・使用可能文字（英数字・ハイフン・アンダースコア）のみ許可。
    if ($name === '' || !preg_match('/^[A-Za-z0-9_-]+\.php$/', $name)) {
        return $fallback;
    }
    return $name;
}

function ksphp_setup_write_secrets(string $path, string $adminpost, string $adminkey): bool
{
    $content = "<?php\n\n"
        . "// 20260720 Gikoneko: Admin secrets. Not part of the newbbs/ template;\n"
        . "// install.php never sees this file. Managed by the setup tool only.\n"
        . "return array(\n"
        . "    'ADMINPOST' => " . var_export($adminpost, true) . ",\n"
        . "    'ADMINKEY' => " . var_export($adminkey, true) . ",\n"
        . ");\n";
    $result = @file_put_contents($path, $content, LOCK_EX);
    return $result !== false;
}

function ksphp_setup_html_head(string $title): string
{
    return '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8">'
        . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head><body>';
}

$secretsExist = file_exists($secretsFile);

// ------------------------------------------------------------------
// ケースA: local.php が存在しない → 新規設定（誰でも実行可）
// ------------------------------------------------------------------
if (!$secretsExist) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password']) && trim($_POST['new_password']) !== '') {

        // 書き込み直前にもう一度存在確認（多重設定の競合を最小化）。
        if (file_exists($secretsFile)) {
            echo ksphp_setup_html_head('Setup');
            echo '<p>既に別の設置者/利用者が設定を完了させたようです。'
                . 'このツールは無効化されました。管理者にご確認ください。</p>';
            echo '<p>Someone else has already completed the setup. This tool is no longer usable here.</p>';
            echo '</body></html>';
            exit();
        }

        $newPassword = (string) $_POST['new_password'];
        $newAdminKey = trim((string) ($_POST['new_adminkey'] ?? ''));
        $defaultName = ksphp_setup_default_name($SETUP_SEED);
        $chosenName = ksphp_setup_sanitize_filename((string) ($_POST['tool_filename'] ?? ''), $defaultName);

        $cryptedPass = password_hash($newPassword, PASSWORD_BCRYPT);

        if (!ksphp_setup_write_secrets($secretsFile, $cryptedPass, $newAdminKey)) {
            echo ksphp_setup_html_head('Setup - Error');
            echo '<p>local.php への書き込みに失敗しました。ディレクトリの書き込み権限を確認してください。</p>';
            echo '</body></html>';
            exit();
        }

        $selfPath = __FILE__;
        $newPath = __DIR__ . '/' . $chosenName;
        $renamed = ($selfPath !== $newPath) ? @rename($selfPath, $newPath) : true;

        echo ksphp_setup_html_head('Setup - Complete');
        echo '<p>設定が完了しました。管理パスワードが有効になりました。</p>';
        if ($renamed) {
            echo '<p>このツールは <code>' . htmlspecialchars($chosenName, ENT_QUOTES, 'UTF-8')
                . '</code> という名前に変更されました。次回パスワードを変更したい時は、'
                . 'このURLを控えてアクセスしてください（他人には教えないでください）。</p>';
        } else {
            echo '<p>ファイル名の変更には失敗しましたが、パスワード自体は設定済みです。'
                . '安全のため、手動でこのファイルの名前を分かりにくいものに変更することを推奨します。</p>';
        }
        echo '</body></html>';
        exit();
    }

    // 初回設定フォーム表示
    $defaultName = ksphp_setup_default_name($SETUP_SEED);
    echo ksphp_setup_html_head('Admin Setup');
    echo '<h1>管理パスワード 初期設定 / Initial Admin Setup</h1>';
    echo '<p>掲示板の管理パスワードがまだ設定されていません。以下のフォームから設定してください。'
        . '（設定完了後、このツール自身のファイル名は指定した名前に変更されます）</p>';
    echo '<form method="post">';
    echo '<p>管理パスワード / Admin password: <input type="password" name="new_password" required></p>';
    echo '<p>管理モード移行キーワード（任意）/ Admin-mode keyword (optional): '
        . '<input type="text" name="new_adminkey"></p>';
    echo '<p>このツールの今後のファイル名 / New filename for this tool: '
        . '<input type="text" name="tool_filename" value="' . htmlspecialchars($defaultName, ENT_QUOTES, 'UTF-8') . '"></p>';
    echo '<p><button type="submit">設定 / Set</button></p>';
    echo '</form>';
    echo '</body></html>';
    exit();
}

// ------------------------------------------------------------------
// ケースB: local.php が存在する → 変更には現行パスワードでの認証が必須
// ------------------------------------------------------------------
$secrets = require $secretsFile;
$currentAdminPost = $secrets['ADMINPOST'] ?? '';
$currentAdminKey = $secrets['ADMINKEY'] ?? '';

session_start();

// ログアウト
if (isset($_GET['logout'])) {
    unset($_SESSION['ksphp_setup_authed']);
    session_destroy();
}

// ログイン処理
if (!empty($_POST['current_password']) && empty($_SESSION['ksphp_setup_authed'])) {
    if ($currentAdminPost !== '' && crypt((string) $_POST['current_password'], $currentAdminPost) === $currentAdminPost) {
        $_SESSION['ksphp_setup_authed'] = true;
    } else {
        echo ksphp_setup_html_head('Setup - Login');
        echo '<p>パスワードが違います。/ Incorrect password.</p>';
        echo '<form method="post"><p><input type="password" name="current_password" required> '
            . '<button type="submit">Login</button></p></form>';
        echo '</body></html>';
        exit();
    }
}

if (empty($_SESSION['ksphp_setup_authed'])) {
    echo ksphp_setup_html_head('Setup - Login');
    echo '<h1>ログイン / Login</h1>';
    echo '<p>設定を変更するには、現在の管理パスワードでログインしてください。</p>';
    echo '<form method="post"><p>現在の管理パスワード / Current admin password: '
        . '<input type="password" name="current_password" required></p>'
        . '<p><button type="submit">Login</button></p></form>';
    echo '</body></html>';
    exit();
}

// ログイン済み: 変更フォームの送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password']) && trim($_POST['new_password']) !== '') {
    $newPassword = (string) $_POST['new_password'];
    $newAdminKey = trim((string) ($_POST['new_adminkey'] ?? ''));
    $cryptedPass = password_hash($newPassword, PASSWORD_BCRYPT);

    if (!ksphp_setup_write_secrets($secretsFile, $cryptedPass, $newAdminKey)) {
        echo ksphp_setup_html_head('Setup - Error');
        echo '<p>local.php への書き込みに失敗しました。</p>';
        echo '</body></html>';
        exit();
    }

    $renamedNote = '';
    if (!empty($_POST['tool_filename'])) {
        $defaultName = ksphp_setup_default_name($SETUP_SEED);
        $chosenName = ksphp_setup_sanitize_filename((string) $_POST['tool_filename'], $defaultName);
        $selfPath = __FILE__;
        $newPath = __DIR__ . '/' . $chosenName;
        if ($selfPath !== $newPath && @rename($selfPath, $newPath)) {
            $renamedNote = '<p>このツールは <code>' . htmlspecialchars($chosenName, ENT_QUOTES, 'UTF-8')
                . '</code> という名前に変更されました。今後はこのURLを使ってください。</p>';
        }
    }

    echo ksphp_setup_html_head('Setup - Updated');
    echo '<p>管理パスワードを更新しました。</p>';
    echo $renamedNote;
    echo '</body></html>';
    exit();
}

// ログイン済み: 変更フォーム表示
echo ksphp_setup_html_head('Admin Setup - Change');
echo '<h1>管理パスワード変更 / Change Admin Password</h1>';
echo '<form method="post">';
echo '<p>新しい管理パスワード / New admin password: <input type="password" name="new_password" required></p>';
echo '<p>管理モード移行キーワード / Admin-mode keyword: '
    . '<input type="text" name="new_adminkey" value="' . htmlspecialchars($currentAdminKey, ENT_QUOTES, 'UTF-8') . '"></p>';
echo '<p>このツールを改名する（任意。空欄なら現在の名前のまま）/ '
    . 'Rename this tool (optional, leave blank to keep current name): <input type="text" name="tool_filename"></p>';
echo '<p><button type="submit">更新 / Update</button></p>';
echo '</form>';
echo '<p><a href="?logout=1">ログアウト / Logout</a></p>';
echo '</body></html>';
