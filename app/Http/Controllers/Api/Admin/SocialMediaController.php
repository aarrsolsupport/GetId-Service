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
                'contact_no1'       => 'nullable|between:6,12',
                'contact_no2'       => 'nullable|between:6,12',
                'whatsapp1'         => 'nullable|between:6,12',
                'whatsapp2'         => 'nullable|between:6,12',
                'email'             => 'nullable|email',
                'telegram_link'     => 'nullable|url',
                'instagram_link'    => 'nullable|url',
                'facebook_link'     => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(),422);  
            }

            $datavalues = [];
            if($request->contact_no1){
                $datavalues['contact_no1'] = $request->contact_no1;
            }
            if($request->contact_no2){
                $datavalues['contact_no2'] = $request->contact_no2;
            }
            if($request->whatsapp1){
                $datavalues['whatsapp1'] = $request->whatsapp1;
            }
            if($request->whatsapp2){
                $datavalues['whatsapp2'] = $request->whatsapp2;
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
