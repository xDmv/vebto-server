<?php namespace Vebto\Auth\Controllers;

use Illuminate\Http\Request;
use Vebto\Settings\Settings;
use Vebto\Bootstrap\Controller;
use Vebto\Bootstrap\BootstrapData;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Vebto\Auth\UserRepository;
use Vebto\Auth\Controllers\PhoneveryfyController;
use Hash;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * @var BootstrapData
     */
    private $bootstrapData;

    /**
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
     * Create a new controller instance.
     *
     * @param BootstrapData $bootstrapData
     * @param Settings $settings
     */
    public function __construct(BootstrapData $bootstrapData, Settings $settings, UserRepository $repository, PhoneveryfyController $phoneveryfy)
    {
        $this->middleware('guest', ['except' => 'logout']);

        $this->bootstrapData = $bootstrapData;
        $this->settings = $settings;
        
        $this->repository = $repository;
        $this->phoneveryfy = $phoneveryfy;
    }
    
     /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function login(Request $request)
    {        
        if(isset($request['email']) && !empty($request['email']) && is_numeric($request['email']) && $this->settings->get('twilio_enable')){
            $response = $this->loginByPhone($request);
            
            if(isset($response['error'])){
                return $this->error($response['error']);
            }elseif(isset($response['success'])){
                return $this->success($response['success']);
            }
                        
        }else{
            $this->validateLogin($request);            
        }
        
            // If the class is using the ThrottlesLogins trait, we can automatically throttle
            // the login attempts for this application. We'll key this by the username and
            // the IP address of the client making these requests into this application.
            if ($this->hasTooManyLoginAttempts($request)) {
                $this->fireLockoutEvent($request);

                return $this->sendLockoutResponse($request);
            }

            if ($this->attemptLogin($request)) {
                if (Auth::check()) {   
                    if($this->getDevId($request->header('User-Agent')) && Auth::user()->session_id && $this->getDevId($request->header('User-Agent')) != Auth::user()->session_id){
                        $this->guard()->logout();                        
                        return $this->error(['password'=>'You are logged on to another device!']);
                    }                    
                }
                
                return $this->sendLoginResponse($request);
            }

        
        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }
    
    /**
    * Send the response after the user was authenticated.
    * Remove the other sessions of this user
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    
    protected function getDevId($str)
   {
        $marker = stripos($str, "devId=");
        if($marker) 
            return substr($str, $marker+6);

       return null;
    }
    
    protected function sendLoginResponse(Request $request)
   {
       $request->session()->regenerate();
       
       if($this->getDevId($request->header('User-Agent'))){
            Auth::user()->session_id = $this->getDevId($request->header('User-Agent'));
            Auth::user()->save();
       }                    

       $this->clearLoginAttempts($request);

       return $this->authenticated($request, $this->guard()->user())
           ?: redirect()->intended($this->redirectPath());
   }
    
    /**
     * Handle a registration request for the application by PHONE.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginByPhone(Request $request)
    {
        $response = [];
        
        $request['email'] = str_replace('+', '', $request['email']);
        
        $rules = [            
            'email' => 'required|digits:12'
        ];

        $this->validate($request, $rules);

        $params = $request->all();
        
        $user = $this->repository->firstPhoneOrFail($params['email']);
        
        if (is_null($user)) {            
            $response['error'] = ['email'=>'Phone number is not registered!'];
            return $response;     
        }
                
        $needsVerify = (!isset($params['sms_code']) || empty($params['sms_code'])) ? true : false;

        $isPhone = $this->phoneveryfy->firstOrCreate(['phone'=>$params['email']]);
        
        if ($needsVerify) {
                                    
            if(!empty($isPhone->sms_code) && strtotime($isPhone->updated_at)+60*5 > time() ){                
                $response['error'] = ['email'=>'Next SMS Verification Code is available in 5 minutes'];
                return $response;     
            }            
            
            $smsCode = substr('00000'.rand(1,99999), -5); 
            $isPhone = $this->phoneveryfy->update($isPhone, ['sms_code' => $smsCode]);
            
            $this->phoneveryfy->sendSMS(['phone'=>$params['email'],'code'=>$smsCode]);            
            
            $response['success'] = ['reg' => 'sms_required'];
            return $response;            
        }

        if($params['sms_code'] != $isPhone->sms_code){  
            $response['error'] = ['sms_code'=>'Wrong SMS Verification Code!'];
            return $response;            
        }
        
        $this->phoneveryfy->update($isPhone, ['sms_code' => NULL]);
             
        return $response;
    }
           
    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        if(isset($request['email']) && !empty($request['email']) && is_numeric($request['email']) && $this->settings->get('twilio_enable')){
            return ['phone'=>$request->get('email'),'password'=>$request->get('password')];                        
        }
        
        return $request->only($this->username(), 'password');
    }
    
    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $this->validate($request, [
            $this->username() => 'required|string|email_confirmed',
            'password' => 'required|string',
        ]);
    }
        
    /**
     * The user has been authenticated.
     *
     * @return mixed
     */
    protected function authenticated(Request $request)
    {
        if(isset($request['phone']) && !empty($request['phone'])){
            $user = $this->repository->firstPhoneOrFail($request->get('phone'));
            if (!is_null($user)) {                            
                $this->repository->updatePhoneCode($user, ['password' => $user->sms_code]);
            }                        
        }
        
        $data = $this->bootstrapData->get();
        return $this->success(['data' => $data]);
    }

    /**
     * Get the failed login response instance.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendFailedLoginResponse()
    {
        return $this->error(['general' => __('auth.failed')]);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {                        
            $UserAgentID = $this->getDevId($request->header('User-Agent'));
            if($UserAgentID && Auth::user()->session_id && $UserAgentID == Auth::user()->session_id){
                Auth::user()->session_id = NULL;
                Auth::user()->save();
            }                    
        }        
        
        $this->guard()->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        return $this->success();
    }
}