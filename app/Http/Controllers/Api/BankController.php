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
            foreach($banks as $bankData) {
                $bankData->labels = unserialize($bankData['labels']);
            }
			return $this->sendResponse($banks, 'success');
		}catch(Exception $e){
			return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
		}
	}

	public function create(Request $request){
        try{            
            if($request->hasFile('icon')) {
                $iconName           = $request->icon;
                $filenamewithExt    = $iconName->getClientOriginalName();
                $filename           = 	pathinfo($filenamewithExt, PATHINFO_FILENAME);
                $extension          = 	strtolower($iconName->getClientOriginalExtension());
                $filenameToStore 	= 	strtolower(str_replace(' ', '_', substr($filename, 0, 5))).'_'.time().rand(11111, 99999).'.'.$extension;
                $bankData['icon']   = $filenameToStore;
                
            }
            $validator   = Validator::make($request->all(),[
                'bank_name' => 'required|max:128|min:2',
                'country'   => 'required|max:128',
                'icon'      => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Errorr.', $validator->errors()->first(),422);  
            }
            $bankData = Bank::create([
                'bank_name' => $request->bank_name,
                'country'   =>  $request->country,
                'icon'      =>  $request->icon,
                'is_active' => 1,
                'labels' => serialize(json_decode($request->lables))
            ]);
            $bankData->labels = $request->lables;
            return $this->sendResponse($bankData, 'Bank registerd successfully.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    public function update(Request $request){
        try{            
            $requestData = $request->all();
            $validator   = Validator::make($requestData,[
                'bank_name' => 'required|max:128|min:2',
                'country'   => 'required|max:128|min:2',
                'icon'      => 'nullable'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }

            $data = array(
            	'bank_name'	=> $request->bank_name,
            	'country'	=> $request->country,
                'id'        => $request->id,
                'labels' => serialize(json_decode($request->lables))
            );
            if($request->icon){
            	$data['icon'] = $request->icon;
            }
            try{
                $bank = Bank::find($request->id);
                if($bank) {
                    $bank->bank_name  = $request->bank_name;
                    $bank->country    = $request->country;
                    $bank->icon       = $request->icon;
                    $bank->labels     = serialize(json_decode($request->lables));
                    $bank->save();
                }
                if($request->icon){
                    $data['icon'] = 'https://victorybucket-new.s3.ap-south-1.amazonaws.com/staging/bank/'.$request->icon;
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

    public function search(Request $request)
    {
        //dd($request->all());
        $banks = new Bank();
        $pattern = $request->lablels.'.*'; // "icici.*"
        // if($request->lablels){
        //     //SELECT * FROM table_name WHERE some_field REGEXP '.*"item_key";s:[0-9]+:"item_value".*'

        //     $banks = $banks->where('labels','REGXP',$pattern);
        // }
        // $banks = $banks->orderBy('_id','desc')->get();

        //$documents = DB::connection('mongodb')->collection('banks')->get(['labels']);

        $searchValue = 'icici';
        
       

        $results = DB::connection('mongodb')->collection('banks')
            ->raw(function ($collection) use ($searchValue) {
                return $collection->aggregate([
                    [
                        '$match' => [
                            'labels' => $searchValue,
                        ],
                    ],
                ]);
            });

            $serializedField = [];
            foreach ($results as $result) {
                // Access the serializedField and other fields
                $serializedField = $result->labels;
                // Process other fields as needed
            }
        dd($serializedField);
    }

    public function deleteAll()
    {
        Bank::truncate(); // Deletes all records from the collection
        return $this->sendResponse([], 'All records deleted successfully.');
    }
}
