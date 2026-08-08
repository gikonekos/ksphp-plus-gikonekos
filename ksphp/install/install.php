<?php

/**
 * install/install.php — KSPHP Plus セットアップ診断・導入ツール
 *
 * 【想定する設置構造】
 *   <サイトルート>/bbs.php              … 本体（導入後）
 *   <サイトルート>/install/install.php  … このファイル
 *   <サイトルート>/install/newbbs/      … 導入する新バージョン一式
 *
 * 「今アクセスしているbbs.phpがどのフォルダのものか分からなくなる」
 * 問題（2026-07-19に実際に発生）に対応するため、このファイル自身の
 * 場所から出発して本体（bbs.php）を自動検出する。
 *
 * 【導入処理の方針】
 * install/newbbs/ の内容をサイトルートへコピーする。上書きされる
 * 既存ファイルは、コピー前に必ず install/backup/YYYY-MM-DD-NN/ へ
 * 退避する（「新規仕様は旧仕様をこわさずバックアップする」の原則）。
 * newbbs/にはbbs.log・bbs.cnt・count/・log/・gikoneko_kotoba.dat等の
 * 実データファイルを含めない方針とし、導入処理もこれらのパスには
 * 一切触れない。
 *
 * 導入後、migrate.php（Migration Engine）を呼び出し、旧構成の
 * データファイルがあればdata/・logs/へ移行する。
 */

header('Content-Type: text/html; charset=UTF-8');

$install_dir  = __DIR__;
$newbbs_dir   = $install_dir . '/newbbs';
$parent_dir   = dirname($install_dir);       // 想定サイトルート
$grandparent_dir = dirname($parent_dir);     // その一つ上（近隣の旧設置検出用）

// 20260719 Gikoneko: install.php多言語化。newbbs/bbs.phpと同じ
// 単一スクリプト＋言語ファイル切り替え方式（sub/ja・sub/en分離は不採用）。
// bbs.php本体側の既定は'english'だが、この診断・導入ツールは
// 既定を'japanese'とする（利用者からの明示指定）。
function ksphp_install_load_language(string $lang): array {
	$result = array();
	$lines = @file(__DIR__ . '/language/' . $lang . '.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

$lang_options = array('english', 'japanese', 'korean', 'portuguese', 'turkish', 'zh-hans', 'zh-hant');
$lang = (string) ($_GET['lang'] ?? 'japanese');
if (!in_array($lang, $lang_options, true)) {
	$lang = 'japanese';
}
$MSG = ksphp_install_load_language($lang);
function T($key) {
	return $GLOBALS['MSG'][$key] ?? $key;
}

function ksphp_install_extract_version(string $bbs_php_path): ?string {
	$head = @file_get_contents($bbs_php_path, false, null, 0, 8192);
	if ($head === false) {
		return null;
	}
	if (preg_match('/\$CONF\[[\'"]VERSION[\'"]\]\s*=\s*\'([^\']*)\'/', $head, $m)) {
		return strip_tags($m[1]);
	}
	return null;
}

function ksphp_install_check_writable(string $dir): array {
	$created_dir = false;
	if (!is_dir($dir)) {
		$created_dir = @mkdir($dir, 0755, true);
		if (!$created_dir) {
			return array('ok' => false, 'note' => T('WRITABLE_DIR_CREATE_FAIL'));
		}
	}
	$test_file = $dir . '/.ksphp_install_writetest';
	$ok = @file_put_contents($test_file, 'test') !== false;
	if ($ok) {
		@unlink($test_file);
	}
	$note = $ok ? T('WRITABLE_OK') : T('WRITABLE_NG');
	if ($created_dir) {
		$note .= T('WRITABLE_TEST_DIR_CREATED');
	}
	return array('ok' => $ok, 'note' => $note);
}

function ksphp_install_conf_marker_score(string $conf_path): int {
	// KSPHP Plus独自の命名規則にリネームされたキー群。
	// Heyuri・古いkuzuhaphp等の別系統は、conf.phpという同名ファイルを
	// 持っていても、これらのキー名は使っていない（BBSMODE_IMAGE・
	// TXTTREE等、別の命名規則を使う）ことを実データで確認済み
	// （2026-07-19）。1個の一致は偶然の可能性もあるため、複数一致を
	// もって「同系統らしさ」の判定材料とする。
	static $markers = array(
		'TREEDISP', 'SECRETCODE', 'UPLOADIDFILE', 'MULTIPLESEARCH',
		'MAX_UPLOADSPACE', 'IMAGE_PREVIEW_RESIZE',
		'C_BRANCH', 'C_NEWMSG', 'C_QUERY', 'C_UPDATE',
	);
	$content = @file_get_contents($conf_path);
	if ($content === false) {
		return 0;
	}
	$score = 0;
	foreach ($markers as $key) {
		if (strpos($content, "'" . $key . "'") !== false) {
			$score++;
		}
	}
	return $score;
}

function ksphp_install_check_pair(string $bbs_php_path): array {
	$dir = dirname($bbs_php_path);
	$has_conf    = file_exists($dir . '/conf.php');
	$has_migrate = file_exists($dir . '/migrate.php');

	if ($has_conf && $has_migrate) {
		$verdict = T('VERDICT_KSPHP_PLUS');
	} elseif ($has_conf) {
		$score = ksphp_install_conf_marker_score($dir . '/conf.php');
		if ($score >= 2) {
			$verdict = T('VERDICT_OLD_KSPHP_PLUS');
		} else {
			$verdict = T('VERDICT_UNRELATED_CONF');
		}
	} else {
		$verdict = T('VERDICT_UNRELATED_NO_CONF');
	}

	return array('has_conf' => $has_conf, 'has_migrate' => $has_migrate, 'verdict' => $verdict);
}

function ksphp_install_list_files(string $base): array {
	$result = array();
	if (!is_dir($base)) {
		return $result;
	}
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
	);
	foreach ($iterator as $item) {
		if ($item->isFile()) {
			$rel = substr($item->getPathname(), strlen($base) + 1);
			$result[] = str_replace('\\', '/', $rel);
		}
	}
	sort($result);
	return $result;
}

/**
 * 開始位置($arr_start：array( の直後)から対応する閉じ括弧までを、
 * トップレベルの項目（カンマ区切り、複数行・入れ子配列も1項目として
 * 扱う）に分割する共通コア処理。
 * コメント（//・#・/* * /）とクォート内は構造解析から除外する。
 *
 * @return array{entries:string[], suffix:string}
 */
function ksphp_scan_array_block(string $content, int $arr_start): array {
	$len = strlen($content);
	$depth = 1;
	$pos = $arr_start;
	$cur_start = $pos;
	$entries = array();

	while ($pos < $len && $depth > 0) {
		$ch = $content[$pos];

		if ($ch === '/' && $pos + 1 < $len && $content[$pos + 1] === '/') {
			$nl = strpos($content, "\n", $pos);
			$pos = ($nl === false) ? $len : $nl + 1;
			continue;
		}
		if ($ch === '#') {
			$nl = strpos($content, "\n", $pos);
			$pos = ($nl === false) ? $len : $nl + 1;
			continue;
		}
		if ($ch === '/' && $pos + 1 < $len && $content[$pos + 1] === '*') {
			$end = strpos($content, '*/', $pos + 2);
			$pos = ($end === false) ? $len : $end + 2;
			continue;
		}

		if ($ch === "'" || $ch === '"') {
			$quote = $ch;
			$pos++;
			while ($pos < $len) {
				if ($content[$pos] === '\\') { $pos += 2; continue; }
				if ($content[$pos] === $quote) { $pos++; break; }
				$pos++;
			}
			continue;
		}

		if ($ch === '(') { $depth++; $pos++; continue; }

		if ($ch === ')') {
			$depth--;
			if ($depth === 0) {
				break;
			}
			$pos++;
			continue;
		}

		if ($ch === ',' && $depth === 1) {
			$entries[] = substr($content, $cur_start, $pos - $cur_start + 1);
			$cur_start = $pos + 1;
			$pos++;
			continue;
		}

		$pos++;
	}

	if ($cur_start < $pos && trim(substr($content, $cur_start, $pos - $cur_start)) !== '') {
		$entries[] = substr($content, $cur_start, $pos - $cur_start);
	}

	$suffix = substr($content, $pos);
	return array('entries' => $entries, 'suffix' => $suffix);
}

/**
 * '$CONF = array( ... );' 形式のPHPソースを、トップレベルの項目
 * （'KEY' => 値, の各エントリ。値が複数行・入れ子配列にまたがる場合も
 * 1エントリとして扱う）単位に分割する。
 *
 * 括弧の深度と文字列リテラル（'...' / "..."）を追跡することで、
 * HANDLENAMES のような入れ子配列（値の中にカンマや改行を含む）も
 * 正しく1エントリとして扱える。
 *
 * @return array{prefix:string, entries:string[], suffix:string}
 */
function ksphp_conf_parse_entries(string $content): array {
	if (!preg_match('/\$CONF\s*=\s*array\s*\(/', $content, $m, PREG_OFFSET_CAPTURE)) {
		return array('prefix' => $content, 'entries' => array(), 'suffix' => '');
	}
	$arr_start = $m[0][1] + strlen($m[0][0]);
	$prefix = substr($content, 0, $arr_start);
	$scan = ksphp_scan_array_block($content, $arr_start);
	return array('prefix' => $prefix, 'entries' => $scan['entries'], 'suffix' => $scan['suffix']);
}

/**
 * sub/bbsimage.php等、モジュールファイル内の
 * '$GLOBALS[\'CONF_XXX\'] = array( ... );' から 'KEY' => 値, の
 * 対応表（KEY => 生の値文字列）を取得する。
 * 旧設置のモジュールファイルから、conf.phpにまだ存在しない
 * 新規キーの実際の設定値を引き継ぐために使う。
 */
function ksphp_parse_module_array(string $content, string $global_var_name): array {
	$pattern = '/\$GLOBALS\[[\'"]' . preg_quote($global_var_name, '/') . '[\'"]\]\s*=\s*array\s*\(/';
	if (!preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
		return array();
	}
	$arr_start = $m[0][1] + strlen($m[0][0]);
	$scan = ksphp_scan_array_block($content, $arr_start);

	$values = array();
	foreach ($scan['entries'] as $entry) {
		$entry_split = ksphp_conf_entry_split_lead_comments($entry);
		if (preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>\s*(.*),(\s*)$/s', $entry_split['code'], $em)) {
			$values[$em[2]] = $em[3];
		}
	}
	return $values;
}

/**
 * conf.phpに新規追加されたキーのうち、元々は個別モジュールファイル内の
 * $GLOBALS['CONF_XXX']配列で設定されていたものについて、
 * 「モジュールファイルの相対パス」と「$GLOBALSの配列名」の対応。
 * 旧設置からのアップデート時、conf.phpにまだ無い新規キーの初期値を、
 * 汎用デフォルトではなく旧モジュールファイルの実際の設定値から
 * 引き継ぐために使う。
 */
function ksphp_legacy_module_key_source(string $key): ?array {
	static $map = null;
	if ($map === null) {
		$map = array(
			'UPLOADDIR'            => array('sub/bbsimage.php', 'CONF_IMAGEBBS'),
			'UPLOADIDFILE'         => array('sub/bbsimage.php', 'CONF_IMAGEBBS'),
			'IMAGETEXT'            => array('sub/bbsimage.php', 'CONF_IMAGEBBS'),
			'MAX_UPLOADSPACE'      => array('sub/bbsimage.php', 'CONF_IMAGEBBS'),
			'MAX_IMAGEWIDTH'       => array('sub/bbsimage.php', 'CONF_IMAGEBBS'),
			'MAX_IMAGEHEIGHT'      => array('sub/bbsimage.php', 'CONF_IMAGEBBS'),
			'MAX_IMAGESIZE'        => array('sub/bbsimage.php', 'CONF_IMAGEBBS'),
			'IMAGE_PREVIEW_RESIZE' => array('sub/bbsimage.php', 'CONF_IMAGEBBS'),
			'C_BRANCH'             => array('sub/bbstree.php', 'CONF_TREEVIEW'),
			'C_UPDATE'             => array('sub/bbstree.php', 'CONF_TREEVIEW'),
			'C_NEWMSG'             => array('sub/bbstree.php', 'CONF_TREEVIEW'),
			'TREEDISP'             => array('sub/bbstree.php', 'CONF_TREEVIEW'),
			'MULTIPLESEARCH'       => array('sub/bbslog.php', 'CONF_GETLOG'),
			'C_QUERY'              => array('sub/bbslog.php', 'CONF_GETLOG'),
			'MAXKEYWORDS'          => array('sub/bbslog.php', 'CONF_GETLOG'),
		);
	}
	return $map[$key] ?? null;
}

/**
 * 20260719 Gikoneko: 「パス指定は弄らない」方針。
 * データの保存場所を指すパス系キーは、旧設置に存在しない新規キーで
 * あっても、旧モジュールファイルからの引き継ぎ・新版テンプレートの
 * 既定値での穴埋めを行わない。導入者が意図しないパスへ実データが
 * 書かれる／過去のデータファイルと食い違う事故を避けるため、必ず
 * 空欄（要手動設定）のまま追加し、本人が明示的に指定するまで動かさない。
 * （CGIURL・INFOPAGEのような自己参照パスは対象外＝通常通り穴埋めしてよい）
 */
function ksphp_is_manual_path_key(string $key): bool {
	static $keys = array(
		'LOGFILENAME'          => true,
		'OLDLOGFILEDIR'        => true,
		'ZIPDIR'               => true,
		'COUNTFILE'            => true,
		'CNTFILENAME'          => true,
		'GIKONEKO_KOTOBA_FILE' => true,
		'UPLOADDIR'            => true,
		'UPLOADIDFILE'         => true,
	);
	return isset($keys[$key]);
}

/**
 * エントリ文字列の先頭から連続する空白・コメント（// # /* * /）を
 * 読み飛ばし、実コード（'KEY' => ...）が始まるバイト位置を返す。
 * ksphp_scan_array_block() と同じコメント判定ロジックを使う。
 *
 * 【20260801 root-cause fix】ksphp_conf_parse_entries() が返す1エントリ
 * には、直前に置かれたコメント（旧デフォルト例など）がそのまま含まれる
 * ことがある。そのコメント内に別キーの 'KEY' => パターンがあると、
 * 後段のキー抽出用正規表現（非貪欲 .*?）がコメント側を実キーと誤認識
 * していた。エントリ先頭の「コメントだけの行」を切り離してから
 * キー抽出することで、これを避ける。
 */
function ksphp_conf_entry_code_start(string $entry): int {
	$len = strlen($entry);
	$pos = 0;
	while ($pos < $len) {
		$save = $pos;
		while ($pos < $len && ctype_space($entry[$pos])) { $pos++; }
		if ($pos < $len && $entry[$pos] === '/' && $pos + 1 < $len && $entry[$pos + 1] === '/') {
			$nl = strpos($entry, "\n", $pos);
			$pos = ($nl === false) ? $len : $nl + 1;
			continue;
		}
		if ($pos < $len && $entry[$pos] === '#') {
			$nl = strpos($entry, "\n", $pos);
			$pos = ($nl === false) ? $len : $nl + 1;
			continue;
		}
		if ($pos < $len && $entry[$pos] === '/' && $pos + 1 < $len && $entry[$pos + 1] === '*') {
			$end = strpos($entry, '*/', $pos + 2);
			$pos = ($end === false) ? $len : $end + 2;
			continue;
		}
		if ($pos === $save) {
			break;
		}
	}
	return $pos;
}

/**
 * エントリ文字列を「先頭の連続コメント（そのまま保持する）」と
 * 「実コード部分（キー抽出用の正規表現に渡す部分）」に分割する。
 */
function ksphp_conf_entry_split_lead_comments(string $entry): array {
	$start = ksphp_conf_entry_code_start($entry);
	return array('lead_comments' => substr($entry, 0, $start), 'code' => substr($entry, $start));
}

/**
 * エントリの先頭コメント（ksphp_conf_entry_split_lead_comments()の
 * lead_comments）から、レビュー画面のヘルプテキストのフォールバック用
 * 原文（日本語＋英語、conf.php記述のまま）を作る。
 *
 * 【20260802】7言語翻訳（CONF_HELP_<キー名>、install/language/*.txt）を
 * 実装。この関数が返す原文は、翻訳が用意されていないキー（今後
 * conf.phpへ追加される新規キーなど）向けのフォールバックとしてのみ
 * 使われる。呼び出し側は ksphp_conf_help_text() を経由すること。
 * 罫線（#---...---）・翻訳者向けメモ（## TL note: ...）は表示から除外する。
 */
function ksphp_conf_entry_comment_text(string $lead_comments): string {
	$lines = preg_split('/\r\n|\r|\n/', $lead_comments);
	$out = array();
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || strpos($line, '##') === 0) {
			continue;
		}
		$line = preg_replace('#^/\*+\s*#', '', $line);
		$line = preg_replace('#\s*\*+/$#', '', $line);
		$line = preg_replace('#^//\s*#', '', $line);
		$line = preg_replace('/^#+\s*/', '', $line);
		$line = trim($line);
		if ($line === '' || preg_match('/^-+$/', $line) || preg_match('/^-{2,}.*-{2,}$/', $line)) {
			continue;
		}
		$out[] = $line;
	}
	return implode(' / ', $out);
}

/**
 * 【20260802 バグ修正】conf.php内の一部設定（C_A_COLOR〜C_A_HOVER・
 * C_SUBJ・C_ERROR等）は、値の直後に同一行でコメントを置くスタイル
 * （例： 'C_A_COLOR' => 'cfe', # 通常 (Normal)）を使っている。
 * ksphp_scan_array_block() はカンマ単位でエントリを分割するため、
 * この「同一行の末尾コメント」は次のエントリの生テキストの先頭に
 * 紛れ込み、ksphp_conf_entry_split_lead_comments() によって「次の
 * キーの先頭コメント」と誤認識されていた（結果、ヘルプテキストが
 * 1項目分ズレて表示される）。
 *
 * この関数は、次エントリの先頭1行が「コメントのみの行」であれば、
 * それを現エントリ自身の末尾コメントとして切り出す。
 */
function ksphp_conf_entry_trailing_comment(?string $next_entry): string {
	if ($next_entry === null) {
		return '';
	}
	$nl = strpos($next_entry, "\n");
	$first_line = ($nl === false) ? $next_entry : substr($next_entry, 0, $nl);
	$trimmed = ltrim($first_line);
	if ($trimmed === '') {
		return '';
	}
	$is_comment = ($trimmed[0] === '#') || (substr($trimmed, 0, 2) === '//');
	return $is_comment ? $trimmed : '';
}

/**
 * ksphp_conf_parse_entries()が返すエントリ配列全体から、
 * キー => ヘルプテキスト（フォールバック用原文）の対応表を作る。
 * 各エントリの先頭コメントに加え、ksphp_conf_entry_trailing_comment()
 * で拾った「自分自身の末尾コメント（次エントリ先頭から回収）」も
 * 合わせて反映する（20260802バグ修正）。
 *
 * @return array<string,string> キー => ヘルプテキスト
 */
function ksphp_conf_build_help_texts(array $entries): array {
	$n = count($entries);
	$stolen = array(); // index => 次エントリの先頭から回収した、このエントリ自身の末尾コメント原文
	for ($i = 0; $i < $n - 1; $i++) {
		$raw = ksphp_conf_entry_trailing_comment($entries[$i + 1]);
		if ($raw !== '') {
			$stolen[$i] = $raw;
		}
	}

	$out = array();
	foreach ($entries as $i => $entry) {
		if (isset($stolen[$i - 1])) {
			// 自エントリの先頭1行は前エントリの末尾コメントなので、
			// 自エントリ自身の先頭コメントとしては読み飛ばす。
			$nl = strpos($entry, "\n");
			$entry = ($nl === false) ? '' : substr($entry, $nl + 1);
		}
		$split = ksphp_conf_entry_split_lead_comments($entry);
		if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>/s', $split['code'], $m)) {
			continue;
		}
		$key = $m[2];
		$lead = ksphp_conf_entry_comment_text($split['lead_comments']);
		$trailing = isset($stolen[$i]) ? ksphp_conf_entry_comment_text($stolen[$i] . "\n") : '';
		if ($lead !== '' && $trailing !== '') {
			$out[$key] = $lead . ' / ' . $trailing;
		} else {
			$out[$key] = ($lead !== '') ? $lead : $trailing;
		}
	}
	return $out;
}

/**
 * レビュー画面に表示する、1設定項目分のヘルプテキストを返す。
 * install/language/*.txt に 'CONF_HELP_<キー名>' の翻訳があれば
 * それを使い、無ければ $fallback（conf.phpコメントの原文、日英混在）
 * をそのまま表示する（多言語UIが未整備の新規キー等への安全策）。
 */
function ksphp_conf_help_text(string $key, string $fallback): string {
	$translated = $GLOBALS['MSG']['CONF_HELP_' . $key] ?? null;
	if ($translated !== null && trim($translated) !== '') {
		return $translated;
	}
	return $fallback;
}

/**
 * 1エントリ（'KEY' => 値, ）を、値を空文字列にし、末尾に「要手動設定」
 * である旨のコメントを付けた形へ差し替える。ksphp_is_manual_path_key()
 * が対象とするキーの新規追加時に使う。
 */
function ksphp_conf_entry_blank_for_manual_setup(string $entry): string {
	$split = ksphp_conf_entry_split_lead_comments($entry);
	if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'(\s*=>\s*)(.*),(\s*)$/s', $split['code'], $m)) {
		return $entry;
	}
	return $split['lead_comments'] . $m[1] . "'" . $m[2] . "'" . $m[3] . "''" . ', // ' . T('PATH_KEY_MANUAL_NOTE') . $m[5];
}

/**
 * 1エントリ（'KEY' => 値, ）の値部分だけを、指定した生の値文字列に
 * 差し替える。リード部・トレイル部（コメント等）はそのまま残す。
 */
function ksphp_conf_entry_with_value(string $entry, string $raw_value): string {
	$split = ksphp_conf_entry_split_lead_comments($entry);
	if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'(\s*=>\s*)(.*),(\s*)$/s', $split['code'], $m)) {
		return $entry;
	}
	return $split['lead_comments'] . $m[1] . "'" . $m[2] . "'" . $m[3] . $raw_value . ',' . $m[5];
}

/**
 * 1エントリ（'KEY' => 値, ）から値部分だけを old 側の値に差し替える。
 * リード部（キー直前までのコメント等）とトレイル部（末尾コメント等）は
 * 新版（テンプレート）側のものをそのまま残す。
 */
function ksphp_conf_merge_entry(string $new_entry, ?string $old_entry): array {
	$new_split = ksphp_conf_entry_split_lead_comments($new_entry);
	if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'(\s*=>\s*)(.*),(\s*)$/s', $new_split['code'], $m)) {
		return array('text' => $new_entry, 'key' => null);
	}
	$lead = $new_split['lead_comments'] . $m[1];
	$key  = $m[2];
	$sep  = $m[3];
	$new_val = $m[4];
	$tail = $m[5];

	if ($old_entry === null) {
		return array('text' => $new_entry, 'key' => $key, 'is_new' => true);
	}
	$old_split = ksphp_conf_entry_split_lead_comments($old_entry);
	if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>\s*(.*),(\s*)$/s', $old_split['code'], $om)) {
		// 旧エントリは存在するのに値の切り出しに失敗した場合。
		// 「本当に新規キーで旧設定が無い」ケースと区別し、呼び出し側で
		// 「マージ失敗・要確認」として明示的にログへ残せるようにする。
		return array('text' => $new_entry, 'key' => $key, 'is_new' => true, 'merge_failed' => true);
	}
	$old_val = $om[3];
	$changed = ($old_val !== $new_val);
	$text = $lead . "'" . $key . "'" . $sep . $old_val . ',' . $tail;
	return array('text' => $text, 'key' => $key, 'is_new' => false, 'changed' => $changed);
}

/**
 * 新版conf.php（テンプレート）をベースに、既存の旧conf.phpの値で上書きマージする。
 * ・新版に存在し、旧版にも存在するキー → 旧版の値を採用（値が変わっていればlogに記録）
 *   値が複数行・入れ子配列（HANDLENAMES等）でも、そのエントリ全体を旧版の内容で置き換える。
 * ・新版のみに存在するキー（新規項目） → 新版の値のまま採用し、logに「新規追加」と記録
 * ・旧版のみに存在するキー（新版で廃止された項目） → 引き継がない（logに記録）
 *
 * @return array{content:string, log:array}
 */
/**
 * 新版conf.php（テンプレート）をベースに、既存の旧conf.phpの値で上書きマージする。
 * ・新版に存在し、旧版にも存在するキー → 旧版の値を採用（値が変わっていればlogに記録）
 *   値が複数行・入れ子配列（HANDLENAMES等）でも、そのエントリ全体を旧版の内容で置き換える。
 * ・新版のみに存在するキー（新規項目） →
 *   $legacy_base_dir が指定されており、かつそのキーが元々個別モジュール
 *   ファイル（sub/bbsimage.php等）の$GLOBALS['CONF_XXX']で設定されていた
 *   ものであれば、旧設置のそのモジュールファイルから実際の設定値を
 *   引き継ぐ（旧モジュールファイルが無い／該当キーが無い場合は、
 *   新版のデフォルト値のまま採用）。logに引き継ぎ元を記録。
 * ・旧版のみに存在するキー（新版で廃止された項目） → 引き継がない（logに記録）
 *
 * @return array{content:string, log:array}
 */
function ksphp_conf_merge(string $old_conf_path, string $new_template_path, ?string $legacy_base_dir = null): array {
	$new_content = @file_get_contents($new_template_path);
	$old_content = @file_get_contents($old_conf_path);
	$log = array();

	if ($new_content === false) {
		return array('content' => '', 'log' => array(array('ok' => false, 'text' => T('MERGE_TEMPLATE_READ_FAIL'))));
	}
	if ($old_content === false) {
		return array('content' => $new_content, 'log' => array());
	}

	$new_parsed = ksphp_conf_parse_entries($new_content);
	$old_parsed = ksphp_conf_parse_entries($old_content);

	if (empty($new_parsed['entries'])) {
		return array('content' => $new_content, 'log' => array());
	}

	$old_by_key = array();
	foreach ($old_parsed['entries'] as $oe) {
		$oe_split = ksphp_conf_entry_split_lead_comments($oe);
		if (preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>/s', $oe_split['code'], $om)) {
			$old_by_key[$om[2]] = $oe;
		}
	}

	// 旧モジュールファイル（sub/bbsimage.php等）のパース結果は、同じ
	// ファイルを何度も読み直さないようにキャッシュする。
	$module_values_cache = array();

	$seen = array();
	$merged_entries = array();
	foreach ($new_parsed['entries'] as $entry) {
		$entry_split = ksphp_conf_entry_split_lead_comments($entry);
		if (preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>/s', $entry_split['code'], $km)) {
			$key = $km[2];
			$seen[$key] = true;
			$old_entry = isset($old_by_key[$key]) ? $old_by_key[$key] : null;

			if ($old_entry !== null) {
				$res = ksphp_conf_merge_entry($entry, $old_entry);
				$merged_entries[] = $res['text'];
				if (!empty($res['merge_failed'])) {
					$log[] = array('ok' => false, 'text' => sprintf(T('MERGE_KEY_PARSE_FAIL'), $key));
				} elseif (!empty($res['changed'])) {
					$log[] = array('ok' => true, 'text' => sprintf(T('MERGE_KEY_KEPT'), $key));
				}
				continue;
			}

			// 新規キー：パス系キーは「パス指定は弄らない」方針により、
			// 旧モジュールファイルからの引き継ぎ・新版既定値での穴埋め
			// のいずれも行わず、必ず空欄（要手動設定）で追加する。
			if (ksphp_is_manual_path_key($key)) {
				$merged_entries[] = ksphp_conf_entry_blank_for_manual_setup($entry);
				$log[] = array('ok' => true, 'text' => sprintf(T('MERGE_KEY_PATH_MANUAL_REQUIRED'), $key));
				continue;
			}

			// 新規キー：旧モジュールファイルに実際の設定値があれば引き継ぐ。
			$legacy_val = null;
			$legacy_rel = null;
			$source = ($legacy_base_dir !== null) ? ksphp_legacy_module_key_source($key) : null;
			if ($source !== null) {
				list($legacy_rel, $legacy_var) = $source;
				if (!array_key_exists($legacy_rel, $module_values_cache)) {
					$mod_path = rtrim($legacy_base_dir, '/') . '/' . $legacy_rel;
					$mod_content = @is_file($mod_path) ? @file_get_contents($mod_path) : false;
					$module_values_cache[$legacy_rel] = ($mod_content !== false)
						? ksphp_parse_module_array($mod_content, $legacy_var)
						: array();
				}
				if (array_key_exists($key, $module_values_cache[$legacy_rel])) {
					$legacy_val = $module_values_cache[$legacy_rel][$key];
				}
			}

			if ($legacy_val !== null) {
				$merged_entries[] = ksphp_conf_entry_with_value($entry, $legacy_val);
				$log[] = array('ok' => true, 'text' => sprintf(T('MERGE_KEY_INHERITED_LEGACY'), $key, $legacy_rel));
			} else {
				$merged_entries[] = $entry;
				$log[] = array('ok' => true, 'text' => sprintf(T('MERGE_KEY_ADDED_NEW'), $key));
			}
		} else {
			$merged_entries[] = $entry;
		}
	}

	foreach ($old_by_key as $key => $oe) {
		if (!isset($seen[$key])) {
			$log[] = array('ok' => true, 'text' => sprintf(T('MERGE_KEY_DROPPED'), $key));
		}
	}

	$content = $new_parsed['prefix'] . implode('', $merged_entries) . $new_parsed['suffix'];
	return array('content' => $content, 'log' => $log);
}

/**
 * ======================================================================
 * conf.php調整確認画面（2026-08-01 Gikoneko追加）
 * ======================================================================
 * 従来のksphp_conf_merge()は全自動でconf.phpを書き換えていたが、
 * 「元ファイルの値を見ながら選択的に引き継ぎたい」という要望を受け、
 * 導入実行の前に確認・編集できる画面を挟めるようにする。
 *
 * 方針：ksphp_conf_merge()の自動マージ結果を「編集フォームの初期値」
 * として使い、フォーム未編集ならこれまでと全く同じ結果になる。
 * ユーザーが編集した項目のみ、送信された値で上書きする。
 *
 * フィールド種別の判定について：
 * 真偽値(0/1)キー・三択(0/1/2)キーは、conf.php内のコメント
 * （「#   0 : 無効」「#   1 : 有効」等の選択肢説明）を今回一度だけ
 * 目視で確認し、下記の固定リストへ手動で反映した（保守コストを
 * 抑えるための一度きりの棚卸し）。
 *
 * 当初は「値が厳密に0または1のみなら真偽値スイッチとして自動認識する」
 * フォールバックも用意していたが、SPTIME/DIFFTIME/DIFFSEC等（秒数など
 * の数値設定で、既定値がたまたま0なだけ）を誤ってチェックボックス化
 * することがテストで判明したため撤去した。手動リストに無い新規キーは、
 * 確実性を優先し安全側のtext入力欄として扱う（今後同種のキーが
 * conf.phpへ追加された場合は、リストへの追記が必要）。
 */

/**
 * 真偽値(0/1)キー一覧。値はnull（汎用ラベルCONF_BOOL_DISABLED/
 * CONF_BOOL_ENABLEDを使う）、またはarray(offラベルキー, onラベルキー)。
 *
 * 【20260801 UI改善】実機フィードバック（checkboxは分かりにくい）を受け、
 * レビュー画面では他の真偽値と同様に2択のラジオボタンとして表示する
 * （ksphp_conf_field_type()側でtype='radio'、options={'0':off,'1':on}に
 * 変換）。この一覧自体は「真偽値キーかどうか」の判定リストとしての
 * 役割は変わらない。
 */
function ksphp_conf_checkbox_keys(): array {
	static $keys = array(
		'RUNMODE'           => null,
		'BBSMODE_IMAGE'     => null,
		'ALLOW_UNDO'        => null,
		'SHOW_READNEWBTN'   => null,
		'GIKONEKO_TOISSHO'  => null,
		'GZIPU'             => null,
		'AUTOLINK'          => null,
		'UAREC'             => null,
		'IPPRINT'           => null,
		'UAPRINT'           => null,
		'COOKIE'            => null,
		'SHOW_SELFFOLLOW'   => null,
		'MULTIPLESEARCH'    => null,
		'RESTRICT_MOBILEIP' => null,
		'FOLLOWWIN'         => array('CONF_FOLLOWWIN_0', 'CONF_FOLLOWWIN_1'),
		'OLDLOGFMT'         => array('CONF_OLDLOGFMT_0', 'CONF_OLDLOGFMT_1'),
		'OLDLOGBTN'         => array('CONF_OLDLOGBTN_0', 'CONF_OLDLOGBTN_1'),
		'OLDLOGSAVESW'      => array('CONF_OLDLOGSAVESW_0', 'CONF_OLDLOGSAVESW_1'),
	);
	return $keys;
}

/** 三択(0/1/2)キー一覧。値は array(選択肢値 => ラベルキー, ...)。 */
function ksphp_conf_radio_keys(): array {
	static $keys = array(
		'BBSMODE_ADMINONLY' => array(
			'0' => 'CONF_BBSMODE_ADMINONLY_0',
			'1' => 'CONF_BBSMODE_ADMINONLY_1',
			'2' => 'CONF_BBSMODE_ADMINONLY_2',
		),
		'IPREC' => array(
			'0' => 'CONF_IPREC_0',
			'1' => 'CONF_IPREC_1',
			'2' => 'CONF_IPREC_2',
		),
	);
	return $keys;
}

/** 単純な文字列の配列（1行1要素として編集させる）キー一覧。 */
function ksphp_conf_list_keys(): array {
	static $keys = array(
		'NGWORD'               => true,
		'HOSTNAME_POSTDENIED'  => true,
		'HOSTNAME_BANNED'      => true,
		'HOSTAGENT_BANNED'     => true,
	);
	return $keys;
}

/**
 * 単純な文字列値だが複数行になり得る（textareaで編集させたい）キー一覧。
 * list_keys()と異なり配列(array(...))ではなく単一の文字列値であるため、
 * 保存処理は通常のtext型と同じ（addcslashesしてクォート）で問題ない。
 * 20260801 Gikoneko: BBSLINKが1行inputになっていた不具合の修正。
 */
function ksphp_conf_longtext_keys(): array {
	static $keys = array(
		'BBSLINK' => true,
	);
	return $keys;
}

/** 正規表現パターンのリストとして検証すべきキー一覧。 */
function ksphp_conf_regex_list_keys(): array {
	static $keys = array(
		'HOSTNAME_POSTDENIED' => true,
		'HOSTNAME_BANNED'     => true,
		'HOSTAGENT_BANNED'    => true,
	);
	return $keys;
}

/**
 * 「パス指定は弄らない」対象キー（ksphp_is_manual_path_key()）のうち、
 * conf.php自身のコメントで「空欄の場合は当該機能を無効化する」と
 * 明記されているもの一覧。これらは値が空でも正当な設定として扱い、
 * 確認画面で必須項目としない。
 *
 * 【20260801 実機フィードバック】ZIPDIRが誤って必須項目として弾かれる
 * バグを受けての対応。同じ理由でOLDLOGFILEDIR（過去ログ保存無効化）・
 * CNTFILENAME（リアルタイム参加者カウント機能無効化）もconf.php本文の
 * コメントに明記されているため対象に含めた。他の手動パス系キー
 * （LOGFILENAME・COUNTFILE・GIKONEKO_KOTOBA_FILE・UPLOADDIR・
 * UPLOADIDFILE）にはそのような記述がないため、引き続き必須のまま。
 */
function ksphp_conf_optional_manual_path_keys(): array {
	static $keys = array(
		'OLDLOGFILEDIR' => true,
		'ZIPDIR'        => true,
		'CNTFILENAME'   => true,
	);
	return $keys;
}

/** 確認画面で必須項目として扱うキー（空欄ならエラー）。 */
function ksphp_conf_is_required_key(string $key): bool {
	if ($key === 'BBSTITLE') {
		return true;
	}
	if (ksphp_is_manual_path_key($key)) {
		return !isset(ksphp_conf_optional_manual_path_keys()[$key]);
	}
	return false;
}

/**
 * エントリの生の値文字列（' => ' の右側、末尾カンマなし）から
 * フィールド種別を判定する。
 *
 * @return array{type:string, options?:array, labels?:?array}
 */
function ksphp_conf_field_type(string $key, string $raw_value): array {
	$trimmed = trim($raw_value);

	// 20260801 Gikoneko: 既存パーサー(ksphp_scan_array_block)の限界により、
	// コメントアウトされたサンプルコード（例: TMPL_MSGの後にコメントで
	// 残っているHTML例）の中に 'KEY' => のような見た目のテキストが
	// あると、その直後の本当のキー（例: HOSTNAME_POSTDENIED）がエントリ
	// 抽出時に誤って手前のダミーキーの値の一部として取り込まれてしまう
	// ケースを確認した。この画面での編集は危険（保存時に本来のキーの
	// 配列を丸ごと消してしまう）なため、値の中にさらに 'IDENT' => の
	// パターンが見つかった場合は、种別判定より先に編集対象外(raw)として
	// 弾き、これまで通り自動マージ結果のまま維持する。
	if (preg_match('/\'[A-Za-z0-9_]+\'\s*=>/', $trimmed)) {
		return array('type' => 'raw');
	}

	$checkbox_keys = ksphp_conf_checkbox_keys();
	if (array_key_exists($key, $checkbox_keys)) {
		$label_keys = $checkbox_keys[$key] ?? array('CONF_BOOL_DISABLED', 'CONF_BOOL_ENABLED');
		return array(
			'type' => 'radio',
			'options' => array(
				'0' => $label_keys[0],
				'1' => $label_keys[1],
			),
		);
	}
	$radio_keys = ksphp_conf_radio_keys();
	if (array_key_exists($key, $radio_keys)) {
		return array('type' => 'radio', 'options' => $radio_keys[$key]);
	}
	if (array_key_exists($key, ksphp_conf_list_keys())) {
		return array('type' => 'list');
	}
	if (array_key_exists($key, ksphp_conf_longtext_keys())) {
		return array('type' => 'longtext');
	}

	// 単純な文字列の並びだけの配列（入れ子・連想配列でない）はlist扱いに
	// フォールバックする。HANDLENAMES等の複雑な入れ子配列は、確実性を
	// 優先しこの画面での編集対象外(raw)として自動マージ結果のまま残す。
	if ($trimmed !== '' && (strpos($trimmed, 'array') === 0 || $trimmed[0] === '[')) {
		if (!preg_match('/=>/', $trimmed)
			&& preg_match('/^(?:array\s*\(|\[)(?:\s*\'(?:[^\'\\\\]|\\\\.)*\'\s*,?)*\s*(?:\)|\])\s*$/s', $trimmed)) {
			return array('type' => 'list');
		}
		return array('type' => 'raw');
	}

	// 20260801 Gikoneko: 当初「値が厳密に0または1のみなら真偽値スイッチ
	// として自動認識する」フォールバックを用意していたが、テストで
	// SPTIME/DIFFTIME/DIFFSEC等（秒数などの数値設定で、たまたま既定値が
	// 0なだけ）を誤ってチェックボックス化することが判明した。値だけでは
	// 真偽値かどうか判定できないため、このフォールバックは撤去する。
	// 手動リストに無い新規キーは、確実性を優先し安全側のtext入力欄
	// として扱う（誤動作より「編集しづらいだけ」の方が安全なため）。
	return array('type' => 'text');
}

/** array(...)の中身（'a','b','c'）を1行1要素のテキストへ変換する。 */
function ksphp_conf_list_raw_to_lines(string $raw): string {
	$trimmed = trim($raw);
	if (preg_match('/^array\s*\((.*)\)\s*$/s', $trimmed, $im)) {
		$body = $im[1];
	} elseif (preg_match('/^\[(.*)\]\s*$/s', $trimmed, $im)) {
		$body = $im[1];
	} else {
		$body = '';
	}
	preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'/', $body, $items);
	$lines = array();
	foreach ($items[1] as $it) {
		$lines[] = stripcslashes($it);
	}
	return implode("\n", $lines);
}

/** 1行1要素のテキスト（配列）から、conf.php用のarray(...)リテラルを組み立てる。 */
function ksphp_conf_build_list_value(array $lines): string {
	$lines = array_values(array_filter(array_map('trim', $lines), function ($l) { return $l !== ''; }));
	if (empty($lines)) {
		return "array(\n  )";
	}
	$body = '';
	foreach ($lines as $l) {
		$body .= "    '" . addcslashes($l, "'\\") . "',\n";
	}
	return "array(\n" . $body . "  )";
}

/** 確認画面のフォームへ表示する初期値文字列（表示用に整形済み）を返す。 */
function ksphp_conf_review_display_value(string $raw, string $type): string {
	$trimmed = trim($raw);
	if ($type === 'radio') {
		return trim($trimmed, "'\" \t");
	}
	if ($type === 'list') {
		return ksphp_conf_list_raw_to_lines($trimmed);
	}
	// longtextはtextと同じ単純クォート解除（type='text'とtype='longtext'は
	// 表示ウィジェットの違いのみで、値のエンコード規則は共通）。
	if ($trimmed !== '' && ($trimmed[0] === "'" || $trimmed[0] === '"') && substr($trimmed, -1) === $trimmed[0] && strlen($trimmed) >= 2) {
		return stripcslashes(substr($trimmed, 1, -1));
	}
	return $trimmed;
}

/**
 * conf.php調整確認画面用に、自動マージ結果をベースにした編集可能な
 * フィールド一覧を組み立てる。ADMINPOST/ADMINKEYは管理パスワード
 * 移行の専用フロー（4節既存仕様）で扱うため、この画面には出さない。
 *
 * @return array{fields:array, merged_content:string, log:array}
 */
function ksphp_conf_build_review(string $old_conf_path, string $new_template_path, ?string $legacy_base_dir = null): array {
	$merged = ksphp_conf_merge($old_conf_path, $new_template_path, $legacy_base_dir);
	$parsed = ksphp_conf_parse_entries($merged['content']);

	$old_by_key = array();
	$old_content = @file_get_contents($old_conf_path);
	if ($old_content !== false) {
		$old_parsed = ksphp_conf_parse_entries($old_content);
		foreach ($old_parsed['entries'] as $oe) {
			$oe_split = ksphp_conf_entry_split_lead_comments($oe);
			if (preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>/s', $oe_split['code'], $om)) {
				$old_by_key[$om[2]] = true;
			}
		}
	}

	$help_texts = ksphp_conf_build_help_texts($parsed['entries']);

	$fields = array();
	foreach ($parsed['entries'] as $entry) {
		$entry_split = ksphp_conf_entry_split_lead_comments($entry);
		if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>\s*(.*),(\s*)$/s', $entry_split['code'], $m)) {
			continue;
		}
		$key = $m[2];
		if ($key === 'ADMINPOST' || $key === 'ADMINKEY') {
			continue;
		}
		$raw = trim($m[3]);
		$type_info = ksphp_conf_field_type($key, $raw);
		if ($type_info['type'] === 'raw') {
			continue;
		}

		$options = null;
		if (isset($type_info['options'])) {
			$options = array();
			foreach ($type_info['options'] as $value => $label_key) {
				$options[] = array('value' => $value, 'label' => T($label_key));
			}
		}

		$fields[] = array(
			'key'         => $key,
			'type'        => $type_info['type'],
			'value'       => ksphp_conf_review_display_value($raw, $type_info['type']),
			'required'    => ksphp_conf_is_required_key($key),
			'is_new'      => !isset($old_by_key[$key]),
			'options'     => $options,
			'description' => ksphp_conf_help_text($key, $help_texts[$key] ?? ''),
		);
	}

	return array('fields' => $fields, 'merged_content' => $merged['content'], 'log' => $merged['log']);
}

/**
 * 確認画面でユーザーが編集した値から、実際に書き込むconf.php本文を
 * 組み立てる。$merged_content は ksphp_conf_build_review() が返した
 * 自動マージ後の内容（フォームの初期値の元）。$overrides はPOSTから
 * 受け取った「キー => 送信値」の連想配列。
 *
 * @return array{content:?string, errors:array<int,array{key:string,text:string}>}
 */
function ksphp_conf_apply_review(string $merged_content, array $overrides): array {
	$parsed = ksphp_conf_parse_entries($merged_content);
	$errors = array();
	$new_entries = array();
	$regex_list_keys = ksphp_conf_regex_list_keys();

	foreach ($parsed['entries'] as $entry) {
		$entry_split = ksphp_conf_entry_split_lead_comments($entry);
		if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>\s*(.*),(\s*)$/s', $entry_split['code'], $m)) {
			$new_entries[] = $entry;
			continue;
		}
		$key = $m[2];
		$current_raw = trim($m[3]);

		if ($key === 'ADMINPOST' || $key === 'ADMINKEY' || !array_key_exists($key, $overrides)) {
			$new_entries[] = $entry;
			continue;
		}

		$field = ksphp_conf_field_type($key, $current_raw);
		$submitted = $overrides[$key];
		$required = ksphp_conf_is_required_key($key);

		if ($field['type'] === 'radio') {
			// PHPは連想配列の'0'/'1'/'2'のような数値文字列キーを自動的に
			// 整数キーへ変換するため、array_keys()の結果は文字列ではなく
			// 整数になる。strict比較のため文字列へ揃えてから照合する。
			$options = array_map('strval', array_keys($field['options']));
			if (!in_array((string) $submitted, $options, true)) {
				$errors[] = array('key' => $key, 'text' => sprintf(T('CONF_REVIEW_ERROR_RADIO'), $key));
				$new_entries[] = $entry;
				continue;
			}
			$new_entries[] = ksphp_conf_entry_with_value($entry, (string) $submitted);
			continue;
		}

		if ($field['type'] === 'list') {
			$lines = preg_split('/\r\n|\r|\n/', (string) $submitted);
			$lines = array_values(array_filter(array_map('trim', $lines), function ($l) { return $l !== ''; }));
			if (isset($regex_list_keys[$key])) {
				foreach ($lines as $li => $pattern) {
					if (@preg_match('#' . str_replace('#', '\#', $pattern) . '#', '') === false) {
						$errors[] = array('key' => $key, 'text' => sprintf(T('CONF_REVIEW_ERROR_INVALID_REGEX'), $key, $li + 1));
					}
				}
			}
			if ($required && empty($lines)) {
				$errors[] = array('key' => $key, 'text' => sprintf(T('CONF_REVIEW_ERROR_REQUIRED'), $key));
			}
			$new_entries[] = ksphp_conf_entry_with_value($entry, ksphp_conf_build_list_value($lines));
			continue;
		}

		// text / longtext（両者とも同じクォート＆保存処理。差はUIのみ）
		$submitted_str = (string) $submitted;
		if ($required && trim($submitted_str) === '') {
			$errors[] = array('key' => $key, 'text' => sprintf(T('CONF_REVIEW_ERROR_REQUIRED'), $key));
			$new_entries[] = $entry;
			continue;
		}
		$was_bare_number = (bool) preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $current_raw);
		// 20260801 Gikoneko: MAXOLDLOGSIZE/MAXMSGSIZE等、conf.php上では
		// `4 * 1024 * 1024`のような数値のみで構成される演算式として
		// 書かれているキーへの対応。$was_bare_numberは単純な数値リテラル
		// のみを対象にしており、演算式はこの判定を素通りしてelse節の
		// クォート付き文字列化に落ちてしまう（実際にMAXMSGSIZEで発生し、
		// 保存後に文字列×数値の演算でPHP Warningが発生した）。
		// 数字・演算子・空白のみで構成される式かどうかを別途判定し、
		// 該当する場合はクォート無しで出力する。
		$was_numeric_expr = (bool) preg_match('/^[0-9\s\.\+\-\*\/\(\)]+$/', $current_raw)
			&& preg_match('/[0-9]/', $current_raw);
		if ($was_bare_number) {
			if (trim($submitted_str) !== '' && !is_numeric(trim($submitted_str))) {
				$errors[] = array('key' => $key, 'text' => sprintf(T('CONF_REVIEW_ERROR_NUMERIC'), $key));
				$new_entries[] = $entry;
				continue;
			}
			$raw = (trim($submitted_str) === '') ? $current_raw : trim($submitted_str);
			$new_entries[] = ksphp_conf_entry_with_value($entry, $raw);
		} elseif ($was_numeric_expr) {
			// フォームは常にtext入力欄なので、編集された場合は送信値
			// (通常は数値そのもの)を使う。未編集(値が空欄で戻ってきた
			// 場合等)は元の演算式をそのまま維持する。
			$submitted_trim = trim($submitted_str);
			if ($submitted_trim !== '' && preg_match('/^[0-9\s\.\+\-\*\/\(\)]+$/', $submitted_trim)) {
				$raw = $submitted_trim;
			} else {
				$raw = $current_raw;
			}
			$new_entries[] = ksphp_conf_entry_with_value($entry, $raw);
		} else {
			$raw = "'" . addcslashes($submitted_str, "'\\") . "'";
			$new_entries[] = ksphp_conf_entry_with_value($entry, $raw);
		}
	}

	if (!empty($errors)) {
		return array('content' => null, 'errors' => $errors);
	}

	$content = $parsed['prefix'] . implode('', $new_entries) . $parsed['suffix'];
	return array('content' => $content, 'errors' => array());
}


/**
 * 20260719 Gikoneko: 導入先パスの安全ガード（最終防衛線）。
 *
 * 呼び出し元（近隣スキャン・新規フォルダ追加のいずれも）のロジックが
 * 正しくても、install.php自体が想定外に浅い場所（ファイルシステム
 * ルート近く）に設置されている等の環境依存の事情で、导入先が
 * ファイルシステムルートや浅すぎる場所に解決されてしまう可能性を
 * 排除できない。過去に「ルートフォルダに設置すると全削除される」
 * 事故が実際に起きたことを踏まえ、ksphp_install_run()に入る直前で
 * 必ずこのチェックを通す。
 *
 * 拒否する条件：
 *   - 空文字列・"/"・"."
 *   - パスの深さが1階層以下（例："/var"）
 *   - install/フォルダ自身、またはその配下（インストーラー自身を
 *     上書きしてしまうため）。前方一致だけだと"install2"のような
 *     別フォルダを誤検出するため、区切り単位で比較する。
 */
function ksphp_install_is_safe_target_dir(string $dir, string $install_dir): bool {
	if ($dir === '' || $dir === '/' || $dir === '.') {
		return false;
	}
	$trimmed = trim($dir, '/');
	if ($trimmed === '' || strpos($trimmed, '/') === false) {
		return false;
	}
	$dir_norm = rtrim($dir, '/');
	$install_norm = rtrim($install_dir, '/');
	if ($dir_norm === $install_norm || strpos($dir_norm, $install_norm . '/') === 0) {
		return false;
	}
	return true;
}

/**
 * 20260719 Gikoneko: バックアップ・退避処理の失敗は「処理を止めるのは
 * もったいない」ためスキップして続行する方針だが、そのままだと
 * ブラウザ上のログ（閉じたら消える）にしか残らず、後から気づかれずに
 * 埋もれてしまう危険がある。install/backup/install-errors-YYYY-MM-DD.txt
 * に追記形式（複数回の実行分を蓄積）で永続的に記録する保険を設ける。
 */
function ksphp_install_log_error(string $install_dir, string $message): void {
	$dir = $install_dir . '/backup';
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	$path = $dir . '/install-errors-' . gmdate('Y-m-d') . '.txt';
	$line = gmdate('Y-m-d\TH:i:s\Z') . ' ' . $message . "\n";
	@file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

/**
 * 20260720 Gikoneko: バージョンアップ移行時の管理パスワード検証用。
 * 指定conf.phpをサンドボックス的に読み込み、ADMINPOST/ADMINKEYの
 * 生値を返す（無ければ空文字）。ksphp_install_read_conf_summary()と
 * 同じ「クロージャ内include」方式を使う。
 */
function ksphp_install_read_admin_secret(string $conf_path): array {
	if (!@file_exists($conf_path)) {
		return array('ADMINPOST' => '', 'ADMINKEY' => '');
	}
	$loader = function () use ($conf_path) {
		$CONF = array();
		include $conf_path;
		return is_array($CONF) ? $CONF : array();
	};
	$conf = $loader();
	return array(
		'ADMINPOST' => (string) ($conf['ADMINPOST'] ?? ''),
		'ADMINKEY'  => (string) ($conf['ADMINKEY'] ?? ''),
	);
}

/**
 * 20260720 Gikoneko: local.php書き込み。newbbs/_setup.phpの
 * ksphp_setup_write_secrets()と同一フォーマット（bbs.php側の
 * requireでの読み込み方式に合わせる）。install.php側にも同じ
 * ロジックを持つ（_setup.phpとは独立したツールのため関数の共有はしない）。
 */
function ksphp_install_write_local_secrets(string $path, string $adminpost, string $adminkey): bool {
	$content = "<?php\n\n"
		. "// " . gmdate('Y-m-d\TH:i:s\Z') . " install.php: admin password migrated from\n"
		. "// the previous conf.php-based ADMINPOST during an upgrade. Not part of\n"
		. "// the newbbs/ template; install.php's file scan never overwrites this file.\n"
		. "return array(\n"
		. "    'ADMINPOST' => " . var_export($adminpost, true) . ",\n"
		. "    'ADMINKEY' => " . var_export($adminkey, true) . ",\n"
		. ");\n";
	$result = @file_put_contents($path, $content, LOCK_EX);
	return $result !== false;
}

function ksphp_install_run(string $newbbs_dir, string $parent_dir, string $backup_root, string $entry_filename = 'bbs.php', string $old_admin_pass = '', string $new_admin_pass = '', ?array $conf_overrides = null, bool $keep_admin_pass = false): array {
	$log = array();

	// newbbs_dir は常に {install_dir}/newbbs なので、ここから install_dir を逆算する。
	$install_dir_check = dirname($newbbs_dir);
	if (!ksphp_install_is_safe_target_dir($parent_dir, $install_dir_check)) {
		$log[] = array('ok' => false, 'text' => T('INSTALL_UNSAFE_TARGET'));
		ksphp_install_log_error($install_dir_check, "unsafe target rejected: $parent_dir");
		return $log;
	}

	// 20260720 Gikoneko: 管理パスワード移行チェック（4節仕様）。
	// ファイル設置・バックアップより前、まだ何も変更していない時点で
	// 旧conf.php（存在すれば）のADMINPOSTを確認する。非空＝バージョン
	// アップ移行ケース。ここでの認証失敗は「乗っ取り試行の可能性」として
	// ファイル設置自体を含めインストール全体を中止する（基さん指示）。
	$admin_migration = null;
	$old_secret = ksphp_install_read_admin_secret($parent_dir . '/conf.php');
	if ($old_secret['ADMINPOST'] !== '') {
		if (!$keep_admin_pass && $new_admin_pass === '') {
			$log[] = array('ok' => false, 'text' => T('ADMIN_MIGRATION_REQUIRED'));
			ksphp_install_log_error($install_dir_check, "admin migration required but no new password submitted: $parent_dir");
			return $log;
		}
		if (crypt($old_admin_pass, $old_secret['ADMINPOST']) !== $old_secret['ADMINPOST']) {
			$log[] = array('ok' => false, 'text' => T('ADMIN_MIGRATION_AUTH_FAIL'));
			ksphp_install_log_error($install_dir_check, "admin migration auth failed, aborting entire install: $parent_dir");
			return $log;
		}
		if ($keep_admin_pass) {
			// 旧パスワードをそのまま継続する：旧ハッシュを引き継ぐ。
			// Keep existing password: reuse the old hash as-is.
			$admin_migration = array(
				'hash' => $old_secret['ADMINPOST'],
				'key'  => $old_secret['ADMINKEY'],
			);
		} else {
			$admin_migration = array(
				'hash' => password_hash($new_admin_pass, PASSWORD_BCRYPT),
				// ADMINKEYは平文のまま旧設定を引き継ぐ（基さん指示、入力欄は設けない）。
				'key'  => $old_secret['ADMINKEY'],
			);
		}
	}

	$files = ksphp_install_list_files($newbbs_dir);

	if (empty($files)) {
		$log[] = array('ok' => false, 'text' => T('INSTALL_NO_FILES'));
		ksphp_install_log_error($install_dir_check, "no files under $newbbs_dir");
		return $log;
	}

	if ($entry_filename !== 'bbs.php') {
		$log[] = array('ok' => true, 'text' => sprintf(T('INSTALL_ENTRY_RENAMED'), $entry_filename, $entry_filename));
	}

	$date = gmdate('Y-m-d');
	$n = 1;
	do {
		$backup_dir = $backup_root . '/' . $date . '-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
		$n++;
	} while (file_exists($backup_dir));

	$any_existing = false;
	foreach ($files as $rel) {
		$dst_rel = ($rel === 'bbs.php') ? $entry_filename : $rel;
		if (file_exists($parent_dir . '/' . $dst_rel)) {
			$any_existing = true;
			break;
		}
	}

	if ($any_existing) {
		if (!@mkdir($backup_dir, 0755, true)) {
			$log[] = array('ok' => false, 'text' => T('INSTALL_BACKUP_DIR_FAIL'));
			ksphp_install_log_error($install_dir_check, "backup root mkdir failed: $backup_dir");
			return $log;
		}
		$log[] = array('ok' => true, 'text' => sprintf(T('INSTALL_BACKUP_DIR'), $backup_dir));
	}

	foreach ($files as $rel) {
		$dst_rel = ($rel === 'bbs.php') ? $entry_filename : $rel;
		$src = $newbbs_dir . '/' . $rel;
		$dst = $parent_dir . '/' . $dst_rel;
		$dst_dir = dirname($dst);
		$existed = file_exists($dst);

		// conf.php以外で既存ファイルと内容が同一なら上書き・バックアップをスキップ。
		// SHA-256ハッシュで比較することで、改行コード変換等の影響を受けず
		// 正確に同一性を判定する。この時点では$dstはまだ元ファイルのままなので
		// 戻す処理が不要。
		// Skip overwrite and backup for non-conf.php files that are identical
		// to the installed version. SHA-256 comparison avoids false negatives
		// caused by line-ending conversion (e.g. CRLF vs LF on download).
		if ($existed && $rel !== 'conf.php') {
			$src_hash = @hash_file('sha256', $src);
			$dst_hash = @hash_file('sha256', $dst);
			if ($src_hash !== false && $dst_hash !== false && $src_hash === $dst_hash) {
				$log[] = array('ok' => true, 'skipped' => true, 'text' => sprintf(T('INSTALL_SKIPPED_UNCHANGED'), $dst_rel));
				continue;
			}
		}

		if (!is_dir($dst_dir)) {
			@mkdir($dst_dir, 0755, true);
		}

		$backup_target = null;
		if ($existed) {
			$backup_target = $backup_dir . '/' . $dst_rel;
			$backup_target_dir = dirname($backup_target);
			if (!is_dir($backup_target_dir) && !@mkdir($backup_target_dir, 0755, true)) {
				$log[] = array('ok' => false, 'text' => sprintf(T('INSTALL_BACKUP_MKDIR_FAIL'), $dst_rel));
				ksphp_install_log_error($install_dir_check, "backup subfolder mkdir failed: $backup_target_dir (file: $dst_rel)");
				continue;
			}
			// 20260719 Gikoneko: copy()ではなくrename()で退避する。rename()は
			// 同一ファイルシステム内であれば原子的（atomic）に行われるため、
			// 「バックアップが完了していないのに元ファイルが失われる」という
			// 中間状態が起こり得ない：成功すれば退避完了・元の場所には何も
			// 残らない、失敗すれば元ファイルはそのまま残る（部分的に消えたり
			// しない）。失敗時はこのファイルの導入だけをスキップし、全体は
			// 中断せず次のファイルへ進む。
			if (!@rename($dst, $backup_target)) {
				$log[] = array('ok' => false, 'text' => sprintf(T('INSTALL_SKIP_BACKUP_FAIL'), $dst_rel));
				ksphp_install_log_error($install_dir_check, "backup rename failed: $dst -> $backup_target");
				continue;
			}
		}

		// conf.phpは設定ファイルのため、既存があれば単純上書きせず、
		// 項目ごとに旧設定値を維持しつつ新版の新規項目のみ追記するマージを行う。
		// 新規項目については、旧設置のモジュールファイル（sub/bbsimage.php等、
		// この時点ではまだ上書きされていない）から実際の設定値があれば引き継ぐ。
		if ($rel === 'conf.php' && $existed && $backup_target !== null) {
			$merged = ksphp_conf_merge($backup_target, $src, $parent_dir);
			$final_content = $merged['content'];

			// 20260801 Gikoneko: 確認画面で編集された値があれば、自動マージ
			// 結果の上にユーザーの入力を適用する。バリデーションエラーが
			// あれば、conf.php単体の失敗として扱い（他ファイルの導入は
			// 継続）、退避済みの旧conf.phpをその場に戻す。
			if ($conf_overrides !== null) {
				$applied = ksphp_conf_apply_review($merged['content'], $conf_overrides);
				if (!empty($applied['errors'])) {
					foreach ($applied['errors'] as $err) {
						$log[] = array('ok' => false, 'text' => $err['text']);
					}
					ksphp_install_log_error($install_dir_check, "conf review validation failed: $dst");
					if (@rename($backup_target, $dst)) {
						$log[] = array('ok' => true, 'text' => sprintf(T('INSTALL_ROLLBACK_OK'), $dst_rel));
					} else {
						$log[] = array('ok' => false, 'text' => sprintf(T('INSTALL_ROLLBACK_FAIL'), $backup_target));
						ksphp_install_log_error($install_dir_check, "rollback rename failed: $backup_target -> $dst");
					}
					continue;
				}
				$final_content = $applied['content'];
			}

			if (@file_put_contents($dst, $final_content) !== false) {
				$log[] = array('ok' => true, 'text' => sprintf(T('INSTALL_CONF_MERGED'), $dst_rel));
				foreach ($merged['log'] as $entry) {
					$log[] = $entry;
				}
			} else {
				$log[] = array('ok' => false, 'text' => sprintf(T('INSTALL_CONF_WRITE_FAIL'), $dst_rel));
				ksphp_install_log_error($install_dir_check, "conf merge write failed: $dst");
				// 20260719 Gikoneko: 新版の書き込みに失敗した場合、退避済みの
				// 元ファイルをその場に戻す（ベストエフォートの自動ロール
				// バック）。これにより「退避はできたが新版が書けず、結局
				// 元ファイルも無くなる」という最悪の状態を避ける。
				if (@rename($backup_target, $dst)) {
					$log[] = array('ok' => true, 'text' => sprintf(T('INSTALL_ROLLBACK_OK'), $dst_rel));
				} else {
					$log[] = array('ok' => false, 'text' => sprintf(T('INSTALL_ROLLBACK_FAIL'), $backup_target));
					ksphp_install_log_error($install_dir_check, "rollback rename failed: $backup_target -> $dst");
				}
			}
			continue;
		}

		if (@copy($src, $dst)) {
			$log[] = array('ok' => true, 'text' => sprintf(T('INSTALL_COPIED'), $dst_rel));
		} else {
			$log[] = array('ok' => false, 'text' => sprintf(T('INSTALL_COPY_FAIL'), $dst_rel));
			ksphp_install_log_error($install_dir_check, "copy failed: $src -> $dst");
			if ($backup_target !== null) {
				if (@rename($backup_target, $dst)) {
					$log[] = array('ok' => true, 'text' => sprintf(T('INSTALL_ROLLBACK_OK'), $dst_rel));
				} else {
					$log[] = array('ok' => false, 'text' => sprintf(T('INSTALL_ROLLBACK_FAIL'), $backup_target));
					ksphp_install_log_error($install_dir_check, "rollback rename failed: $backup_target -> $dst");
				}
			}
		}
	}

	$migrate_file = $parent_dir . '/migrate.php';
	if (file_exists($migrate_file)) {
		require_once $migrate_file;
		if (function_exists('ksphp_migrate')) {
			ksphp_migrate();
			$log[] = array('ok' => true, 'text' => T('INSTALL_MIGRATE_DONE'));
		}
	}

	// 20260720 Gikoneko: 認証済みの管理パスワード移行があれば、ここで
	// local.phpを書き込む。conf.php側の旧ADMINPOST値はconf-mergeで
	// そのまま引き継がれた状態を維持し、あえて空文字化しない
	// （基さん指示：ダミーとして残す。local.phpの有無のみが判定基準）。
	if ($admin_migration !== null) {
		$local_path = $parent_dir . '/local.php';
		if (ksphp_install_write_local_secrets($local_path, $admin_migration['hash'], $admin_migration['key'])) {
			$log[] = array('ok' => true, 'text' => T('ADMIN_MIGRATION_DONE'));
		} else {
			$log[] = array('ok' => false, 'text' => T('ADMIN_MIGRATION_WRITE_FAIL'));
			ksphp_install_log_error($install_dir_check, "local.php write failed during migration: $local_path");
		}
	}

	$log[] = array('ok' => true, 'text' => T('INSTALL_DONE'));
	return $log;
}

/**
 * 指定ディレクトリ直下から「本体らしきPHPファイル」を探す。
/**
 * ファイル名がbbs.phpであればまず採用するが、diary.php等に
 * リネームされているケースに備え、直下のPHPファイルの中身を
 * 軽く確認し、$CONF['VERSION']の特徴的な記述があればそれも候補に
 * 加える。
 *
 * $depth > 0 の場合、直下のサブディレクトリを $depth 段階まで
 * 再帰的に確認する（例：public_html/z/ のような1階層下の設置を
 * 検出するため）。ただし、データ量が多くなりがちなフォルダ
 * （upload/archive/log/logs/data/backup等）や隠しフォルダは
 * 除外し、無制限に広く・深く走査してリソースを消費しないようにする。
 *
 * ディレクトリによってはopen_basedir制限等で読み取れない場合が
 * あるため、is_file()/is_dir()の警告は抑制する（@付き）。
 */
function ksphp_install_scan_dir_for_bbs(string $dir, int $depth = 0): array {
	static $skip_names = array(
		'upload', 'archive', 'log', 'logs', 'data', 'backup',
		'install', 'node_modules', '.git', 'vendor', 'cache',
		'count', 'awstats',
	);

	$found = array();
	if (!@is_dir($dir)) {
		return $found;
	}

	$bbs_php = $dir . '/bbs.php';
	if (@file_exists($bbs_php)) {
		$found[] = $bbs_php;
	}

	$entries = @scandir($dir);
	if ($entries === false) {
		return $found;
	}
	foreach ($entries as $entry) {
		if ($entry === '.' || $entry === '..' || $entry === 'bbs.php') {
			continue;
		}
		$path = $dir . '/' . $entry;

		if (@is_file($path)) {
			if (substr($entry, -4) === '.php' && ksphp_install_extract_version($path) !== null) {
				$found[] = $path;
			}
			continue;
		}

		if ($depth > 0 && @is_dir($path) && substr($entry, 0, 1) !== '.'
			&& !in_array(strtolower($entry), $skip_names, true)
		) {
			foreach (ksphp_install_scan_dir_for_bbs($path, $depth - 1) as $p) {
				$found[] = $p;
			}
		}
	}
	return $found;
}

function ksphp_install_find_candidates(string $parent_dir, string $grandparent_dir): array {
	$candidates = array();

	// parent_dir（例：public_html）配下は1階層下（例：public_html/z/）まで確認する。
	foreach (ksphp_install_scan_dir_for_bbs($parent_dir, 1) as $p) {
		if (!in_array($p, $candidates, true)) {
			$candidates[] = $p;
		}
	}

	if (@is_dir($grandparent_dir)) {
		foreach (ksphp_install_scan_dir_for_bbs($grandparent_dir) as $p) {
			if (!in_array($p, $candidates, true)) {
				$candidates[] = $p;
			}
		}
		$entries = @scandir($grandparent_dir);
		if ($entries !== false) {
			foreach ($entries as $entry) {
				if ($entry === '.' || $entry === '..') {
					continue;
				}
				$sub = $grandparent_dir . '/' . $entry;
				if (!@is_dir($sub)) {
					continue;
				}
				foreach (ksphp_install_scan_dir_for_bbs($sub) as $p) {
					if (!in_array($p, $candidates, true)) {
						$candidates[] = $p;
					}
				}
			}
		}
	}

	return $candidates;
}

/**
 * 20260719 Gikoneko: 新規インストール先フォルダ選択機能。
 *
 * ユーザーがテキスト入力欄で指定した相対パスを$grandparent_dir基準で
 * 解決する。「/」区切りの各セグメントが空・「.」・「..」でないことを
 * 確認するだけで、ディレクトリトラバーサル（例："../../etc"）を
 * 構造的に排除できる（realpath()は対象がまだ存在しない新規フォルダ
 * では使えないため、この方式を採用）。絶対パス・NULLバイトも拒否する。
 *
 * @return string|null 解決できた絶対パス。不正な入力の場合はnull。
 */
function ksphp_install_validate_new_dir(string $grandparent_dir, string $relative, string $install_dir): ?string {
	if ($relative === '' || strpos($relative, "\0") !== false) {
		return null;
	}
	// 絶対パス（先頭が"/"、またはWindows形式のドライブレター）は拒否
	if ($relative[0] === '/' || preg_match('/^[A-Za-z]:/', $relative)) {
		return null;
	}
	$relative = str_replace('\\', '/', $relative);
	$segments = explode('/', $relative);
	foreach ($segments as $seg) {
		if ($seg === '' || $seg === '.' || $seg === '..') {
			return null;
		}
	}
	$resolved = rtrim($grandparent_dir, '/') . '/' . implode('/', $segments);
	if (!ksphp_install_is_safe_target_dir($resolved, $install_dir)) {
		return null;
	}
	return $resolved;
}

// 20260801 Gikoneko: conf.php調整確認画面用。実際のファイル書き込みは
// 一切行わず、自動マージ結果を編集可能なフィールド一覧として返すだけ。
/** POSTで受け取ったconf_overrides（JSON文字列）を安全にデコードする。 */
function ksphp_install_decode_conf_overrides(string $raw): ?array {
	if ($raw === '') {
		return null;
	}
	$decoded = json_decode($raw, true);
	return is_array($decoded) ? $decoded : null;
}

if (($_GET['ajax'] ?? '') === '1' && ($_GET['action'] ?? '') === 'conf_review') {
	header('Content-Type: application/json; charset=UTF-8');
	$kind = (string) ($_GET['kind'] ?? 'index');
	if ($kind === 'new') {
		$new_dir = ksphp_install_validate_new_dir($grandparent_dir, (string) ($_GET['dir'] ?? ''), $install_dir);
		if ($new_dir === null) {
			echo json_encode(array('needed' => false), JSON_UNESCAPED_UNICODE);
			exit;
		}
		$old_conf = $new_dir . '/conf.php';
	} else {
		// パスを直接受け取り、realpathで正規化してからスキャン済みリストと照合する。
		// realpathを通さないと文字列不一致でin_arrayが失敗する場合がある。
		$bbs_path_raw = (string) ($_GET['path'] ?? '');
		$bbs_path = $bbs_path_raw !== '' ? (@realpath($bbs_path_raw) ?: $bbs_path_raw) : '';
		$targets = ksphp_install_find_candidates($parent_dir, $grandparent_dir);
		$targets_real = array_map(function($t) { return @realpath($t) ?: $t; }, $targets);
		if ($bbs_path === '' || !in_array($bbs_path, $targets_real, true)) {
			echo json_encode(array('needed' => false), JSON_UNESCAPED_UNICODE);
			exit;
		}
		$old_conf = dirname($bbs_path) . '/conf.php';
	}
	if (!@file_exists($old_conf)) {
		echo json_encode(array('needed' => false), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$review = ksphp_conf_build_review($old_conf, $newbbs_dir . '/conf.php', dirname($old_conf));
	echo json_encode(array('needed' => true, 'fields' => $review['fields']), JSON_UNESCAPED_UNICODE);
	exit;
}

if (($_GET['ajax'] ?? '') === '1' && ($_GET['action'] ?? '') === 'run_setup_new') {
	header('Content-Type: application/json; charset=UTF-8');
	$new_dir = ksphp_install_validate_new_dir($grandparent_dir, (string) ($_GET['dir'] ?? ''), $install_dir);
	if ($new_dir === null) {
		echo json_encode(array('log' => array(array('ok' => false, 'text' => T('ERROR_INVALID_PATH')))), JSON_UNESCAPED_UNICODE);
		exit;
	}
	// 20260720 Gikoneko: 管理パスワード移行用の値はGETクエリ文字列
	// （アクセスログに残る）ではなくPOSTボディで受け取る。新規追加分
	// （fresh install）では通常不要だが、同名の仕組みを一応通しておく。
	$old_admin_pass = (string) ($_POST['old_admin_pass'] ?? '');
	$new_admin_pass = (string) ($_POST['new_admin_pass'] ?? '');
	$keep_admin_pass = ($_POST['keep_admin_pass'] ?? '') === '1';
	$conf_overrides = ksphp_install_decode_conf_overrides((string) ($_POST['conf_overrides'] ?? ''));
	$backup_root = $install_dir . '/backup/new_' . substr(md5($new_dir), 0, 12);
	echo json_encode(
		array(
			'log' => ksphp_install_run($newbbs_dir, $new_dir, $backup_root, 'bbs.php', $old_admin_pass, $new_admin_pass, $conf_overrides, $keep_admin_pass),
			'entry_filename' => 'bbs.php',
		),
		JSON_UNESCAPED_UNICODE
	);
	exit;
}

if (($_GET['ajax'] ?? '') === '1' && ($_GET['action'] ?? '') === 'run_setup') {
	header('Content-Type: application/json; charset=UTF-8');
	// パスを直接受け取り、realpathで正規化してからスキャン済みリストと照合する。
	// realpathを通さないと文字列不一致でin_arrayが失敗する場合がある。
	$bbs_path_raw = (string) ($_GET['path'] ?? '');
	$bbs_path = $bbs_path_raw !== '' ? (@realpath($bbs_path_raw) ?: $bbs_path_raw) : '';
	$targets = ksphp_install_find_candidates($parent_dir, $grandparent_dir);
	$targets_real = array_map(function($t) { return @realpath($t) ?: $t; }, $targets);
	if ($bbs_path === '' || !in_array($bbs_path, $targets_real, true)) {
		echo json_encode(array('log' => array(array('ok' => false, 'text' => T('ERROR_TARGET_NOT_FOUND')))), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$target_dir = dirname($bbs_path);
	$entry_filename = basename($bbs_path);
	$old_admin_pass = (string) ($_POST['old_admin_pass'] ?? '');
	$new_admin_pass = (string) ($_POST['new_admin_pass'] ?? '');
	$keep_admin_pass = ($_POST['keep_admin_pass'] ?? '') === '1';
	$conf_overrides = ksphp_install_decode_conf_overrides((string) ($_POST['conf_overrides'] ?? ''));
	$backup_root = $install_dir . '/backup/' . substr(md5($bbs_path), 0, 12);
	echo json_encode(
		array(
			'log' => ksphp_install_run($newbbs_dir, $target_dir, $backup_root, $entry_filename, $old_admin_pass, $new_admin_pass, $conf_overrides, $keep_admin_pass),
			'entry_filename' => $entry_filename,
		),
		JSON_UNESCAPED_UNICODE
	);
	exit;
}

if (($_GET['ajax'] ?? '') === '1' && ($_GET['action'] ?? '') === 'status') {
	header('Content-Type: application/json; charset=UTF-8');
	$targets = ksphp_install_find_candidates($parent_dir, $grandparent_dir);
	$idx = (int) ($_GET['target'] ?? 0);
	$target_dir = isset($targets[$idx]) ? dirname($targets[$idx]) : $parent_dir;
	$w_root = ksphp_install_check_writable($target_dir);
	$w_data = ksphp_install_check_writable($target_dir . '/data');
	$w_logs = ksphp_install_check_writable($target_dir . '/logs');
	$marker = $target_dir . '/data/.migrated';
	echo json_encode(array(
		'write_root' => $w_root,
		'write_data' => $w_data,
		'write_logs' => $w_logs,
		'migrated'   => file_exists($marker) ? trim((string) @file_get_contents($marker)) : null,
	), JSON_UNESCAPED_UNICODE);
	exit;
}

$candidates = ksphp_install_find_candidates($parent_dir, $grandparent_dir);

$newbbs_files = ksphp_install_list_files($newbbs_dir);

$write_root   = ksphp_install_check_writable($parent_dir);
$write_backup = ksphp_install_check_writable($install_dir . '/backup');
$write_data   = ksphp_install_check_writable($parent_dir . '/data');
$write_logs   = ksphp_install_check_writable($parent_dir . '/logs');

$migrated_marker = $parent_dir . '/data/.migrated';
$migrated = file_exists($migrated_marker) ? trim((string) @file_get_contents($migrated_marker)) : null;

/**
 * 指定パスのconf.phpを読み込み、BBSTITLE/LANGUAGE_FILEの概要を返す。
 * クロージャ内でincludeすることで、同一リクエスト内で複数の
 * conf.php（別々の導入先のもの）を安全に読み込めるようにする。
 */
function ksphp_install_read_conf_summary(string $conf_path): ?array {
	if (!@file_exists($conf_path)) {
		return null;
	}
	$loader = function () use ($conf_path) {
		$CONF = array();
		include $conf_path;
		return is_array($CONF) ? $CONF : array();
	};
	$conf = $loader();
	if (empty($conf)) {
		return null;
	}
	return array(
		'BBSTITLE'      => $conf['BBSTITLE'] ?? T('NOT_SET'),
		'LANGUAGE_FILE' => $conf['LANGUAGE_FILE'] ?? T('NOT_SET'),
		// 20260720 Gikoneko: 旧conf.php側にADMINPOSTの実値が残っている
		// （＝バージョンアップ移行ケース）かどうかをJS側へ伝える。
		// 実際のcrypt()検証・local.php書き込みはksphp_install_run()側で
		// 行う（ここではUI表示用の判定のみ）。
		'ADMIN_MIGRATION_NEEDED' => !empty($conf['ADMINPOST']),
	);
}

$conf_summaries = array();
foreach ($candidates as $i => $path) {
	// パスをキーにすることで、JS側がスキャン添字に依存せずsummaryを引けるようにする。
	$conf_summaries[$path] = ksphp_install_read_conf_summary(dirname($path) . '/conf.php');
}

function h(string $s): string {
	return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'english' ? 'en' : 'ja'; ?>">
<head>
<meta charset="UTF-8">
<title><?php echo h(T('PAGE_TITLE')); ?></title>
<style>
	body { background:#004040; color:#efefef; font-family:"BIZ UDゴシック","Noto Sans Mono CJK JP",monospace; padding:1.5em; }
	h1 { font-size:1.2em; }
	h2 { font-size:1em; border-bottom:1px solid #007f7f; padding-bottom:0.25em; margin-top:1.5em; }
	table { border-collapse:collapse; margin:0.5em 0; }
	td, th { padding:0.25em 0.75em; border:1px solid #007f7f; text-align:left; }
	.ok      { color:#8cff8c; }
	.ng      { color:#ff8c8c; }
	.skipped { color:#7fd4d4; }
	a:link { color: #cfe; transition:0.2s; }
	a:visited { color: #ddd; }
	a:active { color: #f00; }
	a:hover { color: #1ee; }
	a.help { text-decoration-line: underline; }
	code  { background:#003434; padding:0.1em 0.3em; }
	button { background:#007f7f; color:#fff; border:none; padding:0.5em 1.2em; font-size:1em; cursor:pointer; }
	button:disabled { background:#555; cursor:default; }
	#setup-log { list-style:none; margin:0.5em 0; padding:0; font-size:0.9em; }
	#setup-log li { padding:0.15em 0; }
	#lang-select-wrap { float:right; }
	#conf-review-panel { border:2px solid #ffcf5c; background:#003030; padding:0.75em 1em; margin:0.75em 0; }
	.conf-review-table th { vertical-align:top; white-space:nowrap; }
	.conf-review-table td textarea, .conf-review-table td input[type="text"] { background:#002828; color:#efefef; border:1px solid #007f7f; font-family:inherit; }
	.conf-desc { font-weight:normal; font-size:0.85em; color:#9fdada; margin-top:0.2em; white-space:normal; }
	tr.conf-required th { background:#3a2a00; }
	.req-star { color:#ffcf5c; font-weight:bold; }
	.new-tag { color:#8cff8c; font-size:0.85em; }
	.hint { font-size:0.8em; color:#9fc; }
	#conf-review-confirm-btn { margin-top:0.5em; }
</style>
</head>
<body>
<p id="lang-select-wrap">
	<label for="lang-select"><?php echo h(T('LANG_SELECT_LABEL')); ?></label>
	<select id="lang-select">
		<option value="english" <?php echo $lang === 'english' ? 'selected' : ''; ?>>English</option>
		<option value="japanese" <?php echo $lang === 'japanese' ? 'selected' : ''; ?>>日本語</option>
		<option value="korean" <?php echo $lang === 'korean' ? 'selected' : ''; ?>>한국어</option>
		<option value="portuguese" <?php echo $lang === 'portuguese' ? 'selected' : ''; ?>>Português</option>
		<option value="turkish" <?php echo $lang === 'turkish' ? 'selected' : ''; ?>>Türkçe</option>
		<option value="zh-hans" <?php echo $lang === 'zh-hans' ? 'selected' : ''; ?>>简体中文</option>
		<option value="zh-hant" <?php echo $lang === 'zh-hant' ? 'selected' : ''; ?>>繁體中文</option>
	</select>
</p>
<h1><?php echo h(T('PAGE_TITLE')); ?></h1>
<p><?php echo h(T('LABEL_TOOL_LOCATION')); ?><code><?php echo h($install_dir); ?></code></p>
<p><?php echo h(T('LABEL_INSTALL_TARGET')); ?><code><?php echo h($parent_dir); ?></code></p>

<h2><?php echo h(T('H2_STEP1')); ?></h2>
<?php if (empty($candidates)): ?>
	<p><?php echo h(T('NO_CANDIDATES_FOUND')); ?></p>
<?php else: ?>
	<p>
		<button type="button" id="select-all-btn"><?php echo h(T('BTN_SELECT_ALL')); ?></button>
		<button type="button" id="deselect-all-btn"><?php echo h(T('BTN_DESELECT_ALL')); ?></button>
	</p>
	<table>
	<tr><th><?php echo h(T('TABLE_HEADER_INSTALL')); ?></th><th><?php echo h(T('TABLE_HEADER_PATH')); ?></th><th><?php echo h(T('TABLE_HEADER_VERSION')); ?></th><th><?php echo h(T('TABLE_HEADER_MODIFIED')); ?></th><th><?php echo h(T('TABLE_HEADER_VERDICT')); ?></th></tr>
	<?php foreach ($candidates as $i => $path): $pair = ksphp_install_check_pair($path); ?>
		<tr>
			<td><input type="checkbox" class="target-checkbox" data-index="<?php echo (int) $i; ?>" <?php echo $i === 0 ? 'checked' : ''; ?>></td>
			<td><?php echo h($path); ?><?php echo $i === 0 ? h(T('CURRENT_INSTALL_MARK')) : ''; ?></td>
			<td><?php echo h(ksphp_install_extract_version($path) ?? T('VERSION_UNKNOWN')); ?></td>
			<td><?php echo h(date('Y-m-d H:i', filemtime($path))); ?></td>
			<td class="<?php echo $pair['has_conf'] ? 'ok' : 'ng'; ?>"><?php echo h($pair['verdict']); ?></td>
		</tr>
	<?php endforeach; ?>
	</table>
	<?php if (count($candidates) > 1): ?>
		<p class="ng"><?php echo h(T('MULTIPLE_CANDIDATES_WARN')); ?></p>
	<?php endif; ?>
<?php endif; ?>

<p>
	<?php echo sprintf(h(T('NEW_DIR_INSTRUCTION')), '<code>' . h($grandparent_dir) . '</code>', '<code>z/newboard</code>'); ?>
</p>
<p>
	<input type="text" id="new-dir-input" placeholder="<?php echo h(T('NEW_DIR_INPUT_PLACEHOLDER')); ?>" style="width:20em;">
	<button type="button" id="new-dir-add-btn"><?php echo h(T('BTN_ADD_NEW_DIR')); ?></button>
</p>
<table id="new-dir-table"></table>

<h2><?php echo sprintf(h(T('H2_STEP2')), count($newbbs_files)); ?></h2>
<?php if (empty($newbbs_files)): ?>
	<p class="ng"><?php echo h(T('NO_NEWBBS_FILES')); ?></p>
<?php else: ?>
	<p><?php echo h(implode(T('FILE_LIST_SEPARATOR'), array_slice($newbbs_files, 0, 8))); ?><?php echo count($newbbs_files) > 8 ? h(sprintf(T('FILES_LIST_MORE'), count($newbbs_files) - 8)) : ''; ?></p>
<?php endif; ?>

<h2 id="h-write"><?php echo sprintf(h(T('H2_STEP3')), h($parent_dir)); ?></h2>
<table id="write-table">
<tr><th><?php echo h(T('TABLE_HEADER_LOCATION')); ?></th><th><?php echo h(T('TABLE_HEADER_STATE')); ?></th><th><?php echo h(T('TABLE_HEADER_NOTE')); ?></th></tr>
<tr><td><?php echo h(T('LOCATION_SITEROOT')); ?></td><td class="<?php echo $write_root['ok'] ? 'ok' : 'ng'; ?>" data-key="write_root_state"><?php echo $write_root['ok'] ? 'OK' : 'NG'; ?></td><td data-key="write_root_note"><?php echo h($write_root['note']); ?></td></tr>
<tr><td>install/backup/</td><td class="<?php echo $write_backup['ok'] ? 'ok' : 'ng'; ?>"><?php echo $write_backup['ok'] ? 'OK' : 'NG'; ?></td><td><?php echo h($write_backup['note']); ?></td></tr>
<tr><td>data/</td><td class="<?php echo $write_data['ok'] ? 'ok' : 'ng'; ?>" data-key="write_data_state"><?php echo $write_data['ok'] ? 'OK' : 'NG'; ?></td><td data-key="write_data_note"><?php echo h($write_data['note']); ?></td></tr>
<tr><td>logs/</td><td class="<?php echo $write_logs['ok'] ? 'ok' : 'ng'; ?>" data-key="write_logs_state"><?php echo $write_logs['ok'] ? 'OK' : 'NG'; ?></td><td data-key="write_logs_note"><?php echo h($write_logs['note']); ?></td></tr>
</table>
<?php if (!$write_root['ok']): ?>
<p class="ng"><?php echo h(T('SITEROOT_NOT_WRITABLE_WARN')); ?></p>
<?php endif; ?>

<h2 id="h-migrate"><?php echo h(T('H2_STEP4')); ?></h2>
<p id="migrate-status"><?php echo $migrated !== null ? '<span class="ok">' . sprintf(h(T('MIGRATE_DONE_LABEL')), h($migrated)) . '</span>' : h(T('MIGRATE_NOT_DONE')); ?></p>

<h2><?php echo h(T('H2_STEP5')); ?></h2>
<div id="conf-summary-container"></div>
<script id="conf-summaries-data" type="application/json"><?php echo json_encode($conf_summaries, JSON_UNESCAPED_UNICODE); ?></script>
<script id="conf-summaries-paths" type="application/json"><?php echo json_encode(array_values($candidates), JSON_UNESCAPED_UNICODE); ?></script>

<h2><?php echo h(T('H2_STEP6')); ?></h2>
<p><?php echo T('STEP6_INTRO'); ?></p>
<p><label><input type="checkbox" id="conf-review-toggle" checked> <?php echo h(T('CONF_REVIEW_TOGGLE_LABEL')); ?></label></p>
<div id="conf-review-panel" style="display:none"></div>
<button id="run-setup-btn"><?php echo h(T('BTN_RUN_SETUP')); ?></button>
<ul id="setup-log"></ul>


<hr>
<p><?php echo h(T('FOOTER_NOTE')); ?></p>

<script id="install-lang-data" type="application/json"><?php echo json_encode($MSG, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
<script>
// 20260719 Gikoneko: JS側でも$MSGの翻訳文字列を参照できるようにする
// （bbs.php本体のwindow.KSPHP_LANGと同じ方式）。プレースホルダは
// sprintf形式ではなく{NAME}形式とし、Lf()で単純な文字列置換を行う。
var INSTALL_LANG = {};
try {
	INSTALL_LANG = JSON.parse(document.getElementById('install-lang-data').textContent || '{}');
} catch (e) {
	INSTALL_LANG = {};
}
function L(key) {
	return (key in INSTALL_LANG) ? INSTALL_LANG[key] : key;
}
function Lf(key, replacements) {
	var s = L(key);
	for (var name in replacements) {
		s = s.split('{' + name + '}').join(replacements[name]);
	}
	return s;
}

document.getElementById('lang-select').addEventListener('change', function () {
	location.href = '?lang=' + encodeURIComponent(this.value);
});
</script>
<script>
document.getElementById('select-all-btn') && document.getElementById('select-all-btn').addEventListener('click', function () {
	document.querySelectorAll('.target-checkbox').forEach(function (cb) { cb.checked = true; });
	renderConfSummaries();
});
document.getElementById('deselect-all-btn') && document.getElementById('deselect-all-btn').addEventListener('click', function () {
	document.querySelectorAll('.target-checkbox').forEach(function (cb) { cb.checked = false; });
	renderConfSummaries();
});
document.querySelectorAll('.target-checkbox').forEach(function (cb) {
	cb.addEventListener('change', renderConfSummaries);
});

// 20260719 Gikoneko: 新規インストール先フォルダ選択機能
var newDirCount = 0;
document.getElementById('new-dir-add-btn').addEventListener('click', function () {
	var input = document.getElementById('new-dir-input');
	var path = input.value.trim();
	if (!path) {
		alert(L('JS_ALERT_EMPTY_PATH'));
		return;
	}
	if (!confirm(Lf('JS_CONFIRM_NEW_DIR', { PATH: path }))) {
		return;
	}
	var table = document.getElementById('new-dir-table');
	var row = document.createElement('tr');
	var idx = 'new' + (newDirCount++);
	row.innerHTML = '<td><input type="checkbox" class="target-checkbox" data-new-dir="' + escapeHtml(path) + '" checked></td>'
		+ '<td>' + escapeHtml(path) + escapeHtml(L('JS_NEW_DIR_TAG')) + '</td>';
	table.appendChild(row);
	input.value = '';
	renderConfSummaries();
});

function escapeHtml(s) {
	var div = document.createElement('div');
	div.textContent = s;
	return div.innerHTML;
}

function renderConfSummaries() {
	var container = document.getElementById('conf-summary-container');
	if (!container) { return; }

	var summariesEl = document.getElementById('conf-summaries-data');
	var pathsEl = document.getElementById('conf-summaries-paths');
	var summaries = {};
	var paths = [];
	try { summaries = JSON.parse(summariesEl.textContent || '{}'); } catch (e) { summaries = {}; }
	try { paths = JSON.parse(pathsEl.textContent || '[]'); } catch (e) { paths = []; }

	var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.target-checkbox:checked'));

	if (checkboxes.length === 0) {
		container.innerHTML = '<p>' + escapeHtml(L('JS_NO_TARGET_SELECTED')) + '</p>';
		return;
	}

	var multi = checkboxes.length > 1;
	var html = '';
	checkboxes.forEach(function (cb) {
		var newDir = cb.getAttribute('data-new-dir');
		if (newDir !== null) {
			// 20260719 Gikoneko: 新規追加分はまだconf.phpがあるはずがないため、
			// サーバー側の要約データを持たない。常に「新規導入前」表示にする。
			if (multi) {
				html += '<p><code>' + escapeHtml(newDir) + '</code></p>';
			}
			html += '<p>' + escapeHtml(L('JS_NO_CONF_YET')) + '</p>';
			return;
		}
		var idx = cb.getAttribute('data-index');
		var bbs_path = paths[idx] || '';
		var summary = summaries[bbs_path] || summaries[idx];  // パスキー優先、旧添字キーにフォールバック
		var path = bbs_path || Lf('JS_TARGET_LABEL_FALLBACK', { IDX: idx });
		if (multi) {
			html += '<p><code>' + escapeHtml(path) + '</code></p>';
		}
		if (summary) {
			html += '<table>'
				+ '<tr><th>BBSTITLE</th><td>' + escapeHtml(summary.BBSTITLE) + '</td></tr>'
				+ '<tr><th>LANGUAGE_FILE</th><td>' + escapeHtml(summary.LANGUAGE_FILE) + '</td></tr>'
				+ '</table>';
			// 20260720 Gikoneko: 旧conf.php側にADMINPOSTの実値が残って
			// いる（＝バージョンアップ移行ケース）場合、専用フォームを
			// 表示する。値はrun-setup-btnのクリック時にPOSTボディで
			// 送信する（GETクエリ文字列に平文パスワードを載せない）。
			if (summary.ADMIN_MIGRATION_NEEDED) {
				var ridx = escapeHtml(idx);
				html += '<p class="ng">' + escapeHtml(L('ADMIN_MIGRATION_LABEL')) + '</p>'
					+ '<p>' + escapeHtml(L('ADMIN_MIGRATION_NOTE')) + '</p>'
					+ '<p><label>' + escapeHtml(L('ADMIN_MIGRATION_OLD_LABEL')) + ' <input type="password" class="admin-old-pass" data-for-index="' + ridx + '" autocomplete="off"></label></p>'
					+ '<p>'
					+ '<label><input type="radio" class="admin-keep-radio" name="admin-pass-mode-' + ridx + '" data-for-index="' + ridx + '" value="keep" checked> ' + escapeHtml(L('ADMIN_MIGRATION_KEEP_LABEL')) + '</label>'
					+ ' &nbsp; '
					+ '<label><input type="radio" class="admin-change-radio" name="admin-pass-mode-' + ridx + '" data-for-index="' + ridx + '" value="change"> ' + escapeHtml(L('ADMIN_MIGRATION_CHANGE_LABEL')) + '</label>'
					+ '</p>'
					+ '<p class="admin-new-pass-row" data-for-index="' + ridx + '" style="display:none"><label>' + escapeHtml(L('ADMIN_MIGRATION_NEW_LABEL')) + ' <input type="password" class="admin-new-pass" data-for-index="' + ridx + '" autocomplete="off"></label></p>';
			}
		} else {
			html += '<p>' + escapeHtml(L('JS_NO_CONF_YET')) + '</p>';
		}
	});
	container.innerHTML = html;
	// ラジオボタン（継続／変更）のchangeイベント：新パス入力欄の表示切替。
	// Radio button (keep/change) change event: toggle new-password row visibility.
	container.querySelectorAll('.admin-keep-radio, .admin-change-radio').forEach(function (radio) {
		radio.addEventListener('change', function () {
			var idx = this.getAttribute('data-for-index');
			var row = container.querySelector('.admin-new-pass-row[data-for-index="' + idx + '"]');
			if (row) {
				row.style.display = (this.value === 'change') ? '' : 'none';
				var input = row.querySelector('.admin-new-pass');
				if (input && this.value !== 'change') { input.value = ''; }
			}
		});
	});
}

renderConfSummaries();

// 20260801 Gikoneko: conf.php調整確認画面。
function fetchConfReview(target) {
	var langParam = '&lang=' + encodeURIComponent(<?php echo json_encode($lang); ?>);
	var url = (target.kind === 'new')
		? '?ajax=1&action=conf_review&kind=new&dir=' + encodeURIComponent(target.value) + langParam
		: '?ajax=1&action=conf_review&kind=index&path=' + encodeURIComponent(target.path || '') + langParam;
	return fetch(url)
		.then(function (res) { return res.json(); })
		.catch(function () { return { needed: false }; });
}

function renderConfReviewForm(fields) {
	var panel = document.getElementById('conf-review-panel');
	var html = '<h3>' + escapeHtml(L('H2_CONF_REVIEW')) + '</h3>';
	html += '<p>' + escapeHtml(L('CONF_REVIEW_INTRO')) + '</p>';
	html += '<p class="req-legend"><span class="req-star">*</span> ' + escapeHtml(L('CONF_REVIEW_REQUIRED_MARK')) + '</p>';
	html += '<table class="conf-review-table">';
	fields.forEach(function (f) {
		var reqMark = f.required ? ' <span class="req-star">*</span>' : '';
		var newTag = f.is_new ? ' <span class="new-tag">' + escapeHtml(L('CONF_REVIEW_NEW_KEY_TAG')) + '</span>' : '';
		var descHtml = f.description ? '<div class="conf-desc">' + escapeHtml(f.description) + '</div>' : '';
		html += '<tr class="' + (f.required ? 'conf-required' : '') + '"><th>' + escapeHtml(f.key) + reqMark + newTag + descHtml + '</th><td>';
		if (f.type === 'radio') {
			(f.options || []).forEach(function (opt) {
				var checked = (String(f.value) === String(opt.value)) ? ' checked' : '';
				html += '<label style="display:block"><input type="radio" class="conf-field" name="conf-radio-' + escapeHtml(f.key) + '" data-key="' + escapeHtml(f.key) + '" data-type="radio" value="' + escapeHtml(opt.value) + '"' + checked + '> ' + escapeHtml(opt.label) + '</label>';
			});
		} else if (f.type === 'list') {
			html += '<textarea class="conf-field" data-key="' + escapeHtml(f.key) + '" data-type="list" rows="4" cols="40">' + escapeHtml(f.value) + '</textarea>'
				+ '<div class="hint">' + escapeHtml(L('CONF_REVIEW_LIST_HINT')) + '</div>';
		} else if (f.type === 'longtext') {
			html += '<textarea class="conf-field" data-key="' + escapeHtml(f.key) + '" data-type="text" rows="6" cols="40">' + escapeHtml(f.value) + '</textarea>';
		} else {
			html += '<input type="text" class="conf-field" data-key="' + escapeHtml(f.key) + '" data-type="text" value="' + escapeHtml(f.value) + '" size="40">';
		}
		html += '</td></tr>';
	});
	html += '</table>';
	html += '<button type="button" id="conf-review-confirm-btn">' + escapeHtml(L('CONF_REVIEW_CONFIRM_BTN')) + '</button>';
	panel.innerHTML = html;
	panel.style.display = '';
	panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function collectConfOverrides(fields) {
	var overrides = {};
	fields.forEach(function (f) {
		if (f.type === 'radio') {
			var el = document.querySelector('.conf-field[data-key="' + f.key + '"][data-type="radio"]:checked');
			overrides[f.key] = el ? el.value : String(f.value || '0');
		} else {
			var el = document.querySelector('.conf-field[data-key="' + f.key + '"]');
			overrides[f.key] = el ? el.value : '';
		}
	});
	return overrides;
}

function showConfReviewAndWait(fields) {
	return new Promise(function (resolve) {
		renderConfReviewForm(fields);
		var btn = document.getElementById('conf-review-confirm-btn');
		btn.addEventListener('click', function onClick() {
			btn.removeEventListener('click', onClick);
			var overrides = collectConfOverrides(fields);
			document.getElementById('conf-review-panel').style.display = 'none';
			document.getElementById('conf-review-panel').innerHTML = '';
			resolve(overrides);
		});
	});
}

document.getElementById('run-setup-btn').addEventListener('click', function () {
	var btn = this;
	var logList = document.getElementById('setup-log');
	var pathsEl = document.getElementById('conf-summaries-paths');
	var paths = [];
	try { paths = JSON.parse(pathsEl ? pathsEl.textContent || '[]' : '[]'); } catch (e) { paths = []; }

	// 1. 対象のリストアップ
	// 20260719 Gikoneko: data-index（近隣スキャン検出分）と
	// data-new-dir（新規フォルダ追加分）の両方に対応する。
	var targetsList = Array.prototype.slice.call(document.querySelectorAll('.target-checkbox:checked'))
		.map(function (cb) {
			var newDir = cb.getAttribute('data-new-dir');
			if (newDir !== null) {
				return { kind: 'new', value: newDir, label: newDir + L('JS_NEW_DIR_TAG') };
			}
			var idx = cb.getAttribute('data-index');
			// パスをindexではなく実際のパス文字列で渡す。
			// スキャン再実行による添字ズレを防ぐため。
			var bbs_path = paths[idx] || '';
			return { kind: 'index', value: idx, path: bbs_path, label: bbs_path || ('#' + idx) };
		});

	logList.innerHTML = '';

	if (targetsList.length === 0) {
		var noneLi = document.createElement('li');
		noneLi.className = 'ng';
		noneLi.textContent = L('JS_NO_TARGET_SELECTED_RUN');
		logList.appendChild(noneLi);
		return;
	}

	// 2. 事前バリデーション（管理パスワード移行が必要な場合）
	// 20260720 Gikoneko: 移行フォームが必要な対象について、送信前に
	// 入力値が揃っているか確認する（未入力ならAJAXを呼ばずその場で
	// 中止表示し、他の対象の処理も始めない。サーバー側でも同様の
	// チェックを行うが、二重防御としてここでも確認する）。
	var summaries = {};
	try { summaries = JSON.parse(document.getElementById('conf-summaries-data').textContent || '{}'); } catch (e) { summaries = {}; }
	for (var t = 0; t < targetsList.length; t++) {
		var tgt = targetsList[t];
		if (tgt.kind !== 'index') { continue; }
		var sum = summaries[tgt.path] || summaries[tgt.value];  // パスキー優先
		if (sum && sum.ADMIN_MIGRATION_NEEDED) {
			var oldEl = document.querySelector('.admin-old-pass[data-for-index="' + tgt.value + '"]');
			var newEl = document.querySelector('.admin-new-pass[data-for-index="' + tgt.value + '"]');
			var keepRadio = document.querySelector('.admin-keep-radio[data-for-index="' + tgt.value + '"]');
			var oldV = oldEl ? oldEl.value : '';
			var newV = newEl ? newEl.value : '';
			var keepPass = keepRadio ? keepRadio.checked : false;
			if (!oldV || (!keepPass && !newV)) {
				var warnLi = document.createElement('li');
				warnLi.className = 'ng';
				warnLi.textContent = Lf('JS_ADMIN_MIGRATION_EMPTY', { LABEL: tgt.label });
				logList.appendChild(warnLi);
				return;
			}
			tgt.oldAdminPass = oldV;
			tgt.newAdminPass = newV;
			tgt.keepAdminPass = keepPass;
		}
	}

	btn.disabled = true;
	btn.textContent = L('JS_RUNNING');
	var hadFailure = false;

	// 3. 1件の導入先を最初から最後まで処理するPromise関数。
	// conf.php確認 → 設置 → ログ表示 が全部終わってから resolve するため、
	// 複数選択時も1件ずつ確実に直列処理される（各個撃破）。
	function processSingleTarget(target) {
		return new Promise(function (resolveNextTarget) {
			var header = document.createElement('li');
			header.innerHTML = '<strong>--- ' + Lf('JS_TARGET_HEADER', { LABEL: escapeHtml(target.label) }) + ' ---</strong>';
			logList.appendChild(header);

			// 20260801 Gikoneko: ファイル設置の前に、既存conf.phpがあれば
			// 調整確認画面を挟む。無ければ（新規導入等）従来通り即座に進む。
			// #conf-review-toggle が未チェックなら、この画面自体をスキップし
			// 従来の全自動マージ動作に戻す（個人設定でオン/オフ可能）。
			var reviewToggle = document.getElementById('conf-review-toggle');
			var reviewEnabled = !reviewToggle || reviewToggle.checked;
			var reviewFetchPromise = reviewEnabled ? fetchConfReview(target) : Promise.resolve({ needed: false });

			reviewFetchPromise.then(function (reviewData) {
				var overridesPromise = (reviewData && reviewData.needed && reviewData.fields && reviewData.fields.length)
					? showConfReviewAndWait(reviewData.fields)
					: Promise.resolve(null);

				overridesPromise.then(function (overrides) {
					var langParam = '&lang=' + encodeURIComponent(<?php echo json_encode($lang); ?>);
					var url = (target.kind === 'new')
						? '?ajax=1&action=run_setup_new&dir=' + encodeURIComponent(target.value) + langParam
						: '?ajax=1&action=run_setup&path=' + encodeURIComponent(target.path || '') + langParam;

					// 20260720 Gikoneko: 管理パスワード移行が必要な対象のみ、
					// 平文パスワードをPOSTボディで送信する（GETクエリ文字列に
					// 載せてアクセスログへ残すのを避けるため）。conf.php調整
					// 内容がある場合も同様にPOSTボディで送る。いずれも無ければ
					// 従来通りGETのみ（挙動変更なし）。
					var fetchOptions = undefined;
					if (target.oldAdminPass !== undefined || overrides !== null) {
						var fd = new FormData();
						if (target.oldAdminPass !== undefined) {
							fd.append('old_admin_pass', target.oldAdminPass);
							fd.append('new_admin_pass', target.newAdminPass);
							fd.append('keep_admin_pass', target.keepAdminPass ? '1' : '0');
						}
						if (overrides !== null) {
							fd.append('conf_overrides', JSON.stringify(overrides));
						}
						fetchOptions = { method: 'POST', body: fd };
					}

					fetch(url, fetchOptions)
						.then(function (res) { return res.json(); })
						.then(function (data) {
							var lines = data.log || [];
							var entryFilename = data.entry_filename || 'bbs.php';
							var i = 0;

							function showNextLogLine() {
								if (i >= lines.length) {
									var linkLi = document.createElement('li');
									var bbsIndexAttr = (target.kind === 'new') ? ('new:' + target.value) : target.value;
									linkLi.innerHTML = '<a href="?ajax=0" data-bbs-index="' + escapeHtml(bbsIndexAttr) + '" data-entry-filename="' + entryFilename + '" class="bbs-link" target="_blank">' + escapeHtml(Lf('JS_OPEN_LINK_TEXT', { ENTRY: entryFilename })) + '</a>';
									logList.appendChild(linkLi);
									// この導入先の処理とログ表示が終わったら次へ進む
									resolveNextTarget();
									return;
								}
								var li = document.createElement('li');
								li.className = lines[i].skipped ? 'skipped' : (lines[i].ok ? 'ok' : 'ng');
								if (!lines[i].ok) { hadFailure = true; }
								li.textContent = lines[i].text;
								logList.appendChild(li);
								i++;
								setTimeout(showNextLogLine, 60);
							}
							showNextLogLine();
						})
						.catch(function () {
							var li = document.createElement('li');
							li.className = 'ng';
							li.textContent = Lf('JS_COMM_ERROR', { LABEL: target.label });
							logList.appendChild(li);
							hadFailure = true;
							resolveNextTarget();
						});
				});
			});
		});
	}

	// 4. 直列実行ループの駆動
	var sequence = Promise.resolve();
	targetsList.forEach(function (target) {
		sequence = sequence.then(function () {
			return processSingleTarget(target);
		});
	});

	// 5. 全件完了時の最終処理
	sequence.then(function () {
		if (hadFailure) {
			btn.textContent = L('JS_DONE_WITH_ERRORS_BTN');
			var doneLi = document.createElement('li');
			doneLi.className = 'ng';
			doneLi.textContent = L('JS_DONE_WITH_ERRORS_MSG');
			logList.appendChild(doneLi);
		} else {
			btn.textContent = L('JS_DONE_BTN');
			var doneLi = document.createElement('li');
			doneLi.textContent = L('JS_DONE_MSG');
			logList.appendChild(doneLi);
		}
	});
});

// 「開く」リンクは、対応するテーブル行のパスから実際のURLを組み立て
// られないため（ファイルシステムパスのみ保持）、導入先#0（このinstall.php
// に対応する場所）のみ ../{検出したファイル名} で開けるようにしておく
// （bbs.php以外にリネームされている場合もそのファイル名を使う）。
// それ以外の導入先はパスをコピーしてご確認ください。
document.addEventListener('click', function (e) {
	if (e.target.classList && e.target.classList.contains('bbs-link')) {
		var idx = e.target.getAttribute('data-bbs-index');
		var entryFilename = e.target.getAttribute('data-entry-filename') || 'bbs.php';
		if (idx === '0') {
			e.target.href = '../' + entryFilename;
		} else if (idx && idx.indexOf('new:') === 0) {
			e.preventDefault();
			alert(Lf('JS_OPEN_NEW_DIR_ALERT', { PATH: idx.substring(4), ENTRY: entryFilename }));
		} else {
			e.preventDefault();
			alert(Lf('JS_OPEN_SCAN_ALERT', { IDX: idx, ENTRY: entryFilename }));
		}
	}
});
</script>
</body>
</html>
