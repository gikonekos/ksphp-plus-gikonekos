/*
 * longpostfilter.js
 * 20260801 Gikoneko: 長文NGフィルター（個人設定・ブラウザローカル）。
 *
 * 通常表示画面（div.m > pre.msgnormal）の各投稿について、本文の
 * 行数がしきい値を超えていたら折りたたんで表示する。サーバー側には
 * 一切影響しない、完全にクライアント側の個人設定機能。
 * 折りたたまれた投稿は「表示」リンクでいつでも展開できる。
 *
 * 有効/無効・行数しきい値は「個人環境設定」パネルのJS設定セクションで
 * 切り替える。既定は無効（オプトイン）、しきい値の既定値は30行。
 */
(function () {
	'use strict';

	var LANG = window.KSPHP_LANG || {};
	function L(key) {
		return (typeof LANG[key] === 'string') ? LANG[key] : key;
	}
	function Lf(key, n) {
		return L(key).replace('{N}', String(n));
	}

	var LEGACY_STORAGE_ENABLED = 'ksphp_longpost_enabled';
	var LEGACY_STORAGE_THRESHOLD = 'ksphp_longpost_threshold';
	var DEFAULT_THRESHOLD = 30;

	// 2026-08-01 Gikoneko: 設定の保存先を「個人環境設定」パネル（サーバー
	// cookie 'ksphp_js'、window.KSPHP_SETTINGS経由）に統合。旧バージョン
	// のlocalStorageキーが残っていれば初回のみ優先する（後方互換）。
	function isEnabled() {
		// 2026-08-02 Gikoneko: 管理者ロック（conf.phpのJS_DEFAULT_*=0、
		// window.KSPHP_SETTINGS_LOCKED経由）はlegacy localStorageより
		// 優先する。これがないとRC10-12からの残存値でロックを
		// 貫通できてしまう。
		var locked = window.KSPHP_SETTINGS_LOCKED;
		if (Array.isArray(locked) && locked.indexOf('longpost') !== -1) {
			return false;
		}
		try {
			var legacy = window.localStorage.getItem(LEGACY_STORAGE_ENABLED);
			if (legacy !== null) {
				return legacy === '1';
			}
		} catch (e) { /* localStorage無効環境では無視 */ }
		var s = window.KSPHP_SETTINGS || {};
		return s.longpost === 1;
	}

	function getThreshold() {
		try {
			var legacy = window.localStorage.getItem(LEGACY_STORAGE_THRESHOLD);
			if (legacy !== null) {
				var lv = parseInt(legacy, 10);
				if (Number.isFinite(lv) && lv > 0) {
					return lv;
				}
			}
		} catch (e) { /* localStorage無効環境では無視 */ }
		var s = window.KSPHP_SETTINGS || {};
		var v = parseInt(s.longpost_th, 10);
		return (Number.isFinite(v) && v > 0) ? v : DEFAULT_THRESHOLD;
	}

	function injectStyle() {
		if (document.getElementById('ksphp-longpost-style')) {
			return;
		}
		var style = document.createElement('style');
		style.id = 'ksphp-longpost-style';
		style.textContent =
			'.ksphp-longpost-collapsed{border:1px dashed #888;padding:0.3em 0.6em;font-size:0.85em;}' +
			'.ksphp-longpost-collapsed a{margin-left:0.5em;}';
		document.head.appendChild(style);
	}

	function countLines(text) {
		if (text === '') {
			return 0;
		}
		return text.split('\n').length;
	}

	document.addEventListener('DOMContentLoaded', function () {
		var posts = document.querySelectorAll('div.m');
		if (!posts.length) {
			return; // 通常表示画面以外では何もしない。
		}
		injectStyle();

		// 各投稿の <pre class="msgnormal"> を見つけ、折りたたみ用の
		// プレースホルダをあらかじめ用意しておく（表示/非表示を切り替える
		// だけにして、DOMの生成・破棄を繰り返さないようにする）。
		var entries = [];
		posts.forEach(function (post) {
			var pre = post.querySelector('pre.msgnormal');
			if (!pre) {
				return;
			}
			var lines = countLines(pre.textContent);

			var placeholder = document.createElement('div');
			placeholder.className = 'ksphp-longpost-collapsed';
			placeholder.style.display = 'none';
			var textSpan = document.createElement('span');
			textSpan.textContent = Lf('LONGPOST_COLLAPSED_TEXT', lines);
			var expandLink = document.createElement('a');
			expandLink.href = '#';
			expandLink.textContent = '[' + L('LONGPOST_EXPAND_LINK') + ']';
			placeholder.appendChild(textSpan);
			placeholder.appendChild(expandLink);
			pre.parentNode.insertBefore(placeholder, pre);

			var collapseLink = document.createElement('a');
			collapseLink.href = '#';
			collapseLink.textContent = '[' + L('LONGPOST_COLLAPSE_LINK') + ']';
			collapseLink.style.display = 'none';
			collapseLink.style.fontSize = '0.85em';
			pre.parentNode.insertBefore(collapseLink, pre.nextSibling);

			function collapse() {
				pre.style.display = 'none';
				collapseLink.style.display = 'none';
				placeholder.style.display = '';
			}
			// 20260801 Gikoneko: 折りたたむリンクは「実際に折りたたみ対象と
			// なった投稿を展開した後」にのみ表示する。しきい値未満の投稿
			// では一度も折りたたまれないため、このリンクを表示する必要が
			// ない（常時表示だと閲覧のたびに気疲れするとの指摘を受けて修正）。
			function expand(showCollapseLink) {
				pre.style.display = '';
				collapseLink.style.display = showCollapseLink ? '' : 'none';
				placeholder.style.display = 'none';
			}

			expandLink.addEventListener('click', function (ev) {
				ev.preventDefault();
				expand(true); // 手動展開時は「折りたたむ」リンクを見せる
			});
			collapseLink.addEventListener('click', function (ev) {
				ev.preventDefault();
				collapse();
			});

			entries.push({ lines: lines, collapse: collapse, expand: expand });
		});

		function applyFilter() {
			var enabled = isEnabled();
			var threshold = getThreshold();
			entries.forEach(function (entry) {
				if (enabled && entry.lines > threshold) {
					entry.collapse();
				} else {
					// しきい値未満（またはフィルター無効）の投稿は、そもそも
					// 折りたたまれていないので「折りたたむ」リンクも不要。
					entry.expand(false);
				}
			});
		}

		// 2026-08-01 Gikoneko: 有効/無効・しきい値は「個人環境設定」パネル
		// のJS設定セクションで切り替える（window.KSPHP_SETTINGS経由）。
		applyFilter();
	});
})();
