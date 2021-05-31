<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PaymentManagement;
use App\Models\SimpleResponse;
use App\Models\User;
use App\Rules\MobileRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InvoiceController extends SimpleController
{
    public function getModel()
    {
        return new Invoice();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder
    {
        $model = $this->getModel();
        $model = $model->with(['company']);
        //根据公司名字查询
        if($company_name = data_get($data,"company_name")){
            $model->whereHas("company",function ($query)use($company_name){
                $query->where("name","like","%{$company_name}%");
            });
        }


        return $model;

    }

    public function store(Request $request)
    {
        $data = $this->validate($request,[
           "user_id"=>["required","integer"],
           "type"=>["required","in:1,2"],
           "name"=>["required","string","max:255"],
           "mobile"=>["required",new MobileRule()],
           "money"=>["required","numeric","max:99999999.99"],
           "address"=>["required","string","max:255"]
        ]);

        //验证用户ID有效性
        $user = User::with([])->find(data_get($data,"user_id"));
        if (!$user) throw new NoticeException("无效用户");

        $max_money = $this->getMaxInvoiceMoney($user->id);
        //if(data_get($data,"money")>$max_money) throw new NoticeException("最大可开票金额为：".$max_money);

        //如果认证类型为公司则取出用户公司ID
        if(2==data_get($data,"type")){
            $company_model = new Company();
            $company = $company_model->with([])->where("user_id","=",data_get($data,"user_id"))->first();
            if($company){
                $data["company_id"] = $company->id;
            }else{
                throw new NoticeException("用户未进行企业认证");
            }
        }

        //如果是个人收件人姓名是取得用户姓名即User表中得name字段 如果是企业则是自己填
        //身份证取用户身份证
        $create = $this->getModel()->with([])->create($data);
        if($create){
            log_action($create, "添加发票：" . data_get($create, "name"), ActivityLog::MODULE_NAME_INVOICE);
            return SimpleResponse::success("添加成功");
        }
        return SimpleResponse::error("添加失败");

    }

    /**
     * @param $id
     * @return Builder|Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function show($id)
    {
        return $this->getModel()->with(['user','company'])->findOrFail($id);
    }

    public function update(Request $request,$id)
    {
        $data = $this->validate($request,[
            //"user_id"=>["required","integer"],
            "type"=>["required","in:1,2"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "money"=>["required","numeric","max:99999999.99"],
            "address"=>["required","string","max:255"]
        ]);

        $find = $this->getModel()->with(["user"])->find($id);
        //return $find["user"];
        if(!$find) throw new NoticeException("无效发票");

        $max_money = $this->getMaxInvoiceMoney($find->user_id);
        //if(data_get($data,"money")>$max_money) throw new NoticeException("最大可开票金额为：".$max_money);

        //用户认证类型与所选类型是否相同
        if($find["user"]["type"] != data_get($data,"type")){
            throw new NoticeException("所选类型与用户认证类型不匹配");
        }
        $old = clone $find;
        if($find->update($data)){
            log_action($find, "编辑发票：" . data_get($find, "name"), ActivityLog::MODULE_NAME_INVOICE,$old);
            return SimpleResponse::success("编辑成功");
        }
        return SimpleResponse::error("编辑失败");

    }

    /**
     * @param Request $request
     * @param $id
     * @return SimpleResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setStatus(Request $request,$id)
    {
        $data = $this->validate($request,[
           "status"=>["required","in:0,1"]
        ]);

        $find = $this->getModel()->with([])->find($id);

        if(!$find) throw new NoticeException("无效更改");
        $old = clone $find;
        $find->status = data_get($data,"status");
        if($find->save()){
            log_action($find, "设置发票状态：" . data_get($find, "name"), ActivityLog::MODULE_NAME_INVOICE,$old);
            return SimpleResponse::success("成功");
        }
        return SimpleResponse::error("失败");

    }

    /**
     * @param Request $request
     * @param $id
     * @return SimpleResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setCourier(Request $request,$id)
    {
        $data = $this->validate($request,[
            "courier_company"=>["required","in:1,2,3,4,5"],
            "courier_number"=>["required","string","max:255"]
        ]);

        $find = $this->getModel()->with([])->find($id);

        if(!$find) throw new NoticeException("无效提交");
        $old = clone $find;
        $find->courier_company = data_get($data,"courier_company");
        $find->courier_number = data_get($data,"courier_number");
        if($find->save()){
            log_action($find, "设置快递信息：" . data_get($find, "courier_number"), ActivityLog::MODULE_NAME_INVOICE,$old);
            return SimpleResponse::success("成功");
        }
        return SimpleResponse::error("失败");
    }

    public function courierOptions(){
        return $this->getModel()->getCourierCompany()->values();
    }

    public function getMaxInvoiceMoney($user_id)
    {
        $user = User::with([])->find($user_id);
        //开票的金额不能大于允许开票的金额 允许开票的金额为：已支付总金额-已申请开票的金额
        $all_money = PaymentManagement::with([])
            ->where("user_id","=",$user->id)
            ->where("status","=",2)
            ->sum("money");
        $invoiced_money = Invoice::with([])->where("user_id","=",$user->id)->sum("money");
        return round($all_money-$invoiced_money,2);
    }

}
