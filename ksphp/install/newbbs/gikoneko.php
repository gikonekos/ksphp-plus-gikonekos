<?php
ini_set('display_errors', "On");

// 全てのエラーを表示
error_reporting(E_ALL);

// 擬古猫といっしょ

###############################################################################
#  メッセージ処理（点取り占いのぱくり）
###############################################################################

function giko_fortune(): string {

	$giko_dir = getenv( 'GIKO_DATA_DIR' ) ?: __DIR__;
	$giko_file = $giko_dir . '/gikoneko_kotoba.dat';

	// データファイルが存在しない場合は空ファイルを自動生成する。
	// file_exists()での事前チェックにより、file()の生のPHP警告が
	// ページ出力に混入するのを防ぐ（ここで処理を止まらせない）。
	if ( ! file_exists( $giko_file ) ) {
		@file_put_contents( $giko_file, '', LOCK_EX );
	}

	$fortunedata = @file( $giko_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

	if ( $fortunedata === false || count( $fortunedata ) === 0 ) {
		return '';
	}

	return $fortunedata[ random_int( 0, count( $fortunedata ) - 1 ) ];
}

###############################################################################
#  メイン処理
###############################################################################

function giko_display(): void {

	$mark = array( 'あ', 'い', 'う', 'え', 'お', 'か', 'き', 'く', 'け', 'こ', 'さ', 'し', 'す', 'せ', 'そ', 'た', 'ち', 'つ', 'て', 'と', 'な', 'に', 'ぬ', 'ね', 'の' );

	$points = random_int( 0, count( $mark ) - 1 );
		echo "<BLOCKQUOTE><PRE>

" . T('GIKO_TOGETHER') . "　[<A HREF=\"./gikonekoadd.php\" target=\"link\">" . T('GIKO_TEACH_LINK_TEXT') . "</A>]

";

	if ( $points < 1 ) {
		echo "
【" . T('GIKO_FORTUNE_SHOKICHI') . "】
　　　 ∧ ∧
～′￣(´Д`)＜" . giko_fortune() . "
  UU￣ U  U
";
	} elseif ( $points < 2 ) {
		echo "
【" . T('GIKO_FORTUNE_CHUKICHI') . "】
　　　 ∧ ∧
～′￣(`Д´)＜ﾊｯﾊｰﾝ!  " . giko_fortune() . "
  UU￣ U  U
";
	} elseif ( $points < 3 ) {
		echo "
【" . T('GIKO_FORTUNE_KYOU') . "】
　　　 ∧ ∧
～′￣(;´Д`)＜" . giko_fortune() . "
  UU￣ U  U
";
	} elseif ( $points < 4 ) {
		echo "
【" . T('GIKO_FORTUNE_DAIKICHI') . "】
          ヽ(`ー´)ノ＜" . giko_fortune() . "
       ∧ ∧｜_ ｜
      (`ー´)  < ) ～
        U  U ￣￣UU 
";
	} elseif ( $points < 5 ) {
		echo "
【" . T('GIKO_FORTUNE_YOUKICHI') . "】
   ∧  ∧    
   ﾉ  ﾊ  ＼  
  ﾉ ∂.∂)＜" . giko_fortune() . "
    (∩∩
γ～/___|
     U U
";
	} elseif ( $points < 6 ) {
		echo "
【" . T('GIKO_FORTUNE_GOUKICHI') . "】
                          *  . .  * (ﾟДﾟ)＜" . giko_fortune() . "
＼猫ビィィィィム！／    *  .     .    *  (ﾟДﾟ)＜" . giko_fortune() . "
       ∧ ∧       ＿＿＿＿＿※   .              *  
～′￣(     )￣￣￣        .     .  *
  UU￣ U  U                  . .  *              (ﾟДﾟ)＜" . giko_fortune() . "
";
	} elseif ( $points < 7 ) {
		echo "
【" . T('GIKO_FORTUNE_NEKOKICHI') . "】
       ∧ ∧
       ■●■
      (´ー`)＜" . giko_fortune() . "
      (｜ o｜)
      U｜ o｜U
      Ｕ  Ｕ
";
	} elseif ( $points < 8 ) {
		echo "
【" . T('GIKO_FORTUNE_DOKICHI') . "】
  |
  |
  |    ∧ ∧
  ′￣(`Д´)＜" . giko_fortune() . "
  |  ＿＿  |
  |||    |||
  UU     U U
";
	} elseif ( $points < 9 ) {
		echo "
【" . T('GIKO_FORTUNE_AIKICHI') . "】
       ＿∧ ∧
     ／（´ー`)＜" . giko_fortune() . "
   ／  ／U  U∧ ∧
ノ’（  ￣￣(´ー`)＜" . giko_fortune() . "
  UU  UU￣￣ U  U
";
	} elseif ( $points < 10 ) {
		echo "
【" . T('GIKO_FORTUNE_INKICHI') . "】
       ∧ ∧
    ／(´ー`)＜" . giko_fortune() . "
乙／  ) ⊃ ⊃
  ＼⊃＼⊃  ））））））））
";
	} elseif ( $points < 11 ) {
		echo "
【" . T('GIKO_FORTUNE_KICHI') . "】
   ∧ ∧
／(´ー`)＜" . giko_fortune() . "
￣￣￣￣￣|
";
	} elseif ( $points < 12 ) {
		echo "
【" . T('GIKO_FORTUNE_RAKUKICHI') . "】
       ∧ ∧
    ヽ(´ー`)ノ＜" . giko_fortune() . "
      ｜   ｜
      ﾉ  _ ﾉ
ε≡Ξ∪ ∪
";
	} elseif ( $points < 13 ) {
		echo "
【" . T('GIKO_FORTUNE_KYOU') . "】
　 ∧∧
　/⌒ヽ)＜" . giko_fortune() . "
～(_＿)
";
	} elseif ( $points < 14 ) {
		echo "
【" . T('GIKO_FORTUNE_KICHI') . "】
(" . giko_fortune() . ")
　　 。
　　。
 ∧ ∧⌒ヽ
(´ー`)(　)～
￣￣￣￣￣￣|
";
	} else {
		echo "
【" . T('GIKO_FORTUNE_KICHI') . "】
　　　 ∧ ∧
～′￣(´ー`)＜" . giko_fortune() . "
  UU￣ U  U
";
	}
echo "</PRE></BLOCKQUOTE>\n";
}
