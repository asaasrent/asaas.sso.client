

## Installation
Using composer:
`composer require asaas.rent/sso-client:2.0.0`

## Example
In this example, we will show how to use the package to connect to the SSO server and manage users. For simplicity, we will use the Laravel framework, the same concept can be applied to any framework.

### Initializing the API client
We will create a singleton object of our API client and use Facade object to access it.
#### 1. Create the service provider
Under `app/Providers` folder, create a new file with the following content
```
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Asaas\SSO\ApiClient as SsoClient;
use Session;

class SsoApiClientServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sso_client', function ($app) {
            $client = new SsoClient(config('app.sso'));
            if(Session::has('access_token')){
                $client->setAccessToken(Session::get('access_token'));
            }
            return $client;
        });
    }
}
```

* `$client = new SsoClient(config('app.sso'));`
will inject the application config and create a new object of the API client.
* In this code block, we set the access token if it exists, the token will be retrieved after a successful login
    ```
    if(Session::has('access_token')){ 
        $client->setAccessToken(Session::get('access_token')); 
    }
    ```

#### 2. creating the Facade class
Create a new folder inside `app` folder and create a new file with the following content:
```
<?php 

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class SsoApiClientFacade extends Facade 
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'sso_client'; }

}
```

#### 3. configuring the provider and the aliase
Next, we need to edit the `config\app.php` file to autoload the API client, add the following line to the `providers`
`App\Providers\SsoApiClientServiceProvider::class,`
And the following line to the `aliases`
`'SsoApiClient' => App\Facades\SsoApiClientFacade::class,`

---
### SSO Login
If the user is not logged in yet, we need to redirect him to the SSO login page, to do this, we need to edit `Http/Controllers/Auth/LoginController.php`

```
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use SsoApiClient;

class LoginController extends Controller
{

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;


    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function showLoginForm(){
        return redirect()->away(SsoApiClient::generateRedirectUrl());
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->away(config('app.sso.sso_server_logout_url'));
    }
}
```
We overrided 2 functions here, `showLoginForm` and `loggedOut`.

`return redirect()->away(SsoApiClient::generateRedirectUrl());`

This line will generate the redirect URL and redirect the user to the SSO login page.

When the user log out, the application will redirect him to the SSO server logout page also:

`return redirect()->away(config('app.sso.sso_server_logout_url'));`

After the user login to the SSO server, he will be redirected again to the main website with code to exchange it with an access token, the response of a successful login will be like:
```
{
    "encrypted": true,
    "error": null,
    "message": "user found",
    "data": {
        "user": {
            "id": 29,
            "name": "ali",
            "email": "werwere@werwere.com"
        },
        "parent_user": {
            "id": 15,
            "name": "hosam",
            "email": "hosam@gmail.com"
        }
    }
}
```
The SSO server will use the application redirect URL, so we need to add a route for that URL, in our example, the route of the redirect URL will be:
`Route::get('/sso/login', 'SSOClientController@ssoLogin');`


Our `SSOClientController` will look like:
```
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Log;
use Artisan;
use App\User;
use SsoApiClient;
use App\Http\Managers\DatabaseManager;

class SSOClientController extends Controller
{

    public function ssoLogin(Request $request) {
        /**
        * login function will used  to exchange the authorization code with an access token, the function accepts an optional
        * closure function that will be executed after the token exchange, here, we passed closure function to save the
        * access token in the session to use it later.
        */
        $ssoResponse = SsoApiClient::login($request->authorization_code, function($response) use ($request){
            $request->session()->put('access_token', $response['data']['token']);
        });
       
        // Redirect the user to the login page if there is an error.
        if(!$ssoResponse || !is_null($ssoResponse['error'])){
            return redirect(config('app.sso.sso_server_login_url'));
            die();
        }
        
        // If the application is a multi-tenant application, we can use the parent user id to connect to the 
        // right database. parent_user user here represents the super admin user.
        $ssoUserid = data_get($ssoResponse, 'data.parent_user.id', $ssoResponse['data']['user']['id']);
        DatabaseManager::initMainDB($ssoUserid);

        // After connecting to the database, we will select the user based on it's sso id
        $user = User::where('sso_user_id', $ssoResponse['data']['user']['id'])->first();

        // Login the user
        Auth::login($user);
        
        return redirect('/');
    }
}
```

----

#### Add/Edit/Delete users
##### Add user example:
```
$userData = [
    'user' => [
        'name' => 'name',
        'email' => 'email',
        'password' => '123456',
    ]
];

$ssoResponse = SsoApiClient::addUser($userData);
```

##### Edit user example:
```
$userData = [
	'id' => 
        'name' => 'name',
        'email' => 'email',
        'password' => 'new password',
    ]
];

$ssoResponse = SsoApiClient::editUser($userData);
```

##### Delete user example:
```
$ssoResponse = SsoApiClient::deleteUser(15);
```