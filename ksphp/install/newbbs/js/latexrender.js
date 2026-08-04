/*
 * latexrender.js
 * 20260801 Gikoneko: LaTeX数式レンダリング（個人設定・ブラウザローカル）。
 *
 * $...$ / $$...$$ 記法で書かれた投稿本文を、KaTeXで数式として描画する。
 * ksphp-plus本体は基本的にスタンドアローン設計（外部CDN非依存）だが、
 * LaTeXの完全な自前実装は現実的でないため、この機能に限り有効化時
 * だけCDN（jsDelivr）からKaTeXを読み込む例外とする。無効時は外部への
 * 通信は一切発生しない（既定は無効）。
 */
(function () {
	'use strict';

	var LANG = window.KSPHP_LANG || {};
	function L(key) {
		return (typeof LANG[key] === 'string') ? LANG[key] : key;
	}

	var STORAGE_ENABLED = 'ksphp_latex_enabled';
	var KATEX_VERSION = '0.16.11';
	var KATEX_CSS = 'https://cdn.jsdelivr.net/npm/katex@' + KATEX_VERSION + '/dist/katex.min.css';
	var KATEX_JS = 'https://cdn.jsdelivr.net/npm/katex@' + KATEX_VERSION + '/dist/katex.min.js';
	var KATEX_AUTORENDER_JS = 'https://cdn.jsdelivr.net/npm/katex@' + KATEX_VERSION + '/dist/contrib/auto-render.min.js';

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

	function loadScript(src) {
		return new Promise(function (resolve, reject) {
			var existing = document.querySelector('script[src="' + src + '"]');
			if (existing) {
				resolve();
				return;
			}
			var s = document.createElement('script');
			s.src = src;
			s.onload = function () { resolve(); };
			s.onerror = function () { reject(new Error('load failed: ' + src)); };
			document.head.appendChild(s);
		});
	}

	function loadCss(href) {
		if (document.querySelector('link[href="' + href + '"]')) {
			return;
		}
		var l = document.createElement('link');
		l.rel = 'stylesheet';
		l.href = href;
		document.head.appendChild(l);
	}

	var katexLoadPromise = null;
	function ensureKatexLoaded() {
		if (!katexLoadPromise) {
			loadCss(KATEX_CSS);
			katexLoadPromise = loadScript(KATEX_JS).then(function () {
				return loadScript(KATEX_AUTORENDER_JS);
			});
		}
		return katexLoadPromise;
	}

	// 投稿本文(pre.msgnormal)を対象に、$...$ / $$...$$ をKaTeXで描画する。
	function renderAll() {
		var targets = document.querySelectorAll('pre.msgnormal');
		if (!targets.length || typeof window.renderMathInElement !== 'function') {
			return;
		}
		targets.forEach(function (el) {
			window.renderMathInElement(el, {
				delimiters: [
					{ left: '$$', right: '$$', display: true },
					{ left: '$', right: '$', display: false }
				],
				throwOnError: false
			});
		});
	}

	function applyToggleState(checkbox) {
		if (checkbox.checked) {
			ensureKatexLoaded().then(renderAll).catch(function () {
				// CDNへ到達できない場合は静かに諦める（本文はプレーンテキストのまま）。
			});
		}
		// 無効化時：既に描画済みの数式をプレーンテキストへ戻すのは複雑なため、
		// ページ再読み込みで元に戻す方針とする（他の2機能と異なり、KaTeXは
		// DOM構造そのものを書き換えるため、その場での巻き戻しは行わない）。
	}

	document.addEventListener('DOMContentLoaded', function () {
		var posts = document.querySelectorAll('div.m');
		if (!posts.length) {
			return; // 通常表示画面以外では何もしない。
		}

		var toggleWrap = document.createElement('div');
		toggleWrap.id = 'ksphp-latex-toggle';
		toggleWrap.className = 'ksphp-latex-toggle';
		toggleWrap.style.fontSize = '0.85em';
		toggleWrap.style.margin = '0.3em 0';

		var label = document.createElement('label');
		var checkbox = document.createElement('input');
		checkbox.type = 'checkbox';
		checkbox.checked = isEnabled();
		label.appendChild(checkbox);
		label.appendChild(document.createTextNode(' ' + L('LATEX_SETTING_LABEL')));
		toggleWrap.appendChild(label);

		checkbox.addEventListener('change', function () {
			setEnabled(checkbox.checked);
			applyToggleState(checkbox);
		});

		posts[0].parentNode.insertBefore(toggleWrap, posts[0]);

		if (checkbox.checked) {
			applyToggleState(checkbox);
		}
	});
})();
