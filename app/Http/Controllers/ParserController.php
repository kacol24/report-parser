<?php

namespace App\Http\Controllers;

use App\Exceptions\ReportTypeNotFoundException;
use App\Exports\PaymentExport;
use App\Imports\MajooImporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Khill\Lavacharts\Laravel\LavachartsFacade;

class ParserController extends Controller
{
    const PAYMENT_MAPPER = [
        'Cash'              => 'CASH',
        'Card - GoFood'     => 'GOFOOD',
        'Card - OVO'        => 'OVO',
        'Card - Shopeepay'  => 'SHOPEEPAY',
        'Card - Gopay'      => 'GOPAY',
        'Card - DANA'       => 'DANA',
        'Card - GrabFood'   => 'GRABFOOD',
        'Card - Debit BCA'  => 'EDC',
        'Card - Debit Lain' => 'EDC/OTHER',
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

        if ($request->generate_type === 'chart') {
            return $this->chartView($imported->first());
        }

        $mapped = $imported->first()->filter(function ($value) {
            $method = explode(',', $value['metode_pembayaran'])[0];

            return isset(self::PAYMENT_MAPPER[$method]);
        })->groupBy(function ($row) {
            return Carbon::parse($row['waktu_order'])->format('Y/m/d');
        })->map(function ($byDate, $date) {
            return $byDate->groupBy('metode_pembayaran')->map(function ($byPayment, $payment) {
                $payment = explode(',', $payment)[0];

                if (! isset(self::PAYMENT_MAPPER[$payment])) {
                    return false;
                }

                return $byPayment->sum('penjualan_rp');
            });
        });

        $data = collect();
        foreach ($mapped as $date => $map) {
            foreach ($map as $payment => $amount) {
                $payment = explode(',', $payment)[0];

                $data->push([
                    'date'    => $date,
                    'payment' => self::PAYMENT_MAPPER[$payment],
                    'amount'  => $amount,
                ]);
            }
        }

        return new PaymentExport($data);
    }

    public function chartView($data)
    {
        $dataset = $data->sortBy(function ($row) {
            return Carbon::parse($row['waktu_order'])->format('H');
        })->groupBy(function ($row) {
            return Carbon::parse($row['waktu_order'])->format('H');
        })->filter(function ($row, $time) {
            return is_int($time);
        });

        $chart = LavachartsFacade::DataTable();
        $chart->addStringColumn('Time')
              ->addNumberColumn('Sales');

        $labels = [];
        foreach (range(0, 23) as $time) {
            $theTime = str_pad($time, 2, '0', STR_PAD_LEFT);
            $labels[$theTime] = 0;

            if (isset($dataset[$time])) {
                $labels[$theTime] = count($dataset[$time]);
            }
        }

        foreach ($labels as $time => $sales) {
            $chart->addRow([$time.':00-'.$time.':59', $sales]);
        }

        $groupedByDate = $data->sortBy(function ($row) {
            return $row['waktu_order'];
        })->groupBy(function ($row) {
            return Carbon::parse($row['waktu_order'])->format('d/m/Y');
        })->reverse()->keys();

        LavachartsFacade::BarChart('Finances', $chart, [
            'title'          => 'Sales Grouped By Time (Periode: '.$groupedByDate->first().'-'.$groupedByDate->last().')',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14,
            ],
        ]);

        return view('charts/order_time_chart');
    }
}
