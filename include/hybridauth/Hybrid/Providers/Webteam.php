<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Webteam provider adapter based on OAuth2 protocol
 * Custom Client for a OAuth2 Server based on Symfony2 ; URL are dummies
 * see FOSOAuthServerBundle for Symfony2
 * added by Timothe Perez | https://github.com/AchilleAsh
 */

class Hybrid_Providers_Webteam extends Hybrid_Provider_Model_OAuth2
{
	// default permissions 
	public $scope = "user";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->authorize_url  = "https://webteam-dev.ensea.fr/oauth/v2/auth";
		$this->api->token_url      = "https://webteam-dev.ensea.fr/oauth/v2/token";
		//$this->api->token_info_url
	}

	function loginFinish()
	{
		$error = (array_key_exists('error',$_REQUEST))?$_REQUEST['error']:"";

		// check for errors
		if ( $error ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an error: $error", 5 );
		}

		// try to authenticate user
		$code = (array_key_exists('code',$_REQUEST))?$_REQUEST['code']:"";
		$response = $this->api->authenticate( $code );
		try{
			
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6);
		}

		// check if authenticated
		if (/*  !property_exists($response,'user_id')  || */! $this->api->access_token ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		// store tokens
		$this->token( "access_token" , $this->api->access_token  );
		$this->token( "refresh_token", $this->api->refresh_token );
		$this->token( "expires_in"   , $this->api->access_token_expires_in );
		$this->token( "expires_at"   , $this->api->access_token_expires_at );

		// store user id. it is required for api access to Vkontakte
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.user_id", $response->user_id );

		// set user connected locally
		$this->setUserConnected();
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// refresh tokens if needed 
		$this->refreshToken();

		
		// ask Webteam API for user information
		$response = $this->api->api( "https://webteam-dev.ensea.fr/api/profile" , 'GET');

		if (!isset( $response ) || !isset( $response->{'id'} ) || isset( $response->error ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		//$response = $response->response[0];
		$this->user->profile->identifier    = (property_exists($response,'id'))?$response->id:"";
        $this->user->profile->email         = (property_exists($response,'mail'))?$response->email:"";
		$this->user->profile->firstName     = (property_exists($response,'firstName'))?$response->firstName:"";
		$this->user->profile->lastName      = (property_exists($response,'lastName'))?$response->lastName:"";
		$this->user->profile->displayName   = (property_exists($response,'username'))?$response->username:"";
		$this->user->profile->photoURL      = (property_exists($response,'photo'))?$response->photo:"";
		$this->user->profile->profileURL    = (property_exists($response,'id'))?"https://webteam-dev.ensea.fr/profile/" . $response->id:"";

		if(property_exists($response,'gender')){
			switch ($response->sex)
			{
				case 1: $this->user->profile->gender = 'female'; break;
				case 0: $this->user->profile->gender = 'male'; break;
				default: $this->user->profile->gender = ''; break;
			}
		}
        // To be reimplemented

		/*if( property_exists($response,'bdate') ){
			list($birthday_year, $birthday_month, $birthday_day) = explode( '.', $response->bdate );

			$this->user->profile->birthDay   = (int) $birthday_day;
			$this->user->profile->birthMonth = (int) $birthday_month;
			$this->user->profile->birthYear  = (int) $birthday_year;
		}*/

		return $this->user->profile;
	}
}