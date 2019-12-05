<?php namespace Vebto\Settings;

use App;
use Artisan;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Vebto\Bootstrap\Controller;

class SettingsController extends Controller {

    /**
     * Settings service instance.
     *
     * @var App\Services\Settings;
     */
    private $settings;

    /**
     * Laravel Request instance.
     *
     * @var Request;
     */
    private $request;

    /**
     * @var DotEnvEditor
     */
    private $dotEnv;

    /**
     * SettingsController constructor.
     *
     * @param Request $request
     * @param Settings $settings
     * @param DotEnvEditor $dotEnv
     */
    public function __construct(Request $request, Settings $settings, DotEnvEditor $dotEnv)
    {
        $this->request  = $request;
        $this->settings = $settings;
        $this->dotEnv = $dotEnv;
    }

    /**
     * Get all application settings.
     *
     * @return array
     */
    public function index()
    {
        $this->authorize('index', Setting::class);

        return ['server' => $this->dotEnv->load(), 'client' => $this->settings->all(true)];
    }

    /**
     * Persist given settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function persist()
    {
        $this->authorize('update', Setting::class);

        $data = $this->settings->decodeSettingsString($this->request->get('settings'));

        if (Arr::get($data, 'server')) $this->dotEnv->write($data['server']);
        if (Arr::get($data, 'client')) $this->settings->save($data['client']);

        Artisan::call('cache:clear');

        return $this->success();
    }
}
