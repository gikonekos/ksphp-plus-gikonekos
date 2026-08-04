/*
 * longpostfilter.js
 * 20260801 Gikoneko: 長文NGフィルター（個人設定・ブラウザローカル）。
 *
 * 通常表示画面（div.m > pre.msgnormal）の各投稿について、本文の
 * 行数がしきい値を超えていたら折りたたんで表示する。サーバー側には
 * 一切影響しない、完全にクライアント側の個人設定機能。
 * 折りたたまれた投稿は「表示」リンクでいつでも展開できる。
 *
 * 有効/無効・行数しきい値は個人設定で切り替え可能。既定は無効
 * （オプトイン）、しきい値の既定値は30行。
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

	var STORAGE_ENABLED = 'ksphp_longpost_enabled';
	var STORAGE_THRESHOLD = 'ksphp_longpost_threshold';
	var DEFAULT_THRESHOLD = 30;

	function isEnabled() {
		try {
			return window.localStorage.getItem(STORAGE_ENABLED) === '1';
		} catch (e) {
			return false;
		}
	}

	function setEnabled(v) {
		try {
			window.localStorage.setItem(STORAGE_ENABLED, v ? '1' : '0');
		} catch (e) { /* ignore */ }
	}

	function getThreshold() {
		try {
			var v = parseInt(window.localStorage.getItem(STORAGE_THRESHOLD), 10);
			return (Number.isFinite(v) && v > 0) ? v : DEFAULT_THRESHOLD;
		} catch (e) {
			return DEFAULT_THRESHOLD;
		}
	}

	function setThreshold(v) {
		try {
			window.localStorage.setItem(STORAGE_THRESHOLD, String(v));
		} catch (e) { /* ignore */ }
	}

	function injectStyle() {
		if (document.getElementById('ksphp-longpost-style')) {
			return;
		}
		var style = document.createElement('style');
		style.id = 'ksphp-longpost-style';
		style.textContent =
			'.ksphp-longpost-toggle{font-size:0.85em;margin:0.3em 0;}' +
			'.ksphp-longpost-toggle input[type=number]{width:4em;}' +
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
			function expand() {
				pre.style.display = '';
				collapseLink.style.display = '';
				placeholder.style.display = 'none';
			}

			expandLink.addEventListener('click', function (ev) {
				ev.preventDefault();
				expand();
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
					entry.expand();
				}
			});
		}

		// 個人設定バー（有効/無効・しきい値）を先頭投稿の前に挿入する。
		var toggleWrap = document.createElement('div');
		toggleWrap.id = 'ksphp-longpost-toggle';
		toggleWrap.className = 'ksphp-longpost-toggle';

		var label = document.createElement('label');
		var checkbox = document.createElement('input');
		checkbox.type = 'checkbox';
		checkbox.checked = isEnabled();
		label.appendChild(checkbox);
		label.appendChild(document.createTextNode(' ' + L('LONGPOST_SETTING_LABEL')));
		toggleWrap.appendChild(label);

		toggleWrap.appendChild(document.createTextNode(' '));

		var thLabel = document.createElement('label');
		thLabel.appendChild(document.createTextNode(L('LONGPOST_THRESHOLD_LABEL') + ' '));
		var thInput = document.createElement('input');
		thInput.type = 'number';
		thInput.min = '1';
		thInput.value = String(getThreshold());
		thLabel.appendChild(thInput);
		toggleWrap.appendChild(thLabel);

		checkbox.addEventListener('change', function () {
			setEnabled(checkbox.checked);
			applyFilter();
		});
		thInput.addEventListener('change', function () {
			var v = parseInt(thInput.value, 10);
			if (Number.isFinite(v) && v > 0) {
				setThreshold(v);
				applyFilter();
			}
		});

		posts[0].parentNode.insertBefore(toggleWrap, posts[0]);

		applyFilter();
	});
})();
