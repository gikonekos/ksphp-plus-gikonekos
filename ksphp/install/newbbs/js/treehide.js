/*
 * treehide.js
 * 20260801 Gikoneko: 未読ツリー削除機能（個人設定・ブラウザローカル）。
 *
 * ツリー表示画面（bbstree.php が出力する pre.msgtree ブロック単位）に
 * 「消」リンクを追加し、クリックしたツリーをこのブラウザ上でだけ
 * 非表示にする。サーバー側には一切影響しない、完全にクライアント側の
 * 個人設定機能。非表示にしたツリーは、いつでもパネルから個別に
 * 復元、または「すべて復元」で一括復元できる。
 *
 * 有効/無効は「個人環境設定」パネルのJS設定セクションで切り替える。
 * 既定は無効（オプトイン）。
 */
(function () {
	'use strict';

	var LANG = window.KSPHP_LANG || {};
	function L(key) {
		return (typeof LANG[key] === 'string') ? LANG[key] : key;
	}

	var LEGACY_STORAGE_KEY = 'ksphp_treehide_enabled';
	var STORAGE_HIDDEN = 'ksphp_treehide_hidden';

	// 2026-08-01 Gikoneko: 設定の保存先を「個人環境設定」パネル（サーバー
	// cookie 'ksphp_js'、window.KSPHP_SETTINGS.treehide経由）に統合。
	// 旧バージョンのlocalStorageキーが残っていれば初回のみ優先する
	// （非表示にしたツリーIDの一覧＝STORAGE_HIDDENは今後もlocalStorage
	// のまま。これはON/OFF設定ではなくブラウザ固有の作業データのため）。
	function isEnabled() {
		// 2026-08-02 Gikoneko: 管理者ロック（conf.phpのJS_DEFAULT_*=0、
		// window.KSPHP_SETTINGS_LOCKED経由）はlegacy localStorageより
		// 優先する。これがないとRC10-12からの残存値でロックを
		// 貫通できてしまう。
		var locked = window.KSPHP_SETTINGS_LOCKED;
		if (Array.isArray(locked) && locked.indexOf('treehide') !== -1) {
			return false;
		}
		try {
			var legacy = window.localStorage.getItem(LEGACY_STORAGE_KEY);
			if (legacy !== null) {
				return legacy === '1';
			}
		} catch (e) { /* localStorage無効環境では無視 */ }
		var s = window.KSPHP_SETTINGS || {};
		return s.treehide === 1;
	}

	function loadHiddenSet() {
		try {
			var raw = window.localStorage.getItem(STORAGE_HIDDEN);
			var arr = raw ? JSON.parse(raw) : [];
			if (!Array.isArray(arr)) {
				return {};
			}
			var set = {};
			arr.forEach(function (id) { set[id] = true; });
			return set;
		} catch (e) {
			return {};
		}
	}

	function saveHiddenSet(set) {
		try {
			window.localStorage.setItem(STORAGE_HIDDEN, JSON.stringify(Object.keys(set)));
		} catch (e) { /* 保存できなくても致命的ではないので無視 */ }
	}

	// pre.msgtree の先頭リンク（href="...&m=t&s=THREADID"）からスレッドIDを取り出す。
	function extractThreadId(block) {
		var a = block.querySelector('a[href*="m=t"]');
		if (!a) {
			return null;
		}
		var m = a.getAttribute('href').match(/[?&]s=([^&]+)/);
		return m ? decodeURIComponent(m[1]) : null;
	}

	function escapeHtml(s) {
		var div = document.createElement('div');
		div.textContent = String(s);
		return div.innerHTML;
	}

	function injectStyle() {
		if (document.getElementById('ksphp-treehide-style')) {
			return;
		}
		var style = document.createElement('style');
		style.id = 'ksphp-treehide-style';
		style.textContent =
			'.ksphp-treehide-toggle{font-size:0.85em;margin:0.3em 0;}' +
			'.ksphp-treehide-panel{font-size:0.85em;border:1px dashed #888;padding:0.3em 0.6em;margin:0.3em 0;}' +
			'.ksphp-treehide-panel ul{margin:0.2em 0 0;padding-left:1.2em;}' +
			'.ksphp-treehide-empty{color:#888;list-style:none;margin-left:-1.2em;}' +
			'.ksphp-treehide-link{font-size:0.85em;text-decoration:none;border:1px solid #888;padding:0 0.3em;margin-left:0.3em;}';
		document.head.appendChild(style);
	}

	document.addEventListener('DOMContentLoaded', function () {
		var blocks = document.querySelectorAll('pre.msgtree');
		if (!blocks.length) {
			return; // ツリー表示画面以外では何もしない。
		}
		injectStyle();

		var hidden = loadHiddenSet();
		var panelList = null; // 復元パネルの<ul>要素（後で生成）。

		function refreshPanel() {
			if (!panelList) {
				return;
			}
			panelList.innerHTML = '';
			var found = false;
			blocks.forEach(function (block) {
				var id = block.dataset.ksphpTreeId;
				if (id && hidden[id]) {
					found = true;
					var li = document.createElement('li');
					var restoreLink = document.createElement('a');
					restoreLink.href = '#';
					restoreLink.textContent = L('TREE_RESTORE_LINK');
					restoreLink.addEventListener('click', function (ev) {
						ev.preventDefault();
						delete hidden[id];
						saveHiddenSet(hidden);
						block.style.display = '';
						refreshPanel();
					});
					var label = document.createElement('span');
					label.textContent = ' (#' + id + ') ';
					li.appendChild(restoreLink);
					li.appendChild(label);
					panelList.appendChild(li);
				}
			});
			if (!found) {
				var emptyLi = document.createElement('li');
				emptyLi.className = 'ksphp-treehide-empty';
				emptyLi.textContent = L('TREE_HIDDEN_EMPTY');
				panelList.appendChild(emptyLi);
			}
		}

		function buildPanel() {
			var panel = document.createElement('div');
			panel.id = 'ksphp-treehide-panel';
			panel.className = 'ksphp-treehide-panel';

			var title = document.createElement('strong');
			title.textContent = L('TREE_HIDDEN_PANEL_TITLE');
			panel.appendChild(title);
			panel.appendChild(document.createTextNode(' '));

			var restoreAll = document.createElement('a');
			restoreAll.href = '#';
			restoreAll.textContent = '[' + L('TREE_RESTORE_ALL_LINK') + ']';
			restoreAll.addEventListener('click', function (ev) {
				ev.preventDefault();
				hidden = {};
				saveHiddenSet(hidden);
				blocks.forEach(function (block) { block.style.display = ''; });
				refreshPanel();
			});
			panel.appendChild(restoreAll);

			var ul = document.createElement('ul');
			panel.appendChild(ul);
			panelList = ul;

			return panel;
		}

		function applyTreeHide() {
			var firstBlock = blocks[0];
			var panel = buildPanel();
			firstBlock.parentNode.insertBefore(panel, firstBlock);

			blocks.forEach(function (block) {
				var id = extractThreadId(block);
				if (!id) {
					return;
				}
				block.dataset.ksphpTreeId = id;

				if (hidden[id]) {
					block.style.display = 'none';
				}

				var hideLink = document.createElement('a');
				hideLink.href = '#';
				hideLink.className = 'ksphp-treehide-link';
				hideLink.textContent = L('TREE_HIDE_LINK');
				hideLink.addEventListener('click', function (ev) {
					ev.preventDefault();
					hidden[id] = true;
					saveHiddenSet(hidden);
					block.style.display = 'none';
					refreshPanel();
				});
				// ツリー先頭のリンクの直後に挿入する。
				var firstLink = block.querySelector('a');
				if (firstLink && firstLink.nextSibling) {
					firstLink.parentNode.insertBefore(document.createTextNode(' '), firstLink.nextSibling);
					firstLink.parentNode.insertBefore(hideLink, firstLink.nextSibling.nextSibling);
				} else if (firstLink) {
					block.insertBefore(hideLink, firstLink.nextSibling);
				}
			});

			refreshPanel();
		}

		// 2026-08-01 Gikoneko: 有効/無効は「個人環境設定」パネルのJS設定
		// セクションで切り替える（window.KSPHP_SETTINGS.treehide経由）。
		// ここでは値を見て、有効な場合のみツリー削除機能一式（消リンク＋
		// 復元パネル）を組み立てるだけにする。
		if (isEnabled()) {
			applyTreeHide();
		}
	});
})();
