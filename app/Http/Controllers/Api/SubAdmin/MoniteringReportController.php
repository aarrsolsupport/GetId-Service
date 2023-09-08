<?php

namespace App\Http\Controllers\Api\SubAdmin;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User\UserFirstWithdrawDepositRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use App\Models\User\GetId;
use App\Models\User;
use Carbon\Carbon;
use DB,Validator;

class MoniteringReportController extends BaseController
{

	public function userCreateCountList(Request $request){
		try{
			if($request->user_id){
				$parentId = $request->user_id;
				$list = User::with(['parent'=>function($query){
							$query->select('id','userid','phone','client_parent_id');
						}])
						->where(function ($query) use ($parentId){
							$query->where('client_parent_id', $parentId)
							    ->orWhereIn('client_parent_id', function ($query) use ($parentId) {
							        $query->select('id')
							            ->from('users')
							            ->where('client_parent_id', $parentId);
							    });
						});
						
	            if($request->search && $request->search!='all'){
           			$list = $list->whereBetween('created_at',[$request->from,$request->to]);
	            }
	            $paginate = $request->paginate??config('constants.pagination');
	            $list = $list->orderBy('created_at','desc')->paginate($paginate);

	            return $this->sendResponse($list, 'success');
			}else{
           	 	return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
			}
        }catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function firstDepositCountList(Request $request){
		try{
			$userIds = User::where('client_parent_id',$request->user_id)->pluck('id')->toArray();
			$list = UserFirstWithdrawDepositRequest::whereIn('parent_id',$userIds)
					->where('type',2)
					->with(['user'=>function($query){
						$query->select('id','userid','phone');
					}])
					->with(['parent'=>function($query){
						$query->select('id','userid','phone','client_parent_id');
					}]);
			if($request->search && $request->search!='all'){
				$startDate 	= Carbon::parse($request->from);
				$endDate 	= Carbon::parse($request->to);
       			$list 		= $list->whereBetween('created_at',[$startDate,$endDate]);
            }
			$paginate = $request->paginate??config('constants.pagination');
            $list = $list->orderBy('created_at','desc')->paginate($paginate);
	        return $this->sendResponse($list, 'success');
     	}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function recurringDepositCount(Request $request){
		try{
			$users 		= User::where('client_parent_id',$request->user_id)->select('id','userid','phone')->get();
			$parent_ids	= $users->pluck('id')->toArray();
			$firstUsers = UserFirstWithdrawDepositRequest::whereIn('parent_id',$parent_ids)
							->where('type',2)
							->get();

			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$page 		= (int)$request->page?:1;
			$skip  		= ($page - 1) * $paginate;
			
			$match['parent_id'] = ['$in'=>$parent_ids];
			$match['type'] 		= 2;
			$match['status'] 	= 1;
			if(count($firstUsers)>0){
				$getid_request_id 	= $firstUsers->pluck('getid_request_id')->toArray();
				$getid_request_id 	= $this->convertIdsIntoMongoObject($getid_request_id);
				$match['_id'] 		= ['$nin' =>$getid_request_id];
			}
			if($request->search && $request->search!='all'){
				$match['created_at'] = [
					'$gte' 	=> new \MongoDB\BSON\UTCDateTime(Carbon::createFromFormat("Y-m-d H:i:s", $request->from)->startOfDay()),
					'$lte'	=> new \MongoDB\BSON\UTCDateTime(Carbon::createFromFormat("Y-m-d H:i:s", $request->to)->endOfDay())
				];
            }
			$records = GetId::raw(function ($collection) use($match,$skip,$paginate) {
			    return $collection->aggregate([
			    	[ 	'$match' => $match ],
			        [
			            '$group' => [
				            '_id' 	=> '$user_id',
				            'data' 	=> ['$first' => '$$ROOT'],
				            'count' => ['$sum' => 1],
				        ],
			        ],
			        [ 	'$addFields' 	=> ["data.count" => '$count'] ],
			        [ 	'$replaceRoot' 	=> ['newRoot' =>'$data'] ],
			        [ 	'$sort' 		=> ['_id'=>-1] ],
			        [
				        '$facet' => [
				            'data' => [
				                [
				                    '$sort' => [
				                        '_id' => -1,
				                    ],
				                ],
				                [ '$skip' => $skip, ],
				                [ '$limit' => $paginate, ],
				            ],
				            'count' => [
				                [
				                    '$count' => 'total',
				                ],
				            ],
				        ],
				    ],
				    [
				        '$project' => [
				            'data' => 1,
				            'count' => [
				                '$arrayElemAt' => ['$count.total', 0],
				            ],
				        ],
				    ],			        
			    ]);
			});

			$list 			= $records[0]['data'];
			$totalRecords 	= $records[0]['count'];
			if(count($list)>0){
				foreach($list as $key => $value){
					$user = $firstUsers->where('user_id',$value->user_id)->first();
					if($user){
						$value->userid = $user->user->userid;
						$value->phone = $user->user->phone;

					}
					$parent = $users->where('id',$value->parent_id)->first();
					if($parent){
						$value->parent_userid = $parent->userid;
					}
				}
			}
			$list = $this->covertListIntoPaginator($list,$paginate,$totalRecords);
			return $this->sendResponse($list, 'success');
     	}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}	

	public function userTotalDepositAmounts(Request $request){
		try{
			$agents = User::where('client_parent_id',$request->user_id)
						->select('id','userid','phone')->get();
			$agentids = $agents->pluck('id')->toArray();
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$page 		= (int)$request->page?:1;
			$skip  		= ($page - 1) * $paginate;
			$match['parent_id'] = ['$in' => $agentids];
			$match['type'] = 2;
			$match['status'] = 1;
			if($request->search && $request->search!='all'){
				$match['created_at'] = [
					'$gte' 	=> new \MongoDB\BSON\UTCDateTime(Carbon::createFromFormat("Y-m-d H:i:s", $request->from)->startOfDay()),
					'$lte'	=> new \MongoDB\BSON\UTCDateTime(Carbon::createFromFormat("Y-m-d H:i:s", $request->to)->endOfDay())
				];
            }

			$records = GetId::raw(function ($collection) use($match,$request,$paginate,$skip) {
			    return $collection->aggregate([
			    	[ 	'$match' => $match ],
			        [
			            '$group' => [
			                '_id' 		  	=> '$user_id',
			                'totalAmount' 	=> ['$sum' 	=> '$stake'],
			                'data' 		  	=> ['$first' 	=> '$$ROOT'],
			            ],
			        ],
			        [ 	'$addFields' 	=> [ "data.totalStake" => '$totalAmount'] ],
			        [	'$replaceRoot'	=> ['newRoot' =>'$data'] ],
			        [ 	'$sort' 		=> ['_id'=>-1] ],
			        [
				        '$facet' => [
				            'data' => [
				                [
				                    '$sort' => [
				                        '_id' => -1,
				                    ],
				                ],
				                [ '$skip' => $skip ],
				                [ '$limit' => $paginate],
				            ],
				            'count' => [
				                [
				                    '$count' => 'total',
				                ],
				            ],
				        ],
				    ],
				    [
				        '$project' => [
				            'data' => 1,
				            'count' => [
				                '$arrayElemAt' => ['$count.total', 0],
				            ],
				        ],
				    ],
			    ]);
			});
			$list 			= $records[0]['data'];
			$totalRecords 	= $records[0]['count'];

			if(count($list)>0){
				$dts = $list;
				$users = User::whereIn('id',array_column((array)$dts,'user_id'))
								->with(['parent'=>function($query){
									$query->select('id','userid');
								}])
								->get();
				foreach($list as $key => $value){
					$user = $users->where('id',$value->user_id)->first();
					if($user){
						$value->userid 	= $user->userid;
						$value->phone 	= $user->phone;
						$value->parent_userid = $user->parent->userid;

					}
				}
			}
			$list = $this->covertListIntoPaginator($list,$paginate,$totalRecords);
			return $this->sendResponse($list, 'success');
     	}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function userTotalWithdrawAmounts(Request $request){
		try{
			$agents 	= User::where('client_parent_id',$request->user_id)->select('id','userid','phone')->get();
			$agentids 	= $agents->pluck('id')->toArray();
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$page 		= (int)$request->page?:1;
			$skip  		= ($page - 1) * $paginate;
			$match['parent_id'] = ['$in' => $agentids];
			$match['type'] 		= 3;
			$match['status'] 	= 1;
			if($request->search && $request->search!='all'){
				$match['created_at'] = [
					'$gte' 	=> new \MongoDB\BSON\UTCDateTime(Carbon::createFromFormat("Y-m-d H:i:s", $request->from)->startOfDay()),
					'$lte'	=> new \MongoDB\BSON\UTCDateTime(Carbon::createFromFormat("Y-m-d H:i:s", $request->to)->endOfDay())
				];
            }

			$records = GetId::raw(function ($collection) use($match,$request,$paginate,$skip) {
			    return $collection->aggregate([
			    	[ 	'$match' => $match ],
			        [
			            '$group' => [
			                '_id' 		  	=> '$user_id',
			                'totalAmount' 	=> ['$sum' 	=> '$stake'],
			                'data' 		  	=> ['$first' 	=> '$$ROOT'],
			            ],
			        ],
			        [ 	'$addFields' 	=> [ "data.totalStake" => '$totalAmount'] ],
			        [	'$replaceRoot'	=> ['newRoot' =>'$data'] ],
			        [ 	'$sort' 		=> ['_id'=>-1] ],
			        [
				        '$facet' => [
				            'data' => [
				                [
				                    '$sort' => [
				                        '_id' => -1,
				                    ],
				                ],
				                [ '$skip' => $skip ],
				                [ '$limit' => $paginate],
				            ],
				            'count' => [
				                [
				                    '$count' => 'total',
				                ],
				            ],
				        ],
				    ],
				    [
				        '$project' => [
				            'data' => 1,
				            'count' => [
				                '$arrayElemAt' => ['$count.total', 0],
				            ],
				        ],
				    ],
			    ]);
			});
			$list 			= $records[0]['data'];
			$totalRecords 	= $records[0]['count'];

			if(count($list)>0){
				$dts = $list;
				$users = User::whereIn('id',array_column((array)$dts,'user_id'))
								->with(['parent'=>function($query){
									$query->select('id','userid');
								}])
								->get();
				foreach($list as $key => $value){
					$user = $users->where('id',$value->user_id)->first();
					if($user){
						$value->userid 	= $user->userid;
						$value->phone 	= $user->phone;
						$value->parent_userid = $user->parent->userid;

					}
				}
			}
			$list = $this->covertListIntoPaginator($list,$paginate,$totalRecords);
			return $this->sendResponse($list, 'success');
     	}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function profitLoss(Request $request){
		try{
			$agents 	= User::where('client_parent_id',$request->user_id)->select('id','userid','phone')->get();
			$agentids 	= $agents->pluck('id')->toArray();
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$page 		= (int)$request->page?:1;
			$skip  		= ($page - 1) * $paginate;

			$match['parent_id'] = ['$in' => $agentids];
			$match['status'] 	= 1;
			$match['type']		= ['$in'=>[2,3]];
			$aggregate = [
		    	[ 	'$match' => $match ],
		        [
		            '$group' => [
		                '_id' => [
		                    'user_id' => '$user_id',
		                    'type' => '$type',
		                ],
		                'totalStake' => ['$sum' => '$stake'],
		            ],
		        ],
		        [
		            '$group' => [
		                '_id' => '$_id.user_id',
		                'typeTotals' => [
		                    '$push' => [
		                        'type' => '$_id.type',
		                        'totalStake' => '$totalStake',
		                    ],
		                ],
		            ],
		        ],
		        [
		            '$project' => [
		                '_id' => 0,
		                'user_id' => '$_id',
		                'typeTotals' => 1,
		            ],
		        ],
		        [
		            '$unwind' => '$typeTotals',
		        ],
		        [
		            '$group' => [
		                '_id' => '$user_id',
		                'typeTotals' => [
		                    '$push' => '$typeTotals',
		                ],
		            ],
		        ],
		        [
		            '$project' => [
		                '_id' => 0,
		                'user_id' => '$_id',
		                'typeTotals' => 1,
		            ],
		        ],
		        [
		            '$project' => [
		                'user_id' => 1,
		                'typeTotals' => [
		                    '$reduce' => [
		                        'input' => '$typeTotals',
		                        'initialValue' => [
		                            'type2Total' => 0,
		                            'type3Total' => 0,
		                        ],
		                        'in' => [
		                            'type2Total' => [
		                                '$cond' => [
		                                    'if' => ['$eq' => ['$$this.type', 2]],
		                                    'then' => ['$add' => ['$$value.type2Total', '$$this.totalStake']],
		                                    'else' => '$$value.type2Total',
		                                ],
		                            ],
		                            'type3Total' => [
		                                '$cond' => [
		                                    'if' => ['$eq' => ['$$this.type', 3]],
		                                    'then' => ['$add' => ['$$value.type3Total', '$$this.totalStake']],
		                                    'else' => '$$value.type3Total',
		                                ],
		                            ],
		                        ],
		                    ],
		                ],
		            ],
		        ],
		        [
		            '$project' => [
		                '_id' 			=> 0,
		                'user_id' 		=> 1,
		                'type2Total' 	=> '$typeTotals.type2Total',
		                'type3Total' 	=> '$typeTotals.type3Total',
		                'subtraction' 	=> ['$subtract' => ['$typeTotals.type3Total', '$typeTotals.type2Total']],
		            ],
		        ],
		        [
			        '$facet' => [
			            'data' => [
			                ['$skip' => $skip],
			                ['$limit' => $paginate],
			            ],
			            'total' => [
			                ['$count' => 'total'],
			            ],
			        ],
			    ],
			    [
			        '$project' => [
			            'data' => '$data',
			            'count' => [
			                '$arrayElemAt' => ['$total.total', 0],
			            ],
			        ],
			    ],	        
		    ];

			$records = GetId::raw(function ($collection) use($aggregate,$match,$skip,$paginate) {
			    return $collection->aggregate($aggregate);
			});
			$list 			= $records[0]['data'];
			$totalRecords 	= $records[0]['count'];
			if(count($list)>0){
				$userIds 	= array_unique(array_column((array)$list,'user_id'));
				$users 		= User::whereIn('id',$userIds)
								->with(['parent'=>function($query){
										$query->select('id','userid');
									}])
								// ->select('id','userid','role_id','name','phone')
								->get();

				foreach($list as $key => $value){
					$user = $users->where('id',$value->user_id)->first();
					if($user){
						$value->userid = $user->userid;
						$value->phone = $user->phone;
						$value->parent_userid = $user->parent->userid;
					}
				}
			}

			$list = $this->covertListIntoPaginator($list,$paginate,$totalRecords);
			return $this->sendResponse($list, 'success');
		}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function totalUsers(Request $request){
		try{
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$users = User::whereHas('parent.parent', function ($query) use ($request) {
			    $query->where('id', $request->user_id);
			});
			if($request->search && $request->search!='all'){
       			$users = $users->whereBetween('created_at',[$request->from,$request->to]);
            }
			$users = $users->with(['parent'=>function($q){
				$q->select('id', 'client_parent_id', 'userid', 'created_at','last_login_at');
			}])			
			->select('id', 'client_parent_id', 'userid', 'created_at','last_login_at','phone')
			->paginate($paginate);

			$userIds = $users->pluck('id')->toArray();
			$match['type'] = 2;
			$match['status'] = 1;
			$match['user_id'] 		= ['$in' =>$userIds];
			$list = GetId::raw(function ($collection) use($match) {
			    return $collection->aggregate([
			    	[ 	'$match' => $match ],
			        [ 	'$sort' 		=> ['created_at'=>-1] ],			        
			        [
			            '$group' => [
				            '_id' 	=> '$user_id',
				            'data' 	=> ['$first' => '$$ROOT'],
				        ],
			        ],
			        [ 	'$replaceRoot' 	=> ['newRoot' =>'$data'] ],
			    ]);
			});
			 $users->each(function ($user) use ($list) {		   
			    $user->lastDeposit = $list->where('user_id', $user->id)->first();
			});

            return $this->sendResponse($users, 'success');

		}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function directDownlineUsers(Request $request){
		try{
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$users = User::whereHas('parent.parent', function ($query) use ($request) {
			    $query->where('id', $request->user_id);
			})
			->where('is_direct',1);
			if($request->search && $request->search!='all'){
       			$users = $users->whereBetween('created_at',[$request->from,$request->to]);
            }
			$users = $users->with(['parent'=>function($q){
				$q->select('id', 'client_parent_id', 'userid', 'created_at','last_login_at');
			}])			
			->select('id', 'client_parent_id', 'userid', 'created_at','last_login_at','phone','is_direct')
			->paginate($paginate);

			$userIds = $users->pluck('id')->toArray();
			$match['type'] = 2;
			$match['status'] = 1;
			$match['user_id'] 		= ['$in' =>$userIds];
			$list = GetId::raw(function ($collection) use($match) {
			    return $collection->aggregate([
			    	[ 	'$match' => $match ],
			        [ 	'$sort' 		=> ['created_at'=>-1] ],			        
			        [
			            '$group' => [
				            '_id' 	=> '$user_id',
				            'data' 	=> ['$first' => '$$ROOT'],
				        ],
			        ],
			        [ 	'$replaceRoot' 	=> ['newRoot' =>'$data'] ],
			    ]);
			});
			 $users->each(function ($user) use ($list) {		   
			    $user->lastDeposit = $list->where('user_id', $user->id)->first();
			});

            return $this->sendResponse($users, 'success');

		}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function last24HoursActiveUsers(Request $request){
		try{
			$startTime 	= date('Y-m-d', (time() + 19800)) . " 00:00:00";
			$endTime 	= date('Y-m-d', (time() + 19800)) . " 23:59:59";
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$users 		= User::whereHas('parent.parent', function ($query) use ($request) {
				    		$query->where('id', $request->user_id);
						});
						if($request->search && $request->search!='all'){
				       			$users = $users->whereBetween('created_at',[$request->from,$request->to]);
			            }else{
			            	$users = $user->whereBetween('created_at', [$startTime, $endTime]);
			            }
						$users = $users->with(['parent'=>function($q){
							$q->select('id', 'client_parent_id', 'userid', 'created_at','last_login_at');
						}])			
						->select('id', 'client_parent_id', 'userid', 'created_at','last_login_at','phone','is_direct')
						->paginate($paginate);

			$userIds = $users->pluck('id')->toArray();
			$match['type'] = 2;
			$match['status'] = 1;
			$match['user_id'] 		= ['$in' =>$userIds];
			$list = GetId::raw(function ($collection) use($match) {
			    return $collection->aggregate([
			    	[ 	'$match' => $match ],
			        [ 	'$sort' 		=> ['created_at'=>-1] ],			        
			        [
			            '$group' => [
				            '_id' 	=> '$user_id',
				            'data' 	=> ['$first' => '$$ROOT'],
				        ],
			        ],
			        [ 	'$replaceRoot' 	=> ['newRoot' =>'$data'] ],
			    ]);
			});
			 $users->each(function ($user) use ($list) {		   
			    $user->lastDeposit = $list->where('user_id', $user->id)->first();
			});

            return $this->sendResponse($users, 'success');

		}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function last24HoursRegisteredNonDepositUsers(Request $request){
		try{
			$startTime 	= date('Y-m-d', (time() + 19800)) . " 00:00:00";
			$endTime 	= date('Y-m-d', (time() + 19800)) . " 23:59:59";
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');

			$users 		= User::whereHas('parent.parent', function ($query) use ($request) {
					    		$query->where('id', $request->user_id);
							})
						 	->doesntHave('getFirstDeposit');
							if($request->search && $request->search!='all'){
				       			$users = $users->whereBetween('created_at',[$request->from,$request->to]);
				            }else{
				            	$users = $users->whereBetween('created_at', [$startTime, $endTime]);
				            }
							$users = $users->with(['parent'=>function($q){
								$q->select('id', 'client_parent_id', 'userid', 'created_at','last_login_at');
							}])			
							->select('id','client_parent_id','userid','created_at','last_login_at','phone','is_direct')
							->paginate($paginate);

         	return $this->sendResponse($users, 'success');
		}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function nonDepositUsers(Request $request){
		try{
			$startTime 	= date('Y-m-d', (time() + 19800)) . " 00:00:00";
			$endTime 	= date('Y-m-d', (time() + 19800)) . " 23:59:59";
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');

			$users 		= User::whereHas('parent.parent', function ($query) use ($request) {
					    		$query->where('id', $request->user_id);
							})
						 	->doesntHave('getFirstDeposit');
							if($request->search && $request->search!='all'){
				       			$users = $users->whereBetween('created_at',[$request->from,$request->to]);
				            }
							$users = $users->with(['parent'=>function($q){
								$q->select('id', 'client_parent_id', 'userid', 'created_at','last_login_at');
							}])			
							->select('id','client_parent_id','userid','created_at','last_login_at','phone','is_direct')
							->paginate($paginate);

         	return $this->sendResponse($users, 'success');
		}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function depositWithdrawReports(Request $request){
		try{
			$agents 	= User::where('client_parent_id',$request->user_id)->select('id','userid','phone')->get();
			$agentids 	= $agents->pluck('id')->toArray();
			
			$match['parent_id'] = ['$in' => $agentids];
			$match['status'] 	= 1;
			$match['type']		= ['$in'=>[2,3]];
		    $aggregate = [
		    	[ 	'$match' => $match ],
		    	[
			        '$group' => [
			            '_id' 		=> '$type',
			            'totalSum' 	=> ['$sum' => '$stake'],
			        ],
			    ],
		    ];
			$records = GetId::raw(function ($collection) use($aggregate,$match) {
			    return $collection->aggregate($aggregate);
			});
			$response['totalDeposit'] 	= $records->where('_id',2)->first();
			$response['totalWithdraw'] 	= $records->where('_id',3)->first();



			$match1['parent_id'] = ['$in' => $agentids];
			$match1['status'] 	= 1;
			$match1['type']		= ['$in'=>[2,3]];
			$startDateOfMonth 	= date('Y-m-01').' 00:00:00';
			$currentDateOfMonth = date('Y-m-d').' 23:59:59';
			$match1['created_at'] = [
				'$gte' 	=> new \MongoDB\BSON\UTCDateTime(Carbon::createFromFormat("Y-m-d H:i:s", $startDateOfMonth)->startOfDay()),
				'$lte'	=> new \MongoDB\BSON\UTCDateTime(Carbon::createFromFormat("Y-m-d H:i:s", $currentDateOfMonth)->endOfDay())
			];
			$aggregate1 = [
		    	[ 	'$match' => $match ],
		    	[
			        '$group' => [
			            '_id' 		=> [
			            	'type'	=> 	'$type',
			            	'created_at'	=> 	'$created_at',
			            ],
			            'totalSum' 	=> ['$sum' => '$stake'],
			        ],
			    ],
			    [
                '$project' => [
                    	'_id' 			=> 0,
	                    'type' 			=> '$_id.type',
	                    'created_at' 	=> '$_id.created_at',
	                    'total' 		=> '$totalSum',
	                ],
	            ],
		    ];
			$monthRecord = GetId::raw(function ($collection) use($aggregate1,$match1) {
			    return $collection->aggregate($aggregate1);
			});
			if(count($monthRecord)>0){
				$response['month']['deposit'] 	= $monthRecord->where('type',2)->sum('total');
				$response['month']['withdraw']	= $monthRecord->where('type',3)->sum('total');

				$response['today']['deposit'] 	= $monthRecord->where('type',2)->whereBetween('created_at',[date('Y-m-d')." 00:00:00",date('Y-m-d')." 23:59:59"])->sum('total');
				$response['today']['withdraw']	= $monthRecord->where('type',3)->whereBetween('created_at',[date('Y-m-d')." 00:00:00",date('Y-m-d')." 23:59:59"])->sum('total');

				$response['yesterday']['deposit'] 	= $monthRecord->where('type',2)->whereBetween('created_at',$this->getYesterdayWeekStartAndEndDate())->sum('total');
				$response['yesterday']['withdraw']	= $monthRecord->where('type',3)->whereBetween('created_at',$this->getYesterdayWeekStartAndEndDate())->sum('total');

				$response['current_week']['deposit'] 	= $monthRecord->where('type',2)->whereBetween('created_at',$this->getCurrentWeekStartAndEndDate())->sum('total');
				$response['current_week']['withdraw']	= $monthRecord->where('type',3)->whereBetween('created_at',$this->getCurrentWeekStartAndEndDate())->sum('total');

				$response['last_week']['deposit'] 	= $monthRecord->where('type',2)->whereBetween('created_at',$this->getLastWeekStartAndEndDate())->sum('total');
				$response['last_week']['withdraw']	= $monthRecord->where('type',3)->whereBetween('created_at',$this->getLastWeekStartAndEndDate())->sum('total');
			}
			return $this->sendResponse($response, 'success');
		}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function bankAmountTotalAmount(Request $request){
		try{
			$agents 	= User::where('client_parent_id',$request->user_id)->select('id','userid','phone')->get();
			$agentids 	= $agents->pluck('id')->toArray();
			
			$match['parent_id'] = ['$in' => $agentids];
			$match['status'] 	= 1;
			$match['type']		= ['$in'=>[2,3]];
		    $aggregate = [
		    	[ 	'$match' => $match ],
		    	[
			        '$group' => [
			            '_id' 		=> [
			            	'type' => '$type',
			            	// 'type' => '$type'
			            ],
			            'totalSum' 	=> ['$sum' => '$stake'],
			        ],
			    ],
		    ];
			$records = GetId::raw(function ($collection) use($aggregate,$match) {
			    return $collection->aggregate($aggregate);
			});
			return $this->sendResponse([], 'success');
		}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function covertListIntoPaginator($data,$perPage,$totalRecords){
		// Create a collection from the MongoDB result
		$collection = new Collection($data);
		// Create a LengthAwarePaginator instance
		$paginator = new LengthAwarePaginator(
		    $collection,
		    $totalRecords,
		    $perPage,
		    Paginator::resolveCurrentPage(),
		    ['path' => Paginator::resolveCurrentPath()]
		);
		return $paginator;
	}

	public function convertIdsIntoMongoObject($ids){
		$data = [];
		foreach($ids as $key => $value){
			$data[] = new \MongoDB\BSON\ObjectId($value);
		}
		return $data;
	}	
	
}