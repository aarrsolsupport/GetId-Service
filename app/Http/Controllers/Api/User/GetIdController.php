<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User\UserFirstWithdrawDepositRequest;
use App\Models\User\BankAccount;
use Illuminate\Http\Request;
use App\Models\User\GetId;
use Carbon\Carbon;
use DB, Validator;
use App\Models\AdminAccounts; // MySQL model
use MongoDB\Client as MongoDBClient; // MongoDB Client
use Illuminate\Support\Collection;
use App\Models\PaymentMethod;
use App\Models\UserRequestForGetId;
use App\Models\User;
use App\Models\User\UserFirstWithdrawDepositRequest;

class GetIdController extends BaseController
{

    /**
     * Method walletHistory list
     * 
     * @param Request $request [explicite description]
     *
     * @return void
     */
    public function walletHistory(Request $request)
    {
        if ($request->user_id) {
            $data = GetId::where('user_id', $request->user_id)
                ->select('_id', 'user_id', 'stake', 'type', 'balance', 'remark', 'status', 'created_at', 'accept_reject_time', 'updated_at');
            if ($request->from && $request->to) {
                $from   = new Carbon($request->from);
                $to     = new Carbon($request->to);
                if ($to >= $from) {
                    $data   = $data->whereBetween('created_at', [$from, $to]);
                }
            }
            if ($request->types) {
                $types = $request->types;
                $types = str_replace('zero', '0', $types);
                $intTypeIds = array_map('intval', explode(',', $types));
                $data = $data->whereIn('status', $intTypeIds);
            }
            $data = $data->orderBy('created_at', 'desc')->get();

            $totalDeposit = $data->where('type', 2)->where('status', 1)->sum('stake');
            $totalWithDrawn =  $data->where('type', 3)->where('status', 1)->sum('stake');
            $profitLoss = $totalDeposit - $totalWithDrawn;
            $response['data'] = $data;
            $response['totalDepostit'] = $totalDeposit;
            $response['totalWithDrawn'] = $totalWithDrawn;
            $response['profitLoss'] = $profitLoss;

            return $this->sendResponse($response, 'success');
        }
        return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
    }

    /**
     * Method withdrawRequest
     *
     * @param Request $request [explicite description]
     *
     * @return void
     */
    public function withdrawRequest(Request $request)
    {
        try {
            $requestData = $request->all();
            $validator   = Validator::make($requestData, [
                'bank_account_id'   => 'required',
                'user_id'           => 'required',
                'parent_id'         => 'required',
                'stack'             => 'required|numeric',
            ], [
                'bank_account_id.required'  => 'Bank name field is required.',
                'user_id.required'          => 'Something went wrong. Please try again later.',
                'parent_id.required'        => 'Something went wrong. Please try again later.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(), 422);
            }
            $requestData['type']      = 3;
            $requestData['status']    = 0;
            $requestData['stack']     = (floatval($request->stack));
            $res  = GetId::create($requestData);
            if($res){
                $isfirst = UserFirstWithdrawDepositRequest::where(['user_id'=>$request->user_id,'parent_id'=>$request->parent_id,'type'=>3])->first();
                if($isfirst==null){
                    $datavalue = array(
                        'user_id'   => $request->user_id,
                        'parent_id' => $request->parent_id,
                        'type'      => 3,
                        'amount'    => (floatval($request->stack)),
                        'getid_request_id'  => $res->id,
                    );
                    UserFirstWithdrawDepositRequest::create($datavalue);
                }
                return $this->sendResponse($res, 'Your Withdraw request sent successfully.');
            }else{
                return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
            }
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        } catch (Exception $e) {
            return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
        }
    }

    /**
     * Method depositRequest
     *
     * @param Request $request [explicite description]
     *
     * @return void
     */
    public function depositRequest(Request $request)
    {
        try {
            $requestData = $request->all();
            $validator   = Validator::make($requestData, [
                //'bank_account_id'   => 'required',
                'user_id'           => 'required',
                'parent_id'         => 'required',
                'stake'             => 'required|numeric',
            ], [
                //'bank_account_id.required'  => 'Bank name field is required.',
                'user_id.required'          => 'Something went wrong. Please try again later.',
                'parent_id.required'        => 'Something went wrong. Please try again later.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(), 422);
            }
            if ($request->file) {
                //$files = $this->saveImageIntoS3Bucket($request->file,'deposit_screecshots');
                $requestData['document'] = $request->file;
                unset($requestData['file']);
            }
            $requestData['type']      = 2; // deposit
            $requestData['status']    = 0;
            $requestData['admin_account_id'] = $request->admin_account_id;
            $requestData['utr_number'] = $request->utr_number;

            $requestData['user_id']   = (int)$request->user_id;
            $requestData['parent_id'] = (int)$request->parent_id;
            $requestData['stake']     = (floatval($request->stake));

            $res  = GetId::create($requestData);
            if ($res) {
                $isfirst = UserFirstWithdrawDepositRequest::where(['user_id' => $request->user_id, 'parent_id' => $request->parent_id, 'type' => 3])->first();
                if ($isfirst == null) {
                    $datavalue = array(
                        'user_id'   => (int)$request->user_id,
                        'parent_id' => (int)$request->parent_id,
                        'type'      => 3,
                        'amount'    => (floatval($request->stake)),
                        'getid_request_id'  => $res->id,
                    );
                    $insert = UserFirstWithdrawDepositRequest::create($datavalue);
                }
            }
            return $this->sendResponse($res, 'Your Deposit request sent successfully.');
        } catch (Exception $e) {
            return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
        }
    }

    /**
     * Method walletTransactionDetail
     *
     * @param Request $request [explicite description]
     *
     * @return void
     */
    public function walletTransactionDetail(Request $request)
    {
        try {
            $userId = $request->user_id;
            $id = $request->id;
            $data = GetId::with('adminBank')->where('_id', $id)->first();
            if ($data) {
                return $this->sendResponse($data, 'Wallet history transaction record.');
            }
        } catch (Exception $e) {
            return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
        }
    }

    public function test()
    {

        $adminAccounts = AdminAccounts::with('payment', 'banks')->where('user_id', 2)->get();
        $accounts = [];
        $data = [];
        foreach ($adminAccounts as $key => $val) {
            $accounts[$val->type][] = $val;
        }


        // $adminAccounts = AdminAccounts::where('user_id', 2)->get();
        dd($accounts);

        /*
        this query is for user deposti screen
        $query = UserRequestForGetId::with('userRecord')->where('user_id', 6797)->first();
        dd($query);
        */
    }
}
