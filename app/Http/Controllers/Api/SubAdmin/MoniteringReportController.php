<?php

namespace App\Http\Controllers\Api\SubAdmin;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User\UserFirstWithdrawDepositRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use App\Models\User\GetId;
use App\Models\User;
use DB,Validator;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

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
			$paginate = $request->paginate??config('constants.pagination');
			$list = UserFirstWithdrawDepositRequest::where('parent_id',$request->parent_id)
					->where('type',2)
					->with(['user'=>function($query){
						$query->select('id','userid','phone');
					}]);
			if($request->search && $request->search!='all'){
       			$list = $list->whereBetween('created_at',[$request->from,$request->to]);
            }
            $list = $list->orderBy('created_at','desc')->paginate($paginate);
	        return $this->sendResponse($list, 'success');
     	}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function totalAmountOfFirstTimeDeposit(Request $request){
		try{
			$paginate = $request->paginate??config('constants.pagination');
			$list = UserFirstWithdrawDepositRequest::where('parent_id',$request->parent_id)
					->where('type',2)
					->with(['user'=>function($query){
						$query->select('id','userid','phone');
					}]);
			if($request->search && $request->search!='all'){
       			$list = $list->whereBetween('created_at',[$request->from,$request->to]);
            }
            $list = $list->orderBy('created_at','desc')->paginate($paginate);
	        return $this->sendResponse($list, 'success');
     	}catch(Exception $e){
            return $this->sendError('Error.', 'Something went wrong.Please try again later.',401);  
        }
	}

	public function recurringDepositCount(Request $request){
		try{
			$firstUsers = UserFirstWithdrawDepositRequest::with('user')
							->where('parent_id',$request->parent_id)
							->where('type',2)
							->get();
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$page 		= (int)$request->page?:1;
			$skip  		= ($page - 1) * $paginate;
			
			$match['parent_id'] = $request->parent_id;
			$match['type'] = 2;
			$match['status'] = 1;
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
			$firstUsers = UserFirstWithdrawDepositRequest::with('user')
							->where('parent_id',$request->parent_id)
							->where('type',2)
							->get();
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$page 		= (int)$request->page?:1;
			$skip  		= ($page - 1) * $paginate;
			$match['parent_id'] = $request->parent_id;
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
			                'totalAmount' 	=> ['$sum' 	=> '$stack'],
			                'data' 		  	=> ['$first' 	=> '$$ROOT'],
			            ],
			        ],
			        [ 	'$addFields' 	=> [ "data.totalStack" => '$totalAmount'] ],
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
				foreach($list as $key => $value){
					$user = $firstUsers->where('user_id',$value->user_id)->first();
					if($user){
						$value->userid = $user->user->userid;
						$value->phone = $user->user->phone;

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
			$firstUsers = UserFirstWithdrawDepositRequest::with('user')
							->where('parent_id',$request->parent_id)
							->where('type',3)
							->get();
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$page 		= (int)$request->page?:1;
			$skip  		= ($page - 1) * $paginate;
			$match['parent_id'] = $request->parent_id;
			$match['type'] = 3;
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
			                'totalAmount' 	=> ['$sum' 	=> '$stack'],
			                'data' 		  	=> ['$first' 	=> '$$ROOT'],
			            ],
			        ],
			        [ 	'$addFields' 	=> [ "data.totalStack" => '$totalAmount'] ],
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
				foreach($list as $key => $value){
					$user = $firstUsers->where('user_id',$value->user_id)->first();
					if($user){
						$value->userid = $user->user->userid;
						$value->phone = $user->user->phone;

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
			$paginate 	= (int)$request->paginate?:	config('constants.pagination');
			$page 		= (int)$request->page?:1;
			$skip  		= ($page - 1) * $paginate;

			$match['parent_id'] = $request->parent_id;
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
		                'totalStack' => ['$sum' => '$stack'],
		            ],
		        ],
		        [
		            '$group' => [
		                '_id' => '$_id.user_id',
		                'typeTotals' => [
		                    '$push' => [
		                        'type' => '$_id.type',
		                        'totalStack' => '$totalStack',
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
		                                    'then' => ['$add' => ['$$value.type2Total', '$$this.totalStack']],
		                                    'else' => '$$value.type2Total',
		                                ],
		                            ],
		                            'type3Total' => [
		                                '$cond' => [
		                                    'if' => ['$eq' => ['$$this.type', 3]],
		                                    'then' => ['$add' => ['$$value.type3Total', '$$this.totalStack']],
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
			if(count($list)>0){
				$userIds = array_unique(array_column((array)$list,'user_id'));
				$users = User::whereIn('id',$userIds)
							->select('id','userid','role_id','name','phone')
							->get();

				foreach($list as $key => $value){
					$user = $users->where('id',$value->user_id)->first();
					if($user){
						$value->userid = $user->userid;
						$value->phone = $user->phone;
					}
				}
			}

			$totalRecords 	= $records[0]['count'];
			$list = $this->covertListIntoPaginator($list,$paginate,$totalRecords);
			return $this->sendResponse($list, 'success');
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