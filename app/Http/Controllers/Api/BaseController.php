<?php


namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Facades\Storage;


class   BaseController extends Controller
{
    private $getId_request_types = [
        2 => 'Deposit',
        3 => 'Withdraw',
    ];

    private $getId_request_status = [
        0 => 'Pending',
        1 => 'Accepted',
        2 => 'Rejected',
        3 => 'Cancelled',
    ];
    
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

    public function saveImageIntoS3Bucket($file,$endPath='') {
        $filenamewithExt    =   $file->getClientOriginalName();
        $filename           =   pathinfo($filenamewithExt, PATHINFO_FILENAME);
        $extension          =   strtolower($file->getClientOriginalExtension());
        $filenameToStore    =   strtolower(str_replace(' ', '_', substr($filename, 0, 5))).'_'.time().rand(11111, 99999).'.'.$extension;
        $is_image_saved     =   Storage::disk('s3')->put('/staging/'.$endPath.'/'.$filenameToStore, file_get_contents($file));
        
        $res['status'] = $is_image_saved;
        $res['filename'] = $filenameToStore;
        return $res;
    }

    public function deleteImageIntoS3Bucket($file_path) {
        $image_path = str_replace('https://victory-bucket.s3.ap-south-1.amazonaws.com', '', $file_path);
        Storage::disk('s3')->delete($image_path);
        return true;
    }

    public function getYesterdayWeekStartAndEndDate(){
        $res['start']   = date('Y-m-d',strtotime("-1 days"))." 00:00:00";
        $res['end']     = date('Y-m-d',strtotime("-1 days"))." 23:59:59";
        return $res;
    }

    public function getCurrentWeekStartAndEndDate(){
        $monday = strtotime('next Monday -1 week');
        $monday = date('w', $monday)==date('w') ? strtotime(date("Y-m-d",$monday)." +7 days") : $monday;
        $sunday = strtotime(date("Y-m-d",$monday)." +6 days");

        $res['start'] = date("Y-m-d",$monday)." 00:00:00";
        $res['end'] = date("Y-m-d",$sunday)." 23:59:59";
        return $res;
    }

    public function getLastWeekStartAndEndDate(){
        $date = strtotime(date('Y-m-d'));
        $previous_monday = strtotime("last week monday",$date);
        $previous_sunday= strtotime("last week sunday",$date);
        $res['start'] = date('Y-m-d',$previous_monday)." 00:00:00";
        $res['end'] = date('Y-m-d',$previous_sunday)." 23:59:59";
        return $res;
    }
}