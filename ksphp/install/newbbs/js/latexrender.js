/*
 * latexrender.js
 * 20260801 Gikoneko: LaTeX数式レンダリング（個人設定・ブラウザローカル→
 * 個人環境設定パネルのJS設定セクションへ統合）。
 *
 * $...$ / $$...$$ 記法で書かれた投稿本文を、KaTeXで数式として描画する。
 * ksphp-plus本体は基本的にスタンドアローン設計（外部CDN非依存）だが、
 * LaTeXの完全な自前実装は現実的でないため、この機能に限り有効化時
 * だけCDN（jsDelivr）からKaTeXを読み込む例外とする。無効時は外部への
 * 通信は一切発生しない（既定は無効）。
 *
 * 設定のON/OFFは「個人環境設定」画面（m=c）のJS設定セクションで行う
 * （サーバーcookie 'ksphp_js' 経由、window.KSPHP_SETTINGS.latexとして
 * 各ページに埋め込まれる）。旧バージョン（RC10〜RC12）でページ上部に
 * 独自トグルを表示していた頃のlocalStorageキー('ksphp_latex_enabled')
 * が残っている場合は、初回のみそちらを優先する（後方互換の一度きりの
 * 移行。以後はKSPHP_SETTINGS側が正）。
 */
(function () {
	'use strict';

	var LEGACY_STORAGE_KEY = 'ksphp_latex_enabled';
	var KATEX_VERSION = '0.16.11';
	var KATEX_CSS = 'https://cdn.jsdelivr.net/npm/katex@' + KATEX_VERSION + '/dist/katex.min.css';
	var KATEX_JS = 'https://cdn.jsdelivr.net/npm/katex@' + KATEX_VERSION + '/dist/katex.min.js';
	var KATEX_AUTORENDER_JS = 'https://cdn.jsdelivr.net/npm/katex@' + KATEX_VERSION + '/dist/contrib/auto-render.min.js';

	function isEnabled() {
		try {
			var legacy = window.localStorage.getItem(LEGACY_STORAGE_KEY);
			if (legacy !== null) {
				return legacy === '1';
			}
		} catch (e) { /* localStorage無効環境では無視 */ }
		var s = window.KSPHP_SETTINGS || {};
		return s.latex === 1;
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

	document.addEventListener('DOMContentLoaded', function () {
		var posts = document.querySelectorAll('div.m');
		if (!posts.length || !isEnabled()) {
			return; // 通常表示画面以外、または無効設定時は何もしない。
		}
		ensureKatexLoaded().then(renderAll).catch(function () {
			// CDNへ到達できない場合は静かに諦める（本文はプレーンテキストのまま）。
		});
	});
})();
