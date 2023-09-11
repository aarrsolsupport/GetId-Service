<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\PaymentMethod;
use App\Models\AdminAccounts;
use Illuminate\Http\Request;
use App\Models\User\GetId;
use Carbon\Carbon;
use App\Models\User;
use DB;

class AgentMonitoringController extends BaseController
{
    /**
     * Method list
     *
     * @param Request $request [explicite description]
     *
     * @return void
     */
    public function list(Request $request)
    {
        try {
            $pagination =   !empty($request->page_entries) ? $request->page_entries : 25;
            $parentId = $request->user_id;

            if ($request->parent_id) {
                $agentIds = json_decode($request->parent_id);
            } else {
                $agentIds =  User::where('client_parent_id', $parentId)->pluck('id')->toArray();
            }

            $walletData = GetId::with('user', 'agent')->whereIn('parent_id', $agentIds);

            $walletData = $walletData->orderBy('created_at', 'desc');

            $type = (int)$request->date_type;
            if ($request->type != '') {
                $walletData = $walletData->where('type', intval($request->type));
            }
            if ($request->status != '') {
                if ($request->status == 'pending') {
                    $status = 0;
                } else {
                    $status = $request->status;
                }
                $walletData = $walletData->where('status', intval($status));
            }

            if ($type != '' && in_array($type, [1, 2, 3, 4])) {
                $today = Carbon::today();
                if ($type == 1) {
                    $walletData = $walletData->where('created_at', '>=', $today);
                } else {
                    $arr = array(2 => 1, 3 => 7, 4 => 30);
                    $d = $arr[$type];
                    $dayInterval = Carbon::now()->subDay($d);
                    $walletData = $walletData->where('created_at', '>=', $dayInterval->startOfDay())
                        ->where('created_at', '<', $today->startOfDay());
                }
            } elseif ($type == 5 && (isset($request->from_date) && isset($request->to_date))) {
                $from = Carbon::createFromFormat('Y-m-d H:i:s', $request->from_date . '00:00:00');
                $to = Carbon::createFromFormat('Y-m-d H:i:s', $request->to_date . '23:59:59');
                $walletData = $walletData->whereBetween('created_at', [$from, $to]);
            }
            if ($request->search) {
                $walletData = $walletData->where('utr_number', 'like', '%' . $request->search . '%');
            }


            $walletData = $walletData->paginate($pagination);

            return $this->sendResponse($walletData, 'success');
        } catch (Exception $e) {
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.', 401);
        }
    }

    /**
     * Method request approve by sub-admin
     *
     * @param Request $request [explicite description]
     *
     * @return void
     */
    public function requestApprove(Request $request)
    {
        $id = $request->id;
        $res  =  GetId::where(['_id' => $id])
            ->update([
                'stake' => $request->stake,
                'utr_number' => (int)$request->utr_number,
                'admin_remarks' => $request->admin_remarks,
                'status' => 1
            ]);
        $resultData = array(
            'id' => $request->id,
            'stake' => $request->stake,
            'utr_number' => (int)$request->utr_number,
            'admin_remarks' => $request->admin_remarks,
            'status' => 1
        );
        if ($res) {
            return $this->sendResponse($resultData, 'Request has been approved successfully.');
        } else {
            return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
        }
    }

    /**
     * Method request Reject
     *
     * @return void
     */
    public function requestReject(Request $request)
    {
        $id = $request->id;
        $res  =  GetId::where(['_id' => $id])
            ->update([
                'admin_remarks' => $request->admin_remarks,
                'status' => 2
            ]);
        $resultData = array(
            'id' => $request->id,
            'admin_remarks' => $request->admin_remarks,
            'status' => 2
        );
        if ($res) {
            return $this->sendResponse($resultData, 'Request has been rejected successfully.');
        } else {
            return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
        }
    }

    /**
     * Method sample data insert in table 
     * this api is not for used for any function, it is only for testing purpose
     * @return void
     */
    public function fakeuserDeposit()
    {
        $requestData['type']      = 2; // deposit
        $requestData['status']    = 0;
        $requestData['admin_account_id'] = 11009;
        $requestData['utr_number'] = 213740800825;
        $requestData['user_id']   = 2020;
        $requestData['parent_id'] = 641;
        $requestData['stake']     = 100;

        $res  = GetId::create($requestData);
        return $this->sendResponse($res, 'data created successfully.');
    }

    /**
     * Method sub-admin agents list
     *
     * @param Request $request [explicite description]
     *
     * @return void
     */
    public function agentList(Request $request)
    {
        try {
            $authId = $request->id;
            if ($authId) {
                $usersWithChildCount = DB::table('users as u1')->where('client_parent_id', $authId)
                    ->select('u1.id', 'u1.userid', 'u1.name', 'u1.last_login_at', DB::raw('(SELECT COUNT(*) FROM users as u2 WHERE u2.client_parent_id = u1.id) as child_count'));
                if (isset($request->search) && $request->search != '') {
                    $usersWithChildCount = $usersWithChildCount->where('u1.userid', 'like', '%' . $request->search . '%');
                }
                $usersWithChildCount = $usersWithChildCount->get();
                return $this->sendResponse($usersWithChildCount, 'Master list.');
            }
        } catch (Exception $e) {
            return $this->sendError('Validation Error.', 'something went wrong,please try again later', 500);
        }
    }
}
