<?php

namespace App\Http\Controllers;

use App\Exceptions\ReportTypeNotFoundException;
use App\Exports\PaymentExport;
use App\Imports\MajooImporter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ParserController extends Controller
{
    const PAYMENT_MAPPER = [
        'Cash'             => 'CASH',
        'Card - GoFood'    => 'GOFOOD',
        'Card - OVO'       => 'OVO',
        'Card - Shopeepay' => 'SHOPEEPAY',
        'Card - Gopay'     => 'GOPAY',
        'Card - DANA'      => 'DANA',
        'Card - GrabFood'  => 'GRABFOOD',
        'Card - Debit BCA' => 'EDC',
    ];

    public function __invoke(Request $request)
    {
        switch ($request->report_type) {
            case 'majoo':
                $importer = MajooImporter::class;
                break;
            default:
                throw new ReportTypeNotFoundException('Report type selected is not supported.', 500);
        }

        $imported = (new $importer)->toCollection($request->file('file'));
        $mapped = $imported->first()->filter(function ($value) {
            $method = explode(',', $value['metode_pembayaran']);

            return isset(self::PAYMENT_MAPPER[$method[0]]);
        })->groupBy(function ($row) {
            return Carbon::parse($row['waktu_order'])->format('Y/m/d');
        })->map(function ($byDate, $date) {
            return $byDate->groupBy('metode_pembayaran')->map(function ($byPayment, $payment) {
                if (! isset(self::PAYMENT_MAPPER[$payment])) {
                    return false;
                }

                return $byPayment->sum('penjualan_rp');
            });
        });

        $data = collect();
        foreach ($mapped as $date => $map) {
            foreach ($map as $payment => $amount) {
                $data->push([
                    'date'    => $date,
                    'payment' => self::PAYMENT_MAPPER[$payment],
                    'amount'  => $amount,
                ]);
            }
        }

        return new PaymentExport($data);
    }
}
