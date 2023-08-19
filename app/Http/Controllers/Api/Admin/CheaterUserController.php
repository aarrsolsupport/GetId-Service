<?php

namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB,Validator;
use App\Models\Admin\CheaterUser;

class CheaterUserController extends BaseController
{
    public function list(Request $request){
        try{
            $methods = new CheaterUser();
            if($request->search){
                $methods = $methods->where('user_name','LIKE','%'.$request->search.'%')
                                    ->orWhere('parent_name','LIKE','%'.$request->search.'%')
                                    ->orWhere('mobile_no','LIKE','%'.$request->search.'%');
            }
            $paginate = $request->paginate??config('constants.pagination');
            $methods = $methods->orderBy('created_at','desc')
                            ->paginate($paginate);
            return $this->sendResponse($methods, 'success');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

     public function create(Request $request){
        try{            
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'user_id' => [
                    'required',
                    function ($attribute, $value, $fail) use($request){
                        $existingPoster = CheaterUser::where('user_id', $value)->first();
                        if ($existingPoster) {
                            $fail('The '.$request->user_name.' has already been taken.');
                        }
                    },
                ],
                'user_name'     => 'required',
                'parent_id'     => 'required',
                'parent_name'   => 'required',
                'mobile_no'     => 'required|between:6,12',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }
            $res  = CheaterUser::create($requestData);
            return $this->sendResponse($res, 'User add in cheater user successfully.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    public function delete($id){
        try{           
            $res  = CheaterUser::find($id);
            if($res){
                $res->delete();
                return $this->sendResponse([], 'User deleted successfully.');
            }else{
                return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
            }
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
    }
}
