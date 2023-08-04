<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Bank;
use Illuminate\Http\Request;
use DB,Validator;

class BankController extends BaseController
{

	public function banks(Request $request){
		try{
			$banks = DB::table('banks');
			if($request->search){
				$banks = $banks->where('name','like',$request->search);
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
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'bank_name' => 'required|max:128|min:2|unique:banks',
                'country'   => 'required|max:128|min:2',
                'icon'      => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ],[
            	'icon.max' => 'File should be less than 2MB.',
            	'icon.mimes' => 'File format should be : jpeg, png, jpg, gif, svg',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(),422);  
            }
            $data = array(
            	'bank_name'	=> $request->bank_name,
            	'country'	=> $request->country,
            	'icon'		=> $request->icon,
            	// 'icon'		=> $this->saveImageIntoS3Bucket($request->icon),
            );
            $res  = DB::table('banks')->insert($data);
            return $this->sendResponse([], 'Bank registerd successfully.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    public function update(Request $request){
        try{            
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'bank_name' => 'required|max:128|min:2|unique:banks,bank_name,'.$request->id,
                'country'   => 'required|max:128|min:2',
                'icon'      => 'mimes:jpeg,png,jpg,gif,svg|max:2048'
            ],[
            	'icon.max' => 'File should be less than 2MB.',
            	'icon.mimes' => 'File format should be : jpeg, png, jpg, gif, svg',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(),422);  
            }
            $data = array(
            	'bank_name'	=> $request->bank_name,
            	'country'	=> $request->country,
            );
            if($request->icon){
            	$data['icon'] = $request->icon;
            }
            $res  = DB::table('banks')->where('id',$request->id)->update($data);
            return $this->sendResponse([], 'Bank updated successfully.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }    

    public function delete(Request $request){
        try{           
            $res  = DB::table('banks')->where('id',$request->id)->delete();
            if($res){
				return $this->sendResponse([], 'Bank deleted successfully.');
        	}else{
            	return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
			}
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }
}
