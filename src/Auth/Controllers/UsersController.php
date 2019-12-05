<?php namespace Vebto\Auth\Controllers;

use App\User;
use App\VoucherHistory;
use App\SubscriptionsTypes;
use App\SubscriptionsActivation;
use Illuminate\Http\Request;
use Vebto\Auth\UserRepository;
use Vebto\Bootstrap\Controller;
use Vebto\Auth\Requests\ModifyUsers;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use Vebto\Settings\Settings;
use Vebto\Auth\Controllers\PhoneveryfyController;
use Vebto\Auth\Controllers\WalletApiController;

class UsersController extends Controller {

    /**
     * @var User
     */
    private $model;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Request
     */
    private $request;
    
    /**
     * PhoneveryfyController service instance.
     *
     * @var Phoneveryfy
     */
    private $phoneveryfy;
    
    /**
     * WalletApiController service instance.
     *
     * @var WalletApi
     */
    private $wallet;
    
    /**
     * @var Settings
     */
    private $settings;

    /**
     * UsersController constructor.
     *
     * @param User $user
     * @param UserRepository $userRepository
     * @param Request $request
     */
    public function __construct(User $user, UserRepository $userRepository, Request $request, PhoneveryfyController $phoneveryfy, Settings $settings, WalletApiController $wallet)
    {
        $this->model = $user;
        $this->request = $request;
        $this->userRepository = $userRepository;
        
        $this->settings = $settings;
        $this->phoneveryfy = $phoneveryfy;
        $this->wallet = $wallet;

        $this->middleware('auth', ['except' => ['show']]);
    }

    /**
     * Return a collection of all registered users.
     *
     * @return LengthAwarePaginator
     */
    public function index()
    {
        $this->authorize('index', User::class);

        return $this->userRepository->paginateUsers($this->request->all());
    }

    /**
     * Return user matching given id.
     *
     * @param integer $id
     * @return User
     */
    public function show($id)
    {
        $with = array_filter(explode(',', $this->request->get('with', '')));

        $user = $this->model->with(array_merge(['groups', 'social_profiles'], $with))->findOrFail($id);

        $this->authorize('show', $user);

        return $this->success(['user' => $user]);
    }

    /**
     * Create a new user.
     *
     * @param ModifyUsers $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ModifyUsers $request)
    {
        $this->authorize('store', User::class);
        
        if(isset($request['phone']))
            $request['phone'] = str_replace('+', '', $request['phone']);

        $user = $this->userRepository->create($this->request->all());

        return $this->success(['user' => $user], 201);
    }

    /**
     * Update an existing user.
     *
     * @param integer $id
     * @param ModifyUsers $request
     *
     * @return User
     */
    public function update($id, ModifyUsers $request)
    {
        $user = $this->userRepository->findOrFail($id);

        $this->authorize('update', $user);
        
        if(isset($request['phone']))
            $request['phone'] = str_replace('+', '', $request['phone']);
        
        $params = $this->request->all();
        
        if(!$this->settings->get('twilio_enable')){
            unset($params['phone']);
        }
        if(!empty($this->request->get('phone')) && $user->phone != $this->request->get('phone')  && $this->settings->get('twilio_enable')){
            
            $rules = [            
                'phone' => 'digits:12|nullable|unique:users'
            ];
            $this->validate($request, $rules);
            
            $response = $this->verificationPhone($request);
            
            if(isset($response['error'])){
                return $this->error($response['error']);
            }elseif(isset($response['success'])){
                return $this->success($response['success']);
            }
        }
        			
        if(isset($request['subscription'])){
            $subscription_s = (strtotime($request['subscription']) > time())? 1 : 0;		
            $user->forceFill(['subscription'=>$request['subscription'], 'subscription_s' => $subscription_s])->save();
        }	
		
        $user = $this->userRepository->update($user, $params);

        return $this->success(['user' => $user]);
    }

     /**
     * Subscribe user.
     *
     * @param integer $id
     * @param ModifyUsers $request
     *
     * @return User
     */
    public function subscrib($id, ModifyUsers $request)
    {
        $user = $this->userRepository->findOrFail($id);
        
        if(!$user->subscription)
            return $this->error(['voucher' => 'The duration of your subscription is infinite']);

        $this->authorize('update', $user);
        
        $request = $this->wallet->voucherActivate([
            'key'=>$this->request->get('voucher'),
            'user'=>$user->id,
            ]);
        if(isset($request->status) && $request->status == 'Activate Success'){
                        
            return $this->success(['user' => $this->addVoucher($id, $request->amount,$request->date_sold, $this->request->get('voucher'))]);
        }
        
        return $this->error(['voucher' => 'Invalid voucher code or expired']);
        
    }
    
     /**
     * Update an Subscription user.
     *
     * @param integer $validity
     * @param integer $id
     *
     * @return User
     */
    private function addSubscription($id, $validity)
    {
        $user = $this->userRepository->findOrFail($id);
        
        if(!$user->subscription)
            return $user;
        
        $this->authorize('update', $user);
        
        $expire = NULL;
        
        if($validity > 0){
            $start_time = ($user->subscription && strtotime($user->subscription) > time()) ? strtotime($user->subscription) : time();
            $expire = date('Y-m-d H:i:s',($start_time + $validity*24*60*60));
        }
        
        $user->forceFill(['subscription'=>$expire,'subscription_s'=>true])->save();
        
        return $user;
        
    }
	
	private function addVoucher($id, $amount, $sold, $voucher)
    {
		$user = $this->userRepository->findOrFail($id);
		$voucherHistory = new VoucherHistory();
        $voucherHistory->forceFill(['user_id'=>$id,'amount'=>$amount,'voucher'=>$voucher,'date_sold'=>$sold->date])->save();
		$balance = $user->balance+$amount;
		$user->forceFill(['balance'=>$balance])->save();
		
		if(!$user->subscription)
            return $user;
        
        $this->authorize('update', $user);
        
        $expire = NULL;
		$max_price = 0;
		$iter =true;
		
		while ($iter) {
		
//			$subscriptions_types = SubscriptionsTypes::where('status', 'active')->orderByDesc('price')->get()->toArray();
			if ($max_price == 0 || $balance >= $max_price) {
				$max_sub_price = SubscriptionsTypes::where('status', 'active')->max('price');
				$subscriptions_type = SubscriptionsTypes::where('status', 'active')->where('price', $max_sub_price)->first();
			} else {
				$max_sub_price = SubscriptionsTypes::where('status', 'active')->where('price','<',$max_price)->max('price');
				$subscriptions_type = SubscriptionsTypes::where('status', 'active')->where('price', $max_sub_price)->first();
			}
			$max_price = $max_sub_price;
			if ($subscriptions_type) {
				if ($balance >= $subscriptions_type->price) {
					if( $subscriptions_type->validity > 0){
						$start_time = ($user->subscription && strtotime($user->subscription) > time()) ? strtotime($user->subscription) : time();
						$expire = date('Y-m-d H:i:s',($start_time + $subscriptions_type->validity*24*60*60));
					}

					$user->forceFill(['subscription'=>$expire,'subscription_s'=>true])->save();
					$balance = $balance - $max_sub_price;
					$user->forceFill(['balance'=>$balance])->save();
					$subscriptionsActivation = new SubscriptionsActivation();
					$subscriptionsActivation->forceFill(['user_id'=>$id,'subscription_id'=>$subscriptions_type->id,'validity'=>$subscriptions_type->validity])->save();
				}
			} else {
				$iter = false;
			}
		
		}
		
		return $user;
	}
    
	
	public function subscriptions() {
		$perPage    = null !== $this->request->get('per_page') ? $this->request->get('per_page') : 15;
		$page    = null !== $this->request->get('page') ? $this->request->get('page') : 1;
		
		$id = $this->request->get('user_id');
				
		$activations = SubscriptionsActivation::where('user_id',$id)->with('subscriptions_types')->get();
		$vouchers = VoucherHistory::where('user_id',$id)->get();
		$actions = array();
		foreach ($activations as $key => $activation) {
			$actions[] = array('created_at'=>$activation->created_at,
								'action'=>'Subscription Activation',
								'validity'=>$activation->validity,
								'amount'=>$activation->subscriptions_types->price);
		}
		$vouchers = VoucherHistory::where('user_id',$id)->get();
		foreach ($vouchers as $key => $activation) {
			$actions[] = array('created_at'=>$activation->created_at,
								'action'=>'Voucher Activation',
								'validity'=>'',
								'amount'=>$activation->amount);
		}
		
		$date = array_column($actions, 'created_at');
		
		array_multisort($date, SORT_DESC, $actions);
		
		$sliced_array = array_slice($actions, ($page-1)*$perPage, $perPage);
		
		return array('data'=>$sliced_array,
					'current_page' => $page,
					'per_page' => $perPage,
					'from' => $page*$perPage - $perPage + 1,
					'to' => $page*$perPage,
					'total' => sizeof($actions));
	}
	
     /**
     * SMS verification add/edit user phone.
     *     
     * @param ModifyUsers $request
     * 
     */
    public function verificationPhone(ModifyUsers $request)
    {        
        $response = [];
        
        $needsVerify = empty($this->request->get('sms_code')) ? true : false;

        $phone = str_replace('+', '', $request->get('phone'));
        
        $isPhone = $this->phoneveryfy->firstOrCreate(['phone'=>$phone]);
        
        if ($needsVerify) {
                                    
            if(!empty($isPhone->sms_code) && strtotime($isPhone->updated_at)+60*5 > time() ){                
                $response['error'] = ['phone'=>'Next SMS Verification Code is available in 5 minutes'];
                return $response;     
            }            
            
            $smsCode = substr('00000'.rand(1,99999), -5); 
            $isPhone = $this->phoneveryfy->update($isPhone, ['sms_code' => $smsCode]);
                        
            $this->phoneveryfy->sendSMS(['phone'=>$phone,'code'=>$smsCode]);            
            
            $response['success'] = ['reg' => 'sms_required'];
            return $response;            
        }

        if($this->request->get('sms_code') != $isPhone->sms_code){  
            $response['error'] = ['sms_code'=>'Wrong SMS Verification Code!'];
            return $response;            
        }
        
        $this->phoneveryfy->update($isPhone, ['sms_code' => NULL]);
             
        return $response;
    }
    
    /**
     * Delete multiple users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteMultiple()
    {
        $this->authorize('destroy', User::class);

        $this->validate($this->request, [
            'ids' => 'required|array|min:1'
        ]);

        $this->userRepository->deleteMultiple($this->request->get('ids'));

        return $this->success([], 204);
    }
	
	private function adslimit($id)
    {
        $user = $this->userRepository->findOrFail($id);
        
        $adslimit = $user->adds_limit+1;
		$limit_date = date('Y-m-d',$user->ads_limit_date);
		
		if (date('Y-m-d') > $limit_date) {
            $limit_date = date('Y-m-d');
			$adslimit = 1;
        }
        
        $user->forceFill(['ads_limit'=>$adslimit,'limit_date'=>$limit_date])->save();
        
        return $user;
        
    }
	
	/**
     * Return a collection of all filtered by subscription users.
     *
     * @return LengthAwarePaginator
     */
    public function filter()
    {
        $this->authorize('index', User::class);

        return $this->userRepository->paginateUsers($this->request->all());
    }
	
	/**
     * Get vouchers prices from wallet.
     *
     * @param integer $id
     * @param ModifyUsers $request
     *
     * @return User
     */
    public function getVouchersPrices()
    {
        $request = $this->wallet->getVoucherPrices();
        
        return $request;
        
    }
}
