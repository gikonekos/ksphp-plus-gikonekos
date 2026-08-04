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

function ksphp_install_check_pair(string $bbs_php_path): array {
	$dir = dirname($bbs_php_path);
	$has_conf    = file_exists($dir . '/conf.php');
	$has_migrate = file_exists($dir . '/migrate.php');

	if ($has_conf && $has_migrate) {
		$verdict = 'KSPHP Plus系（conf.php・migrate.php確認、本ツール対応版の可能性が高い）';
	} elseif ($has_conf) {
		$verdict = 'conf.phpあり・migrate.phpなし（KSPHP Plus系だが本ツール未対応の旧バージョンの可能性）';
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

function ksphp_install_run(string $newbbs_dir, string $parent_dir, string $install_dir): array {
	$log = array();
	$files = ksphp_install_list_files($newbbs_dir);

	if (empty($files)) {
		$log[] = array('ok' => false, 'text' => 'install/newbbs/ にファイルが見つかりません。導入対象がありません。');
		return $log;
	}

	$date = gmdate('Y-m-d');
	$n = 1;
	do {
		$backup_dir = $install_dir . '/backup/' . $date . '-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
		$n++;
	} while (file_exists($backup_dir));

	$any_existing = false;
	foreach ($files as $rel) {
		if (file_exists($parent_dir . '/' . $rel)) {
			$any_existing = true;
			break;
		}
	}

	if ($any_existing) {
		if (!@mkdir($backup_dir, 0755, true)) {
			$log[] = array('ok' => false, 'text' => 'バックアップ先フォルダを作成できませんでした。導入を中止します。');
			return $log;
		}
		$log[] = array('ok' => true, 'text' => "バックアップ先: install/backup/" . basename($backup_dir));
	}

	foreach ($files as $rel) {
		$src = $newbbs_dir . '/' . $rel;
		$dst = $parent_dir . '/' . $rel;
		$dst_dir = dirname($dst);

		if (!is_dir($dst_dir)) {
			@mkdir($dst_dir, 0755, true);
		}

		if (file_exists($dst)) {
			$backup_target = $backup_dir . '/' . $rel;
			$backup_target_dir = dirname($backup_target);
			if (!is_dir($backup_target_dir)) {
				@mkdir($backup_target_dir, 0755, true);
			}
			if (!@copy($dst, $backup_target)) {
				$log[] = array('ok' => false, 'text' => "スキップ: {$rel}（既存ファイルのバックアップに失敗したため上書きしていません）");
				continue;
			}
		}

		if (@copy($src, $dst)) {
			$log[] = array('ok' => true, 'text' => "導入: {$rel}");
		} else {
			$log[] = array('ok' => false, 'text' => "失敗: {$rel}（コピーできませんでした。権限を確認してください）");
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

if (($_GET['ajax'] ?? '') === '1' && ($_GET['action'] ?? '') === 'run_setup') {
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode(
		array('log' => ksphp_install_run($newbbs_dir, $parent_dir, $install_dir)),
		JSON_UNESCAPED_UNICODE
	);
	exit;
}

if (($_GET['ajax'] ?? '') === '1' && ($_GET['action'] ?? '') === 'status') {
	header('Content-Type: application/json; charset=UTF-8');
	$w_root = ksphp_install_check_writable($parent_dir);
	$w_data = ksphp_install_check_writable($parent_dir . '/data');
	$w_logs = ksphp_install_check_writable($parent_dir . '/logs');
	$marker = $parent_dir . '/data/.migrated';
	echo json_encode(array(
		'write_root' => $w_root,
		'write_data' => $w_data,
		'write_logs' => $w_logs,
		'migrated'   => file_exists($marker) ? trim((string) @file_get_contents($marker)) : null,
	), JSON_UNESCAPED_UNICODE);
	exit;
}

$candidates = array();

$primary = $parent_dir . '/bbs.php';
if (file_exists($primary)) {
	$candidates[] = $primary;
}

if (is_dir($grandparent_dir)) {
	$sibling_bbs = $grandparent_dir . '/bbs.php';
	if (file_exists($sibling_bbs) && !in_array($sibling_bbs, $candidates, true)) {
		$candidates[] = $sibling_bbs;
	}
	$entries = @scandir($grandparent_dir);
	if ($entries !== false) {
		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$maybe = $grandparent_dir . '/' . $entry . '/bbs.php';
			if (is_dir($grandparent_dir . '/' . $entry) && file_exists($maybe) && !in_array($maybe, $candidates, true)) {
				$candidates[] = $maybe;
			}
		}
	}
}

$newbbs_files = ksphp_install_list_files($newbbs_dir);

$write_root   = ksphp_install_check_writable($parent_dir);
$write_backup = ksphp_install_check_writable($install_dir . '/backup');
$write_data   = ksphp_install_check_writable($parent_dir . '/data');
$write_logs   = ksphp_install_check_writable($parent_dir . '/logs');

$migrated_marker = $parent_dir . '/data/.migrated';
$migrated = file_exists($migrated_marker) ? trim((string) @file_get_contents($migrated_marker)) : null;

$conf_summary = null;
if (file_exists($parent_dir . '/conf.php')) {
	$CONF = array();
	include $parent_dir . '/conf.php';
	if (isset($CONF) && is_array($CONF)) {
		$conf_summary = array(
			'BBSTITLE'      => $CONF['BBSTITLE'] ?? '(未設定)',
			'LANGUAGE_FILE' => $CONF['LANGUAGE_FILE'] ?? '(未設定)',
		);
	}
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
	<table>
	<tr><th>パス</th><th>VERSION</th><th>最終更新</th><th>判定</th></tr>
	<?php foreach ($candidates as $i => $path): $pair = ksphp_install_check_pair($path); ?>
		<tr>
			<td><?php echo h($path); ?><?php echo $i === 0 ? '　←このinstall.phpに対応する導入先' : ''; ?></td>
			<td><?php echo h(ksphp_install_extract_version($path) ?? '(検出できず)'); ?></td>
			<td><?php echo h(date('Y-m-d H:i', filemtime($path))); ?></td>
			<td class="<?php echo $pair['has_conf'] ? 'ok' : 'ng'; ?>"><?php echo h($pair['verdict']); ?></td>
		</tr>
	<?php endforeach; ?>
	</table>
	<?php if (count($candidates) > 1): ?>
		<p class="ng">複数のbbs.phpが近隣に見つかりました。アクセスするURLを間違えないよう注意してください。</p>
	<?php endif; ?>
<?php endif; ?>

<h2>2. 導入対象（install/newbbs/、<?php echo count($newbbs_files); ?>ファイル）</h2>
<?php if (empty($newbbs_files)): ?>
	<p class="ng">install/newbbs/ にファイルがありません。</p>
<?php else: ?>
	<p><?php echo h(implode('、', array_slice($newbbs_files, 0, 8))); ?><?php echo count($newbbs_files) > 8 ? ' 他' . (count($newbbs_files) - 8) . '件' : ''; ?></p>
<?php endif; ?>

<h2 id="h-write">3. 書き込み権限</h2>
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
<?php if ($conf_summary !== null): ?>
	<table>
	<tr><th>BBSTITLE</th><td><?php echo h($conf_summary['BBSTITLE']); ?></td></tr>
	<tr><th>LANGUAGE_FILE</th><td><?php echo h($conf_summary['LANGUAGE_FILE']); ?></td></tr>
	</table>
<?php else: ?>
	<p>まだconf.phpがありません（新規導入前）。</p>
<?php endif; ?>

<h2>6. セットアップの実行</h2>
<p>install/newbbs/ の内容をサイトルートへ導入します。上書きされる既存ファイルはすべて事前に <code>install/backup/</code> へバックアップされます。</p>
<button id="run-setup-btn">セットアップを実行する</button>
<ul id="setup-log"></ul>

<hr>
<p>診断部分はコードやデータを変更しません（書き込みテスト用の一時ファイルは即座に削除しています）。セットアップ実行時のみ、上記の通りバックアップの上でファイルをコピーします。</p>

<script>
document.getElementById('run-setup-btn').addEventListener('click', function () {
	var btn = this;
	var logList = document.getElementById('setup-log');
	btn.disabled = true;
	btn.textContent = '実行中…';
	logList.innerHTML = '';

	fetch('?ajax=1&action=run_setup')
		.then(function (res) { return res.json(); })
		.then(function (data) {
			var lines = data.log || [];
			var i = 0;
			function showNext() {
				if (i >= lines.length) {
					btn.textContent = '完了しました';
					refreshStatus();
					var linkLi = document.createElement('li');
					linkLi.innerHTML = '<a href="../bbs.php" target="_blank">→ bbs.php を開く</a>';
					logList.appendChild(linkLi);
					return;
				}
				var li = document.createElement('li');
				li.className = lines[i].ok ? 'ok' : 'ng';
				li.textContent = lines[i].text;
				logList.appendChild(li);
				i++;
				setTimeout(showNext, 80);
			}
			showNext();
		})
		.catch(function () {
			var li = document.createElement('li');
			li.className = 'ng';
			li.textContent = '通信エラーが発生しました。ページを再読み込みしてもう一度お試しください。';
			logList.appendChild(li);
			btn.disabled = false;
			btn.textContent = 'セットアップを実行する';
		});
});

function refreshStatus() {
	fetch('?ajax=1&action=status')
		.then(function (res) { return res.json(); })
		.then(function (data) {
			setCell('write_root_state', data.write_root.ok ? 'OK' : 'NG', data.write_root.ok);
			setCell('write_root_note', data.write_root.note, null);
			setCell('write_data_state', data.write_data.ok ? 'OK' : 'NG', data.write_data.ok);
			setCell('write_data_note', data.write_data.note, null);
			setCell('write_logs_state', data.write_logs.ok ? 'OK' : 'NG', data.write_logs.ok);
			setCell('write_logs_note', data.write_logs.note, null);

			var migrateEl = document.getElementById('migrate-status');
			if (data.migrated) {
				migrateEl.innerHTML = '<span class="ok">移行済みです（' + data.migrated + '）。</span>';
			} else {
				migrateEl.textContent = 'まだ移行されていません。';
			}
		});
}

function setCell(key, text, okOrNull) {
	var el = document.querySelector('[data-key="' + key + '"]');
	if (!el) { return; }
	el.textContent = text;
	if (okOrNull !== null) {
		el.className = okOrNull ? 'ok' : 'ng';
	}
}
</script>
</body>
</html>
