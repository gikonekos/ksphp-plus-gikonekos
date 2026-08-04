<?php

/**
 * migrate.php — KSPHP Plus Migration Engine（初版ドラフト）
 *
 * 旧構成（ルート直下にデータ・ログファイルが散在する構成）から、
 * data/ ・ logs/ への新構成へ、初回起動時のみ自動で移行する。
 *
 * 設計方針（doc/migration-engine-spec-2026-07-19-01.txt 参照）：
 *  - bbs.php本体には組み込まず、独立ファイルとする
 *    （毎リクエストごとの検索・バックアップ試行を避けるため、
 *    また bbs.php 自体が上書きコピーされる危険を避けるため）。
 *  - 呼び出しはbbs.php側からrequire_onceし、ksphp_migrate()を
 *    1回呼ぶだけでよい。マーカーファイル（data/.migrated）が
 *    存在すれば即return するため、2回目以降のコストはfile_exists()
 *    1回分のみ。
 *  - 移行前に旧データをbackup/YYYY-MM-DD-NN/へ丸ごとコピーする
 *    （フォルダ分離方式。ファイル名はリネームしない）。
 *  - 元ファイルは移行成功確認まで削除しない（rename()は
 *    バックアップコピー成功後にのみ行う）。
 *
 * 呼び出し元：bbs.phpのconf.php読み込み直後からrequire_once・
 * ksphp_migrate()呼び出しで組み込み済み（2026-07-19）。conf.php側の
 * パス設定（LOGFILENAME・CNTFILENAME・OLDLOGFILEDIR・COUNTFILE）も
 * 新構成パスへ書き換え済み。
 *
 * 移行対象範囲（2026-07-19最終決定、doc/migration-engine-spec-
 * 2026-07-19-01.txt 10節参照）：
 *   bbs.log              (logs/)   … テキストで軽量なため対象
 *   log/                 (logs/log/)
 *   bbs.cnt              (data/)
 *   count/               (data/count/)
 *   gikoneko_kotoba.dat  (data/)
 *
 * 対象外（バックアップコストが重いため、現状のパスのまま流用）：
 *   upload/（画像アップロード）
 *   archive/（過去ログzip、conf.phpのZIPDIR）
 *
 * 既知の制約：同時に複数リクエストが初回移行を踏んだ場合の排他制御
 * は行っていない（小規模コミュニティ向けの低トラフィック環境を
 * 前提とし、file lockによる直列化までは実装していない）。競合時は
 * 一方が安全にリトライされるのみで、データ破損は起きない設計だが、
 * 厳密な原子性が必要な環境では別途対応を検討すること。
 */

if (!defined('KSPHP_ROOT')) {
	define('KSPHP_ROOT', __DIR__);
}

function ksphp_migrate(): void {

	$root = KSPHP_ROOT;
	$marker = $root . '/data/.migrated';

	// 移行済みなら即終了。file_exists() 1回分のコストのみ。
	if (file_exists($marker)) {
		return;
	}

	// 移行対象ファイル／ディレクトリと、移行先トップディレクトリの対応。
	// キー：ルート直下の相対パス、値：移行先（'data' または 'logs'）。
	$targets = array(
		'bbs.log'             => 'logs',
		'log'                 => 'logs',
		'bbs.cnt'             => 'data',
		'count'               => 'data',
		'gikoneko_kotoba.dat' => 'data',
	);

	$found_any = false;
	foreach ($targets as $name => $dest) {
		if (file_exists($root . '/' . $name)) {
			$found_any = true;
			break;
		}
	}

	if (!$found_any) {
		// 旧構成のファイルが1つも無い＝新規設置とみなし、
		// マーカーだけ立てて終了（次回以降の判定コストを避ける）。
		ksphp_migrate_mark_done($root, $marker);
		return;
	}

	// バックアップ先フォルダを決定（同日中は連番、フォルダ分離方式で
	// 同名ファイルの上書き衝突を避ける）。
	$date = gmdate('Y-m-d');
	$n = 1;
	do {
		$backup_dir = $root . '/backup/' . $date . '-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
		$n++;
	} while (file_exists($backup_dir));

	if (!@mkdir($backup_dir, 0755, true)) {
		// バックアップ先を作れない場合は、確実性を優先して移行を
		// 中止する（中途半端な移行は行わない）。
		return;
	}

	$log = array();
	$log[] = gmdate('Y-m-d\TH:i') . ' UTC start: legacy structure detected';

	foreach ($targets as $name => $dest) {
		$src = $root . '/' . $name;
		if (!file_exists($src)) {
			continue;
		}

		$dest_dir = $root . '/' . $dest;
		if (!is_dir($dest_dir)) {
			@mkdir($dest_dir, 0755, true);
		}

		$dest_path = $dest_dir . '/' . $name;
		if (file_exists($dest_path)) {
			// 移行先に既に同名の何かがある（想定外の状態）。上書きは
			// 危険なため、確実性を優先してスキップする（元データはルート
			// に残るのでデータ消失はない）。
			$log[] = gmdate('Y-m-d\TH:i') . " UTC skip: {$dest}/{$name} already exists, not migrated";
			continue;
		}

		if (is_dir($src)) {
			$backup_target = $backup_dir . '/' . $name;
			if (!ksphp_migrate_copy_recursive($src, $backup_target)) {
				// このディレクトリのバックアップに失敗した場合は、
				// 確実性を優先してこのディレクトリの移行はスキップする
				// （元データはそのままルートに残るのでデータ消失はない）。
				$log[] = gmdate('Y-m-d\TH:i') . " UTC skip: {$name}/ backup failed, not migrated";
				continue;
			}
			$log[] = gmdate('Y-m-d\TH:i') . " UTC backup: {$name}/ -> backup/" . basename($backup_dir) . "/{$name}/";

			if (@rename($src, $dest_path)) {
				$log[] = gmdate('Y-m-d\TH:i') . " UTC migrate: {$name}/ -> {$dest}/{$name}/";
			} else {
				$log[] = gmdate('Y-m-d\TH:i') . " UTC skip: {$name}/ rename failed, left in place";
			}
		} else {
			if (!@copy($src, $backup_dir . '/' . $name)) {
				$log[] = gmdate('Y-m-d\TH:i') . " UTC skip: {$name} backup failed, not migrated";
				continue;
			}
			$log[] = gmdate('Y-m-d\TH:i') . " UTC backup: {$name} -> backup/" . basename($backup_dir) . "/{$name}";

			if (@rename($src, $dest_path)) {
				$log[] = gmdate('Y-m-d\TH:i') . " UTC migrate: {$name} -> {$dest}/{$name}";
			} else {
				$log[] = gmdate('Y-m-d\TH:i') . " UTC skip: {$name} rename failed, left in place";
			}
		}
	}

	$log[] = gmdate('Y-m-d\TH:i') . ' UTC done: migration finished';
	@file_put_contents($backup_dir . '/migration.log', implode("\r\n", $log) . "\r\n");

	ksphp_migrate_mark_done($root, $marker);
}

/**
 * ディレクトリを再帰的にコピーする（バックアップ用）。
 * 失敗時はfalseを返す（呼び出し側で元データの移行を見送るための判定に使う）。
 */
function ksphp_migrate_copy_recursive( string $src, string $dst ): bool {
	if (!@mkdir($dst, 0755, true) && !is_dir($dst)) {
		return false;
	}

	$items = @scandir($src);
	if ($items === false) {
		return false;
	}

	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$src_path = $src . '/' . $item;
		$dst_path = $dst . '/' . $item;

		if (is_dir($src_path)) {
			if (!ksphp_migrate_copy_recursive($src_path, $dst_path)) {
				return false;
			}
		} else {
			if (!@copy($src_path, $dst_path)) {
				return false;
			}
		}
	}

	return true;
}

/**
 * マーカーファイルを作成し、以降のksphp_migrate()呼び出しを
 * file_exists() 1回で即終了させる。
 */
function ksphp_migrate_mark_done( string $root, string $marker ): void {
	if (!is_dir($root . '/data')) {
		@mkdir($root . '/data', 0755, true);
	}
	@file_put_contents($marker, gmdate('Y-m-d\TH:i') . " UTC\r\n");
}
