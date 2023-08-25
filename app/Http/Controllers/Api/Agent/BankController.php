<?php

namespace App\Http\Controllers\Api\Agent;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\PaymentMethod;
use App\Models\AdminAccounts;
use Illuminate\Http\Request;
use App\Models\Bank;
use DB,Validator;

class BankController extends BaseController
{

	public function list(Request $request){
		try{
			$banks = DB::table('admin_accounts')
                        ->where('user_id',$request->user_id)
                        ->where('is_deleted',0);

            if($request->onlyBank){
                $banks = $banks->where('type',1);
            }
			if($request->search && $request->search !=null && $request->search!=''){
                $banks = $banks->where(function($query)use($request){
                            $query->where('holder_name','like','%'.$request->search.'%')
                            ->orWhere('account_no','like','%'.$request->search.'%')
                            ->orWhere('upi_id','like','%'.$request->search.'%');
                        });
			}
			$orderBy = $request->orderBy?:'desc';
			$banks = $banks->orderBy('id',$orderBy)->paginate(config('constants.pagination'));
            if(count($banks)>0){
                // payment methods list
                $paymentMethodIds = array_unique($banks->where('payment_method_id','!=',null)->pluck('payment_method_id')->toArray());
                $methods = PaymentMethod::whereIn('_id',$paymentMethodIds)->get();
                
                // banks list
                $bankIds = array_unique($banks->where('bank_id','!=',null)->pluck('bank_id')->toArray());
                $banksss = Bank::whereIn('_id',$bankIds)->get();

                foreach($banks as $key => $bank){
                    if($bank->payment_method_id==1){
                        $bank->payment_method_name = 'Bank';
                    }else{
                        $payM = $methods->where('_id',$bank->payment_method_id)->first();
                        $bank->payment_method_name = $payM->name;
                    }

                    $bank->bank_name = '';
                    if($bank->bank_id){
                        $bk = $banksss->where('_id',$bank->bank_id)->first();
                        $bank->bank_name = $bk->bank_name;
                    }
                }
            }
			return $this->sendResponse($banks, 'success');
		}catch(Exception $e){
			return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
		}
	}

	public function create(Request $request){
        try{            
            $requestData = array(
                'user_id'       => $request->user_id,
                'payment_method_id'  => $request->type,
                'holder_name'   => $request->holder_name,
                'max_amount'    => $request->max_amount,
                'total_request' => $request->total_request,
            );
            if($request['type_str']=='bank'){
                $requestData['bank_id']     = $request->bank;
                $requestData['wallet_id']   = $request->wallet_id;
                $requestData['type']        = 1;
            }
            if($request['type_str']=='phonepay'){
                $requestData['wallet_id']   = $request->wallet_id;            
                $requestData['type']        = 2;
            }
            if($request['type_str']!='phonepay' && $request['type_str']!='bank'){
                $requestData['upi_id']   = $request->upi_id;            
                $requestData['phone']   = $request->phone; 
                if($request['type_str']=='paytm'){
                    $requestData['type']        = 4;
                }else{
                    $requestData['type']        = 3;
                }
            }
            if(isset($request->label1)){
                $requestData['label1']   = $request->label1;
            }
            if(isset($request->label2)){
                $requestData['label2']   = $request->label2;
            }
            if(isset($request->label3)){
                $requestData['label3']   = $request->label3;
            }
            if(isset($request->label4)){
                $requestData['label4']   = $request->label4;
            }
            if($request->qrCode){
                $requestData['qrCode'] = $request->qrCode;
            }
            if($request->id){
                $res  = AdminAccounts::updateOrCreate(['id'=>$request->id,'user_id'=>$request->user_id],$requestData);
                $res->bank_name = '';
                $res->payment_method_name = '';
                if($res->payment_method_id==1){
                    $res->payment_method_name = 'Bank';
                }else{
                    $methods = PaymentMethod::where('_id',$res->payment_method_id)->first();
                    $res->payment_method_name = $methods->name;
                }                

                $banksss = Bank::where('_id',$res->bank_id)->first();
                if($banksss){
                    $res->bank_name = $banksss->bank_name;
                }
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

    public function updateStatus(Request $request){
        try{ 
            if($request->id && $request->user_id){
                $res  =  AdminAccounts::where(['id'=>$request->id,'user_id'=>$request->user_id])->update(['status'=>(int) $request->status]);
                if($res){
                    return $this->sendResponse([], 'Bank account status change successfully.');
                }else{
                    return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
                }
            }
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }
}
