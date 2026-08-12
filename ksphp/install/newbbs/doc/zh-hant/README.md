# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
KuzuhaScript（くずはすくりぷと）PHP移植版的改進版本。
截至2024/10/16，僅可在PHP8及以上版本執行。
最後一個支援舊版PHP（4.1.0～7.4）的版本可在此處獲取：[https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)

本程式基於2005/04/01改版的KuzuhaScriptPHP（くずはすくりぷとPHP）。

本程式最初由[Strange World@Heyuri.net的Anonymous-san](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555)翻譯為英文，此後由Heyuri的多位匿名開發者持續維護。

* [KuzuhaScriptPHP（鏡像）](http://qptn.x.fc2.com/up/dauso0059.zip)
* [2005/04/01改版](http://qptn.x.fc2.com/up/dauso0073.zip)

## 維護者資訊
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## 安裝步驟
1. 解壓縮下載的ZIP檔案
2. 開啟並設定conf.php
3. 使用FTP用戶端等工具將檔案上傳至伺服器（建議建立專用目錄）
4. 設定以下所述的權限
5. 在瀏覽器中存取`_setup.php`（套件內附的獨立工具，非conf.php或install.php的一部分），設定管理員密碼。完成後工具會自動重新命名——請記下新的名稱/URL。
6. *（現已不需要——管理員密碼由`_setup.php`寫入專用檔案，不再寫入conf.php）*
7. 在瀏覽器中存取bbs.php，確認可以發文
8. 在瀏覽器中存取日誌檔案的URL（bbs.log、log/等），確認無法公開存取（若可存取，請使用.htaccess等方式限制）

## 疑難排解
### 升級現有網站（管理員密碼遷移）
自RC8起，管理員密碼（ADMINPOST/ADMINKEY）存放在conf.php之外的固定名稱檔案`local.php`中，install.php不會覆寫此檔案。當install.php偵測到現有conf.php中有非空的ADMINPOST時，會顯示遷移表單，要求輸入**舊密碼**（身份驗證）和**新密碼**。ADMINKEY會自動繼承。

- 舊密碼驗證成功後，`local.php`會被寫入並繼續安裝。
- 驗證失敗時，**整個安裝流程會中止**（不會安裝任何檔案）。
- 若真的忘記舊密碼，請手動清空伺服器上conf.php中的`ADMINPOST`值，install.php將以全新安裝方式處理，之後可透過`_setup.php`設定新密碼。

## 建議權限設定
權限設定錯誤可能導致問題和資料洩漏（IP位址、遠端主機等）。

```
[檔案結構]
|-- bbs.cnt   600（可寫入）  參與者清單記錄檔（空白文字檔）
|-- bbs.log   600（可寫入）  日誌檔案（空白文字檔）
|-- conf.php  644（唯讀）    設定檔
|-- bbs.php   644（唯讀）    留言板主程式
|-- readme.md               本檔案
|-- vanish.js               詞彙過濾腳本
|
+-- archive/  700（可寫入）  ZIP封存儲存目錄
+-- count/    700（可寫入）  計數器輸出目錄
+-- log/      700（可寫入）  訊息日誌儲存目錄
+-- sub/      755（唯讀）    子模組
    |-- bbsadmin.php    644  管理模組
    |-- bbslog.php      644  日誌檢視模組
    |-- bbstree.php     644  樹狀顯示模組
    |-- phpzip.inc.php  644  ZIP建立函式庫
```

若PHP以Apache模組方式執行，bbs.php設定為644即可。若以CGI方式執行，需設定為755。

## 參考
### bbs.php?m=* 參數說明

| 參數 | 說明 |
| --- | --- |
| m=g | 訊息日誌搜尋 |
| m=ad | 管理員模式 |
| m=tree | 樹狀顯示 |
| m=p | 發文／重新載入 |
| m=c | 個人設定 |
| m=f | 回覆畫面 |
| m=t | 討論串顯示 |
| m=s | 依使用者搜尋 |
| m=u | 執行UNDO |

## 沿革
（早期版本詳情請參閱英文版README）

### RC8 (2026/07/20)
* 管理員密碼（ADMINPOST/ADMINKEY）移至conf.php外的`local.php`，由獨立工具`_setup.php`管理
* install.php：升級時若存在ADMINPOST則顯示密碼遷移表單

### RC9 (2026/07/25)
* 行動裝置顯示修正、ZIP建立未定義變數修正
* 文章內`#標籤`自動轉換為日期範圍getlog搜尋連結

### RC10 (2026/08/01)
* install.php：conf.php調整・確認畫面（自動合併、7語言支援、逐檔復原）
* 選擇性JS功能3項：LaTeX數式渲染（latexrender.js）、未讀討論串折疊（treehide.js）、長文行數過濾（longpostfilter.js）

### RC11 (2026/08/01)
* install.php：conf.php項目邊界解析器根本修正
* bbs.php：修正LaTeX `$...$` 分隔符在第二行以後失效的問題

### RC12 (2026/08/01)
* BBSLINK確認畫面改為textarea
* 數值運算式格式的設定值不再被儲存為字串

### RC13 (2026/08/01)
* 瀏覽器單位的JS切換整合至個人設定（m=c）的「JS設定」fieldset，從localStorage改為cookie儲存

### RC14 (2026/08/01)
* RC13錯誤修正3項：表情符號fieldset間距、longpostfilter折疊連結、ayashiibreaker目標寬度
* ayashiibreaker：ASCII單詞邊界換行重新實作，過長單詞現在必定會被分割

### RC15 (2026/08/02)
* PHPStan Level 5全面審查・錯誤修正3項
* conf.php確認畫面：顯示各設定的說明文字，所有98個CONF_HELP_*索引鍵支援7種語言
* doc/整理為各語言子目錄結構

### RC16 (2026/08/03)
* main_upper新增語言切換選單
* 個人設定面板：可在客戶端覆蓋conf.php層級的`GIKONEKO_TOISSHO`設定
* gikoneko.php / gikonekoadd.php多語言化

### RC17 (2026/08/03)
* 樹狀顯示：改寫引用行以金色標示，新增排序切換（新→舊/舊→新，瀏覽器儲存）
* LaTeX：修正`$變數`形式的符號被誤認為數式分隔符的問題
* install.php：安裝程式UI・CONF_HELP_*項目的7語言支援

### RC18 (2026/08/07)
* bbs.php：日誌寫入語言一致性修正——參考行、自回覆標籤、星期名稱現在一律以留言板預設語言（LANGUAGE_FILE）寫入日誌，不受訪客選擇語言影響（新增TDefault() / getdatestr_default()）
* bbs.php：移除tripuse()中的mbstring依賴，僅使用iconv()（qptns.com行為不變）
* install.php：版本升級時新增管理員密碼保留/變更選擇UI（全7種語言）
* install.php：版本升級時略過內容相同的檔案（SHA-256雜湊比較，不受換行符差異影響）
* bbs.php：參考行和自我回覆標籤在顯示時跟隨訪客語言（日誌仍以預設語言儲存，僅顯示時轉換，日誌格式不變）
* bbs.php・sub/*.php：參考行的刪除處理（樹狀顯示・日誌摘要・管理畫面・圖片BBS）從英語硬編碼改為多語言支援

### RC18 後續修正（2026/08/08）——僅安裝程式，版本號不變
* install.php：多目標安裝串列化——將遞迴的 runNextTarget() 替換為 processSingleTarget()＋順序 Promise 鏈；conf 確認、管理員密碼輸入等互動現在按目標逐一正確執行
* install.php：conf 確認表單內嵌至記錄列表；透過 CGIURL 產生動態連結；新增安裝記錄儲存功能
* install.php：步驟編號（NN-S/T 格式）顯示、conf 跳過原因顯示、按目標顯示安裝標題
* install.php：廢除 ksphp_migrate() 呼叫，改為直接在每個目標目錄內建立 data/.migrated 標記（解決 KSPHP_ROOT 無法重複定義的問題）
* install.php：修復 ksphp_install_run() 內未定義的 $target_dir（正確名稱為 $parent_dir）——多目標安裝通訊錯誤的根本原因
* template.html：再次還原 main_upper 中缺失的語言選擇器

詳細內容請參閱 `doc/changelog-2026-07-16-01.txt`。

## 待辦事項
* **上傳器縮圖JS** — 使upthumb.js易於支援Uploader@Heyuri以外的Uploader實例
* **新發文畫面不顯示表單** — 間歇性發生，重現條件不明
* **在實際瀏覽器中確認RTL版面** — 邏輯屬性和`dir`屬性已經導入，但實際顯示尚未確認。由於`.msgtree`使用`white-space: pre`，樹狀顯示最容易出現錯亂。發文表單、頁首導覽列以及引用和文章編號的位置也需要檢查。
* **安裝程式的阿拉伯語檔案** — 論壇本體的`language/arabic.txt`已完成；`install/language/`是247個鍵的獨立體系，尚無阿拉伯語版本。安裝程式透過掃描目錄建立語言清單，因此阿拉伯語目前只是不會出現在那裡。
* **另外七個語言檔案** — 西班牙語、法語、德語、印尼語、越南語、他加祿語和印地語。它們都已預先登記在同樣的表中，僅剩`.txt`檔案本體。
* **install.php的自動重新命名** — `_setup.php`在首次使用後會自動改名，而`install.php`沒有該機制。目前僅有刪除步驟的文件說明，以及用於抑制目錄清單的`index.html`。

## 已知問題
* 搜尋日誌時出現大量`&nbsp;`
