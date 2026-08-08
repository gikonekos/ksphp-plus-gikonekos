# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
KuzuhaScript（くずはすくりぷと）의 PHP 이식판 개선 버전입니다.
2024/10/16 기준, PHP8 이상에서만 동작합니다.
레거시 PHP（4.1.0～7.4）를 지원하는 마지막 버전：[https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)

이 프로그램은 2005/04/01 개조판 KuzuhaScriptPHP（くずはすくりぷとPHP）를 기반으로 합니다.

이 프로그램은 [Strange World@Heyuri.net의 Anonymous-san](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555)이 최초로 영문 번역하였으며, 이후 Heyuri의 여러 익명 개발자들이 기여해 왔습니다.

* [KuzuhaScriptPHP（미러）](http://qptn.x.fc2.com/up/dauso0059.zip)
* [2005/04/01 개조판](http://qptn.x.fc2.com/up/dauso0073.zip)

## 메인테이너 정보
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## 설치 방법
1. 다운로드한 ZIP 파일 압축 해제
2. conf.php를 열어 설정
3. FTP 클라이언트 등으로 서버에 파일 업로드（다른 파일과 섞이지 않도록 전용 디렉터리 생성 권장）
4. 아래 권한 설정
5. 브라우저에서 `_setup.php`（패키지에 포함된 독립 도구; conf.php나 install.php의 일부가 아님）에 접속하여 관리자 비밀번호 설정. 완료 시 도구는 자동으로 이름이 변경됨 — 새 이름/URL을 메모해 두세요.
6. *（더 이상 필요 없음 — 관리자 비밀번호는 `_setup.php`가 전용 파일에 직접 씁니다）*
7. 브라우저에서 bbs.php에 접속하여 게시 가능 여부 확인
8. 로그 파일의 URL（bbs.log, log/ 등）에 브라우저로 접속하여 공개 접근 여부 확인（공개되어 있다면 .htaccess 등으로 차단）

## 문제 해결
### 기존 사이트 업그레이드（관리자 비밀번호 마이그레이션）
RC8부터 관리자 비밀번호（ADMINPOST/ADMINKEY）는 conf.php 외부의 고정 파일 `local.php`에 저장되며, install.php는 이 파일을 덮어쓰지 않습니다. install.php가 기존 conf.php에서 비어있지 않은 ADMINPOST를 감지하면, **이전 비밀번호**（본인 확인）와 **새 비밀번호**를 입력하는 마이그레이션 폼을 표시합니다. ADMINKEY는 자동으로 인계됩니다.

- 이전 비밀번호 확인 성공 시, `local.php`가 작성되고 설치가 계속됩니다.
- 확인 실패 시, **전체 설치가 중단됩니다**（파일이 일절 설치되지 않음）.
- 이전 비밀번호를 잊어버린 경우, 서버의 기존 conf.php에서 `ADMINPOST` 값을 직접 빈 문자열로 만드세요. install.php가 신규 설치로 처리하며, 이후 `_setup.php`를 통해 새 비밀번호를 설정할 수 있습니다.

## 권한 설정 권장값
권한 설정이 잘못되면 문제 및 데이터 유출（IP 주소, 원격 호스트 등）이 발생할 수 있습니다.

```
[파일 구성]
|-- bbs.cnt   600（쓰기 가능）  참여자 목록 기록 파일（빈 텍스트 파일）
|-- bbs.log   600（쓰기 가능）  로그 파일（빈 텍스트 파일）
|-- conf.php  644（읽기 전용）  설정 파일
|-- bbs.php   644（읽기 전용）  게시판 본체 스크립트
|-- readme.md                   이 파일
|-- vanish.js                   단어 필터링 스크립트
|
+-- archive/  700（쓰기 가능）  ZIP 아카이브 저장 디렉터리
+-- count/    700（쓰기 가능）  카운터 출력 디렉터리
+-- log/      700（쓰기 가능）  메시지 로그 저장 디렉터리
+-- sub/      755（읽기 전용）  서브모듈
    |-- bbsadmin.php    644     관리 모듈
    |-- bbslog.php      644     로그 뷰어 모듈
    |-- bbstree.php     644     트리 표시 모듈
    |-- phpzip.inc.php  644     ZIP 생성 라이브러리
```

PHP가 Apache 모듈로 동작하는 경우 bbs.php는 644로 설정해도 됩니다. CGI로 동작하는 경우 755로 설정하세요.

## 참고
### bbs.php?m=* 파라미터 의미

| 파라미터 | 의미 |
| --- | --- |
| m=g | 메시지 로그 검색 |
| m=ad | 관리자 모드 |
| m=tree | 트리 표시 |
| m=p | 게시 / 새로고침 |
| m=c | 개인 설정 |
| m=f | 답글 화면 |
| m=t | 스레드 표시 |
| m=s | 사용자 검색 |
| m=u | UNDO 실행 |

## 연혁
（초기 버전 상세 내용은 영문 README 참조）

### RC8 (2026/07/20)
* 관리자 비밀번호（ADMINPOST/ADMINKEY）를 conf.php 외부의 `local.php`로 이전, 독립 도구 `_setup.php`로 관리
* install.php：업그레이드 시 ADMINPOST가 존재하면 비밀번호 마이그레이션 폼 표시

### RC9 (2026/07/25)
* 모바일 표시 수정, ZIP 생성 미정의 변수 수정
* 게시물 본문의 `#해시태그`가 날짜 범위 지정 getlog 검색 링크로 자동 변환

### RC10 (2026/08/01)
* install.php：conf.php 조정・확인 화면（자동 병합, 7개 언어 지원, 파일 단위 롤백）
* 선택적 JS 기능 3종：LaTeX 수식 렌더링（latexrender.js）, 미읽음 스레드 접기（treehide.js）, 장문 행수 필터（longpostfilter.js）

### RC11 (2026/08/01)
* install.php：conf.php 항목 경계 파서 근본 수정
* bbs.php：LaTeX `$...$` 구분자가 2번째 줄 이후에서 동작하지 않던 버그 수정

### RC12 (2026/08/01)
* BBSLINK 확인 화면을 textarea로 변경
* 수식 형식의 설정값이 문자열로 저장되던 버그 수정

### RC13 (2026/08/01)
* 브라우저 단위 JS 토글을 개인 설정（m=c）의 「JS설정」 fieldset에 통합, localStorage→cookie로 이전

### RC14 (2026/08/01)
* RC13 버그 수정 3건：이모티콘 fieldset 여백, longpostfilter 접기 링크, ayashiibreaker 목표 너비
* ayashiibreaker：ASCII 단어 경계 줄바꿈 재구현, 긴 단어도 반드시 분할되도록

### RC15 (2026/08/02)
* PHPStan Level 5 전체 검토・버그 수정 3건
* conf.php 확인 화면：각 설정의 설명 텍스트 표시, 98개 CONF_HELP_* 키 7개 언어 지원
* doc/ 언어별 서브디렉터리 구조로 정리

### RC16 (2026/08/03)
* main_upper에 언어 전환 셀렉터 추가
* 개인 설정 패널：conf.php 레벨의 `GIKONEKO_TOISSHO` 설정을 클라이언트 측에서 덮어쓰기 가능
* gikoneko.php / gikonekoadd.php 다국어화

### RC17 (2026/08/03)
* 트리 표시：재작성 인용 행을 금색으로 강조, 정렬 순서 토글（새→오래/오래→새, 브라우저 저장）
* LaTeX：`$변수` 형식의 토큰이 수식 구분자로 오인식되던 버그 수정
* install.php：설치 프로그램 UI・CONF_HELP_* 항목의 7개 언어 지원

### RC18 (2026/08/07)
* bbs.php：로그 쓰기 언어 일관성 수정——참고 행, 자기 답글 태그, 요일 이름이 방문자의 선택 언어에 관계없이 게시판 기본 언어（LANGUAGE_FILE）로 항상 로그에 기록되도록 수정（TDefault() / getdatestr_default() 추가）
* bbs.php: tripuse()의 mbstring 의존성 제거, iconv()만 사용 (qptns.com 동작 변경 없음)
* install.php: 버전 업그레이드 시 관리자 비밀번호 유지/변경 선택 UI 추가 (전 7개 언어 지원)
* install.php: 버전 업그레이드 시 내용이 동일한 파일 건너뜀（SHA-256 해시 비교, 줄 바꿈 차이에 영향받지 않음）
* bbs.php: 참조 행・자기 답글 태그가 표시 시 방문자 언어를 따름（로그는 기본 언어로 저장, 표시 시에만 변환. 로그 형식 불변）
* bbs.php・sub/*.php: 참조 행 제거 처리（트리 표시・로그 다이제스트・관리 화면・이미지 BBS）를 영어 하드코딩에서 다국어 대응으로 변경

### RC19 (2026/08/08)
* install.php: 복수 설치 대상 직렬화——재귀적 runNextTarget()을 processSingleTarget()＋순차 Promise 체인으로 교체; conf 확인·관리자 비밀번호 입력 등이 각 대상별로 올바르게 처리됨
* install.php: conf 확인 폼 인라인화（로그 목록 내에 표시）, CGIURL 기반 동적 링크, 설치 로그 저장 기능 추가
* install.php: 스텝 번호（NN-S/T 형식）표시, conf 스킵 사유 표시, 대상별 설치 헤더 표시
* install.php: ksphp_migrate() 호출 제거, data/.migrated 마커를 대상 디렉터리별로 직접 생성（KSPHP_ROOT 재정의 불가 문제 해결）
* install.php: ksphp_install_run() 내의 미정의 $target_dir（올바른 이름: $parent_dir）버그 수정——복수 설치 통신 오류의 근본 원인
* template.html: main_upper의 언어 선택기 재복원（다시 사라져 있던 문제를 재수정）

자세한 내용은 `doc/changelog-2026-07-16-01.txt` 를 참조하세요.

## 할 일
* **업로더 썸네일 JS** — upthumb.js를 Uploader@Heyuri 이외의 Uploader 인스턴스에도 쉽게 대응할 수 있도록 정비
* **새 게시물 화면에 폼이 표시되지 않음** — 간헐적 발생, 재현 조건 불명

## 알려진 버그
* 로그 검색 시 `&nbsp;`가 대량으로 나타남
