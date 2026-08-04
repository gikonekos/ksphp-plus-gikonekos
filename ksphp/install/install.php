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
			return array('ok' => false, 'note' => 'ディレクトリ自体が作成できません（親ディレクトリの書き込み権限を確認してください）。');
		}
	}
	$test_file = $dir . '/.ksphp_install_writetest';
	$ok = @file_put_contents($test_file, 'test') !== false;
	if ($ok) {
		@unlink($test_file);
	}
	$note = $ok ? '書き込み可能です。' : '書き込みできません（権限を確認してください）。';
	if ($created_dir) {
		$note .= '（このチェックのためにディレクトリを新規作成しました）';
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
		$verdict = 'KSPHP Plus系（conf.php・migrate.php確認、本ツール対応版の可能性が高い）';
	} elseif ($has_conf) {
		$score = ksphp_install_conf_marker_score($dir . '/conf.php');
		if ($score >= 2) {
			$verdict = 'conf.phpあり・migrate.phpなし（固有設定キーが複数一致、KSPHP Plus系の旧バージョンの可能性が高い。本ツール未対応）';
		} else {
			$verdict = 'conf.phpという名前のファイルはありますが、固有の設定キーがほとんど一致しません。別系統（無関係な設置）の可能性が高いため、導入前に必ず内容を目視確認してください。';
		}
	} else {
		$verdict = 'conf.phpが見当たらず、別系統のbbs.php（無関係な設置）の可能性が高い';
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
		return array('content' => '', 'log' => array(array('ok' => false, 'text' => 'newbbs/conf.php を読み込めませんでした。')));
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
					$log[] = array('ok' => false, 'text' => "conf.php: {$key} は旧設定の解析に失敗したため、新版の既定値を使用しました（要確認）。");
				} elseif (!empty($res['changed'])) {
					$log[] = array('ok' => true, 'text' => "conf.php: {$key} は既存の設定値を維持しました。");
				}
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
				$log[] = array('ok' => true, 'text' => "conf.php: {$key} は旧 {$legacy_rel} の設定値を引き継ぎました。");
			} else {
				$merged_entries[] = $entry;
				$log[] = array('ok' => true, 'text' => "conf.php: {$key} は新版の新規項目のため追加しました。");
			}
		} else {
			$merged_entries[] = $entry;
		}
	}

	foreach ($old_by_key as $key => $oe) {
		if (!isset($seen[$key])) {
			$log[] = array('ok' => true, 'text' => "conf.php: {$key} は新版に存在しないため引き継ぎませんでした。");
		}
	}

	$content = $new_parsed['prefix'] . implode('', $merged_entries) . $new_parsed['suffix'];
	return array('content' => $content, 'log' => $log);
}


function ksphp_install_run(string $newbbs_dir, string $parent_dir, string $backup_root, string $entry_filename = 'bbs.php'): array {
	$log = array();
	$files = ksphp_install_list_files($newbbs_dir);

	if (empty($files)) {
		$log[] = array('ok' => false, 'text' => 'install/newbbs/ にファイルが見つかりません。導入対象がありません。');
		return $log;
	}

	if ($entry_filename !== 'bbs.php') {
		$log[] = array('ok' => true, 'text' => "本体ファイル名は {$entry_filename} として検出されたため、bbs.php ではなく {$entry_filename} として導入します。");
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
			$log[] = array('ok' => false, 'text' => 'バックアップ先フォルダを作成できませんでした。導入を中止します。');
			return $log;
		}
		$log[] = array('ok' => true, 'text' => "バックアップ先: {$backup_dir}");
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
			if (!is_dir($backup_target_dir)) {
				@mkdir($backup_target_dir, 0755, true);
			}
			if (!@copy($dst, $backup_target)) {
				$log[] = array('ok' => false, 'text' => "スキップ: {$dst_rel}（既存ファイルのバックアップに失敗したため上書きしていません）");
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
				$log[] = array('ok' => true, 'text' => "導入: {$dst_rel}（既存設定を維持してマージしました）");
				foreach ($merged['log'] as $entry) {
					$log[] = $entry;
				}
			} else {
				$log[] = array('ok' => false, 'text' => "失敗: {$dst_rel}（マージ結果の書き込みに失敗しました。権限を確認してください）");
			}
			continue;
		}

		if (@copy($src, $dst)) {
			$log[] = array('ok' => true, 'text' => "導入: {$dst_rel}");
		} else {
			$log[] = array('ok' => false, 'text' => "失敗: {$dst_rel}（コピーできませんでした。権限を確認してください）");
		}
	}

	$migrate_file = $parent_dir . '/migrate.php';
	if (file_exists($migrate_file)) {
		require_once $migrate_file;
		if (function_exists('ksphp_migrate')) {
			ksphp_migrate();
			$log[] = array('ok' => true, 'text' => 'Migration Engineを実行しました（旧構成データがあれば data/・logs/ へ移行済みです）。');
		}
	}

	$log[] = array('ok' => true, 'text' => '導入処理が完了しました。');
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

if (($_GET['ajax'] ?? '') === '1' && ($_GET['action'] ?? '') === 'run_setup') {
	header('Content-Type: application/json; charset=UTF-8');
	$targets = ksphp_install_find_candidates($parent_dir, $grandparent_dir);
	$idx = (int) ($_GET['target'] ?? -1);
	if (!isset($targets[$idx])) {
		echo json_encode(array('log' => array(array('ok' => false, 'text' => '対象が見つかりません（一覧が変わった可能性があります。ページを再読み込みしてください）。'))), JSON_UNESCAPED_UNICODE);
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
		'BBSTITLE'      => $conf['BBSTITLE'] ?? '(未設定)',
		'LANGUAGE_FILE' => $conf['LANGUAGE_FILE'] ?? '(未設定)',
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
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>KSPHP Plus セットアップ診断・導入</title>
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
</style>
</head>
<body>
<h1>KSPHP Plus セットアップ診断・導入</h1>
<p>このツール自身の場所：<code><?php echo h($install_dir); ?></code></p>
<p>導入先（サイトルート）：<code><?php echo h($parent_dir); ?></code></p>

<h2>1. bbs.php本体の検出</h2>
<?php if (empty($candidates)): ?>
	<p>近隣にbbs.phpが見つかりませんでした。まだ導入されていない可能性があります（下の「セットアップを実行する」から導入できます）。</p>
<?php else: ?>
	<p>
		<button type="button" id="select-all-btn">全選択</button>
		<button type="button" id="deselect-all-btn">全解除</button>
	</p>
	<table>
	<tr><th>導入</th><th>パス</th><th>VERSION</th><th>最終更新</th><th>判定</th></tr>
	<?php foreach ($candidates as $i => $path): $pair = ksphp_install_check_pair($path); ?>
		<tr>
			<td><input type="checkbox" class="target-checkbox" data-index="<?php echo (int) $i; ?>" <?php echo $i === 0 ? 'checked' : ''; ?>></td>
			<td><?php echo h($path); ?><?php echo $i === 0 ? '　←このinstall.phpに対応する導入先' : ''; ?></td>
			<td><?php echo h(ksphp_install_extract_version($path) ?? '(検出できず)'); ?></td>
			<td><?php echo h(date('Y-m-d H:i', filemtime($path))); ?></td>
			<td class="<?php echo $pair['has_conf'] ? 'ok' : 'ng'; ?>"><?php echo h($pair['verdict']); ?></td>
		</tr>
	<?php endforeach; ?>
	</table>
	<?php if (count($candidates) > 1): ?>
		<p class="ng">複数のbbs.phpが近隣に見つかりました。チェックボックスで導入先を選び、複数選択も可能です。</p>
	<?php endif; ?>
<?php endif; ?>

<h2>2. 導入対象（install/newbbs/、<?php echo count($newbbs_files); ?>ファイル）</h2>
<?php if (empty($newbbs_files)): ?>
	<p class="ng">install/newbbs/ にファイルがありません。</p>
<?php else: ?>
	<p><?php echo h(implode('、', array_slice($newbbs_files, 0, 8))); ?><?php echo count($newbbs_files) > 8 ? ' 他' . (count($newbbs_files) - 8) . '件' : ''; ?></p>
<?php endif; ?>

<h2 id="h-write">3. 書き込み権限（主対象：<?php echo h($parent_dir); ?> のみ表示）</h2>
<table id="write-table">
<tr><th>場所</th><th>状態</th><th>備考</th></tr>
<tr><td>サイトルート</td><td class="<?php echo $write_root['ok'] ? 'ok' : 'ng'; ?>" data-key="write_root_state"><?php echo $write_root['ok'] ? 'OK' : 'NG'; ?></td><td data-key="write_root_note"><?php echo h($write_root['note']); ?></td></tr>
<tr><td>install/backup/</td><td class="<?php echo $write_backup['ok'] ? 'ok' : 'ng'; ?>"><?php echo $write_backup['ok'] ? 'OK' : 'NG'; ?></td><td><?php echo h($write_backup['note']); ?></td></tr>
<tr><td>data/</td><td class="<?php echo $write_data['ok'] ? 'ok' : 'ng'; ?>" data-key="write_data_state"><?php echo $write_data['ok'] ? 'OK' : 'NG'; ?></td><td data-key="write_data_note"><?php echo h($write_data['note']); ?></td></tr>
<tr><td>logs/</td><td class="<?php echo $write_logs['ok'] ? 'ok' : 'ng'; ?>" data-key="write_logs_state"><?php echo $write_logs['ok'] ? 'OK' : 'NG'; ?></td><td data-key="write_logs_note"><?php echo h($write_logs['note']); ?></td></tr>
</table>
<?php if (!$write_root['ok']): ?>
<p class="ng">サイトルートに書き込めません。多くの場合、ファイルの所有者（例：ubuntu）とApache/PHPの実行ユーザー（例：www-data）が異なることが原因です。doc/permissions.md を参照してください。</p>
<?php endif; ?>

<h2 id="h-migrate">4. Migration Engineの状態</h2>
<p id="migrate-status"><?php echo $migrated !== null ? '<span class="ok">移行済みです（' . h($migrated) . '）。</span>' : 'まだ移行されていません。'; ?></p>

<h2>5. conf.php設定の概要（導入先に既存のものがある場合）</h2>
<div id="conf-summary-container"></div>
<script id="conf-summaries-data" type="application/json"><?php echo json_encode($conf_summaries, JSON_UNESCAPED_UNICODE); ?></script>
<script id="conf-summaries-paths" type="application/json"><?php echo json_encode(array_values($candidates), JSON_UNESCAPED_UNICODE); ?></script>

<h2>6. セットアップの実行</h2>
<p>install/newbbs/ の内容を、上でチェックした導入先へ順番に導入します。上書きされる既存ファイルはすべて事前に、それぞれの導入先ごとの <code>install/backup/targetN/</code> へバックアップされます。</p>
<button id="run-setup-btn">セットアップを実行する</button>
<ul id="setup-log"></ul>

<hr>
<p>診断部分はコードやデータを変更しません（書き込みテスト用の一時ファイルは即座に削除しています）。セットアップ実行時のみ、上記の通りバックアップの上でファイルをコピーします。</p>

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

	var indices = Array.prototype.slice.call(document.querySelectorAll('.target-checkbox:checked'))
		.map(function (cb) { return cb.getAttribute('data-index'); });

	if (indices.length === 0) {
		container.innerHTML = '<p>導入先が選択されていません。</p>';
		return;
	}

	var multi = indices.length > 1;
	var html = '';
	indices.forEach(function (idx) {
		var summary = summaries[idx];
		var path = paths[idx] || ('対象#' + idx);
		if (multi) {
			html += '<p><code>' + escapeHtml(path) + '</code></p>';
		}
		if (summary) {
			html += '<table>'
				+ '<tr><th>BBSTITLE</th><td>' + escapeHtml(summary.BBSTITLE) + '</td></tr>'
				+ '<tr><th>LANGUAGE_FILE</th><td>' + escapeHtml(summary.LANGUAGE_FILE) + '</td></tr>'
				+ '</table>';
		} else {
			html += '<p>まだconf.phpがありません（新規導入前）。</p>';
		}
	});
	container.innerHTML = html;
}

renderConfSummaries();

document.getElementById('run-setup-btn').addEventListener('click', function () {
	var btn = this;
	var logList = document.getElementById('setup-log');
	var indices = Array.prototype.slice.call(document.querySelectorAll('.target-checkbox:checked'))
		.map(function (cb) { return cb.getAttribute('data-index'); });

	logList.innerHTML = '';

	if (indices.length === 0) {
		var noneLi = document.createElement('li');
		noneLi.className = 'ng';
		noneLi.textContent = '導入先が選択されていません。チェックボックスで1つ以上選んでください。';
		logList.appendChild(noneLi);
		return;
	}

	btn.disabled = true;
	btn.textContent = '実行中…';

	var targetIdx = 0;
	var hadFailure = false;
	function runNextTarget() {
		if (targetIdx >= indices.length) {
			if (hadFailure) {
				btn.textContent = '完了しました（一部エラーあり）';
				var doneLi = document.createElement('li');
				doneLi.className = 'ng';
				doneLi.textContent = 'すべての導入先の処理が終わりましたが、一部でエラー（赤字）がありました。ログの内容を必ずご確認ください。最新の状態を見るにはページを再読み込みしてください。';
				logList.appendChild(doneLi);
			} else {
				btn.textContent = '完了しました';
				var doneLi = document.createElement('li');
				doneLi.textContent = 'すべての導入先の処理が終わりました。最新の状態を見るにはページを再読み込みしてください。';
				logList.appendChild(doneLi);
			}
			return;
		}
		var idx = indices[targetIdx];
		var header = document.createElement('li');
		header.innerHTML = '<strong>--- 導入先 #' + idx + ' ---</strong>';
		logList.appendChild(header);

		fetch('?ajax=1&action=run_setup&target=' + encodeURIComponent(idx))
			.then(function (res) { return res.json(); })
			.then(function (data) {
				var lines = data.log || [];
				var entryFilename = data.entry_filename || 'bbs.php';
				var i = 0;
				function showNext() {
					if (i >= lines.length) {
						var linkLi = document.createElement('li');
						linkLi.innerHTML = '<a href="?ajax=0" data-bbs-index="' + idx + '" data-entry-filename="' + entryFilename + '" class="bbs-link" target="_blank">→ この導入先の' + entryFilename + 'を開く</a>';
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
				li.textContent = '導入先 #' + idx + ' で通信エラーが発生しました。';
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
		} else {
			e.preventDefault();
			alert('導入先#' + idx + ' は、このinstall.phpの近隣スキャンで見つかった別のフォルダです。ブラウザで直接URLを開くには、そのフォルダのURLを手動で確認してください（本体ファイル名: ' + entryFilename + '）。');
		}
	}
});
</script>
</body>
</html>
