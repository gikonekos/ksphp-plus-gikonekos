# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
KuzuhaScript'in (くずはすくりぷと) PHP portunu geliştirmiş bir sürümüdür.
2024/10/16 itibarıyla yalnızca PHP8+ ile çalışmaktadır.
Eski PHP (4.1.0–7.4) desteği olan son sürüm: [https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)

Bu program, 2005/04/01 tarihli KuzuhaScriptPHP'nin (くずはすくりぷとPHP) değiştirilmiş sürümünü temel almaktadır.

Bu program ilk olarak [Strange World@Heyuri.net'teki Anonymous-san](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555) tarafından İngilizce'ye çevrilmiş olup o tarihten bu yana Heyuri'nin birçok anonim geliştiricisi katkıda bulunmuştur.

* [KuzuhaScriptPHP (ayna)](http://qptn.x.fc2.com/up/dauso0059.zip)
* [2005/04/01 değiştirilmiş sürüm](http://qptn.x.fc2.com/up/dauso0073.zip)

## Geliştirici bilgileri
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## Kurulum
1. İndirilen ZIP dosyasını çıkartın
2. conf.php'yi açın ve yapılandırın
3. Dosyaları FTP istemcisi vb. ile sunucuya yükleyin (başka dosyalarla karışmaması için özel bir dizin oluşturmanız önerilir)
4. Aşağıda açıklanan izinleri ayarlayın
5. Tarayıcıda `_setup.php`'ye erişin (pakette yer alan bağımsız bir araç; conf.php veya install.php'nin parçası değildir) ve yönetici şifresini belirleyin. Tamamlandığında araç kendiliğinden yeniden adlandırılır — yeni adı/URL'yi not edin.
6. *(Artık gerekli değil — yönetici şifresi `_setup.php` tarafından doğrudan kendi dosyasına yazılır, conf.php'ye değil.)*
7. Tarayıcıda bbs.php'ye erişin ve gönderi yapılabildiğini doğrulayın
8. Log dosyalarının URL'lerine (bbs.log, log/ vb.) tarayıcıdan erişin ve herkese açık olmadıklarını kontrol edin (açıksa .htaccess vb. ile kısıtlayın)

## Sorun giderme
### Mevcut siteyi güncelleme (yönetici şifresi geçişi)
RC8'den itibaren yönetici şifresi (ADMINPOST/ADMINKEY) conf.php'nin dışında, install.php tarafından hiçbir zaman üzerine yazılmayan sabit adlı `local.php` dosyasında saklanmaktadır. install.php mevcut conf.php'de boş olmayan bir ADMINPOST algıladığında, **eski şifrenizi** (doğrulama) ve **yeni şifrenizi** isteyen bir geçiş formu gösterir. ADMINKEY otomatik olarak aktarılır.

- Eski şifre doğrulanırsa `local.php` yazılır ve kurulum devam eder.
- Doğrulama başarısız olursa **kurulumun tamamı iptal edilir** (hiçbir dosya kurulmaz).
- Eski şifreyi gerçekten unuttuysanız, sunucudaki conf.php'deki `ADMINPOST` değerini el ile boş bırakın. install.php bunu yeni kurulum olarak ele alır ve `_setup.php` aracılığıyla yeni bir şifre belirleyebilirsiniz.

## Önerilen izin ayarları
Hatalı izinler sorunlara ve veri sızıntısına (IP adresleri, uzak sunucu adları vb.) yol açabilir.

```
[Dosya yapısı]
|-- bbs.cnt   600 (yazılabilir)  Katılımcı listesi kayıt dosyası (boş metin dosyası)
|-- bbs.log   600 (yazılabilir)  Log dosyası (boş metin dosyası)
|-- conf.php  644 (salt okunur)  Yapılandırma dosyası
|-- bbs.php   644 (salt okunur)  Ana pano betiği
|-- readme.md                    Bu dosya
|-- vanish.js                    Kelime filtreleme betiği
|
+-- archive/  700 (yazılabilir)  ZIP arşiv depolama dizini
+-- count/    700 (yazılabilir)  Sayaç çıktı dizini
+-- log/      700 (yazılabilir)  Ham log depolama dizini
+-- sub/      755 (salt okunur)  Alt modüller
    |-- bbsadmin.php    644      Yönetim modülü
    |-- bbslog.php      644      Log görüntüleyici modülü
    |-- bbstree.php     644      Ağaç görünümü modülü
    |-- phpzip.inc.php  644      ZIP oluşturma kütüphanesi
```

PHP, Apache modülü olarak çalışıyorsa bbs.php salt okunur (644) olabilir. CGI olarak çalışıyorsa 755 (çalıştırılabilir) olarak ayarlayın.

## Başvuru
### bbs.php?m=* parametre anlamları

| Parametre | Anlam |
| --- | --- |
| m=g | Mesaj logu araması |
| m=ad | Yönetici modu |
| m=tree | Ağaç görünümü |
| m=p | Gönderi / yenile |
| m=c | Kişisel ayarlar |
| m=f | Yanıt ekranı |
| m=t | Konu görünümü |
| m=s | Kullanıcıya göre ara |
| m=u | UNDO çalıştır |

## Tarihçe
(RC8 öncesi sürümler için İngilizce README'ye bakın)

### RC8 (2026/07/20)
* Yönetici şifresi (ADMINPOST/ADMINKEY) conf.php dışındaki `local.php` dosyasına taşındı; bağımsız `_setup.php` aracıyla yönetilir
* install.php: güncelleme sırasında mevcut ADMINPOST varsa şifre geçiş formu gösterilir

### RC9 (2026/07/25)
* Mobil görüntüleme düzeltmeleri; ZIP oluşturma tanımsız değişken düzeltmesi
* Gönderi metnindeki `#hashtag` otomatik olarak tarih aralıklı getlog arama bağlantısına dönüştürülür

### RC10 (2026/08/01)
* install.php: conf.php ayarlama/gözden geçirme ekranı (otomatik birleştirme, 7 dil desteği, dosya başına geri alma)
* 3 isteğe bağlı JS özelliği: LaTeX formül oluşturma (latexrender.js), okunmamış konu daraltma (treehide.js), uzun gönderi satır filtresi (longpostfilter.js)

### RC11 (2026/08/01)
* install.php: conf.php giriş sınırı ayrıştırıcı kök düzeltmesi
* bbs.php: LaTeX `$...$` sınırlayıcılarının ikinci satırdan sonra çalışmaması hatası düzeltildi

### RC12 (2026/08/01)
* BBSLINK gözden geçirme ekranında textarea olarak görüntülenir
* Sayısal ifade biçimindeki yapılandırma değerleri artık string olarak kaydedilmez

### RC13 (2026/08/01)
* Tarayıcı başına JS geçişleri kişisel ayarlar (m=c) sayfasındaki "JS設定" fieldset'ine entegre edildi; localStorage'dan cookie'ye geçildi

### RC14 (2026/08/01)
* RC13 hata düzeltmeleri (3 adet): kaomoji fieldset kenar boşluğu, longpostfilter daraltma bağlantısı, ayashiibreaker hedef genişliği
* ayashiibreaker: ASCII kelime sınırı satır kesmesi yeniden yazıldı; artık çok uzun kelimeler mutlaka bölünür

### RC15 (2026/08/02)
* PHPStan Level 5 tam inceleme; 3 hata düzeltmesi
* conf.php gözden geçirme ekranı: her ayar için açıklama metni gösterilir; 98 CONF_HELP_* anahtarı 7 dilde çevrildi
* doc/ dil bazlı alt dizin yapısına düzenlendi

### RC16 (2026/08/03)
* main_upper'a dil seçici eklendi
* Kişisel ayarlar paneli: conf.php düzeyindeki `GIKONEKO_TOISSHO` ayarı istemci tarafında geçersiz kılınabilir hale geldi
* gikoneko.php / gikonekoadd.php çok dilli hale getirildi

### RC17 (2026/08/03)
* Ağaç görünümü: yeniden yazılmış alıntı satırları altın rengiyle vurgulanır; sıralama düzeni geçişi (yeni/eski önce, tarayıcıda kaydedilir)
* LaTeX: `$değişken` biçimindeki simgelerin formül sınırlayıcısı olarak yanlış yorumlanması hatası düzeltildi
* install.php: yükleyici UI ve CONF_HELP_* girişleri için 7 dil desteği genişletildi

### RC18 (2026/08/07)
* bbs.php: log yazma dil tutarlılığı düzeltmesi — referans satırı, kendi yanıtı etiketi ve gün adları artık ziyaretçinin seçtiği dilden bağımsız olarak her zaman panonun varsayılan diliyle (LANGUAGE_FILE) loga yazılır (TDefault() / getdatestr_default() eklendi)
* bbs.php: tripuse() içindeki mbstring bağımlılığı kaldırıldı; yalnızca iconv() kullanılıyor (qptns.com'da işlevsel değişiklik yok)
* install.php: sürüm yükseltmede yönetici şifresi için tut/değiştir seçeneği eklendi; tüm 7 dil destekleniyor
* install.php: sürüm yükseltmede değişmeyen dosyalar atlanır (yedeklemeden önce içerik karşılaştırması)

Herhangi bir sürümün tam ayrıntıları için `doc/changelog-2026-07-16-01.txt` dosyasına bakın.

## Yapılacaklar

* **Yükleyici küçük resim JS** — upthumb.js'yi Uploader@Heyuri dışındaki Uploader örnekleri için kolayca yapılandırılabilir hale getir
* **Yeni gönderi ekranında form görünmüyor** — aralıklı olarak gerçekleşiyor; yeniden üretme koşulları bilinmiyor

## Bilinen Hatalar
* Log araması sırasında çok sayıda `&nbsp;` varlığı görünüyor
