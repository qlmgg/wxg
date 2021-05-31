<?php

namespace App\Exports;

use App\Models\Comment;
use Maatwebsite\Excel\Concerns\FromCollection;

class CommentExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Comment::all();
    }
}
