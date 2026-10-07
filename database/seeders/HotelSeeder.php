<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Hotel;
use App\Models\Reservation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['Antalya', 'Türkiye', [
                ['Akdeniz Palace Resort', 5, 'Lara', 'Denize sıfır, her şey dahil aile oteli.', ['havuz', 'spa', 'denize sıfır', 'her şey dahil', 'aquapark']],
                ['Kaleiçi Butik Otel', 3, 'Kaleiçi', 'Tarihi Kaleiçi\'nde taş konak butik otel.', ['kahvaltı dahil', 'wifi', 'tarihi bölge']],
            ]],
            ['İstanbul', 'Türkiye', [
                ['Boğaz Manzara Hotel', 5, 'Beşiktaş', 'Boğaz manzaralı iş ve şehir oteli.', ['spa', 'fitness', 'boğaz manzarası', 'toplantı salonu']],
                ['Sultanahmet Konak', 4, 'Fatih', 'Ayasofya\'ya yürüme mesafesinde tarihi otel.', ['kahvaltı dahil', 'teras', 'wifi']],
            ]],
            ['Bodrum', 'Türkiye', [
                ['Ege Mavisi Beach Club', 5, 'Yalıkavak', 'Özel plajlı, yetişkinlere uygun beach otel.', ['özel plaj', 'havuz', 'spa', 'marina yakını']],
            ]],
            ['Kapadokya', 'Türkiye', [
                ['Peri Bacası Mağara Otel', 4, 'Göreme', 'Kayaya oyulmuş mağara odalar, balon manzaralı teras.', ['mağara oda', 'teras', 'kahvaltı dahil', 'balon turu']],
            ]],
            ['İzmir', 'Türkiye', [
                ['Kordon Park Otel', 4, 'Alsancak', 'Kordon\'a yürüme mesafesinde şehir oteli.', ['wifi', 'fitness', 'toplantı salonu']],
            ]],
            ['Trabzon', 'Türkiye', [
                ['Uzungöl Dağ Evi', 3, 'Çaykara', 'Göl manzaralı ahşap dağ oteli.', ['göl manzarası', 'şömine', 'kahvaltı dahil']],
            ]],
            ['Paris', 'Fransa', [
                ['Hôtel Seine Étoile', 4, 'Saint-Germain', 'Seine nehrine yakın butik Paris oteli.', ['wifi', 'kahvaltı', 'eyfel manzarası']],
            ]],
            ['Roma', 'İtalya', [
                ['Colosseo Grand Hotel', 5, 'Monti', 'Kolezyum\'a 5 dakika, çatı terasli lüks otel.', ['çatı terası', 'spa', 'restoran']],
            ]],
            ['Barselona', 'İspanya', [
                ['Barceloneta Sun Hotel', 4, 'Barceloneta', 'Plaja 100 metre, havuzlu modern otel.', ['havuz', 'plaj yakını', 'bar']],
            ]],
        ];

        foreach ($data as [$cityName, $country, $hotels]) {
            $city = City::create([
                'name' => $cityName,
                'country' => $country,
            ]);

            foreach ($hotels as [$name, $stars, $district, $description, $amenities]) {
                $hotel = $city->hotels()->create([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'description' => $description,
                    'stars' => $stars,
                    'address' => "$district, $cityName",
                    'amenities' => $amenities,
                ]);

                $this->createUnits($hotel);
            }
        }

        $this->createSampleReservations();
    }

    private function createUnits(Hotel $hotel): void
    {
        $multiplier = $hotel->stars / 3;

        $hotel->units()->createMany([
            ['type' => 'room', 'name' => 'Standart Oda', 'capacity' => 2, 'quantity' => 10, 'price_per_night' => round(1500 * $multiplier)],
            ['type' => 'room', 'name' => 'Aile Odası', 'capacity' => 4, 'quantity' => 5, 'price_per_night' => round(2600 * $multiplier)],
            ['type' => 'room', 'name' => 'Suit', 'capacity' => 3, 'quantity' => 2, 'price_per_night' => round(4500 * $multiplier)],
        ]);

        if ($hotel->stars >= 4) {
            $hotel->units()->create(
                ['type' => 'hall', 'name' => 'Toplantı Salonu', 'capacity' => 80, 'quantity' => 1, 'price_per_night' => round(8000 * $multiplier)]
            );
        }

        if ($hotel->slug === 'ege-mavisi-beach-club') {
            $hotel->units()->create(
                ['type' => 'villa', 'name' => 'Özel Havuzlu Villa', 'capacity' => 6, 'quantity' => 3, 'price_per_night' => 18000]
            );
        }

        if ($hotel->stars === 5) {
            $hotel->units()->create(
                ['type' => 'hall', 'name' => 'Balo / Düğün Salonu', 'capacity' => 300, 'quantity' => 1, 'price_per_night' => round(25000 * $multiplier)]
            );
        }
    }

    // Sahte Dolu Rezervasyonlar
    private function createSampleReservations(): void
    {
        $start = Carbon::today()->addDays(7);
        $end = $start->copy()->addDays(5);

        $suit = Hotel::where('slug', 'akdeniz-palace-resort')->first()->units()->where('name', 'Suit')->first();
        $this->book($suit, $start, $end, $suit->quantity, 'Örnek Misafir');
        $standard = Hotel::where('slug', 'kaleici-butik-otel')->first()->units()->where('name', 'Standart Oda')->first();
        $this->book($standard, $start, $end, 8, 'Tur Grubu');

        $hall = Hotel::where('slug', 'bogaz-manzara-hotel')->first()->units()->where('type', 'hall')->first();
        $this->book($hall, $start, $start->copy()->addDays(2), 1, 'ABC Şirketi');
    }

    private function book($unit, Carbon $in, Carbon $out, int $qty, string $guest): void
    {
        Reservation::create([
            'unit_id' => $unit->id,
            'guest_name' => $guest,
            'check_in' => $in->toDateString(),
            'check_out' => $out->toDateString(),
            'quantity' => $qty,
            'guests' => $qty * 2,
            'total_price' => $unit->price_per_night * $qty * $in->diffInDays($out),
            'status' => 'confirmed',
        ]);
    }
}
