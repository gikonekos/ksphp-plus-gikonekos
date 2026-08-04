# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
An improved version of the PHP port of KuzuhaScript (くずはすくりぷと).
As of 2024/10/16, it only works with PHP8+
Last legacy PHP (from 4.1.0 to 7.4) version can be found here: [https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)


This program is based on the 2005/04/01 modified version of KuzuhaScriptPHP (くずはすくりぷとPHP).

This program has originally been translated to English by [Anonymous-san at Strange World@Heyuri.net](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555) and several anonymous developers from Heyuri have contributed to it since.


* [KuzuhaScriptPHP (mirror)](http://qptn.x.fc2.com/up/dauso0059.zip)  
* [2005/04/01 modified version](http://qptn.x.fc2.com/up/dauso0073.zip)

## Maintainer information
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## Installation process (for reference only)
1. Unzip the downloaded ZIP file
2. Open and configure conf.php
3. Upload the files to the server using an FTP client or similar (it's a good idea to create a dedicated directory so that it doesn't get mixed up with other files)
4. Set the permissions as described in readme.md
5. Open a web browser, access `_setup.php` (a standalone tool included in the package; it is not part of conf.php or install.php), and set the administrator password there. On completion, the tool renames itself to a name of your choosing (a hard-to-guess default is suggested) — make a note of the new name/URL, since you'll need it to change the password later.
6. (No longer needed — the admin password is written directly by `_setup.php` into its own file, not into conf.php.)
7. Open your web browser, go to bbs.php, and see if you can post
8. Access the URL where the log files (bbs.log, files inside log/, etc.) are located using a web browser, and check if you can see it (if you can see it, please hide it with .htaccess, etc.)

## Troubleshooting
### Upgrading an existing site (admin password migration)
Starting with RC8, the admin password (ADMINPOST/ADMINKEY) lives outside conf.php, in a fixed-name file called `local.php` that is not part of this template and is never overwritten by install.php. When install.php detects that the site you're upgrading already has a non-empty ADMINPOST in its existing conf.php, it will show a migration form asking for your **old password** (to verify it's really you) and a **new password** to set. The admin-mode keyword (ADMINKEY) is carried over automatically — there's no separate field for it.

- If the old password is verified successfully, `local.php` is written with the new password and installation proceeds normally.
- If verification fails, **the entire installation is aborted** (no files are installed, nothing is backed up or overwritten) to prevent someone else from hijacking the admin account of a site they don't control.
- If you genuinely forgot the old password yourself, open the existing conf.php on the server and manually blank out the `ADMINPOST` value (set it to an empty string). This makes install.php treat the site as a fresh install, letting you set a brand-new password through `_setup.php` afterward, the same as a new installation.

## Recommended permission settings
Incorrect permissions can cause problems and data leaks (such as a post's IP address or remote host), so please make sure that they are set correctly.

```
[File structure]
|-- bbs.cnt   600 (writable)      Participant list record file (empty text file)
|-- bbs.log   600 (writable)      Log file (empty text file)
|-- conf.php  644 (read-only)     For configuration
|-- bbs.php 644 (read-only)     Main bulletin board script
|-- readme.md                     Instructions (this file)
|
|-- vanish.js                     Script for word filtering
|
|
+-- archive/  700 (writable)      ZIP archive storage directory
+-- count/    700 (writable)      Counter output directory
+-- log/      700 (writable)      Message log files (raw logs) storage directory
+-- sub/      755 (read-only)     Submodule storage directory
    |
    |-- bbsadmin.php    644 (read-only)     Administration module
    |-- bbslog.php      644 (read-only)     Log viewer module
    |-- bbstree.php     644 (read-only)     Tree view module
    |-- phpzip.inc.php  644 (read-only)     ZIP file creation library
```

If PHP runs as an Apache module, bbs.php will run as read-only, 
but if it runs as CGI, bbs.php needs to be set to 755 (executable).

## Memo:
### List of bbs.php?m=* meanings

| Parameter | Meaning |
| --- | --- |
| m=g | Message log search |
| m=ad | Administrator mode |
| m=tree | Tree view |
| m=p | Post/reload |
| m=c | Settings |
| m=f |Follow screen |
| m=t |Thread display |
| m=s |Search by user |
| m=u |Execute UNDO |

## History
### Cion (しおん) version
* 2003/01/21 work began
* 2003/01/31 0.0.1alpha
* 2003/02/03 0.0.2alpha
* 2003/02/11 0.0.3alpha
* 2003/02/13 0.0.4alpha
* 2003/02/14 0.0.5alpha
* 2003/02/16 0.0.6alpha
* 2003/02/18 0.0.7alpha

### Unofficial
* 2005/04/01 0.0.8alpha(Unofficial) A modified version released by a volunteer (Mirror: http://www.freak.ne.jp/~lunatica/home/up/freak/dauso0073.zip)

### Unknown dates (Hirugatake (蛭ヶ岳) version)
* Fixed UI, easier to use on smartphones etc.
* Switched to UTF-8 (＠Links)
* Update PHPZip to v1.2
* Various other fixes (not recorded)

### Unknown dates
* Slight change in coding style
* Fixed bug in follow-up posts
* Removed jcode-LE
* Fixed problem where user settings weren't reflected(?)
* Templates are no longer a concern
* Solved mysterious implementation of the func class (incomplete)
* Preperation for PHP7.x support

### 2018/10/12
* Changed name to "KuzuhaScriptPHP+"(くずはすくりぷとPHP+)
* Missing form data invalidated due to faulty checking
* Minor UI changes

### 2018/11/18
* Applied Gikoneko(擬古猫)'s tree view corrections
* Built-in vanish.js

### 2019/11/02
* Removed EZweb view (HDML)
* Removed imode view

### 2019/11/02
* Removed EZweb view (HDML)
* Removed imode view

### 2020/02/11
* Applied Gikoneko(擬古猫)'s tree view bugfixes

### 2020/03/15
* Seperated counters with commas

### 2020/03/29
* Added Gikoneko(擬古猫)'s YouTube embedding function

### 2021/03/08
* Design changes (text boxes, etc.)
* conf.php (changed expressions and default values)

### 2021/07/03
* Added Gikoneko(擬古猫)'s 2chtrip(20210625)
* Removed bbs.cgi

### 2021/07/27
* Fixed an issue where the admin password leaked when using both admin password and trip (Gikoneko(擬古猫))
* Maximum number of characters for Name, Email, and Title can now be set
* Moved description in bbs.php to readme.md

### 2022/05/06
* Moved bbs.php to index.php
* Minor UI fixes

### 20221127
* sub/patTemplate.php: Applied Gikoneko(擬古猫)'s fixes

### 20230118
* Fixed a bug where the tree was not displayed correctly

### 20230520
* Applied Gikoneko's admin path disclosure prevention (20210923)
* Applied Gikoneko's fixed handle name password prevention (20210923)

### Unknown dates (Heyuri version)
* Wholly translated to English
* Added kaomoji buttons
* Changed line height to 1
* Commented out the CSS for browsers to break lines
* Added a javascript for making it easy for users to break lines
* Commented out Gikoneko(擬古猫)'s YouTube embedding function
* Implemented a javascript for YouTube embedding
* Added Javascript for thumbnailing images, by default it's set to only work for Uploader@Heyuri

### 2024/10/16
* Migrated to PHP8
* Renamed index.php back to bbs.php
* Moved JS files to a separate directory

### 2025/04/29
* Fixed redirect not working after applying user settings
* Removed unused configs for customization off conf.php

### 2025/06/07
* Added support for IPv6 IPs
* Changed http header settings to allow embedding from anywhere

### 2025/09/09
* Fixed host bans

### 2025/11/08
* Added vertical scroller CSS for long lines (PC-only)
* Modified Kaomoji buttons to a fieldset
* Fixed a bug where clicking User Settings submitted the post if the form wasn't empty
* Made the ayashii breaker warn the user by glowing for long line posts

### 2025/11/09
* Added complete internationalization for Japanese, English being now optional
* Renamed software back to KuzuhaScriptPHP+ from KuzuhaScriptPHP+EN

### 2026/03/10
* Administrator keys can now be combined with a tripcode, implemented by gikonekos(擬古猫)

### 2026/03/15
* The way to enter administration menu is changed to a link at top
* Administrator menu is reworked, it was also changed to use sessions for security and convenience

### 2026/06/27
* Added filtering and mass-selection helpers to the post deletion admin screen

### 2026/07/18
* gikoneko.php / gikonekoadd.php UI text is now localized through the
  standard `$MSG` mechanism (22 new keys added to language/*.txt)
* gikoneko.php: the fortune data file is now auto-created when missing,
  so `file()` no longer emits a raw PHP warning into the page output
* gikonekoadd.php: fixed a data-file path mismatch (`../cgi-bin/...`)
  that caused newly taught words to never reach gikoneko.php's fortune
  pool; both scripts now reference the same root-level data file
* ayashiibreaker.js updated to v0.4.0: Japanese lines are now
  line-broken by character count with kinsoku shori (禁則処理) rules,
  instead of relying on space-delimited word wrapping

### 2026/07/19
* bbs.php: the main board display (`getdispmessage()`) and the current-log
  branch of `msgsearchlist()` now stream the log file line-by-line
  (`Func::fgetline()`) instead of loading the whole file into memory via
  `file()`. Peak memory for a normal page view no longer scales with
  `LOGSAVE` / log file size (verified: ~131MB -> ~2MB peak on a
  102,300-line / ~58MB log; a simulated 100-concurrent-request traffic
  spike no longer triggers OOM kills, unlike the previous implementation)
* bbs.php: removed the long-commented-out YouTube embedding code
  (superseded by ytthumb.js since 2024/10/16)
* bbs.php / conf.php: whether "Gikoneko-to-issho" is shown when there are
  no unread posts is now configurable via `GIKONEKO_TOISSHO` (1=on,
  0=off, default 1); disabling it falls back to the previous plain
  "no unread posts" message
* install.php: added a text-input "add a new install destination" flow
  for folders not found by the nearby-scan (with path-traversal
  validation and a confirmation step before use)
* install.php: added Japanese/English UI language switching (default
  Japanese), including translated install-log messages
* install.php: added a safety guard rejecting installs targeting the
  filesystem root, overly shallow paths, or the install/ folder itself
* install.php: existing files are now moved to backup via `rename()`
  (atomic) instead of `copy()`, per-file backup/rename failures now skip
  just that file instead of aborting the whole run, a failed write of
  the new file automatically rolls back the moved-aside original, and
  failures are additionally recorded to
  `install/backup/install-errors-YYYY-MM-DD.txt` for later review

## ToDo:
* (Implemented 2026-08-01) install.php: a conf.php "adjustment" step
  during migration -- checkbox/radio/list/text fields pre-filled from
  the automatic merge, required-field highlighting, server-side
  validation with per-file rollback, opt-out personal toggle
  (default ON), full 7-language translations. Follow-up items not yet
  done, requested after live testing on qptns.com: (1) convert all
  checkboxes to radio buttons (found confusing in live use), (2)
  ZIPDIR (and similar manual-path keys where blank is a valid
  "feature disabled" state) is currently wrongly marked required --
  needs fixing, (3) root-cause fix for the
  ksphp_conf_parse_entries() entry-boundary parser bug found during
  testing (see Known Bugs) -- currently only defended against, not
  fixed. This follow-up work is planned to ship as a future revision.
* (Implemented 2026-08-01, client-side JS) Community feature requests
  raised on the board -- LaTeX math rendering (`$E=mc^2$` style,
  newbbs/js/latexrender.js, loads KaTeX from a CDN only when the
  individual reader opts in), a "collapse/delete unread thread"
  control (newbbs/js/treehide.js, restorable any time), and a
  long-post line-count filter (newbbs/js/longpostfilter.js,
  adjustable threshold) -- all three implemented as personal
  (per-browser, localStorage-based) opt-in settings, default OFF, no
  server-side involvement. A separate request to port the NG-word
  matcher to WebAssembly for speed remains deferred: maintainer will
  prototype and benchmark it first, and only implement if it turns
  out to meaningfully help (suspects the real bottleneck is NG-word
  list size, not the matching algorithm).
* (Reviewed 2026-07-25, maintainer decision) Old "mobile module" setting:
  confirmed the `RESTRICT_MOBILEIP` config key is dead/unused (no code
  references it anywhere) -- it's a leftover from a discontinued
  separate mobile-device output module. Current mobile support is
  CSS-only (viewport meta + media query breakpoints), no server-side
  UA detection. Maintainer decided: keep the "not-PC" UA-detection
  approach in mind for a future need, but for now just fix concrete
  CSS gaps found during review -- `.msgtree` (AA/thread view) was
  missing `overflow-x: auto` on narrow screens (present only at
  desktop widths, so long AA lines pushed the whole page sideways
  instead of scrolling within the block) and `.postlists` (admin
  post-list table) had no horizontal-scroll handling at all. Both
  fixed.
* (Implemented 2026-07-25) `#hashtag` in a post body is now
  auto-converted (governed by the existing `AUTOLINK` setting) into a
  getlog (`m=g`) full-text search link, scoped to a date window
  anchored on the post's own date: the post's own month when
  `OLDLOGSAVESW=1` (monthly log files), or the 7 days up to and
  including the post date when `OLDLOGSAVESW=0` (daily log files).
* Form does not appear on the new post screen
* Proper use of multi-byte functions and jcode
* Have uploader thumbnailing javascript to easily support other instances of Uploader softwares
* (Decided 2026-07-18, maintainer judgment call) Top-page post form
  intentionally does NOT show the "Post complete" confirmation screen
  (only follow-up/reply posts show it). Reviewed and kept as-is; not a
  bug.
* (Decided 2026-07-18, maintainer judgment call) `Cache-Control:
  no-store` will NOT be added to bbs.php. The occasional stale form
  content after posting then navigating back is accepted as a
  trade-off in favor of bfcache's faster "back" navigation.
* (Noted 2026-07-19; still applies after the 2026-07-20 change below)
  `ADMINKEY` (admin post mode entry keyword) is stored in plaintext
  and matched via a plain string comparison, unlike `ADMINPOST` which
  is a crypt/bcrypt hash. Security concern acknowledged; keeping the
  feature as-is for now, but a future version should consider
  hashing/comparing it the same way as `ADMINPOST`.
* (Implemented 2026-07-20) Admin secrets (`ADMINPOST`/`ADMINKEY`) have
  been moved out of conf.php entirely, into a fixed-name file
  (`local.php`) that is not part of the newbbs/ distribution template
  and is therefore never touched by install.php's conf-merge process.
  They are set/changed via a standalone tool (initially named
  `_setup.php`, renamed by the operator on first use) instead of
  through conf.php or install.php. See
  doc/admin-secrets-concept-2026-07-19-01.txt for the original design
  discussion.

## Known Bugs:
* Large number of \&nbsp; appearances when searching logs
* (Found 2026-08-01 during install.php conf-review testing)
  ksphp_conf_parse_entries()'s entry-boundary scanner mis-attributes
  a key when it's immediately preceded by a commented-out example
  containing a quoted 'KEY' => -looking string (e.g.
  HOSTNAME_POSTDENIED after commented TMPL_MSG/TMPL_ENVLIST,
  CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC after their own commented
  examples) -- the wrong key ends up "owning" a blob that includes
  the real key's array/value. Currently only defended against in the
  new conf-review screen (such entries are excluded from editing,
  not fixed at the root); the underlying automatic conf-merge has
  carried this latent quirk since before this session. Root-cause fix
  planned for a future revision.
* (Confirmed live on qptns.com 2026-08-01) install.php's conf-review
  screen wrongly marks ZIPDIR as a required field; an empty ZIPDIR is
  actually a valid setting meaning "don't create a zip log". Needs
  fixing.
* ZIPLOG's "file didn't exist, so it was created" notice shows on
  every first post of a new day/month (routine automatic log
  rotation), which surprises viewers -- it should only show for
  genuinely unexpected/non-routine creation.



