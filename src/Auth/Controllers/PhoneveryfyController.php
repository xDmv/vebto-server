<?php namespace Vebto\Auth\Controllers;

use Vebto\Bootstrap\Controller;
use Vebto\Auth\Phoneveryfy;

use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;
//use Services_Twilio;

class PhoneveryfyController extends Controller
{
    /**
     * Phoneveryfy model instance.
     *
     * @var Phoneveryfy
     */
    private $phoneveryfy;
    
    /**
     * Twilio instance.
     *
     * @var $twilioClient
     */    
    private $twilioClient;
    
    private $twilioAccountSid;
    private $twilioAuthToken;
    private $twilioAppSid;
    private $twilioPhone;
        
    /**
     * PhoneveryfyController constructor.
     *
     * @param User $user
     * @param Group $group
     * @param Settings $settings
     */
    public function __construct(
        Phoneveryfy $phoneveryfy
    )
    {
        $this->phoneveryfy  = $phoneveryfy;  
                
        $this->twilioAccountSid = config('app.twilio')['TWILIO_ACCOUNT_SID'];
        $this->twilioAuthToken  = config('app.twilio')['TWILIO_AUTH_TOKEN'];
        $this->twilioAppSid     = config('app.twilio')['TWILIO_APP_SID'];
        $this->twilioPhone     = config('app.twilio')['TWILIO_PHONE_NUMBER'];
        
        $this->twilioClient = new Client($this->twilioAccountSid, $this->twilioAuthToken);
    }
        
    /**
     * Send Verify SMS by Twilio service.
     *
     * @param array $params
     */
    public function sendSMS($params)
    {                
        try
        {
            $this->twilioClient->messages->create(                
                '+'.$params['phone'],
                array(
                    'from' => $this->twilioPhone,                    
                    'body' => $params['code']
                )
            );
        }
        catch (Exception $e)
        {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    /**
     * Return first phone matching attributes or create a new one.
     *
     * @param array $params
     * @return Phoneveryfy
     */
    public function firstOrCreate($params)
    {
        $phoneveryfy = $this->phoneveryfy->where('phone', $params['phone'])->first();

        if (is_null($phoneveryfy)) {            
            $phoneveryfy = $this->create($params);
        }
        
        return $phoneveryfy;
    }
    
     /**
     * Update given phone.
     *
     * @param Phoneveryfy $phoneveryfy
     * @param array $params
     *
     * @return Phoneveryfy
     */
    public function update(Phoneveryfy $phoneveryfy, $params)
    {
        $phoneveryfy->forceFill($params)->save();
        
        return $phoneveryfy;
    }
    
    /**
     * Create a new phone and set sms_code.
     *
     * @throws \Exception
     *
     * @param array $params
     * @return Phoneveryfy
     */
    public function create($params)
    {
        /** @var Phoneveryfy $phoneveryfy */
        $phoneveryfy = Phoneveryfy::forceCreate($params);
        
        return $phoneveryfy;
    }

}