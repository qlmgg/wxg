<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\BigFileUploadSuccessJob;
use App\Models\BigFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BigFileController extends Controller
{
    protected function getModel()
    {
        return new BigFile();
    }

    public function getEmptyFile(Request $request)
    {
        $data = $this->validate($request, [
            'client_original_name' => ['required', 'string', 'max:255'],
            'sha1' =>  ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($data) {
            $client_original_name = data_get($data, 'client_original_name');
            $pathinfo = pathinfo($client_original_name);
            $extension = data_get($pathinfo, 'extension');
            $sha1 = data_get($data, 'sha1');
            if ($sha1) {
                $create = $this->getModel()->with([])->firstOrCreate([
                    'sha1' => $sha1,
                ], [
                    'client_original_name' => data_get($data, 'client_original_name'),
                    'path' => $extension ? "{$sha1}.$extension" : $sha1,
                    'extension' => $extension
                ]);
            } else {
                $create = $this->getModel()->create([
                    'client_original_name' => data_get($data, 'client_original_name'),
                    'extension' => $extension,
                ]);
                $id = $create->id;
                $create->path = $extension ? "{$id}.$extension" : $id;
                $create->extension = $extension;
                $create->save();
            }
            return $create;
        });
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function uploadSuccess(Request $request, $id) {
        $find = $this->getModel()->find($id);
        $this->dispatchNow(new BigFileUploadSuccessJob($find));
    }

    /**
     * 根据ID获取文件信息
     * @param Request $request
     * @return array
     */
    public function files(Request $request)
    {
        $ids = $request->input('ids');


        if (is_array($ids) && count($ids) > 0) {
            return $this->getModel()->whereIn('id', $ids)->get()->toArray();
        } else if (is_string($ids))  {

            $ids = explode(",", $ids);

            $ids =  collect($ids)->filter(function ($id) {
                return is_numeric($id);
            })->toArray();
            return $this->getModel()->whereIn('id', $ids)->get()->toArray();
        }
        return [];
    }
}
