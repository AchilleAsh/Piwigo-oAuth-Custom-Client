<?php

return array(
  'Facebook' => array(
    'name'              => 'Facebook',
    'require_client_id' => true,
    'new_app_link'      => 'https://developers.facebook.com/apps',
    'scope'             => 'email',
  ),
  'Google' => array(
    'name'              => 'Google',
    'require_client_id' => true,
    'new_app_link'      => 'https://cloud.google.com/console/project',
    'scope'             => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',
  ),
  'LinkedIn' => array( 
    'name'              => 'LinkedIn',
    'new_app_link'      => 'https://www.linkedin.com/secure/developer',
  ),
  'Webteam' => array(
    'name'              => 'Webteam',
    'require_client_id' => true,
    'new_app_link'      => 'https://webteam.ensea.fr/dev',
    'scope'             => 'user',
  ),
);