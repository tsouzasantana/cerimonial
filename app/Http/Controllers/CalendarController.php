<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $requestedMonth = $request->query('month');
        $monthParam = is_string($requestedMonth) && preg_match('/^\d{4}-\d{2}$/', $requestedMonth)
            ? $requestedMonth
            : now()->format('Y-m');

        $month = Carbon::createFromFormat('Y-m-d', "{$monthParam}-01")->startOfMonth();

        $contracts = Contract::with('client')
            ->whereBetween('event_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('event_date')
            ->get()
            ->groupBy(fn (Contract $contract) => $contract->event_date->format('Y-m-d'));

        $weeks = $this->buildWeeks($month, $contracts);

        return view('calendar.index', [
            'month' => $month,
            'weeks' => $weeks,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }

    private function buildWeeks(Carbon $month, Collection $contractsByDay): array
    {
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $week = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $week[] = [
                'date' => $day->copy(),
                'inMonth' => $day->month === $month->month,
                'isToday' => $day->isToday(),
                'contracts' => $contractsByDay->get($day->format('Y-m-d'), collect()),
            ];

            if ($day->dayOfWeek === Carbon::SATURDAY) {
                $weeks[] = $week;
                $week = [];
            }
        }

        return $weeks;
    }
}
