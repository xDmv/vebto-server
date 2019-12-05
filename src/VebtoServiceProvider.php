<?php

namespace Vebto;

use Vebto\Billing\SyncBillingPlansCommand;
use Vebto\Bootstrap\Middleware\RestrictDemoSiteFunctionality;
use Vebto\Commands\SeedCommand;
use Vebto\Localizations\Commands\ExportTranslations;
use Vebto\Mail\MailTemplate;
use Vebto\Policies\MailTemplatePolicy;
use Vebto\Policies\ReportPolicy;
use Vebto\Auth\User;
use Gate;
use Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;
use Vebto\Appearance\Commands\GenerateCssTheme;
use Vebto\Files\Upload;
use Vebto\Groups\Group;
use Vebto\Localizations\Localization;
use Vebto\Pages\Page;
use Vebto\Policies\AppearancePolicy;
use Vebto\Policies\LocalizationPolicy;
use Vebto\Policies\PagePolicy;
use Vebto\Policies\PermissionPolicy;
use Vebto\Policies\SettingPolicy;
use Vebto\Policies\UploadPolicy;
use Vebto\Policies\UserPolicy;
use Vebto\Policies\GroupPolicy;
use Vebto\Settings\Setting;

class VebtoServiceProvider extends ServiceProvider
{
    const CONFIG_FILES = ['permissions', 'default-settings', 'site', 'demo'];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');
        $this->loadViewsFrom(__DIR__.'/views', 'vebto');

        $this->registerPolicies();
        $this->registerCustomValidators();
        $this->registerCommands();
        $this->registerMiddleware();

        $configs = collect(self::CONFIG_FILES)->mapWithKeys(function($file) {
            return [__DIR__."/Config/$file.php" => config_path("vebto/$file.php")];
        })->toArray();

        $this->publishes($configs);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();

        //register socialite service provider and alias
        $this->app->register(SocialiteServiceProvider::class);
        $loader->alias('Socialite', Socialite::class);

        $this->deepMergeDefaultSettings(__DIR__."/Config/default-settings.php", "vebto.default-settings");
        $this->deepMergeConfigFrom(__DIR__."/Config/demo-blocked-routes.php", "vebto.demo-blocked-routes");
        $this->deepMergeConfigFrom(__DIR__."/Config/permissions.php", "vebto.permissions");
        $this->mergeConfigFrom(__DIR__."/Config/site.php", "vebto.site");
    }

    /**
     * Register package middleware.
     */
    private function registerMiddleware()
    {
        if ($this->app['config']->get('vebto.site.demo')) {
            $this->app['router']->pushMiddlewareToGroup('web', RestrictDemoSiteFunctionality::class);
        }
    }

    /**
     * Register custom validation rules with laravel.
     */
    private function registerCustomValidators()
    {
        Validator::extend('hash', 'Vebto\Validators\HashValidator@validate');
        Validator::extend('email_confirmed', 'Vebto\Validators\EmailConfirmedValidator@validate');
    }

    /**
     * Deep merge the given configuration with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    private function deepMergeConfigFrom($path, $key)
    {
        $config = $this->app['config']->get($key, []);
        $this->app['config']->set($key, array_merge_recursive(require $path, $config));
    }

    private function registerPolicies()
    {
        Gate::policy('App\Model', 'App\Policies\ModelPolicy');
        Gate::policy(Upload::class, UploadPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy('PermissionPolicy', PermissionPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(Localization::class, LocalizationPolicy::class);
        Gate::policy('AppearancePolicy', AppearancePolicy::class);
        Gate::policy('ReportPolicy', ReportPolicy::class);
        Gate::policy(MailTemplate::class, MailTemplatePolicy::class);
    }

    private function registerCommands()
    {
        $this->commands([
            GenerateCssTheme::class,
            ExportTranslations::class,
            SeedCommand::class,
            SyncBillingPlansCommand::class,
        ]);
    }

    /**
     * Deep merge "default-settings" config values.
     *
     * @param string $path
     * @param string $key
     * @return array
     */
    private function deepMergeDefaultSettings($path, $configKey)
    {
        $defaultSettings = require $path;
        $userSettings = $this->app['config']->get($configKey, []);

        foreach ($userSettings as $userSetting) {
            //remove default setting, if it's overwritten by user setting
            foreach ($defaultSettings as $key => $defaultSetting) {
                if ($defaultSetting['name'] === $userSetting['name']) {
                    unset($defaultSettings[$key]);
                }
            }

            //push user setting into default settings array
            $defaultSettings[] = $userSetting;
        }

        $this->app['config']->set($configKey, $defaultSettings);
    }
}
