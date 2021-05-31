<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{

    protected function getModel()
    {
        return new Area();
    }

    /**
     * 地区数据
     * @queryParam parent_no 父级no
     *
     * @return array
     */
    public function index()
    {
        $request = request();

        if ($request->ajax()) {
            $parent_no = $request->input('parent_no', 0);

            return $this->getModel()->with([])->where('parent_code', '=', $parent_no)->get([
                'code as value', 'text'
            ]);
        }

        return [];
    }
}
