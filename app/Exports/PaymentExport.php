<?php

namespace App\Exports;

use App\Actions\FetchReport;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel;

class PaymentExport implements FromCollection, Responsable, ShouldAutoSize, WithHeadings
{
    use Exportable;

    const PAYMENT_MAPPER = [
        'CASH'      => [
            'commission'  => 0,
            'source'      => 'WALK IN',
            'destination' => 'CASH',
        ],
        'GOFOOD'    => [
            'commission'  => 20,
            'source'      => 'GOFOOD',
            'destination' => 'GOPAY',
        ],
        'GRABFOOD'  => [
            'commission'  => 20,
            'source'      => 'GRABFOOD',
            'destination' => 'OVO',
        ],
        'OVO'       => [
            'commission'  => 0.7,
            'source'      => 'WALK IN',
            'destination' => 'OVO',
        ],
        'EDC'       => [
            'commission'  => 0.15,
            'source'      => 'WALK IN',
            'destination' => 'EDC',
        ],
        'DANA'      => [
            'commission'  => 0,
            'source'      => 'WALK IN',
            'destination' => 'DANA',
        ],
        'GOPAY'     => [
            'commission'  => 0.7,
            'source'      => 'WALK IN',
            'destination' => 'GOPAY',
        ],
        'SHOPEEPAY' => [
            'commission'  => 0.7,
            'source'      => 'WALK IN',
            'destination' => 'SHOPEEPAY',
        ],
    ];

    public $data;

    protected $startDate;

    protected $endDate;

    private $fileName = 'payments.csv';

    private $writerType = Excel::CSV;

    public function __construct($data)
    {
        $this->data = $data->map(function ($value) {
            $currentPayment = self::PAYMENT_MAPPER[$value['payment']];

            return [
                'DESCRIPTION'     => $value['payment'] . '/' . $value['date'],
                'ORIGINAL AMOUNT' => $value['amount'],
                'FINAL AMOUNT'    => $value['amount'] * ((100 - $currentPayment['commission']) / 100),
                'SOURCE'          => $currentPayment['source'],
                'DESTINATION'     => $currentPayment['destination'],
                'DATE'            => Carbon::parse($value['date'])->format('Y-m-d'),
                'MDR'             => $currentPayment['commission'] > 0 ? 'MDR: ' . $currentPayment['commission'] . '%' : '',
            ];
        });
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'DESCRIPTION',
            'ORIGINAL AMOUNT',
            'FINAL AMOUNT',
            'SOURCE',
            'DESTINATION',
            'DATE',
            'MDR',
        ];
    }
}
