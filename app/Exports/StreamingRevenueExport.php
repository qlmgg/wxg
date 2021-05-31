<?php

namespace App\Exports;

use App\Models\StreamingRevenue;
use Maatwebsite\Excel\Concerns\FromCollection;

class StreamingRevenueExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return StreamingRevenue::all();
    }
}
