<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Bank;
use DB,Validator;
use Exception;
use Illuminate\Support\Str;


class BankController extends BaseController
{

	public function banks(Request $request){
		try{
            $pagination =   !empty($request->page_entries) ? $request->page_entries : 25 ;
			$banks = new Bank();
            
			if($request->search){
				$banks = $banks->where('bank_name','like','%'.$request->search.'%')
				                ->orWhere('country','like','%'.$request->search.'%');
			}
			$banks = $banks->orderBy('_id','desc')->paginate($pagination);
            //dd($banks);
			return $this->sendResponse($banks, 'success');
		}catch(Exception $e){
			return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
		}
	}

	public function create(Request $request){
        try{            
            $requestData = $request->all();
            if($request->hasFile('icon')) {
                $iconName           = $request->icon;
                $filenamewithExt    = $iconName->getClientOriginalName();
                $filename           = 	pathinfo($filenamewithExt, PATHINFO_FILENAME);
                $extension          = 	strtolower($iconName->getClientOriginalExtension());
                $filenameToStore 	= 	strtolower(str_replace(' ', '_', substr($filename, 0, 5))).'_'.time().rand(11111, 99999).'.'.$extension;
                // $isIconSaved     =   Storage::disk('s3')->put(env('AWS_IMAGES_S3_ENV_URL').'bankIcon/'.$filenameToStore, file_get_contents($iconName));
                $bankData['icon']   = $filenameToStore;
                
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
            // $rr = Str::slug($request->bank_name);
            // dd($rr);
            $bankData = Bank::create([
                'bank_name' => $request->bank_name,
                'country'   =>  $request->country,
                'icon'      =>  $request->icon,
                'is_active' => 1,
            ]);
            return $this->sendResponse($bankData, 'Bank registerd successfully.');
        }catch(Exception $e){
            //dd($e);
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
                'id'        => $request->id
            );
            if($request->icon){
            	$data['icon'] = $request->icon;
            }
            try{
                $bank = Bank::find($request->id);
                if($bank) {
                    $bank->bank_name  = $request->bank_name;
                    $bank->country    = $request->country;
                    $bank->save();
                }
                return $this->sendResponse($data, 'Bank updated successfully.');
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
                $bank->is_active  = 0;
                $bank->save();
                return $this->sendResponse($id, 'Bank deleted successfully.');
            }
            else{
            	return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
			}
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    public function updateBankStatus(Request $request)
    {
        try {
            $requestData = $request->all();
            $id = $requestData['id'];
            $bank = Bank::find($id);
            if($bank) {
                $bank->is_active = $bank->is_active == 0 ? 1 : 0;
                $bank->save();
                $data['id'] = $id;
                $data['is_active'] = $bank->is_active;
                return $this->sendResponse($data, 'Bank status updated successfully.');
            } else {
                return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);
            }
        } catch (Exception $e) {
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);
        }
    }
}
