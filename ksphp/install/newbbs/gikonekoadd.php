<?php

// CGIを設置するホストアドレス
$bbshost = 'qptns.com';

// このスクリプトの名前
$mycginame = 'gikonekoadd.php';

// データファイル名
$giko_dir = getenv( 'GIKO_DATA_DIR' ) ?: __DIR__;
$data = $giko_dir . '/gikoneko_kotoba.dat';

// ことばのmax値
$maxword = 128; # 128なら全角で64文字

// UI文言（$MSG）の読み込み。bbs.phpと同じlanguage/{LANGUAGE_FILE}.txtを
// 参照するが、本スクリプトは単体で呼び出されるためbbs.php本体は
// requireせず、conf.phpのみ読み込んで軽量にロードする。
require_once( $giko_dir . '/conf.php' );
$language_file_name = $CONF['LANGUAGE_FILE'] ?? 'english';
$langfile = $giko_dir . '/language/' . $language_file_name . '.txt';
$MSG = array();
if ( file_exists( $langfile ) ) {
	$lines = @file( $langfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	if ( $lines !== false ) {
		foreach ( $lines as $line ) {
			$trimmed = ltrim( $line );
			if ( $trimmed === '' || $trimmed[0] === '#' || $trimmed[0] === ';' ) {
				continue;
			}
			$pos = strpos( $line, '=' );
			if ( $pos === false ) {
				continue;
			}
			$MSG[ trim( substr( $line, 0, $pos ) ) ] = substr( $line, $pos + 1 );
		}
	}
}
function T( string $key ): string {
	return $GLOBALS['MSG'][ $key ] ?? $key;
}

$title = T( 'GIKO_ADD_TITLE' );

function giko_prterror( string $msg, string $title ): never {
	echo "<html><head><title>{$title}</title><META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head>\n";
	echo "<body bgcolor=\"004040\" text=\"ffffff\">\n";
	echo "<h3>{$msg}</h3>\n";
	echo "</body></html>\n";
	exit;
}

header( 'Content-type: text/html; charset=UTF-8' );

$http_host = $_SERVER['HTTP_HOST'] ?? '';
if ( $http_host !== '' && stripos( $http_host, $bbshost ) === false ) {
	giko_prterror( T( 'GIKO_ERR_BAD_HOST' ), $title );
}

$mode = trim( (string) ( $_POST['mode'] ?? $_GET['mode'] ?? '' ) );
$text = trim( (string) ( $_POST['text'] ?? $_GET['text'] ?? '' ) );
$text = str_replace( array( "\n", "\r" ), '', $text );
$text = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );

if ( $mode === 'add' && $text !== '' ) {

	$fortunedata = file_exists( $data ) ? file( $data, FILE_IGNORE_NEW_LINES ) : array();

	if ( in_array( $text, $fortunedata, true ) ) {
		giko_prterror( T( 'GIKO_ERR_DUPLICATE' ), $title );
	}

	echo "<html><head><title>{$title}</title><META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head>\n";
	echo "<BODY bgcolor=\"#004040\" text=\"#ffffff\" link=\"#eeffee\" vlink=\"#dddddd\" alink=\"#ff0000\">\n";
	echo "<h1><a href=\"{$mycginame}\">" . T( 'GIKO_POST_COMPLETE' ) . "</a><p></h1><a href=\"./bbs.php\">" . T( 'GIKO_BACK_TO_BBS' ) . "</a>\n";
	echo "</body></html>\n";

	file_put_contents( $data, $text . "\n", FILE_APPEND | LOCK_EX );

} else {

	echo "<html><head><title>{$title}</title><META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head>\n";
	echo "<BODY bgcolor=\"#004040\" text=\"#ffffff\" link=\"#eeffee\" vlink=\"#dddddd\" alink=\"#ff0000\"><center>\n";
	echo "<p><font size=\"+2\"><B>{$title}</B></font><br>\n";
	echo "<P>" . sprintf( T( 'GIKO_ADD_PROMPT' ), $maxword ) . "</p>\n";
	echo "<form method=\"post\" action=\"{$mycginame}\">\n";
	echo "<input type=\"hidden\" name=\"mode\" value=\"add\">\n";
	echo "<input type=\"text\" name=\"text\" size=\"30\" maxlength=\"{$maxword}\">\n";
	echo "<input type=\"submit\" value=\"" . T( 'GIKO_ADD_SUBMIT' ) . "\" accesskey=\"R\">\n";
	echo " <INPUT type=\"reset\" value=\"" . T( 'GIKO_ADD_RESET' ) . "\">\n";
	echo "</form><p><a href=\"./bbs.php\" >" . T( 'GIKO_BACK_TO_BBS' ) . "</a></center></body></html>\n";

}
