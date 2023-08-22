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
            $data = GetId::where('user_id',$request->user_id)
                        ->select('_id','user_id','stake','type','balance','remark','status','created_at','accept_reject_time','updated_at');
            if($request->from && $request->to){
                $from   = new Carbon($request->from);
                $to     = new Carbon($request->to);
                if( $to >= $from){
                    $data   = $data->whereBetween('created_at',[$from,$to]);
                }
            }
            if($request->types){
                $types = $request->types;
                $types = str_replace('zero','0',$types);
                $intTypeIds = array_map('intval', explode(',', $types));
                $data = $data->whereIn('status',$intTypeIds);
            }
            $data = $data->orderBy('created_at','desc')->get();

            $totalDeposit = $data->where('type',2)->where('status',1)->sum('stake');
            $totalWithDrawn =  $data->where('type',3)->where('status',1)->sum('stake');
            $profitLoss = $totalDeposit - $totalWithDrawn;
            $response['data'] = $data;
            $response['totalDepostit'] = $totalDeposit;
            $response['totalWithDrawn'] = $totalWithDrawn;
            $response['profitLoss'] = $profitLoss;
            
            return $this->sendResponse($response, 'success');
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
                'stake'             => 'required|numeric',
            ],[
                'bank_account_id.required'  => 'Bank name field is required.',
                'user_id.required'          => 'Something went wrong. Please try again later.',
                'parent_id.required'        => 'Something went wrong. Please try again later.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }
            if($request->file){
                //$files = $this->saveImageIntoS3Bucket($request->file,'deposit_screecshots');
                $requestData['document'] = $request->file;
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

    public function walletTransactionDetail(Request $request)
    {
        try{
            $userId = $request->user_id;
            $id = $request->id;
            $data = GetId::where('_id',$id)->first();
            if($data){
                return $this->sendResponse($data, 'Wallet history transaction record.');
            }
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

}
