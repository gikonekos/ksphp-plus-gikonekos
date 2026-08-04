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
		// 2026-08-02 Gikoneko: 管理者ロック（conf.phpのJS_DEFAULT_*=0、
		// window.KSPHP_SETTINGS_LOCKED経由）はlegacy localStorageより
		// 優先する。これがないとRC10-12からの残存値でロックを
		// 貫通できてしまう。
		var locked = window.KSPHP_SETTINGS_LOCKED;
		if (Array.isArray(locked) && locked.indexOf('latex') !== -1) {
			return false;
		}
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

	// 20260801 Gikoneko: $...$ / $$...$$ のうち、区切り記号の間に改行を
	// 含むペアはLaTeX数式として扱わない（プレーンテキストのまま表示）。
	// renderMathInElement()自体には「改行を含む区切りを除外する」機能が
	// ないため、レンダリング前に該当ペアの$記号だけを一時的な無害な
	// マーカー文字列に置換しておき、KaTeXの処理が終わった後に元の$へ
	// 戻す方式で対応する。
	//
	// $の個数が奇数（閉じ忘れ）の投稿など、ペアリングが一意に定まらない
	// ケースがあるため、方針を「改行を含まない完結ペア $...$ だけを本物
	// として残し、それ以外（改行をまたぐもの・孤立した$）は全てエスケープ
	// する」に統一した。$$...$$ ブロックは改行を含んでいても許可する
	// （複数行のdisplay数式はLaTeXとして自然な書き方であるため）。
	var ESCAPE_MARKER = '\uE000KSPHP_DOLLAR\uE000'; // Private Use Area文字を混ぜて衝突を避ける
	var BLOCK_MARKER_PREFIX = '\uE001KSPHP_BLOCK';
	var BLOCK_MARKER_SUFFIX = '\uE001';

	function escapeMultilineDollarPairs(html) {
		// 1) $$...$$ ブロックを退避（中身の改行は許可、単一$走査に巻き
		//    込まれないようプレースホルダに置換しておく）。
		var savedBlocks = [];
		var out = html.replace(/\$\$([\s\S]*?)\$\$/g, function (match) {
			var idx = savedBlocks.length;
			savedBlocks.push(match);
			return BLOCK_MARKER_PREFIX + idx + BLOCK_MARKER_SUFFIX;
		});

		// 2) 残りのテキストから、改行を含まない完結ペア $...$ だけを
		//    そのまま残し、それ以外の$（改行をまたぐ・孤立）はマーカー化。
		out = out.replace(/\$[^$\n]*\$|\$/g, function (match) {
			if (match.length >= 2 && match.charAt(0) === '$' && match.charAt(match.length - 1) === '$') {
				return match;
			}
			return ESCAPE_MARKER;
		});

		// 3) $$...$$ ブロックを復元
		out = out.replace(new RegExp(BLOCK_MARKER_PREFIX + '(\\d+)' + BLOCK_MARKER_SUFFIX, 'g'), function (m, idx) {
			return savedBlocks[Number(idx)];
		});

		return out;
	}

	function restoreEscapedDollars(el) {
		// テキストノードのみを対象に、マーカーを$へ戻す。KaTeXが生成した
		// 数式表示用DOM（.katex等）の内部には触れないよう、テキスト
		// ノード単位で置換する。
		var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
		var node;
		while ((node = walker.nextNode())) {
			if (node.nodeValue.indexOf(ESCAPE_MARKER) !== -1) {
				node.nodeValue = node.nodeValue.split(ESCAPE_MARKER).join('$');
			}
		}
	}

	// 投稿本文(pre.msgnormal)を対象に、$...$ / $$...$$ をKaTeXで描画する。
	function renderAll() {
		var targets = document.querySelectorAll('pre.msgnormal');
		if (!targets.length || typeof window.renderMathInElement !== 'function') {
			return;
		}
		targets.forEach(function (el) {
			el.innerHTML = escapeMultilineDollarPairs(el.innerHTML);
			window.renderMathInElement(el, {
				delimiters: [
					{ left: '$$', right: '$$', display: true },
					{ left: '$', right: '$', display: false }
				],
				throwOnError: false
			});
			restoreEscapedDollars(el);
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
