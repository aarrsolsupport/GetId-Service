<?php

namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB,Validator;
use App\Models\Admin\Poster;

class PosterController extends BaseController
{
    public function __construct(){

    }
    
    /*
     *** List Payment methods
    */
    public function list(Request $request){
        try{
            $methods = new Poster();
            if($request->search){
                $methods = $methods->where('type','like','%'.$request->search.'%');
            }
            $paginate = $request->paginate??config('constants.pagination');
            $methods = $methods->orderBy('created_at','desc')
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
                'type'     => 'required',
                'image'    => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(),422);  
            }
            $file = $this->saveImageIntoS3Bucket($request->image,'posters');
            $data = array(
                'type'          => $request->type,
                'is_active'     => 1,
                'image'          => $file['filename']?$file['filename']:null,
            );
            $res  = Poster::create($data);
            return $this->sendResponse($res, 'Poster inserted successfully.');
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
                'type'     => 'required',
                'image'    => 'nullable',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(),422);  
            }
            $data = array(
                'type'      => $request->type,
            );
            if($request->image){
                $file = $this->saveImageIntoS3Bucket($request->image,'posters');
                $data['icon'] = $file['filename'];
            }
            $res  = Poster::where('_id',$request->id)->update($data);
            if($res){
                return $this->sendResponse($res, 'Poster updated successfully.');
            }else{
                return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
            }
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }    

    /*
     ** Delete payment methods
    */
    public function delete($id){
        try{           
            $res  = Poster::where('_id',$id)->update(['is_active'=>0]);
            if($res){
                return $this->sendResponse([], 'Poster deleted successfully.');
            }else{
                return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
            }
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
    }
}
