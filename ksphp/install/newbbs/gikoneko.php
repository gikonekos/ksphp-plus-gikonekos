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
	$fortunedata = file( $giko_dir . '/gikoneko_kotoba.dat', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

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

擬古猫といっしょ　[<A HREF=\"./gikonekoadd.php\" target=\"link\">擬古猫にことばを教える</A>]

";

	if ( $points < 1 ) {
		echo "
【小吉】
　　　 ∧ ∧
～′￣(´Д`)＜" . giko_fortune() . "
  UU￣ U  U
";
	} elseif ( $points < 2 ) {
		echo "
【中吉】
　　　 ∧ ∧
～′￣(`Д´)＜ﾊｯﾊｰﾝ!  " . giko_fortune() . "
  UU￣ U  U
";
	} elseif ( $points < 3 ) {
		echo "
【凶】
　　　 ∧ ∧
～′￣(;´Д`)＜" . giko_fortune() . "
  UU￣ U  U
";
	} elseif ( $points < 4 ) {
		echo "
【大吉】
          ヽ(`ー´)ノ＜" . giko_fortune() . "
       ∧ ∧｜_ ｜
      (`ー´)  < ) ～
        U  U ￣￣UU 
";
	} elseif ( $points < 5 ) {
		echo "
【幼吉】
   ∧  ∧    
   ﾉ  ﾊ  ＼  
  ﾉ ∂.∂)＜" . giko_fortune() . "
    (∩∩
γ～/___|
     U U
";
	} elseif ( $points < 6 ) {
		echo "
【轟吉】
                          *  . .  * (ﾟДﾟ)＜" . giko_fortune() . "
＼猫ビィィィィム！／    *  .     .    *  (ﾟДﾟ)＜" . giko_fortune() . "
       ∧ ∧       ＿＿＿＿＿※   .              *  
～′￣(     )￣￣￣        .     .  *
  UU￣ U  U                  . .  *              (ﾟДﾟ)＜" . giko_fortune() . "
";
	} elseif ( $points < 7 ) {
		echo "
【猫吉】
       ∧ ∧
       ■●■
      (´ー`)＜" . giko_fortune() . "
      (｜ o｜)
      U｜ o｜U
      Ｕ  Ｕ
";
	} elseif ( $points < 8 ) {
		echo "
【怒吉】
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
【愛吉】
       ＿∧ ∧
     ／（´ー`)＜" . giko_fortune() . "
   ／  ／U  U∧ ∧
ノ’（  ￣￣(´ー`)＜" . giko_fortune() . "
  UU  UU￣￣ U  U
";
	} elseif ( $points < 10 ) {
		echo "
【引吉】
       ∧ ∧
    ／(´ー`)＜" . giko_fortune() . "
乙／  ) ⊃ ⊃
  ＼⊃＼⊃  ））））））））
";
	} elseif ( $points < 11 ) {
		echo "
【吉】
   ∧ ∧
／(´ー`)＜" . giko_fortune() . "
￣￣￣￣￣|
";
	} elseif ( $points < 12 ) {
		echo "
【楽吉】
       ∧ ∧
    ヽ(´ー`)ノ＜" . giko_fortune() . "
      ｜   ｜
      ﾉ  _ ﾉ
ε≡Ξ∪ ∪
";
	} elseif ( $points < 13 ) {
		echo "
【凶】
　 ∧∧
　/⌒ヽ)＜" . giko_fortune() . "
～(_＿)
";
	} elseif ( $points < 14 ) {
		echo "
【吉】
(" . giko_fortune() . ")
　　 。
　　。
 ∧ ∧⌒ヽ
(´ー`)(　)～
￣￣￣￣￣￣|
";
	} else {
		echo "
【吉】
　　　 ∧ ∧
～′￣(´ー`)＜" . giko_fortune() . "
  UU￣ U  U
";
	}
echo "</PRE></BLOCKQUOTE>\n";
}
