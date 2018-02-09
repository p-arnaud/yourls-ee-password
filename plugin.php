<?php
/*
Plugin Name: YOURLS EE Password
Plugin URI: https://github.com/p-arnaud/yourls-ee-password
Description: This plugin enables the feature of password for your short URLs.
Version: 1.1
Author: p-arnaud
Author URI: https://github.com/p-arnaud
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// Add column to admin's url listing
yourls_add_filter('table_head_cells', 'ee_password_table_head_cells');
function ee_password_table_head_cells($args) {
    $ee_multi_users_plugin = yourls_is_active_plugin('yourls-ee-multi-users/plugin.php');
    if ($ee_multi_users_plugin == 1 and ee_multi_users_is_admin(YOURLS_USER) === true) {
        return $args;
    }
    else {
        $args['password'] = 'Password';
    }
    return $args;
}
// Show password in admin's url listing
yourls_add_filter('table_add_row_cell_array', 'ee_password_table_add_row_cell_array');
function ee_password_table_add_row_cell_array($args) {
    $ee_multi_users_plugin = yourls_is_active_plugin('yourls-ee-multi-users/plugin.php');
    if ($ee_multi_users_plugin == 1 and ee_multi_users_is_admin(YOURLS_USER) === true) {

    }
    else {
      global $ydb;
      $ee_password_array = json_decode( $ydb->option[ 'ee_password' ], true );

      if ($ee_password_array[$args['keyword']['keyword_html']]) {
          $password = $ee_password_array[$args['keyword']['keyword_html']];
      } else {
        $password = "";
      }

      $args['password'] = array(
        'template' => '%password%',
        'password' => '<a " href=plugins.php?page=ee_password&shortname=' . $args['keyword']['keyword_html'] . '><img src="../images/pencil.png"/></a> ' . $password,
      );
  }
  return $args;
}

// Do redirection
yourls_add_action( 'pre_redirect', 'ee_check_password' );
function ee_check_password( $args ) {
	global $ydb;
	if( !isset($ydb->option[ 'ee_password' ]) ){
		yourls_add_option( 'ee_password', 'null' );
	}

	$ee_password_fullurl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$ee_password_urlpath = parse_url( $ee_password_fullurl, PHP_URL_PATH );
	$ee_password_pathFragments = explode( '/', $ee_password_urlpath );
	$ee_password_short = end( $ee_password_pathFragments );

  $ee_password_array = json_decode( $ydb->option[ 'ee_password' ], true );
  if( array_key_exists( $ee_password_short, $ee_password_array ) ){
    if( (isset( $_POST[ 'password' ] ) && ($_POST[ 'password' ] != $ee_password_array[ $ee_password_short ])) || !isset( $_POST[ 'password' ] ) ) {
      $error = ( isset( $_POST[ 'password' ] ) ? "\n<br><span style='color: red;'><u>". yourls__( "Incorrect Password", "ee_password" ). "</u></span>" : "");
      $ee_ppu =    yourls__( "Password Protected URL",                       "ee_password" ); //Translate Password Title
      $ee_ph =     yourls__( "Password"                                    , "ee_password" ); //Translate the word Password
      $ee_sm =     yourls__( "Please enter the password below to continue.", "ee_password" ); //Translate the main message
      $ee_submit = yourls__( "Send!"                                       , "ee_password" ); //Translate the Submit button
      //Displays main "Insert Password" area
      echo <<<PWP
    <style>
      #password {
        background-color: #e8e8e8;
        box-shadow: 0 10px 16px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19) !important;

        width: 400px !important;
        height: 220px !important;

        position: fixed;
        top: 50%;
        left: 50%;
        /* bring your own prefixes */
        transform: translate(-50%, -50%);
      }

      #password form {
        margin-top: 30px;
        margin-left: 14px;
        width: 95%;
        height: 20px;
      }

      #password input[type="password"]{
        box-sizing: border-box;
        border-radius: 4px;
        margin-left: 14px;
        padding: 10px;
        border: none;
        height: 30px;
        width: 84%;
        /* background-color: #3CBC8D;
        color: white; */
      }

        #password input[type="password"]:focus{
          outline: none;
          background-color: inherit;
        }

        #password input[type="submit"]{
          box-sizing: border-box;
          border-radius: 4px;
          margin-left: 14px;
          border: none;
          height: 30px;
          width: 84%;

          background-color: lightgrey;
          outline: aqua !important;
          outline-color: grey;
        }
        #password input[type="submit"]:focus{
          box-shadow: 0 10px 16px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19) !important;
          background-color: inherit;
          outline: aqua !important;
          outline-color: red;
        }
      </style>
      <div id="password">
        <center>
          <br><span style="font-size: 35px;"><u>$ee_ppu</u></span>$error
        </center>
        <form method="post">
          <p><i>$ee_sm</i></p>
          <input type="password" name="password" placeholder="$ee_ph"><br><br>
          <input type="submit" value="$ee_submit"><br><br>
        </form>
      </div>
PWP;
      die();
    }
  }
}

// Register plugin page in admin page
yourls_add_action( 'plugins_loaded', 'ee_password_display_panel' );
function ee_password_display_panel() {
	yourls_register_plugin_page( 'ee_password', 'YOURLS EE Password', 'ee_password_display_page' );
}

// Function which will draw the admin page
function ee_password_display_page() {
	global $ydb;

	if( isset( $_POST[ 'password-checked' ] ) && isset( $_POST[ 'password' ] ) || isset( $_POST[ 'password-unchecked' ] ) ) {
		ee_password_process_new();
	} else {
		if( !isset( $ydb->option[ 'ee_password' ] ) ){
			yourls_add_option( 'ee_password', 'null' );
		}
	}
  ee_password_process_display();
}

// Set/Delete date from DB
function ee_password_process_new() {
  global $ydb;
	$ee_password_array = json_decode( $ydb->option[ 'ee_password' ], true ); //Get's array of currently active Date Protected URLs
  $ee_multi_users_plugin = yourls_is_active_plugin('yourls-ee-multi-users/plugin.php');
  $user_keywords = array();
  if ($ee_multi_users_plugin == 1) {
    $user_keywords = ee_multi_users_get_current_user_keywords();
  }

  // Sanitize
  foreach ($_POST[ 'password' ] as $key => $value) {
    if (array_search($key, $user_keywords) !== false) {
      $sanitized = yourls_sanitize_string($value);
      if ($sanitized === false) {
        unset($ee_password_array[$key]);
      } else {
        $ee_password_array[$key] = yourls_sanitize_string($value);
      }
    }
  }
  foreach ( $ee_password_array as $key => $value ){
      if ($ee_multi_users_plugin == 0 || array_search($key, $user_keywords) !== false) {
        if (array_search($key, array_keys($_POST['password'])) === false) {
          unset($ee_password_array[ $key ]);
        }
      }
  }
  yourls_update_option( 'ee_password', json_encode( $ee_password_array ) );
	echo "<p style='color: green'>Success!</p>";
  return yourls_apply_filter( 'ee_password_process_new', $_POST );
}

//Display Form
function ee_password_process_display() {
	global $ydb;
  $where = ee_multi_users_admin_list_where();
	$table = YOURLS_DB_TABLE_URL;
	$query = $ydb->get_results( "SELECT * FROM `$table` WHERE 1=1"  . $where );

	$ee_su = yourls__( "Short URL"   , "ee_password" ); //Translate "Short URL"
	$ee_ou = yourls__( "Original URL", "ee_password" ); //Translate "Original URL"
	$ee_pw = yourls__( "Password"    , "ee_password" ); //Translate "Password"

	echo <<<TB
	<style>
	table {
		border-collapse: collapse;
		width: 100%;
	}

	th, td {
		text-align: left;
		padding: 8px;
	}

	tr:nth-child(even){background-color: #f2f2f2}
	tr:nth-child(odd){background-color: #fff}
	</style>
	<div style="overflow-x:auto;">
		<form method="post">
			<table>
				<tr>
					<th>$ee_su</th>
					<th>$ee_ou</th>
					<th>$ee_pw</th>
				</tr>
TB;
	foreach( $query as $link ) { // Displays all shorturls in the YOURLS DB

  		$short = $link->keyword;
  		$url = $link->url;
  		$ee_password_array = json_decode( $ydb->option[ 'ee_password' ], true ); //Get's array of currently active Date Protected URLs

  		if( strlen( $url ) > 31 ) { //If URL is too long it will shorten it
  			$sURL = substr( $url, 0, 30 ). "...";
  		} else {
  			$sURL = $url;
  		}

      $password = null;
  		$password_text = yourls__( "Enable?" );
  		$password_date = '';
  		$password_checked = '';
  		$password_unchecked = ' disabled';
  		$password_style = 'display: none';
  		$password_disabled = ' disabled';
  		if( array_key_exists( $short, $ee_password_array ) ){ //Check's if URL is currently date protected or not
  			$text = yourls__( "Enable?" );
  			$password = $ee_password_array[ $short ];
  			$password_checked = " checked";
  			$password_unchecked = '';
  			$password_style = '';
  			$password_disabled = '';
  		}

     // Only show selected item if this page is called with 'shortname' parameter
     if ((isset($_GET['shortname']) && $_GET['shortname'] == $link->keyword) || !isset($_GET['shortname'])) {
       $display = 'table-row';
     }
     else {
      $display = 'none';
     }

  		echo <<<TABLE
  				<tr style=display:$display>
  					<td>$short</td>
  					<td><span title="$url">$sURL</span></td>
  					<td>
  						<input type="checkbox" name="password-checked[{$short}]" class="ee_password_checkbox" value="enable" data-input="password-$short"$password_checked> $text
  						<input type="hidden" name="password-unchecked[{$short}]" id="password-{$short}_hidden" value="true"$password_unchecked>
  						<input id="password-$short" type="text" name="password[$short]" style="$password_style" value="$password" placeholder="Password..."$password_disabled ><br>
  					</td>
  				</tr>
TABLE;
    // }
	}
	echo <<<END
			</table>
			<input type="submit" value="Submit">
		</form>
	</div>
	<script>
		$( ".ee_password_checkbox" ).click(function() {
			var dataAttr = "#" + this.dataset.input;
      console.log(dataAttr);
			$( dataAttr ).toggle();
			if( $( dataAttr ).attr( 'disabled' ) ) {
				$( dataAttr ).removeAttr( 'disabled' );

				$( dataAttr + "_hidden" ).attr( 'disabled' );
				$( dataAttr + "_hidden" ).prop('disabled', true);
			} else {
				$( dataAttr ).attr( 'disabled' );
				$( dataAttr ).prop('disabled', true);

				$( dataAttr + "_hidden" ).removeAttr( 'disabled' );
			}
		});
	</script>
END;
}

// Delete old settings when a link is delete
yourls_add_action( 'delete_link' , 'ee_password_delete_link');
function ee_password_delete_link( $args ) {
  $keyword = $args[0];
  global $ydb;
  $ee_password_array = json_decode( $ydb->option[ 'ee_password' ], true );
  unset( $ee_password_array[$keyword] );
  if ( count($ee_password_array) > 0) {
    yourls_update_option( 'ee_password', json_encode( $ee_password_array ) );
  }
  else {
    yourls_update_option( 'ee_password', null );
  }

}

yourls_add_filter( 'api_action_update', 'api_edit_url_update_password' );
function api_edit_url_update_password() {
  global $ydb;
  if( !isset( $ydb->option[ 'ee_password' ] ) ){
    yourls_add_option( 'ee_password', 'null' );
  }
  if( isset( $_REQUEST[ 'url-password-active' ]) && ( $_REQUEST[ 'url-password-active' ] === 'true' ) && isset( $_REQUEST[ 'url-password' ] ) ){
    $shorturl = yourls_sanitize_string($_REQUEST['shorturl']);
    $password = yourls_sanitize_string($_REQUEST[ 'url-password' ]);
    $ee_password_array = json_decode( $ydb->option[ 'ee_password' ], true );
    $ee_password_array[$shorturl] = $password;
    yourls_update_option( 'ee_password', json_encode( $ee_password_array ) );
  }
  elseif (isset( $_REQUEST[ 'url-password-active' ]) && $_REQUEST[ 'url-password-active' ] === 'false') {
    $shorturl = yourls_sanitize_string($_REQUEST['shorturl']);
    $ee_password_array = json_decode( $ydb->option[ 'ee_password' ], true );
    unset($ee_password_array[$shorturl] );
    yourls_update_option( 'ee_password', json_encode( $ee_password_array ) );
  }
}

?>
