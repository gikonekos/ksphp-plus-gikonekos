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
  (default ON), full 7-language translations.
* (Implemented 2026-08-01) Follow-up UI fixes requested after live
  testing on qptns.com: (1) all former checkboxes (boolean 0/1 keys)
  now render as a 2-option radio group instead, for consistency with
  the existing 3-option radio keys and to address "checkbox was
  confusing" feedback; (2) ZIPDIR, OLDLOGFILEDIR, and CNTFILENAME are
  no longer marked required in the review screen -- conf.php's own
  comments document blank as a valid "feature disabled" state for
  these three (verified by reading each manual-path key's actual
  comment text; the other manual-path keys -- LOGFILENAME, COUNTFILE,
  GIKONEKO_KOTOBA_FILE, UPLOADDIR, UPLOADIDFILE -- have no such
  comment and remain required).
* (Fixed 2026-08-01, root cause) `ksphp_conf_parse_entries()`
  entry-boundary parser bug (see Known Bugs for the original report):
  the key-extraction step across `ksphp_conf_merge()`,
  `ksphp_conf_build_review()`, `ksphp_conf_apply_review()`, and
  `ksphp_parse_module_array()` now strips an entry's *leading*
  comment lines (via a new `ksphp_conf_entry_split_lead_comments()`
  helper) before running the key-name regex, so a commented-out
  example containing its own `'KEY' => ...` no longer gets
  mis-attributed as the following real key. The original comment
  text is preserved verbatim in the written-out conf.php (only the
  internal key-matching input is affected, never the output). The
  install.php-side defensive fallback (forcing such fields to
  uneditable "raw" display) is superseded by this fix but left in
  place as a safety net. Verified against the reported
  HOSTNAME_POSTDENIED/TMPL_MSG/TMPL_ENVLIST case, a
  CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC sequence, and a HANDLENAMES-style
  nested-array entry (regression check for the outer-key detection
  the non-greedy match relies on).
* (Implemented 2026-08-01) conf.php review screen now shows each
  setting's own comment text (conf.php already carries paired
  Japanese/English comments per key) as a help line under the key
  name in the review table, via a new
  `ksphp_conf_entry_comment_text()` helper. Decorative section-header
  dividers (`#---- ... ----`) and translator-only notes (`## TL
  note: ...`) are filtered out. Deliberately scoped light: the text
  shown is the conf.php source comment as-is (Japanese + English),
  not translated into the install UI's other 5 languages -- relies
  on the reader's browser translation feature for those. Full
  per-key translation into all 7 install-UI languages (~98 keys) was
  discussed and deferred as a separate, heavier future task (see
  further down this list).
* Not yet started: full 7-language translation of each conf.php key's
  help text (currently Japanese/English source text only, see above).
  Scope is roughly 98 keys; would need new `install/language/*.txt`
  keys (e.g. `CONF_HELP_<KEY>`) for Korean, Portuguese, Turkish,
  zh-hans, zh-hant.
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

* (Fixed 2026-08-01, RC11) install.php's conf-review numeric-expression
  values (e.g. `MAXOLDLOGSIZE`'s `4 * 1024 * 1024`) were written back
  out as quoted strings (`'1023998976'`) instead of bare numbers,
  causing string-vs-int comparison bugs at runtime (log-size checks
  firing incorrectly). Fixed by adding a `$was_numeric_expr` check
  (digit/operator/whitespace-only) alongside the existing bare-number
  check in `ksphp_conf_apply_review()`, so such values are written
  unquoted. Existing conf.php files created before this fix need a
  one-time manual edit to their affected keys (change the quoted
  value to a bare integer or expression).
* (Fixed 2026-08-01, RC11) `Func::html_escape()` in bbs.php stripped
  the `$` character at the start of any post-body line after the
  first, via `str_replace("\015$", "", $value)` -- a leftover from
  before the CR/LF normalization above it, with no discernible
  security rationale. This broke LaTeX-style `$...$` delimiters
  (see latexrender.js above) on line 2 and beyond of a post. Line
  removed.
* (Implemented 2026-08-01, RC11) conf.php review screen now shows
  each setting's own conf.php comment as help text (see the
  `ksphp_conf_entry_comment_text()` entry above for detail).
* (Fixed 2026-08-01, RC12) `BBSLINK` rendered as a single-line text
  input in the conf.php review screen despite holding a multi-line
  HTML/text block as its value. Added a new
  `ksphp_conf_longtext_keys()` list and `'longtext'` field type
  (display/save logic identical to the existing `'text'` type; only
  the form widget differs -- a `<textarea>` instead of
  `<input type="text">`) and registered `BBSLINK` under it.
* Not yet started: integrate all per-browser JS feature toggles
  (existing -- kaomoji.js, ayashiibreaker.js, upthumb.js, imgthumb.js,
  vidembed.js -- and new -- longpostfilter.js, latexrender.js,
  treehide.js) into the existing personal-settings panel
  (個人用環境設定 in sub/template.html), instead of the three newest
  toggles floating independently at the top of the page as they do
  now. Design points from maintainer: each JS individually
  on/off-able via checkbox except ayashiibreaker.js (line breaker,
  mandatory -- cannot be disabled, but its parameters should stay
  configurable from the panel); allow overriding the conf.php-level
  擬古猫といっしょ setting from this panel too (conf.php can enable
  it, reader can still turn it off client-side); settings continue
  to live in localStorage, same as the current per-feature toggles;
  needs full 7-language translation additions to
  `newbbs/language/*.txt`.

## Known Bugs:
* Large number of \&nbsp; appearances when searching logs
* (Found 2026-08-01 during install.php conf-review testing; fixed
  2026-08-01, root cause -- see ToDo) ksphp_conf_parse_entries()'s
  entry-boundary scanner mis-attributed a key when it was immediately
  preceded by a commented-out example containing a quoted 'KEY' =>
  -looking string (e.g. HOSTNAME_POSTDENIED after commented
  TMPL_MSG/TMPL_ENVLIST, CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC after their
  own commented examples). Fixed by stripping leading comment lines
  before key-name matching in all four call sites
  (ksphp_conf_merge(), ksphp_conf_build_review(),
  ksphp_conf_apply_review(), ksphp_parse_module_array()); the
  install.php-side defensive "raw" fallback for such entries remains
  in place as a safety net but should no longer trigger in practice.
* (Confirmed live on qptns.com 2026-08-01; fixed 2026-08-01 -- see
  ToDo) install.php's conf-review screen wrongly marked ZIPDIR as a
  required field; an empty ZIPDIR is actually a valid setting meaning
  "don't create a zip log". Fixed, along with the same issue on
  OLDLOGFILEDIR and CNTFILENAME (same "blank = feature disabled"
  pattern, confirmed via their own conf.php comments).
* (Implemented 2026-08-01) The "file didn't exist, so it was
  created" auto-create notice for the daily/monthly old-log rotation
  file now only shows on a genuine first-time setup (no other
  same-extension old-log file present yet in OLDLOGFILEDIR). Routine
  rotation -- a new dated file appearing at each day/month boundary
  while past files already exist -- no longer triggers the notice.
  Deliberately scoped light: a directory-listing check (does any
  other old-log file already exist) rather than a calendar/date
  calculation of "is this the first post of the period".



