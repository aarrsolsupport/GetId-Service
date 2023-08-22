<?php

namespace App\Http\Controllers\Api\Agent;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\AdminAccounts;
use Illuminate\Http\Request;
use DB,Validator;

class BankController extends BaseController
{

	public function list(Request $request){
		try{
			$banks = DB::table('admin_accounts')
                        ->where('user_id',$request->user_id)
                        ->where('is_deleted',0);
			if($request->search && $request->search !=null && $request->search!=''){
                dd($request->search);
				$banks = $banks->where('holder_name','like',$request->search)
                                ->orWhere('account_no','like',$request->search);
			}
			$orderBy = $request->orderBy?:'desc';
			$banks = $banks->orderBy('id',$orderBy)->paginate(config('constants.pagination'));
			return $this->sendResponse($banks, 'success');
		}catch(Exception $e){
			return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
		}
	}

	public function create(Request $request){
        try{            
            $requestData = array(
                'user_id'       => $request->user_id,
                'country'       => $request->country,
                'holder_name'   => $request->holder_name,
                'type'          => $request->type=='bank'?1:2,
                'phone'         => $request->phone,
                'max_amount'    => $request->max_amount,
                'total_request' => $request->total_request,
            );
            if($request->qrCode){
                $requestData['qrCode'] = $request->qrCode;
            }
            if($request->type=='bank'){
                $requestData['account_no'] = $request->account_no;
                $requestData['ifsc_code'] = $request->ifsc;
            }
            if($request->type=='upi'){                
                $requestData['upi_id'] = $request->upi_id;
            }
            if($request->id){
                $res  = AdminAccounts::updateOrCreate(['id'=>$request->id,'user_id'=>$request->user_id],$requestData);
                return $this->sendResponse($res, 'Bank Account updated successfully.');                
            }else{
                $res  = AdminAccounts::create($requestData);
                return $this->sendResponse($res, 'Bank Account created successfully.');
            }
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }
	
    public function delete($id,$user_id){
        try{           
            $res  =  AdminAccounts::where(['id'=>$id,'user_id'=>$user_id])->update(['is_deleted'=>1]);
            if($res){
				return $this->sendResponse([], 'Bank account deleted successfully.');
        	}else{
            	return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
			}
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }
}
