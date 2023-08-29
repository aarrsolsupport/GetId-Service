<?php

namespace App\Http\Controllers\Api\Agent;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\PaymentMethod;
use App\Models\AdminAccounts;
use Illuminate\Http\Request;
use App\Models\NotifyToAll;
use DB,Validator;
use Aws\Sns\SnsClient;

class NotificationController extends BaseController
{

	public function create(Request $request){
        try{            
            $res = NotifyToAll::create($request->all());
            return $this->sendResponse($res, 'Success');
        }catch(Exception $e){   
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
    }



    public function sendPushNotification(Request $request){
        $message = 'Testing Message';
        $snsClient = new SnsClient([
            'region'    => env('AWS_SMS_DEFAULT_REGION'),
            'version'   => 'latest',
            'credentials' => [
                'key'       => env('AWS_SMS_ACCESS_KEY_ID'),
                'secret'    => env('AWS_SMS_SECRET_ACCESS_KEY'),
            ],
        ]);
        $topicArn = 'arn:aws:sns:your-region:your-account-id:your-topic-name';
        dd($snsClient);

        $snsClient->publish([
            'TopicArn' => $topicArn,
            'Message' => $message,
        ]);

        return response()->json(['message' => 'Push notification sent.']);
    }

    public function test(){
        //The code for generate endpoint ARN
        $platformApplicationArn = config('services.sns.android_arn');

        $client = new SnsClient([
            'version'     => '2010-03-31',
            'region'      => config('services.sns.region'),
            'credentials' => new Credentials(
                config('services.sns.key'),
                config('services.sns.secret')
            ),
        ]);

        $result = $client->createPlatformEndpoint(array(
            'PlatformApplicationArn' => $platformApplicationArn,
            'Token'                  => $deviceToken,
        ));

        $endPointArn = isset($result['EndpointArn']) ? $result['EndpointArn'] : '';

        //The code for send push notification
        $client = new SnsClient([
            'version'     => '2010-03-31',
            'region'      => config('services.sns.region'),
            'credentials' => new Credentials(
                    config('services.sns.key'),
                    config('services.sns.secret')
                ),
            ]);

        $fcmPayload = json_encode(
                [
                    'notification' => 
                        [
                            'title' => 'Test Notification',
                            'body'  => 'Hi from RB',
                            'sound' => 'default',
                        ],
                ]
            );

        $message = json_encode(['GCM' => $fcmPayload]);

        $client->publish([
              'TargetArn'        => $userDeviceToken->endpoint_arn,
              'Message'          => $message,
              'MessageStructure' => 'json',
        ]);
    }
}
