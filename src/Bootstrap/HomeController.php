<?php namespace Vebto\Bootstrap;

use Illuminate\View\View;
use Vebto\Settings\Settings;

class HomeController extends Controller {

    /**
     * @var BootstrapData
     */
    private $bootstrapData;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * HomeController constructor.
     *
     * @param BootstrapData $bootstrapData
     * @param Settings $settings
     */
    public function __construct(BootstrapData $bootstrapData, Settings $settings)
    {
        $this->bootstrapData = $bootstrapData;
        $this->settings = $settings;
    }

    /**
	 * Show the application home screen to the user.
	 *
	 * @return View
	 */
	public function index()
	{
        $htmlBaseUri = '/';

        //get uri for html "base" tag
        if (substr_count(url(''), '/') > 2) {
            $htmlBaseUri = parse_url(url(''))['path'] . '/';
        }

        return view('app')
            ->with('bootstrapData', $this->bootstrapData->get())
            ->with('htmlBaseUri', $htmlBaseUri)
            ->with('settings', $this->settings);
	}
}
