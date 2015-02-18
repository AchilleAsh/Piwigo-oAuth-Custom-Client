<?php
defined('OAUTH_PATH') or die('Hacking attempt!');

function load_hybridauth_conf()
{
  global $hybridauth_conf, $conf;
  
  if (file_exists(PHPWG_ROOT_PATH.OAUTH_CONFIG))
  {
    $hybridauth_conf = include(PHPWG_ROOT_PATH.OAUTH_CONFIG);
    $hybridauth_conf['base_url'] = OAUTH_PUBLIC;
    if (!empty($conf['oauth_debug_file']))
    {
      $hybridauth_conf['debug_mode'] = true;
      $hybridauth_conf['debug_file'] = $conf['oauth_debug_file'];
    }
    return true;
  }
  else
  {
    return false;
  }
}

function oauth_assign_template_vars($u_redirect=null)
{
  global $template, $conf, $hybridauth_conf, $user;
  
  $conf['oauth']['include_common_template'] = true;
  
  if ($template->get_template_vars('OAUTH') == null)
  {
    if (!empty($user['oauth_id']))
    {
      list($provider, $identifier) = explode('---', $user['oauth_id'], 2);
      if ($provider == 'Persona')
      {
        $persona_email = $identifier;
      }
    }

    $template->assign('OAUTH', array(
      'conf' => $conf['oauth'],
      'u_login' => get_root_url() . OAUTH_PATH . 'auth.php?provider=',
      'providers' => $hybridauth_conf['providers'],
      'persona_email' => @$persona_email,
      'key' => get_ephemeral_key(0),
      ));
    $template->assign(array(
      'OAUTH_PATH' => OAUTH_PATH,
      'OAUTH_ABS_PATH' => realpath(OAUTH_PATH) . '/',
      'ABS_ROOT_URL' => rtrim(get_gallery_home_url(), '/') . '/',
      ));
  }
  
  if (isset($u_redirect))
  {
    $template->append('OAUTH', compact('u_redirect'), true);
  }
}

function get_oauth_id($user_id)
{
  $query = '
SELECT oauth_id FROM ' . USER_INFOS_TABLE . '
  WHERE user_id = ' . $user_id . '
  AND oauth_id != ""
;';
  $result = pwg_query($query);
  
  if (!pwg_db_num_rows($result))
  {
    return null;
  }
  else
  {
    list($oauth_id) = pwg_db_fetch_row($result);
    return $oauth_id;
  }
}

function get_servername($with_port=false)
{
  $scheme = 'http';
  if ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 )
  {
    $scheme = 'https';
  }
  
  $servername = $scheme . '://' . $_SERVER['HTTP_HOST'];
  if ($with_port)
  {
    $servername.= ':' . $_SERVER['SERVER_PORT'];
  }
    
  return $servername;
}



/**
 * Creates a new user (use to bypass the register page if disabled)
 *
 * @param string $login
 * @param string $password
 * @param string $mail_address
 * @param bool $notify_admin
 * @param array &$errors populated with error messages
 * @param bool $notify_user
 * @return int|false user id or false
 */
function oauth_register_user($login, $password, $mail_address, $errors = array())
{
  global $conf;

  if ($login == '')
  {
    $errors[] = l10n('Please, enter a login');
  }
  if (preg_match('/^.* $/', $login))
  {
    $errors[] = l10n('login mustn\'t end with a space character');
  }
  if (preg_match('/^ .*$/', $login))
  {
    $errors[] = l10n('login mustn\'t start with a space character');
  }
  if (get_userid($login))
  {
    $errors[] = l10n('this login is already used');
  }
  if ($login != strip_tags($login))
  {
    $errors[] = l10n('html tags are not allowed in login');
  }
  $mail_error = validate_mail_address(null, $mail_address);
  if ('' != $mail_error)
  {
    $errors[] = $mail_error;
  }

  if ($conf['insensitive_case_logon'] == true)
  {
    $login_error = validate_login_case($login);
    if ($login_error != '')
    {
      $errors[] = $login_error;
    }
  }

  $errors = trigger_change(
      'register_user_check',
      $errors,
      array(
          'username'=>$login,
          'password'=>$password,
          'email'=>$mail_address,
      )
  );

  // if no error until here, registration of the user
  if (count($errors) == 0)
  {
    $insert = array(
        $conf['user_fields']['username'] => pwg_db_real_escape_string($login),
        $conf['user_fields']['password'] => $conf['password_hash']($password),
        $conf['user_fields']['email'] => $mail_address
    );

    single_insert(USERS_TABLE, $insert);
    $user_id = pwg_db_insert_id();

    // Assign by default groups
    $query = '
SELECT id
  FROM '.GROUPS_TABLE.'
  WHERE is_default = \''.boolean_to_string(true).'\'
  ORDER BY id ASC
;';
    $result = pwg_query($query);

    $inserts = array();
    while ($row = pwg_db_fetch_assoc($result))
    {
      $inserts[] = array(
          'user_id' => $user_id,
          'group_id' => $row['id']
      );
    }

    if (count($inserts) != 0)
    {
      mass_inserts(USER_GROUP_TABLE, array('user_id', 'group_id'), $inserts);
    }
    create_user_infos($user_id, null);


    trigger_notify(
        'register_user',
        array(
            'id'=>$user_id,
            'username'=>$login,
            'email'=>$mail_address,
        )
    );

    return $user_id;
  }
  else
  {
    return false;
  }
}