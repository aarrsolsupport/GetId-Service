<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Illuminate\Notifications\Notifiable;
use App\UserRegistration;
use App\UserLoginHistory;
use App\PokerMarketBets;
use App\Client;
use App\Models\UserNumber;
use Auth;
use DB;


class User extends Authenticatable
{
	use Notifiable;
	use HybridRelations;

	protected $connection = 'mysql';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'userid',
		'name',
		'chip_rate',
		'balance',
		'exposure',
		'winnings',
		'role_id',
		'password',
		'is_bet',
		'is_book',
		'is_result',
		'is_fresh',
		'phone',
		'is_direct',
		'casino_access_token',
		'parents',
		'user_level',
		'site',
		'is_hide_user',
		'is_pin_user',
		'old_chip_rate',
		'remark',
		'total_uncleared_amount',
		'is_check_user',
		'qtech_access_token',
		'client_parent_id',
		'client_parent_userid',
		'casino_suite_access_token',
		'session_token',
		'last_credit_reference_update',
		'maximum_balance',
		'currency',
		'verified',
		'is_hide_user_by',
		'settlement',
		'r_settlement',
		'cricket',
		'tennis',
		'soccer',
		'indian_casino',
		'inter_casino',
		'matka',
		'other',
		'is_landing_page',
		'landing_page',
		'register_itself',
		'has_accepting_getid', // default 0, and active 1
		'level_2',
		'level_3',
		'level_4',
		'level_5',
		'level_6',
		'level_7',
		'level_8',
	];

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 */
	protected static function boot()
	{
		parent::boot();
		static::creating(function ($model) {
			if (!$model->register_itself) {
				if (Auth::check()) {
					$model->parents = implode(',', static::getParentsId(Auth::user()));
				} else {
					$model->parents = implode(',', static::getParentsId(User::where('role_id', 1)->first()));
				}
			}
		});
	}

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password', 'remember_token', 'casino_access_token', 'qtech_access_token', 'casino_suite_access_token'
	];

	public function clientsOfMine()
	{
		return $this->belongsToMany('App\User', 'clients', 'user_id', 'client_id')->withPivot('partnership', 'book_com_pr', 'fancy_com_pr', 'worli_com_pr', 'book_com', 'fancy_com', 'worli_com', 'book_com_take', 'fancy_com_take', 'worli_com_take', 'exchange_com_pr', 'exchange_com', 'exchange_com_take'); //,'book_com_type'
	}

	/**
	 * [allClientsOfMine description]
	 * @param  [type] $loggedIn_id [description]
	 * @param  string $role_id     [description]
	 * @return [type]              [description]
	 */
	public static function allClientsOfMine($loggedIn_id, $role_id = '')
	{
		if (empty($role_id)) {
			return User::whereRaw("FIND_IN_SET($loggedIn_id, parents)")->get();
		} else {
			return User::whereRaw("FIND_IN_SET($loggedIn_id, parents)")->where('role_id', $role_id)->get();
		}
	}

	public static function allClientsOfMineID($loggedIn_id, $role_id = '')
	{
		if (empty($role_id)) {
			return DB::table('users')->whereRaw("FIND_IN_SET($loggedIn_id, parents)")->pluck('id');
		} else {
			return DB::table('users')->whereRaw("FIND_IN_SET($loggedIn_id, parents)")->where('role_id', $role_id)->pluck('id');
			// return User::whereRaw("FIND_IN_SET($loggedIn_id, parents)")->where('role_id', $role_id)->pluck('id');
		}
	}

	/**
	 * [allClientsOfMineByKeywords description]
	 * @param  [type] $loggedIn_id [description]
	 * @param  [type] $search      [description]
	 * @return [type]              [description]
	 */
	public static function allClientsOfMineByKeywords($loggedIn_id, $search)
	{
		$proxydbconnection 	= 	dbProxyConnectionMain();
		if ($loggedIn_id == 1) {
			return $proxydbconnection->table('users')->select('id', 'userid', 'role_id')->where('userid', 'LIKE', '%' . $search . '%')->take(20)->get();
		}
		return $proxydbconnection->table('users')->select('id', 'userid', 'role_id')->whereRaw("FIND_IN_SET($loggedIn_id, parents)")->where('userid', 'LIKE', '%' . $search . '%')->take(20)->get();
	}

	public static function getParentsId($user, $ids = [], $flag = false)
	{
		if ($flag) {
			if (isset($user->clientOf[0])) {
				array_push($ids, $user->clientOf[0]->id);
			}
		} else {
			if (!empty($user)) {
				array_push($ids, $user->id);
			}
		}
		if (isset($user->clientOf[0])) {
			$ids = static::getParentsId($user->clientOf[0], $ids, $flag);
		}
		return $ids;
	}

	public function user_self_clients()
	{
		return $this->hasOne(Client::class, 'client_id', 'id');
	}

	public function userSelfClient()
	{
		return $this->hasOne(Client::class, 'client_id', 'id');
	}

	public function clientOf()
	{
		return $this->belongsToMany('App\User', 'clients', 'client_id', 'user_id')->with('user_login_histories')->withPivot('partnership', 'book_com_pr', 'fancy_com_pr', 'worli_com_pr', 'book_com', 'worli_com', 'fancy_com', 'book_com_take', 'fancy_com_take', 'worli_com_take', 'exchange_com_pr', 'exchange_com', 'exchange_com_take', 'book_com_type', 'show_com'); //,'book_com_type'
	}

	/**
	 * [userSetting description]
	 * @return [type] [description]
	 */
	public function userSetting()
	{
		return $this->hasOne('App\UserGeneralSetting', 'user_id', 'id');
	}

	public function messagesData()
	{
		return $this->hasMany('App\Message', 'from_user', 'id')->where('seen_at', '=', NULL);
	}

	public function favoriteEvents()
	{
		return $this->hasMany('App\Favorite');
	}

	public function statements()
	{
		return $this->hasMany('App\Statement');
	}

	public function myBets()
	{
		return $this->hasMany('App\Bet');
	}

	public function bets()
	{
		return $this->hasMany('App\Bet')->where('status', 'open');
	}

	// All Event Exposures List
	public function mainExposures()
	{
		return $this->hasMany('App\Exposure');
	}

	// Dealer Event
	public function table()
	{
		return $this->hasOne('App\DealerTable', 'user_id');
	}

	public function chips()
	{
		return $this->hasOne('App\ChipSetting');
	}

	public function agents()
	{
		return $this->belongsToMany('App\User', 'manager_agents', 'manager_id', 'agent_id');
	}

	// Agent Markets
	public function agentMarket()
	{
		return $this->hasOne('App\AgentMarket', 'user_id');
	}

	public function agentFancyMarket()
	{
		return $this->hasMany('App\AgentFancy');
	}

	public function agentFancies()
	{
		return $this->belongsToMany('App\Fancy', 'agent_fancies');
	}

	public function agentBookmakerMarket()
	{
		return $this->hasMany('App\AgentBookmaker');
	}

	public function agentBookmakers()
	{
		return $this->belongsToMany('App\Bookmaker', 'agent_bookmakers');
	}

	// Role Of User
	public function role()
	{
		return $this->belongsTo('App\Role');
	}

	public function is_director()
	{
		if ($this->role->role == 'is_director') {
			return true;
		} else {
			return false;
		}
	}

	public function is_sub_admin()
	{
		if ($this->role->role == 'is_sub_admin') {
			return true;
		} else {
			return false;
		}
	}

	public function is_super_admin()
	{
		if ($this->role->role == 'is_super_admin') {
			return true;
		} else {
			return false;
		}
	}

	public function is_admin()
	{
		if ($this->role->role == 'is_admin') {
			return true;
		} else {
			return false;
		}
	}

	public function is_agent()
	{
		if ($this->role->role == 'is_agent') {
			return true;
		} else {
			return false;
		}
	}

	public function is_super_master()
	{
		if ($this->role->role == 'is_super_master') {
			return true;
		} else {
			return false;
		}
	}

	public function is_master()
	{
		if ($this->role->role == 'is_master') {
			return true;
		} else {
			return false;
		}
	}

	public function is_user()
	{
		if ($this->role->role == 'is_user') {
			return true;
		} else {
			return false;
		}
	}

	public function is_manager()
	{
		if ($this->role->role == 'is_manager') {
			return true;
		} else {
			return false;
		}
	}

	public function is_fancy_agent()
	{
		if ($this->role->role == 'is_fancy_agent') {
			return true;
		} else {
			return false;
		}
	}

	public function is_dealer()
	{
		if ($this->role->role == 'is_dealer') {
			return true;
		} else {
			return false;
		}
	}

	public function banMatches()
	{
		return $this->hasMany('App\MatchBan');
	}

	public function banLeagues()
	{
		return $this->hasMany('App\LeagueBan');
	}

	public function banMatkas()
	{
		return $this->hasMany('App\WorliMatkaBan');
	}

	public function userDetail()
	{
		return $this->hasOne('App\UserDetail');
	}

	public function paymentRequests()
	{
		return $this->hasMany('App\UserPaymentRequest');
	}

	public function user_unclear_balances()
	{
		return $this->hasMany('App\ManageUserBalance')->where('is_deleted', 0);
	}

	public static function getAllChildUser($loggedIn_user)
	{
		$childUsers = array();
		if ($loggedIn_user['role_id'] == 5) {
			$childUsers[] = $loggedIn_user['id'];
		} else {
			$childUsers = DB::table('users')->whereRaw("(FIND_IN_SET(" . $loggedIn_user['id'] . ", parents) AND role_id = 5)")->pluck('id')->toArray();
		}
		return $childUsers;
	}

	public function lockBetLeagues()
	{
		return $this->hasMany('App\LeagueBetLock');
	}

	public function lockBetMatches()
	{
		return $this->hasMany('App\MatchesBetLock');
	}

	public function lockBetVenues()
	{
		return $this->hasMany('App\VenueBetLock');
	}

	public function banVenues()
	{
		return $this->hasMany('App\VenueBan');
	}

	// public function getUnclearBalanceAttribute() {
	// 	// $unClearBalances = $this->user_unclear_balances()->sum('amount');
	// 	return 0;
	// }
	// protected $appends = ['unclear_balance'];
	// 

	/**
	 * [poker_market_bets description]
	 * @return [type] [description]
	 */
	public function poker_market_bets()
	{
		return $this->hasMany(PokerMarketBets::class, 'userid', 'id');
	}

	/**
	 * [allClientsOfMineOnlyId description]
	 * @param  [type] $loggedIn_id [description]
	 * @param  string $role_id     [description]
	 * @return [type]              [description]
	 */
	public static function allClientsOfMineOnlyId($loggedIn_id, $role_id = '')
	{
		$resposne  	=	DB::table('users')->whereRaw("FIND_IN_SET($loggedIn_id, parents)");
		if (!empty($role_id)) {
			$resposne  	=	$resposne->where('role_id', $role_id);
		}
		return $resposne->pluck('id')->toArray();
	}

	/**
	 * [user_registration description]
	 * @return [type] [description]
	 */
	public function user_registration()
	{
		return $this->hasOne(UserRegistration::class, 'userid', 'userid');
	}

	/**
	 * [user_login_histories description]
	 */
	public function user_login_histories()
	{
		return $this->hasOne(UserLoginHistory::class, 'user_id', 'id');
	}

	public function user_number()
	{
		return $this->hasOne(UserNumber::class, 'user_id', 'id');
	}

	public function user_request_get_id()
	{
		return $this->hasMany(UserRequestForGetId::class, 'user_id', 'id');
	}
}
