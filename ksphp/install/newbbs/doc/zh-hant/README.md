# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
KuzuhaScript（くずはすくりぷと）PHP移植版的改進版本。
截至2024/10/16，僅可在PHP8及以上版本執行。
最後一個支援舊版PHP（4.1.0～7.4）的版本可在此處獲取：[https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)


本程式基於2005/04/01修改版的KuzuhaScriptPHP（くずはすくりぷとPHP）。

本程式最初由[Strange World@Heyuri.net的Anonymous-san](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555)翻譯為英文，此後Heyuri的多位匿名開發者陸續做出了貢獻。


* [KuzuhaScriptPHP（映象）](http://qptn.x.fc2.com/up/dauso0059.zip)  
* [2005/04/01修改版](http://qptn.x.fc2.com/up/dauso0073.zip)

## 維護者資訊
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## 安裝流程（僅供參考）
1. 解壓下載的ZIP檔案
2. 開啟並配置conf.php
3. 使用FTP客戶端等工具將檔案上傳至伺服器（建議建立專用目錄，避免與其他檔案混雜）
4. 按照readme.md中的說明設定許可權
5. 開啟瀏覽器，訪問`_setup.php`（包內附帶的獨立工具，不屬於conf.php或install.php的一部分），在此設定管理員密碼。完成後，該工具會自動重新命名為您指定的名稱（系統會建議一個不易被猜到的預設名稱）——請務必記下新的名稱/URL，因為之後修改密碼時會用到。
6. （現已不再需要——管理員密碼現在由`_setup.php`直接寫入其專用檔案，而非conf.php。）
7. 開啟瀏覽器訪問bbs.php，確認是否可以發帖
8. 用瀏覽器訪問日誌檔案（bbs.log、log/內的檔案等）所在的URL，確認是否能夠看到（如果能看到，請用.htaccess等方式將其隱藏）

## 故障排除
### 升級現有站點（管理員密碼遷移）
自RC8起，管理員密碼（ADMINPOST/ADMINKEY）存放在conf.php之外，位於一個不屬於本模板、且install.php永遠不會覆蓋的固定檔名`local.php`中。當install.php檢測到您正在升級的站點在其現有conf.php中已有非空的ADMINPOST時，會顯示一個遷移表單，要求輸入**舊密碼**（用於驗證確實是您本人）以及要設定的**新密碼**。管理模式關鍵詞（ADMINKEY）會自動繼承，無需單獨填寫。

- 若舊密碼驗證成功，`local.php`會以新密碼寫入，安裝照常繼續。
- 若驗證失敗，**整個安裝過程將被中止**（不會安裝任何檔案，也不會進行任何備份或覆蓋），以防止他人劫持一個自己並不掌控的站點的管理員賬號。
- 如果您確實忘記了舊密碼，請在伺服器上開啟現有的conf.php，手動將`ADMINPOST`的值清空（設為空字串）。這會使install.php將該站點視為全新安裝，之後即可像新安裝一樣，透過`_setup.php`設定全新的密碼。

## 建議的許可權設定
許可權設定不當可能導致問題及資料洩露（如帖子釋出者的IP地址或遠端主機名），請務必確認設定正確。

```
[檔案結構]
|-- bbs.cnt   600（可寫）        參與者列表記錄檔案（空文字檔案）
|-- bbs.log   600（可寫）        日誌檔案（空文字檔案）
|-- conf.php  644（只讀）        用於配置
|-- bbs.php 644（只讀）          論壇主指令碼
|-- readme.md                     說明文件（本檔案）
|
|-- vanish.js                     詞語過濾指令碼
|
|
+-- archive/  700（可寫）        ZIP存檔儲存目錄
+-- count/    700（可寫）        計數器輸出目錄
+-- log/      700（可寫）        訊息日誌（原始日誌）儲存目錄
+-- sub/      755（只讀）        子模組儲存目錄
    |
    |-- bbsadmin.php    644（只讀）    管理模組
    |-- bbslog.php      644（只讀）    日誌檢視模組
    |-- bbstree.php     644（只讀）    樹狀檢視模組
    |-- phpzip.inc.php  644（只讀）    ZIP檔案建立庫
```

若PHP以Apache模組方式執行，bbs.php保持只讀即可，
但若以CGI方式執行，則需將bbs.php設為755（可執行）。

## 備註：
### bbs.php?m=* 引數含義一覽

| 引數 | 含義 |
| --- | --- |
| m=g | 訊息日誌搜尋 |
| m=ad | 管理員模式 |
| m=tree | 樹狀檢視 |
| m=p | 發帖／重新整理 |
| m=c | 個人設定 |
| m=f | 跟帖介面 |
| m=t | 串（Thread）顯示 |
| m=s | 按發帖者搜尋 |
| m=u | 執行UNDO |

## 沿革
### Cion（しおん）版
* 2003/01/21 開始開發
* 2003/01/31 0.0.1alpha
* 2003/02/03 0.0.2alpha
* 2003/02/11 0.0.3alpha
* 2003/02/13 0.0.4alpha
* 2003/02/14 0.0.5alpha
* 2003/02/16 0.0.6alpha
* 2003/02/18 0.0.7alpha

### 非官方
* 2005/04/01 0.0.8alpha（非官方）由志願者釋出的修改版（映象：http://www.freak.ne.jp/~lunatica/home/up/freak/dauso0073.zip）

### 日期不明（蛭ヶ嶽版）
* 修正了UI，在智慧手機等裝置上更易使用
* 遷移至UTF-8（＠Links）
* PHPZip更新至v1.2
* 其他各種修復（未記錄）

### 日期不明
* 編碼風格微調
* 修復跟帖漏洞
* 移除jcode-LE
* 修復使用者設定未生效的問題(?)
* 模板不再是問題
* 解決func類的神秘實現問題（未完成）
* 為支援PHP7.x做準備

### 2018/10/12
* 更名為"KuzuhaScriptPHP+"（くずはすくりぷとPHP+）
* 修復因檢查不完善導致表單資料缺失時未能失效的問題
* 微調UI

### 2018/11/18
* 應用了Motoi(gikonekos)的樹狀檢視修正
* 內建vanish.js

### 2019/11/02
* 移除EZweb顯示（HDML）
* 移除imode顯示

### 2019/11/02
* 移除EZweb顯示（HDML）
* 移除imode顯示

### 2020/02/11
* 應用了Motoi(gikonekos)的樹狀檢視漏洞修復

### 2020/03/15
* 計數器改為逗號分隔顯示

### 2020/03/29
* 新增Motoi(gikonekos)的YouTube嵌入功能

### 2021/03/08
* 設計調整（文字框等）
* conf.php（修改了表述與預設值）

### 2021/07/03
* 新增Motoi(gikonekos)的2ch trip功能(20210625)
* 移除bbs.cgi

### 2021/07/27
* 修復同時使用管理員密碼與trip（Motoi(gikonekos)）時密碼洩露的問題
* 姓名、郵箱、標題的最大字元數現可設定
* 將bbs.php中的說明移至readme.md

### 2022/05/06
* 將bbs.php遷移為index.php
* 微調UI

### 20221127
* sub/patTemplate.php：應用Motoi(gikonekos)的修復

### 20230118
* 修復樹狀檢視顯示不正確的漏洞

### 20230520
* 應用Motoi(gikonekos)的管理路徑洩露防範措施(20210923)
* 應用Motoi(gikonekos)的暱稱密碼洩露防範修復(20210923)

### 日期不明（Heyuri版）
* 全面英譯
* 新增表情符號（顔文字）按鈕
* 行高改為1
* 註釋掉瀏覽器換行用CSS
* 新增便於使用者換行的JavaScript
* 註釋掉Motoi(gikonekos)的YouTube嵌入功能
* 實現YouTube嵌入用JavaScript
* 新增圖片縮圖JavaScript，預設僅對Uploader@Heyuri生效

### 2024/10/16
* 遷移至PHP8
* 將index.php改回bbs.php
* JS檔案移至獨立目錄

### 2025/04/29
* 修復應用使用者設定後重定向不生效的問題
* 移除conf.php中未使用的自定義設定

### 2025/06/07
* 新增IPv6地址支援
* 修改http頭設定以允許從任意位置嵌入

### 2025/09/09
* 修復主機封禁相關漏洞

### 2025/11/08
* 為長行新增縱向滾動CSS（僅限PC）
* 將表情符號按鈕改為fieldset形式
* 修復表單非空時點選使用者設定會導致誤發帖的漏洞
* あやしいブレーカー（換行器）現會在長行發帖時透過發光提示使用者

### 2025/11/09
* 新增日語的完整國際化支援，英語現變為可選項
* 軟體名稱由KuzuhaScriptPHP+EN改回KuzuhaScriptPHP+

### 2026/03/10
* 管理員金鑰現可與由Motoi(gikonekos)實現的trip程式碼組合使用

### 2026/03/15
* 進入管理選單的方式改為頂部連結
* 管理選單重新設計，出於安全與便利性考慮改用會話（session）機制

### 2026/06/27
* 帖子刪除管理介面新增篩選與批次選擇輔助功能

### 2026/07/18
* gikoneko.php / gikonekoadd.php的介面文字現已透過標準`$MSG`機制實現多語言支援（language/*.txt新增22個鍵）
* gikoneko.php：占卜資料檔案缺失時現會自動建立，`file()`不再直接向頁面輸出PHP原生警告
* gikonekoadd.php：修復資料檔案路徑不一致（`../cgi-bin/...`）的問題，此前該問題導致新學習的詞語始終無法進入gikoneko.php的占卜詞庫；兩個指令碼現統一引用同一個根目錄下的資料檔案
* ayashiibreaker.js更新至v0.4.0：日語行的換行現基於禁則處理（禁則処理）規則按字元數計算，而非依賴以空格分隔的單詞換行

### 2026/07/19
* bbs.php：主論壇顯示（`getdispmessage()`）以及`msgsearchlist()`的當日日誌分支，現改為透過`Func::fgetline()`逐行流式讀取日誌檔案，而非透過`file()`將整個檔案讀入記憶體。普通頁面瀏覽時的峰值記憶體佔用不再與`LOGSAVE`／日誌檔案大小成比例增長（已驗證：在一份102,300行、約58MB的日誌上，峰值記憶體由約131MB降至約2MB；模擬100個併發請求的流量峰值測試中，不再像此前實現那樣觸發OOM Kill）
* bbs.php：移除長期被註釋掉的YouTube嵌入程式碼（自2024/10/16起已由ytthumb.js取代）
* bbs.php / conf.php：未讀帖數為0時是否顯示"Gikoneko-to-issho"現可透過`GIKONEKO_TOISSHO`（1=開啟，0=關閉，預設1）配置；禁用後將回退到此前簡單的"無未讀帖"提示資訊
* install.php：為附近掃描未能找到的資料夾新增文字輸入式"新增新安裝目標"流程（含路徑穿越校驗及使用前的確認步驟）
* install.php：新增日語／英語介面語言切換功能（預設日語），包括安裝日誌訊息的翻譯
* install.php：新增安全防護，拒絕以檔案系統根目錄、過淺路徑或install/資料夾本身作為安裝目標
* install.php：現有檔案的備份方式由`copy()`改為`rename()`（原子操作）；單個檔案的備份／重新命名失敗時，現僅跳過該檔案而不再中止整個執行；新檔案寫入失敗時會自動回滾已挪至一旁的原檔案，失敗情況還會額外記錄到`install/backup/install-errors-YYYY-MM-DD.txt`以便後續查閱

## 待辦事項：
* （2026-08-01已實現）install.php：遷移過程中新增conf.php"調整"步驟——由自動合併結果預填的核取方塊／單選／列表／文字欄位、必填項高亮、支援按檔案回滾的伺服器端校驗、可選退出的個人化開關（預設開啟）、7種語言完整支援。
* （2026-08-01已實現）根據qptns.com實機測試反饋進行的後續UI修復：(1) 所有原有核取方塊（布林0/1鍵）現統一渲染為2選項單選按鈕組，以與現有的3選項單選鍵保持一致，並回應"核取方塊令人困惑"的反饋；(2) ZIPDIR、OLDLOGFILEDIR、CNTFILENAME在確認介面中不再標記為必填——conf.php自身的註釋已說明，對這三項而言留空是有效的"功能禁用"狀態（已透過查閱各手動路徑鍵的實際註釋文字驗證；其他手動路徑鍵——LOGFILENAME、COUNTFILE、GIKONEKO_KOTOBA_FILE、UPLOADDIR、UPLOADIDFILE——沒有此類註釋，仍保持必填）。
* （2026-08-01根本修復）`ksphp_conf_parse_entries()`條目邊界解析器漏洞（原始報告詳見"已知漏洞"）：`ksphp_conf_merge()`、`ksphp_conf_build_review()`、`ksphp_conf_apply_review()`、`ksphp_parse_module_array()`中的鍵提取步驟，現透過新增的`ksphp_conf_entry_split_lead_comments()`輔助函式，在執行鍵名正則匹配前先剝離條目*開頭*的註釋行，使得內含自身`'KEY' => ...`的註釋掉示例不會再被誤判為緊隨其後的真實鍵。寫回的conf.php中原始註釋文字會原樣保留（僅內部鍵匹配的輸入受影響，輸出完全不受影響）。install.php端的防禦性回退（將此類欄位強制顯示為不可編輯的"raw"形式）已因本次修復而不再必要，但作為安全網繼續保留。已針對所報告的HOSTNAME_POSTDENIED/TMPL_MSG/TMPL_ENVLIST案例、CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC序列，以及類似HANDLENAMES的巢狀陣列條目（針對非貪婪匹配所依賴的外層鍵檢測的迴歸測試）進行了驗證。
* （2026-08-01已實現）conf.php確認介面現會透過新增的`ksphp_conf_entry_comment_text()`輔助函式，在確認表格的鍵名下方顯示各設定項自身的註釋文字（conf.php中每個鍵本就配有成對的日語／英語註釋）作為幫助說明。裝飾性的分隔線（`#---- ... ----`）以及僅供譯者參考的備註（`## TL note: ...`）會從顯示中過濾掉。故意保持輕量實現：顯示的文字是conf.php原始碼註釋原樣（日語+英語），並未翻譯為安裝介面的其他5種語言——這部分依賴讀者的瀏覽器翻譯功能。將每個鍵的說明文字完整翻譯為全部7種安裝介面語言（約98個鍵）的工作，經討論後作為一項更繁重的獨立任務被推遲（詳見本列表下文）。
* 尚未開始：將conf.php各鍵的幫助文字完整翻譯為7種語言（目前僅有日語／英語原文，見上文）。範圍約為98個鍵；需要為韓語、葡萄牙語、土耳其語、簡體中文、繁體中文新增`install/language/*.txt`鍵（例如`CONF_HELP_<KEY>`）。

  **【2026-08-02更新】上述工作已於今日完成，並附帶一處漏洞修復。** conf.php的條目拆分邏輯，對使用行末註釋形式（而非前置註釋塊）的6個鍵（C_A_COLOR・C_A_VISITED・C_A_ACTIVE・C_A_HOVER・C_SUBJ・C_ERROR，即連結色、標題色、錯誤色相關設定）存在實際的幫助文字錯位一項的漏洞，已透過新增`ksphp_conf_entry_trailing_comment()` / `ksphp_conf_build_help_texts()`修復。在此基礎上，已按`CONF_HELP_<KEY>`格式，為7個`install/language/*.txt`檔案各自新增98個條目（english/japanese/korean/portuguese/turkish/zh-hans/zh-hant）。同時實現了`ksphp_conf_help_text()`查詢機制：對尚無翻譯的鍵，會安全回退到（已修復漏洞的）conf.php原始註釋提取內容。已透過PHP內建伺服器對實際的`?ajax=1&action=conf_review`端點進行了7種語言的端到端驗證。尚未打包進新的釋出zip，也未反映到changelog中。
* （2026-08-01已實現，客戶端JS）論壇上提出的社群功能需求——LaTeX數學公式渲染（`$E=mc^2$`格式，newbbs/js/latexrender.js，僅當讀者個人主動開啟時才從CDN載入KaTeX）、"摺疊／刪除未讀串"控制（newbbs/js/treehide.js，隨時可恢復）、以及長帖行數過濾器（newbbs/js/longpostfilter.js，閾值可調）——三者均以個人化（按瀏覽器、基於localStorage）的自願開啟方式實現，預設關閉，不涉及伺服器端。將NG詞匹配器移植到WebAssembly以提升速度的另一項需求仍處於擱置狀態：維護者會先製作原型並進行基準測試，只有在確實能帶來顯著幫助時才會實現（懷疑真正的瓶頸在於NG詞列表的規模，而非匹配演算法本身）。
* （2026-07-25已審查，維護者決定）舊版"移動模組"設定：已確認`RESTRICT_MOBILEIP`配置鍵實際上已死（未被任何程式碼引用）——它是已廢棄的獨立移動裝置輸出模組的遺留物。當前的移動端支援僅透過CSS實現（viewport meta標籤+媒體查詢斷點），沒有伺服器端UA判斷。維護者的決定：將"非PC"式UA判斷方案留待未來有需要時再考慮，目前僅修復審查中發現的具體CSS問題——`.msgtree`（AA／串檢視）在窄屏下缺少`overflow-x: auto`（僅在桌面寬度下生效，導致過長的AA行將整個頁面向側邊推擠，而非在區塊內滾動），`.postlists`（管理員帖子列表表格）則完全沒有橫向滾動處理。兩處均已修復。
* （2026-07-25已實現）帖子正文中的`#話題標籤`現會（在現有`AUTOLINK`設定控制下）自動轉換為getlog（`m=g`）全文搜尋連結，搜尋範圍以該帖子自身的日期為基準劃定時間視窗——`OLDLOGSAVESW=1`（按月日誌檔案）時為該帖所在的月份，`OLDLOGSAVESW=0`（按日日誌檔案）時為包含發帖當天在內的最近7天。
* 新帖介面未顯示錶單
* 正確使用多位元組函式與jcode
* 完善上傳器縮圖JavaScript，使其能方便地支援其他Uploader類軟體的例項
* （2026-07-18決定，維護者判斷）首頁發帖表單刻意不顯示"發帖完成"確認介面（僅跟帖／回覆會顯示）。已審查並保持現狀，非漏洞。
* （2026-07-18決定，維護者判斷）不會在bbs.php中新增`Cache-Control: no-store`。發帖後返回時偶爾殘留舊錶單內容的問題，作為換取bfcache更快"返回"導航速度的權衡而被接受。
* （2026-07-19記錄，下述2026-07-20的變更後依然適用）`ADMINKEY`（管理員發帖模式進入關鍵詞）以明文儲存，並透過簡單字串比較進行匹配，這與使用crypt/bcrypt雜湊的`ADMINPOST`不同。已確認存在安全隱患；該功能暫時保持現狀，但未來版本應考慮採用與`ADMINPOST`類似的雜湊/比對方案。
* （2026-07-20已實現）管理員機密設定（`ADMINPOST`/`ADMINKEY`）已完全從conf.php中分離，移至一個不屬於newbbs/釋出模板、因而永遠不會被install.php的conf合併流程觸及的固定檔名檔案（`local.php`）。其設定／修改不再透過conf.php或install.php進行，而是透過一個獨立工具（初始名為`_setup.php`，首次使用時由運營者重新命名）。原始設計討論詳見doc/admin-secrets-concept-2026-07-19-01.txt。

* （2026-08-01已修復，RC11）install.php的conf-review確認介面中，數學表示式形式的值（例如`MAXOLDLOGSIZE`的`4 * 1024 * 1024`）在寫回時被作為帶引號的字串（`'1023998976'`）而非純數字儲存，導致執行時出現字串與整數比較錯誤（日誌大小檢查誤動作）。已透過在`ksphp_conf_apply_review()`中，於現有的純數字檢查基礎上新增`$was_numeric_expr`檢查（僅由數字/運算子/空白組成）來修復，使此類值以不加引號的形式寫入。在本修復之前建立的現有conf.php檔案，其受影響的鍵需要進行一次性手動修改（將帶引號的值改為不加引號的整數或表示式）。
* （2026-08-01已修復，RC11）bbs.php中的`Func::html_escape()`，透過`str_replace("\015$", "", $value)`刪除了帖子正文第二行起每行行首的`$`字元——這是其上方CR/LF標準化處理之前遺留下來的程式碼，也看不出有任何安全方面的依據。這導致帖子第二行及以後的LaTeX風格`$...$`定界符（參見上文latexrender.js）被破壞。已刪除該行程式碼。
* （2026-08-01已實現，RC11）conf.php確認介面現會將各設定項自身的conf.php註釋顯示為幫助文字（詳情參見上文`ksphp_conf_entry_comment_text()`條目）。
* （2026-08-01已修復，RC12）`BBSLINK`的值實際上是多行的HTML／文字塊，但在conf.php確認介面中卻被渲染為單行文字輸入框。已新增`ksphp_conf_longtext_keys()`列表及`'longtext'`欄位型別（顯示／儲存邏輯與現有的`'text'`型別完全相同，僅表單控制元件不同——使用`<textarea>`而非`<input type="text">`），並將`BBSLINK`註冊到該型別下。
* （2026-08-01已實現，RC13）所有按瀏覽器區分的JS功能開關（現有的kaomoji.js・upthumb.js・imgthumb.js・vidembed.js，以及新增的longpostfilter.js・latexrender.js・treehide.js）均已整合進個人設定（"個人環境設定"，m=c）頁面內新增的"JS設定"fieldset。設定項現改為透過新增的獨立Cookie `ksphp_js`（JSON格式，90天有效期）儲存，而非localStorage，與頁面其他設定一同透過同一個"註冊"按鈕提交；單一的PHP定義表（bbs.php中的`ksphp_js_setting_defs()`）負責驅動Cookie的載入／儲存／表單渲染，因此今後新增JS功能只需在該表中新增一行。原先頁面頂部三個獨立的開關（treehide/longpostfilter/latexrender）已移除；針對從RC10~RC12升級的使用者，這三項會先優先採用已有的localStorage值一次，此後則以Cookie為準。ayashiibreaker.js（換行功能）按規格不設開關核取方塊（視為必需功能），但其行長度引數現已可在面板中配置；可配置的最大值由伺服器端根據conf.php自身的`MAXMSGCOL`計算得出，因此永遠不會超出伺服器可接受的範圍；由於`MAXMSGCOL`是基於`strlen()`的位元組數限制，而換行功能按字元數計算，含日語的行使用約為設定值1/3的數值（禁則處理安全邊距）。新增的9個模板鍵已新增完整的7語言翻譯。
* 尚未開始：允許個人設定面板在客戶端覆蓋conf.php層級的"Gikoneko-to-issho"設定（即conf.php端可以啟用它，但讀者仍可自行將其關閉）。這原本是JS面板需求的一部分，但在專注於上述各JS檔案的RC13階段範圍之外，暫未實現。

## 已知漏洞：
* （遷移說明，RC13及以後）RC10~RC12新增的三個按瀏覽器區分的JS功能（treehide、longpostfilter、latexrender）最初將開關狀態儲存在localStorage中。RC13將這些設定遷移至伺服器Cookie（`ksphp_js`），出於遷移考慮，各指令碼在存在已有localStorage值時仍會優先採用該值。因此，若某瀏覽器在RC10~RC12時期曾明確將某項功能關閉，在新的JS設定面板中將其開啟也可能看似沒有效果——因為舊的localStorage中的`0`佔了上風。修復方法是每個瀏覽器一次性操作：在面板中將該功能關閉再重新開啟，或刪除舊的鍵（`ksphp_treehide_enabled`、`ksphp_longpost_enabled`、`ksphp_latex_enabled`）。全新安裝以及從未經歷過RC10~RC12的瀏覽器不受影響。
* （2026-08-01發現並修復，RC13）longpostfilter.js中每條帖子的"折りたたむ"（摺疊）連結，無論是否真正超過行數閾值都會顯示，原因是未摺疊的渲染路徑會無條件呼叫expand()並保持連結可見。已修復為僅在真正被摺疊（即確實超過閾值）的帖子被手動重新展開後，才會顯示摺疊連結。
* （2026-08-01發現並修復，RC12-02）RC11的交接記錄曾聲稱已在`ksphp_conf_apply_review()`中新增`$was_numeric_expr`檢查，以在儲存時使數學表示式形式的配置值（例如`MAXOLDLOGSIZE`的`4 * 1024 * 1024`）保持不加引號，但實際上出貨程式碼（RC11與RC12均是）中從未真正存在該檢查——僅保留了原有的純數字檢查。此問題是透過某實機站點的`MAXMSGSIZE`值（`'250*120*128*256*128'`，此前某次安裝執行時被儲存為帶引號的字串）在`procForm()`的內容長度比較中觸發PHP非數值警告而發現的，該警告進而破壞了HTTP響應，在瀏覽器端表現為`ERR_CONTENT_DECODING_FAILED`。該檢查現已真正實現。重要限制：這僅能防止今後安裝／遷移中出現該漏洞——並**不會**自動修復已經儲存為帶引號字串的conf.php值（確認表單只會原樣重新提交現有的帶引號值）。任何已經損壞的數學表示式鍵，都需要手動編輯conf.php，或在確認表單中重新輸入並提交該值。
* 搜尋日誌時出現大量\&nbsp;
* （2026-08-01，在install.php conf-review測試過程中發現；同日修復，根本原因詳見"待辦事項"）`ksphp_conf_parse_entries()`的條目邊界掃描器，在緊鄰其前方存在包含形似帶引號`'KEY' =>`字串的註釋掉示例時，會誤判鍵名（例如註釋掉的TMPL_MSG/TMPL_ENVLIST之後的HOSTNAME_POSTDENIED，以及各自注釋掉示例之後的CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC）。已在全部四處呼叫點（`ksphp_conf_merge()`、`ksphp_conf_build_review()`、`ksphp_conf_apply_review()`、`ksphp_parse_module_array()`）中，透過在鍵名匹配前剝離開頭的註釋行進行修復；install.php端的防禦性"raw"回退機制作為安全網繼續保留，但實際使用中應不再會被觸發。
* （2026-08-01已在qptns.com實機確認；同日修復——詳情參見"待辦事項"）install.php的conf-review確認介面曾錯誤地將ZIPDIR標記為必填項；實際上ZIPDIR為空是一個有效設定，表示"不建立zip日誌"。已一併修復OLDLOGFILEDIR及CNTFILENAME的相同問題（同樣的"留空＝功能禁用"模式，已透過各自的conf.php註釋確認）。
* （2026-08-01已實現）按日／按月的過去日誌輪換檔案的"檔案不存在，已新建"自動建立提示，現僅在真正的首次設定時顯示（即OLDLOGFILEDIR內尚不存在其他相同副檔名的過去日誌檔案時）。在日／月邊界處出現帶日期的新檔案、而已有過去檔案存在的常規輪換場景中，該提示不再顯示。刻意保持輕量實現：僅透過目錄列表檢查（OLDLOGFILEDIR內是否已存在其他過去日誌檔案），而非透過日曆/日期計算來判斷"是否為該週期的首篇帖子"。
* （2026-08-01發現並修復，RC14-02，透過qptns.com/test/上的實機測試）ayashiibreaker.js的換行目標值，在RC14初次修復（日語行為configuredLen-2）之後仍然過於緊湊：針對特定長測試行進行實機驗證後，日語與ASCII行均上調至configuredLen-12。另外，ASCII（英語）單詞邊界換行還存在自身的漏洞：僅在已確定要追加某單詞"之後"才判斷是否超出閾值，且從不拆分長度超過目標值的單個單詞（例如"ayashiibreaker.js"、"word-boundary"），因此無論邊距如何設定，某一行都可能保持未被截斷的狀態。已重寫為按行累積陣列、對過長單詞強制按字元拆分、並在追加前先檢查候選長度的方式，使ASCII行現在必定遵守所配置的上限。
