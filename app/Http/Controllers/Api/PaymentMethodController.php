<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use DB,Validator;
use App\Models\PaymentMethod;
use Illuminate\Validation\Rule;

class PaymentMethodController extends BaseController
{
    public function __construct(){

    }
    
    /*
     *** List Payment methods
    */
    public function paymentMethods(Request $request){
        try{
            $methods = new PaymentMethod();
            if($request->search){
                $methods = $methods->where('name','like','%'.$request->search.'%');
            }
            $paginate = $request->paginate??config('constants.pagination');            
            
            $methods = $methods->whereIn('is_active',['1',1])
                            ->orderBy('created_at','desc')
                            ->paginate($paginate);
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
                'name'          => 'required|max:128|min:2',
                'icon'          => 'required',
            ],[
                'name.required' => 'Payment method name is required.',
                'name.unique'   => 'Payment method name already been taken.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }
            // $file = $this->saveImageIntoS3Bucket($request->icon,'payment-method');
            $data = array(
                'name'          => $request->name,
                'icon'          => $request->icon,
                'is_active'     => 1,
            );
            $res  = PaymentMethod::create($data);
            return $this->sendResponse($res, 'Payment method inserted successfully.');
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
                'name'       => 'required|max:128|min:2',
                'icon'       => 'nullable',
            ],[
                'name.required' => 'Payment method name is required.',
                'name.unique'   => 'Payment method name already been taken.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }
            $data['name'] = $request->name;
            if($request->icon){
                $data['icon'] = $request->icon;
            }
            $res  = PaymentMethod::where('_id',$request->id)->update($data);
            $img_url = env('AWS_IMAGE_URL').'payment-method/'.$data['icon'];
            return $this->sendResponse(['icon'=>$img_url], 'Payment method updated successfully.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }    

    /*
     ** Delete payment methods
    */
    public function delete($id){
        try{           
            $res  = PaymentMethod::find($id);
            if($res){
                $res->is_active = 0;
                $res->save();
                return $this->sendResponse([], 'Payment method deleted successfully.');
            }else{
                return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
            }
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

}
