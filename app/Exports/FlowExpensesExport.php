<?php

namespace App\Exports;

use App\Models\FlowExpenses;
use Maatwebsite\Excel\Concerns\FromCollection;

class FlowExpensesExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return FlowExpenses::all();
    }
}
