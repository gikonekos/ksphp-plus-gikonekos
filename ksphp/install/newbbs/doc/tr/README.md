# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
KuzuhaScript'in (くずはすくりぷと) PHP portunun geliştirilmiş bir sürümü.
2024/10/16 itibarıyla yalnızca PHP8+ ile çalışır.
Son eski (legacy) PHP (4.1.0'dan 7.4'e kadar) sürümü burada bulunabilir: [https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)


Bu program, KuzuhaScriptPHP'nin (くずはすくりぷとPHP) 2005/04/01 tarihli değiştirilmiş sürümüne dayanmaktadır.

Bu program başlangıçta [Strange World@Heyuri.net'ten Anonymous-san](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555) tarafından İngilizceye çevrilmiştir ve o zamandan beri Heyuri'den çeşitli anonim geliştiriciler katkıda bulunmuştur.


* [KuzuhaScriptPHP (yansı/mirror)](http://qptn.x.fc2.com/up/dauso0059.zip)  
* [2005/04/01 değiştirilmiş sürüm](http://qptn.x.fc2.com/up/dauso0073.zip)

## Bakımcı bilgileri
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## Kurulum süreci (yalnızca referans amaçlı)
1. İndirilen ZIP dosyasını açın
2. conf.php dosyasını açıp yapılandırın
3. Bir FTP istemcisi vb. kullanarak dosyaları sunucuya yükleyin (diğer dosyalarla karışmaması için özel bir dizin oluşturmak iyi bir fikirdir)
4. readme.md'de açıklanan izinleri ayarlayın
5. Bir web tarayıcısı açın, `_setup.php`'ye erişin (pakete dahil edilen bağımsız bir araçtır; conf.php veya install.php'nin bir parçası değildir) ve yönetici parolasını orada belirleyin. Tamamlandığında, araç kendisini seçtiğiniz bir ada yeniden adlandırır (tahmin edilmesi zor bir varsayılan önerilir) -- parolayı daha sonra değiştirmek için ihtiyacınız olacağından yeni adı/URL'yi not edin.
6. (Artık gerekli değil -- yönetici parolası artık conf.php'ye değil, `_setup.php` tarafından doğrudan kendi dosyasına yazılıyor.)
7. Tarayıcınızı açın, bbs.php'ye gidin ve gönderi yapıp yapamadığınıza bakın
8. Günlük dosyalarının (bbs.log, log/ içindeki dosyalar vb.) bulunduğu URL'ye tarayıcıyla erişin ve görüp göremediğinizi kontrol edin (görebiliyorsanız, lütfen .htaccess vb. ile gizleyin)

## Sorun giderme
### Mevcut bir siteyi güncelleme (yönetici parolası taşıma)
RC8'den itibaren, yönetici parolası (ADMINPOST/ADMINKEY) conf.php dışında, bu şablonun bir parçası olmayan ve install.php tarafından asla üzerine yazılmayan `local.php` adlı sabit isimli bir dosyada bulunur. install.php, güncellediğiniz sitenin mevcut conf.php'sinde boş olmayan bir ADMINPOST tespit ettiğinde, **eski parolanızı** (gerçekten siz olduğunuzu doğrulamak için) ve ayarlanacak bir **yeni parola** isteyen bir taşıma formu gösterecektir. Yönetici modu anahtar kelimesi (ADMINKEY) otomatik olarak taşınır -- bunun için ayrı bir alan yoktur.

- Eski parola başarıyla doğrulanırsa, `local.php` yeni parolayla yazılır ve kurulum normal şekilde devam eder.
- Doğrulama başarısız olursa, **kurulumun tamamı iptal edilir** (hiçbir dosya kurulmaz, hiçbir şey yedeklenmez veya üzerine yazılmaz) -- böylece başkasının kontrol etmediği bir sitenin yönetici hesabını ele geçirmesi önlenir.
- Eski parolayı gerçekten unuttuysanız, sunucudaki mevcut conf.php'yi açın ve `ADMINPOST` değerini manuel olarak boşaltın (boş bir dize olarak ayarlayın). Bu, install.php'nin siteyi yeni bir kurulum olarak ele almasını sağlar ve ardından yeni bir kurulumda olduğu gibi `_setup.php` üzerinden tamamen yeni bir parola belirlemenize olanak tanır.

## Önerilen izin ayarları
Yanlış izinler sorunlara ve veri sızıntılarına (bir gönderinin IP adresi veya uzak host gibi) yol açabilir, bu yüzden lütfen doğru şekilde ayarlandığından emin olun.

```
[Dosya yapısı]
|-- bbs.cnt   600 (yazılabilir)      Katılımcı listesi kayıt dosyası (boş metin dosyası)
|-- bbs.log   600 (yazılabilir)      Günlük dosyası (boş metin dosyası)
|-- conf.php  644 (salt okunur)      Yapılandırma için
|-- bbs.php 644 (salt okunur)        Ana forum betiği
|-- readme.md                        Talimatlar (bu dosya)
|
|-- vanish.js                        Kelime filtreleme betiği
|
|
+-- archive/  700 (yazılabilir)      ZIP arşiv depolama dizini
+-- count/    700 (yazılabilir)      Sayaç çıktı dizini
+-- log/      700 (yazılabilir)      Mesaj günlüğü dosyaları (ham günlükler) depolama dizini
+-- sub/      755 (salt okunur)      Alt modül depolama dizini
    |
    |-- bbsadmin.php    644 (salt okunur)    Yönetim modülü
    |-- bbslog.php      644 (salt okunur)    Günlük görüntüleyici modülü
    |-- bbstree.php     644 (salt okunur)    Ağaç görünümü modülü
    |-- phpzip.inc.php  644 (salt okunur)    ZIP dosyası oluşturma kitaplığı
```

PHP bir Apache modülü olarak çalışıyorsa, bbs.php salt okunur kalabilir,
ancak CGI olarak çalışıyorsa, bbs.php 755 (çalıştırılabilir) olarak ayarlanmalıdır.

## Notlar:
### bbs.php?m=* anlamları listesi

| Parametre | Anlam |
| --- | --- |
| m=g | Mesaj günlüğü arama |
| m=ad | Yönetici modu |
| m=tree | Ağaç görünümü |
| m=p | Gönder/yeniden yükle |
| m=c | Ayarlar |
| m=f | Yanıt ekranı |
| m=t | Konu görünümü |
| m=s | Kullanıcıya göre arama |
| m=u | UNDO çalıştır |

## Geçmiş
### Cion (しおん) sürümü
* 2003/01/21 çalışma başladı
* 2003/01/31 0.0.1alpha
* 2003/02/03 0.0.2alpha
* 2003/02/11 0.0.3alpha
* 2003/02/13 0.0.4alpha
* 2003/02/14 0.0.5alpha
* 2003/02/16 0.0.6alpha
* 2003/02/18 0.0.7alpha

### Resmi olmayan
* 2005/04/01 0.0.8alpha (resmi olmayan) Bir gönüllü tarafından yayınlanan değiştirilmiş sürüm (yansı: http://www.freak.ne.jp/~lunatica/home/up/freak/dauso0073.zip)

### Bilinmeyen tarihler (Hirugatake/蛭ヶ岳 sürümü)
* Arayüz düzeltildi, akıllı telefonlarda vb. kullanımı kolaylaştı
* UTF-8'e geçildi (＠Links)
* PHPZip v1.2'ye güncellendi
* Çeşitli diğer düzeltmeler (kaydedilmedi)

### Bilinmeyen tarihler
* Kodlama stilinde ufak değişiklik
* Yanıt gönderilerindeki hata düzeltildi
* jcode-LE kaldırıldı
* Kullanıcı ayarlarının yansıtılmaması sorunu düzeltildi(?)
* Şablonlar artık dert değil
* func sınıfının gizemli uygulaması çözüldü (tamamlanmadı)
* PHP7.x desteğine hazırlık

### 2018/10/12
* Ad "KuzuhaScriptPHP+" (くずはすくりぷとPHP+) olarak değiştirildi
* Hatalı kontrol nedeniyle eksik form verileri geçersiz kılınıyordu, düzeltildi
* Küçük arayüz değişiklikleri

### 2018/11/18
* Motoi(gikonekos)'nin ağaç görünümü düzeltmeleri uygulandı
* vanish.js dahil edildi

### 2019/11/02
* EZweb görünümü (HDML) kaldırıldı
* imode görünümü kaldırıldı

### 2019/11/02
* EZweb görünümü (HDML) kaldırıldı
* imode görünümü kaldırıldı

### 2020/02/11
* Motoi(gikonekos)'nin ağaç görünümü hata düzeltmeleri uygulandı

### 2020/03/15
* Sayaçlar virgülle ayrıldı

### 2020/03/29
* Motoi(gikonekos)'nin YouTube gömme işlevi eklendi

### 2021/03/08
* Tasarım değişiklikleri (metin kutuları vb.)
* conf.php (ifadeler ve varsayılan değerler değiştirildi)

### 2021/07/03
* Motoi(gikonekos)'nin 2chtrip'i (20210625) eklendi
* bbs.cgi kaldırıldı

### 2021/07/27
* Yönetici parolası ve trip (Motoi(gikonekos)) birlikte kullanıldığında parolanın sızdığı bir sorun düzeltildi
* İsim, E-posta ve Başlık için maksimum karakter sayısı artık ayarlanabilir
* bbs.php içindeki açıklama readme.md'ye taşındı

### 2022/05/06
* bbs.php, index.php'ye taşındı
* Küçük arayüz düzeltmeleri

### 20221127
* sub/patTemplate.php: Motoi(gikonekos)'nin düzeltmeleri uygulandı

### 20230118
* Ağacın doğru görüntülenmediği bir hata düzeltildi

### 20230520
* Motoi(gikonekos)'nin yönetici yolu ifşa önleme (20210923) uygulandı
* Motoi(gikonekos)'nin takma ad parolası sızıntı önleme düzeltmesi (20210923) uygulandı

### Bilinmeyen tarihler (Heyuri sürümü)
* Tamamen İngilizceye çevrildi
* Suratlı (kaomoji) düğmeleri eklendi
* Satır yüksekliği 1 olarak değiştirildi
* Tarayıcının satır sonu için CSS'i yorum satırına alındı
* Kullanıcıların satırları kolayca bölebilmesi için bir JavaScript eklendi
* Motoi(gikonekos)'nin YouTube gömme işlevi yorum satırına alındı
* YouTube gömme için JavaScript uygulandı
* Görsel küçük resimleri için JavaScript eklendi, varsayılan olarak yalnızca Uploader@Heyuri ile çalışacak şekilde ayarlandı

### 2024/10/16
* PHP8'e geçildi
* index.php tekrar bbs.php olarak adlandırıldı
* JS dosyaları ayrı bir dizine taşındı

### 2025/04/29
* Kullanıcı ayarları uygulandıktan sonra yönlendirmenin çalışmaması düzeltildi
* conf.php'den kullanılmayan özelleştirme ayarları kaldırıldı

### 2025/06/07
* IPv6 adresleri için destek eklendi
* Her yerden gömme işlemine izin vermek için http başlık ayarları değiştirildi

### 2025/09/09
* Host engellemeleri düzeltildi

### 2025/11/08
* Uzun satırlar için dikey kaydırma CSS'i eklendi (yalnızca PC)
* Suratlı (kaomoji) düğmeleri bir fieldset'e dönüştürüldü
* Form boş değilken Kullanıcı Ayarlarına tıklamanın gönderiyi gönderdiği bir hata düzeltildi
* ayashii breaker artık uzun satırlı gönderiler için parlayarak kullanıcıyı uyarıyor

### 2025/11/09
* Japonca için tam uluslararasılaştırma eklendi, İngilizce artık isteğe bağlı
* Yazılım adı KuzuhaScriptPHP+EN'den tekrar KuzuhaScriptPHP+ olarak değiştirildi

### 2026/03/10
* Yönetici anahtarları artık Motoi(gikonekos) tarafından uygulanan bir tripcode ile birleştirilebiliyor

### 2026/03/15
* Yönetim menüsüne giriş şekli üstteki bir bağlantı olarak değiştirildi
* Yönetici menüsü yeniden tasarlandı, güvenlik ve kolaylık için oturumlar kullanacak şekilde de değiştirildi

### 2026/06/27
* Gönderi silme yönetim ekranına filtreleme ve toplu seçim yardımcıları eklendi

### 2026/07/18
* gikoneko.php / gikonekoadd.php arayüz metni artık standart `$MSG` mekanizması aracılığıyla yerelleştiriliyor (language/*.txt dosyasına 22 yeni anahtar eklendi)
* gikoneko.php: fal veri dosyası eksikse artık otomatik oluşturuluyor, böylece `file()` artık sayfa çıktısına ham bir PHP uyarısı yaymıyor
* gikonekoadd.php: yeni öğretilen kelimelerin hiçbir zaman gikoneko.php'nin fal havuzuna ulaşmamasına neden olan bir veri dosyası yolu uyumsuzluğu (`../cgi-bin/...`) düzeltildi; her iki betik de artık aynı kök düzeyindeki veri dosyasına başvuruyor
* ayashiibreaker.js v0.4.0'a güncellendi: Japonca satırlar artık boşlukla ayrılmış kelime kaydırmaya güvenmek yerine, kinsoku shori (禁則処理) kurallarına dayalı karakter sayısına göre satır sonlarına bölünüyor

### 2026/07/19
* bbs.php: ana forum görünümü (`getdispmessage()`) ve `msgsearchlist()`'in güncel günlük dalı artık dosyayı tamamen belleğe `file()` ile yüklemek yerine günlük dosyasını satır satır akış olarak okuyor (`Func::fgetline()`). Normal bir sayfa görüntüleme için tepe bellek kullanımı artık `LOGSAVE` / günlük dosyası boyutuyla orantılı olarak ölçeklenmiyor (doğrulandı: 102.300 satır / ~58MB'lık bir günlükte ~131MB'den ~2MB'ye tepe düşüşü; simüle edilmiş 100 eşzamanlı istek trafiği artışı, önceki uygulamanın aksine artık OOM (bellek yetersizliği) sonlandırmalarına yol açmıyor)
* bbs.php: uzun süredir yorum satırına alınmış YouTube gömme kodu kaldırıldı (2024/10/16'dan beri ytthumb.js ile değiştirilmişti)
* bbs.php / conf.php: okunmamış gönderi olmadığında "Gikoneko-to-issho"nun gösterilip gösterilmeyeceği artık `GIKONEKO_TOISSHO` (1=açık, 0=kapalı, varsayılan 1) ile yapılandırılabilir; devre dışı bırakmak, eski basit "okunmamış gönderi yok" mesajına geri döner
* install.php: yakın taramayla bulunamayan klasörler için metin girişli bir "yeni kurulum hedefi ekle" akışı eklendi (yol geçişi doğrulaması ve kullanmadan önce bir onay adımıyla birlikte)
* install.php: Japonca/İngilizce arayüz dili değiştirme eklendi (varsayılan Japonca), çevrilmiş kurulum günlüğü mesajları dahil
* install.php: dosya sistemi köküne, aşırı sığ yollara veya install/ klasörünün kendisine yönelik kurulumları reddeden bir güvenlik önlemi eklendi
* install.php: mevcut dosyalar artık `copy()` yerine `rename()` (atomik) ile yedeğe taşınıyor; dosya başına yedekleme/yeniden adlandırma hataları artık tüm çalıştırmayı iptal etmek yerine yalnızca o dosyayı atlıyor; yeni dosyanın yazılmasındaki bir hata, kenara taşınan orijinali otomatik olarak geri alıyor ve hatalar daha sonra incelenmek üzere ayrıca `install/backup/install-errors-YYYY-MM-DD.txt`'ye kaydediliyor

## Yapılacaklar:
* (2026-08-01'de uygulandı) install.php: geçiş sırasında bir conf.php "ayarlama" adımı -- otomatik birleştirmeden önceden doldurulmuş checkbox/radio/liste/metin alanları, zorunlu alan vurgulama, dosya başına geri alma ile sunucu tarafı doğrulama, isteğe bağlı kişisel geçiş (varsayılan AÇIK), tam 7 dil çevirisi.
* (2026-08-01'de uygulandı) qptns.com'da yapılan canlı testlerin ardından istenen arayüz düzeltmeleri: (1) eski tüm onay kutuları (boolean 0/1 anahtarları) artık, mevcut 3 seçenekli radyo anahtarlarıyla tutarlılık için ve "onay kutusu kafa karıştırıcıydı" geri bildirimine yanıt olarak 2 seçenekli bir radyo grubu olarak görüntüleniyor; (2) ZIPDIR, OLDLOGFILEDIR ve CNTFILENAME artık inceleme ekranında zorunlu olarak işaretlenmiyor -- conf.php'nin kendi yorumları, bu üçü için boşluğun geçerli bir "özellik devre dışı" durumu olduğunu belgeliyor (her manuel yol anahtarının gerçek yorum metni okunarak doğrulandı; diğer manuel yol anahtarları -- LOGFILENAME, COUNTFILE, GIKONEKO_KOTOBA_FILE, UPLOADDIR, UPLOADIDFILE -- böyle bir yoruma sahip değil ve zorunlu kalmaya devam ediyor).
* (2026-08-01'de kök nedeniyle düzeltildi) `ksphp_conf_parse_entries()` giriş sınırı ayrıştırıcısı hatası (orijinal rapor için Bilinen Hatalar'a bakın): `ksphp_conf_merge()`, `ksphp_conf_build_review()`, `ksphp_conf_apply_review()` ve `ksphp_parse_module_array()` genelindeki anahtar çıkarma adımı, artık anahtar adı düzenli ifadesini çalıştırmadan önce bir girişin *baştaki* yorum satırlarını (yeni bir `ksphp_conf_entry_split_lead_comments()` yardımcısı aracılığıyla) kaldırıyor, böylece kendi `'KEY' => ...`'ini içeren yorum satırına alınmış bir örnek artık sonraki gerçek anahtar olarak yanlış atanmıyor. Orijinal yorum metni, yazılan conf.php'de olduğu gibi korunuyor (yalnızca dahili anahtar eşleştirme girdisi etkileniyor, çıktı asla etkilenmiyor). install.php tarafındaki savunmacı geri dönüş (bu tür alanları düzenlenemez "raw" görünümüne zorlayarak) bu düzeltmeyle aşılmış olsa da, bir güvenlik önlemi olarak bırakıldı. Bildirilen HOSTNAME_POSTDENIED/TMPL_MSG/TMPL_ENVLIST vakasına, bir CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC dizisine ve HANDLENAMES tarzı iç içe dizi girişine (açgözlü olmayan eşleşmenin dayandığı dış anahtar tespiti için bir regresyon kontrolü) karşı doğrulandı.
* (2026-08-01'de uygulandı) conf.php inceleme ekranı artık her ayarın kendi yorum metnini (conf.php zaten her anahtar için eşleştirilmiş Japonca/İngilizce yorumlar taşıyor) yeni bir `ksphp_conf_entry_comment_text()` yardımcısı aracılığıyla, inceleme tablosundaki anahtar adının altında bir yardım satırı olarak gösteriyor. Dekoratif bölüm başlığı ayırıcıları (`#---- ... ----`) ve yalnızca çevirmenlere yönelik notlar (`## TL note: ...`) görüntüden filtreleniyor. Kasıtlı olarak hafif tutuldu: gösterilen metin conf.php kaynak yorumunun olduğu gibi hali (Japonca + İngilizce), kurulum arayüzünün diğer 5 diline çevrilmiyor -- bunun için okuyucunun tarayıcı çeviri özelliğine güveniliyor. Her anahtarın tüm 7 kurulum arayüzü diline tam çevirisi (~98 anahtar) tartışıldı ve daha ağır, ayrı bir gelecek görev olarak ertelendi (bu listenin devamına bakın).
* Henüz başlanmadı: conf.php'nin her anahtarının yardım metninin 7 dile tam çevirisi (şu anda yalnızca Japonca/İngilizce kaynak metin, yukarıya bakın). Kapsam yaklaşık 98 anahtar; Korece, Portekizce, Türkçe, zh-hans, zh-hant için yeni `install/language/*.txt` anahtarları (ör. `CONF_HELP_<KEY>`) gerekecektir.

  **(2026-08-02 güncellemesi) Yol boyunca bulunan bir hata düzeltmesi dahil olmak üzere tamamlandı.** Yorum ayırma mantığı, satır sonu yorumları kullanan (baştaki yorum bloğu yerine) 6 conf.php ayarı için (C_A_COLOR/C_A_VISITED/C_A_ACTIVE/C_A_HOVER/C_SUBJ/C_ERROR -- bağlantı/başlık/hata rengi ayarları) yardım metnini bir anahtar kayması ile yanlış atıyordu; yeni `ksphp_conf_entry_trailing_comment()` / `ksphp_conf_build_help_texts()` yardımcıları ile düzeltildi. Bunun üzerine, 7 `install/language/*.txt` dosyasının her birine 98 `CONF_HELP_<KEY>` girişi eklendi; henüz çevirisi olmayan herhangi bir anahtar için (artık hatası düzeltilmiş) ham conf.php yorum çıkarımına geri dönen bir `ksphp_conf_help_text()` sorgusu ile birlikte. PHP'nin yerleşik sunucusu aracılığıyla gerçek `?ajax=1&action=conf_review` uç noktasına karşı 7 dilin tümü için uçtan uca doğrulandı. Henüz yeni bir sürüm zip'ine paketlenmedi veya changelog'a yansıtılmadı.
* (2026-08-01'de uygulandı, istemci tarafı JS) Forumda gündeme gelen topluluk özellik istekleri -- LaTeX matematik render'ı (`$E=mc^2$` tarzı, newbbs/js/latexrender.js, yalnızca bireysel okuyucu tercih ettiğinde bir CDN'den KaTeX yükler), bir "okunmamış konuyu daralt/sil" kontrolü (newbbs/js/treehide.js, her zaman geri yüklenebilir) ve uzun gönderi satır sayısı filtresi (newbbs/js/longpostfilter.js, ayarlanabilir eşik) -- üçü de kişisel (tarayıcı başına, localStorage tabanlı) isteğe bağlı ayarlar olarak uygulandı, varsayılan KAPALI, sunucu tarafı katılım yok. Hız için NG-kelime eşleştiricisini WebAssembly'ye taşıma yönündeki ayrı bir istek ertelenmeye devam ediyor: bakımcı önce bir prototip yapıp kıyaslama yapacak ve yalnızca gerçekten anlamlı bir fayda sağlarsa uygulayacak (gerçek darboğazın eşleştirme algoritması değil, NG-kelime listesi boyutu olduğundan şüpheleniliyor).
* (2026-07-25'te gözden geçirildi, bakımcı kararı) Eski "mobil modül" ayarı: `RESTRICT_MOBILEIP` yapılandırma anahtarının ölü/kullanılmadığı doğrulandı (hiçbir kod hiçbir yerde ona başvurmuyor) -- durdurulmuş, ayrı bir mobil cihaz çıktı modülünün kalıntısı. Mevcut mobil desteği yalnızca CSS ile sağlanıyor (viewport meta + medya sorgusu kesme noktaları), sunucu tarafı UA tespiti yok. Bakımcı kararı: "PC değil" UA tespit yaklaşımını gelecekteki bir ihtiyaç için akılda tutmak, ancak şimdilik yalnızca gözden geçirme sırasında bulunan somut CSS boşluklarını düzeltmek -- `.msgtree` (AA/konu görünümü) dar ekranlarda `overflow-x: auto` eksikti (yalnızca masaüstü genişliklerinde mevcuttu, bu yüzden uzun AA satırları blok içinde kaydırmak yerine tüm sayfayı yana itiyordu) ve `.postlists` (yönetici gönderi listesi tablosu) hiçbir yatay kaydırma işlemine sahip değildi. İkisi de düzeltildi.
* (2026-07-25'te uygulandı) Bir gönderi metnindeki `#hashtag` artık (mevcut `AUTOLINK` ayarına tabi olarak) getlog (`m=g`) tam metin arama bağlantısına otomatik olarak dönüştürülüyor, gönderinin kendi tarihine dayalı bir tarih penceresiyle sınırlandırılmış: `OLDLOGSAVESW=1` (aylık günlük dosyaları) olduğunda gönderinin kendi ayı, veya `OLDLOGSAVESW=0` (günlük günlük dosyaları) olduğunda gönderi tarihi dahil son 7 gün.
* Form yeni gönderi ekranında görünmüyor
* Çoklu bayt fonksiyonlarının ve jcode'un doğru kullanımı
* Diğer Uploader tarzı yazılım örneklerini kolayca desteklemek için yükleyici küçük resim JavaScript'ine sahip olmak
* (2026-07-18'de karar verildi, bakımcı takdiri) Ana sayfa gönderi formu kasıtlı olarak "Gönderi tamamlandı" onay ekranını göstermiyor (yalnızca yanıt/takip gönderileri gösteriyor). Gözden geçirildi ve olduğu gibi bırakıldı; bir hata değil.
* (2026-07-18'de karar verildi, bakımcı takdiri) `Cache-Control: no-store`, bbs.php'ye eklenmeyecek. Gönderi yaptıktan sonra geri gidildiğinde bazen eski form içeriğinin kalması, bfcache'in daha hızlı "geri" gezinmesi lehine bir ödünleşim olarak kabul ediliyor.
* (2026-07-19'da not edildi; aşağıdaki 2026-07-20 değişikliğinden sonra da geçerliliğini koruyor) `ADMINKEY` (yönetici gönderi modu giriş anahtar kelimesi) düz metin olarak saklanıyor ve basit bir dize karşılaştırmasıyla eşleştiriliyor, crypt/bcrypt hash'i olan `ADMINPOST`'un aksine. Güvenlik endişesi kabul edildi; özellik şimdilik olduğu gibi bırakılıyor, ancak gelecekteki bir sürüm `ADMINPOST` ile benzer bir hash/karşılaştırma şemasını değerlendirmeli.
* (2026-07-20'de uygulandı) Yönetici gizli bilgileri (`ADMINPOST`/`ADMINKEY`) conf.php'den tamamen ayrıldı, newbbs/ dağıtım şablonunun bir parçası olmayan ve bu nedenle install.php'nin conf-birleştirme sürecine hiçbir zaman dokunulmayan sabit isimli bir dosyaya (`local.php`) taşındı. Bunlar conf.php veya install.php yerine bağımsız bir araç (başlangıçta `_setup.php` olarak adlandırılır, ilk kullanımda operatör tarafından yeniden adlandırılır) aracılığıyla ayarlanır/değiştirilir. Orijinal tasarım tartışması için doc/admin-secrets-concept-2026-07-19-01.txt'ye bakın.

* (2026-08-01'de düzeltildi, RC11) install.php'nin conf-review sayısal ifade değerleri (ör. `MAXOLDLOGSIZE`'ın `4 * 1024 * 1024`'ü) çıplak sayılar yerine tırnak içinde dizeler (`'1023998976'`) olarak geri yazılıyordu, bu da çalışma zamanında dize-vs-int karşılaştırma hatalarına (günlük boyutu kontrollerinin yanlış tetiklenmesine) yol açıyordu. `ksphp_conf_apply_review()`'da mevcut çıplak sayı kontrolünün yanına bir `$was_numeric_expr` kontrolü (yalnızca rakam/operatör/boşluk) eklenerek düzeltildi, böylece bu tür değerler tırnaksız yazılıyor. Bu düzeltmeden önce oluşturulan mevcut conf.php dosyaları, etkilenen anahtarlarında tek seferlik manuel bir düzenleme gerektiriyor (tırnak içindeki değeri tırnaksız bir tam sayı veya ifadeye değiştirme).
* (2026-08-01'de düzeltildi, RC11) bbs.php'deki `Func::html_escape()`, `str_replace("\015$", "", $value)` aracılığıyla, bir gönderi gövdesindeki ilk satırdan sonraki her satırın başındaki `$` karakterini kaldırıyordu -- bunun üzerindeki CR/LF normalleştirmesinden önceki bir kalıntıydı, görünür bir güvenlik gerekçesi de yoktu. Bu, bir gönderinin 2. satırından itibaren LaTeX tarzı `$...$` sınırlayıcılarını (yukarıdaki latexrender.js'ye bakın) bozuyordu. Satır kaldırıldı.
* (2026-08-01'de uygulandı, RC11) conf.php inceleme ekranı artık her ayarın kendi conf.php yorumunu yardım metni olarak gösteriyor (ayrıntılar için yukarıdaki `ksphp_conf_entry_comment_text()` girdisine bakın).
* (2026-08-01'de düzeltildi, RC12) `BBSLINK`, değeri olarak çok satırlı bir HTML/metin bloğu tutmasına rağmen conf.php inceleme ekranında tek satırlık bir metin girişi olarak render ediliyordu. Yeni bir `ksphp_conf_longtext_keys()` listesi ve `'longtext'` alan tipi eklendi (görüntüleme/kaydetme mantığı mevcut `'text'` tipiyle aynı; yalnızca form widget'ı farklı -- `<input type="text">` yerine bir `<textarea>`) ve `BBSLINK` bunun altına kaydedildi.
* (2026-08-01'de uygulandı, RC13) Tarayıcı başına tüm JS özellik geçişleri (mevcut olanlar -- kaomoji.js, upthumb.js, imgthumb.js, vidembed.js -- ve yeniler -- longpostfilter.js, latexrender.js, treehide.js) kişisel ayarlar ("個人環境設定", m=c) sayfası içinde yeni bir "JS設定" (JS Ayarları) fieldset'ine entegre edildi. Ayarlar artık localStorage yerine yeni bir bağımsız çerez (`ksphp_js`, JSON, 90 gün geçerlilik) aracılığıyla kaydediliyor, sayfanın diğer ayarlarıyla aynı "登録" (Kaydet) düğmesi üzerinden gönderiliyor; tek bir PHP tanım tablosu (bbs.php'deki `ksphp_js_setting_defs()`) çerez yükleme/kaydetme/form render'ını yönetiyor, böylece gelecekteki bir JS özelliği eklemek bu tabloya tek satırlık bir ekleme oluyor. Sayfanın üstünde daha önce bağımsız olan üç geçiş (treehide/longpostfilter/latexrender) kaldırıldı; RC10-12'den yükseltme yapan kullanıcılar için bu üçü, çerez ileriye dönük gerçek kaynak olmadan önce mevcut bir localStorage değerini bir kez tercih etmeye devam ediyor. ayashiibreaker.js (satır sonu) belirtildiği gibi açma/kapama onay kutusuna sahip değil (zorunlu), ancak satır uzunluğu parametresi artık panelden yapılandırılabilir; yapılandırılabilir maksimum, conf.php'nin kendi `MAXMSGCOL`'ünden sunucu tarafında hesaplanıyor, böylece sunucunun kabul edeceğinden asla fazla ayarlanamıyor ve Japonca içeren satırlar, MAXMSGCOL bir bayt sınırı (`strlen()` tabanlı) iken satır sonu karakter saydığından, yapılandırılan değerin yaklaşık 1/3'ünü (kinsoku açısından güvenli marj) kullanıyor. Yeni 9 şablon anahtarı için tam 7 dil çevirisi eklendi.
* Henüz başlanmadı: kişisel ayarlar panelinin, conf.php düzeyindeki "Gikoneko-to-issho" ayarını istemci tarafında geçersiz kılmasına izin vermek (conf.php onu etkinleştirebilir, ancak okuyucu yine de kendisi için kapatabilir). Bu, orijinal JS panel isteğinin bir parçasıydı, ancak yukarıda sayılan JS dosyalarına odaklanan RC13 geçişinin kapsamı dışında bırakıldı.

## Bilinen hatalar:
* (Geçiş notu, RC13+) RC10-RC12'de eklenen üç tarayıcı başına JS özelliği (treehide, longpostfilter, latexrender) başlangıçta açma/kapama durumlarını localStorage'da saklıyordu. RC13 bu ayarları bir sunucu çerezine (`ksphp_js`) taşıdı ve geçiş için bu betiklerin her biri, mevcutsa hâlâ bir localStorage değerini tercih ediyor. Sonuç olarak, RC10-RC12'de bir tarayıcıda bu özelliklerden biri açıkça KAPALI yapılmışsa, yeni JS Ayarları panelinde AÇIK'a geçirmenin hiçbir etkisi yokmuş gibi görünür -- eski localStorage'daki `0` kazanır. Bunu düzeltmek tarayıcı başına tek seferlik bir işlemdir: panelde özelliği kapatıp tekrar açın, veya eski anahtarları (`ksphp_treehide_enabled`, `ksphp_longpost_enabled`, `ksphp_latex_enabled`) silin. Yeni kurulumlar ve RC10-RC12'yi hiç görmemiş tarayıcılar etkilenmez.
* (2026-08-01'de bulundu ve düzeltildi, RC13) longpostfilter.js'nin gönderi başına "折りたたむ" (daralt) bağlantısı, gerçekten satır sayısı eşiğini aşıp aşmadığına bakılmaksızın her gönderide görünüyordu, çünkü daraltılmamış render yolu, bağlantı görünür durumdayken koşulsuz olarak expand()'i çağırıyordu. Daraltma bağlantısının yalnızca gerçekten katlanmış (yani eşiği aşmış) bir gönderi manuel olarak yeniden genişletildikten sonra görünmesi için düzeltildi.
* (2026-08-01'de bulundu ve düzeltildi, RC12-02) RC11 devir notları, sayısal ifade yapılandırma değerlerini (ör. `MAXOLDLOGSIZE`'ın `4 * 1024 * 1024`'ü) kaydederken tırnaksız tutmak için `ksphp_conf_apply_review()`'a bir `$was_numeric_expr` kontrolünün eklendiğini iddia ediyordu. Bu kontrol yayınlanan kodda (ne RC11'de ne de RC12'de) hiçbir zaman gerçekten mevcut değildi -- yalnızca önceden var olan çıplak sayı kontrolü kalmıştı. Gerçek bir sitedeki bir `MAXMSGSIZE` değerinin (`'250*120*128*256*128'`, önceki bir kurulum çalıştırmasıyla tırnak içinde bir dize olarak kaydedilmiş) `procForm()`'un içerik uzunluğu karşılaştırmasında bir PHP sayısal olmayan değer uyarısına neden olması yoluyla keşfedildi; bu da HTTP yanıtını bozdu ve tarayıcıda `ERR_CONTENT_DECODING_FAILED` olarak ortaya çıktı. Kontrol artık gerçekten uygulanmış durumda. Önemli sınırlama: bu yalnızca gelecekteki kurulumlarda/geçişlerde hatayı önlüyor -- zaten tırnak içinde bir dize olarak kaydedilmiş bir conf.php değerini OTOMATİK OLARAK ONARMIYOR (inceleme formu, mevcut tırnaklı değeri değiştirmeden yeniden gönderir). Zaten bozulmuş herhangi bir sayısal ifade anahtarı, manuel bir conf.php düzenlemesi veya değerin yeniden yazılıp inceleme formundan yeniden gönderilmesi gerektirir.
* Günlükleri ararken çok sayıda \&nbsp; görünmesi
* (2026-08-01'de install.php conf-review testi sırasında bulundu; aynı gün düzeltildi, kök neden için Yapılacaklar'a bakın) `ksphp_conf_parse_entries()`'in giriş sınırı tarayıcısı, hemen öncesinde tırnaklı `'KEY' =>` gibi görünen bir dize içeren, yorum satırına alınmış bir örnek olduğunda bir anahtarı yanlış atıyordu (ör. yorum satırına alınmış TMPL_MSG/TMPL_ENVLIST'ten sonra HOSTNAME_POSTDENIED, kendi yorum satırına alınmış örneklerinden sonra CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC). Dört çağrı noktasının hepsinde (`ksphp_conf_merge()`, `ksphp_conf_build_review()`, `ksphp_conf_apply_review()`, `ksphp_parse_module_array()`) anahtar adı eşleştirmesinden önce baştaki yorum satırlarını kaldırarak düzeltildi; install.php tarafındaki savunmacı "raw" geri dönüşü bir güvenlik önlemi olarak yerinde bırakıldı, ancak pratikte artık tetiklenmemesi bekleniyor.
* (2026-08-01'de qptns.com'da canlı olarak doğrulandı; aynı gün düzeltildi -- ayrıntılar için Yapılacaklar'a bakın) install.php'nin conf-review ekranı ZIPDIR'ı yanlışlıkla zorunlu bir alan olarak işaretliyordu; boş bir ZIPDIR aslında "zip günlüğü oluşturma" anlamına gelen geçerli bir ayardır. OLDLOGFILEDIR ve CNTFILENAME'deki aynı sorun (kendi conf.php yorumlarıyla doğrulanan aynı "boş = özellik devre dışı" deseni) ile birlikte düzeltildi.
* (2026-08-01'de uygulandı) günlük/aylık eski günlük döndürme dosyası için "dosya mevcut değildi, bu yüzden yeni oluşturuldu" otomatik oluşturma bildirimi artık yalnızca gerçek bir ilk kurulumda görünüyor (OLDLOGFILEDIR'de aynı uzantıya sahip başka bir eski günlük dosyası henüz yoksa). Her gün/ay sınırında tarihli yeni bir dosyanın belirdiği, önceki dosyaların zaten var olduğu rutin döngü artık bildirimi tetiklemiyor. Kasıtlı olarak hafif kapsamlı: "bu, dönemin ilk gönderisi mi" konusunda bir takvim/tarih hesaplaması yerine, bir dizin listeleme kontrolü (başka bir eski günlük dosyası zaten var mı) yapılıyor.
* (2026-08-01'de bulundu ve düzeltildi, RC14-02, qptns.com/test/'te yapılan canlı testler yoluyla) ayashiibreaker.js'nin satır sonu hedefi, RC14'ün ilk düzeltmesinden sonra (Japonca satırlar için configuredLen-2) hâlâ çok sıkıydı: belirli uzun bir test satırına karşı canlı doğrulamanın ardından, hem Japonca hem de ASCII satırları için configuredLen-12'ye yükseltildi. Ayrı olarak, ASCII (İngilizce) kelime sınırı sarmalamasının kendi hatası vardı: taşma eşiğini yalnızca bir kelimeyi eklemeye zaten karar verdikten sonra kontrol ediyordu ve hedef uzunluktan daha uzun tek bir kelimeyi (ör. "ayashiibreaker.js", "word-boundary") hiçbir zaman bölmüyordu, bu yüzden marjdan bağımsız olarak bir satır kesilmeden kalabiliyordu. Satırları bir diziye biriktiren ve aşırı uzun kelimeleri zorla karakter karakter bölen, eklemeden önce aday uzunluğunu kontrol eden bir yapıya yeniden yazıldı, böylece ASCII satırları artık yapılandırılan sınıra kesinlikle uyuyor.
