<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\PaymentMethod;
use App\Models\AdminAccounts;
use Illuminate\Http\Request;
use App\Models\Bank;
use DB, Validator;
use App\Models\User\GetId;
use Carbon\Carbon;

class WalletController extends BaseController
{

    public function list(Request $request)
    {
        try {

            $pagination =   !empty($request->page_entries) ? $request->page_entries : 25;
            $walletData = GetId::with('user', 'adminBank')->where('parent_id', $request->user_id)->orderBy('id', 'desc');
            $type = $request->date_type;
            $from = Carbon::create($request->from_date) . ' 00:00:00';
            $to = Carbon::create($request->to_date) . ' 23:59:59';
            //dd($request->all());
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
                if ($type == 1) {
                    $today = Carbon::today();
                    $walletData = $walletData->where('created_at', '>=', $today);
                } else {
                    $arr = array(2 => 1, 3 => 7, 4 => 30);
                    $d = $arr[$type];
                    $dayInterval = Carbon::now()->subDay($d);
                    $walletData = $walletData->where('created_at', '>=', $dayInterval);
                }
            } elseif ($type == 5 && (isset($request->from) && isset($request->to)) && ($from != '' && $to != '')) {
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

    public function create(Request $request)
    {
        try {
            $requestData = array(
                'user_id'       => $request->user_id,
                'payment_method_id'  => $request->type,
                'holder_name'   => $request->holder_name,
                'max_amount'    => $request->max_amount,
                'total_request' => $request->total_request,
            );
            if ($request['type_str'] == 'bank') {
                $requestData['bank_id']     = $request->bank;
                $requestData['wallet_id']   = $request->wallet_id;
                $requestData['type']        = 1;
            }
            if ($request['type_str'] == 'phonepay') {
                $requestData['wallet_id']   = $request->wallet_id;
                $requestData['type']        = 2;
            }
            if ($request['type_str'] != 'phonepay' && $request['type_str'] != 'bank') {
                $requestData['upi_id']   = $request->upi_id;
                $requestData['phone']   = $request->phone;
                if ($request['type_str'] == 'paytm') {
                    $requestData['type']        = 4;
                } else {
                    $requestData['type']        = 3;
                }
            }
            if (isset($request->label1)) {
                $requestData['label1']   = $request->label1;
            }
            if (isset($request->label2)) {
                $requestData['label2']   = $request->label2;
            }
            if (isset($request->label3)) {
                $requestData['label3']   = $request->label3;
            }
            if (isset($request->label4)) {
                $requestData['label4']   = $request->label4;
            }
            if ($request->qrCode) {
                $requestData['qrCode'] = $request->qrCode;
            }
            if ($request->id) {
                $res  = AdminAccounts::updateOrCreate(['id' => $request->id, 'user_id' => $request->user_id], $requestData);
                $res->bank_name = '';
                $res->payment_method_name = '';
                if ($res->payment_method_id == 1) {
                    $res->payment_method_name = 'Bank';
                } else {
                    $methods = PaymentMethod::where('_id', $res->payment_method_id)->first();
                    $res->payment_method_name = $methods->name;
                }

                $banksss = Bank::where('_id', $res->bank_id)->first();
                if ($banksss) {
                    $res->bank_name = $banksss->bank_name;
                }
                return $this->sendResponse($res, 'Bank Account updated successfully.');
            } else {
                $res  = AdminAccounts::create($requestData);
                return $this->sendResponse($res, 'Bank Account created successfully.');
            }
        } catch (Exception $e) {
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.', 401);
        }
    }

    public function delete($id, $user_id)
    {
        try {
            $res  =  AdminAccounts::where(['id' => $id, 'user_id' => $user_id])->update(['is_deleted' => 1]);
            if ($res) {
                return $this->sendResponse([], 'Bank account deleted successfully.');
            } else {
                return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
            }
        } catch (Exception $e) {
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.', 401);
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            if ($request->id && $request->user_id) {
                $res  =  AdminAccounts::where(['id' => $request->id, 'user_id' => $request->user_id])->update(['status' => (int) $request->status]);
                if ($res) {
                    return $this->sendResponse([], 'Bank account status change successfully.');
                } else {
                    return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
                }
            }
        } catch (Exception $e) {
            return $this->sendError('Validation Error.', 'Something went wrong.Please try again later.', 401);
        }
    }
}
