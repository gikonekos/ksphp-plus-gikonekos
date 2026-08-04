<?php

// CGIを設置するホストアドレス
$bbshost = 'qptns.com';

// このスクリプトの名前
$mycginame = 'gikonekoadd.php';

// タイトル
$title = '擬古猫といっしょ 投稿画面';

// データファイル名
$giko_dir = getenv( 'GIKO_DATA_DIR' ) ?: __DIR__;
$data = $giko_dir . '/../cgi-bin/gikoneko_kotoba.dat';

// ことばのmax値
$maxword = 128; # 128なら全角で64文字

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
	giko_prterror( '呼び出し元が不正です。', $title );
}

$mode = trim( (string) ( $_POST['mode'] ?? $_GET['mode'] ?? '' ) );
$text = trim( (string) ( $_POST['text'] ?? $_GET['text'] ?? '' ) );
$text = str_replace( array( "\n", "\r" ), '', $text );
$text = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );

if ( $mode === 'add' && $text !== '' ) {

	$fortunedata = file_exists( $data ) ? file( $data, FILE_IGNORE_NEW_LINES ) : array();

	if ( in_array( $text, $fortunedata, true ) ) {
		giko_prterror( 'すでに登録されています。', $title );
	}

	echo "<html><head><title>{$title}</title><META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head>\n";
	echo "<BODY bgcolor=\"#004040\" text=\"#ffffff\" link=\"#eeffee\" vlink=\"#dddddd\" alink=\"#ff0000\">\n";
	echo "<h1><a href=\"{$mycginame}\">書き込み完了</a><p></h1><a href=\"./bbs.php\">掲示板に戻る</a>\n";
	echo "</body></html>\n";

	file_put_contents( $data, $text . "\n", FILE_APPEND | LOCK_EX );

} else {

	echo "<html><head><title>{$title}</title><META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head>\n";
	echo "<BODY bgcolor=\"#004040\" text=\"#ffffff\" link=\"#eeffee\" vlink=\"#dddddd\" alink=\"#ff0000\"><center>\n";
	echo "<p><font size=\"+2\"><B>{$title}</B></font><br>\n";
	echo "<P>擬古猫に喋らせたい言葉を書いてください（半角{$maxword}文字まで）</p>\n";
	echo "<form method=\"post\" action=\"{$mycginame}\">\n";
	echo "<input type=\"hidden\" name=\"mode\" value=\"add\">\n";
	echo "<input type=\"text\" name=\"text\" size=\"30\" maxlength=\"{$maxword}\">\n";
	echo "<input type=\"submit\" value=\"ことばを教える\" accesskey=\"R\">\n";
	echo " <INPUT type=\"reset\" value=\"消す\">\n";
	echo "</form><p><a href=\"./bbs.php\" >掲示板に戻る</a></center></body></html>\n";

}