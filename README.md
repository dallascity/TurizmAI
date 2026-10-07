# TurizmAI

Yapay zekâ destekli otel rezervasyon asistanı. Müşteri otel aramaz; chat üzerinden nereye, ne zaman ve kaç kişi gideceğini yazar. Asistan (Google Gemini) uygun otelleri önerir, boş odaları ve fiyatları gösterir, rezervasyonu oluşturur.

**Kullanılanlar:** Laravel 12 · MySQL 8 · Docker · Google Gemini · Bootstrap 5 · jQuery

---

## Kurulum

### Gereksinim

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (kurulu ve açık olmalı)

PHP, Composer veya MySQL kurmanıza **gerek yok**; hepsi Docker içinde çalışır.

### 1. Projeyi indirin

```bash
git clone <repo-adresi> TurizmAI
cd TurizmAI
```

### 2. `.env` dosyasını oluşturun

```bash
cp .env.example .env
```

> Windows (PowerShell) kullanıyorsanız: `copy .env.example .env`

### 3. Gemini API anahtarını ekleyin (önemli!)

Asistanın çalışması için **ücretsiz** bir Google Gemini API anahtarı gerekir.
Anahtar eklenmezse sayfa açılır ama asistan cevap veremez.

1. https://aistudio.google.com/apikey adresine gidin ve Google hesabınızla giriş yapın.
2. **"Create API key"** butonuna basın ve oluşan anahtarı kopyalayın.
3. `.env` dosyasını açın ve en alttaki satıra yapıştırın:

```env
GEMINI_API_KEY=buraya_anahtarinizi_yapistirin
```

> ⚠️ Proje adı (`gen-lang-client-...`) anahtar **değildir**. Anahtarı "Key" sütunundaki kopyalama ikonundan alın.

### 4. Projeyi başlatın

```bash
docker compose up -d --build
```

İlk çalıştırmada gerekli dosyalar indirildiği için birkaç dakika sürebilir.

### 5. Tarayıcıda açın

| Sayfa | Adres |
|---|---|
| 💬 Asistan (chat) | http://localhost:8080 |
| 📋 Oluşturulan rezervasyonlar | http://localhost:8080/reservations |

Hepsi bu kadar. 🎉

---

## Örnek konuşma

Asistan serbest dili anlar, belirli bir kalıpla yazmak gerekmez:

```
> Selam, deniz kenarında sakin bir yer arıyorum
> Antalya olsun, 20-23 kasım 2 kişiyiz
> ikincisi olsun, standart oda
> Ali Yılmaz, 0532 123 45 67
> evet onaylıyorum
```

Oluşan rezervasyonu ve sohbetin tamamını http://localhost:8080/reservations sayfasında görebilirsiniz.

---

## Sık sorulanlar

**Veritabanı ayarı yapmam gerekiyor mu?**
Hayır. MySQL Docker içinde otomatik kurulur; tablolar ve örnek oteller (9 şehir, 11 otel) ilk açılışta kendiliğinden oluşturulur. `.env` içindeki veritabanı satırlarına dokunmanıza gerek yoktur.

**API anahtarını sonradan ekledim / değiştirdim, ne yapmalıyım?**
`.env` dosyasını kaydedip şu komutu çalıştırın:

```bash
docker compose up -d
```

**Asistan "kullanım limiti doldu" diyor.**
Ücretsiz Gemini anahtarının dakikalık/günlük istek limiti vardır. Bir dakika bekleyip tekrar deneyin. Limitinizi [AI Studio](https://aistudio.google.com/rate-limit) üzerinden görebilirsiniz.

**Projeyi nasıl durdururum?**

```bash
docker compose down        # durdurur, veriler kalır
docker compose down -v     # durdurur ve veritabanını sıfırlar (örnek veriler bir sonraki açılışta tekrar yüklenir)
```

**"8080 portu kullanımda" hatası alıyorum.**
`docker-compose.yml` içindeki `"8080:80"` satırını örneğin `"8081:80"` yapın ve http://localhost:8081 adresini kullanın.

---

## Nasıl çalışır?

```
Müşteri (chat)  →  Laravel  →  Gemini
                                  │  "Antalya'daki otelleri getir", "20-23 Kasım boş oda var mı?"
                                  ▼
                           Otel araçları  →  MySQL
```

Yapay zekâ veritabanına doğrudan erişmez; sadece tanımlı araçları kullanabilir:

| Araç | Ne yapar |
|---|---|
| `list_destinations` | Otel bulunan şehirleri listeler |
| `search_hotels` | Şehir, ülke veya otel adına göre otel arar |
| `check_availability` | Seçilen tarihlerde boş oda/salonları ve toplam fiyatı hesaplar |
| `create_reservation` | Ad soyad ve telefonu alıp, müşteri onayından sonra rezervasyonu kaydeder |

### Ayarlar (`.env`)

| Ayar | Açıklama |
|---|---|
| `GEMINI_API_KEY` | **Zorunlu.** Gemini API anahtarı |
| `GEMINI_MODEL` | Kullanılan model (varsayılan: `gemini-3.5-flash-lite`) |
| `GEMINI_FALLBACK_MODELS` | Ana model yoğun veya limitli olursa sırayla denenen yedek modeller |
