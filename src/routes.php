<?php

Route::group(['prefix' => 'secure', 'middleware' => 'web'], function () {
    //BOOTSTRAP
    Route::get('bootstrap-data', 'Vebto\Bootstrap\BootstrapController@getBootstrapData');

    //AUTH ROUTES
    Route::post('auth/register', 'Vebto\Auth\Controllers\RegisterController@register');
    Route::post('auth/login', 'Vebto\Auth\Controllers\LoginController@login');
    Route::post('auth/logout', 'Vebto\Auth\Controllers\LoginController@logout');
    Route::post('auth/password/email', 'Vebto\Auth\Controllers\ForgotPasswordController@sendResetLinkEmail');
    Route::post('auth/password/reset', 'Vebto\Auth\Controllers\ResetPasswordController@reset')->name('password.reset');
    Route::get('auth/email/confirm/{code}', 'Vebto\Auth\Controllers\ConfirmEmailController@confirm');

    //SOCIAL AUTHENTICATION
    Route::get('auth/social/{provider}/connect', 'Vebto\Auth\Controllers\SocialAuthController@connect');
    Route::get('auth/social/{provider}/login', 'Vebto\Auth\Controllers\SocialAuthController@login');
    Route::get('auth/social/{provider}/callback', 'Vebto\Auth\Controllers\SocialAuthController@loginCallback');
    Route::post('auth/social/extra-credentials', 'Vebto\Auth\Controllers\SocialAuthController@extraCredentials');
    Route::post('auth/social/{provider}/disconnect', 'Vebto\Auth\Controllers\SocialAuthController@disconnect');

    //USERS
    Route::get('users', 'Vebto\Auth\Controllers\UsersController@index');
    Route::get('users/{id}', 'Vebto\Auth\Controllers\UsersController@show');
    Route::post('users', 'Vebto\Auth\Controllers\UsersController@store');
    Route::put('users/{id}', 'Vebto\Auth\Controllers\UsersController@update');
    Route::delete('users/delete-multiple', 'Vebto\Auth\Controllers\UsersController@deleteMultiple');
    
    Route::put('users/{id}/subscrib', 'Vebto\Auth\Controllers\UsersController@subscrib');

    //GROUPS
    Route::get('groups', 'Vebto\Groups\GroupsController@index');
    Route::post('groups', 'Vebto\Groups\GroupsController@store');
    Route::put('groups/{id}', 'Vebto\Groups\GroupsController@update');
    Route::delete('groups/{id}', 'Vebto\Groups\GroupsController@destroy');
    Route::post('groups/{id}/add-users', 'Vebto\Groups\GroupsController@addUsers');
    Route::post('groups/{id}/remove-users', 'Vebto\Groups\GroupsController@removeUsers');

    //USER PASSWORD
    Route::post('users/{id}/password/change', 'Vebto\Auth\Controllers\ChangePasswordController@change');

    //USER AVATAR
    Route::post('users/{id}/avatar', 'Vebto\Auth\Controllers\UserAvatarController@store');
    Route::delete('users/{id}/avatar', 'Vebto\Auth\Controllers\UserAvatarController@destroy');

    //USER GROUPS
    Route::post('users/{id}/groups/attach', 'Vebto\Groups\UserGroupsController@attach');
    Route::post('users/{id}/groups/detach', 'Vebto\Groups\UserGroupsController@detach');

    //USER PERMISSIONS
    Route::post('users/{id}/permissions/add', 'Vebto\Auth\UserPermissionsController@add');
    Route::post('users/{id}/permissions/remove', 'Vebto\Auth\UserPermissionsController@remove');
	
	Route::post('users/{id}/adslimit', 'Vebto\Auth\UsersController@adslimit');

    //UPLOADS
	Route::get('uploads', 'Vebto\Files\UploadsController@index');
	Route::post('uploads/images', 'Vebto\Files\PublicUploadsController@images');
	Route::post('uploads/videos', 'Vebto\Files\PublicUploadsController@videos');
	Route::get('uploads/{id}', 'Vebto\Files\UploadsController@show');
	Route::post('uploads', 'Vebto\Files\UploadsController@store');
    Route::delete('uploads', 'Vebto\Files\UploadsController@destroy');

    //PAGES
    Route::get('pages', 'Vebto\Pages\PagesController@index');
    Route::get('pages/{id}', 'Vebto\Pages\PagesController@show');
    Route::post('pages', 'Vebto\Pages\PagesController@store');
    Route::put('pages/{id}', 'Vebto\Pages\PagesController@update');
    Route::delete('pages', 'Vebto\Pages\PagesController@destroy');

    //VALUE LISTS
    Route::get('value-lists/{name}', 'Vebto\ValueLists\ValueListsController@getValueList');

    //SETTINGS
    Route::get('settings', 'Vebto\Settings\SettingsController@index');
    Route::post('settings', 'Vebto\Settings\SettingsController@persist');

    //APPEARANCE EDITOR
    Route::post('admin/appearance', 'Vebto\Appearance\AppearanceController@save');
    Route::get('admin/appearance/values', 'Vebto\Appearance\AppearanceController@getValues');

    //LOCALIZATIONS
    Route::get('localizations', 'Vebto\Localizations\LocalizationsController@index');
    Route::post('localizations', 'Vebto\Localizations\LocalizationsController@store');
    Route::put('localizations/{id}', 'Vebto\Localizations\LocalizationsController@update');
    Route::delete('localizations/{id}', 'Vebto\Localizations\LocalizationsController@destroy');
    Route::get('localizations/{name}', 'Vebto\Localizations\LocalizationsController@show');

    //MAIL TEMPLATES
    Route::get('mail-templates', 'Vebto\Mail\MailTemplatesController@index');
    Route::post('mail-templates/render', 'Vebto\Mail\MailTemplatesController@render');
    Route::post('mail-templates/{id}/restore-default', 'Vebto\Mail\MailTemplatesController@restoreDefault');
    Route::put('mail-templates/{id}', 'Vebto\Mail\MailTemplatesController@update');

    //OTHER ADMIN ROUTES
    Route::get('admin/analytics/stats', 'App\Http\Controllers\AnalyticsController@stats');
    Route::post('cache/clear', 'App\Http\Controllers\CacheController@clear');
    
    //OTHER ADMIN ROUTES STATISTICS
    Route::get('admin/statistics/stats', 'App\Http\Controllers\StatisticsController@stats');    
    
    Route::get('admin/statistics/trends', 'App\Http\Controllers\StatisticsController@trends');    
    Route::get('admin/statistics/sales', 'App\Http\Controllers\StatisticsController@sales');    
    Route::get('admin/statistics/shares', 'App\Http\Controllers\StatisticsController@shares');    

    //billing plans
    Route::get('billing/plans', 'Vebto\Billing\Plans\BillingPlansController@index');
    Route::post('billing/plans', 'Vebto\Billing\Plans\BillingPlansController@store');
    Route::post('billing/plans/sync', 'Vebto\Billing\Plans\BillingPlansController@sync');
    Route::put('billing/plans/{id}', 'Vebto\Billing\Plans\BillingPlansController@update');
    Route::delete('billing/plans', 'Vebto\Billing\Plans\BillingPlansController@destroy');

    //subs
    Route::get('billing/subscriptions', 'Vebto\Billing\Subscriptions\SubscriptionsController@index');
    Route::post('billing/subscriptions', 'Vebto\Billing\Subscriptions\SubscriptionsController@store');
    Route::post('billing/subscriptions/stripe', 'Vebto\Billing\Gateways\Stripe\StripeController@createSubscription');
    Route::post('billing/subscriptions/paypal/agreement/create', 'Vebto\Billing\Gateways\Paypal\PaypalController@createSubscriptionAgreement');
    Route::post('billing/subscriptions/paypal/agreement/execute', 'Vebto\Billing\Gateways\Paypal\PaypalController@executeSubscriptionAgreement');
    Route::delete('billing/subscriptions/{id}', 'Vebto\Billing\Subscriptions\SubscriptionsController@cancel');
    Route::put('billing/subscriptions/{id}', 'Vebto\Billing\Subscriptions\SubscriptionsController@update');
    Route::post('billing/subscriptions/{id}/resume', 'Vebto\Billing\Subscriptions\SubscriptionsController@resume');
    Route::post('billing/subscriptions/{id}/change-plan', 'Vebto\Billing\Subscriptions\SubscriptionsController@changePlan');
    Route::post('billing/stripe/cards/add', 'Vebto\Billing\Gateways\Stripe\StripeController@addCard');
});

//paypal
Route::get('billing/paypal/callback/approved', 'Vebto\Billing\Gateways\Paypal\PaypalController@approvedCallback');
Route::get('billing/paypal/callback/canceled', 'Vebto\Billing\Gateways\Paypal\PaypalController@canceledCallback');

//stripe webhook
Route::post('billing/stripe/webhook', 'Vebto\Billing\Webhooks\StripeWebhookController@handleWebhook');
Route::post('billing/paypal/webhook', 'Vebto\Billing\Gateways\Paypal\PaypalWebhookController@handleWebhook');