<?php

namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB,Validator;
use App\Models\Admin\Poster;

class PosterController extends BaseController
{
    
    /*
     *** List Payment methods
    */
    public function list(Request $request){
        try{
            $methods = new Poster();
            if($request->search && $request->search!='all'){
                $methods = $methods->where('type',$request->search);
            }
            $paginate = $request->paginate??config('constants.pagination');
            $methods = $methods->whereIn('is_active',[1,"1"])
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
                'type' => [
                    'required','numeric','between:1,11',
                    function ($attribute, $value, $fail) {
                        $existingPoster = Poster::where('type', $value)->first();

                        if ($existingPoster) {
                            $fail('The '.$attribute.' has already been taken.');
                        }
                    },
                ],
                'image' => 'required',
            ],[
                'type.between'  => 'Invalid Type value.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }
            $data = array(
                'type'          => $request->type,
                'is_active'     => 1,
                'image'         => $request->image,
            );
            // if($request->image){
            //     $file = $this->saveImageIntoS3Bucket($request->image,'posters');
            //     $data['image'] = $file['filename']
            // }
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
                'type' => [
                    'required',
                    function ($attribute, $value, $fail)  use($request){
                        $existingPoster = Poster::where('type', $value)->whereNotIn('_id',[$request->id])->first();

                        if ($existingPoster) {
                            $fail('The '.$attribute.' has already been taken.');
                        }
                    },
                ],
                'image' => 'nullable',
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
