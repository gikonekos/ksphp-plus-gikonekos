function insertThisInThere(thisChar, thereId) {
	function theCursorPosition(ofThisInput) {
		// set a fallback cursor location
		var theCursorLocation = 0;
		// find the cursor location via IE method...
		if (document.selection) {
			ofThisInput.focus();
			var theSelectionRange = document.selection.createRange();
			theSelectionRange.moveStart('character', -ofThisInput.value.length);
			theCursorLocation = theSelectionRange.text.length;
		} else if (ofThisInput.selectionStart || ofThisInput.selectionStart == '0') {
			// or the FF way 
			theCursorLocation = ofThisInput.selectionStart;
		}
		return theCursorLocation;
	}
	// now get ready to place our new character(s)...
	var theIdElement = document.getElementById(thereId);
	var currentPos = theCursorPosition(theIdElement);
	var origValue = theIdElement.value;
	var newValue = origValue.substr(0, currentPos) + thisChar + origValue.substr(currentPos);
	theIdElement.value = newValue;
}



 const isJP = navigator.language.startsWith('ja');
 const SHOW_MORE = isJP ? '［▼ もっと表示］' : '［▼ Show more kaomoji］';
 const SHOW_LESS = isJP ? '［▲ 表示を減らす］' : '［▲ Show fewer kaomoji］';

// 2026-08-01 Gikoneko: 「個人環境設定」パネルのJS設定セクションで
// 無効化された場合は、投稿フォーム上の顔文字パレット（クイックアクセス
// ボタン＋展開パネル、いずれもtemplate.html側で静的に出力される）を
// 非表示にする。insertThisInThere()自体は他機能から呼ばれないため、
// パレットさえ隠せば実質的に無効化になる。
document.addEventListener('DOMContentLoaded', function () {
	if (window.KSPHP_SETTINGS && window.KSPHP_SETTINGS.kaomoji === 0) {
		document.querySelectorAll('input.kaomoji, a.kaomoji, #kaomoji-alt').forEach(function (el) {
			el.style.display = 'none';
		});
	}
});

function toggleKaomojiAlt() {
	var alt = document.getElementById('kaomoji-alt');
	var btn = document.getElementById('kaomojiAltToggle');
	if (!alt) return;

	// just check the hidden attribute
	if (alt.hidden) {
		// show
		alt.hidden = false;
		if (btn) btn.textContent = SHOW_LESS;
	} else {
		// hide
		alt.hidden = true;
		if (btn) btn.textContent = SHOW_MORE;
	}
}
