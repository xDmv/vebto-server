<?php namespace Vebto\Auth\Controllers;

use Mail;
use Vebto\Auth\User;
use Vebto\Mail\ConfirmEmail;
use Vebto\Settings\Settings;
use Illuminate\Http\Request;
use Vebto\Bootstrap\Controller;
use Vebto\Auth\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;

use Vebto\Auth\Controllers\PhoneveryfyController;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Settings service instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * UserRepository service instance.
     *
     * @var UserRepository
     */
    private $repository;
    
    /**
     * PhoneveryfyController service instance.
     *
     * @var Phoneveryfy
     */
    private $phoneveryfy;

    /**
     * RegisterController constructor.
     *
     * @param Settings $settings
     * @param UserRepository $repository
     */
    public function __construct(Settings $settings, UserRepository $repository, PhoneveryfyController $phoneveryfy)
    {
        $this->settings = $settings;
        $this->repository = $repository;
        $this->phoneveryfy = $phoneveryfy;

        $this->middleware('guest');

        //abort if registration should be disabled
        if ($this->settings->get('disable.registration')) abort(404);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        
        if(isset($request['email']) && !empty($request['email']) && is_numeric($request['email']) && $this->settings->get('twilio_enable')){
            return $this->registerByPhone($request);
        }
                
        $rules = [
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|max:255|confirmed',
        ];

        $this->validate($request, $rules);

        $params = $request->all();
        $needsConfirmation = $this->settings->get('require_email_confirmation');

        if ($needsConfirmation) {
            $code = str_random(30);
            $params['confirmation_code'] = $code;
            $params['confirmed'] = 0;
        }

        event(new Registered($user = $this->create($params)));

        if ($needsConfirmation) {
            Mail::queue(new ConfirmEmail($params['email'], $code));
            return $this->success(['type' => 'confirmation_required']);
        }

        $this->guard()->login($user);

        return $this->registered($request, $user);
    }

    /**
     * Handle a registration request for the application by PHONE.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerByPhone(Request $request)
    {
        $request['email'] = str_replace('+', '', $request['email']);
        
        $rules = [            
            'email' => 'required|digits:12|unique:users'
        ];

        $this->validate($request, $rules);

        $params = $request->all();
        
        $needsVerify = (!isset($params['sms_code']) || empty($params['sms_code'])) ? true : false;

        $isPhone = $this->phoneveryfy->firstOrCreate(['phone'=>$params['email']]);
        
        if ($needsVerify) {
                                    
            if(!empty($isPhone->sms_code) && strtotime($isPhone->updated_at)+60*60 > time() ){
                return $this->error(['email' => 'Next SMS Verification Code is available in an hour']);            
            }            
            
            $smsCode = substr('00000'.rand(1,99999), -5);            
            $isPhone = $this->phoneveryfy->update($isPhone, ['sms_code' => $smsCode]);
            
            $this->phoneveryfy->sendSMS(['phone'=>$params['email'],'code'=>$smsCode]);
            
            return $this->success(['reg' => 'sms_required']);            
        }

        if($params['sms_code'] != $isPhone->sms_code){            
            return $this->error(['sms_code'=>'Wrong SMS Verification Code ']);            
        }
        
        $this->phoneveryfy->update($isPhone, ['sms_code' => NULL]);
        
        $params['phone'] = $params['email'];
        unset($params['email']);
        
        event(new Registered($user = $this->create($params)));

        $this->guard()->login($user);

        return $this->registered($request, $user);
    }
            
    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * The user has been registered.
     *
     * @param Request $request
     * @param $user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function registered(Request $request, User $user)
    {
        return $this->success(['data' => $user->load('groups')->toArray()]);
    }
}