# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
KuzuhaScript（くずはすくりぷと）PHP移植版的改进版本。
截至2024/10/16，仅可在PHP8及以上版本运行。
最后一个支持旧版PHP（4.1.0～7.4）的版本可在此处获取：[https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)

本程序基于2005/04/01改版的KuzuhaScriptPHP（くずはすくりぷとPHP）。

本程序最初由[Strange World@Heyuri.net的Anonymous-san](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555)翻译为英文，此后由Heyuri的多位匿名开发者持续维护。

* [KuzuhaScriptPHP（镜像）](http://qptn.x.fc2.com/up/dauso0059.zip)
* [2005/04/01改版](http://qptn.x.fc2.com/up/dauso0073.zip)

## 维护者信息
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## 安装步骤
1. 解压缩下载的ZIP文件
2. 打开并配置conf.php
3. 使用FTP客户端等工具将文件上传至服务器（建议创建专用目录）
4. 设置以下所述的权限
5. 在浏览器中访问`_setup.php`（套件内附的独立工具，非conf.php或install.php的一部分），设置管理员密码。完成后工具会自动重命名——请记下新的名称/URL。
6. *（现已不需要——管理员密码由`_setup.php`写入专用文件，不再写入conf.php）*
7. 在浏览器中访问bbs.php，确认可以发帖
8. 在浏览器中访问日志文件的URL（bbs.log、log/等），确认无法公开访问（若可访问，请使用.htaccess等方式限制）

## 故障排除
### 升级现有网站（管理员密码迁移）
自RC8起，管理员密码（ADMINPOST/ADMINKEY）存放在conf.php之外的固定名称文件`local.php`中，install.php不会覆写此文件。当install.php检测到现有conf.php中有非空的ADMINPOST时，会显示迁移表单，要求输入**旧密码**（身份验证）和**新密码**。ADMINKEY会自动继承。

- 旧密码验证成功后，`local.php`会被写入并继续安装。
- 验证失败时，**整个安装流程会中止**（不会安装任何文件）。
- 若真的忘记旧密码，请手动清空服务器上conf.php中的`ADMINPOST`值，install.php将以全新安装方式处理，之后可通过`_setup.php`设置新密码。

## 建议权限设置
权限设置错误可能导致问题和数据泄露（IP地址、远程主机等）。

```
[文件结构]
|-- bbs.cnt   600（可写入）  参与者列表记录文件（空白文本文件）
|-- bbs.log   600（可写入）  日志文件（空白文本文件）
|-- conf.php  644（只读）    配置文件
|-- bbs.php   644（只读）    留言板主程序
|-- readme.md               本文件
|-- vanish.js               词汇过滤脚本
|
+-- archive/  700（可写入）  ZIP存档存储目录
+-- count/    700（可写入）  计数器输出目录
+-- log/      700（可写入）  消息日志存储目录
+-- sub/      755（只读）    子模块
    |-- bbsadmin.php    644  管理模块
    |-- bbslog.php      644  日志查看模块
    |-- bbstree.php     644  树状显示模块
    |-- phpzip.inc.php  644  ZIP创建库
```

若PHP以Apache模块方式运行，bbs.php设置为644即可。若以CGI方式运行，需设置为755。

## 参考
### bbs.php?m=* 参数说明

| 参数 | 说明 |
| --- | --- |
| m=g | 消息日志搜索 |
| m=ad | 管理员模式 |
| m=tree | 树状显示 |
| m=p | 发帖／重新加载 |
| m=c | 个人设置 |
| m=f | 回复画面 |
| m=t | 讨论串显示 |
| m=s | 按用户搜索 |
| m=u | 执行UNDO |

## 沿革
（早期版本详情请参阅英文版README）

### RC8 (2026/07/20)
* 管理员密码（ADMINPOST/ADMINKEY）移至conf.php外的`local.php`，由独立工具`_setup.php`管理
* install.php：升级时若存在ADMINPOST则显示密码迁移表单

### RC9 (2026/07/25)
* 移动端显示修正、ZIP创建未定义变量修正
* 帖子内`#标签`自动转换为日期范围getlog搜索链接

### RC10 (2026/08/01)
* install.php：conf.php调整・确认画面（自动合并、7语言支持、逐文件回滚）
* 可选JS功能3项：LaTeX数式渲染（latexrender.js）、未读话题折叠（treehide.js）、长文行数过滤（longpostfilter.js）

### RC11 (2026/08/01)
* install.php：conf.php条目边界解析器根本修正
* bbs.php：修正LaTeX `$...$` 分隔符在第二行以后失效的问题

### RC12 (2026/08/01)
* BBSLINK确认画面改为textarea
* 数值运算式格式的配置值不再被保存为字符串

### RC13 (2026/08/01)
* 浏览器单位的JS切换整合至个人设置（m=c）的「JS设置」fieldset，从localStorage改为cookie存储

### RC14 (2026/08/01)
* RC13错误修正3项：表情符号fieldset间距、longpostfilter折叠链接、ayashiibreaker目标宽度
* ayashiibreaker：ASCII单词边界换行重新实现，过长单词现在必定会被分割

### RC15 (2026/08/02)
* PHPStan Level 5全面审查・错误修正3项
* conf.php确认画面：显示各设置的说明文字，所有98个CONF_HELP_*键支持7种语言
* doc/整理为各语言子目录结构

### RC16 (2026/08/03)
* main_upper新增语言切换下拉菜单
* 个人设置面板：可在客户端覆盖conf.php层级的`GIKONEKO_TOISSHO`设置
* gikoneko.php / gikonekoadd.php多语言化

### RC17 (2026/08/03)
* 树状显示：改写引用行以金色标示，新增排序切换（新→旧/旧→新，浏览器保存）
* LaTeX：修正`$变量`形式的符号被误认为数式分隔符的问题
* install.php：安装程序UI・CONF_HELP_*条目的7语言支持

### RC18 (2026/08/07)
* bbs.php：日志写入语言一致性修正——参考行、自回复标签、星期名称现在一律以留言板默认语言（LANGUAGE_FILE）写入日志，不受访客选择语言影响（新增TDefault() / getdatestr_default()）
* bbs.php：删除tripuse()中的mbstring依赖，仅使用iconv()（qptns.com行为不变）
* install.php：版本升级时新增管理员密码保留/变更选择UI（全7种语言）
* install.php：版本升级时跳过内容相同的文件（SHA-256哈希比较，不受换行符差异影响）
* bbs.php：参考行和自我回复标签在显示时跟随访问者语言（日志仍以默认语言保存，仅显示时转换，日志格式不变）
* bbs.php・sub/*.php：参考行的删除处理（树状显示・日志摘要・管理画面・图片BBS）从英语硬编码改为多语言支持

### RC18 后续修正（2026/08/08）——仅安装程序，版本号不变
* install.php：多目标安装串行化——将递归的 runNextTarget() 替换为 processSingleTarget()＋顺序 Promise 链；conf 确认、管理员密码输入等交互现在按目标逐一正确执行
* install.php：conf 确认表单内联至日志列表；通过 CGIURL 生成动态链接；新增安装日志保存功能
* install.php：步骤编号（NN-S/T 格式）显示、conf 跳过原因显示、按目标显示安装标题
* install.php：废除 ksphp_migrate() 调用，改为直接在每个目标目录内创建 data/.migrated 标记（解决 KSPHP_ROOT 无法重复定义的问题）
* install.php：修复 ksphp_install_run() 内未定义的 $target_dir（正确名称为 $parent_dir）——多目标安装通信错误的根本原因
* template.html：再次恢复 main_upper 中缺失的语言选择器

详细内容请参阅 `doc/changelog-2026-07-16-01.txt`。

### RC19 (2026/08/12)
* RTL 第一阶段；添加阿拉伯语语言文件（language/arabic.txt，298个键）

### RC20 (2026/08/15)
* 添加阿拉伯语安装器UI文件；以两套体系添加7种语言（西班牙语·法语·德语·印度尼西亚语·越南语·他加禄语·印地语）。共15种语言
* 文档文件名稳定化；删除install/index.html

### RC21 (2026/08/17)
* NG哈希过滤器（SHA-256、部分匹配、星号替换、确认对话框）
* 哈希前对全角英数字进行半角正规化

详细内容请参阅 `doc/changelog.txt`。

## 待办事项
* **上传器缩略图JS** — 使upthumb.js易于支持Uploader@Heyuri以外的Uploader实例
* **新发帖画面不显示表单** — 间歇性发生，重现条件不明
* **在实际浏览器中确认RTL布局** — 逻辑属性和`dir`属性已经导入，但实际显示尚未确认。由于`.msgtree`使用`white-space: pre`，树形显示最容易出现错乱。发帖表单、页眉导航行以及引用和帖子编号的位置也需要检查。
* **安装程序的阿拉伯语文件** — 论坛本体的`language/arabic.txt`已完成；`install/language/`是247个键的独立体系，尚无阿拉伯语版本。安装程序通过扫描目录构建语言列表，因此阿拉伯语目前只是不会出现在那里。
* **另外七个语言文件** — 西班牙语、法语、德语、印尼语、越南语、他加禄语和印地语。它们都已预先登记在同样的表中，仅剩`.txt`文件本体。
* **install.php的自动重命名** — `_setup.php`在首次使用后会自动改名，而`install.php`没有该机制。目前仅有删除步骤的文档说明。

## 已知问题
* 搜索日志时出现大量`&nbsp;`
