# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
KuzuhaScript（くずはすくりぷと）PHP移植版的改进版本。
截至2024/10/16，仅可在PHP8及以上版本运行。
最后一个支持旧版PHP（4.1.0～7.4）的版本可在此处获取：[https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)


本程序基于2005/04/01修改版的KuzuhaScriptPHP（くずはすくりぷとPHP）。

本程序最初由[Strange World@Heyuri.net的Anonymous-san](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555)翻译为英文，此后Heyuri的多位匿名开发者陆续做出了贡献。


* [KuzuhaScriptPHP（镜像）](http://qptn.x.fc2.com/up/dauso0059.zip)  
* [2005/04/01修改版](http://qptn.x.fc2.com/up/dauso0073.zip)

## 维护者信息
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## 安装流程（仅供参考）
1. 解压下载的ZIP文件
2. 打开并配置conf.php
3. 使用FTP客户端等工具将文件上传至服务器（建议创建专用目录，避免与其他文件混杂）
4. 按照readme.md中的说明设置权限
5. 打开浏览器，访问`_setup.php`（包内附带的独立工具，不属于conf.php或install.php的一部分），在此设置管理员密码。完成后，该工具会自动重命名为您指定的名称（系统会建议一个不易被猜到的默认名称）——请务必记下新的名称/URL，因为之后修改密码时会用到。
6. （现已不再需要——管理员密码现在由`_setup.php`直接写入其专用文件，而非conf.php。）
7. 打开浏览器访问bbs.php，确认是否可以发帖
8. 用浏览器访问日志文件（bbs.log、log/内的文件等）所在的URL，确认是否能够看到（如果能看到，请用.htaccess等方式将其隐藏）

## 故障排除
### 升级现有站点（管理员密码迁移）
自RC8起，管理员密码（ADMINPOST/ADMINKEY）存放在conf.php之外，位于一个不属于本模板、且install.php永远不会覆盖的固定文件名`local.php`中。当install.php检测到您正在升级的站点在其现有conf.php中已有非空的ADMINPOST时，会显示一个迁移表单，要求输入**旧密码**（用于验证确实是您本人）以及要设置的**新密码**。管理模式关键词（ADMINKEY）会自动继承，无需单独填写。

- 若旧密码验证成功，`local.php`会以新密码写入，安装照常继续。
- 若验证失败，**整个安装过程将被中止**（不会安装任何文件，也不会进行任何备份或覆盖），以防止他人劫持一个自己并不掌控的站点的管理员账号。
- 如果您确实忘记了旧密码，请在服务器上打开现有的conf.php，手动将`ADMINPOST`的值清空（设为空字符串）。这会使install.php将该站点视为全新安装，之后即可像新安装一样，通过`_setup.php`设置全新的密码。

## 建议的权限设置
权限设置不当可能导致问题及数据泄露（如帖子发布者的IP地址或远程主机名），请务必确认设置正确。

```
[文件结构]
|-- bbs.cnt   600（可写）        参与者列表记录文件（空文本文件）
|-- bbs.log   600（可写）        日志文件（空文本文件）
|-- conf.php  644（只读）        用于配置
|-- bbs.php 644（只读）          论坛主脚本
|-- readme.md                     说明文档（本文件）
|
|-- vanish.js                     词语过滤脚本
|
|
+-- archive/  700（可写）        ZIP存档保存目录
+-- count/    700（可写）        计数器输出目录
+-- log/      700（可写）        消息日志（原始日志）保存目录
+-- sub/      755（只读）        子模块保存目录
    |
    |-- bbsadmin.php    644（只读）    管理模块
    |-- bbslog.php      644（只读）    日志查看模块
    |-- bbstree.php     644（只读）    树状视图模块
    |-- phpzip.inc.php  644（只读）    ZIP文件创建库
```

若PHP以Apache模块方式运行，bbs.php保持只读即可，
但若以CGI方式运行，则需将bbs.php设为755（可执行）。

## 备注：
### bbs.php?m=* 参数含义一览

| 参数 | 含义 |
| --- | --- |
| m=g | 消息日志搜索 |
| m=ad | 管理员模式 |
| m=tree | 树状视图 |
| m=p | 发帖／刷新 |
| m=c | 个人设置 |
| m=f | 跟帖界面 |
| m=t | 串（Thread）显示 |
| m=s | 按发帖者搜索 |
| m=u | 执行UNDO |

## 沿革
### Cion（しおん）版
* 2003/01/21 开始开发
* 2003/01/31 0.0.1alpha
* 2003/02/03 0.0.2alpha
* 2003/02/11 0.0.3alpha
* 2003/02/13 0.0.4alpha
* 2003/02/14 0.0.5alpha
* 2003/02/16 0.0.6alpha
* 2003/02/18 0.0.7alpha

### 非官方
* 2005/04/01 0.0.8alpha（非官方）由志愿者发布的修改版（镜像：http://www.freak.ne.jp/~lunatica/home/up/freak/dauso0073.zip）

### 日期不明（蛭ヶ岳版）
* 修正了UI，在智能手机等设备上更易使用
* 迁移至UTF-8（＠Links）
* PHPZip更新至v1.2
* 其他各种修复（未记录）

### 日期不明
* 编码风格微调
* 修复跟帖漏洞
* 移除jcode-LE
* 修复用户设置未生效的问题(?)
* 模板不再是问题
* 解决func类的神秘实现问题（未完成）
* 为支持PHP7.x做准备

### 2018/10/12
* 更名为"KuzuhaScriptPHP+"（くずはすくりぷとPHP+）
* 修复因检查不完善导致表单数据缺失时未能失效的问题
* 微调UI

### 2018/11/18
* 应用了Motoi(gikonekos)的树状视图修正
* 内置vanish.js

### 2019/11/02
* 移除EZweb显示（HDML）
* 移除imode显示

### 2019/11/02
* 移除EZweb显示（HDML）
* 移除imode显示

### 2020/02/11
* 应用了Motoi(gikonekos)的树状视图漏洞修复

### 2020/03/15
* 计数器改为逗号分隔显示

### 2020/03/29
* 新增Motoi(gikonekos)的YouTube嵌入功能

### 2021/03/08
* 设计调整（文本框等）
* conf.php（修改了表述与默认值）

### 2021/07/03
* 新增Motoi(gikonekos)的2ch trip功能(20210625)
* 移除bbs.cgi

### 2021/07/27
* 修复同时使用管理员密码与trip（Motoi(gikonekos)）时密码泄露的问题
* 姓名、邮箱、标题的最大字符数现可设置
* 将bbs.php中的说明移至readme.md

### 2022/05/06
* 将bbs.php迁移为index.php
* 微调UI

### 20221127
* sub/patTemplate.php：应用Motoi(gikonekos)的修复

### 20230118
* 修复树状视图显示不正确的漏洞

### 20230520
* 应用Motoi(gikonekos)的管理路径泄露防范措施(20210923)
* 应用Motoi(gikonekos)的昵称密码泄露防范修复(20210923)

### 日期不明（Heyuri版）
* 全面英译
* 新增表情符号（顔文字）按钮
* 行高改为1
* 注释掉浏览器换行用CSS
* 新增便于用户换行的JavaScript
* 注释掉Motoi(gikonekos)的YouTube嵌入功能
* 实现YouTube嵌入用JavaScript
* 新增图片缩略图JavaScript，默认仅对Uploader@Heyuri生效

### 2024/10/16
* 迁移至PHP8
* 将index.php改回bbs.php
* JS文件移至独立目录

### 2025/04/29
* 修复应用用户设置后重定向不生效的问题
* 移除conf.php中未使用的自定义设置

### 2025/06/07
* 新增IPv6地址支持
* 修改http头设置以允许从任意位置嵌入

### 2025/09/09
* 修复主机封禁相关漏洞

### 2025/11/08
* 为长行新增纵向滚动CSS（仅限PC）
* 将表情符号按钮改为fieldset形式
* 修复表单非空时点击用户设置会导致误发帖的漏洞
* あやしいブレーカー（换行器）现会在长行发帖时通过发光提示用户

### 2025/11/09
* 新增日语的完整国际化支持，英语现变为可选项
* 软件名称由KuzuhaScriptPHP+EN改回KuzuhaScriptPHP+

### 2026/03/10
* 管理员密钥现可与由Motoi(gikonekos)实现的trip代码组合使用

### 2026/03/15
* 进入管理菜单的方式改为顶部链接
* 管理菜单重新设计，出于安全与便利性考虑改用会话（session）机制

### 2026/06/27
* 帖子删除管理界面新增筛选与批量选择辅助功能

### 2026/07/18
* gikoneko.php / gikonekoadd.php的界面文字现已通过标准`$MSG`机制实现多语言支持（language/*.txt新增22个键）
* gikoneko.php：占卜数据文件缺失时现会自动创建，`file()`不再直接向页面输出PHP原生警告
* gikonekoadd.php：修复数据文件路径不一致（`../cgi-bin/...`）的问题，此前该问题导致新学习的词语始终无法进入gikoneko.php的占卜词库；两个脚本现统一引用同一个根目录下的数据文件
* ayashiibreaker.js更新至v0.4.0：日语行的换行现基于禁则处理（禁則処理）规则按字符数计算，而非依赖以空格分隔的单词换行

### 2026/07/19
* bbs.php：主论坛显示（`getdispmessage()`）以及`msgsearchlist()`的当日日志分支，现改为通过`Func::fgetline()`逐行流式读取日志文件，而非通过`file()`将整个文件读入内存。普通页面浏览时的峰值内存占用不再与`LOGSAVE`／日志文件大小成比例增长（已验证：在一份102,300行、约58MB的日志上，峰值内存由约131MB降至约2MB；模拟100个并发请求的流量峰值测试中，不再像此前实现那样触发OOM Kill）
* bbs.php：移除长期被注释掉的YouTube嵌入代码（自2024/10/16起已由ytthumb.js取代）
* bbs.php / conf.php：未读帖数为0时是否显示"Gikoneko-to-issho"现可通过`GIKONEKO_TOISSHO`（1=开启，0=关闭，默认1）配置；禁用后将回退到此前简单的"无未读帖"提示信息
* install.php：为附近扫描未能找到的文件夹新增文本输入式"添加新安装目标"流程（含路径穿越校验及使用前的确认步骤）
* install.php：新增日语／英语界面语言切换功能（默认日语），包括安装日志消息的翻译
* install.php：新增安全防护，拒绝以文件系统根目录、过浅路径或install/文件夹本身作为安装目标
* install.php：现有文件的备份方式由`copy()`改为`rename()`（原子操作）；单个文件的备份／重命名失败时，现仅跳过该文件而不再中止整个执行；新文件写入失败时会自动回滚已挪至一旁的原文件，失败情况还会额外记录到`install/backup/install-errors-YYYY-MM-DD.txt`以便后续查阅

## 待办事项：
* （2026-08-01已实现）install.php：迁移过程中新增conf.php"调整"步骤——由自动合并结果预填的复选框／单选／列表／文本字段、必填项高亮、支持按文件回滚的服务器端校验、可选退出的个人化开关（默认开启）、7种语言完整支持。
* （2026-08-01已实现）根据qptns.com实机测试反馈进行的后续UI修复：(1) 所有原有复选框（布尔0/1键）现统一渲染为2选项单选按钮组，以与现有的3选项单选键保持一致，并回应"复选框令人困惑"的反馈；(2) ZIPDIR、OLDLOGFILEDIR、CNTFILENAME在确认界面中不再标记为必填——conf.php自身的注释已说明，对这三项而言留空是有效的"功能禁用"状态（已通过查阅各手动路径键的实际注释文本验证；其他手动路径键——LOGFILENAME、COUNTFILE、GIKONEKO_KOTOBA_FILE、UPLOADDIR、UPLOADIDFILE——没有此类注释，仍保持必填）。
* （2026-08-01根本修复）`ksphp_conf_parse_entries()`条目边界解析器漏洞（原始报告详见"已知漏洞"）：`ksphp_conf_merge()`、`ksphp_conf_build_review()`、`ksphp_conf_apply_review()`、`ksphp_parse_module_array()`中的键提取步骤，现通过新增的`ksphp_conf_entry_split_lead_comments()`辅助函数，在执行键名正则匹配前先剥离条目*开头*的注释行，使得内含自身`'KEY' => ...`的注释掉示例不会再被误判为紧随其后的真实键。写回的conf.php中原始注释文本会原样保留（仅内部键匹配的输入受影响，输出完全不受影响）。install.php端的防御性回退（将此类字段强制显示为不可编辑的"raw"形式）已因本次修复而不再必要，但作为安全网继续保留。已针对所报告的HOSTNAME_POSTDENIED/TMPL_MSG/TMPL_ENVLIST案例、CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC序列，以及类似HANDLENAMES的嵌套数组条目（针对非贪婪匹配所依赖的外层键检测的回归测试）进行了验证。
* （2026-08-01已实现）conf.php确认界面现会通过新增的`ksphp_conf_entry_comment_text()`辅助函数，在确认表格的键名下方显示各设置项自身的注释文字（conf.php中每个键本就配有成对的日语／英语注释）作为帮助说明。装饰性的分隔线（`#---- ... ----`）以及仅供译者参考的备注（`## TL note: ...`）会从显示中过滤掉。故意保持轻量实现：显示的文字是conf.php源码注释原样（日语+英语），并未翻译为安装界面的其他5种语言——这部分依赖读者的浏览器翻译功能。将每个键的说明文字完整翻译为全部7种安装界面语言（约98个键）的工作，经讨论后作为一项更繁重的独立任务被推迟（详见本列表下文）。
* 尚未开始：将conf.php各键的帮助文字完整翻译为7种语言（目前仅有日语／英语原文，见上文）。范围约为98个键；需要为韩语、葡萄牙语、土耳其语、简体中文、繁体中文新增`install/language/*.txt`键（例如`CONF_HELP_<KEY>`）。

  **【2026-08-02更新】上述工作已于今日完成，并附带一处漏洞修复。** conf.php的条目拆分逻辑，对使用行末注释形式（而非前置注释块）的6个键（C_A_COLOR・C_A_VISITED・C_A_ACTIVE・C_A_HOVER・C_SUBJ・C_ERROR，即链接色、标题色、错误色相关设置）存在实际的帮助文字错位一项的漏洞，已通过新增`ksphp_conf_entry_trailing_comment()` / `ksphp_conf_build_help_texts()`修复。在此基础上，已按`CONF_HELP_<KEY>`格式，为7个`install/language/*.txt`文件各自新增98个条目（english/japanese/korean/portuguese/turkish/zh-hans/zh-hant）。同时实现了`ksphp_conf_help_text()`查找机制：对尚无翻译的键，会安全回退到（已修复漏洞的）conf.php原始注释提取内容。已通过PHP内置服务器对实际的`?ajax=1&action=conf_review`端点进行了7种语言的端到端验证。尚未打包进新的发布zip，也未反映到changelog中。
* （2026-08-01已实现，客户端JS）论坛上提出的社区功能需求——LaTeX数学公式渲染（`$E=mc^2$`格式，newbbs/js/latexrender.js，仅当读者个人主动开启时才从CDN加载KaTeX）、"折叠／删除未读串"控制（newbbs/js/treehide.js，随时可恢复）、以及长帖行数过滤器（newbbs/js/longpostfilter.js，阈值可调）——三者均以个人化（按浏览器、基于localStorage）的自愿开启方式实现，默认关闭，不涉及服务器端。将NG词匹配器移植到WebAssembly以提升速度的另一项需求仍处于搁置状态：维护者会先制作原型并进行基准测试，只有在确实能带来显著帮助时才会实现（怀疑真正的瓶颈在于NG词列表的规模，而非匹配算法本身）。
* （2026-07-25已审查，维护者决定）旧版"移动模块"设置：已确认`RESTRICT_MOBILEIP`配置键实际上已死（未被任何代码引用）——它是已废弃的独立移动设备输出模块的遗留物。当前的移动端支持仅通过CSS实现（viewport meta标签+媒体查询断点），没有服务器端UA判断。维护者的决定：将"非PC"式UA判断方案留待未来有需要时再考虑，目前仅修复审查中发现的具体CSS问题——`.msgtree`（AA／串视图）在窄屏下缺少`overflow-x: auto`（仅在桌面宽度下生效，导致过长的AA行将整个页面向侧边推挤，而非在区块内滚动），`.postlists`（管理员帖子列表表格）则完全没有横向滚动处理。两处均已修复。
* （2026-07-25已实现）帖子正文中的`#话题标签`现会（在现有`AUTOLINK`设置控制下）自动转换为getlog（`m=g`）全文搜索链接，搜索范围以该帖子自身的日期为基准划定时间窗口——`OLDLOGSAVESW=1`（按月日志文件）时为该帖所在的月份，`OLDLOGSAVESW=0`（按日日志文件）时为包含发帖当天在内的最近7天。
* 新帖界面未显示表单
* 正确使用多字节函数与jcode
* 完善上传器缩略图JavaScript，使其能方便地支持其他Uploader类软件的实例
* （2026-07-18决定，维护者判断）首页发帖表单刻意不显示"发帖完成"确认界面（仅跟帖／回复会显示）。已审查并保持现状，非漏洞。
* （2026-07-18决定，维护者判断）不会在bbs.php中添加`Cache-Control: no-store`。发帖后返回时偶尔残留旧表单内容的问题，作为换取bfcache更快"返回"导航速度的权衡而被接受。
* （2026-07-19记录，下述2026-07-20的变更后依然适用）`ADMINKEY`（管理员发帖模式进入关键词）以明文存储，并通过简单字符串比较进行匹配，这与使用crypt/bcrypt哈希的`ADMINPOST`不同。已确认存在安全隐患；该功能暂时保持现状，但未来版本应考虑采用与`ADMINPOST`类似的哈希/比对方案。
* （2026-07-20已实现）管理员机密设置（`ADMINPOST`/`ADMINKEY`）已完全从conf.php中分离，移至一个不属于newbbs/发布模板、因而永远不会被install.php的conf合并流程触及的固定文件名文件（`local.php`）。其设置／修改不再通过conf.php或install.php进行，而是通过一个独立工具（初始名为`_setup.php`，首次使用时由运营者重命名）。原始设计讨论详见doc/admin-secrets-concept-2026-07-19-01.txt。

* （2026-08-01已修复，RC11）install.php的conf-review确认界面中，数学表达式形式的值（例如`MAXOLDLOGSIZE`的`4 * 1024 * 1024`）在写回时被作为带引号的字符串（`'1023998976'`）而非纯数字保存，导致运行时出现字符串与整数比较错误（日志大小检查误动作）。已通过在`ksphp_conf_apply_review()`中，于现有的纯数字检查基础上新增`$was_numeric_expr`检查（仅由数字/运算符/空白组成）来修复，使此类值以不加引号的形式写入。在本修复之前创建的现有conf.php文件，其受影响的键需要进行一次性手动修改（将带引号的值改为不加引号的整数或表达式）。
* （2026-08-01已修复，RC11）bbs.php中的`Func::html_escape()`，通过`str_replace("\015$", "", $value)`删除了帖子正文第二行起每行行首的`$`字符——这是其上方CR/LF标准化处理之前遗留下来的代码，也看不出有任何安全方面的依据。这导致帖子第二行及以后的LaTeX风格`$...$`定界符（参见上文latexrender.js）被破坏。已删除该行代码。
* （2026-08-01已实现，RC11）conf.php确认界面现会将各设置项自身的conf.php注释显示为帮助文字（详情参见上文`ksphp_conf_entry_comment_text()`条目）。
* （2026-08-01已修复，RC12）`BBSLINK`的值实际上是多行的HTML／文本块，但在conf.php确认界面中却被渲染为单行文本输入框。已新增`ksphp_conf_longtext_keys()`列表及`'longtext'`字段类型（显示／保存逻辑与现有的`'text'`类型完全相同，仅表单控件不同——使用`<textarea>`而非`<input type="text">`），并将`BBSLINK`注册到该类型下。
* （2026-08-01已实现，RC13）所有按浏览器区分的JS功能开关（现有的kaomoji.js・upthumb.js・imgthumb.js・vidembed.js，以及新增的longpostfilter.js・latexrender.js・treehide.js）均已整合进个人设置（"个人环境设置"，m=c）页面内新增的"JS设置"fieldset。设置项现改为通过新增的独立Cookie `ksphp_js`（JSON格式，90天有效期）保存，而非localStorage，与页面其他设置一同通过同一个"注册"按钮提交；单一的PHP定义表（bbs.php中的`ksphp_js_setting_defs()`）负责驱动Cookie的加载／保存／表单渲染，因此今后新增JS功能只需在该表中添加一行。原先页面顶部三个独立的开关（treehide/longpostfilter/latexrender）已移除；针对从RC10~RC12升级的用户，这三项会先优先采用已有的localStorage值一次，此后则以Cookie为准。ayashiibreaker.js（换行功能）按规格不设开关复选框（视为必需功能），但其行长度参数现已可在面板中配置；可配置的最大值由服务器端根据conf.php自身的`MAXMSGCOL`计算得出，因此永远不会超出服务器可接受的范围；由于`MAXMSGCOL`是基于`strlen()`的字节数限制，而换行功能按字符数计算，含日语的行使用约为设定值1/3的数值（禁则处理安全边距）。新增的9个模板键已添加完整的7语言翻译。
* 尚未开始：允许个人设置面板在客户端覆盖conf.php层级的"Gikoneko-to-issho"设置（即conf.php端可以启用它，但读者仍可自行将其关闭）。这原本是JS面板需求的一部分，但在专注于上述各JS文件的RC13阶段范围之外，暂未实现。

## 已知漏洞：
* （迁移说明，RC13及以后）RC10~RC12新增的三个按浏览器区分的JS功能（treehide、longpostfilter、latexrender）最初将开关状态存储在localStorage中。RC13将这些设置迁移至服务器Cookie（`ksphp_js`），出于迁移考虑，各脚本在存在已有localStorage值时仍会优先采用该值。因此，若某浏览器在RC10~RC12时期曾明确将某项功能关闭，在新的JS设置面板中将其打开也可能看似没有效果——因为旧的localStorage中的`0`占了上风。修复方法是每个浏览器一次性操作：在面板中将该功能关闭再重新开启，或删除旧的键（`ksphp_treehide_enabled`、`ksphp_longpost_enabled`、`ksphp_latex_enabled`）。全新安装以及从未经历过RC10~RC12的浏览器不受影响。
* （2026-08-01发现并修复，RC13）longpostfilter.js中每条帖子的"折りたたむ"（折叠）链接，无论是否真正超过行数阈值都会显示，原因是未折叠的渲染路径会无条件调用expand()并保持链接可见。已修复为仅在真正被折叠（即确实超过阈值）的帖子被手动重新展开后，才会显示折叠链接。
* （2026-08-01发现并修复，RC12-02）RC11的交接记录曾声称已在`ksphp_conf_apply_review()`中新增`$was_numeric_expr`检查，以在保存时使数学表达式形式的配置值（例如`MAXOLDLOGSIZE`的`4 * 1024 * 1024`）保持不加引号，但实际上出货代码（RC11与RC12均是）中从未真正存在该检查——仅保留了原有的纯数字检查。此问题是通过某实机站点的`MAXMSGSIZE`值（`'250*120*128*256*128'`，此前某次安装运行时被保存为带引号的字符串）在`procForm()`的内容长度比较中触发PHP非数值警告而发现的，该警告进而破坏了HTTP响应，在浏览器端表现为`ERR_CONTENT_DECODING_FAILED`。该检查现已真正实现。重要限制：这仅能防止今后安装／迁移中出现该漏洞——并**不会**自动修复已经保存为带引号字符串的conf.php值（确认表单只会原样重新提交现有的带引号值）。任何已经损坏的数学表达式键，都需要手动编辑conf.php，或在确认表单中重新输入并提交该值。
* 搜索日志时出现大量\&nbsp;
* （2026-08-01，在install.php conf-review测试过程中发现；同日修复，根本原因详见"待办事项"）`ksphp_conf_parse_entries()`的条目边界扫描器，在紧邻其前方存在包含形似带引号`'KEY' =>`字符串的注释掉示例时，会误判键名（例如注释掉的TMPL_MSG/TMPL_ENVLIST之后的HOSTNAME_POSTDENIED，以及各自注释掉示例之后的CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC）。已在全部四处调用点（`ksphp_conf_merge()`、`ksphp_conf_build_review()`、`ksphp_conf_apply_review()`、`ksphp_parse_module_array()`）中，通过在键名匹配前剥离开头的注释行进行修复；install.php端的防御性"raw"回退机制作为安全网继续保留，但实际使用中应不再会被触发。
* （2026-08-01已在qptns.com实机确认；同日修复——详情参见"待办事项"）install.php的conf-review确认界面曾错误地将ZIPDIR标记为必填项；实际上ZIPDIR为空是一个有效设置，表示"不创建zip日志"。已一并修复OLDLOGFILEDIR及CNTFILENAME的相同问题（同样的"留空＝功能禁用"模式，已通过各自的conf.php注释确认）。
* （2026-08-01已实现）按日／按月的过去日志轮换文件的"文件不存在，已新建"自动创建提示，现仅在真正的首次设置时显示（即OLDLOGFILEDIR内尚不存在其他相同扩展名的过去日志文件时）。在日／月边界处出现带日期的新文件、而已有过去文件存在的常规轮换场景中，该提示不再显示。刻意保持轻量实现：仅通过目录列表检查（OLDLOGFILEDIR内是否已存在其他过去日志文件），而非通过日历/日期计算来判断"是否为该周期的首篇帖子"。
* （2026-08-01发现并修复，RC14-02，通过qptns.com/test/上的实机测试）ayashiibreaker.js的换行目标值，在RC14初次修复（日语行为configuredLen-2）之后仍然过于紧凑：针对特定长测试行进行实机验证后，日语与ASCII行均上调至configuredLen-12。另外，ASCII（英语）单词边界换行还存在自身的漏洞：仅在已确定要追加某单词"之后"才判断是否超出阈值，且从不拆分长度超过目标值的单个单词（例如"ayashiibreaker.js"、"word-boundary"），因此无论边距如何设置，某一行都可能保持未被截断的状态。已重写为按行累积数组、对过长单词强制按字符拆分、并在追加前先检查候选长度的方式，使ASCII行现在必定遵守所配置的上限。
