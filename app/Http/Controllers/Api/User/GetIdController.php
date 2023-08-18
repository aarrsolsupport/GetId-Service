<?php

namespace App\Http\Controllers\Api\User;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\User\GetId;
use App\Models\User\BankAccount;
use Carbon\Carbon;
use DB,Validator;

class GetIdController extends BaseController
{

    public function walletHistory(Request $request){
        if($request->user_id){
            $data = GetId::where('user_id',(int)$request->user_id)
                        ->select('_id','user_id','type','balance','remark','status','created_at','accept_reject_time');
            if($request->from && $request->to){
                $from   = new Carbon($request->from);
                $to     = new Carbon($request->to);
                if( $to >= $from){
                    $data   = $data->whereBetween('created_at',[$from,$to]);
                }
            }
            if($request->filter && $request->filter!='0' && $request->filter!=0){
                $data = $data->where('type',$request->filter);
            }
            $data = $data->orderBy('created_at','desc')->get();
            return $this->sendResponse($data, 'success');
        }
        return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
    }

    public function withdrawRequest(Request $request){
        try{            
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'bank_account_id'   => 'required',  
                'user_id'           => 'required',
                'parent_id'         => 'required',
                'stack'             => 'required|numeric',
            ],[
                'bank_account_id.required'  => 'Bank name field is required.',
                'user_id.required'          => 'Something went wrong. Please try again later.',
                'parent_id.required'        => 'Something went wrong. Please try again later.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }
            $requestData['type']      = 3;
            $requestData['status']    = 0;
            $res  = GetId::create($requestData);
            return $this->sendResponse($res, 'Your Withdraw request sent successfully.');
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    public function depositRequest(Request $request){
        try{            
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'bank_account_id'   => 'required',  
                'user_id'           => 'required',
                'parent_id'         => 'required',
                'stack'             => 'required|numeric',
                'file'              => 'required|max:2048|mimes:jpeg,jpg,png',
            ],[
                'bank_account_id.required'  => 'Bank name field is required.',
                'user_id.required'          => 'Something went wrong. Please try again later.',
                'parent_id.required'        => 'Something went wrong. Please try again later.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }
            if($request->file){
                $files = $this->saveImageIntoS3Bucket($request->file,'deposit_screecshots');
                $requestData['document'] = $files['filename'];
                unset($requestData['file']);
            }
            $requestData['type']      = 2;
            $requestData['status']    = 0;
            $res  = GetId::create($requestData);
            return $this->sendResponse($res, 'Your Deposit request sent successfully.');
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

}
