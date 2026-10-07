<?php

namespace App\Services\Hotel;

use App\Models\City;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

// Geminin çağırdığı araçlar
class HotelTools
{
    // Gemini
    public const DEFINITIONS = [
        [
            'name' => 'list_destinations',
            'description' => 'Otel bulunan tüm şehirleri, ülkeleri ve otel sayılarını listeler. Kullanıcı nereye gideceğini bilmiyorsa kullan.',
        ],
        [
            'name' => 'search_hotels',
            'description' => 'Şehir, ülke veya otel adına göre otel arar. Yıldız, özellikler ve başlangıç fiyatlarını döner.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Şehir, ülke veya otel adı. Örn: "Antalya", "Fransa"'],
                    'min_stars' => ['type' => 'integer', 'description' => 'En az yıldız (1-5)'],
                    'amenity' => ['type' => 'string', 'description' => 'İstenen özellik. Örn: "havuz", "spa"'],
                ],
            ],
        ],
        [
            'name' => 'check_availability',
            'description' => 'Bir otelin oda ve salonlarının verilen tarihlerde kaç tane boş olduğunu ve toplam fiyatını döner. Oteli adıyla belirt, ID tahmin etme.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'hotel_name' => ['type' => 'string', 'description' => 'Otelin adı, örn. "Boğaz Manzara Hotel"'],
                    'hotel_id' => ['type' => 'integer'],
                    'check_in' => ['type' => 'string', 'description' => 'Giriş tarihi, YYYY-MM-DD'],
                    'check_out' => ['type' => 'string', 'description' => 'Çıkış tarihi, YYYY-MM-DD'],
                    'guests' => ['type' => 'integer', 'description' => 'Kişi sayısı'],
                    'type' => ['type' => 'string', 'description' => 'Sadece bu türü getir: room, hall veya villa'],
                ],
                'required' => ['check_in', 'check_out'],
            ],
        ],
        [
            'name' => 'create_reservation',
            'description' => 'Rezervasyon oluşturur. Sadece ad soyad ve telefon alındıktan ve müşteri özeti onayladıktan sonra çağır.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'unit_id' => ['type' => 'integer', 'description' => 'check_availability sonucundaki unit_id'],
                    'guest_name' => ['type' => 'string'],
                    'guest_phone' => ['type' => 'string', 'description' => 'Örn. 0532 123 45 67'],
                    'guest_email' => ['type' => 'string'],
                    'check_in' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                    'check_out' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                    'quantity' => ['type' => 'integer', 'description' => 'Kaç oda/salon'],
                    'guests' => ['type' => 'integer'],
                ],
                'required' => ['unit_id', 'guest_name', 'guest_phone', 'check_in', 'check_out'],
            ],
        ],
    ];

    // $userMessage: müşterinin son mesajı, rezervasyondan önce onay verilip verilmediğini kontrol etmek için
    public function call(string $name, array $args, ?int $chatSessionId = null, string $userMessage = ''): array
    {
        try {
            return match ($name) {
                'list_destinations' => $this->listDestinations(),
                'search_hotels' => $this->searchHotels($args),
                'check_availability' => $this->checkAvailability($args),
                'create_reservation' => $this->createReservation($args, $chatSessionId, $userMessage),
                default => ['error' => "Bilinmeyen araç: $name"],
            };
        } catch (InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function listDestinations(): array
    {
        return City::withCount('hotels')->orderBy('country')->orderBy('name')->get()
            ->map(fn (City $city) => [
                'city' => $city->name,
                'country' => $city->country,
                'hotel_count' => $city->hotels_count,
            ])
            ->all();
    }

    private function searchHotels(array $args): array
    {
        $hotels = Hotel::with(['city', 'units'])->get();

        if (! empty($args['query'])) {
            $query = self::normalize($args['query']);
            $hotels = $hotels->filter(function (Hotel $hotel) use ($query) {
                $name = self::normalize($hotel->name);
                $city = self::normalize($hotel->city->name);

                return str_contains($name, $query) || str_contains($query, $name)
                    || str_contains($city, $query) || str_contains($query, $city)
                    || str_contains(self::normalize($hotel->city->country), $query);
            });
        }

        if (! empty($args['min_stars'])) {
            $hotels = $hotels->where('stars', '>=', $args['min_stars']);
        }

        if (! empty($args['amenity'])) {
            $amenity = self::normalize($args['amenity']);
            $hotels = $hotels->filter(fn (Hotel $hotel) => collect($hotel->amenities)
                ->contains(fn ($item) => str_contains(self::normalize($item), $amenity)));
        }

        if ($hotels->isEmpty()) {
            return ['hotels' => [], 'available_cities' => $this->listDestinations()];
        }

        return ['hotels' => $hotels->sortByDesc('stars')->values()->map(fn (Hotel $hotel) => [
            'hotel_id' => $hotel->id,
            'name' => $hotel->name,
            'city' => $hotel->city->name,
            'country' => $hotel->city->country,
            'stars' => $hotel->stars,
            'address' => $hotel->address,
            'description' => $hotel->description,
            'amenities' => $hotel->amenities,
            'rooms' => $hotel->units->reject->isHall()->map(fn ($u) => "{$u->name} ({$u->capacity} kişi, {$u->quantity} adet)")->values(),
            'halls' => $hotel->units->filter->isHall()->map(fn ($u) => "{$u->name} ({$u->capacity} kişi)")->values(),
            'min_price_per_night' => (float) $hotel->units->reject->isHall()->min('price_per_night'),
        ])->all()];
    }

    private function checkAvailability(array $args): array
    {
        $hotel = $this->findHotel($args);
        [$checkIn, $checkOut] = self::parseDates($args);
        $nights = $checkIn->diffInDays($checkOut);
        $guests = $args['guests'] ?? null;

        $units = $hotel->units;
        if (! empty($args['type'])) {
            $units = $units->where('type', $args['type']);
        }

        return [
            'hotel' => $hotel->name,
            'city' => $hotel->city->name,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nights' => $nights,
            'currency' => 'TRY',
            'units' => $units->map(function (Unit $unit) use ($checkIn, $checkOut, $nights, $guests) {
                $available = $unit->availableQuantity($checkIn, $checkOut);
                $needed = $guests ? (int) ceil($guests / $unit->capacity) : 1;

                return [
                    'unit_id' => $unit->id,
                    'type' => $unit->typeLabel(),
                    'name' => $unit->name,
                    'capacity' => $unit->capacity,
                    'available' => $available,
                    'needed_for_guests' => $needed,
                    'fits' => $available >= $needed,
                    'price_per_night' => (float) $unit->price_per_night,
                    'total_price' => $unit->price_per_night * $nights * $needed,
                ];
            })->values()->all(),
        ];
    }

    private function createReservation(array $args, ?int $chatSessionId, string $userMessage): array
    {
        // Model bazen onay beklemeden rezervasyon yapmaya çalışıyor; bunu kodla engelliyoruz
        if (! self::isConfirmation($userMessage)) {
            throw new InvalidArgumentException('Müşteri henüz onay vermedi. Önce ad, telefon, otel, oda, tarih, kişi ve toplam fiyatı içeren özeti göster ve "Onaylıyor musunuz?" diye sor. Müşteri onaylarsa tekrar çağır.');
        }

        $unit = Unit::with('hotel')->find($args['unit_id'] ?? null);
        if (! $unit) {
            throw new InvalidArgumentException('Oda/salon bulunamadı.');
        }
        if (empty($args['guest_name'])) {
            throw new InvalidArgumentException('Misafir adı gerekli.');
        }
        if (empty($args['guest_phone'])) {
            throw new InvalidArgumentException('Telefon numarası gerekli, müşteriden isteyin.');
        }

        $phone = self::normalizePhone($args['guest_phone']);
        [$checkIn, $checkOut] = self::parseDates($args);
        $quantity = max(1, (int) ($args['quantity'] ?? 1));

        // Model yanılsa bile dolu odaya rezervasyon yapılmasın diye kayıttan hemen önce tekrar kontrol
        $available = $unit->availableQuantity($checkIn, $checkOut);
        if ($available < $quantity) {
            throw new InvalidArgumentException("Bu tarihlerde yeterli boş {$unit->name} yok (boş: $available).");
        }

        $reservation = Reservation::create([
            'unit_id' => $unit->id,
            'chat_session_id' => $chatSessionId,
            'guest_name' => $args['guest_name'],
            'guest_phone' => $phone,
            'guest_email' => $args['guest_email'] ?? null,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'quantity' => $quantity,
            'guests' => $args['guests'] ?? $quantity,
            'total_price' => $unit->price_per_night * $quantity * $checkIn->diffInDays($checkOut),
        ]);

        return [
            'reservation_id' => $reservation->id,
            'hotel' => $unit->hotel->name,
            'unit' => $unit->name,
            'guest_name' => $reservation->guest_name,
            'guest_phone' => $phone,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'quantity' => $quantity,
            'total_price' => (float) $reservation->total_price,
            'currency' => 'TRY',
        ];
    }

    // Oteli adından bulur; model bazen ID'yi yanlış tahmin ettiği için ada öncelik verilir.
    private function findHotel(array $args): Hotel
    {
        if (! empty($args['hotel_name'])) {
            $name = self::normalize($args['hotel_name']);
            $hotel = Hotel::with(['city', 'units'])->get()->first(function (Hotel $hotel) use ($name) {
                $hotelName = self::normalize($hotel->name);

                return str_contains($hotelName, $name) || str_contains($name, $hotelName);
            });
        } else {
            $hotel = Hotel::with(['city', 'units'])->find($args['hotel_id'] ?? null);
        }

        if (! $hotel) {
            throw new InvalidArgumentException('Otel bulunamadı, önce search_hotels ile arayın.');
        }

        return $hotel;
    }

    private static function parseDates(array $args): array
    {
        try {
            $checkIn = Carbon::parse($args['check_in'] ?? '')->startOfDay();
            $checkOut = Carbon::parse($args['check_out'] ?? '')->startOfDay();
        } catch (\Throwable) {
            throw new InvalidArgumentException('Tarih formatı geçersiz, YYYY-MM-DD kullanın.');
        }

        if ($checkIn->isPast() && ! $checkIn->isToday()) {
            throw new InvalidArgumentException('Giriş tarihi geçmişte olamaz.');
        }
        if ($checkOut->lte($checkIn)) {
            throw new InvalidArgumentException('Çıkış tarihi girişten sonra olmalı.');
        }

        return [$checkIn, $checkOut];
    }

    // 0532..., 532..., +90 532..., 0090 532... -> +905321234567. Yabancı numaralar + ile yazılmalı.
    private static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $isInternational = str_starts_with(trim($phone), '+') || str_starts_with($digits, '00');
        $digits = preg_replace('/^00/', '', $digits);

        if (! $isInternational && strlen($digits) === 11 && $digits[0] === '0') {
            $digits = '90'.substr($digits, 1);
        } elseif (! $isInternational && strlen($digits) === 10) {
            $digits = '90'.$digits;
        }

        $isValid = str_starts_with($digits, '90')
            ? strlen($digits) === 12
            : strlen($digits) >= 10 && strlen($digits) <= 15;

        if (! $isValid) {
            throw new InvalidArgumentException("Telefon numarası geçersiz ($phone). Müşteriden 0532 123 45 67 biçiminde tekrar isteyin.");
        }

        return '+'.$digits;
    }

    private static function isConfirmation(string $message): bool
    {
        $message = self::normalize($message);

        if (preg_match('/\b(hayir|istemiyorum|onaylamiyorum|vazgec)/', $message)) {
            return false;
        }

        return (bool) preg_match('/\b(evet|onay\w*|tamam|tamamdir|olur|kabul\w*|dogru|ok|okey|tabi|tabii|aynen)\b/', $message);
    }

    // Türkçe karakter ve büyük/küçük harf farkı olmadan karşılaştırmak için: "İstanbul'da" -> "istanbulda"
    private static function normalize(string $text): string
    {
        $text = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $text));

        return Str::of($text)->ascii()->replaceMatches('/[^a-z0-9 ]/', '')->squish()->toString();
    }
}
