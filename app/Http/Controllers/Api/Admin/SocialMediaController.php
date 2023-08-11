<?php

namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB,Validator;
use App\Models\Admin\SocialMedia;

class SocialMediaController extends BaseController
{
    
    public function socialMediaList(Request $request){
        try{
            $res = SocialMedia::first();
            if($res){
                return $this->sendResponse($res, 'Social Media links listed successfully.');
            }else{
                return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
            }
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }

    public function socialMedia(Request $request){
        try{
            $requestData = $request->all();
            $validator = Validator::make($requestData,[
                'support_no1'       => 'required|between:6,12',
                'support_no2'       => 'required|between:6,12',
                'whatsapp_no1'      => 'required|between:6,12',
                'whatsapp_no2'      => 'required|between:6,12',
                'email'             => 'required|email',
                'telegram_link'     => 'required',
                'instagram_link'    => 'required',
                'facebook_link'     => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(),422);  
            }

            $datavalues = [];
            if($request->support_no1){
                $datavalues['support_no1'] = $request->support_no1;
            }
            if($request->support_no2){
                $datavalues['support_no2'] = $request->support_no2;
            }
            if($request->whatsapp_no1){
                $datavalues['whatsapp_no1'] = $request->whatsapp_no1;
            }
            if($request->whatsapp_no2){
                $datavalues['whatsapp_no2'] = $request->whatsapp_no2;
            }
            if($request->email){
                $datavalues['email'] = $request->email;
            }
            if($request->telegram_link){
                $datavalues['telegram_link'] = $request->telegram_link;
            }
            if($request->instagram_link){
                $datavalues['instagram_link'] = $request->instagram_link;
            }
            if($request->facebook_link){
                $datavalues['facebook_link'] = $request->facebook_link;
            }
            $res = SocialMedia::updateOrCreate(['_id'=>$request->id],$datavalues);
            if($res){
                return $this->sendResponse([], 'Social Media links updated successfully.');
            }else{
                return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
            }
        }catch(Exception $e){
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.',401);  
        }
    }
}
