<?php


namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Storage;


class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message){
    	$response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];
        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404){
    	$response = [
            'success' => false,
            'message' => $error,
        ];
        if(!empty($errorMessages)){
            $response['errors'] = $errorMessages;
        }
        return response()->json($response, $code);
    }

    public function saveImageIntoS3Bucket($file) {
        $icon_name = time().".".$file->getClientOriginalExtension();
        $is_provider_image_saved = Storage::disk('s3')->put('/exchange-icons/'.$icon_name, file_get_contents($file));
        $file_full_path = Storage::disk('s3')->url('/exchange-icons/'.$icon_name);
        return ['status'=> $is_provider_image_saved, 'file_path'=> $file_full_path];
    }

    public function deleteImageIntoS3Bucket($file_path) {
        $image_path = str_replace('https://victory-bucket.s3.ap-south-1.amazonaws.com', '', $file_path);
        Storage::disk('s3')->delete($image_path);
        return true;
    }
}