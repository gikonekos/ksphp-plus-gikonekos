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

$lang_options = array('japanese', 'english');
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
		if (preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>\s*(.*),(\s*)$/s', $entry, $em)) {
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
 * 1エントリ（'KEY' => 値, ）を、値を空文字列にし、末尾に「要手動設定」
 * である旨のコメントを付けた形へ差し替える。ksphp_is_manual_path_key()
 * が対象とするキーの新規追加時に使う。
 */
function ksphp_conf_entry_blank_for_manual_setup(string $entry): string {
	if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'(\s*=>\s*)(.*),(\s*)$/s', $entry, $m)) {
		return $entry;
	}
	return $m[1] . "'" . $m[2] . "'" . $m[3] . "''" . ', // ' . T('PATH_KEY_MANUAL_NOTE') . $m[5];
}

/**
 * 1エントリ（'KEY' => 値, ）の値部分だけを、指定した生の値文字列に
 * 差し替える。リード部・トレイル部（コメント等）はそのまま残す。
 */
function ksphp_conf_entry_with_value(string $entry, string $raw_value): string {
	if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'(\s*=>\s*)(.*),(\s*)$/s', $entry, $m)) {
		return $entry;
	}
	return $m[1] . "'" . $m[2] . "'" . $m[3] . $raw_value . ',' . $m[5];
}

/**
 * 1エントリ（'KEY' => 値, ）から値部分だけを old 側の値に差し替える。
 * リード部（キー直前までのコメント等）とトレイル部（末尾コメント等）は
 * 新版（テンプレート）側のものをそのまま残す。
 */
function ksphp_conf_merge_entry(string $new_entry, ?string $old_entry): array {
	if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'(\s*=>\s*)(.*),(\s*)$/s', $new_entry, $m)) {
		return array('text' => $new_entry, 'key' => null);
	}
	$lead = $m[1];
	$key  = $m[2];
	$sep  = $m[3];
	$new_val = $m[4];
	$tail = $m[5];

	if ($old_entry === null) {
		return array('text' => $new_entry, 'key' => $key, 'is_new' => true);
	}
	if (!preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>\s*(.*),(\s*)$/s', $old_entry, $om)) {
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
		if (preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>/s', $oe, $om)) {
			$old_by_key[$om[2]] = $oe;
		}
	}

	// 旧モジュールファイル（sub/bbsimage.php等）のパース結果は、同じ
	// ファイルを何度も読み直さないようにキャッシュする。
	$module_values_cache = array();

	$seen = array();
	$merged_entries = array();
	foreach ($new_parsed['entries'] as $entry) {
		if (preg_match('/^(.*?)\'([A-Za-z0-9_]+)\'\s*=>/s', $entry, $km)) {
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

function ksphp_install_run(string $newbbs_dir, string $parent_dir, string $backup_root, string $entry_filename = 'bbs.php'): array {
	$log = array();

	// newbbs_dir は常に {install_dir}/newbbs なので、ここから install_dir を逆算する。
	$install_dir_check = dirname($newbbs_dir);
	if (!ksphp_install_is_safe_target_dir($parent_dir, $install_dir_check)) {
		$log[] = array('ok' => false, 'text' => T('INSTALL_UNSAFE_TARGET'));
		ksphp_install_log_error($install_dir_check, "unsafe target rejected: $parent_dir");
		return $log;
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
			if (@file_put_contents($dst, $merged['content']) !== false) {
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

if (($_GET['ajax'] ?? '') === '1' && ($_GET['action'] ?? '') === 'run_setup_new') {
	header('Content-Type: application/json; charset=UTF-8');
	$new_dir = ksphp_install_validate_new_dir($grandparent_dir, (string) ($_GET['dir'] ?? ''), $install_dir);
	if ($new_dir === null) {
		echo json_encode(array('log' => array(array('ok' => false, 'text' => T('ERROR_INVALID_PATH')))), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$backup_root = $install_dir . '/backup/new_' . substr(md5($new_dir), 0, 12);
	echo json_encode(
		array(
			'log' => ksphp_install_run($newbbs_dir, $new_dir, $backup_root, 'bbs.php'),
			'entry_filename' => 'bbs.php',
		),
		JSON_UNESCAPED_UNICODE
	);
	exit;
}

if (($_GET['ajax'] ?? '') === '1' && ($_GET['action'] ?? '') === 'run_setup') {
	header('Content-Type: application/json; charset=UTF-8');
	$targets = ksphp_install_find_candidates($parent_dir, $grandparent_dir);
	$idx = (int) ($_GET['target'] ?? -1);
	if (!isset($targets[$idx])) {
		echo json_encode(array('log' => array(array('ok' => false, 'text' => T('ERROR_TARGET_NOT_FOUND')))), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$target_dir = dirname($targets[$idx]);
	$entry_filename = basename($targets[$idx]);
	$backup_root = $install_dir . '/backup/target' . $idx;
	echo json_encode(
		array(
			'log' => ksphp_install_run($newbbs_dir, $target_dir, $backup_root, $entry_filename),
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
	);
}

$conf_summaries = array();
foreach ($candidates as $i => $path) {
	$conf_summaries[$i] = ksphp_install_read_conf_summary(dirname($path) . '/conf.php');
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
	.ok   { color:#8cff8c; }
	.ng   { color:#ff8c8c; }
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
</style>
</head>
<body>
<p id="lang-select-wrap">
	<label for="lang-select"><?php echo h(T('LANG_SELECT_LABEL')); ?></label>
	<select id="lang-select">
		<option value="japanese" <?php echo $lang === 'japanese' ? 'selected' : ''; ?>>日本語</option>
		<option value="english" <?php echo $lang === 'english' ? 'selected' : ''; ?>>English</option>
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
		var summary = summaries[idx];
		var path = paths[idx] || Lf('JS_TARGET_LABEL_FALLBACK', { IDX: idx });
		if (multi) {
			html += '<p><code>' + escapeHtml(path) + '</code></p>';
		}
		if (summary) {
			html += '<table>'
				+ '<tr><th>BBSTITLE</th><td>' + escapeHtml(summary.BBSTITLE) + '</td></tr>'
				+ '<tr><th>LANGUAGE_FILE</th><td>' + escapeHtml(summary.LANGUAGE_FILE) + '</td></tr>'
				+ '</table>';
		} else {
			html += '<p>' + escapeHtml(L('JS_NO_CONF_YET')) + '</p>';
		}
	});
	container.innerHTML = html;
}

renderConfSummaries();

document.getElementById('run-setup-btn').addEventListener('click', function () {
	var btn = this;
	var logList = document.getElementById('setup-log');
	// 20260719 Gikoneko: data-index（近隣スキャン検出分）と
	// data-new-dir（新規フォルダ追加分）の両方に対応する。
	var targetsList = Array.prototype.slice.call(document.querySelectorAll('.target-checkbox:checked'))
		.map(function (cb) {
			var newDir = cb.getAttribute('data-new-dir');
			if (newDir !== null) {
				return { kind: 'new', value: newDir, label: newDir + L('JS_NEW_DIR_TAG') };
			}
			var idx = cb.getAttribute('data-index');
			return { kind: 'index', value: idx, label: '#' + idx };
		});

	logList.innerHTML = '';

	if (targetsList.length === 0) {
		var noneLi = document.createElement('li');
		noneLi.className = 'ng';
		noneLi.textContent = L('JS_NO_TARGET_SELECTED_RUN');
		logList.appendChild(noneLi);
		return;
	}

	btn.disabled = true;
	btn.textContent = L('JS_RUNNING');

	var targetIdx = 0;
	var hadFailure = false;
	function runNextTarget() {
		if (targetIdx >= targetsList.length) {
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
			return;
		}
		var target = targetsList[targetIdx];
		var idx = target.value;
		var header = document.createElement('li');
		header.innerHTML = '<strong>--- ' + Lf('JS_TARGET_HEADER', { LABEL: escapeHtml(target.label) }) + ' ---</strong>';
		logList.appendChild(header);

		var langParam = '&lang=' + encodeURIComponent(<?php echo json_encode($lang); ?>);
		var url = (target.kind === 'new')
			? '?ajax=1&action=run_setup_new&dir=' + encodeURIComponent(target.value) + langParam
			: '?ajax=1&action=run_setup&target=' + encodeURIComponent(target.value) + langParam;

		fetch(url)
			.then(function (res) { return res.json(); })
			.then(function (data) {
				var lines = data.log || [];
				var entryFilename = data.entry_filename || 'bbs.php';
				var i = 0;
				function showNext() {
					if (i >= lines.length) {
						var linkLi = document.createElement('li');
						var bbsIndexAttr = (target.kind === 'new') ? ('new:' + idx) : idx;
						linkLi.innerHTML = '<a href="?ajax=0" data-bbs-index="' + escapeHtml(bbsIndexAttr) + '" data-entry-filename="' + entryFilename + '" class="bbs-link" target="_blank">' + escapeHtml(Lf('JS_OPEN_LINK_TEXT', { ENTRY: entryFilename })) + '</a>';
						logList.appendChild(linkLi);
						targetIdx++;
						runNextTarget();
						return;
					}
					var li = document.createElement('li');
					li.className = lines[i].ok ? 'ok' : 'ng';
					if (!lines[i].ok) {
						hadFailure = true;
					}
					li.textContent = lines[i].text;
					logList.appendChild(li);
					i++;
					setTimeout(showNext, 60);
				}
				showNext();
			})
			.catch(function () {
				var li = document.createElement('li');
				li.className = 'ng';
				li.textContent = Lf('JS_COMM_ERROR', { LABEL: target.label });
				logList.appendChild(li);
				hadFailure = true;
				targetIdx++;
				runNextTarget();
			});
	}
	runNextTarget();
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
