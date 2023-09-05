<?php

namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\UserNumber;
use App\Models\User;
use DB,Validator;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class UserNumberController extends BaseController
{
	
	/**
	 * Method User Number list
	 * created by: aakash gupta
	 * @param Request $request [explicite description]
	 *
	 * @return void
	*/
	public function userNumberList(Request $request){
        try {
            $pagination =   !empty($request->page_entries) ? $request->page_entries : 25 ;
            $userIds = [];
            $userNumber = new UserNumber();
            $type = $request->date_type;
            $from = Carbon::create($request->from_date);
            $to = Carbon::create($request->to_date);
            
            if($type != '' && in_array($type,[1,2,3,4])){
                if($type==1){
                    $today = Carbon::today();
                    $userNumber = $userNumber->where('created_at','>=',$today);
                }else{
                    $arr = array(2=>1,3=>7,4=>30);
                    $d = $arr[$type];
                    $dayInterval = Carbon::now()->subDay($d);
                    $userNumber = $userNumber->where('created_at', '>=',$dayInterval);
                }
            }elseif($type==5){
                $userNumber = $userNumber->whereBetween('created_at',[$from,$to]);
            }
            
            if($request->search){
            	$userNumber = $userNumber->where('name','like','%'.$request->search.'%')
            	                ->orWhere('phone','like','%'.$request->search.'%');
            }
            if($request->master_id) {
                $masterId = intval($request->master_id);
                $userNumber = $userNumber->where('user_id','=',$masterId);
            }    
            $userNumberData = $userNumber->select('_id','user_id','is_saved','is_called')->paginate($pagination);
            $userIds = array_unique($userNumberData->pluck('user_id')->toArray());
            
            
            if($userNumberData->count() > 0) {
                $userQuery = User::select('id','userid as mainUserId','phone','client_parent_id as userParentId', 'client_parent_userid as userParentName' )->whereIn('id',$userIds)->get();
                foreach ($userNumberData as $key => $value) {
                    foreach ($userQuery as $userKey => $userValue) {
                        if($value['user_id'] == $userValue['id']) {
                            $value['mainUserId'] = $userValue['mainUserId'];
                            $value['userParentId'] = $userValue['userParentId'];
                            $value['userParentName'] = $userValue['userParentName'];
                            $value['phone'] = $userValue['phone'];
    
                        }
                    }
                }
            }
            return $this->sendResponse($userNumberData, 'success');
        } catch(Exception $e){
            dd($e->getMessage());
         	return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
	}
    
    /**
     * Method isSaved to change the is saved property
     * created by: aakash gupta
     * @param Request $request [explicite description]
     *
     * @return void
     */
    public function isSaved(Request $request)
    {
        try {
            $id = $request->id;
            $userNumber = UserNumber::find($id);
            if($userNumber) {
                $userNumber->is_saved = $userNumber->is_saved == 0 ? 1 : 0;
                $userNumber->save();
                $data['id'] = $id;
                $data['is_saved'] = $userNumber->is_saved;
                return $this->sendResponse($data, 'Data updated successfully.');
            } else {
                return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);
            }

        } catch (Exception $e) {
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);
        }
    }
    
    /**
     * Method isCalled to change the is called property
     * created by: aakash gupta
     * @param Request $request [explicite description]
     *
     * @return void
     */
    public function isCalled(Request $request)
    {
        try {
            $id = $request->id;
            $userNumber = UserNumber::find($id);
            if($userNumber) {
                $userNumber->is_called = $userNumber->is_called == 0 ? 1 : 0;
                $userNumber->save();
                $data['id'] = $id;
                $data['is_called'] = $userNumber->is_called;
                return $this->sendResponse($data, 'Data updated successfully.');
            } else {
                return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);
            }

        } catch (Exception $e) {
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);
        }
    }
	
	/**
	 * create method to add data in the table for testing purpose
     * 
	 * created by: aakash gupta
	 * @param Request $request [explicite description]
	 *
	 * @return void
	 */
	public function create(Request $request){
        try{            
            $userDetail = UserNumber::create([
                'name' => 'virendera',
                'user_id' => 24,
                'country_id' =>  3,
                'state_id' =>  4,
                'city_id' => 5,
                'promo_code' => 'AAbbTr',
                'is_saved' => 0,
                'is_called' => 1,
            ]);
            return $this->sendResponse($userDetail, 'Details has been saved.');
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'something went wrong,please try again later',401);  
        }
    }

    public function masterList(Request $request)
    {
        try {
            $authId = $request->id;
            if($authId) {
                $masterList = User::select('id','userid','name')->where('client_parent_id', $authId)->get();
                return $this->sendResponse($masterList, 'Master list.');
            }
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'something went wrong,please try again later',500);  
        }
        
    }

}
