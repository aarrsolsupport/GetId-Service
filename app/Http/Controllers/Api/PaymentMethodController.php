<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use DB,Validator;
use App\Models\Bank;

class PaymentMethodController extends BaseController
{
    public function __construct(){

    }
    
    /*
     *** List Payment methods
    */
    public function paymentMethods(Request $request){
        try{
            $methods = DB::table('payment_methods');
            if($request->search){
                $methods = $methods->where('name','like',$request->search);
                $methods = $methods->orWhere('country','like',$request->search);
            }
            $orderBy = $request->orderBy?:'desc';
            $methods = $methods->orderBy('id',$orderBy)->paginate(config('constants.pagination'));
            return $this->sendResponse($methods, 'success');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    /*
     **** Add payment method
    */
    public function create(Request $request){
        try{            
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'name'      => 'required|max:128|min:2|unique:payment_methods',
                'country'   => 'required|max:128|min:2',
                'icon'      => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'is_wallet' => 'in:0,1'
            ],[
                'name.required' => 'Payment method name is required.',
                'name.unique'   => 'Payment method name already been taken.',
                'icon.max'      => 'File should be less than 2MB.',
                'icon.mimes'    => 'File format should be : jpeg, png, jpg, gif, svg',
                'is_wallet.in'  => 'Wallet value invalid'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(),422);  
            }
            $data = array(
                'name' => $request->name,
                'country'   => $request->country,
                'icon'      => $request->icon,
                'is_wallet' => $request->is_wallet,
                // 'icon'       => $this->saveImageIntoS3Bucket($request->icon),
            );
            $res  = DB::table('payment_methods')->insert($data);
            return $this->sendResponse([], 'Payment method inserted successfully.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    /*
     *** upate payment method
    */
    public function update(Request $request){
        try{            
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'name' => 'required|max:128|min:2|unique:payment_methods,name,'.$request->id,
                'country'   => 'required|max:128|min:2',
                'icon'      => 'mimes:jpeg,png,jpg,gif,svg|max:2048',
                'is_wallet' => 'in:0,1'
            ],[
                'name.required' => 'Payment method name is required.',
                'name.unique'   => 'Payment method name already been taken.',
                'icon.max'      => 'File should be less than 2MB.',
                'icon.mimes'    => 'File format should be : jpeg, png, jpg, gif, svg',
                'is_wallet.in'  => 'Wallet value invalid'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(),422);  
            }
            $data = array(
                'name'      => $request->name,
                'country'   => $request->country,
                'is_wallet' => $request->is_wallet,
            );
            if($request->icon){
                $data['icon'] = $request->icon;
            }
            $res  = DB::table('payment_methods')->where('id',$request->id)->update($data);
            return $this->sendResponse([], 'Payment method updated successfully.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }    

    /*
     ** Delete payment methods
    */
    public function delete(Request $request){
        try{           
            $res  = DB::table('payment_methods')->where('id',$request->id)->delete();
            if($res){
                return $this->sendResponse([], 'Payment method deleted successfully.');
            }else{
                return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
            }
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

}
