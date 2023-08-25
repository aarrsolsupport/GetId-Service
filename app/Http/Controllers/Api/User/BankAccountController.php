<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use App\Models\User\BankAccount;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use App\Models\Bank;
use Carbon\Carbon;
use DB, Validator;
use App\Models\AdminAccounts;

class BankAccountController extends BaseController
{

    public function bankList()
    {
        try {
            $list = Bank::orderBy('created_at', 'desc')->get();
            $banks = [];
            if (count($list) > 0) {
                foreach ($list as $key => $value) {
                    $banks[$value->country][] = $value;
                }
            }
            return $this->sendResponse($banks, 'success');
        } catch (Exception $e) {
            return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
        }
    }

    public function list(Request $request)
    {
        if ($request->user_id) {
            $data = BankAccount::where('user_id', $request->user_id)
                ->with(['bank' => function ($query) {
                    $query->select('bank_name', 'country', 'icon');
                }])
                ->orderBy('created_at', 'desc')
                ->get();
            return $this->sendResponse($data, 'success');
        }
        return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
    }

    public function create(Request $request)
    {
        try {
            $requestData = $request->all();
            $validator   = Validator::make($requestData, [
                'bank_id' => 'required',
                'user_id' => 'required',
            ], [
                'bank_id.required'  => 'Bank name field is required.',
                'user_id.required'  => 'Something went wrong. Please try again later.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors()->first(), 422);
            }
            if ($request->file) {
                $files = $this->saveImageIntoS3Bucket($request->file, 'bank');
                $requestData['document'] = $files['filename'];
                unset($requestData['file']);
            }
            $requestData['is_active'] = 1;
            $res  = BankAccount::create($requestData);
            return $this->sendResponse($res, 'Bank added successfully.');
        } catch (Exception $e) {
            return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
        }
    }

    public function paymentMethodList(Request $request)
    {
        try {
            $paymentMethods = [];
            $userParentId = $request->parent_id;
            $adminAccountQuery = AdminAccounts::with('payment', 'banks')->where('user_id', $userParentId)->get();
            if (count($adminAccountQuery) > 0) {
                foreach ($adminAccountQuery as $key => $val) {
                    $paymentMethods[$val->type][] = $val;
                }
            }

            return $this->sendResponse($paymentMethods, 'Payment method data.');
        } catch (Exception $e) {
            return $this->sendError('Error.', 'Something went wrong.Please try again later.', 401);
        }
    }
}
