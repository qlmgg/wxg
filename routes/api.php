<?php

use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\AdminUsersController;
use App\Http\Controllers\Api\Admin\BrandsController;
use App\Http\Controllers\Api\Admin\CommentsController;
use App\Http\Controllers\Api\Admin\CommunicationRecordController;
use App\Http\Controllers\Api\Admin\ContractManagementController;
use App\Http\Controllers\Api\Admin\EmployeeWorkRecordController;
use App\Http\Controllers\Api\Admin\FaultSummaryRecordController;
use App\Http\Controllers\Api\Admin\FixedCheckItemsController;
use App\Http\Controllers\Api\Admin\FlowExpensesController;
use App\Http\Controllers\Api\Admin\FreeCheckOrderController;
use App\Http\Controllers\Api\Admin\GoodsController;
use App\Http\Controllers\Api\Admin\JobContentController;
use App\Http\Controllers\Api\Admin\MaterialListRecordController;
use App\Http\Controllers\Api\Admin\MonthChecksController;
use App\Http\Controllers\Api\Admin\MonthCheckWorkersController;
use App\Http\Controllers\Api\Admin\MonthlyInspectionOrderController;
use App\Http\Controllers\Api\Admin\PaymentManagementController;
use App\Http\Controllers\Api\Admin\PushRecordsController;
use App\Http\Controllers\Api\Admin\StatisticalDataController;
use App\Http\Controllers\Api\Admin\StreamingRevenueController;
use App\Http\Controllers\Api\Admin\SystemParametersController;
use App\Http\Controllers\Api\Admin\VideoManagementController;
use App\Http\Controllers\Api\Admin\WebsiteContentController;
use App\Http\Controllers\Api\IndexController;
use App\Http\Controllers\Api\MonthCheckOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// 微信端授权登录
Route::group(["prefix" => "oauth"], function () {
    // 授权
    Route::post("oauth/token", [App\Http\Controllers\Api\OAuthController::class, "token"]);

    // 测试用的登录
    Route::post("oauth/local-login", [App\Http\Controllers\Api\OAuthController::class, "localLogin"]);
});

// 文件系统
Route::group(["prefix" => "file"], function () {
    // 获取图片上传认证信息
    Route::get('ali/oss-sts', [App\Http\Controllers\Api\AliController::class, "ossSts"]);

    // 获取要上传的文件信息
    Route::post('big-file/empty-file', [App\Http\Controllers\Api\BigFileController::class, "getEmptyFile"]);
    // 上传成功的通知
    Route::get('big-file/{id}/success', [App\Http\Controllers\Api\BigFileController::class, "uploadSuccess"]);

    // 根据文件IDs 获取文件信息
    Route::get('big-file/files', [App\Http\Controllers\Api\BigFileController::class, "files"]);
});

Route::apiResource("index", IndexController::class);

Route::post("user/month-orders/pay-data", [MonthCheckOrderController::class, "payData"]);
Route::any("user/month-orders/notify-url", [MonthCheckOrderController::class, "notifyUrl"]);

Route::post('user/share-user-info', [App\Http\Controllers\Api\ShareController::class, "shareUserInfo"]);
// 用户模块
Route::post("user/is-in-area",[\App\Http\Controllers\Api\DemandController::class,"isInArea"]);
Route::post("user/nature-option",[\App\Http\Controllers\Api\DemandController::class,"natureOptions"]);
Route::post("user/activity",[\App\Http\Controllers\Api\DemandController::class,"getSystemParameters"]);
Route::group(["prefix" => "user", "middleware" => ['auth:api']], function () {
    // 用户基本信息
    Route::get('user/info', [App\Http\Controllers\Api\UserController::class, "info"]);
    Route::post("set-mobile",[\App\Http\Controllers\Api\UserController::class,"setMobile"]);
    //个人认证
    Route::post('user-p-authenticate', [App\Http\Controllers\Api\UserController::class, "personalAuthenticate"]);
    //企业认证
    Route::post('user-c-authenticate', [App\Http\Controllers\Api\UserController::class, "companyAuthenticate"]);
    //提交需求


    Route::apiResource("user-demand",\App\Http\Controllers\Api\DemandController::class);
    //免费检查单
//    Route::post('user-free-inspect-staff-info', [App\Http\Controllers\Api\FreeInspectController::class, "getStaffInfo"]);
//    Route::post('user-evaluate', [App\Http\Controllers\Api\FreeInspectController::class, "evaluate"]);
//    Route::post('user-free-inspect-details', [App\Http\Controllers\Api\FreeInspectController::class, "getInspectDetails"]);
//    Route::apiResource("user-free-inspect",\App\Http\Controllers\Api\FreeInspectController::class);
    Route::get('free-order/{id}/faults', [App\Http\Controllers\Api\FreeCheckOrderController::class, "getOrderFaults"]);
    Route::get('free-order/{id}/site-conditions', [App\Http\Controllers\Api\FreeCheckOrderController::class, "getSiteConditions"]);
    Route::get('free-order/{id}/contracts', [App\Http\Controllers\Api\FreeCheckOrderController::class, "getContracts"]);
    Route::get('free-order/{id}/materials', [App\Http\Controllers\Api\FreeCheckOrderController::class, "getMaterials"]);
    Route::post('free-order/comment', [App\Http\Controllers\Api\FreeCheckOrderController::class, "comment"]);
    Route::get('free-order/{id}/comments', [App\Http\Controllers\Api\FreeCheckOrderController::class, "getComments"]);
    Route::post('free-order/worker-actions', [App\Http\Controllers\Api\FreeCheckOrderController::class, "getWorkerActionInfo"]);
    Route::apiResource("free-check-order",\App\Http\Controllers\Api\FreeCheckOrderController::class);
    //发票管理
    Route::apiResource("user-invoice",\App\Http\Controllers\Api\InvoiceController::class);
    Route::get("user-max-invoice",[\App\Http\Controllers\Api\InvoiceController::class,"getMaxInvoiceMoney"]);
    //消息中心
    Route::put('user-message-confirm/{id}', [App\Http\Controllers\Api\MessageController::class,"confirm"]);
    Route::apiResource('user-message', App\Http\Controllers\Api\MessageController::class);
    //意见反馈
    Route::post('opinion', [App\Http\Controllers\Api\OpinionController::class,"opinion"]);

    //分享
    Route::apiResource("share",\App\Http\Controllers\Api\ShareController::class);
    Route::get('share-code', [App\Http\Controllers\Api\ShareController::class, "getShareCode"]);
    //通过CODE获取用户信息 --不需要通过登陆
    //Route::post('share-user-info', [App\Http\Controllers\Api\ShareController::class, "shareUserInfo"]);
    //确认加入 --需要登陆之后加入
    Route::post('share-confirm', [App\Http\Controllers\Api\ShareController::class, "confirm"]);
    //解除绑定
    Route::delete('share-delete/{id}', [App\Http\Controllers\Api\ShareController::class, "unbind"]);

    Route::get('share-url', [App\Http\Controllers\Api\ShareController::class, "getShareUrl"]);
    Route::delete('share-delete/{id}', [App\Http\Controllers\Api\ShareController::class, "unbind"]);
    //获取分享的用户的信息
    Route::get('share-confirm/{code}', [App\Http\Controllers\Api\ShareController::class, "toConfirm"]);

    // 月检合同订单 zx
    Route::get("month-orders/public-info", [MonthCheckOrderController::class, "publicInfo"]);
    Route::post("month-orders/comment", [MonthCheckOrderController::class, "comment"]);
    Route::get("month-orders/{mcid}/month-check", [MonthCheckOrderController::class, "monthCheck"]);
    Route::get("month-orders/work-record", [MonthCheckOrderController::class, "workRecord"]);
    Route::apiResource("month-orders", MonthCheckOrderController::class);
});

// 微信
Route::group(["prefix" => "wechat", "middleware" => ['auth:api']], function () {
    // 刷新微信解密
    Route::post('wechat/refresh-session-key', [App\Http\Controllers\Api\WeChatController::class, "refreshSessionKey"]);

    // 解密
    Route::post('wechat/decrypt-data', [App\Http\Controllers\Api\WeChatController::class, "decryptData"]);

});

// 微信
Route::group(["prefix" => "wechat"], function () {
    // 维修号关注
    Route::any('wechat/official', [App\Http\Controllers\Api\WeChatController::class, "official"]);

    Route::get('wechat/office-user-info', [App\Http\Controllers\Api\WeChatController::class, "officeUserInfo"]);
});



// 员工端
Route::group(["prefix" => "worker"], function () {

    Route::post("w-oauth/token", [App\Http\Controllers\Api\Worker\OAuthController::class, "token"]);

    Route::post("w-mobile/token", [App\Http\Controllers\Api\Worker\OAuthController::class, "mobileLogin"]);
    Route::post('worker-login-sms', [\App\Http\Controllers\Api\Worker\AuthController::class, "sendLoginSms"]);
    Route::post('worker-login', [\App\Http\Controllers\Api\Worker\AuthController::class, "smsLogin"]);

    // 账号密码登录
    Route::post('password-login', [\App\Http\Controllers\Api\Worker\AuthController::class, "passwordLogin"]);

    Route::group(["middleware"=>["auth:worker","workerAuth"]],function(){
        //更新微信信息
        Route::post("w-update/token", [App\Http\Controllers\Api\Worker\OAuthController::class, "mtoken"]);
        // 刷新微信解密
        Route::post('w-wechat/refresh-session-key', [\App\Http\Controllers\Api\Worker\WeChatController::class, "refreshSessionKey"]);
        // 解密
        Route::post('w-wechat/decrypt-data', [\App\Http\Controllers\Api\Worker\WeChatController::class, "decryptData"]);
        //首页信息
        Route::post("worker-info",[\App\Http\Controllers\Api\Worker\WorkerController::class,"workerInfo"]);
        Route::post("set-mobile",[\App\Http\Controllers\Api\Worker\WorkerController::class,"setMobile"]);
        Route::post("statistics",[\App\Http\Controllers\Api\Worker\WorkerController::class,"statistics"]);
        Route::put("set-work-status/{id}",[\App\Http\Controllers\Api\Worker\WorkerController::class,"setWorkerStatus"]);
        //消息中心
        Route::put('message-confirm/{id}', [App\Http\Controllers\Api\Worker\MessageController::class,"confirm"]);
        Route::apiResource('message', App\Http\Controllers\Api\Worker\MessageController::class);

        //派单/抢单列表
        Route::get("order-receiving",[\App\Http\Controllers\Api\Worker\CheckOrderController::class,"orderReceiving"]);
        Route::get("order-grab",[\App\Http\Controllers\Api\Worker\CheckOrderController::class,"orderGrab"]);
        Route::post("confirm-receive/{month_check_id}",[\App\Http\Controllers\Api\Worker\CheckOrderController::class,"confirmReceiveOrder"]);
        Route::post("reject-order",[\App\Http\Controllers\Api\Worker\CheckOrderController::class,"reject"]);
        Route::post("order-detail",[\App\Http\Controllers\Api\Worker\CheckOrderController::class,"checkOrderDeatail"]);

        //我的订单
        Route::post("month-check-list",[\App\Http\Controllers\Api\Worker\MonthCheckController::class,"monthCheckList"]);
        Route::post("entry-sign",[\App\Http\Controllers\Api\Worker\MonthCheckController::class,"entrySign"]);
        Route::post("job-content",[\App\Http\Controllers\Api\Worker\MonthCheckController::class,"jobContent"]);
        Route::post("get-job-content",[\App\Http\Controllers\Api\Worker\MonthCheckController::class,"getJobContents"]);
        //故障汇总
        Route::apiResource('fault-summary', App\Http\Controllers\Api\Worker\FaultSummaryRecordController::class);
        //现场情况
        Route::apiResource('site-conditions', App\Http\Controllers\Api\Worker\SiteConditionsController::class);
        //合同
        Route::apiResource('contract', App\Http\Controllers\Api\Worker\ContractsController::class);
        //赠送材料/材料清单
        Route::get('brand-option', [App\Http\Controllers\Api\Worker\MaterialListRecordController::class,"brandOptions"]);
        Route::get('choose-goods', [App\Http\Controllers\Api\Worker\MaterialListRecordController::class,"chooseGoods"]);
        Route::get('choose-goods/{id}/sku', [App\Http\Controllers\Api\Worker\MaterialListRecordController::class,"chooseGoodsSku"]);
        Route::post('get-material-list', [App\Http\Controllers\Api\Worker\MaterialListRecordController::class,"getMaterialList"]);
        Route::apiResource('material-list', App\Http\Controllers\Api\Worker\MaterialListRecordController::class);
        //月检合同-月检表内容
        Route::apiResource('inspect', App\Http\Controllers\Api\Worker\InspectController::class);

        //区域经理-需求
        Route::apiResource('manager-demand', App\Http\Controllers\Api\Worker\ManagerDemandController::class);
        //区域经理-合同
        Route::apiResource('manager-contract', App\Http\Controllers\Api\Worker\ManagerContractController::class);
        //区域经理-消息
        Route::apiResource('manager-message', App\Http\Controllers\Api\Worker\ManagerMessageController::class);
        Route::put('manager-message-confirm/{id}', [App\Http\Controllers\Api\Worker\ManagerMessageController::class,"confirm"]);
        //区域经理-员工信息
        Route::apiResource('manager-worker', App\Http\Controllers\Api\Worker\ManagerWorkerController::class);

        //派单
        //Route::apiResource("worker-inspect-order",\App\Http\Controllers\Api\Worker\InspectController::class);

    });

});

// 后台端php
// 无需登录的选项
Route::group(["prefix" => "option"], function () {

    // 地址
    Route::get('area', [\App\Http\Controllers\Api\AreaController::class, "index"]);
});

// 后台端
Route::group(["prefix" => "admin"], function () {

    Route::post('login', [\App\Http\Controllers\Api\Admin\AuthController::class, "login"]);

    Route::get("comments/export", [CommentsController::class, "export"]);

    Route::group(["middleware" => ['auth:adminWorker', 'adminWork']], function () { // 要登录的

        Route::get('auth/info', [\App\Http\Controllers\Api\Admin\AuthController::class, "userInfo"]);

    });

    Route::group(["middleware" => ['auth:adminWorker', 'adminWork']], function () { // 要登录的
        //权限管理模块
        // 菜单类型
        Route::get('menu/types', [App\Http\Controllers\Api\Admin\MenuController::class, "types"]);
        // 菜单方法
        Route::get('menu/methods', [App\Http\Controllers\Api\Admin\MenuController::class, "methods"]);
        // 所有菜单
        Route::get('menu/all', [App\Http\Controllers\Api\Admin\MenuController::class, "all"]);
        // 后台可用的所有接口
        Route::get('menu/api-all', [App\Http\Controllers\Api\Admin\MenuController::class, "allApi"]);

        // 菜单排序
        Route::put('/menu/{id}/sort', [App\Http\Controllers\Api\Admin\MenuController::class, "sort"]);

        // 菜单管理
        Route::apiResource('menu', App\Http\Controllers\Api\Admin\MenuController::class);

        // 修改状态
        Route::put("role/{id}/status", [App\Http\Controllers\Api\Admin\RoleController::class, "setStatus"]);
        // 角色管理
        Route::apiResource('role', App\Http\Controllers\Api\Admin\RoleController::class);

    });


    Route::group(["middleware" => ['auth:adminWorker', 'adminWork']], function () { // 要登录的
        //区域管理
        Route::get("region/option",[\App\Http\Controllers\Api\Admin\RegionController::class,"option"]);
        Route::apiResource("region",\App\Http\Controllers\Api\Admin\RegionController::class);

        //工人管理
        Route::post("worker/options",[\App\Http\Controllers\Api\Admin\WorkerController::class,"options"]);
        Route::post("worker/wx-info",[\App\Http\Controllers\Api\Admin\WorkerController::class,"getWxInfo"]);
        Route::put("worker/{id}/status",[\App\Http\Controllers\Api\Admin\WorkerController::class,"setStatus"]);
        Route::apiResource("worker",\App\Http\Controllers\Api\Admin\WorkerController::class);

        //级别管理
        Route::get("levels/options",[\App\Http\Controllers\Api\Admin\LevelController::class,"levelOptions"]);
        Route::apiResource("levels",\App\Http\Controllers\Api\Admin\LevelController::class);
        //提成管理
        Route::put("royalty/{id}/status",[\App\Http\Controllers\Api\Admin\RoyaltyController::class,"setStatus"]);
        Route::apiResource("royalty",\App\Http\Controllers\Api\Admin\RoyaltyController::class);

        //用户管理
        Route::apiResource("user",\App\Http\Controllers\Api\Admin\UserController::class);

        //发票管理
        Route::get("invoice/{user_id}/max-invoice",[\App\Http\Controllers\Api\Admin\InvoiceController::class,"getMaxInvoiceMoney"]);
        Route::get("invoice/courier-option",[\App\Http\Controllers\Api\Admin\InvoiceController::class,"courierOptions"]);
        Route::put("invoice/{id}/status",[\App\Http\Controllers\Api\Admin\InvoiceController::class,"setStatus"]);
        Route::put("invoice/{id}/courier",[\App\Http\Controllers\Api\Admin\InvoiceController::class,"setCourier"]);
        Route::apiResource("invoice",\App\Http\Controllers\Api\Admin\InvoiceController::class);

        //需求管理
        Route::post("test-send",[\App\Http\Controllers\Api\Admin\DemandController::class,"testSendDemandToRegionWorker"]);
        Route::get("demand-users",[\App\Http\Controllers\Api\Admin\DemandController::class,"userOption"]);
        Route::apiResource("demand",\App\Http\Controllers\Api\Admin\DemandController::class);
        Route::apiResource("demand-communication",\App\Http\Controllers\Api\Admin\DemandCommunicationController::class);
        //创建免费检查订单
        Route::post("demand-create-free",[\App\Http\Controllers\Api\Admin\DemandController::class,"createFreeInspect"]);
    });

    // zx 20210112
    Route::name('admin.')->middleware(['auth:adminWorker'])->group(function () { // 要登录的

        // 数据统计
        # 实时统计
        Route::post('statistical-data/real-time', [StatisticalDataController::class, 'realTime']);
        # 收支统计
        Route::post('statistical-data/in-exp', [StatisticalDataController::class, 'inExp']);
        # 需求统计
        Route::post('statistical-data/demand', [StatisticalDataController::class, 'demand']);
        # 月检统计
        Route::post('statistical-data/monthly-check', [StatisticalDataController::class, 'monthlyCheck']);
        # 合同统计
        Route::post('statistical-data/contract', [StatisticalDataController::class, 'contract']);
        # 工人统计
        Route::post('statistical-data/worker', [StatisticalDataController::class, 'worker']);

        // 免费检查订单
        Route::apiResource('free-check-order', FreeCheckOrderController::class);

        // 免费检查订单-操作-沟通
        Route::apiResource('communication-record', CommunicationRecordController::class);

        // 月检合同订单
        Route::post('monthly-inspection-order/add', [MonthlyInspectionOrderController::class, "add"]);
        Route::apiResource('monthly-inspection-order', MonthlyInspectionOrderController::class);
        //设置月检合同订单客户结算金额是否可见
        Route::put('monthly-inspection-order/{id}/set-is-show-cs', [MonthlyInspectionOrderController::class, "setIsShowClientSettlement"]);

        // 月检合同订单 支付管理
        Route::apiResource('payment-management', PaymentManagementController::class);

        // 月检合同订单 月检记录
        Route::apiResource('month-checks', MonthChecksController::class);

        // 月检合同订单 月检员工
        Route::apiResource('month-check-workers', MonthCheckWorkersController::class);

        // 赠送情况/材料清单
        Route::apiResource('material-list-record', MaterialListRecordController::class);

        // 免费检查订单/月检合同订单 故障汇总单
        Route::apiResource('fault-summary-record', FaultSummaryRecordController::class);

        // 免费检查订单/月检合同订单 员工查看
        Route::apiResource('employee-work-record', EmployeeWorkRecordController::class);

        // 月检合同订单 工作内容
        Route::apiResource('job-content', JobContentController::class);

        // 合同管理
        Route::apiResource('contract-management', ContractManagementController::class);

        // 流水收入列表
        Route::get("streaming-revenue/to-export", [StreamingRevenueController::class, "toExport"]);
        Route::get('streaming-revenue', [StreamingRevenueController::class, 'index']);
        // 流水支出列表
        Route::get("flow-expenses/to-export", [FlowExpensesController::class, "toExport"]);
        Route::get('flow-expenses', [FlowExpensesController::class, 'index']);

        // 固定检查项
        Route::apiResource('fixed-check-items', FixedCheckItemsController::class);
        Route::put("fixed-check-items/{id}/status",[FixedCheckItemsController::class,"status"]);

        // 系统参数管理
        Route::get('system-parameters', [SystemParametersController::class, 'index']);
        Route::put('system-parameters/edit', [SystemParametersController::class, 'edit']);

        // 管理员管理
        Route::post("admin-users/options",[AdminUsersController::class,"options"]);
        Route::post("admin-users/wx-info",[AdminUsersController::class,"getWxInfo"]);
        Route::put("admin-users/{id}/status",[AdminUsersController::class,"setStatus"]);
        Route::apiResource("admin-users", AdminUsersController::class);

        // 视频管理
        Route::apiResource('video-management', VideoManagementController::class);
        Route::put("video-management/{id}/status",[VideoManagementController::class,"status"]);

        // 官网内容管理
        Route::apiResource('website-content', WebsiteContentController::class);
        Route::put("website-content/{id}/status",[WebsiteContentController::class,"status"]);

        // 意见反馈管理
        Route::put("comments/{id}/status",[CommentsController::class, "status"]);
        Route::apiResource('comments', CommentsController::class);

        // 平台消息推送
        Route::apiResource('push-records', PushRecordsController::class);

        // 建筑性质
        Route::get('nature', [\App\Http\Controllers\Api\Admin\NatureController::class, "index"]);
        Route::get('nature/info', [\App\Http\Controllers\Api\Admin\NatureController::class, "info"]);
        Route::post('nature/add', [\App\Http\Controllers\Api\Admin\NatureController::class, "add"]);
        Route::put('nature/edit', [\App\Http\Controllers\Api\Admin\NatureController::class, "edit"]);
        Route::put('nature/status', [\App\Http\Controllers\Api\Admin\NatureController::class, "status"]);
        Route::delete('nature/del', [\App\Http\Controllers\Api\Admin\NatureController::class, "del"]);

        // 品牌管理
        Route::apiResource('brands', BrandsController::class);
        Route::put('brands/{id}/status', [BrandsController::class, 'status']);

        // 商品管理
        Route::apiResource('goods', GoodsController::class);
        Route::put('goods/{id}/status', [GoodsController::class, 'status']);
        Route::delete('goods/{id}/sku-del', [GoodsController::class, 'skuDel']);
        Route::post('goods/import', [GoodsController::class, 'import']);

        // 日志管理
        Route::apiResource('activity-log', ActivityLogController::class);

    });

});
