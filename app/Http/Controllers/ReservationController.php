<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\View\View;

/**
 * oluşan rezervasyonlar.
 */
class ReservationController extends Controller
{
    public function index(): View
    {
        $reservations = Reservation::with(['unit.hotel.city'])
            ->whereNotNull('chat_session_id')
            ->latest('id')
            ->paginate(20);

        $stats = Reservation::whereNotNull('chat_session_id')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_price), 0) as revenue, COALESCE(SUM(DATEDIFF(check_out, check_in)), 0) as nights')
            ->first();

        return view('reservations.index', compact('reservations', 'stats'));
    }

    public function show(Reservation $reservation): View
    {
        if ($reservation->chat_session_id === null) {
            abort(404);
        }

        $reservation->load(['unit.hotel.city', 'chatSession.messages' => fn ($q) => $q->orderBy('id')]);

        return view('reservations.show', compact('reservation'));
    }
}
