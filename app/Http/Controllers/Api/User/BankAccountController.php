<?php

namespace App\Http\Controllers\Api\User;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use App\Models\User\BankAccount;
use Illuminate\Http\Request;
use App\Models\Bank;
use Carbon\Carbon;
use DB,Validator;
class BankAccountController extends BaseController
{

    public function bankList(){
        try{
            $list = Bank::orderBy('created_at','desc')->get();
            $banks = [];
            if(count($list)>0){
                foreach($list as $key => $value){
                    $banks[$value->country][] = $value;
                }    
            }
            return $this->sendResponse($banks, 'success');
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
    }
    
    public function list($id){
        if($id){
            $data = BankAccount::where('user_id',$id)
                        ->orderBy('created_at','desc')
                        ->get();
            return $this->sendResponse($data, 'success');
        }
        return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
    }

    public function create(Request $request){
        try{            
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'account_no' => [
                    'required','max:128','min:2',
                    function ($attribute, $value, $fail) {
                        $existingMethod = BankAccount::where('account_no', $value)->first();

                        if ($existingMethod) {
                            $fail('This account no. has already been added.');
                        }
                    },
                ],
                'country'               => 'required',  
                'bank_name'             => 'required',
                'account_holder_name'   => 'required|min:2',
                'user_id'               => 'required',
            ],[
                'country.required'      => 'Please select country name.',
                'bank_name.required'    => 'Please select bank name.',
                'user_id.required'      => 'Something went wrong. Please try again later.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }
            $requestData['is_active'] = 1;
            $res  = BankAccount::create($requestData);
            return $this->sendResponse($res, 'Bank Account added successfully.');
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    public function createUpiAccount(Request $request){
        try{            
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'account_no' => [
                    'required','max:128','min:2',
                    function ($attribute, $value, $fail) {
                        $existingMethod = BankAccount::where('account_no', $value)->first();

                        if ($existingMethod) {
                            $fail('This account no. has already been added.');
                        }
                    },
                ],
                'upi_id' => [
                    'required','max:128','min:2',
                    function ($attribute, $value, $fail) {
                        $existingMethod = BankAccount::where('upi_id', $value)->first();

                        if ($existingMethod) {
                            $fail('This UPI id has already been taken.');
                        }
                    },
                ],
                'account_holder_name'   => 'required|min:2',
                'user_id'               => 'required',
            ],[
                'user_id.required'      => 'Something went wrong. Please try again later.',
            ]);


            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }
            if($request->file){
                $files = $this->saveImageIntoS3Bucket($request->file,'bank');
                $requestData['document'] = $files['filename'];
                unset($requestData['file']);
            }
            $requestData['is_active'] = 1;
            $res  = BankAccount::create($requestData);
            return $this->sendResponse($res, 'Bank UPI Account added successfully.');
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

}
