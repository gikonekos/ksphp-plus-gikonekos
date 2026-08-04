<?php

/**
 * migrate.php — KSPHP Plus Migration Engine（2026-07-19 廃止・簡略化）
 *
 * 【経緯】
 * 当初は、旧構成（ルート直下にデータ・ログファイルが散在する構成）
 * から data/・logs/ への新構成へ、初回起動時に自動でファイル移動
 * および conf.php 側のパス設定（LOGFILENAME・CNTFILENAME・
 * OLDLOGFILEDIR・COUNTFILE）の書き換えまで行う設計だった。
 *
 * しかし、この「良かれと思って」の自動パス書き換えが実機で
 * OLDLOGFILEDIR不整合の原因となった（2026-07-19）。調査の結果、
 * install.php側の新規キー穴埋め処理についても同様の問題が見つかり、
 * 「パス指定（confの設定値）は自動で推測・変更しない」方針を
 * プロジェクト全体の運用原則として採用した。
 *
 * この方針に伴い、ファイル移動・conf.phpパス書き換えを行う機能
 * そのものを廃止した（過去のドラフト実装は削除済み。必要になる
 * とも考えにくいため、コメントとしても残さない）。
 *
 * 【現在の挙動】
 * 何もしない。旧設置のデータ・ログファイルは、物理的な場所も
 * conf.php側のパス設定も、一切変更されない（通常のconf.phpマージと
 * 同じ扱いで、特別扱いをしない）。マーカーファイル（data/.migrated）
 * を作成し、以降の呼び出しをfile_exists()1回分のコストで即終了させる
 * だけの、後方互換のための薄いラッパーとして存続する。
 *
 * 呼び出し元：bbs.php・install.phpからrequire_once・ksphp_migrate()
 * 呼び出しで組み込み済み。呼び出し側の変更は不要。
 */

if (!defined('KSPHP_ROOT')) {
	define('KSPHP_ROOT', __DIR__);
}

/**
 * 後方互換のための薄いラッパー。data/.migrated が無ければ作成して
 * 終了するのみで、ファイル移動・conf.php書き換えは一切行わない。
 */
function ksphp_migrate(): void {
	$root = KSPHP_ROOT;
	$marker = $root . '/data/.migrated';

	if (file_exists($marker)) {
		return;
	}

	if (!is_dir($root . '/data')) {
		@mkdir($root . '/data', 0755, true);
	}
	@file_put_contents($marker, gmdate('Y-m-d\TH:i') . " UTC\r\n");
}
