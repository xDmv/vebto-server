<?php namespace Vebto\Billing\Gateways;

use App;
use Vebto\Settings\Settings;
use Illuminate\Support\Collection;
use Vebto\Billing\Gateways\Paypal\PaypalGateway;
use Vebto\Billing\Gateways\Stripe\StripeGateway;

class GatewayFactory
{
    /**
     * @var Settings
     */
    private $settings;

    /**
     * All available billing gateways.
     *
     * @var array
     */
    private $gateways = [
        'paypal' => PaypalGateway::class,
        'stripe' => StripeGateway::class,
    ];

    /**
     * GatewayFactory constructor.
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get gateway by specified name.
     *
     * @param string $name
     * @return Vebto\Billing\Gateways\Contracts\GatewayInterface
     */
    public function get($name)
    {
        return App::make($this->gateways[$name]);
    }

    /**
     * Get currently enabled payment gateways.
     *
     * @return Collection
     */
    public function getEnabledGateways()
    {
        return collect($this->gateways)->filter(function($namespace, $name) {
            return $this->settings->get("billing.$name.enable");
        })->map(function($namespace) {
            return App::make($namespace);
        });
    }
}