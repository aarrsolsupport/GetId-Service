<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Bank;
use Illuminate\Http\Request;
use DB,Validator;
use Exception;
use Illuminate\Support\Facades\Storage;
class BankController extends BaseController
{

	public function banks(Request $request){
		try{
            $pagination =   !empty($request->page_entries) ? $request->page_entries : 25 ;
			$banks = new Bank();
            
			// if($request->search){
			// 	$banks = $banks->where('name','like',$request->search);
			// 	//$banks = $banks->orWhere('country','like',$request->search);
			// }
			// $banks = $banks->orderBy('id','desc')->paginate($pagination);
            $banks = DB::connection('mongodb')->collection('banks')->paginate($pagination);
			return $this->sendResponse($banks, 'success');
		}catch(Exception $e){
			return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
		}
	}

	public function create(Request $request){
        try{            
            $requestData = $request->all();
            if($request->hasFile('icon')) {
                $iconName = $request->icon;
                $filenamewithExt = $iconName->getClientOriginalName();
                $filename = 	pathinfo($filenamewithExt, PATHINFO_FILENAME);
                $extension = 	strtolower($iconName->getClientOriginalExtension());
                $filenameToStore 	= 	strtolower(str_replace(' ', '_', substr($filename, 0, 5))).'_'.time().rand(11111, 99999).'.'.$extension;
                // $isIconSaved     =   Storage::disk('s3')->put(env('AWS_IMAGES_S3_ENV_URL').'bankIcon/'.$filenameToStore, file_get_contents($iconName));
                $bankData['icon'] = $filenameToStore;
                
            }
            // return ($isIconSaved);
            // $validator   = Validator::make($requestData,[
            //     'bank_name' => 'required|max:128|min:2|unique:banks',
            //     //'country'   => 'required|max:128|min:2',
            //     'country'   => 'required|max:128',
            //     'icon'      => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048'
            // ],[
            // 	'icon.max' => 'File should be less than 2MB.',
            // 	'icon.mimes' => 'File format should be : jpeg, png, jpg, gif, svg',
            // ]);

            // if ($validator->fails()) {
            //     return $this->sendError('Validation Error.', $validator->errors(),422);  
            // }

            $bankData = Bank::create([
                'bank_name'            => $request->bank_name,
                'country'          =>  $request->country,
                'icon'           =>  $request->icon,
            ]);
            return $this->sendResponse([], 'Bank registerd successfully.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    public function update(Request $request){
        try{            
            $requestData = $request->all();
            // $validator   = Validator::make($requestData,[
            //     'bank_name' => 'required|max:128|min:2|unique:banks,bank_name,'.$request->id,
            //     'country'   => 'required|max:128|min:2',
            //     'icon'      => 'mimes:jpeg,png,jpg,gif,svg|max:2048'
            // ],[
            // 	'icon.max' => 'File should be less than 2MB.',
            // 	'icon.mimes' => 'File format should be : jpeg, png, jpg, gif, svg',
            // ]);

            // if ($validator->fails()) {
            //     return $this->sendError('Validation Error.', $validator->errors(),422);  
            // }

            $data = array(
            	'bank_name'	=> $request->bank_name,
            	'country'	=> $request->country,
            );
            if($request->icon){
            	$data['icon'] = $request->icon;
            }
            try{
                $bank = Bank::find($request->id);
                if($bank) {
                    $bank->bank_name = $request->bank_name;
                    $bank->country = $request->country;
                    $bank->save();
                }
                return $this->sendResponse([], 'Bank updated successfully.');
            }
            catch(Exception $e){
                return $this->sendError('Validation Error.', 'Data not found',404);  
            }
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }    

    public function delete($id){
        try{         
            $bank = Bank::find($id);
            if($bank){
                //$bank->delete();
                return $this->sendResponse([], 'Bank deleted successfully.');
            }
            else{
            	return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
			}
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }
}
