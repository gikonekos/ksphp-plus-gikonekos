# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
An improved version of the PHP port of KuzuhaScript (くずはすくりぷと).
As of 2024/10/16, it only works with PHP8+.
Last legacy PHP (4.1.0–7.4) version: [https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)

This program is based on the 2005/04/01 modified version of KuzuhaScriptPHP (くずはすくりぷとPHP).

This program was originally translated to English by [Anonymous-san at Strange World@Heyuri.net](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555) and several anonymous developers from Heyuri have contributed since.

* [KuzuhaScriptPHP (mirror)](http://qptn.x.fc2.com/up/dauso0059.zip)
* [2005/04/01 modified version](http://qptn.x.fc2.com/up/dauso0073.zip)

## Maintainer information
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## Installation
1. Unzip the downloaded ZIP file
2. Open and configure conf.php
3. Upload the files to the server using an FTP client or similar (a dedicated directory is recommended)
4. Set permissions as described below
5. Access `_setup.php` in a browser (a standalone tool included in the package; not part of conf.php or install.php) and set the administrator password. On completion it renames itself — note the new URL, you'll need it to change the password later.
6. *(No longer needed — the admin password is written by `_setup.php` into its own file, not into conf.php.)*
7. Access bbs.php in a browser and confirm you can post
8. Access the log file URLs (bbs.log, log/, etc.) in a browser and confirm they are not publicly readable (hide them with .htaccess etc. if they are)

## Troubleshooting
### Upgrading an existing site (admin password migration)
Starting with RC8, the admin password (ADMINPOST/ADMINKEY) lives outside conf.php, in a fixed-name file `local.php` that is never overwritten by install.php. When install.php detects a non-empty ADMINPOST in the existing conf.php, it shows a migration form asking for your **old password** (verification) and a **new password**. ADMINKEY is carried over automatically.

- If the old password verifies, `local.php` is written and installation proceeds.
- If verification fails, **the entire installation is aborted** — nothing is installed or overwritten.
- If you forgot the old password, manually blank out `ADMINPOST` in the server's conf.php. install.php will then treat it as a fresh install, and you can set a new password via `_setup.php`.

## Recommended permissions
Incorrect permissions can cause problems and data leaks (IP addresses, remote hosts, etc.).

```
[File structure]
|-- bbs.cnt   600 (writable)    Participant list record file (empty text file)
|-- bbs.log   600 (writable)    Log file (empty text file)
|-- conf.php  644 (read-only)   Configuration
|-- bbs.php   644 (read-only)   Main board script
|-- readme.md                   This file
|-- vanish.js                   Word filtering script
|
+-- archive/  700 (writable)    ZIP archive storage
+-- count/    700 (writable)    Counter output
+-- log/      700 (writable)    Raw log storage
+-- sub/      755 (read-only)   Submodules
    |-- bbsadmin.php    644     Administration module
    |-- bbslog.php      644     Log viewer module
    |-- bbstree.php     644     Tree view module
    |-- phpzip.inc.php  644     ZIP creation library
```

If PHP runs as an Apache module, bbs.php can be read-only (644). If it runs as CGI, set bbs.php to 755 (executable).

## Reference
### bbs.php?m=* parameter meanings

| Parameter | Meaning |
| --- | --- |
| m=g | Message log search |
| m=ad | Administrator mode |
| m=tree | Tree view |
| m=p | Post / reload |
| m=c | Personal settings |
| m=f | Follow screen |
| m=t | Thread display |
| m=s | Search by user |
| m=u | Execute UNDO |

## History
### Cion (しおん) version
* 2003/01/21 Work began
* 2003/01/31 0.0.1alpha
* 2003/02/03 0.0.2alpha
* 2003/02/11 0.0.3alpha
* 2003/02/13 0.0.4alpha
* 2003/02/14 0.0.5alpha
* 2003/02/16 0.0.6alpha
* 2003/02/18 0.0.7alpha

### Unofficial
* 2005/04/01 0.0.8alpha (unofficial) — volunteer-modified release (mirror: http://www.freak.ne.jp/~lunatica/home/up/freak/dauso0073.zip)

### Unknown dates (Hirugatake (蛭ヶ岳) version)
* Fixed UI, easier to use on smartphones etc.
* Switched to UTF-8 (＠Links)
* Updated PHPZip to v1.2
* Various other fixes (not recorded)

### Unknown dates
* Minor coding style changes
* Fixed bug in follow-up posts
* Removed jcode-LE
* Fixed problem where user settings weren't reflected (?)
* Templates are no longer a concern
* Solved mysterious implementation of the func class (incomplete)
* Preparation for PHP7.x support

### 2018/10/12
* Renamed to "KuzuhaScriptPHP+" (くずはすくりぷとPHP+)
* Missing form data now invalidates the post correctly
* Minor UI changes

### 2018/11/18
* Applied Motoi(gikonekos)'s tree view corrections
* Built-in vanish.js

### 2019/11/02
* Removed EZweb (HDML) and imode views

### 2020/02/11
* Applied Motoi(gikonekos)'s tree view bugfixes

### 2020/03/15
* Separated counters with commas

### 2020/03/29
* Added Motoi(gikonekos)'s YouTube embedding function

### 2021/03/08
* Design changes (text boxes, etc.)
* conf.php: updated expressions and default values

### 2021/07/03
* Added Motoi(gikonekos)'s 2ch tripcode (20210625)
* Removed bbs.cgi

### 2021/07/27
* Fixed admin password leak when used together with a tripcode (Motoi(gikonekos))
* Name/Email/Title max character counts now configurable
* Moved description from bbs.php to readme.md

### 2022/05/06
* Moved bbs.php to index.php
* Minor UI fixes

### 2022/11/27
* sub/patTemplate.php: applied Motoi(gikonekos)'s fixes

### 2023/01/18
* Fixed a bug where the tree was not displayed correctly

### 2023/05/20
* Applied Motoi(gikonekos)'s admin path disclosure prevention (20210923)
* Applied Motoi(gikonekos)'s fixed-handle name password leak prevention (20210923)

### Unknown dates (Heyuri version)
* Wholly translated to English
* Added kaomoji buttons
* Changed line height to 1
* Commented out CSS browser line-break handling
* Added JavaScript to help users break lines
* Commented out Motoi(gikonekos)'s YouTube embedding; reimplemented as JS
* Added image thumbnailing JavaScript (default: Uploader@Heyuri only)

### 2024/10/16
* Migrated to PHP8
* Renamed index.php back to bbs.php
* Moved JS files to a separate directory

### 2025/04/29
* Fixed redirect not working after applying user settings
* Removed unused customization config keys from conf.php

### 2025/06/07
* Added IPv6 support
* Changed HTTP headers to allow embedding from anywhere

### 2025/09/09
* Fixed host bans

### 2025/11/08
* Added vertical-scroll CSS for long lines (PC only)
* Kaomoji buttons moved into a fieldset
* Fixed a bug where clicking User Settings submitted the form if it wasn't empty
* ayashiibreaker.js now glows to warn users about long lines

### 2025/11/09
* Complete internationalization; Japanese is now the primary language and English is optional
* Renamed back to KuzuhaScriptPHP+ from KuzuhaScriptPHP+EN

### 2026/03/10
* Admin key and tripcode can now be combined (Motoi(gikonekos))

### 2026/03/15
* Admin menu is now accessed via a top-page link
* Admin menu reworked to use sessions for security and convenience

### 2026/06/27
* Added filtering and mass-selection helpers to the post-deletion admin screen

### 2026/07/18 — 2026/07/19
* gikoneko.php / gikonekoadd.php: localized via standard `$MSG` mechanism; data file path bug fixed
* ayashiibreaker.js v0.4.0: Japanese line-breaking now uses kinsoku character-count rules
* bbs.php: log file now read line-by-line (Func::fgetline()), greatly reducing peak memory usage
* `GIKONEKO_TOISSHO` config key added (show/hide Gikoneko when no unread posts)
* install.php: atomic backup via rename(), rollback on failure, per-file error log, UI language switching (ja/en), path safety guards

### RC8 (2026/07/20)
* Admin secrets (ADMINPOST/ADMINKEY) moved out of conf.php into `local.php`; managed via standalone `_setup.php` tool
* install.php: admin password migration form when upgrading from a site with existing ADMINPOST

### RC9 (2026/07/25)
* Mobile display fixes; ZIP creation undefined-variable fix
* `#hashtag` in post body auto-converts to a date-scoped getlog search link

### RC10 (2026/08/01)
* install.php: conf.php adjustment/review screen with auto-merge, 7-language UI, per-file rollback
* Three opt-in JS features: LaTeX math rendering (latexrender.js), collapsible unread threads (treehide.js), long-post line filter (longpostfilter.js)

### RC11 (2026/08/01)
* install.php: conf-entry boundary parser root-cause fix (commented-out examples no longer mis-attributed as real keys)
* bbs.php: fixed `$` stripping that broke LaTeX delimiters on lines after the first

### RC12 (2026/08/01)
* BBSLINK now renders as a textarea in the conf review screen
* Numeric-expression config values (e.g. `4 * 1024 * 1024`) no longer saved as quoted strings

### RC13 (2026/08/01)
* All per-browser JS toggles integrated into the personal-settings (m=c) page as a single "JS設定" fieldset; settings stored in cookie `ksphp_js` instead of localStorage

### RC14 (2026/08/01)
* Three RC13 bug fixes: kaomoji fieldset margin, longpostfilter collapse-link always showing, ayashiibreaker target width
* ayashiibreaker: ASCII word-boundary wrapping rewritten; over-length words now force-split

### RC15 (2026/08/02)
* PHPStan Level 5 full review; three bugs fixed (bbsimage.php IMAGE_PREVIEW_RESIZE, bbstree.php thread-index O(n²), undefined variable in getdispmessage)
* conf.php review screen: per-key help text from conf.php comments; 7-language translation of all 98 CONF_HELP_* keys
* doc/ reorganized into per-language subdirectories

### RC16 (2026/08/03)
* Language selector added to main_upper (UI language switchable without the settings page)
* Personal-settings panel: client-side override for the conf.php-level `GIKONEKO_TOISSHO` setting
* Gikoneko (gikoneko.php / gikonekoadd.php): localized via `$MSG`

### RC17 (2026/08/03)
* Tree view: rewritten-quote display (changed lines highlighted in gold); tree sort order toggle (new/old first, saved in browser)
* LaTeX: fixed `$variable`-style tokens incorrectly consumed as math delimiters
* install.php: 7-language support extended to installer UI and all CONF_HELP_* entries

### RC18 (2026/08/07)
* bbs.php: log-language consistency fix — Reference line, self-reply tag, and day-of-week names are now always written to the log in the board's configured default language (LANGUAGE_FILE), regardless of the visitor's selected language (TDefault() / getdatestr_default() added)
* bbs.php: removed mbstring dependency in tripuse(); iconv() only (no functional change on qptns.com)
* install.php: keep/change option for admin password on version upgrade; all 7 languages
* install.php: skip unchanged files during version upgrade (SHA-256 comparison; avoids false negatives from line-ending differences between ZIP and server)
* bbs.php: reference line and self-reply tag now follow the visitor's language on display (log still stores the default language; display-only translation, no log format change)
* bbs.php + sub/*.php: reference-line stripping (tree view, log digest, admin, image BBS) is now language-aware instead of hardcoded to English "Reference: "

### RC18 post-release fixes (2026/08/08) — installer only, version string unchanged
* install.php: serialise multi-target install — replaced recursive runNextTarget() with processSingleTarget() + sequential Promise chain; interactive prompts (conf overrides, admin-password dialogs) now fire correctly per target
* install.php: conf review inlined into the log list; dynamic link via CGIURL; install-log save feature added
* install.php: step numbering (NN-S/T format), conf-skip reason display, per-target install header
* install.php: removed ksphp_migrate() call; data/.migrated marker now created directly per target_dir (fixes KSPHP_ROOT redefine issue on the second target)
* install.php: fixed undefined $target_dir (correct name: $parent_dir) in migration marker block — root cause of the comm error in multi-target installs
* template.html: lang selector in main_upper restored (had gone missing again)

For full details on any release, see `doc/changelog-2026-07-16-01.txt`.

## ToDo
* **Uploader thumbnail JS** — make upthumb.js easily configurable for Uploader software instances other than Uploader@Heyuri
* **New-post form not appearing** — intermittent; reproduction conditions unknown

## Known Bugs
* Large number of `&nbsp;` entities appear when searching logs
