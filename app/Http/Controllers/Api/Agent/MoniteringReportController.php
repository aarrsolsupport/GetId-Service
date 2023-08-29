<?php

namespace App\Http\Controllers\Api\Agent;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\PaymentMethod;
use App\Models\AdminAccounts;
use Illuminate\Http\Request;
use App\Models\User;
use DB,Validator;

class MoniteringReportController extends BaseController
{


	public function userCreateCountList(Request $request){
		try{
			if($request->user_id){
				$list = User::where('client_parent_id',$request->user_id);
	            if($request->search && $request->search!='all'){
           			$list = $list->whereBetween('created_at',[$request->from,$request->to]);
	            }
	            $paginate = $request->paginate??config('constants.pagination');
	            $list = $list->orderBy('created_at','desc')
	                            ->paginate($paginate);
	            return $this->sendResponse($list, 'success');
			}else{
           	 	return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
			}
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function firstDepositCountList(Request $request){
		dd($request->all());
	}
}