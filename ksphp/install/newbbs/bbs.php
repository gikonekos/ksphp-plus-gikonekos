<?php

// Start session for admin/mod login
session_start();

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo 'Error: PHP version is '.phpversion().'. This script is compatible with PHP 8.0 and above.';
    exit();
}
if (ini_get('register_globals') == 1) {
    #print 'Error: register_globals is turned on. Please turn it off in your PHP configuration for security reasons.';
    #exit();
}

/*
The instructions have been moved to readme.md.
*/

// Configuration file
require_once("./conf.php");

// 20260720 Gikoneko: Admin secrets (ADMINPOST/ADMINKEY) live outside
// conf.php, in a fixed-name file (local.php) that is not part of the
// newbbs/ distribution template and is therefore invisible to
// install.php's file scan (ksphp_install_list_files()). This keeps the
// admin password/keyword out of install.php's merge/backup/overwrite
// logic entirely. See _setup.php (initial name; the operator renames it
// after first use) for how local.php is created and updated.
//
// The existence of local.php is the ONLY source of truth for whether
// the admin password is configured. If local.php does not exist yet,
// ADMINPOST/ADMINKEY are treated as unset (even if conf.php happens to
// still carry an old hash forward from a pre-RC8 install via
// install.php's conf-merge) so that upgraders are routed through
// _setup.php and asked to set a brand-new password there, rather than
// silently continuing to rely on the old conf.php-based value forever
// (which would defeat the point of moving secrets out of conf.php).
// _setup.php itself handles the upgrade case specially: if conf.php
// still has a non-empty legacy ADMINPOST, it requires authenticating
// with that old password before a new one can be set.
$ksphp_local_secrets_file = __DIR__ . '/local.php';
if (file_exists($ksphp_local_secrets_file)) {
    $ksphp_local_secrets = require $ksphp_local_secrets_file;
    $CONF['ADMINPOST'] = $ksphp_local_secrets['ADMINPOST'] ?? '';
    $CONF['ADMINKEY']  = $ksphp_local_secrets['ADMINKEY']  ?? '';
} else {
    $CONF['ADMINPOST'] = '';
    $CONF['ADMINKEY']  = '';
}
unset($ksphp_local_secrets_file, $ksphp_local_secrets);

// Version (for copyright notice)
$CONF['VERSION'] = '擬古猫+RC19 [20260812] (Heyuri, ヶ, ＠Links, 擬古猫)';

// Internal build identifier (matches the distribution zip filename, minus the
// .zip extension: {name}(-rcN)?-{ISO date}-{NN}). $CONF['VERSION'] above is a
// display/branding version and does not change on every build; this constant
// is for precise build-to-build comparison (e.g. future differential-update
// tooling). Update this value whenever a new package zip is built.
define('KSPHP_PLUS_BUILD', 'ksphp-rc9-2026-08-01-01');

/* Launch */

// 2026-07-16：sub/ja・sub/enの重複を解消し、ロジック／テンプレートは
// sub/ 直下に一本化した。$SUBDIR分岐は廃止。
// 2026-07-16: Unified the previously-duplicated sub/ja and sub/en trees;
// logic and templates now live directly under sub/. The old $SUBDIR
// branching has been removed.
$SUBDIR = './sub/';

/**
 * language/*.txt を読み込み、KEY=値 の連想配列に変換する。
 * - 「#」または「;」で始まる行はコメント
 * - 空行は無視
 * - 「=」より前がキー（前後の空白は無視）、より後ろが値（そのまま、
 *   末尾の空白も含めて保持）
 * - 文字コードはUTF-8（BOM無し）前提
 *
 * Reads a language/*.txt file and converts it to a KEY => value array.
 * - Lines starting with "#" or ";" are comments
 * - Blank lines are ignored
 * - Everything before the first "=" is the key (surrounding whitespace
 *   trimmed); everything after is the value, taken as-is (including any
 *   trailing whitespace)
 * - Assumes UTF-8 encoding without a BOM
 *
 * @param   String  $path  言語ファイルのパス / path to the language file
 * @return  Array   キー => 値 / key => value
 */
function loadLanguageFile($path) {
    $result = array();
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $result;
    }
    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '' || $trimmed[0] === '#' || $trimmed[0] === ';') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $result[$key] = substr($line, $pos + 1);
    }
    return $result;
}

// UI文言（$MSG）の読み込み。language/{LANGUAGE_FILE}.txtを読む。
// 2026-07-16：sub/{ja,en}/lang.phpへのフォールバックは、当該サブ
// フォルダ自体を廃止したため削除した。LANGUAGE_FILE未設定時は
// 'english'を既定値として扱う。
// 2026-07-20：独立Cookie 'ksphp_lang' によるユーザー個人の言語切替
// に対応。Cookie値があり、対応するlanguage/*.txtが存在すればそちらを
// 優先する。Cookie未設定またはファイルが無ければconf.phpのLANGUAGE_FILE
// にフォールバック（サイト管理者のデフォルト設定を尊重）。
//
// Loads the UI strings ($MSG) from language/{LANGUAGE_FILE}.txt.
// 2026-07-16: Removed the fallback to sub/{ja,en}/lang.php, since those
// subfolders no longer exist. Defaults to 'english' if LANGUAGE_FILE is
// not set.
// 2026-07-20: Added per-user language switching via the 'ksphp_lang'
// cookie. If the cookie is set and a matching language/*.txt exists,
// that language is used; otherwise falls back to LANGUAGE_FILE in
// conf.php (respecting the site admin's default).
$language_file_name = $CONF['LANGUAGE_FILE'] ?? 'english';
if (isset($_COOKIE['ksphp_lang'])) {
    // Cookieから取得した値は安全にサニタイズ（パストラバーサル防止：
    // ファイル名として有効な文字のみ許可、スラッシュ・ドット・バックスラッシュ
    // は除去してからファイル存在チェック）。
    $cookie_lang = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_COOKIE['ksphp_lang']);
    if ($cookie_lang !== '' && file_exists('./language/' . $cookie_lang . '.txt')) {
        $language_file_name = $cookie_lang;
    }
}
$langfile = './language/' . $language_file_name . '.txt';
if (file_exists($langfile)) {
    $MSG = loadLanguageFile($langfile);
} else {
    die("Language file not found: $langfile");
}

// 2026-08-12：<html lang> と <html dir> を選択中の言語に追従させる。
// 従来 META_LANGUAGE は conf.php の静的値（'ja'）だったため、言語を
// 切り替えてもlang属性が'ja'のまま変わらなかった（既存の不具合）。
// ここで選択中の言語ファイル名からBCP47言語タグと書字方向を解決し、
// $CONFを上書きする。$CONFは後段で array_merge されて全テンプレートに
// 渡るため、これだけで {META_LANGUAGE} / {META_DIRECTION} に反映される。
// 未登録の言語ファイル名はconf.phpの値とltrにフォールバックする。
//
// 2026-08-12: Make <html lang> and <html dir> follow the selected
// language. META_LANGUAGE used to be a static conf.php value, so the
// lang attribute never changed when the visitor switched languages.
// Resolving it here and overwriting $CONF is enough, because $CONF is
// array_merge()d into the global template vars further down.
$ksphp_lang_tags = array(
    'japanese'   => 'ja',
    'english'    => 'en',
    'korean'     => 'ko',
    'portuguese' => 'pt',
    'turkish'    => 'tr',
    'zh-hans'    => 'zh-Hans',
    'zh-hant'    => 'zh-Hant',
    // 追加予定分（language/*.txt を置けばそのまま有効になる）
    // Planned additions -- effective as soon as the .txt file is added.
    'spanish'    => 'es',
    'hindi'      => 'hi',
    'french'     => 'fr',
    'indonesian' => 'id',
    'vietnamese' => 'vi',
    'tagalog'    => 'tl',
    'german'     => 'de',
    'arabic'     => 'ar',
);
// 右横書き（RTL）の言語。ここに列挙した言語では <html dir="rtl"> となり、
// CSSの論理プロパティ（margin-inline-start 等）が自動的に反転する。
// RTL languages: listed here to emit <html dir="rtl">, which flips all
// the CSS logical properties automatically.
$ksphp_rtl_langs = array('arabic');

$CONF['META_LANGUAGE'] = $ksphp_lang_tags[$language_file_name]
    ?? ($CONF['META_LANGUAGE'] ?? 'en');
$CONF['META_DIRECTION'] = in_array($language_file_name, $ksphp_rtl_langs, true)
    ? 'rtl'
    : 'ltr';

// 2026-08-12：「戻る」「次へ」を示す矢印。←(U+2190)・→(U+2192)は
// Unicodeの双方向アルゴリズムでは自動反転しない（Bidi_Mirrored=No）
// ため、書字方向に応じて明示的に入れ替える必要がある。
// テンプレート側は {ARROW_BACK} / {ARROW_FORWARD} を使うこと。
// 2026-08-12: Arrows meaning "back" and "next". U+2190/U+2192 are not
// Bidi_Mirrored, so they do NOT flip automatically under dir="rtl" and
// must be swapped explicitly. Templates use {ARROW_BACK}/{ARROW_FORWARD}.
if ($CONF['META_DIRECTION'] === 'rtl') {
    $CONF['ARROW_BACK']    = '→';
    $CONF['ARROW_FORWARD'] = '←';
} else {
    $CONF['ARROW_BACK']    = '←';
    $CONF['ARROW_FORWARD'] = '→';
}

// Load the board's default language separately so that strings written
// to the log (Reference line, self-reply tag) are always stored in the
// configured default language, not in the visitor's selected language.
// 掲示板のデフォルト言語（conf.phpのLANGUAGE_FILE）を別途読み込み、
// ログに書き込まれる文字列（参考行・自己レスタグ）を常にデフォルト
// 言語で保存するために使用する。
$default_langfile = './language/' . ($CONF['LANGUAGE_FILE'] ?? 'english') . '.txt';
if (file_exists($default_langfile)) {
    $MSG_DEFAULT = loadLanguageFile($default_langfile);
} else {
    $MSG_DEFAULT = $MSG;  // fallback to current language
}

// 2026-07-20：language/ディレクトリを動的スキャンし、利用可能な言語
// ファイル一覧を取得する。プルダウンのoption HTML生成もここで行い、
// テンプレート変数 LANG_OPTIONS_HTML として全テンプレートに渡す。
// 表示ラベルは各言語ファイル自体には持たせず（KEY=値形式に「自分の
// 言語名」を入れると循環するため）、ここにマッピングを持つ。
// 新規言語を追加した場合はこの配列にも追加する。未登録の言語名は
// ファイル名そのままを表示する。
$ksphp_lang_labels = array(
    'japanese'   => '日本語',
    'english'    => 'English',
    'portuguese' => 'Português',
    'turkish'    => 'Türkçe',
    'zh-hant'    => '繁體中文',
    'zh-hans'    => '简体中文',
    'korean'     => '한국어',
    // 2026-08-12：追加予定言語の表示名を先行登録。language/*.txt を
    // 置いた時点でプルダウンに正しい言語名で現れる（未登録だと
    // ファイル名がそのまま出てしまうため）。
    // Display names for planned languages, registered ahead of time so
    // that dropping in language/*.txt is all that's needed.
    'spanish'    => 'Español',
    'french'     => 'Français',
    'german'     => 'Deutsch',
    'indonesian' => 'Bahasa Indonesia',
    'vietnamese' => 'Tiếng Việt',
    'tagalog'    => 'Tagalog',
    'hindi'      => 'हिन्दी',
    'arabic'     => 'العربية',
);
$ksphp_lang_files = glob('./language/*.txt');
$ksphp_lang_options_html = '';
if ($ksphp_lang_files !== false) {
    sort($ksphp_lang_files);
    foreach ($ksphp_lang_files as $lf) {
        $name = basename($lf, '.txt');
        $label = htmlspecialchars($ksphp_lang_labels[$name] ?? $name, ENT_QUOTES, 'UTF-8');
        $sel = ($name === $language_file_name) ? ' selected' : '';
        $ksphp_lang_options_html .= '<option value="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . $label . '</option>';
    }
}
// 2026-07-17：JS側（imgthumb.js等）でも$MSGの文言を参照できるよう、
// JSONにしておく。window.KSPHP_LANGとしてHTMLヘッダーに埋め込む。
// 2026-07-17: Pre-encode $MSG as JSON so JavaScript files (imgthumb.js
// etc.) can reference the same translations, exposed as
// window.KSPHP_LANG in the HTML header.
$MSG_JSON = json_encode($MSG, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

// 2026-08-01 Gikoneko: 「個人環境設定」内JS設定セクション。
// 対象JS機能を1箇所で定義（拡張性重視：新規JS機能は配列に1行足すだけで
// 保存・表示・JSへの受け渡しすべてに反映される）。
// type: 'bool'（チェックボックス）または 'int'（数値パラメータ）。
// 'int'型のみ min/max を持ち、範囲外はデフォルトにフォールバックする。
function ksphp_js_setting_defs(): array {
    // ラインブレーカーの1行目標文字数は、投稿バリデーション側の上限
    // conf.php['MAXMSGCOL']（1行の最大バイト数、strlen()基準）を超えて
    // 投稿するとエラーになるため、これを超えられないようにする。
    // ブレーカー側は「文字数」で行を区切る一方MAXMSGCOLは「バイト数」の
    // 上限であり、日本語（UTF-8で1文字最大3バイト）が最も不利なので、
    // 設定可能な上限は MAXMSGCOL/3 を基準にする（日本語だけの行でも
    // サーバー側の検証を通せる値に必ず収まる）。
    $maxmsgcol = isset($GLOBALS['CONF']['MAXMSGCOL']) && is_numeric($GLOBALS['CONF']['MAXMSGCOL'])
        ? (int) $GLOBALS['CONF']['MAXMSGCOL']
        : 1000;
    $linebreaker_max = max(10, (int) floor($maxmsgcol / 3) - 2);
    // 既定値は従来のハードコード値（72文字）を踏襲する。MAXMSGCOLが
    // 極端に小さい設置ではそちらに合わせて縮める。
    $linebreaker_default = min(72, $linebreaker_max);

    // JS_DEFAULT_* conf.php キー（3値）からbool設定のデフォルト値と
    // ロック状態を決めるヘルパー。
    //   0 : 完全無効（個人設定に非表示・機能ロック。cookieも無視）
    //   1 : デフォルトON（ユーザーが個人設定で切替可）
    //   2 : デフォルトOFF（ユーザーが個人設定で切替可）
    // キー未設定・非数値の場合は従来のハードコードデフォルト（ロックなし）。
    $js_bool = function(string $conf_key, int $hardcoded_default): array {
        if (!isset($GLOBALS['CONF'][$conf_key]) || !is_numeric($GLOBALS['CONF'][$conf_key])) {
            return array('default' => $hardcoded_default, 'locked' => false);
        }
        $n = (int) $GLOBALS['CONF'][$conf_key];
        if ($n === 0) {
            return array('default' => 0, 'locked' => true);
        }
        if ($n === 2) {
            return array('default' => 0, 'locked' => false);
        }
        return array('default' => 1, 'locked' => false);
    };
    // int設定のデフォルト値。conf値があれば min..max に clamp して使う。
    $js_int = function(string $conf_key, int $hardcoded_default, int $min, int $max): int {
        if (!isset($GLOBALS['CONF'][$conf_key]) || !is_numeric($GLOBALS['CONF'][$conf_key])) {
            return $hardcoded_default;
        }
        $n = (int) $GLOBALS['CONF'][$conf_key];
        if ($n < $min) { $n = $min; }
        if ($n > $max) { $n = $max; }
        return $n;
    };

    $defs = array(
        'giko'            => array_merge(array('type' => 'bool', 'conf_key' => 'JS_DEFAULT_GIKO'),     $js_bool('JS_DEFAULT_GIKO',     1)),
        'imgthumb'        => array_merge(array('type' => 'bool', 'conf_key' => 'JS_DEFAULT_IMGTHUMB'), $js_bool('JS_DEFAULT_IMGTHUMB', 1)),
        'kaomoji'         => array_merge(array('type' => 'bool', 'conf_key' => 'JS_DEFAULT_KAOMOJI'),  $js_bool('JS_DEFAULT_KAOMOJI',  1)),
        'latex'           => array_merge(array('type' => 'bool', 'conf_key' => 'JS_DEFAULT_LATEX'),    $js_bool('JS_DEFAULT_LATEX',    0)),
        'linebreaker_len' => array('type' => 'int', 'conf_key' => 'JS_DEFAULT_LINEBREAKER_LEN', 'default' => $js_int('JS_DEFAULT_LINEBREAKER_LEN', $linebreaker_default, 10, $linebreaker_max), 'min' => 10, 'max' => $linebreaker_max),
        'longpost'        => array_merge(array('type' => 'bool', 'conf_key' => 'JS_DEFAULT_LONGPOST'), $js_bool('JS_DEFAULT_LONGPOST', 0)),
        'longpost_th'     => array('type' => 'int', 'conf_key' => 'JS_DEFAULT_LONGPOST_TH', 'default' => $js_int('JS_DEFAULT_LONGPOST_TH', 10, 1, 9999), 'min' => 1, 'max' => 9999),
        'treehide'        => array_merge(array('type' => 'bool', 'conf_key' => 'JS_DEFAULT_TREEHIDE'), $js_bool('JS_DEFAULT_TREEHIDE', 0)),
        'upthumb'         => array_merge(array('type' => 'bool', 'conf_key' => 'JS_DEFAULT_UPTHUMB'),  $js_bool('JS_DEFAULT_UPTHUMB',  1)),
        'vidembed'        => array_merge(array('type' => 'bool', 'conf_key' => 'JS_DEFAULT_VIDEMBED'), $js_bool('JS_DEFAULT_VIDEMBED', 1)),
    );
    // GIKONEKO_TOISSHO（サーバー側マスター）が0のときは giko も
    // ロック扱いに畳み込む（個人設定非表示・保存も0・JSON上も0）。
    if (empty($GLOBALS['CONF']['GIKONEKO_TOISSHO'])) {
        $defs['giko']['default'] = 0;
        $defs['giko']['locked'] = true;
    }
    return $defs;
}

// cookie 'ksphp_js'（JSON文字列）から現在値を読み込む。壊れている／
// 未設定のキーはすべて定義済みデフォルト値にフォールバックする。
function ksphp_js_settings_load(): array {
    $defs = ksphp_js_setting_defs();
    $result = array();
    $raw = isset($_COOKIE['ksphp_js']) ? $_COOKIE['ksphp_js'] : '';
    $decoded = array();
    if ($raw !== '') {
        $tmp = json_decode($raw, true);
        if (is_array($tmp)) {
            $decoded = $tmp;
        }
    }
    foreach ($defs as $key => $def) {
        $val = $decoded[$key] ?? null;
        if ($def['type'] === 'bool') {
            // ロック済み（conf.phpで0＝完全無効、またはgikoで
            // GIKONEKO_TOISSHO=0）のキーはcookieを無視して常に 0。
            if (!empty($def['locked'])) {
                $result[$key] = 0;
            } else {
                $result[$key] = ($val === null) ? $def['default']
                    : (($val === 1 || $val === '1' || $val === true) ? 1 : 0);
            }
        } else { // int
            $n = is_numeric($val) ? (int) $val : $def['default'];
            if ($n < $def['min'] || $n > $def['max']) {
                $n = $def['default'];
            }
            $result[$key] = $n;
        }
    }
    return $result;
}

$ksphp_js_settings = ksphp_js_settings_load();
$KSPHP_JS_SETTINGS_JSON = json_encode($ksphp_js_settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
// 管理者ロック済み（conf.phpのJS_DEFAULT_*=0）のboolキー一覧。JS側で
// RC10-12互換のlegacy localStorageフォールバックより優先して参照させ、
// ロックがlocalStorage残存値に貫通されないようにする。
$ksphp_js_locked = array();
foreach (ksphp_js_setting_defs() as $ksphp_lk => $ksphp_ld) {
    if ($ksphp_ld['type'] === 'bool' && !empty($ksphp_ld['locked'])) {
        $ksphp_js_locked[] = $ksphp_lk;
    }
}
$KSPHP_JS_LOCKED_JSON = json_encode($ksphp_js_locked, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

// Translation helper
function T($key) {
    return $GLOBALS['MSG'][$key] ?? $key;
}

// Default-language translation helper: always returns strings from the
// board's configured default language (LANGUAGE_FILE in conf.php).
// Use this for strings written to the log file so that they are stored
// in a consistent language regardless of the visitor's language setting.
// ログファイルに書き込まれる文字列用。訪問者の言語設定によらず、
// conf.phpのLANGUAGE_FILEで指定したデフォルト言語で返す。
function TDefault($key) {
    return $GLOBALS['MSG_DEFAULT'][$key] ?? $key;
}

// 表示専用：ログにデフォルト言語で焼き込まれた参考リンクのテキスト
// （REFERENCE_COLON・曜日名）を、閲覧者の選択言語へ差し替える。ログ
// 本体は書き換えず、表示時のみ置換する。参考リンク（m=f）という限定
// された場所だけを対象にするため、本文中の同一文字への誤爆を避けられる。
// Display-only: rewrites the reference-link text (REFERENCE_COLON and
// day-of-week name), which is baked into the log in the board's default
// language, into the visitor's selected language. The log itself is left
// untouched; only the on-screen output is translated. Scoped to the
// reference link (m=f) so it never touches identical characters in body text.
function ksphp_translate_reflink_text($text) {
    $default = $GLOBALS['MSG_DEFAULT'] ?? array();
    $current = $GLOBALS['MSG'] ?? array();
    // REFERENCE_COLON（参考：）を差し替え
    if (isset($default['REFERENCE_COLON'], $current['REFERENCE_COLON'])
        && $default['REFERENCE_COLON'] !== '' ) {
        $text = str_replace($default['REFERENCE_COLON'], $current['REFERENCE_COLON'], $text);
    }
    // 曜日名（(金) → (Fri) 等）を差し替える。DATEFORMATは管理者が自由に
    // 設定できるため「(金)」の括弧形とは限らない。日付らしい並び（数字・
    // 区切り記号・空白・括弧）に挟まれた曜日名のみを対象にすることで、
    // 括弧の有無に依存せず、かつ通常の語（金曜日・金額など）への誤爆も防ぐ。
    // The day-of-week name is not necessarily wrapped in parentheses, since
    // DATEFORMAT is admin-configurable. Match only names surrounded by
    // date-like context so this works regardless of format and never hits
    // ordinary words that happen to contain the same character.
    $wdaykeys = array('SUNDAY','MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY');
    $wmap = array();
    $walts = array();
    foreach ($wdaykeys as $wk) {
        if (isset($default[$wk], $current[$wk]) && $default[$wk] !== ''
            && $default[$wk] !== $current[$wk]) {
            $wmap[$default[$wk]] = $current[$wk];
            $walts[] = preg_quote($default[$wk], '/');
        }
    }
    if ($wmap) {
        $alt = implode('|', $walts);
        $replaced = preg_replace_callback(
            '/(?<=[\d\/\-\.\s\(])(' . $alt . ')(?=[\)\s\d:]|$)/u',
            function ($m) use ($wmap) { return $wmap[$m[1]] ?? $m[1]; },
            $text
        );
        // 不正なUTF-8等でpreg_replace_callbackがnullを返した場合は原文を保つ
        if ($replaced !== null) {
            $text = $replaced;
        }
    }
    return $text;
}

// 表示専用：ログにデフォルト言語で焼き込まれた自己レスタグ
// （SELF_REPLY_TAG）を、閲覧者の選択言語へ差し替える。$message['USER']
// 内の <span class="muh">（自己レス）</span> を対象にする。muhクラスは
// 管理者名等でも使われるため、デフォルト言語の自己レス文字列そのものを
// 含む場合のみ置換し、他用途への誤爆を避ける。
// Display-only: rewrites the self-reply tag baked into the log in the
// board's default language into the visitor's selected language.
function ksphp_translate_selfreply_tag($user_html) {
    $default = $GLOBALS['MSG_DEFAULT'] ?? array();
    $current = $GLOBALS['MSG'] ?? array();
    if (isset($default['SELF_REPLY_TAG'], $current['SELF_REPLY_TAG'])
        && $default['SELF_REPLY_TAG'] !== ''
        && $default['SELF_REPLY_TAG'] !== $current['SELF_REPLY_TAG']) {
        $user_html = str_replace(
            '<span class="muh">' . $default['SELF_REPLY_TAG'] . '</span>',
            '<span class="muh">' . $current['SELF_REPLY_TAG'] . '</span>',
            $user_html
        );
    }
    return $user_html;
}

// 参考リンク（<a ...>参考： 日付</a>）を本文から除去する正規表現パターンを
// 返す。参考行はログにデフォルト言語（TDefault）で焼き込まれているため、
// デフォルト言語のREFERENCE_COLONでマッチさせる。過去ログ互換のため
// 英語("Reference: ")もマッチ対象に含める。区切り文字はスラッシュ前提。
// Returns a regex alternation matching the reference colon in both the
// board's default language and English (for legacy logs). Delimiter is '/'.
function ksphp_reflink_colon_pattern() {
    // ログ全件ループ内から呼ばれるため、結果を静的にキャッシュする。
    // Cached: called from inside per-message loops.
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $ref_default = $GLOBALS['MSG_DEFAULT']['REFERENCE_COLON'] ?? 'Reference:';
    $patterns = array();
    $patterns[] = preg_quote($ref_default, '/');
    if ($ref_default !== 'Reference:') {
        $patterns[] = preg_quote('Reference:', '/');
    }
    $cached = '(?:' . implode('|', $patterns) . ')';
    return $cached;
}

// Set error output level
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Demote "Undefined array key" warnings to notice
// https://github.com/php/php-src/issues/8906#issuecomment-1172810362
set_error_handler(function($errno, $error){
    if (!str_starts_with($error, 'Undefined array key')){
        return false;  //default error handler.
    }else{
        trigger_error($error, E_USER_NOTICE);
        return true;
    }
}, E_WARNING);

if ($CONF['RUNMODE'] == 2) {
    print T('BBS_OUT_OF_SERVICE');
    exit();
}
/* Process to prohibit access by host name */
if (Func::hostname_match($CONF['HOSTNAME_BANNED'],$CONF['HOSTAGENT_BANNED'])) {
    print T('ACCESS_PROHIBITED');
    exit();
}

// Override template file paths according to language
$CONF['TEMPLATE']          = $SUBDIR . 'template.html';
$CONF['TEMPLATE_ADMIN']    = $SUBDIR . 'tmpladmin.html';
$CONF['TEMPLATE_LOG']      = $SUBDIR . 'tmpllog.html';
$CONF['TEMPLATE_TREEVIEW'] = $SUBDIR . 'tmpltree.html';
$CONF['TEMPLATE_LOGIN']    = $SUBDIR . 'login.html';

// ----------------------------------------------------------------------
// Include file paths
// ----------------------------------------------------------------------

/**
 * Message log search module
 * @const PHP_GETLOG
 */
define('PHP_GETLOG', $SUBDIR . 'bbslog.php');

/**
 * Admin module
 * @const PHP_BBSADMIN
 */
define('PHP_BBSADMIN', $SUBDIR . 'bbsadmin.php');

/**
 * Tree view module
 * @const PHP_TREEVIEW
 */
define('PHP_TREEVIEW', $SUBDIR . 'bbstree.php');

/**
 * BBS with image upload function module
 * @const PHP_IMAGEBBS
 */
define('PHP_IMAGEBBS', $SUBDIR . 'bbsimage.php');

/**
 * HTML template library
 * (not language-dependent)
 * @const LIB_TEMPLATE
 */
define('LIB_TEMPLATE', './sub/patTemplate.php');

/**
 * ZIP file creation library
 * (not language-dependent)
 * @const LIB_PHPZIP
 */
define('LIB_PHPZIP', './sub/phpzip.inc.php');

/**
 * Constant for file include detection
 * @const INCLUDED_FROM_BBS
 */
define('INCLUDED_FROM_BBS', TRUE);

/**
 * Constant for current time
 * @const CURRENT_TIME
 */
define('CURRENT_TIME', time() - $CONF['DIFFTIME'] * 60 * 60 + $CONF['DIFFSEC']);

/* Execute */
{
    require_once(LIB_TEMPLATE);
    script_run();
}

/**
 * Script execution main process
 *
 * Basically, this is where the module branches are described
 */

function script_run() {

    $CONF = &$GLOBALS['CONF'];
    # 20260720 Gikoneko: Admin password is no longer configured through this
    # in-board flow (formerly Bbsadmin::prtsetpass()/prtpass()). It is now
    # set up via a standalone tool (initially named _setup.php, renamed by
    # the operator on first run) that writes ADMINPOST/ADMINKEY directly to
    # local.php, outside of conf.php and outside install.php's view.
    if ($CONF['ADMINPOST'] == '') {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8">'
            . '<title>' . htmlspecialchars($CONF['BBSTITLE'], ENT_QUOTES, 'UTF-8') . '</title></head><body>'
            . '<p>管理パスワードが未設定です。'
            . '設置時に配布された初期設定ツール（初期名: _setup.php。'
            . '設置者が既に別名へ変更している場合はその名前）にアクセスして、'
            . '管理パスワードを設定してください。</p>'
            . '<p>Admin password is not set yet. Please access the setup tool '
            . 'included at install time (initially named _setup.php; if you '
            . 'already renamed it, use that name instead) to set the admin '
            . 'password.</p>'
            . '</body></html>';
        exit();
    }

    # Admin/mod login page (GET: ?m=login)
    elseif (@$_GET['m'] == 'login') {
        if (isModerator()) {
            header('Location: ' . $CONF['CGIURL'] . '?m=ad');
            exit();
        }
        require_once(LIB_TEMPLATE);
        $t = new patTemplate();
        // Load both main template and login template for subtemplate support
        $t->readTemplatesFromFile($CONF['TEMPLATE']);
        $t->readTemplatesFromFile($CONF['TEMPLATE_LOGIN']);

        $templateValues = removeArrayValues(array_merge($CONF, $GLOBALS['MSG']));
        $templateValues['JS_LANG_JSON'] = $GLOBALS['MSG_JSON'];
        $templateValues['JS_SETTINGS_JSON'] = $GLOBALS['KSPHP_JS_SETTINGS_JSON'];
        $templateValues['JS_LOCKED_JSON'] = $GLOBALS['KSPHP_JS_LOCKED_JSON'];
        $templateValues['LANG_OPTIONS_HTML'] = $GLOBALS['ksphp_lang_options_html'];

        $t->addGlobalVars($templateValues);
        $t->displayParsedTemplate('login');
        exit();
    }
    # Admin/mod login POST
    elseif (@$_POST['m'] == 'login') {
        $pass = trim(@$_POST['adminpass'] ?? '');
        $is_mod = false;
        if (crypt($pass, $CONF['ADMINPOST']) === $CONF['ADMINPOST']) {
            $is_mod = true;
        }
        if ($is_mod) {
            $_SESSION['is_mod'] = true;
            header('Location: ' . $CONF['CGIURL'] . '?m=ad');
            exit();
        } else {
            require_once(LIB_TEMPLATE);
            $t = new patTemplate();
            $t->readTemplatesFromFile($CONF['TEMPLATE']);
            $t->readTemplatesFromFile($CONF['TEMPLATE_LOGIN']);

            // environment vars for template
            $templateValues = removeArrayValues(array_merge($CONF, $GLOBALS['MSG']));
            $templateValues['JS_LANG_JSON'] = $GLOBALS['MSG_JSON'];
            $templateValues['JS_SETTINGS_JSON'] = $GLOBALS['KSPHP_JS_SETTINGS_JSON'];
            $templateValues['JS_LOCKED_JSON'] = $GLOBALS['KSPHP_JS_LOCKED_JSON'];
            $templateValues['LANG_OPTIONS_HTML'] = $GLOBALS['ksphp_lang_options_html'];

            $t->addGlobalVars($templateValues);
            $t->addVar('login', 'LOGIN_ERROR', T('LOGIN_ERROR'));
            $t->setAttribute('login_error', 'visibility', 'visible');
            $t->displayParsedTemplate('login');
            exit();
        }
    }
    # Admin mode (GET: ?m=ad) - session required
    elseif (@$_REQUEST['m'] == 'ad' && isModerator()) {
        require_once(PHP_BBSADMIN);
        $bbsadmin = new Bbsadmin();
        $bbsadmin->main();
    }
    # Message log search mode (sub/bbslog.php)
    elseif (@$_GET['m'] == 'g' or @$_POST['m'] == 'g') {
        require_once(PHP_GETLOG);
        $getlog = new Getlog();
        $getlog->main();
    }
    # Legacy admin POST (disable: always redirect to login)
    elseif (@$_POST['m'] == 'ad') {
        header('Location: ' . $CONF['CGIURL'] . '?m=login');
        exit();
    }
    # Tree view (sub/bbstree.php)
    elseif (@$_GET['m'] == 'tree' or @$_POST['m'] == 'tree') {
        require_once(PHP_TREEVIEW);
        $treeview = new Treeview();
        $treeview->main();
    }
    # Image bulletin board (sub/bbsimage.php)
    elseif ($CONF['BBSMODE_IMAGE'] == 1) {
        require_once(PHP_IMAGEBBS);
        $imagebbs = new Imagebbs();
        $imagebbs->main();
    }
    # Bulletin board mode (bbs.php)
    else {
        $bbs = new Bbs();
        $bbs->main();
    }
    exit();

}

/**
 * Detects if any honeypot fields are filled (for spam prevention)
 * @param array $fields Optional array of field values (defaults to $_POST)
 * @return bool True if any honeypot field is filled, false otherwise
 */
function detectHoneyPot(?array $fields = null): bool {
    // If no fields are provided, use $_POST by default
    if ($fields === null) {
        $fields = $_POST;
    }

    // List of honeypot field names to check
    $honeypots = [
        'e-mail',
        'username',
        'subject',
        'comment',
        'firstname',
        'lastname',
        'city',
        'state',
        'zipcode'
    ];
    
    // Check if any of the honeypot fields are filled
    foreach ($honeypots as $name) {
        if (!empty($fields[$name])) {
            return true;
        }
    }

    // No honeypot fields are filled
    return false;
}

/**
 * Removes array values from an array, leaving only scalar values.
 *
 * @param array $array The input array
 * @return array The array with only scalar values
 */
function removeArrayValues(array $array): array {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            unset($array[$key]);
        }
    }
    return $array;
}

/**
 * Validates if the given string is a valid email address.
 *
 * @param string $email The email address to validate
 * @return bool True if the email is valid, false otherwise
 */
function isModerator(): bool {
    return isset($_SESSION['is_mod']) && $_SESSION['is_mod'];
}

/**
 * Base web application class - Webapp
 *
 * Super class for each mode. Describes the processing common to each module.
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Webapp {

    var $c; /* Settings information */
    var $f; /* Form input */
    var $s = array(); /* Session-specific information such as the user's host */
    var $t; /* HTML template object */

    /**
     * Constructor
     *
     */
    function __construct() {
        $this->c = &$GLOBALS['CONF'];
        $this->t = new patTemplate();
        $this->t->readTemplatesFromFile($this->c['TEMPLATE']);
    }

    /**
     * Destructor
     */
    function destroy() {
    }

    /*20210625 Neko/2chtrip http://www.mits-jp.com/2ch/ */

function tripuse($key) {
    #$tripkey = '#istrip';? // String to be used as password (with #)
            // iconv（mbstring不要）でUTF-8→CP932変換。
            // CP932はSJIS-winと一部文字（波ダッシュ等）の扱いが異なるため、
            // 他サイトのトリップ計算機と結果が完全一致しない可能性がある。
            // Convert UTF-8 to CP932 via iconv (no mbstring required).
            // CP932 and SJIS-win differ in how they handle some characters
            // (e.g. the wave dash), so the result may not exactly match
            // other sites' trip calculators.
            $converted = @iconv('UTF-8', 'CP932//IGNORE', $key);
            $key = ($converted !== false) ? $converted : $key;
    #		$key = '#'.substr($key, strpos($key, '#'));
    
    # Trip
    # $trip is used for 0thello
    $trip = '';
    if (preg_match("/([^\#]*)\#(.+)/", $key, $match)) {
        if (strlen($match[2]) >= 12){
        # New conversion method
            $mark = substr($match[2], 0, 1);
            if ($mark == '#' || $mark == '$'){
                if (preg_match('|^#([[:xdigit:]]{16})([./0-9A-Za-z]{0,2})$|',$match[2],$str)){
                    $trip = substr(crypt(pack('H*', $str[1]), "$str[2].."), -10);
                } else {
                # For future expansion
                    $trip = '???';
                }
            } else {
    //		$trip = substr(base64_encode(pack('H*', sha1($match[2]))), 0, 12);
            $trip = substr(base64_encode(sha1($match[2],TRUE)),0,12);
            $trip = str_replace('+','.',$trip);
            }
        } else {
            $salt = substr($match[2]."H.", 1, 2);
            $salt = preg_replace("/[^\.-z]/", ".", $salt);
            $salt = strtr($salt,":;<=>?@[\\]^_`","ABCDEFGabcdef");
            $trip = substr(crypt($match[2], $salt),-10);
        }
    #	$match[1] = str_replace("◆", "◇", $match[1]);
    #	@$_POST['FROM'] = $match[1]."</b> ◆".$trip."<b>";
    $trip ="◆".$trip;
    } else {
        $trip = str_replace("◆", "◇", $key);
    }
    return $trip;
    }
    


    /**
     * Form acquisition preprocessing
     */
    function procForm() {
        if (!$this->c['BBSMODE_IMAGE'] and @$_SERVER['CONTENT_LENGTH'] > $this->c['MAXMSGSIZE'] * 5) {
            $this->prterror(T('POST_TOO_LARGE'));
        }
        if ($this->c['BBSHOST'] and @$_SERVER['HTTP_HOST'] != $this->c['BBSHOST']) {
            $this->prterror(T('INVALID_CALLER'));
        }
        # Limited to POST or GET only
        if (@$_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->f = $_POST;
        }
        else {
            $this->f = $_GET;
        }
        # String replacement
        foreach ($this->f as $name => $value) {
            if (is_array($value)) {
                foreach (array_keys($value) as $valuekey) {
                    $value[$valuekey] = Func::html_escape($value[$valuekey]);
                }
            }
            else {
                $value = Func::html_escape($value);
            }
            $this->f[$name] = $value;
        }
    }

    /**
     * Session-specific information settings
     */
    function setusersession() {

        $this->s['U'] = @$this->f['u'];
        $this->s['I'] = @$this->f['i'];
        $this->s['C'] = @$this->f['c'];
        $this->s['MSGDISP'] = @$this->f['d'];
        $this->s['TOPPOSTID'] = @$this->f['p'];
        # Get settings information cookies
        if ($this->c['COOKIE'] and @$_COOKIE['c']
            and preg_match("/u=([^&]*)&i=([^&]*)&c=([^&]*)/", @$_COOKIE['c'], $matches)) {
            if (!isset($this->f['u'])) {
                $this->s['U'] = urldecode($matches[1]);
            }
            if (!isset($this->f['i'])) {
                $this->s['I'] = urldecode($matches[2]);
            }
            if (!isset($this->f['c'])) {
                $this->s['C'] = $matches[3];
            }
        }
        # Get cookie for the UNDO button
        if ($this->c['COOKIE'] and $this->c['ALLOW_UNDO'] and @$_COOKIE['undo']
            and preg_match("/p=([^&]*)&k=([^&]*)/", @$_COOKIE['undo'], $matches)) {
            $this->s['UNDO_P'] = $matches[1];
            $this->s['UNDO_K'] = $matches[2];
        }
        # Default query
        $this->s['QUERY'] = "c=".$this->s['C'];
        if ($this->s['MSGDISP']) {
            $this->s['QUERY'] .= "&amp;d=".$this->s['MSGDISP'];
        }
        if ($this->s['TOPPOSTID']) {
            $this->s['QUERY'] .= "&amp;p=".$this->s['TOPPOSTID'];
        }
        # Default URL
        $this->s['DEFURL'] = $this->c['CGIURL'] . '?' . $this->s['QUERY'];
        # Initialize template variables
        # 2026-07-17：$MSG（言語ファイル）も統合し、テンプレート側で{KEY}として
        # 使えるようにする（テンプレート内の固定文言のハードコード対策）。
        # 2026-07-17: Also merge in $MSG (the language file) so templates can
        # reference any $MSG key as {KEY} (fixes hardcoded strings in
        # templates that previously bypassed the language file).
        $tmp = array_merge($this->c, $this->s, $GLOBALS['MSG']);

        $tmp = removeArrayValues($tmp);

        # 2026-07-17：LOGSAVE_TEXT等も同様に、$CONFの値が確定しているこの時点で
        # sprintf()により先に完成させておく（{}のネスト置換はpatTemplateでは
        # 機能しないため）。MAX_IMAGE*系はBBSMODE_IMAGE=1の時のみ存在するため
        # ??で未設定時も安全にフォールバックする。
        # 2026-07-17: Likewise pre-resolve LOGSAVE_TEXT and friends here via
        # sprintf(), since $CONF values are already known at this point
        # (nested {} substitution is not supported by patTemplate). The
        # MAX_IMAGE* keys only exist when BBSMODE_IMAGE=1, so fall back
        # safely with ?? when unset.
        $tmp['LOGSAVE_TEXT'] = sprintf($GLOBALS['MSG']['LOGSAVE_TEXT'], $this->c['LOGSAVE']);
        $tmp['FORM_CONTENTS_HELP_SIMPLE'] = sprintf($GLOBALS['MSG']['FORM_CONTENTS_HELP_SIMPLE'], $this->c['MAXMSGCOL'], $this->c['MAXMSGLINE']);
        $tmp['FORM_CONTENTS_HELP_IMAGE'] = sprintf($GLOBALS['MSG']['FORM_CONTENTS_HELP_IMAGE'], $this->c['MAXMSGCOL'], $this->c['MAXMSGLINE'], $this->c['IMAGETEXT'] ?? '');
        $tmp['IMAGE_UPLOAD_HELP'] = sprintf($GLOBALS['MSG']['IMAGE_UPLOAD_HELP'], $this->c['MAX_IMAGEWIDTH'] ?? '', $this->c['MAX_IMAGEHEIGHT'] ?? '', $this->c['MAX_IMAGESIZE'] ?? '');
        $tmp['JS_LANG_JSON'] = $GLOBALS['MSG_JSON'];
        $tmp['JS_SETTINGS_JSON'] = $GLOBALS['KSPHP_JS_SETTINGS_JSON'];
        $tmp['JS_LOCKED_JSON'] = $GLOBALS['KSPHP_JS_LOCKED_JSON'];

        // 20260720 Gikoneko: 言語切替プルダウンのoption HTMLをテンプレート
        // 変数として渡す。グローバルスコープで生成済みの$ksphp_lang_options_html
        // を使う。
        $tmp['LANG_OPTIONS_HTML'] = $GLOBALS['ksphp_lang_options_html'];

        $this->t->addGlobalVars($tmp);
    }

    /**
     * Error indication
     *
     * @access  public
     * @param   String  $err_message  Error message
     */
    function prterror($err_message) {
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('TITLE_ERROR'));
        $this->t->addVar('error', 'ERR_MESSAGE', $err_message);
        if (isset($this->s['DEFURL'])) {
            $this->t->setAttribute('backnavi', 'visibility', 'visible');
        }
        $this->t->displayParsedTemplate('error');
        print $this->prthtmlfoot ();
        $this->destroy();
        exit();
    }

    /**
     * 20260717 Gikoneko: create a required runtime file (e.g. the main log
     * file) if it doesn't exist yet, instead of the caller having to
     * hard-fail on first launch. Only ever call this with a fixed
     * configuration path (never with a path built from user input).
     *
     * @access  public
     * @param   String  $path            File path to ensure exists
     * @param   String  $initialcontent  Content to write if the file is created
     * @return  Boolean TRUE if the file was just created, FALSE if it already existed
     */
    function ensurefile($path, $initialcontent = '') {
        if (file_exists($path)) {
            return FALSE;
        }
        $dir = dirname($path);
        if ($dir and $dir != '.' and !is_dir($dir)) {
            @mkdir($dir, 0755, TRUE);
        }
        return @file_put_contents($path, $initialcontent) !== FALSE;
    }

    /**
     * 20260717 Gikoneko: notify the user that one or more required runtime
     * files were missing and have just been auto-created, then offer a
     * link back to the board (always shown, unlike prterror() which only
     * shows it when $this->s['DEFURL'] happens to already be set).
     *
     * @access  public
     * @param   Array   $messages  List of already-translated notice strings
     */
    function prtfilecreated($messages) {
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('TITLE_NOTICE'));
        $this->t->addVar('error', 'ERR_MESSAGE', implode('<br>', $messages));
        if (!isset($this->s['DEFURL'])) {
            $this->s['DEFURL'] = $this->c['CGIURL'];
        }
        $this->t->setAttribute('backnavi', 'visibility', 'visible');
        $this->t->displayParsedTemplate('error');
        print $this->prthtmlfoot ();
        $this->destroy();
        exit();
    }

    /**
     * Display HTML header section
     *
     * @access  public
     * @param   String  $title        HTML title
     * @param   String  $customhead   Custom header in the head tag
     * @param   String  $customstyle  Custom style sheets in the style tag
     * @return  String  HTML data
     */
    function prthtmlhead($title = "", $customhead = "", $customstyle = "") {
        $this->t->clearTemplate('header');
        $this->t->addVars('header', array(
            'TITLE' => $title,
            'CUSTOMHEAD' => $customhead,
            'CUSTOMSTYLE' => $customstyle,
        ));
        $htmlstr = $this->t->getParsedTemplate('header');
        return $htmlstr;
    }

    /**
     * Display HTML footer section
     *
     * @access  public
     * @return  String  HTML data
     */
    function prthtmlfoot() {
        if ($this->c['SHOW_PRCTIME'] and $this->s['START_TIME']) {
            $duration = Func::microtime_diff($this->s['START_TIME'], microtime());
            $duration = sprintf("%0.6f", $duration);
            $this->t->setAttribute('duration', 'visibility', 'visible');
            $this->t->addVar('duration', 'DURATION', $duration);
            $this->t->addVar('duration', 'PAGE_GEN_TIME_TEXT', sprintf(T('PAGE_GEN_TIME_TEXT'), $duration));
        }
        $htmlstr = $this->t->getParsedTemplate('footer');
        return $htmlstr;
    }

    /**
     * Copyright notice
     */
    function prtcopyright() {
        $copyright = $this->t->getParsedTemplate('copyright');
        return $copyright;
    }

    /**
     * Redirector output with META tags
     *
     * @access  public
     * @param   String  $redirecturl    URL to redirect
     */
    function prtredirect($redirecturl) {
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' - ' . T('URL_REDIRECTION'),
            "<meta http-equiv=\"refresh\" content=\"1;url={$redirecturl}\">\n");
        $this->t->addVar('redirect', 'REDIRECTURL', $redirecturl);
        $this->t->displayParsedTemplate('redirect');
        print $this->prthtmlfoot ();
    }

    /**
     * Display message contents definition
     */
    function setmessage($message, $mode = 0, $tlog = '') {

        if (count($message) < 10) {
            return;
        }
        $message['WDATE'] = Func::getdatestr($message['NDATE'], $this->c['DATEFORMAT']);
        # 表示専用：ログにデフォルト言語で焼き込まれた自己レスタグを閲覧者言語へ差し替え
        $message['USER'] = ksphp_translate_selfreply_tag($message['USER']);
		#20181102 Gikoneko: Escape special characters
		$message['MSG'] = preg_replace("/{/i","&#123;", $message['MSG'], -1);
        $message['MSG'] = preg_replace("/}/i","&#125;", $message['MSG'], -1);

#20260601 gikoneko ttp -> http converted
            $message['MSG'] = preg_replace("/[^h]((ttps?|ftp|news):\/\/[-_.,!~*'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/",
                "<a href=\"h$1\" target=\"link\">$1</a>", $message['MSG']);

        # "Reference"
        if (!$mode) {
            $message['MSG'] = preg_replace_callback("/<a href=\"m=f&s=(\d+)[^>]+>([^<]+)<\/a>$/i",
                function ($m) {
                    return "<a href=\"{$this->c['CGIURL']}?m=f&amp;s={$m[1]}&amp;{$this->s['QUERY']}\">" . ksphp_translate_reflink_text($m[2]) . "</a>";
                }, $message['MSG'], 1);
            $message['MSG'] = preg_replace_callback("/<a href=\"mode=follow&search=(\d+)[^>]+>([^<]+)<\/a>$/i",
                function ($m) {
                    return "<a href=\"{$this->c['CGIURL']}?m=f&amp;s={$m[1]}&amp;{$this->s['QUERY']}\">" . ksphp_translate_reflink_text($m[2]) . "</a>";
                }, $message['MSG'], 1);
        } else {
            $message['MSG'] = preg_replace_callback("/<a href=\"m=f&s=(\d+)[^>]+>([^<]+)<\/a>$/i",
                function ($m) {
                    return "<a href=\"#a{$m[1]}\">" . ksphp_translate_reflink_text($m[2]) . "</a>";
                }, $message['MSG'], 1);
            $message['MSG'] = preg_replace_callback("/<a href=\"mode=follow&search=(\d+)[^>]+>([^<]+)<\/a>$/i",
                function ($m) {
                    return "<a href=\"#a{$m[1]}\">" . ksphp_translate_reflink_text($m[2]) . "</a>";
                }, $message['MSG'], 1);
        }
        if ($mode == 0 or ($mode == 1 and $this->c['OLDLOGBTN'])) {

            if (!$this->c['FOLLOWWIN']) { $newwin = " target=\"link\""; }
            else { $newwin = ''; }
            $spacer = "&nbsp;&nbsp;&nbsp;";
            $lnk_class = "class=\"internal\"";
            # Follow-up post button
            $message['BTNFOLLOW'] = '';
            if ($this->c['BBSMODE_ADMINONLY'] != 1) {
                $message['BTNFOLLOW'] = "$spacer<a href=\"{$this->c['CGIURL']}"
                    ."?m=f&amp;s={$message['POSTID']}&amp;".$this->s['QUERY'];
                if (@$this->f['w']) {
                    $message['BTNFOLLOW'] .= "&amp;w=".@$this->f['w'];
                }
                if ($mode == 1) {
                    $message['BTNFOLLOW'] .= "&amp;ff=$tlog";
                }
                $message['BTNFOLLOW'] .= "\"$newwin $lnk_class title=\"" . T('TITLE_FOLLOWUP') . "\" >{$this->c['TXTFOLLOW']}</a>";
            }
            # Search by user button
            $message['BTNAUTHOR'] = '';
            if ($message['USER'] != $this->c['ANONY_NAME'] and $this->c['BBSMODE_ADMINONLY'] != 1) {
                $message['BTNAUTHOR'] = "$spacer<a href=\"{$this->c['CGIURL']}"
                    ."?m=s&amp;s=". urlencode(preg_replace("/<[^>]*>/", '', $message['USER'])) ."&amp;".$this->s['QUERY'];
                if (@$this->f['w']) {
                    $message['BTNAUTHOR'] .= "&amp;w=".@$this->f['w'];
                }
                if ($mode == 1) {
                    $message['BTNAUTHOR'] .= "&amp;ff=$tlog";
                }
                $message['BTNAUTHOR'] .= "\" target=\"link\" $lnk_class title=\"" . T('TITLE_SEARCH_BY_USER') . "\" >{$this->c['TXTAUTHOR']}</a>";
            }
            # Thread view button
            if (!$message['THREAD']) {
                $message['THREAD'] = $message['POSTID'];
            }
            $message['BTNTHREAD'] = '';
            if ($this->c['BBSMODE_ADMINONLY'] != 1) {
                $message['BTNTHREAD'] = "$spacer<a href=\"{$this->c['CGIURL']}?m=t&amp;s={$message['THREAD']}&amp;".$this->s['QUERY'];
                if ($mode == 1) {
                    $message['BTNTHREAD'] .= "&amp;ff=$tlog";
                }
                $message['BTNTHREAD'] .= "\" target=\"link\" $lnk_class title=\"" . T('TITLE_THREAD_VIEW') . "\" >{$this->c['TXTTHREAD']}</a>";
            }
            # Tree view button
            $message['BTNTREE'] = '';
            if ($this->c['BBSMODE_ADMINONLY'] != 1) {
                $message['BTNTREE'] = "$spacer<a href=\"{$this->c['CGIURL']}?m=tree&amp;s={$message['THREAD']}&amp;".$this->s['QUERY'];
                if ($mode == 1) {
                    $message['BTNTREE'] .= "&amp;ff=$tlog";
                }
                $message['BTNTREE'] .= "\" target=\"link\" $lnk_class title=\"" . T('TITLE_TREE_VIEW') . "\" >{$this->c['TXTTREE']}</a>";
            }
            # UNDO button
            $message['BTNUNDO'] = '';
            if ($this->c['ALLOW_UNDO'] and isset($this->s['UNDO_P']) and $this->s['UNDO_P'] == $message['POSTID']) {
                $message['BTNUNDO'] = "$spacer<a href=\"{$this->c['CGIURL']}?m=u&amp;s={$message['POSTID']}&amp;".$this->s['QUERY'];
                $message['BTNUNDO'] .= "\" $lnk_class title=\"" . T('TITLE_DELETE_POST') . "\" >{$this->c['TXTUNDO']}</a>";
            }
            # Button integration
            $message['BTN'] = $message['BTNFOLLOW']. $message['BTNAUTHOR']. $message['BTNTHREAD']. $message['BTNTREE']. $message['BTNUNDO'];
        }
        # Email address
        if ($message['MAIL']) {
            $message['USER'] = "<a href=\"mailto:{$message['MAIL']}\">{$message['USER']}</a>";
        }
        # Change quote color
        $message['MSG'] = preg_replace("/(^|\r)(\&gt;[^\r]*)/", "$1<span class=\"q\">$2</span>", $message['MSG']);
        $message['MSG'] = str_replace("</span>\r<span class=\"q\">", "\r", $message['MSG']);
        # Environment variables
        $message['ENVADDR'] = '';
        $message['ENVUA'] = '';
        $message['ENVBR'] = '';
        if ($this->c['IPPRINT'] or $this->c['UAPRINT']) {
            if ($this->c['IPPRINT']) {
                $message['ENVADDR'] = $message['PHOST'];
            }
            if ($this->c['UAPRINT']) {
                $message['ENVUA'] = $message['AGENT'];
            }
            if ($this->c['IPPRINT'] and $this->c['UAPRINT']) {
                $message['ENVBR'] = '<br>';
            }
            if ($message['ENVADDR'] or $message['ENVUA']) {
                $this->t->clearTemplate('envlist');
                $this->t->setAttribute("envlist", "visibility", "visible");
                $this->t->addVars('envlist', array(
                    'ENVADDR' => $message['ENVADDR'],
                    'ENVUA' => $message['ENVUA'],
                    'ENVBR' => $message['ENVBR'],
                ));
            }
        }
        # Whether or not to display images on the image BBS
        if (!$this->c['SHOWIMG']) {
            $message['MSG'] = Func::conv_imgtag($message['MSG']);
        }
        # Convert img tags even if there is no image file
        elseif (preg_match("/<a href=[^>]+><img [^>]*?src=\"([^\"]+)\"[^>]+><\/a>/i", $message['MSG'], $matches)) {
            if (!file_exists($matches[1])) {
                $message['MSG'] = Func::conv_imgtag($message['MSG']);
            }
        }
        # Message display content definition
        $this->t->clearTemplate('message');
        $this->t->addVars('message', $message);
    }

    /**
     * Single message output
     *
     * Outputs the HTML of a message based on the message array.
     * Supports the message log module.
     *
     * @access  public
     * @param   Array   $message    Message
     * @param   Integer $mode       0: Bulletin board / 1: Message log search (with buttons displayed) / 2: Message log search (without buttons displayed) / 3: For message log output file
     * @param   String  $tlog       Specified log file
     * @return  String  Message HTML data
     */
    function prtmessage($message, $mode = 0, $tlog = '') {
        $this->setmessage($message, $mode, $tlog);
        $prtmessage = $this->t->getParsedTemplate('message');
        return $prtmessage;
    }

    /**
     * Log reading
     *
     * Reads the log file, returns it as a line array.
     *
     * @access  public
     * @param   String  $logfilename  Log file name (optional)
     * @return  Array   Log line array
     */
    function loadmessage($logfilename = "") {
        if ($logfilename) {
            preg_match("/^([\w.]*)$/", $logfilename, $matches);
            $logfilename = $this->c['OLDLOGFILEDIR']."/".$matches[1];
        }
        else {
            $logfilename = $this->c['LOGFILENAME'];
            #20260717 Gikoneko: auto-create the main log file on first run
            #(never do this for the old-log branch above, since that path
            #is built from user input and must not auto-create arbitrary files)
            if (!file_exists($logfilename) and $this->ensurefile($logfilename)) {
                $this->prtfilecreated(array(sprintf(T('FILE_AUTOCREATED'), $logfilename)));
            }
        }
        if (!file_exists($logfilename)) {
            $this->prterror(T('FAILED_TO_READ_MESSAGE'));
        }
        $logdata = file($logfilename);
        return $logdata;
    }

    /**
     * Get single message
     *
     * Converts a log line to a message array and returns it.
     *
     * @access  public
     * @param   String  $logline  Log line
     * @return  Array   Message array
     */
    function getmessage($logline) {

        $logsplit = @explode (',', rtrim($logline));
        if (count($logsplit) < 10) {
            return;
        }
        $i = 6;
        while ($i <= 9) {
            $logsplit[$i] = strtr ($logsplit[$i], "\0", ",");
            $logsplit[$i] = str_replace ("&#44;", ",", $logsplit[$i]);
            $i++;
        }
        $message = array();
        $messagekey = array('NDATE', 'POSTID', 'PROTECT', 'THREAD', 'PHOST', 'AGENT', 'USER', 'MAIL', 'TITLE', 'MSG', 'REFID', 'RESERVED1', 'RESERVED2', 'RESERVED3', );
        $logsplitcount = count($logsplit);
        $i = 0;
        while ($i < $logsplitcount) {
            if ($i > 12) { break; }
            $message[$messagekey[$i]] = $logsplit[$i];
            $i++;
        }
        return $message;
    }

    /**
     * Reflect user settings
     */
    function refcustom() {

        $this->c['LINKOFF'] = 0;
        $this->c['HIDEFORM'] = 0;
        $this->c['RELTYPE'] = 0;
        if (!isset($this->c['SHOWIMG'])) {
            $this->c['SHOWIMG'] = 0;
        }
        $flgcolorchanged = FALSE;

        $colors = array(
            'C_BACKGROUND',
            'C_TEXT',
            'C_A_COLOR',
            'C_A_VISITED',
            'C_SUBJ',
            'C_QMSG',
            'C_A_ACTIVE',
            'C_A_HOVER',
        );
        $flags = array(
            'GZIPU',
            'RELTYPE',
            'AUTOLINK',
            'FOLLOWWIN',
            'COOKIE',
            'LINKOFF',
            'HIDEFORM',
            'SHOWIMG',
        );
        # Update from settings string
        if (@$this->f['c']) {
            $strflag = '';
            $formc = @$this->f['c'];
            if (strlen($formc) > 5) {
                $formclen = strlen($formc);
                $strflag = substr($formc, 0, 2);
                $currentpos = 2;
                foreach ($colors as $confname) {
                    $colorval = Func::base64_threebytehex(substr($formc, $currentpos, 4));
                    if (strlen($colorval) == 6 and strcasecmp($this->c[$confname], $colorval) != 0) {
                        $flgcolorchanged = TRUE;
                        $this->c[$confname] = $colorval;
                    }
                    $currentpos += 4;
                    if ($currentpos > $formclen) {
                        break;
                    }
                }
            }
            elseif (strlen($formc) == 2) {
                $strflag = $formc;
            }
            if ($strflag) {
                $flagbin = str_pad(base_convert ($strflag, 32, 2), count($flags), "0", STR_PAD_LEFT);
                $currentpos = 0;
                foreach ($flags as $confname) {
                    $this->c[$confname] = substr($flagbin, $currentpos, 1);
                    $currentpos++;
                }
            }
        }
        # Update settings information
        if (@$this->f['m'] == 'p' or @$this->f['m'] == 'c' or @$this->f['m'] == 'g') {
            @$this->f['a'] ? $this->c['AUTOLINK'] = 1 : $this->c['AUTOLINK'] = 0;
            @$this->f['g'] ? $this->c['GZIPU'] = 1 : $this->c['GZIPU'] = 0;
            @$this->f['loff'] ? $this->c['LINKOFF'] = 1 : $this->c['LINKOFF'] = 0;
            @$this->f['hide'] ? $this->c['HIDEFORM'] = 1 : $this->c['HIDEFORM'] = 0;
            @$this->f['sim'] ? $this->c['SHOWIMG'] = 1 : $this->c['SHOWIMG'] = 0;
            if (@$this->f['m'] == 'c') {
                @$this->f['fw'] ? $this->c['FOLLOWWIN'] = 1 : $this->c['FOLLOWWIN'] = 0;
                @$this->f['rt'] ? $this->c['RELTYPE'] = 1 : $this->c['RELTYPE'] = 0;
                @$this->f['cookie'] ? $this->c['COOKIE'] = 1 : $this->c['COOKIE'] = 0;

                // 2026-08-01 Gikoneko: 「個人環境設定」内JS設定セクション。
                // 定義テーブル(ksphp_js_setting_defs())をループして保存する
                // ため、新規JS機能追加時もこの箇所は変更不要（定義テーブルに
                // 1行足すだけで自動的に保存対象になる）。
                $js_defs = ksphp_js_setting_defs();
                $js_settings = array();
                foreach ($js_defs as $js_key => $js_def) {
                    if ($js_def['type'] === 'bool') {
                        // ロック済み（判定はksphp_js_setting_defs()で一元
                        // 計算）のキーはフォーム値を無視して強制 0 を保存。
                        // チェックボックス自体が非表示なのでフォームから
                        // 来ることはないが、念のため POST 改ざん対策も兼ねる。
                        $js_settings[$js_key] = !empty($js_def['locked']) ? 0 : (@$this->f['js_' . $js_key] ? 1 : 0);
                    } else { // int
                        $submitted = @$this->f['js_' . $js_key];
                        $n = is_numeric($submitted) ? (int) $submitted : $js_def['default'];
                        if ($n < $js_def['min'] || $n > $js_def['max']) {
                            $n = $js_def['default'];
                        }
                        $js_settings[$js_key] = $n;
                    }
                }
                setcookie('ksphp_js', json_encode($js_settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), CURRENT_TIME + 7776000); // 90日
            }
        }
        # Special conditions
        if ($this->c['BBSMODE_ADMINONLY'] != 0) {
            (@$this->f['m'] == 'f' or (@$this->f['m'] == 'p' and @$this->f['write'])) ? $this->c['HIDEFORM'] = 0 : $this->c['HIDEFORM'] = 1;
        }
        # Update the settings string
        {
            $flagbin = '';
            foreach ($flags as $confname) {
                $this->c[$confname] ? $flagbin .= '1' : $flagbin .= '0';
            }
            $flagvalue = str_pad(base_convert ($flagbin, 2, 32), 2, "0", STR_PAD_LEFT);

            if ($flgcolorchanged) {
                @$this->f['c'] = $flagvalue . substr(@$this->f['c'], 2);
            }
            else {
                @$this->f['c'] = $flagvalue;
            }
        }
    }

    /**
     * HTTP header settings
     */
    function sethttpheader() {
        header('Content-Type: text/html; charset=UTF-8');
        header("X-XSS-Protection: 1; mode=block");
        // Remove X-Frame-Options (not needed when using CSP)
        header_remove("X-Frame-Options");
        // Allow embedding from anywhere
        header("Content-Security-Policy: frame-ancestors *;");

    }

    /**
     * Start execution time measurement
     */
    function setstarttime() {
        $this->s['START_TIME'] = microtime();
    }

}

/**
 * Standard bulletin board class - Bbs
 *
 * A bulletin board display class for PC.
 * If you want to customize/extend the bulletin board function itself, inherit this class.
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Bbs extends Webapp {

    /**
     * Constructor
     *
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * Main process
     */
    function main() {
        # Start execution time measurement
        $this->setstarttime();
        # Form acquisition preprocessing
        $this->procForm();
        # Reflect user settings
        $this->refcustom();
        $this->setusersession();
        # gzip compression transfer
        if ($this->c['GZIPU']) {
            ob_start("ob_gzhandler");
        }
	# Prevent accidental posting when opening settings
	if (@$this->f['setup']) {
	$this->prtcustom();
	return;
	}
        # Post operation
        if (@$this->f['m'] == 'p' and trim(@$this->f['v'])) {
            # Get environment variables
            $this->setuserenv();
            # Parameter check
            $posterr = $this->chkmessage();
            # Post operation
            if (!$posterr) {
                $posterr = $this->putmessage($this->getformmessage());
            }
            # Douple post error, etc.
            if ($posterr == 1) {
                $this->prtmain();
            }
            # Protect code redisplayed due to time lapse
            elseif ($posterr == 2) {
                if (@$this->f['f']) {
                    $this->prtfollow(TRUE);
                }
                elseif (@$this->f['write']) {
                    $this->prtnewpost(TRUE);
                }
                else {
                    $this->prtmain(TRUE);
                }
            }
            # Admin mode via post is disabled
            elseif ($posterr == 3) {
                $this->prterror(T('ADMIN_POST_DISABLED'));
            }
            # Post completion page
            elseif (@$this->f['f']) {
                $this->prtputcomplete();
            }
            else {
                $this->prtmain();
            }
        }
        # Display follow-up page
        elseif (@$this->f['m'] == 'f') {
            $this->prtfollow();
        }
        # Post search
        elseif (@$this->f['m'] == 't' or @$this->f['m'] == 's') {
            $this->prtsearchlist();
        }
        # Display user settings page
        elseif (@$this->f['setup']) {
            $this->prtcustom();
        }
        # User settings process
        elseif (@$this->f['m'] == 'c') {
            $this->setcustom();
        }
        # New post
        elseif (@$this->f['m'] == 'p' and @$this->f['write']) {
            $this->prtnewpost();
        }
        # UNDO process
        elseif (@$this->f['m'] == 'u') {
            $this->prtundo();
        }
        # Default: bulletin board display
        else {
            $this->prtmain();
        }

        if ($this->c['GZIPU']) {
            ob_end_flush();
        }
    }

    /**
     * Display bulletin board
     *
     * @access  public
     * @param   Boolean  $retry  Retry flag
     */
    function prtmain($retry = FALSE) {
        # Get display message
        list ($logdatadisp, $bindex, $eindex, $lastindex) = $this->getdispmessage();
        # Form section settings
        $dtitle = "";
        $dmsg = "";
        $dlink = "";
        if ($retry) {
            $dtitle = @$this->f['t'];
            $dmsg = @$this->f['v'];
            $dlink = @$this->f['l'];
        }
        $this->setform ($dtitle, $dmsg, $dlink);
        # HTML header partial output
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE']);
        # Upper main section
        $this->t->displayParsedTemplate('main_upper');
        # Display message
        foreach ( $logdatadisp as $msgdata) {
            print $this->prtmessage($this->getmessage($msgdata), 0, 0);
        }
        # Message information
        if ($this->s['MSGDISP'] < 0) {
            $msgmore = '';
        }
        elseif ($eindex > 0) {
		$msgmore = str_replace(['{BINDEX}','{EINDEX}'], [$bindex,$eindex], T('POSTS_RANGE_NEWEST_TO_OLDEST'));
        }
        else {

#20260719 Gikoneko: conf.php の GIKONEKO_TOISSHO（あり=1／なし=0）で
#分岐できるようにした。旧処理（NO_UNREAD_MESSAGES表示）はGIKONEKO_TOISSHO=0の
#場合のフォールバックとして残す。
#20260719 Gikoneko: Made this branch on conf.php's GIKONEKO_TOISSHO
#(1=on/0=off). The old behavior (showing NO_UNREAD_MESSAGES) is kept as
#the fallback for GIKONEKO_TOISSHO=0.
            if ($this->c['GIKONEKO_TOISSHO']) {
                // 20260802: also respect the reader's personal setting (cookie
                // 'ksphp_js' -> 'giko'). The server flag (GIKONEKO_TOISSHO)
                // is the master; if it is 0 we never reach this branch at all.
                // If it is 1 but the reader has turned it off locally, fall
                // back to the plain NO_UNREAD_MESSAGES text instead.
                $js_current = ksphp_js_settings_load();
                if ($js_current['giko']) {
require_once("./gikoneko.php");

ob_start();
giko_display();
$msgmore = ob_get_clean();
                } else {
                    $msgmore = T('NO_UNREAD_MESSAGES') . ' ';
                }
            }
            else {
                $msgmore = T('NO_UNREAD_MESSAGES') . ' ';
            }

        }
        if ($eindex >= $lastindex) {
            $msgmore .= T('NO_POSTS_BELOW');
        }
        $this->t->addVar('main_lower', 'MSGMORE', $msgmore);
        # Navigation buttons
        if ($eindex > 0) {
            if ($eindex >= $lastindex) {
                $this->t->setAttribute("nextpage", "visibility", "hidden");
            }
            else {
                $this->t->addVar('nextpage', 'EINDEX', $eindex);
            }
            if (!$this->c['SHOW_READNEWBTN']) {
                $this->t->setAttribute("readnew", "visibility", "hidden");
            }
        }
        # Post as administrator
        if ($this->c['BBSMODE_ADMINONLY'] == 0) {
            $this->t->setAttribute("adminlogin", "visibility", "hidden");
        }
        # Lower main section
        $this->t->displayParsedTemplate('main_lower');
        print $this->prthtmlfoot ();
    }

    /**
     * Get display range messages and parameters
     *
     * @access  public
     * @return  Array   $logdatadisp  Log line array
     * @return  Integer $bindex       Beginning of index
     * @return  Integer $eindex       End of index
     * @return  Integer $lastindex    End of all logs index
     */
    function getdispmessage() {

        # 20260719 Gikoneko: one-pass streaming read (Func::fgetline) instead of
        # loading the whole log via file(), to avoid holding LOGSAVE lines in
        # memory when only a small display window is needed. array_splice()'s
        # PHP-specific negative offset/length ("count from the end") semantics
        # require knowing the total line count in advance, so that rare edge
        # case (bindex<0, or eindex-bindex<0 before clamping) falls back to a
        # cheap count-only pre-pass + a second targeted read. The normal case
        # (bindex>=0 and eindex>=bindex) stays a true single pass.
        $logfilename = $this->c['LOGFILENAME'];
        #20260717 Gikoneko: auto-create the main log file on first run
        if (!file_exists($logfilename) and $this->ensurefile($logfilename)) {
            $this->prtfilecreated(array(sprintf(T('FILE_AUTOCREATED'), $logfilename)));
        }
        if (!file_exists($logfilename)) {
            $this->prterror(T('FAILED_TO_READ_MESSAGE'));
        }
        $fh = @fopen($logfilename, "rb");
        if (!$fh) {
            $this->prterror(T('FAILED_TO_READ_MESSAGE'));
        }

        # Unread pointer (latest POSTID) -- only line 0 is needed for this
        $firstline = Func::fgetline($fh);
        $items = @explode (',', $firstline, 3);
        $toppostid = $items[1];

        # Number of posts displayed
        $msgdisp = Func::fixnumberstr(@$this->f['d']);
        if ($msgdisp === FALSE) {
            $msgdisp = $this->c['MSGDISP'];
        }
        elseif ($msgdisp < 0) {
            $msgdisp = -1;
        }
        elseif ($msgdisp > $this->c['LOGSAVE']) {
            $msgdisp = $this->c['LOGSAVE'];
        }
        if (@$this->f['readzero']) {
            $msgdisp = 0;
        }
        # Beginning of index
        $bindex = @$this->f['b'];
        if (!$bindex) {
            $bindex = 0;
        }
        # For the next and subsequent pages
        if ($bindex > 1) {
            # If there are new posts, shift the beginning of the index
            if ($toppostid > @$this->f['p']) {
                $bindex += ($toppostid - @$this->f['p']);
            }
            # Don't update unread pointer
            $toppostid = @$this->f['p'];
        }
        # End of index
        $eindex = $bindex + $msgdisp;
        # Unread reload
        if (@$this->f['readnew'] or ($msgdisp == '0' and $bindex == 0)) {
            $bindex = 0;
            $eindex = $toppostid - @$this->f['p'];
        }
        # Display posts -1
        if ($msgdisp < 0) {
            $bindex = 0;
            $eindex = 0;
        }

        # 20260717 Gikoneko: does resolving this window require knowing the
        # total line count in advance? (true array_splice() would need it
        # whenever the offset is negative, or the length is negative even
        # before clamping to the actual total)
        $needsEndCount = ($bindex < 0 or ($eindex - $bindex) < 0);

        if ($needsEndCount) {
            # Pre-pass: count remaining lines only, no storage (line 0 already read above)
            $lastindex = ($firstline === FALSE) ? 0 : 1;
            while (Func::fgetline($fh) !== FALSE) {
                $lastindex++;
            }
            # Resolve the exact array_splice-equivalent [start, end) range
            $offset = $bindex;
            $start = ($offset >= 0) ? min($offset, $lastindex) : max($lastindex + $offset, 0);
            if ($eindex > $lastindex) {
                $eindex = $lastindex;
            }
            $lengthparam = $eindex - $bindex;
            $end = ($lengthparam >= 0) ? min($start + $lengthparam, $lastindex) : max($lastindex + $lengthparam, $start);

            # Second pass: re-open and read only the resolved [start, end) range
            fclose($fh);
            $fh = @fopen($logfilename, "rb");
            if (!$fh) {
                $this->prterror(T('FAILED_TO_READ_MESSAGE'));
            }
            $logdatadisp = array();
            $lineindex = 0;
            while (($line = Func::fgetline($fh)) !== FALSE) {
                if ($lineindex >= $start and $lineindex < $end) {
                    $logdatadisp[] = $line;
                }
                $lineindex++;
                if ($lineindex >= $end) {
                    break;
                }
            }
            fclose($fh);
        }
        else {
            # Normal case: single forward pass, buffer only [bindex, eindex)
            $logdatadisp = array();
            $lineindex = 0;
            $line = $firstline;
            while ($line !== FALSE) {
                if ($lineindex >= $bindex and $lineindex < $eindex) {
                    $logdatadisp[] = $line;
                }
                $lineindex++;
                $line = Func::fgetline($fh);
            }
            fclose($fh);
            $lastindex = $lineindex;
            # For the last page, truncate (mirrors original array_splice truncation)
            if ($eindex > $lastindex) {
                $eindex = $lastindex;
            }
        }

        if ($this->c['RELTYPE'] and (@$this->f['readnew'] or ($msgdisp == '0' and $bindex == 0))) {
            $logdatadisp = array_reverse($logdatadisp);
        }
        $this->s['TOPPOSTID'] = $toppostid;
        $this->s['MSGDISP'] = $msgdisp;
        $this->t->addGlobalVars(array(
            'TOPPOSTID' => $this->s['TOPPOSTID'],
            'MSGDISP' => $this->s['MSGDISP']
        ));
        return array($logdatadisp, $bindex + 1, $eindex, $lastindex);
    }

    /**
     * Form section settings
     *
     * @access  public
     * @param   String  $dtitle     Initial value of the form title
     * @param   String  $dmsg       Initial value for the form contents
     * @param   String  $dlink      Initial value for the form link
     */
    function setform($dtitle, $dmsg, $dlink, $mode = '') {
        # Protect code generation
        $pcode = Func::pcode();
        if (!$mode) {
            $mode = '<input type="hidden" name="m" value="p" />';
        }
        $this->t->addVars('form', array(
            'MODE' => $mode,
            'PCODE' => $pcode,
        ));
        # Hide post form
        if ($this->c['HIDEFORM'] and @$this->f['m'] != 'f' and !@$this->f['write']) {
            $this->t->addVar('postform', 'mode', 'hide');
        }
        else {
            $this->t->addVars('postform', array(
                'DTITLE' => $dtitle,
                'DMSG' => $dmsg,
                'DLINK' => $dlink,
            ));
        }
        # Settings and links lines
        if (@$this->f['m'] != 'f' and !isset($this->f['f']) and !@$this->f['write']) {
            # Counter
            if ($this->c['SHOW_COUNTER']) {
                $counter = $this->counter();
                if (is_numeric($counter)) { $counter = number_format((int)$counter); }
                $this->t->addVar("counter", 'COUNTER', $counter);
                # 2026-07-17：{COUNTER_TEXT}の中に{COUNTDATE}等をネストして
                # 埋め込んでいたが、patTemplateは一度置換した文字列を再度
                # 走査しないため、sprintf()で先に文字列を完成させてから渡す。
                # 2026-07-17: COUNTER_TEXT previously embedded nested
                # {COUNTDATE}/{COUNTER}/{COUNTLEVEL} tokens, but patTemplate
                # does not re-scan already-substituted text, so those never
                # resolved. Build the final string with sprintf() first.
                $this->t->addVar("counter", 'COUNTER_TEXT', sprintf(T('COUNTER_TEXT'), $this->c['COUNTDATE'], $counter, $this->c['COUNTLEVEL']));
                $this->t->setAttribute("counter", "visibility", "visible");
            }
            if ($this->c['CNTFILENAME']) {
                $mbrcount = $this->mbrcount();
                if (is_numeric($mbrcount)) { $mbrcount = number_format((int)$mbrcount); }
                $this->t->addVar("mbrcount", 'MBRCOUNT', $mbrcount);
                $this->t->addVar("mbrcount", 'MBRCOUNT_TEXT', sprintf(T('MBRCOUNT_TEXT'), $mbrcount, $this->c['CNTLIMIT']));
                $this->t->setAttribute("mbrcount", "visibility", "visible");
            }
            if (!$this->c['SHOW_COUNTER'] and !$this->c['CNTFILENAME']) {
                $this->t->setAttribute("counterrow", "visibility", "hidden");
            }
            if ($this->c['BBSMODE_ADMINONLY'] == 0) {
                if ($this->c['AUTOLINK']) $this->t->addVar('formconfig', 'CHK_A', ' checked="checked"');
                if ($this->c['HIDEFORM']) $this->t->addVar('formconfig', 'CHK_HIDE', ' checked="checked"');
            }
            else {
                $this->t->setAttribute("formconfig", "visibility", "hidden");
            }
            # Hide link line
            if ($this->c['LINKOFF']) {
                $this->t->addVar('extraform', 'CHK_LOFF', ' checked="checked"');
                $this->t->setAttribute("linkrow", "visibility", "hidden");
            }
            # Hide help line
            if ($this->c['BBSMODE_ADMINONLY'] != 1) {
                if (!$this->c['ALLOW_UNDO']) {
                    $this->t->setAttribute("helpundo", "visibility", "hidden");
                }
            }
            else {
                $this->t->setAttribute("helprow", "visibility", "hidden");
            }
            # Navigation buttons line
            if (!$this->c['SHOW_READNEWBTN']) {
                $this->t->setAttribute("readnewbtn", "visibility", "hidden");
            }
            if (!($this->c['HIDEFORM'] and $this->c['BBSMODE_ADMINONLY'] == 0)) {
                $this->t->setAttribute("newpostbtn", "visibility", "hidden");
            }
        }
        else {
            $this->t->setAttribute("extraform", "visibility", "hidden");
        }
    }

    /**
     * Display follow-up page
     *
     * @access  public
     * @param   Boolean $retry  Retry flag
     */
    function prtfollow($retry = FALSE) {

        if (!@$this->f['s']) {
            $this->prterror(T('NO_PARAMETERS'));
        }

        # Administrator authentication
        if ($this->c['BBSMODE_ADMINONLY'] == 1
            and crypt(@$this->f['u'], $this->c['ADMINPOST']) != $this->c['ADMINPOST']) {
            $this->prterror(T('INVALID_PASSWORD'));
        }
        $filename = '';
        if (@$this->f['ff']) {
            $filename = trim(@$this->f['ff']);
        }
        $result = $this->searchmessage('POSTID', @$this->f['s'], FALSE, $filename);
        if (!$result) {
            $this->prterror(T('MESSAGE_NOT_FOUND'));
        }
        # Get message
        $message = $this->getmessage($result[0]);

        if (!$retry) {
            $formmsg = $message['MSG'];
            $formmsg = preg_replace ("/&gt; &gt;[^\r]+\r/", "", $formmsg);
            $formmsg = preg_replace ("/<a href=\"m=f\S+\"[^>]*>[^<]+<\/a>/i", "", $formmsg);
            $formmsg = preg_replace ("/<a href=\"[^>]+>([^<]+)<\/a>/i", "$1", $formmsg);
            $formmsg = preg_replace ("/\r*<a href=[^>]+><img [^>]+><\/a>/i", "", $formmsg);
            $formmsg = preg_replace ("/\r/", "\r> ", $formmsg);
            $formmsg = "> $formmsg\r";
            $formmsg = preg_replace ("/\r>\s+\r/", "\r", $formmsg);
            $formmsg = preg_replace ("/\r>\s+\r$/", "\r", $formmsg);
        } else {
            $formmsg = @$this->f['v'];
            $formmsg = preg_replace ("/<a href=\"m=f\S+\"[^>]*>[^<]+<\/a>/i", "", $formmsg);
        }
        $formmsg .= "\r";

        $this->setform ( "＞" . preg_replace("/<[^>]*>/", '', $message['USER']) . $this->c['FSUBJ'], $formmsg, '');

        if (!$message['THREAD']) {
            $message['THREAD'] = $message['POSTID'];
        }
        $filename ? $mode = 1 : $mode = 0;
        $this->setmessage ($message, $mode, $filename);

        if ($this->c['AUTOLINK']) $this->t->addVar('follow', 'CHK_A', ' checked="checked"');
        $this->t->addVar('follow', 'FOLLOWID', $message['POSTID']);
        $this->t->addVar('follow', 'SEARCHID', @$this->f['s']);
        $this->t->addVar('follow', 'FF', @$this->f['ff']);
        # Display
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('FOLLOW_UP_POST'));
        $this->t->displayParsedTemplate('follow');
        print $this->prthtmlfoot ();

    }

    /**
     * Display new post page
     *
     * @access  public
     */
    function prtnewpost($retry = FALSE) {

        # Administrator authentication
        if ($this->c['BBSMODE_ADMINONLY'] != 0
            and crypt(@$this->f['u'], $this->c['ADMINPOST']) != $this->c['ADMINPOST']) {
            $this->prterror(T('INVALID_PASSWORD'));
        }
        # Form section
        $dtitle = "";
        $dmsg = "";
        $dlink = "";
        if ($retry) {
            $dtitle = @$this->f['t'];
            $dmsg = @$this->f['v'];
            $dlink = @$this->f['l'];
        }
        $this->setform ($dtitle, $dmsg, $dlink);

        if ($this->c['AUTOLINK']) $this->t->addVar('newpost', 'CHK_A', ' checked="checked"');

        $this->sethttpheader();
        print $this->prthtmlhead ( $this->c['BBSTITLE'] . ' ' . T('NEW_POST') );
        $this->t->displayParsedTemplate('newpost');
        print $this->prthtmlfoot ();

    }

    /**
     * Post search
     *
     * @param   Integer $mode       0: Bulletin board / 1: Message log search (with buttons displayed) / 2: Message log search (without buttons displayed) / 3: For message log file output
     */
    function prtsearchlist($mode = "") {

        if (!@$this->f['s']) {
            $this->prterror(T('NO_PARAMETERS'));
        }
        if (!$mode) {
            $mode = @$this->f['m'];
        }
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('POST_SEARCH'));
        $this->t->displayParsedTemplate('searchlist_upper');

        $result = $this->msgsearchlist($mode);
        foreach ($result as $message) {
            print $this->prtmessage ($message, $mode, @$this->f['ff']);
        }
        $success = count($result);

        $this->t->addVar('searchlist_lower', 'SUCCESS', $success);
        $this->t->displayParsedTemplate('searchlist_lower');
        print $this->prthtmlfoot ();

    }

    /**
     * Post search process
     */
    function msgsearchlist($mode) {

        $fh = NULL;
        if (@$this->f['ff']) {
            if (preg_match("/^[\w.]+$/", @$this->f['ff'])) {
                $fh = @fopen($this->c['OLDLOGFILEDIR'] . @$this->f['ff'], "rb");
            }
            if (!$fh) {
                $this->prterror( sprintf(T('FAILED_TO_OPEN_LOG'), @$this->f['ff']) );
            }
            flock ($fh, 1);
        }

        $result = array();

        if ($fh) {
            $linecount = 0;
            $threadstart = FALSE;
            while (($logline = Func::fgetline($fh)) !== FALSE) {
                if ($threadstart) {
                    $linecount++;
                }
                if ($linecount > $this->c['LOGSAVE']) {
                    break;
                }
                $message = $this->getmessage($logline);
                # Search by user
                if ($mode == 's' and preg_replace("/<[^>]*>/", '', $message['USER']) == @$this->f['s']) {
                    $result[] = $message;
                }
                # Search by thread
                elseif ($mode == 't'
                    and ($message['THREAD'] == @$this->f['s'] or $message['POSTID'] == @$this->f['s'])) {
                    $result[] = $message;
                    if (!$threadstart) {
                        $threadstart = TRUE;
                    }
                }
            }
            flock ($fh, 3);
            fclose ($fh);
        }
        else {
            # 20260719 Gikoneko: loadmessage()（file()で全件配列化）ではなく、
            # "ff"分岐と同じFunc::fgetline()によるストリーム読みに統一。
            # 全件を配列で保持しないため、ログが大きいほどメモリ削減効果が出る。
            # 20260719 Gikoneko: Unified this with the same
            # Func::fgetline()-based stream reading used by the "ff" branch,
            # instead of loadmessage() (which loads everything into an array
            # via file()). Since the whole log is no longer held in memory
            # at once, the memory savings grow with log size.
            $logfilename = $this->c['LOGFILENAME'];
            if (!file_exists($logfilename) and $this->ensurefile($logfilename)) {
                $this->prtfilecreated(array(sprintf(T('FILE_AUTOCREATED'), $logfilename)));
            }
            if (!file_exists($logfilename)) {
                $this->prterror(T('FAILED_TO_READ_MESSAGE'));
            }
            $fh2 = @fopen($logfilename, "rb");
            if ($fh2) {
                while (($logline = Func::fgetline($fh2)) !== FALSE) {
                    $message = $this->getmessage($logline);
                    # Search by user
                    if ($mode == 's' and preg_replace("/<[^>]*>/", '', $message['USER']) == @$this->f['s']) {
                        $result[] = $message;
                    }
                    # Search by thread
                    elseif ($mode == 't'
                        and ($message['THREAD'] == @$this->f['s'] or $message['POSTID'] == @$this->f['s'])) {
                        $result[] = $message;
                        if ($message['POSTID'] == @$this->f['s']) {
                            break;
                        }
                    }
                }
                fclose($fh2);
            }
        }
        return $result;
    }

    /**
     * Post complete
     */
    function prtputcomplete() {

        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('POST_COMPLETE'));
        $this->t->displayParsedTemplate('postcomplete');
        print $this->prthtmlfoot ();

    }

    /**
     * Display user settings page
     */
    function prtcustom($mode = '') {

        if ($this->c['GZIPU']) $this->t->addVar('custom', 'CHK_G', ' checked="checked"');
        if ($this->c['AUTOLINK']) $this->t->addVar('custom', 'CHK_A', ' checked="checked"');
        if ($this->c['LINKOFF']) $this->t->addVar('custom', 'CHK_LOFF', ' checked="checked"');
        if ($this->c['HIDEFORM']) $this->t->addVar('custom', 'CHK_HIDE', ' checked="checked"');
        if ($this->c['SHOWIMG']) $this->t->addVar('custom', 'CHK_SI', ' checked="checked"');
        if ($this->c['COOKIE']) $this->t->addVar('custom', 'CHK_COOKIE', ' checked="checked"');

        $this->c['FOLLOWWIN'] ? $this->t->addVar('custom', 'CHK_FW_1', ' checked="checked"')
            : $this->t->addVar('custom', 'CHK_FW_0', ' checked="checked"');
        $this->c['RELTYPE'] ? $this->t->addVar('custom', 'CHK_RT_1', ' checked="checked"')
            : $this->t->addVar('custom', 'CHK_RT_0', ' checked="checked"');

        // 2026-08-01 Gikoneko: 「個人環境設定」内JS設定セクション。
        // cookie 'ksphp_js' の現在値を読み、フォームのチェック状態/数値欄
        // へ反映する。定義テーブルをループするため、新規JS機能追加時も
        // ここは変更不要。
        $js_current = ksphp_js_settings_load();
        foreach (ksphp_js_setting_defs() as $js_key => $js_def) {
            // bool キーは各自のサブテンプレート（js_<key>_row）で囲まれて
            // いる。conf.php で管理者がデフォルト=0（無効化）に設定した
            // キーはサブテンプレートを hidden のまま残す（visibility を
            // 'visible' にしない）。有効なキーのみ visible 化する。
            // addVar()はテンプレート名で厳密にスコープされるため、
            // 各チェックボックスはそれぞれ自分のサブテンプレート名を
            // 対象として addVar する（nextpage/backnavi と同じ慣例）。
            if ($js_def['type'] === 'bool') {
                $row_tmpl = 'js_' . $js_key . '_row';
                // ロック済み（conf.phpで0、またはgikoでGIKONEKO_TOISSHO=0。
                // 判定はksphp_js_setting_defs()で一元計算）のキーは
                // サブテンプレートを非表示のまま残してスキップ。
                if (empty($js_def['locked'])) {
                    $this->t->setAttribute($row_tmpl, 'visibility', 'visible');
                    if ($js_current[$js_key]) {
                        $this->t->addVar($row_tmpl, 'CHK_JS_' . strtoupper($js_key), ' checked="checked"');
                    }
                }
            } else { // int
                // longpost_th の入力欄は js_longpost_row サブテンプレート
                // 内にあるため、addVar の対象もそのサブテンプレート名を
                // 指定する必要がある（addVar厳密スコープ）。
                // linebreaker_len は custom 直下なので 'custom' でよい。
                $int_target = ($js_key === 'longpost_th') ? 'js_longpost_row' : 'custom';
                $this->t->addVar($int_target, 'VAL_JS_' . strtoupper($js_key), (string) $js_current[$js_key]);
                // 動的にmax値を計算するキー（linebreaker_len等）用に、
                // フォームのmax属性へも渡しておく。
                $this->t->addVar($int_target, 'VAL_JS_' . strtoupper($js_key) . '_MAX', (string) $js_def['max']);
            }
        }

        $this->t->addVar('custom_hide', 'BBSMODE_ADMINONLY', $this->c['BBSMODE_ADMINONLY']);
        $this->t->addVar('custom_a', 'BBSMODE_ADMINONLY', $this->c['BBSMODE_ADMINONLY']);
        $this->t->addVar('custom', 'MODE', $mode);

        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('USER_SETTINGS'));
        $this->t->displayParsedTemplate('custom');
        print $this->prthtmlfoot ();
    }

    /**
     * User settings process
     */
    function setcustom() {

        $redirecturl = $this->c['CGIURL'];

        # Cookie消去
        # Clear cookies
        if (@$this->f['cr']) {
            @$this->f['c'] = '';
            setcookie('c');
            setcookie('undo');
            setcookie('ksphp_lang', '', 1); // 20260720 Gikoneko: 言語設定もリセット
            $this->s['UNDO_P'] = '';
            $this->s['UNDO_K'] = '';
        }
        else {
            $colors = array(
                'C_BACKGROUND',
                'C_TEXT',
                'C_A_COLOR',
                'C_A_VISITED',
                'C_SUBJ',
                'C_QMSG',
                'C_A_ACTIVE',
                'C_A_HOVER',
            );

            $flgchgindex = -1;
            $cindex = 0;
            foreach ($colors as $confname) {
                if (strlen(@$this->f[$confname] ?? '') == 6 and preg_match("/^[0-9a-fA-F]{6}$/", @$this->f[$confname] ?? '')
                    and @$this->f[$confname] != $this->c[$confname]) {
                    $this->c[$confname] = @$this->f[$confname];
                    $flgchgindex = $cindex;
                }
                $cindex++;
            }

            $cbase64str = '';
            for ($i = 0; $i <= $flgchgindex; $i++) {
                $cbase64str .= Func::threebytehex_base64($this->c[$colors[$i]]);
            }
            $this->refcustom();

            @$this->f['c'] = substr(@$this->f['c'], 0, 2) . $cbase64str;

            $redirecturl .= "?c=".@$this->f['c'];
            foreach (array('w', 'd',) as $key) {
                if ($this->f[$key] != '') {
                    $redirecturl .= "&{$key}=".$this->f[$key];
                }
            }
            if (@$this->f['nm']) {
                $redirecturl .= "&m=".@$this->f['nm'];
            }
            if ($this->c['COOKIE']) {
                $this->setbbscookie();
            }
        }
        # Redirect
        if (preg_match("/^(https?):\/\//", $this->c['CGIURL'])) {
            header ("Location: {$redirecturl}");
        }
        else {
            $this->prtredirect(htmlentities($redirecturl));
        }
    }

    /**
     * UNDO process
     */
    function prtundo() {
        if (!@$this->f['s']) {
            $this->prterror(T('NO_PARAMETERS'));
        }
        if (isset($this->s['UNDO_P']) and $this->s['UNDO_P'] == @$this->f['s']) {
            $loglines = $this->searchmessage('POSTID', $this->s['UNDO_P']);
            if (count($loglines) < 1) {
                #20260717 Gikoneko: the post is already gone (most commonly a
                #double-submitted UNDO request racing with itself — the first
                #request already deleted it before this one's search ran).
                #The end state the user wants (post deleted) is already true,
                #so treat this as success rather than showing an error.
                #Note: this can't distinguish "already deleted" from "rotated
                #into the old-log archive", so if LOGSAVE rotation is the
                #actual cause here, the post itself is left untouched even
                #though this reports success.
                $this->s['UNDO_P'] = '';
                $this->s['UNDO_K'] = '';
                setcookie('undo');
            }
            else {
                $message = $this->getmessage($loglines[0]);
                $undokey = substr (preg_replace("/\W/", "", crypt($message['PROTECT'], $this->c['ADMINPOST'])), -8);
                if ($undokey != $this->s['UNDO_K']) {
                    $this->prterror ( T('UNDO_NOT_PERMITTED') );
                }
                # Erase operation
                require_once(PHP_BBSADMIN);
                $bbsadmin = new Bbsadmin();
                $bbsadmin->killmessage($this->s['UNDO_P']);

                $this->s['UNDO_P'] = '';
                $this->s['UNDO_K'] = '';
                setcookie('undo');
            }
        }
        else {
            $this->prterror ( T('UNDO_NOT_PERMITTED') );
        }
        $this->sethttpheader();
        print $this->prthtmlhead($this->c['BBSTITLE'] . ' ' . T('DELETION_COMPLETE'));
        $this->t->displayParsedTemplate('undocomplete');
        print $this->prthtmlfoot ();
    }

    /**
     * Message search (exact match)
     *
     * @access  public
     * @param   String  $varname      Variable name
     * @param   String  $searchvalue  Search string
     * @param   Boolean $ismultiple   Multiple search flag
     * @return  Array   Log line array
     */
    function searchmessage($varname, $searchvalue, $ismultiple = FALSE, $filename = "") {
        $result = array();
        $logdata = $this->loadmessage($filename);
        foreach ($logdata as $logline) {
            $message = $this->getmessage($logline);
            if (isset($message[$varname]) and $message[$varname] == $searchvalue) {
                $result[] = $logline;
                if (!$ismultiple) {
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Post check
     *
     * @access  public
     * @param   Boolean   $limithost  Whether or not to check for same host
     * @return  Integer   Error code
     */
    function chkmessage($limithost = TRUE) {
        $posterr = 0;
        if ($this->c['RUNMODE'] == 1) {
            $this->prterror(T('POSTING_SUSPENDED'));
        }
        /* Prohibit access by host name process */
        if (Func::hostname_match($this->c['HOSTNAME_POSTDENIED'], $this->c['HOSTAGENT_BANNED'])) {
            $this->prterror(T('POSTING_SUSPENDED'));
        }
        
        /* Catch spambots */
        if(detectHoneyPot()) {
            $this->prterror(T('SPAM_KUN'));
        }

        if ($this->c['BBSMODE_ADMINONLY'] == 1 or ($this->c['BBSMODE_ADMINONLY'] == 2 and !@$this->f['f'])) {
            if (crypt(@$this->f['u'], $this->c['ADMINPOST']) != $this->c['ADMINPOST']) {
                $this->prterror(T('ADMIN_ONLY_POSTING'));
            }
        }
        if (@$_SERVER['HTTP_REFERER'] and $this->c['REFCHECKURL']
            and (strpos(@$_SERVER['HTTP_REFERER'], $this->c['REFCHECKURL']) === FALSE
            or strpos(@$_SERVER['HTTP_REFERER'], $this->c['REFCHECKURL']) > 0)) {
            $this->prterror(T('BAD_REFERER') . "<br>{$this->c['REFCHECKURL']}.");
        }
        foreach (explode ("\r", @$this->f['v']) as $line) {
            if (strlen ($line) > $this->c['MAXMSGCOL']) {
                $this->prterror(T('POST_TOO_WIDE'));
            }
        }
        if (substr_count (@$this->f['v'], "\r") > $this->c['MAXMSGLINE'] - 1) {
            $this->prterror(T('POST_TOO_MANY_LINES'));
        }
        if (strlen (@$this->f['v']) > $this->c['MAXMSGSIZE']) {
            $this->prterror(T('POST_TOO_LARGE'));
        }
        if (strlen (@$this->f['u']) > $this->c['MAXNAMELENGTH']) {
            $this->prterror(sprintf(T('NAME_TOO_LONG'), $this->c['MAXNAMELENGTH']));
        }
        if (strlen (@$this->f['i']) > $this->c['MAXMAILLENGTH']) {
            $this->prterror(sprintf(T('EMAIL_TOO_LONG'), $this->c['MAXMAILLENGTH']));
        }
//        if (@$this->f['i']) { ## mod
//            $this->prterror(T('SPAM_KUN')); ## mod
//        } ## mod
        if (strlen (@$this->f['t']) > $this->c['MAXTITLELENGTH']) {
            $this->prterror(sprintf(T('TITLE_TOO_LONG'), $this->c['MAXTITLELENGTH']));
        }
        {
            $timestamp = Func::pcode_verify (@$this->f['pc'], $limithost);

            if ((CURRENT_TIME - $timestamp ) < $this->c['MINPOSTSEC'] ) {
                $this->prterror(T('POST_TOO_FAST'));
            }
/*            if ((CURRENT_TIME - $timestamp ) > $this->c['MAXPOSTSEC'] ) {
                $this->prterror ( 'The time between posts is too long. Please try again.');
                $posterr = 2;
                return $posterr;
            } */
        }

        if (trim(@$this->f['v']) == '') {
            $posterr = 2;
            return $posterr;
        }

        ## if ($this->c['NGWORD']) {
        ##     foreach ($this->c['NGWORD'] as $ngword) {
        ##         if (strpos(@$this->f['v'], $ngword) !== FALSE
        ##             or strpos(@$this->f['l'], $ngword) !== FALSE
        ##             or strpos(@$this->f['t'], $ngword) !== FALSE
        ##             or strpos(@$this->f['u'], $ngword) !== FALSE
        ##             or strpos(@$this->f['i'], $ngword) !== FALSE) {
        ##            $this->prterror( T('NGWORD_FOUND') );
        ##         }
        ##     }
        ## }
        if ($this->c['NGWORD']) { ## mod
            foreach ($this->c['NGWORD'] as $ngword) {
                $ngword = strtolower($ngword); // Convert prohibited word to lowercase
                if (
                    strpos(strtolower(@$this->f['v']), $ngword) !== FALSE ||
                    strpos(strtolower(@$this->f['l']), $ngword) !== FALSE ||
                    strpos(strtolower(@$this->f['t']), $ngword) !== FALSE ||
                    strpos(strtolower(@$this->f['u']), $ngword) !== FALSE ||
                    strpos(strtolower(@$this->f['i']), $ngword) !== FALSE
                ) {
                    $this->prterror( T('NGWORD_FOUND') );
                }
            }
        } ## mod end

        #20240204 猫 spam detection (https://php.o0o0.jp/article/php-spam)
        # Number of characters: char_num = mb_strlen( @$this->f['v'], 'UTF8');
        # Number of bytes: byte_num = strlen( @$this->f['v']);

        ## $char_num = mb_strlen( @$this->f['v'], 'UTF8');
        ## $byte_num = strlen( @$this->f['v']);

        # When single-byte characters makes up more than 90% of the total
        ## if ((($char_num * 3 - $byte_num) / 2 / $char_num * 100) > 90) {
        ##     # Treat as spam
        ##     $this->prterror('This bulletin board\'s post function is currently disabled.');
        ## }
        ## disabled by TL: not suitable for languages that use single-byte characters (i.e. English)


        return $posterr;
    }
    
################
#post user function by chatGPT 20260310 gikoneko
################

########## 1 メイン処理
########## 1 Main process

function handleUser(&$message)
{
    $user = $message['USER'] ?? '';

    if ($user === '') {
        $message['USER'] = $this->c['ANONY_NAME'];
        return;
    }

    [$name, $trip, $copy] = $this->parseUser($user);

    $admin = $this->checkAdmin($name, $trip, $copy, $message);

    if ($admin === true) {
        return;
    }

    // Admin access via post form is disabled
    $name = $this->checkAdminFraud($name);
    $name = $this->protectAdminName($name,$trip,$copy);
    $name = $this->convertHandle($name);

    $message['USER'] = $this->buildName($name, $trip, $copy);
}

###########  2 ユーザー解析
###########  2 User parsing

function parseUser($user)
{
    $trip = '';
    $copy = '';

    # ◆コピー分解
    # Split off a raw ◆-copy (a pasted trip-like string, not a computed trip)
    if (strpos($user, '◆') !== false) {

        $parts = explode('◆', $user);

        $name = $parts[0];
        $copy = implode('◆', array_slice($parts,1));

        return [$name, '', $copy];
    }

    # 通常トリップ
    # Normal (computed) trip
    if (($pos = strpos($user,'#')) !== false) {

        $name = substr($user,0,$pos);
        $tripkey = substr($user,$pos);

        $trip =
            substr(
                preg_replace("/\W/",'',crypt($tripkey,'00')),
                -7
            ) .
            $this->tripuse($tripkey);

        return [$name,$trip,''];
    }

    return [$user,'',''];
}

############ 3 admin判定
############ 3 Admin detection

function checkAdmin($name,$trip,$copy,&$message)
{
    $adminPost = $this->c['ADMINPOST'] ?? '';

    if (!$adminPost) {
        return false;
    }

    // Warn only if admin password is posted in BOTH the name and comment fields
    if ($adminPost && crypt($name, $adminPost) === $adminPost 
        && crypt(($message['MSG'] ?? ''), $adminPost) === $adminPost
    ) {    
        $this->prterror(T('ADMIN_POST_DISABLED'));
    }
    
    # ◆コピーがある場合は管理不可
    # If a ◆-copy is present, admin status is not granted
    if ($copy !== '') {
        return false;
    }

    if (crypt($name,$adminPost) === $adminPost) {

        $adminName = $this->c['ADMINNAME'];

        $message['USER'] =
            "<span class=\"muh\">{$adminName}</span>";

        if ($trip !== '') {
            $message['USER'] .=
                ' <span class="mut">◆'.$trip.'</span>';
        }

        $message['MAIL'] = $this->c['ADMINMAIL'];

        if (!empty($this->c['ADMINKEY']) &&
            trim($message['MSG']) === $this->c['ADMINKEY']) {
            return 3;
        }

        return true;
    }

    return false;
}

########## 4 admin騙り検出
########## 4 Admin-impersonation detection

function checkAdminFraud($name)
{
    $adminName = $this->c['ADMINNAME'] ?? '';

    if ($adminName && strpos($name,$adminName) !== false) {

        return $adminName .
            '<span class="muh">' .
            T('FRAUDSTER_TAG') .
            '</span>';
    }

    return $name;
}

########### 5 固定ハンドル処理
########### 5 Fixed-handle processing

function convertHandle($name)
{
    $handles = $this->c['HANDLENAMES'] ?? [];

    if (!$handles) {
        return $name;
    }

    if (isset($handles[$name])) {

        return $name .
            '<span class="muh">' .
            T('FRAUDSTER_TAG') .
            '</span>';
    }

    $search = array_search(trim($name),$handles,true);

    if ($search !== false) {
        return "<span class=\"muh\">{$search}</span>";
    }

    return $name;
}

######### 6 表示組み立て
######### 6 Building the display string

function buildName($name,$trip,$copy)
{
    $adminPost = $this->c['ADMINPOST'] ?? '';
    $adminName = $this->c['ADMINNAME'] ?? '';

    # 管理パス + ◆コピー → 管理人騙り
    # Admin password + ◆-copy → treat as admin impersonation
    if ($copy !== '' && $adminPost && crypt($name,$adminPost) === $adminPost) {

        $copy = str_replace('◆','◇',$copy);

        return "<span class=\"muh\">{$adminName}</span>"
            .' <span class="mut">◇'.$copy.'</span>'
            .T('FRAUDSTER_TAG');
    }

    if ($copy !== '') {

        $copy = str_replace('◆','◇',$copy);

        return $name
            .' <span class="mut">◇'.$copy.'</span>'
            .T('FRAUDSTER_TAG');
    }

    if ($trip !== '') {

        return $name
            .' <span class="mut">◆'.$trip.'</span>';
    }

    return $name;
}

###### 7 protectAdminName

function protectAdminName($name,$trip,$copy)
{
    $adminName = $this->c['ADMINNAME'] ?? '';

    if (!$adminName) {
        return $name;
    }

    if (strpos($name,$adminName) !== false) {

        return $adminName
            .'<span class="muh">'
            .T('FRAUDSTER_TAG')
            .'</span>';
    }

    return $name;
}

    /**
     * Get message from form input
     *
     * @access  public
     * @return  Array  Message array
     */
    /**
     * Build a getlog (m=g) search link for a #hashtag found in a post.
     * The file list is anchored on the post's own date ($ndate), not
     * "today", so the link keeps pointing at the same window even when
     * viewed later:
     *   - OLDLOGSAVESW=1 (monthly log files): search the post's own month
     *   - OLDLOGSAVESW=0 (daily log files): search the 7 days up to and
     *     including the post's date
     * @param   String  $tag    Tag text (already html-escaped by procForm())
     * @param   Integer $ndate  Post's Unix timestamp
     * @return  String  <a> tag linking to the getlog search
     * @access  private
     */
    function makehashtaglink($tag, $ndate) {
        $ext = $this->c['OLDLOGFMT'] ? 'dat' : 'html';
        $files = array();
        if ($this->c['OLDLOGSAVESW']) {
            $files[] = date("Ym", $ndate) . ".$ext";
        }
        else {
            for ($i = 0; $i < 7; $i++) {
                $files[] = date("Ymd", $ndate - $i * 86400) . ".$ext";
            }
        }
        $searchtag = html_entity_decode($tag, ENT_QUOTES);
        $query = 'm=g&amp;q=' . rawurlencode($searchtag);
        foreach ($files as $file) {
            $query .= '&amp;f[]=' . rawurlencode($file);
        }
        # getconditions()'s sd/sh/si/ed/eh/ei default to "00" when omitted,
        # which msgsearch() reads as an *empty* time range (rejects every
        # message) rather than "no limit" -- the search form only avoids
        # this because its own <select> defaults happen to submit the
        # full-range values below. Since this link bypasses that form,
        # supply the same full-range values explicitly.
        if ($this->c['OLDLOGSAVESW']) {
            $query .= '&amp;sd=01&amp;sh=00&amp;ed=31&amp;eh=24';
        }
        else {
            $query .= '&amp;sh=00&amp;si=00&amp;eh=24&amp;ei=00';
        }
        return "<a href=\"{$this->s['DEFURL']}&amp;{$query}\" target=\"link\">#{$tag}</a>";
    }

    function getformmessage() {

        $message = array();
        $message['PCODE'] = @$this->f['pc'];
        $message['USER'] = @$this->f['u'];
        $message['MAIL'] = @$this->f['i'];
        $message['TITLE'] = @$this->f['t'];
        $message['MSG'] = @$this->f['v'];
        $message['URL'] = @$this->f['l'];
        $message['PHOST'] = $this->s['HOST'];
        $message['AGENT'] = $this->s['AGENT'];
        # Reference ID
        if (@$this->f['f']) {
            $message['REFID'] = @$this->f['f'];
        }
        else {
            $message['REFID'] = '';
        }
        # Protect code
        $message['PCODE'] = substr($message['PCODE'], 8, 4);
        # Title
        if (!$message['TITLE']) {
            $message['TITLE'] = ' ';
        }
        # User

####################
#Execute Post User
####################
$result = $this->handleUser($message);
if ($result === 3) {
	return 3;
}
####################

        $message['MSG'] = rtrim ($message['MSG']);

        # Auto-link URLs
        if ( $this->c['AUTOLINK'] ) {
            # Hashtags (#tag) -> getlog (m=g) full-text search link, restricted
            # to a date window anchored on this post's own date. Must run
            # before the URL regex below, since a "#" is only treated as a
            # hashtag when preceded by whitespace/line-start -- never right
            # after non-space text, which avoids catching a URL's own
            # "#fragment" here.
            $message['MSG'] = preg_replace_callback("/(?<=^|\s)#([^\s#<>&]+)/u",
                function ($matches) {
                    return $this->makehashtaglink($matches[1], CURRENT_TIME);
                }, $message['MSG']);
            $message['MSG'] = preg_replace("/((https?|ftp|news):\/\/[-_.,!~*'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/",
                "<a href=\"$1\" target=\"link\">$1</a>", $message['MSG']);
        }
        # URL field
        $message['URL'] = trim($message['URL']);
        if ($message['URL']) {
            $message['MSG'] .= "\r\r<a href=\"".Func::escape_url($message['URL'])."\" target=\"link\">{$message['URL']}</a>";
        }
        # Reference
        if ($message['REFID']) {
            $refdata = $this->searchmessage('POSTID', $message['REFID'], FALSE, @$this->f['ff']);
            if (!$refdata) {
                $this->prterror ( T('REFERENCE_NOT_FOUND') );
            }
            $refmessage = $this->getmessage($refdata[0]);
            $refmessage['WDATE'] = Func::getdatestr_default($refmessage['NDATE'], $this->c['DATEFORMAT']);
            $message['MSG'] .= "\r\r<a href=\"m=f&s={$message['REFID']}&r=&\">" . TDefault('REFERENCE_COLON') . " {$refmessage['WDATE']}</a>";
            # Simple self-reply prevention function
            if ($this->c['IPREC'] and $this->c['SHOW_SELFFOLLOW']
                and $refmessage['PHOST'] != '' and $refmessage['PHOST'] == $message['PHOST']) {
                $message['USER'] .= '<span class="muh">' . TDefault('SELF_REPLY_TAG') . '</span>';
            }
        }
        # Check
        if (strlen ($message['MSG']) > $this->c['MAXMSGSIZE']) {
            $this->prterror ( T('POST_TOO_LARGE') );
        }
        return $message;
    }

    /**
     * Message registration process
     *
     * @access  public
     * @return  Integer  Error code
     */
    function putmessage($message) {
        if (!is_array($message)) {
            return $message;
        }
        #20260717 Gikoneko: auto-create the main log file on first post
        if (!file_exists($this->c['LOGFILENAME']) and $this->ensurefile($this->c['LOGFILENAME'])) {
            $this->prtfilecreated(array(sprintf(T('FILE_AUTOCREATED'), $this->c['LOGFILENAME'])));
        }
        $fh = @fopen($this->c['LOGFILENAME'], "rb+");
        if (!$fh) {
            $this->prterror ( T('FAILED_TO_READ_MESSAGE') );
        }
        flock ($fh, 2);
        fseek ($fh, 0, 0);

        $logdata = array();
        while (($logline = Func::fgetline($fh)) !== FALSE) {
                $logdata[] = $logline;
        }
        $posterr = 0;
        if (@$this->f['ff']) {
            $refdata = $this->searchmessage('THREAD', $message['REFID'], FALSE, @$this->f['ff']);
            if (isset($refdata[0])) {
                $refmessage = $this->getmessage($refdata[0]);
                if ($refmessage) {
                    $message['THREAD'] = $refmessage['thread'];
                }
                else {
                    $message['THREAD'] = '';
                }
            }
            else {
                $message['THREAD'] = '';
            }
        }
        else {
            for ($i = 0; $i < count($logdata); $i++) {
                $items = @explode(',', $logdata[$i]);
                if (count($items) > 8) {
                    $items[9] = rtrim($items[9]);
                    if ($i < $this->c['CHECKCOUNT'] and $message['MSG'] == $items[9]) {
                        $posterr = 1;
                        break;
                    }
                    if ($this->c['IPREC'] and CURRENT_TIME < ($items[0] + $this->c['SPTIME'])
                        and $this->s['HOST'] == $items[4]) {
                        $posterr = 2;
                        break;
                    }
                    if ($message['PCODE'] == $items[2]) {
                        $posterr = 2;
                        break;
                    }
                    if ($message['REFID'] and $items[1] == $message['REFID']) {
                        $message['THREAD'] = $items[3];
                        if (!$message['THREAD']) {
                            $message['THREAD'] = $items[1];
                        }
                    }
                }
            }
        }
        if ($posterr) {
            flock ($fh, 3);
            fclose ($fh);
            return $posterr;
        }
        else {
            $items = @explode (',', $logdata[0], 3);
            $message['POSTID'] = $items[1] + 1;
            if (!$message['REFID']) {
                $message['THREAD'] = $message['POSTID'];
            }
            $msgdata = implode (',', array(
                CURRENT_TIME,
                $message['POSTID'],
                $message['PCODE'],
                $message['THREAD'],
                $message['PHOST'],
                $message['AGENT'],
                $message['USER'],
                $message['MAIL'],
                $message['TITLE'],
                $message['MSG'],
                $message['REFID'],
            ));
            $msgdata = strtr ($msgdata, "\n", "") . "\n";
            if (count($logdata) >= $this->c['LOGSAVE']) {
                $logdata = array_slice($logdata, 0, $this->c['LOGSAVE'] - 2);
            }
            {
                $logdata = $msgdata . implode ('', $logdata);
                fseek ($fh, 0, 0);
                ftruncate ($fh, 0);
                fwrite ($fh, $logdata);
            }
            flock ($fh, 3);
            fclose ($fh);
            # Cookie registration
            if ($this->c['COOKIE']) {
                $this->setbbscookie();
                if ($this->c['ALLOW_UNDO']) {
                    $this->setundocookie($message['POSTID'], $message['PCODE']);
                }
            }

            # Message log output
            if ($this->c['OLDLOGFILEDIR']) {
                $dir = $this->c['OLDLOGFILEDIR'];

                if ($this->c['OLDLOGFMT']) {
                    $oldlogext = 'dat';
                }
                else {
                    $oldlogext = 'html';
                }
                if ($this->c['OLDLOGSAVESW']) {
                    $oldlogfilename = $dir . date("Ym", CURRENT_TIME) . ".$oldlogext";
                    $oldlogtitle = $this->c['BBSTITLE'] . date(" Y.m", CURRENT_TIME);
                }
                else {
                    $oldlogfilename = $dir . date("Ymd", CURRENT_TIME) . ".$oldlogext";
                    $oldlogtitle = $this->c['BBSTITLE'] . date(" Y.m.d", CURRENT_TIME);
                }
                if (@filesize($oldlogfilename) > $this->c['MAXOLDLOGSIZE']) {
                    $this->prterror ( T('OLDLOG_TOO_LARGE') );
                }
                #20260719 Gikoneko: auto-create the old-log directory/file on first use
                #(mirrors the 20260717 LOGFILENAME auto-create pattern in putmessage())
                #20260801 Gikoneko: only show the "newly created" notice when this
                #looks like a genuinely first-time setup (no other old-log files
                #exist yet in the directory). Routine daily/monthly rotation --
                #the normal case, where past-period files already exist -- creates
                #a new dated file at every period boundary; showing the notice on
                #every such occasion surprised viewers into thinking something had
                #gone wrong. A directory-listing check (any other *.$oldlogext file
                #already present) is used instead of a stricter date calculation,
                #since it is enough to distinguish "brand new setup" from "routine
                #rotation" without needing to reason about calendar boundaries.
                if (!file_exists($oldlogfilename) and $this->ensurefile($oldlogfilename)) {
                    $has_other_oldlogs = FALSE;
                    $dh_check = @opendir($dir);
                    if ($dh_check) {
                        while ($entry_check = readdir($dh_check)) {
                            if ($entry_check !== basename($oldlogfilename)
                                and preg_match("/\.$oldlogext$/", $entry_check)) {
                                $has_other_oldlogs = TRUE;
                                break;
                            }
                        }
                        closedir($dh_check);
                    }
                    if (!$has_other_oldlogs) {
                        $this->prtfilecreated(array(sprintf(T('FILE_AUTOCREATED'), $oldlogfilename)));
                    }
                }
                $fh = @fopen($oldlogfilename, "ab");
                if (!$fh) {
                    $this->prterror( T('FAILED_TO_OUTPUT_LOG') );
                }
                flock ($fh, 2);
                $isnewdate = FALSE;
                if (!@filesize($oldlogfilename)) {
                    $isnewdate = TRUE;
                }
                if ($this->c['OLDLOGFMT']) {
                    fwrite ($fh, $msgdata);
                }
                else {
                    # HTML header for HTML output
                    if ($isnewdate) {
                        $oldloghtmlhead = $this->prthtmlhead($oldlogtitle);
                        $oldloghtmlhead .= "<span class=\"pagetitle\">$oldlogtitle</span>\n\n<hr />\n";
                        fwrite ($fh, $oldloghtmlhead);
                    }
                    $msghtml = $this->prtmessage($this->getmessage($msgdata), 3);
                    fwrite ($fh, $msghtml);
                }
                flock ($fh, 3);
                fclose ($fh);
                if (@filesize($oldlogfilename) > $this->c['MAXOLDLOGSIZE']) {
                    @chmod ($oldlogfilename, 0400);
                }
                # Delete old log files
                if (!$this->c['OLDLOGSAVESW'] and $isnewdate) {
                    $limitdate = CURRENT_TIME - $this->c['OLDLOGSAVEDAY'] * 60 * 60 * 24;
                    $limitdate = date("Ymd", $limitdate);
                    $dh = opendir($dir);
                    while ($entry = readdir($dh)) {
                        $matches = array();
                        if (is_file($dir . $entry)
                            and preg_match("/(\d+)\.$oldlogext$/", $entry, $matches)) {
                            $timestamp = $matches[1];
                            if (strlen($timestamp) == strlen($limitdate) and $timestamp < $limitdate) {
                                unlink ($dir . $entry);
                            }
                        }
                    }
                    closedir ($dh);
                }

                # Archive creation
                if ($this->c['ZIPDIR'] and @function_exists('gzcompress')) {
                    # In the case of dat, it also writes the message log in HTML format as a temporary file to be saved in the ZIP
                    if ($this->c['OLDLOGFMT']) {
                        if ($this->c['OLDLOGSAVESW']) {
                            $tmplogfilename = $this->c['ZIPDIR'] . date("Ym", CURRENT_TIME) . ".html";
                        }
                        else {
                            $tmplogfilename = $this->c['ZIPDIR'] . date("Ymd", CURRENT_TIME) . ".html";
                        }

                        $fhtmp = @fopen($tmplogfilename, "ab");
                        if (!$fhtmp) {
                            return;
                        }
                        flock ($fhtmp, 2);

                        if (!@filesize($tmplogfilename)) {
                            $oldloghtmlhead = $this->prthtmlhead($oldlogtitle);
                            $oldloghtmlhead .= "<span class=\"pagetitle\">$oldlogtitle</span>\n\n<hr />\n";
                            fwrite ($fhtmp, $oldloghtmlhead);
                        }
                        $msghtml = $this->prtmessage($this->getmessage($msgdata), 3);
                        fwrite ($fhtmp, $msghtml);
                        flock ($fhtmp, 3);
                        fclose ($fhtmp);
                    }
                    $tmpdir = $dir;
                    if ($this->c['OLDLOGFMT']) {
                        $tmpdir = $this->c['ZIPDIR'];
                    }
                    if ($this->c['OLDLOGSAVESW']) {
                        $currentfile = date("Ym", CURRENT_TIME) . ".html";
                    }
                    else {
                        $currentfile = date("Ymd", CURRENT_TIME) . ".html";
                    }

                    $files = array();
                    $dh = opendir($tmpdir);
                    if (!$dh) {
                        return;
                    }
                    while ($entry = readdir($dh)) {
                        if ($entry != $currentfile and is_file($tmpdir . $entry) and preg_match("/^\d+\.html$/", $entry)) {
                            $files[] = $entry;
                        }
                    }
                    closedir ($dh);

                    # File with the latest update time, other than the current log
                    $maxftime = 0;
                    $checkedfile = '';
                    foreach ($files as $filename) {
                        $fstat = stat ($tmpdir . $filename);
                        if ($fstat[9] > $maxftime) {
                            $maxftime = $fstat[9];
                            $checkedfile = $tmpdir . $filename;
                        }
                    }
                    if (!$checkedfile) {
                        return;
                    }
                    $zipfilename = preg_replace("/\.\w+$/", ".zip", $checkedfile);

                    # Create a ZIP file
                    require_once(LIB_PHPZIP);
                    $zip = new PHPZip();
                    $zipfiles[] = $checkedfile;
                    $zip->Zip($zipfiles, $zipfilename);

                    # Delete temporary files
                    if ($this->c['OLDLOGFMT']) {
                        unlink ($checkedfile);
                    }
                }
            }
        }
        return 0;
    }

    /**
     * Get environment variables
     */
    function setuserenv() {

        if ($this->c['UAREC']) {
            $agent = @$_SERVER['HTTP_USER_AGENT'];
            $agent = Func::html_escape($agent);
            $this->s['AGENT'] = $agent;
        }
        if (!$this->c['IPREC']) {
            return;
        }
        list ($addr, $host, $proxyflg, $realaddr, $realhost) = Func::getuserenv();

        $this->s['ADDR'] = $addr;
        $this->s['HOST'] = $host;
        $this->s['PROXYFLG'] = $proxyflg;
        $this->s['REALADDR'] = $realaddr;
        $this->s['REALHOST'] = $realhost;
    }

    /**
     * Bulletin board cookie registration
     */
    function setbbscookie() {
        $cookiestr = "u=" . urlencode(@$this->f['u']);
        $cookiestr .= "&i=" . urlencode(@$this->f['i']);
        $cookiestr .= "&c=" . @$this->f['c'];
        setcookie('c', $cookiestr, CURRENT_TIME + 7776000); // expires in 90 days
    }

    /**
     * Register cookie for post UNDO
     */
    function setundocookie($undoid, $pcode) {
        $undokey = substr (preg_replace("/\W/", "", crypt($pcode, $this->c['ADMINPOST'])), -8);
        $cookiestr = "p=$undoid&k=$undokey";
        $this->s['UNDO_P'] = $undoid;
        $this->s['UNDO_K'] = $undokey;
        setcookie('undo', $cookiestr, CURRENT_TIME + 86400); // expires in 24 hours
    }

    /**
     * Bulletproof counter process
     *
     * @access  public
     * @param   Integer Bulletproof level
     * @return  String  Counter value
     */
    function counter($countlevel = 0) {
        if (!$countlevel) {
            if (isset($this->c['COUNTLEVEL'])) {
                $countlevel = $this->c['COUNTLEVEL'];
            }
            if ($countlevel < 1) {
                $countlevel = 1;
            }
        }
        #20260719 Gikoneko: auto-create the counter directory on first use
        #(mirrors the 20260717 LOGFILENAME auto-create pattern in putmessage();
        #COUNTFILE is a filename prefix, not a single path, so ensurefile()
        #itself doesn't apply here -- only the directory needs pre-creating)
        $countdir = dirname($this->c['COUNTFILE']);
        if ($countdir and $countdir != '.' and !is_dir($countdir)) {
            @mkdir($countdir, 0755, TRUE);
        }
        $count = array();
        $filenumber = array();
        for ($i = 0; $i < $countlevel; $i++) {
            $filename = "{$this->c['COUNTFILE']}{$i}.dat";
            if (is_writable ($filename) and $fh = @fopen ($filename, "r")) {
                $count[$i] = fgets ($fh, 10);
                fclose ($fh);
            }
            else {
                $count[$i] = 0;
            }
            $filenumber[$count[$i]] = $i;
        }
        sort ($count, SORT_NUMERIC);
        $mincount = $count[0];
        $maxcount = $count[$countlevel-1] + 1;
        if ($fh = @fopen("{$this->c['COUNTFILE']}{$filenumber[$mincount]}.dat", "w")) {
            fputs ($fh, $maxcount);
            fclose ($fh);
            return $maxcount;
        } else {
            return T('COUNTER_ERROR');
        }
    }

    /**
     * Participant count (currently viewing)
     *
     * @access  public
     * @param   $cntfilename  Record file name
     * @return  String  Number of participants
     */
    function mbrcount($cntfilename = "") {
        if (!$cntfilename) {
            $cntfilename = $this->c['CNTFILENAME'];
        }
        if ($cntfilename) {
            $mbrcount = 0;
            $remoteaddr = '0.0.0.0';
            if (@$_SERVER['REMOTE_ADDR']) {
                $remoteaddr = @$_SERVER['REMOTE_ADDR'];
            }
            $ukey = hexdec(substr(md5($remoteaddr), 0, 8));
            $newcntdata = array();
            if (is_writable ($cntfilename)) {
                $cntdata = file ($cntfilename);
                $cadd = 0;
                foreach ($cntdata as $cntvalue) {
                    if (strrpos($cntvalue, ',') !== FALSE) {
                        list ($cuser, $ctime,) = @explode (',', trim ($cntvalue));
                        if ($cuser == $ukey) {
                            $newcntdata[] = "$ukey,".CURRENT_TIME."\n";
                            $cadd = 1;
                            $mbrcount++;
                        }
                        elseif (($ctime + $this->c['CNTLIMIT']) >= CURRENT_TIME) {
                            $newcntdata[] = "$cuser,$ctime\n";
                            $mbrcount++;
                        }
                    }
                }
                if (!$cadd) {
                    $newcntdata[] = "$ukey,".CURRENT_TIME."\n";
                    $mbrcount++;
                }
            }
            else {
                $newcntdata[] = "$ukey,".CURRENT_TIME."\n";
                $mbrcount++;
            }
            if ($fh = @fopen ($cntfilename, "w")) {
                $cntdatastr = implode('', $newcntdata);
                flock ($fh, 2);
                fwrite ($fh, $cntdatastr);
                flock ($fh, 3);
                fclose ($fh);
            }
            else {
                return T('PARTICIPANT_FILE_ERROR');
            }
            return $mbrcount;
        }
        else {
            return;
        }
    }
}
/* end of class Bbs */

/**
 * Shared function class
 *
 * A class that stores general-purpose functions that do not depend on configuration information.
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Func {

    /**
     * Constructor
     *
     */
    public function __construct() {
    }


    public static function getuserenv() {

        $addr = @$_SERVER['REMOTE_ADDR'];
        $host = @$_SERVER['REMOTE_HOST'];
        $agent = @$_SERVER['HTTP_USER_AGENT'];
        if ($addr == $host or !$host) {
            $host = gethostbyaddr ($addr);
        }

        $proxyflg = 0;

        if (@$_SERVER['HTTP_CACHE_CONTROL']) { $proxyflg = 1; }
        if (@$_SERVER['HTTP_CACHE_INFO']) { $proxyflg += 2; }
        if (@$_SERVER['HTTP_CLIENT_IP']) { $proxyflg += 4; }
        if (@$_SERVER['HTTP_FORWARDED']) { $proxyflg += 8; }
        if (@$_SERVER['HTTP_FROM']) { $proxyflg += 16; }
        if (@$_SERVER['HTTP_PROXY_AUTHORIZATION']) { $proxyflg += 32; }
        if (@$_SERVER['HTTP_PROXY_CONNECTION']) { $proxyflg += 64; }
        if (@$_SERVER['HTTP_SP_HOST']) { $proxyflg += 128; }
        if (@$_SERVER['HTTP_VIA']) { $proxyflg += 256; }
        if (@$_SERVER['HTTP_X_FORWARDED_FOR']) { $proxyflg += 512; }
        if (@$_SERVER['HTTP_X_LOCKING']) { $proxyflg += 1024; }
        if (preg_match ("/cache|delegate|gateway|httpd|proxy|squid|www|via/i", $agent)) {
            $proxyflg += 2048;
        }
        if (preg_match ("/cache|^dns|dummy|^ns|firewall|gate|keep|mail|^news|pop|proxy|smtp|w3|^web|www/i", $host)) {
            $proxyflg += 4096;
        }
        if ($host == $addr) {
            $proxyflg += 8192;
        }
        $realaddr = '';
        $realhost = '';
        if ( $proxyflg > 0 ) {
            $matches = array();
            if (preg_match ("/^(\d+)\.(\d+)\.(\d+)\.(\d+)/", @$_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
                $realaddr = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
            }
            elseif (preg_match ("/(\d+)\.(\d+)\.(\d+)\.(\d+)/", @$_SERVER['HTTP_FORWARDED'], $matches)) {
                $realaddr = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
            }
            elseif (preg_match ("/(\d+)\.(\d+)\.(\d+)\.(\d+)/", @$_SERVER['HTTP_VIA'], $matches)) {
                $realaddr = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
            }
            elseif (preg_match ("/(\d+)\.(\d+)\.(\d+)\.(\d+)/", @$_SERVER['HTTP_CLIENT_IP'], $matches)) {
                $realaddr = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
            }
            elseif (preg_match ("/(\d+)\.(\d+)\.(\d+)\.(\d+)/", @$_SERVER['HTTP_SP_HOST'], $matches)) {
                $realaddr = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
            }
            elseif (preg_match ("/.*\sfor\s(.+)/", @$_SERVER['HTTP_FORWARDED'], $matches)) {
                $realhost = $matches[1];
            }
            elseif (preg_match ("/\-\@(.+)/", @$_SERVER['HTTP_FROM'], $matches)) {
                $realhost = $matches[1];
            }
            if (!$realaddr and $realhost) {
                $realaddr = gethostbyname ($realhost);
            }
        }
        return array($addr, $host, $proxyflg, $realaddr, $realhost);
    }

    /**
     * Protect code generation
     *
     * @access  public
     * @param   Integer $timestamp  Timestamp
     * @param   Boolean $limithost  Whether or not to check for same host
     * @return  String  Protect code (12 alphanumeric characters)
     */
    public static function pcode($timestamp = 0, $limithost = TRUE) {
        if (!$timestamp) {
            $timestamp = CURRENT_TIME;
        }
        $ukey = 0;
        if ($limithost) {
            $remoteaddr = '0.0.0.0';
            if (@$_SERVER['REMOTE_ADDR']) {
                $remoteaddr = @$_SERVER['REMOTE_ADDR'];
            }
            $ukey = hexdec(substr(md5($remoteaddr), 0, 8));
        }

        $basecode =  dechex ($timestamp + $ukey);
        $cryptcode = crypt ($basecode . substr($GLOBALS['CONF']['ADMINPOST'], -4), substr($GLOBALS['CONF']['ADMINPOST'], -4) . $basecode);
        $cryptcode = substr (preg_replace ("/\W/", "", $cryptcode), -4);
        $pcode = dechex ($timestamp) . $cryptcode;
        return $pcode;
    }

    /**
     * Protect code verification
     *
     * @access  public
     * @param   String  $pcode  Protect code (12 alphanumeric characters)
     * @param   Boolean $limithost  Whether or not to check for same host
     * @return  Integer Timestamp
     */
    public static function pcode_verify($pcode, $limithost = TRUE) {

        if (strlen($pcode) != 12) {
            return;
        }
        $timestamphex = substr($pcode, 0, 8);
        $cryptcode = substr($pcode, 8, 4);

        $ukey = 0;
        if ($limithost) {
            $remoteaddr = '0.0.0.0';
            if (@$_SERVER['REMOTE_ADDR']) {
                $remoteaddr = @$_SERVER['REMOTE_ADDR'];
            }
            $ukey = hexdec(substr(md5($remoteaddr), 0, 8));
        }

        $timestamp = hexdec ($timestamphex);
        $basecode = dechex ($timestamp + $ukey);
        $verifycode = crypt ($basecode . substr($GLOBALS['CONF']['ADMINPOST'], -4), substr($GLOBALS['CONF']['ADMINPOST'], -4) . $basecode);
        $verifycode = substr (preg_replace ("/\W/", "", $verifycode), -4);
        if ($cryptcode != $verifycode) {
            return;
        }
        return $timestamp;
    }

    /**
     * Checkbox flag output process
     *
     * @access  public
     * @param   Integer $flag  Checkbox flag
     * @return  String  String for checkbox
     */
    public static function chkval($flag = 0, $attrvalue = FALSE) {
        if ($flag) {
            if ($attrvalue) {
                return 'checked';
            }
            else {
                return ' checked="checked"';
            }
        }
    }

    /**
     * Escaping for HTML display
     *
     * @access  public
     * @param   String  $value  Original string
     * @return  String  String after escaping process
     */
    public static function html_escape($value) {
        if ($value == '') {
            return $value;
        }
        if (!preg_match("/^\w+$/", $value)) {
            $value = htmlspecialchars($value, ENT_QUOTES);
        }
        $value = str_replace("\015\012", "\015", $value);
        $value = str_replace("\012", "\015", $value);
        // 20260801 Gikoneko: 旧コード str_replace("\015$", "", $value) を削除。
        // これは「行末のCR + 直後の $ を削除する」意図と思われるが、実際は
        // 文字列中の \015$ という2文字並び（＝前行のCRの直後に$がある箇所）
        // をすべて削除してしまい、複数行投稿の2行目以降の行頭$が消える
        // 副作用があった（LaTeX $...$ 記法が2行目以降で機能しない不具合）。
        // セキュリティ上の除去根拠は調査済みで見当たらないため撤去する。
        $value = str_replace(",", "&#44;", $value);

        return $value;
    }

    /**
     * Unescaping for HTML display
     *
     * @access  public
     * @param   String  $value  Original string
     * @return  String  String after unescaping process
     */
    public static function html_decode($value) {
        if ($value == '') {
            return $value;
        }

        if (!preg_match("/^\w+$/", $value)) {
            $value = strtr($value, array_flip(get_html_translation_table(HTML_ENTITIES)));
            $value = preg_replace_callback("/&#([0-9]+);/m", function($m){ return chr((int)$m[1]); }, $value);
        }
        return $value;
    }

    /**
     * Time format conversion
     *
     * @access  public
     * @param   Integer $timestamp  Timestamp
     * @return  String  Date string
     */
    public static function getdatestr($timestamp, $format = "") {
        if (!$format) {
            $format = "Y/m/d(-) H:i:s";
        }
        $datestr = date($format, $timestamp);
        if (strrpos($format, '-') !== FALSE) {
            static $wdays;
            if (!isset($wdays)) {
                $wdays = [ T('SUNDAY'), T('MONDAY'), T('TUESDAY'), T('WEDNESDAY'), T('THURSDAY'), T('FRIDAY'), T('SATURDAY') ];
            }
            $datestr = str_replace('-', $wdays[date("w", $timestamp)], $datestr);
        }
        return $datestr;
    }

    // Like getdatestr() but always uses the board's default language for
    // day-of-week names. Use this when generating strings written to the
    // log file, so that the stored date is independent of the visitor's
    // language setting.
    // ログに書き込まれる日付文字列用。曜日名をデフォルト言語（conf.php
    // のLANGUAGE_FILE）で出力する。TDefault()と対になるもの。
    public static function getdatestr_default($timestamp, $format = "") {
        if (!$format) {
            $format = "Y/m/d(-) H:i:s";
        }
        $datestr = date($format, $timestamp);
        if (strrpos($format, '-') !== FALSE) {
            $wdays = [ TDefault('SUNDAY'), TDefault('MONDAY'), TDefault('TUESDAY'), TDefault('WEDNESDAY'), TDefault('THURSDAY'), TDefault('FRIDAY'), TDefault('SATURDAY') ];
            $datestr = str_replace('-', $wdays[date("w", $timestamp)], $datestr);
        }
        return $datestr;
    }

    /**
     * Numeric character formatting
     *
     * @access  public
     * @param   Integer $numberstr  Original string
     * @return  String  Character string after formatting
     */
    public static function fixnumberstr($numberstr) {
        $numberstr = trim($numberstr ?? '');
        $twobytenumstr = array ('０', '１', '２', '３', '４', '５', '６', '７', '８', '９', );
        for ($i = 0; $i < count($twobytenumstr); $i++) {
            $numberstr = str_replace($twobytenumstr[$i], "$i", $numberstr);
        }
        if (is_numeric ($numberstr)) {
            return $numberstr;
        }
        else {
            return FALSE;
        }
    }

    /**
     * Escape link strings
     *
     * This process is to deal with XSS vunerabilities
     *
     * @access  public
     * @param   Integer $numberstr  Original string
     * @return  String  Character string after escaping
     */
    public static function escape_url($src_url) {
        $src_url = preg_replace("/script:/i", "script", $src_url);
        $src_url = urlencode($src_url);
        $src_url = str_replace ("%2F", "/", $src_url);
        $src_url = str_replace ("%3A", ":", $src_url);
        $src_url = str_replace ("%3D", "=", $src_url);
        $src_url = str_replace ("%23", "#", $src_url);
        $src_url = str_replace ("%26", "&", $src_url);
        $src_url = str_replace ("%3B", ";", $src_url);
        $src_url = str_replace ("%3F", "?", $src_url);
        $src_url = str_replace ("%25", "%", $src_url);

        return $src_url;
    }

    /**
     * Convert image tags to links
     *
     * @access  public
     * @param   String  $value  Original string
     * @return  String  String after tag conversion
     */
    public static function conv_imgtag ($value) {
        if ($value == '') {
            return $value;
        }
        while (preg_match("/(<a href=[^>]+>)<img ([^>]+)>(<\/a>)/i", $value, $matches)) {
            $altvalue = '';
            if (preg_match("/alt=\"([^\"]+)\"/", $matches[2], $submatches)) {
                $altvalue = $submatches[1];
            }
            elseif (preg_match("/src=\"([^\"]+)\"/", $matches[2], $submatches)) {
                $altvalue = substr($submatches[1], strrpos($submatches[1], '/'));
            }
            $value = str_replace($matches[0], " [{$matches[1]}{$altvalue}{$matches[3]}] ", $value);
        }
        return $value;
    }

    /**
     * Encoding 6-character hexidecimal strings into base64
     *
     * @access  public
     * @param   String  $inputhex  6-character hexidecimal string
     * @return  String  4-character base64 string
     */
    public static function threebytehex_base64($inputhex) {
        $inputdec = hexdec($inputhex);

        $a = floor($inputdec / 262144);
        $tmp_a = $inputdec - 262144 * $a;
        $b = floor($tmp_a / 4096);
        $tmp_b = $tmp_a - 4096 * $b;
        $c = floor($tmp_b / 64);
        $d = $tmp_b - 64 * $c;

        $basestr = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        // 20260802: floor() returns a float, and using a float as a string
        // offset emits "Warning: String offset cast occurred" on every call
        // (reachable from setcustom() when a user saves colour settings).
        // Cast to int explicitly; the values are already whole numbers so
        // the resulting characters are unchanged.
        $base64val = $basestr[(int)$a] . $basestr[(int)$b] . $basestr[(int)$c] . $basestr[(int)$d];
        return $base64val;
    }

    /**
     * Decoding base64 strings into 6-character hexidecimal
     *
     * @access  public
     * @param   String  $str  4-character base64 string
     * @return  String  6-character hexidecimal string
     */
    public static function base64_threebytehex($str) {
        if (strlen($str) != 4) {
            return '';
        }
        $basestr = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        $decval =
            262144 * @strrpos($basestr, substr($str, 0, 1))
            + 4096 * @strrpos($basestr, substr($str, 1, 1))
            + 64 * @strrpos($basestr, substr($str, 2, 1))
            + @strrpos($basestr, substr($str, 3, 1));
        $hexval = str_pad(@dechex($decval), 6, "0", STR_PAD_LEFT);
        return $hexval;
    }

    /**
     * Measure the time difference between microtime() strings
     *
     * @access  public
     * @param   String  $a  Measurement start time microtime() string
     * @param   String  $b  Measurement end time microtime() string
     * @return  String  Time difference string
     */
    public static function microtime_diff($a, $b) {
        list($a_dec, $a_sec) = explode(" ", $a);
        list($b_dec, $b_sec) = explode(" ", $b);
        return $b_sec - $a_sec + $b_dec - $a_dec;
    }

    /**
     * Get lines from file
     *
     * @access  public
     * @param   Integer $fh             File pointer
     * @param   Integer $maxbuffersize  Read buffer size
     * @return  String  Line string
     */
    public static function fgetline(&$fh, $maxbuffersize = 16000) {
        $line = '';
        do {
            $line .= fgets($fh, $maxbuffersize);
        } while (strrpos($line, "\n") === FALSE and !feof($fh));
        return strlen ($line) == 0 ? FALSE : $line;
    }


    /**
     * Check if there's an IP address in the specified IP address band
     * @param   String  $cidraddr   IP address bandwidth in CIDR format (e.g. 210.153.84.0/24)
     * @param   String  $checkaddr  IP address to check (e.g. 210.153.84.7)
     * @return  Boolean Result
     */
    public static function checkiprange($cidraddr, $checkaddr) {
        list($netaddr, $cidrmask) = explode("/", $cidraddr);
        $netaddr_long = ip2long($netaddr);
        $cidrmask = pow(2, 32 - $cidrmask) - 1;
        // 20260802: STR_PAD_LEFT was quoted as a string, which is a fatal
        // TypeError under PHP 8 (str_pad() argument #4 must be int). This
        // function currently has no call sites, so it never fired in
        // production, but it would have crashed immediately if used.
        $bits1 = str_pad(decbin($netaddr_long), 32, "0", STR_PAD_LEFT);
        $bits2 = str_pad(decbin($cidrmask), 32, "0", STR_PAD_LEFT);
        $final = '';
        for ($i = 0; $i < 32; $i++) {
            if ($bits1[$i] == $bits2[$i]) {
                $final .= $bits1[$i];
            }
            if ($bits1[$i] == 1 and $bits2[$i] == 0) {
                $final .= $bits1[$i];
            }
            if ($bits1[$i] == 0 and $bits2[$i] == 1) {
                $final .= $bits2[$i];
            }
        }
        $final_long = ip2long(long2ip(bindec($final)));
        $checkaddr_long = ip2long($checkaddr);
        if ($checkaddr_long >= $netaddr_long and $checkaddr_long <= $final_long) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    /**
     * Host name pattern list matching
     *
     * @access  public
     * @param   Array   $hostlist Host name pattern list
     * @return  Boolean Match or not
     */
    public static function hostname_match($hostlist,$hostagent) {
        if (!$hostlist or !is_array($hostlist)) {
            return;
        }
        $hit = FALSE;
        list ($addr, $host, $proxyflg, $realaddr, $realhost) = Func::getuserenv();
        $agent = @$_SERVER['HTTP_USER_AGENT'];
        foreach ($hostlist as $hostpattern) {
            foreach ($hostagent as $hostagentpattern) {
                if ((preg_match("/$hostpattern/", $host) or preg_match("/$hostpattern/", $realhost)) or preg_match("/$hostagentpattern/", $agent)) {
                    $hit = TRUE;
                    break;
                }
            }
        }
        return $hit;
    }

    /**
     * For debugging
     *
     */
    public static function debugwrite($debugstr, $printdate = TRUE, $debugfile = "debug.txt") {
        $fhdebug = @fopen($debugfile, "ab");
        if (!$fhdebug) {
            return;
        }
        flock ($fhdebug, 2);
        if ($printdate) {
            fwrite ($fhdebug, date("Y/m/d H:i:s\t (T)", CURRENT_TIME));
        }
        fwrite ($fhdebug, "$debugstr\n");
        flock ($fhdebug, 3);
        fclose ($fhdebug);
    }
}
/* end of class Func */
