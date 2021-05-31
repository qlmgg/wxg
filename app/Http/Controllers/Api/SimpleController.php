<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class SimpleController extends Controller
{
    /**
     * 获取操作模型
     * @return Model
     */
    abstract protected function getModel();



    /**
     * 实现搜索
     * @param Request $request
     * @return Builder
     */
    abstract protected function search(Request $request): Builder;


    protected $orderBy = [
        [
            'column' => 'id',
            'direction' => 'desc'
        ]
    ];


    /**
     * 列表
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Contracts\Pagination\Paginator|View
     */
    public function index()
    {
        $request = request();
        $model = $this->search($request);

        foreach ($this->orderBy as $val) {
            $column = data_get($val, 'column');
            $direction = data_get($val, 'direction');
            if ($column && $direction) {
                $model->orderBy($column, $direction);
            }
        }

        if ($request->header('simple-page') == 'true') {
            return $model->simplePaginate($request->input("per-page", 15));
        } else {
            return $model->paginate($request->input("per-page", 15));
        }
    }
}
