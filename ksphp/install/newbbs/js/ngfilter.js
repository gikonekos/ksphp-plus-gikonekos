// js/ngfilter.js — NG-hash censor (client-side)
// 2026-08-15 Gikoneko
//
// hashes.txt.gz（SHA-256ハッシュ辞書）を fetch して投稿フォームの送信前に
// 部分一致チェックを行い、NGワードを全角→＊、半角→* に伏字変換する。
// - JS は「確認ダイアログ＋伏字変換」の役割。
// - PHP側（bbs.php: ksphp_nghash_censor）が確実なフォールバック。
// - DecompressionStream / crypto.subtle.digest / TextEncoder が必要。
//   対応ブラウザ: Chrome 80+, Firefox 113+, Safari 16.4+
//   非対応環境では静かに無効化してフォームの動作を妨げない。
//
// 設定値は window.KSPHP_NGHASH_CONFIG で外部から注入する:
//   window.KSPHP_NGHASH_CONFIG = {
//     hashesUrl : './filter/hashes.txt.gz',  // gzip辞書のURL
//     minChars  : 4,                          // 最小照合文字数
//     confirmMsg: '...',                      // confirm()に表示するメッセージ
//   };

(function () {
    'use strict';

    // ---- 機能確認（非対応環境では黙って終了）----
    if (
        typeof DecompressionStream === 'undefined' ||
        typeof crypto === 'undefined' ||
        typeof crypto.subtle === 'undefined' ||
        typeof TextEncoder === 'undefined'
    ) { return; }

    var cfg = window.KSPHP_NGHASH_CONFIG || {};
    var HASHES_URL  = cfg.hashesUrl  || './filter/hashes.txt.gz';
    var MIN_CHARS   = parseInt(cfg.minChars, 10) || 4;
    var CONFIRM_MSG = cfg.confirmMsg || 'NGワードが含まれています。伏字に変換して投稿しますか？';

    // ---- 辞書ロード（Promise、1回だけfetch）----
    var hashSetPromise = null;
    function loadHashes() {
        if (hashSetPromise) return hashSetPromise;
        hashSetPromise = (async function () {
            try {
                var res = await fetch(HASHES_URL);
                if (!res.ok) throw new Error('HTTP ' + res.status);
                var ds   = new DecompressionStream('gzip');
                var text = await new Response(res.body.pipeThrough(ds)).text();
                return new Set(
                    text.split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean)
                );
            } catch (e) {
                console.warn('[ngfilter] 辞書の読み込みに失敗しました:', e);
                return new Set(); // 失敗時はフィルタなしで投稿を通す
            }
        }());
        return hashSetPromise;
    }

    // ---- SHA-256ハッシュ（hex）----
    async function sha256hex(str) {
        var buf = await crypto.subtle.digest(
            'SHA-256',
            new TextEncoder().encode(str)
        );
        return Array.from(new Uint8Array(buf))
            .map(function (b) { return b.toString(16).padStart(2, '0'); })
            .join('');
    }

    // ---- 全角英数字を半角に変換（照合前正規化）----
    // Ａ–Ｚ (U+FF21–FF3A) → A–Z、ａ–ｚ (U+FF41–FF5A) → a–z、
    // ０–９ (U+FF10–FF19) → 0–9
    function fullwidthToAscii(str) {
        return str.replace(/[\uFF10-\uFF19\uFF21-\uFF3A\uFF41-\uFF5A]/g, function (c) {
            var cp = c.codePointAt(0);
            if (cp >= 0xFF21 && cp <= 0xFF3A) return String.fromCharCode(cp - 0xFF21 + 0x41); // A–Z
            if (cp >= 0xFF41 && cp <= 0xFF5A) return String.fromCharCode(cp - 0xFF41 + 0x61); // a–z
            if (cp >= 0xFF10 && cp <= 0xFF19) return String.fromCharCode(cp - 0xFF10 + 0x30); // 0–9
            return c;
        });
    }

    // ---- Unicodeコードポイント配列に分解 ----
    // (surrogate pairを1文字として扱う)
    function splitCodepoints(str) {
        // Array.from はサロゲートペアを正しく扱う
        return Array.from(str);
    }

    // ---- 全角判定（UTF-16でU+00FFより大きいものを全角扱い）----
    // 日本語・韓国語・中国語等の文字は全角、ASCII範囲は半角。
    function isFullwidth(cp) {
        return cp.codePointAt(0) > 0x00FF;
    }

    // ---- 伏字変換のコアロジック ----
    async function censorText(text, hashes) {
        if (!text) return { result: text, found: false };
        var cps      = splitCodepoints(text);
        var cpsLower = splitCodepoints(fullwidthToAscii(text).toLowerCase());
        var n        = cps.length;
        if (n < MIN_CHARS) return { result: text, found: false };

        var masked = new Array(n).fill(false);
        var found  = false;

        // 長いウィンドウから優先（より長い語を先に確定）
        for (var win = n; win >= MIN_CHARS; win--) {
            for (var start = 0; start + win <= n; start++) {
                // マスク済み区間はスキップ
                var skip = false;
                for (var k = start; k < start + win; k++) {
                    if (masked[k]) { skip = true; break; }
                }
                if (skip) continue;

                var substr = cpsLower.slice(start, start + win).join('');
                var hash   = await sha256hex(substr);
                if (hashes.has(hash)) {
                    for (var k2 = start; k2 < start + win; k2++) {
                        masked[k2] = true;
                    }
                    found = true;
                }
            }
        }

        if (!found) return { result: text, found: false };

        var out = '';
        for (var i = 0; i < n; i++) {
            out += masked[i]
                ? (isFullwidth(cps[i]) ? '＊' : '*')
                : cps[i];
        }
        return { result: out, found: true };
    }

    // ---- フォーム送信インターセプト ----
    async function handleSubmit(e) {
        // 「post」ボタンの送信のみ対象（reload / readnew 等は除外）
        if (e.submitter && e.submitter.name !== 'post') return;

        var form     = e.currentTarget;
        var nameEl   = form.querySelector('input[name="u"]');
        var titleEl  = form.querySelector('input[name="t"]');
        var bodyEl   = form.querySelector('textarea[name="v"]');
        if (!nameEl && !titleEl && !bodyEl) return;

        e.preventDefault();

        var hashes = await loadHashes();

        var nameVal  = nameEl  ? nameEl.value  : '';
        var titleVal = titleEl ? titleEl.value : '';
        var bodyVal  = bodyEl  ? bodyEl.value  : '';

        var nameR  = await censorText(nameVal,  hashes);
        var titleR = await censorText(titleVal, hashes);
        var bodyR  = await censorText(bodyVal,  hashes);

        if (nameR.found || titleR.found || bodyR.found) {
            // 確認ダイアログ
            if (!window.confirm(CONFIRM_MSG)) {
                // キャンセル→フォームに戻す（変換しない）
                return;
            }
            // OK→伏字変換後の値をフォームに書き戻す
            if (nameEl  && nameR.found)  nameEl.value  = nameR.result;
            if (titleEl && titleR.found) titleEl.value = titleR.result;
            if (bodyEl  && bodyR.found)  bodyEl.value  = bodyR.result;
        }

        // 送信続行
        form.submit();
    }

    // ---- 初期化：投稿フォームに送信イベントを登録 ----
    function init() {
        // 投稿フォームを特定（enctype=multipart/form-data のフォーム）
        var forms = document.querySelectorAll('form[enctype="multipart/form-data"]');
        forms.forEach(function (form) {
            form.addEventListener('submit', handleSubmit);
        });

        // 辞書を事前ロード開始（送信時の遅延を減らす）
        loadHashes();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
