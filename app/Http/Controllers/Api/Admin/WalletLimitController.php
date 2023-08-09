<?php

namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB,Validator;
use App\Models\Admin\WalletLimit;

class WalletLimitController extends BaseController
{
    public function getWalletLimitData(Request $request){
        try{
            $res = WalletLimit::first();
            return $this->sendResponse($res, 'Wallet limit data get successfully.');            
        }catch(Exception $e){
            return $this->sendError('Error', 'Something went wrong.Please try again later.',401);  
        }
    }

    public function create(Request $request){
        try{
            $requestData = $request->all();
            $validator = Validator::make($requestData,[
                'minimum_deposit_limit'       => 'required|numeric|between:100,100000',
                'maximum_deposit_limit'       => 'required|numeric|between:100,100000',
                'minimum_withdraw_limit'      => 'required|numeric|between:100,100000',
                'maximum_withdraw_limit'      => 'required|numeric|between:100,100000',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }
            $res = WalletLimit::updateOrCreate(['_id'=>$request->id],$request->all());
            if($res){
                return $this->sendResponse([], 'Wallet limit updated successfully.');
            }else{
                return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
            }
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }
}
