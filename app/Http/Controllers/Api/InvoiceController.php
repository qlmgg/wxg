<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PaymentManagement;
use App\Models\SimpleResponse;
use App\Models\User;
use App\Rules\MobileRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends SimpleController
{
    public function getModel()
    {
        return new Invoice();
    }

    public function search(Request $request): Builder
    {
        // TODO: Implement search() method.
        return $this->query($request->input());
    }

    public function query(array $data):Builder
    {
        $user = Auth::user();
        $model = $this->getModel();
        $model = $model->with(['company',"user"]);
        $model = $model->where("user_id","=",$user->id);
        return $model;
    }

    public function store(Request $request)
    {
        $data = $this->validate($request,[
            "type"=>["required","in:1,2"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "money"=>["required","numeric","max:99999999.99"],
            "address"=>["required","string","max:255"]
        ]);

        //验证用户ID有效性
        $user = Auth::user();
        if(!$user){
            throw new NoticeException("无效用户");
        }

        $max_money = $this->getMaxInvoiceMoney($request);
        if(data_get($data,"money")>$max_money) throw new NoticeException("最大可开票金额为：".$max_money);

        $data['user_id'] = $user->id;
        //如果认证类型为公司则取出用户公司ID
        if(2==data_get($data,"type")){
            $company_model = new Company();
            $company = $company_model->with([])->where("user_id","=",$user->id)->first();
            if($company){
                $data["company_id"] = $company->id;
            }else{
                throw new NoticeException("用户未进行企业认证");
                //return SimpleResponse::error("用户未进行企业认证");
            }
        }
        //如果是个人收件人姓名是取得用户姓名即User表中得name字段 如果是企业则是自己填
        //身份证取用户身份证
        $create = $this->getModel()->with([])->create($data);
        if($create){
            return SimpleResponse::success("添加成功");
        }
        throw new NoticeException("失败");

    }

    public function show($id)
    {
        return $this->getModel()->with(['company',"user"])->findOrFail($id);
    }

    public function update(Request $request,$id)
    {
        $data = $this->validate($request,[
            "type"=>["required","in:1,2"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "money"=>["required","numeric","max:99999999.99"],
            "address"=>["required","string","max:255"]
        ]);
        $user = Auth::user();

        $max_money = $this->getMaxInvoiceMoney($request);
        if(data_get($data,"money")>$max_money) throw new NoticeException("最大可开票金额为：".$max_money);

        //如果认证类型为公司则取出用户公司ID
        if(2==data_get($data,"type")){
            $company_model = new Company();
            $company = $company_model->with([])->where("user_id","=",$user->id)->first();
            if($company){
                $data["company_id"] = $company->id;
            }else{
                throw new NoticeException("用户未进行企业认证");
            }
        }

        $find = $this->getModel()->with([])->find($id);
        $find->update($data);
        return SimpleResponse::success("编辑成功");

    }

    public function destroy($id)
    {
        $find = $this->getModel()->with([])->find($id);
        if(!$find) throw new NoticeException("发票不存在");
        $find->delete();
        return SimpleResponse::success("删除成功");
    }

    public function getMaxInvoiceMoney(Request $request)
    {
        $user = $request->user();
        //开票的金额不能大于允许开票的金额 允许开票的金额为：已支付总金额-已申请开票的金额
        $all_money = PaymentManagement::with([])
            ->where("user_id","=",$user->id)
            ->where("status","=",2)
            ->sum("money");
        $invoiced_money = Invoice::with([])->where("user_id","=",$user->id)->sum("money");
        return round($all_money-$invoiced_money,2);
    }

}
