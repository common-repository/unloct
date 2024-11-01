<?php

class core_unloct_login {
	
	protected function __construct() {
		$this->add_actions();
		register_activation_hook($this->my_plugin_basename(), array( $this, 'unloct_activation_hook' ) );
	}

	protected static $unloct_cookie_name = 'wordpress_unloct_login';
	
	public function unloct_activation_hook($network_wide) {
		global $unloct_core_already_exists;
		if ($unloct_core_already_exists) {
			deactivate_plugins( $this->my_plugin_basename() );
			echo( 'Please Deactivate the current version of Unloct before installing new versions.' );
			exit;
		}
	}
	
	protected $newcookievalue = null;
	protected function get_cookie_value() {
		if (!$this->newcookievalue) {
			if (isset($_COOKIE[self::$unloct_cookie_name])) {
				$this->newcookievalue = $_COOKIE[self::$unloct_cookie_name];
			}
			else {
				$this->newcookievalue = md5(rand());
			}
		}
		return $this->newcookievalue;
	}
	
	private $doneIncludePath = false;
	private function setIncludePath() {
		if (!$this->doneIncludePath) {
			set_include_path(plugin_dir_path(__FILE__) . PATH_SEPARATOR . get_include_path());
			$this->doneIncludePath = true;
		}
	}
	

	public function unloct_login_styles() {
		$options = $this->get_option_unloct_login();
		wp_enqueue_script('jquery');
		 ?>
		<style type="text/css">
			form#loginform p.galogin {
				background: none repeat scroll 0 0 #2EA2CC;
				border-color: #0074A2;
				box-shadow: 0 1px 0 rgba(120, 200, 230, 0.5) inset, 0 1px 0 rgba(0, 0, 0, 0.15);
				color: #FFFFFF;
				text-decoration: none;
				text-align: center;
				vertical-align: middle;
				border-radius: 3px;
				padding: 4px;
				height: 27px;
				font-size: 14px;
			}
			
			form#loginform p.galogin a {
				color: #FFFFFF;
				line-height: 27px;
				font-weight: bold;
			}

			form#loginform p.galogin a:hover {
				color: #CCCCCC;
			}
			
			h3.galogin-or {
				text-align: center;
				margin-top: 16px;
				margin-bottom: 16px;
			}

			
		 </style>
	<?php }
	

	public function unloct_start_auth_get_url() {
	    $options = $this->get_option_unloct_login();
	    if ($options['unloct_clientid'] == '' || $options['unloct_clientsecret'] == '') {
			$authUrl = "?error=ga_needs_configuring";
		}
		
		$authUrl = "https://unloct.com/oauth/authorize?client_id=" . $options['unloct_clientid'] . "&response_type=code&scope=*&redirect_uri=" . $this->get_login_url();
		return $authUrl;
	}
	
	public function unloct_login_form() {
		$options = $this->get_option_unloct_login();
		
		$authUrl = $this->unloct_start_auth_get_url();
		
	
		
?>
		<p class="galogin"> 
			<a href="<?php echo $authUrl; ?>"><?php echo esc_html($this->get_login_button_text()); ?></a>
		</p>
		
		<script>
		jQuery(document).ready(function(){
			<?php ob_start(); ?>
			
			var loginform = jQuery('#loginform,#front-login-form');
			var unloctlink = jQuery('p.galogin');
			var poweredby = jQuery('p.galogin-powered');

			loginform.prepend("<h3 class='galogin-or'><?php esc_html_e( 'or' , 'unloct-login'); ?></h3>");

			loginform.prepend(unloctlink);

			<?php 
				$fntxt = ob_get_clean(); 
				echo apply_filters('gal_login_form_readyjs', $fntxt);
			?>
		});
		
		</script>
<?php 	
	}
	
	protected function get_login_button_text() {
		$login_button_text = "Login with Unloct";
		return apply_filters('unloct_login_button_text', $login_button_text);
	}

	protected function get_redirect_url() {
		$options = $this->get_option_unloct_login();
		
		if (array_key_exists('redirect_to', $_REQUEST) && $_REQUEST['redirect_to']) {
			return sanitize_text_field($_REQUEST['redirect_to']);
		} 
		return '';
	}
	
	public function unloct_authenticate($user, $username=null, $password=null) {
		if (isset($_REQUEST['error'])) {
			switch ($_REQUEST['error']) {
				case 'access_denied':
					$error_message = __( 'You did not grant access' , 'unloct-login');
				break;
				default:
					$error_message = __( 'Unrecognized error message' , 'unloct-login');
				break;
			}
			$user = new WP_Error('unloct_login_error', $error_message);
			return $this->displayAndReturnError($user);
		}
		if(isset($_GET['code']))
		{
		    	$access_token = $this->get_access_token($_GET['code']);

        		if($access_token == "ERROR") {
        		    $user = new WP_Error('unloct_login_error', "not able to get access token");
        			return $this->displayAndReturnError($user);
        		}
        		
        		$user_details = $this->get_user_details($access_token);
        		if($user_details && $user_details->email) {
        		    	$user = get_user_by('email', $user_details->email);
        		    		if ($user == false) {
        		    		    $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
        		    		    $user_id  = wp_create_user(  $user_details->name, $random_password,  $user_details->email ); 
        		    		    if(!$user_id) {
        		    		        $user = new WP_Error('unloct_login_error', "Unable to create user");
        			                return $this->displayAndReturnError($user);
        		    		    }
                    			$user = get_user_by('email', $user_details->email);
                    		}
        		
        			    return $user;
        		}
        		else {
        		    $user = new WP_Error('unloct_login_error', "not able to get user details");
        			return $this->displayAndReturnError($user);
        		}
	    
		}
	
		$user = $this->checkRegularWPLogin($user, $username, $password, $options);
        $this->displayAndReturnError($user);
		
		if (is_wp_error($user)) {
			$this->displayAndReturnError($user);
		}
		
		return $user;
	}
	
	protected function checkRegularWPLogin($user, $username, $password, $options) {
		return $user;
	}

	protected function displayAndReturnError($user) {
		if (is_wp_error($user) && get_bloginfo('version') < 3.8) {
				global $error;
			$error = htmlentities2($user->get_error_message());
		}
		return $user;
	}
	
	protected $_final_redirect = '';
	
	protected function setFinalRedirect($redirect_to) {
		$this->_final_redirect = $redirect_to;
	}

	protected function getFinalRedirect() {
		return $this->_final_redirect;
	}
	
	public function unloct_login_redirect($redirect_to, $request_from='', $user=null) {
		if ($user && !is_wp_error($user)) {
			$final_redirect = $this->getFinalRedirect();
			if ($final_redirect !== '') {
				return $final_redirect;
			}
		}
		return $redirect_to;
	}
	
	public function unloct_init() {
		if (isset($_GET['code']) && isset($_GET['state']) && $_SERVER['REQUEST_METHOD']=='GET') {
			$options = $this->get_option_unloct_login();
		}
		if (!isset($_COOKIE[self::$unloct_cookie_name]) && apply_filters('gal_set_login_cookie', true)) {
			$secure = ( 'https' === parse_url( $this->get_login_url(), PHP_URL_SCHEME ) );
			setcookie(self::$unloct_cookie_name, $this->get_cookie_value(), 0, '/', defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '', $secure );
		}
	}
	
	protected function get_login_url() {
		$options = $this->get_option_unloct_login();
		$login_url = wp_login_url();

		if (force_ssl_admin() && strtolower(substr($login_url,0,7)) == 'http://') {
			$login_url = 'https://'.substr($login_url,7);
		}

		return apply_filters( 'gal_login_url', $login_url );
	}


	// ADMIN AND OPTIONS
	// *****************

	protected function get_options_menuname() {
		return 'unloctlogin_list_options';
	}
	
	protected function get_options_pagename() {
		return 'unloctlogin_options';
	}
	
	protected function get_settings_url() {
		return is_multisite()
			? network_admin_url( 'settings.php?page='.$this->get_options_menuname() )
			: admin_url( 'options-general.php?page='.$this->get_options_menuname() );
	}
	
	public function unloct_admin_auth_message() {
		echo '<div class="error"><p>';
		echo sprintf( __('You will need to complete Unloct <a href="%s">Settings</a> in order for the plugin to work', 'unloct-login'), 
					esc_url($this->get_settings_url()) ); 
		echo '</p></div>';
	}
	
	public function unloct_admin_init() {
		register_setting( $this->get_options_pagename(), $this->get_options_name(), Array($this, 'unloct_options_validate') );
				
		// Admin notice that configuration is required
		$options = $this->get_option_unloct_login();
		
		if (current_user_can( is_multisite() ? 'manage_network_options' : 'manage_options' ) 
				&& ($options['unloct_clientid'] == '' || $options['unloct_clientsecret'] == '')) {

			if (!array_key_exists('page', $_REQUEST) || $_REQUEST['page'] != $this->get_options_menuname()) {
				add_action('admin_notices', Array($this, 'unloct_admin_auth_message'));
				if (is_multisite()) {
					add_action('network_admin_notices', Array($this, 'unloct_admin_auth_message'));
				}
			}
		}
		else {
			$this->set_other_admin_notices();
		}
		
		
	}

	// Has content in Basic
	protected function set_other_admin_notices() {
	}
	
	public function ga_admin_menu() {

			add_menu_page( __( 'Unloct settings' , 'unloct-login'), __( 'Unloct' , 'unloct-login'),
				 'manage_options', $this->get_options_menuname(),
				 array($this, 'unloct_options_do_page'));
	}
	
	public function unloct_options_do_page() {
		if (!current_user_can(is_multisite() ? 'manage_network_options' : 'manage_options')) {
			wp_die();
		}
		
		//wp_enqueue_script( 'gal_admin_js', $this->my_plugin_url().'js/gal-admin.js', array('jquery') );
		wp_enqueue_style( 'unloct_admin_css', $this->my_plugin_url().'css/unloct-admin.css' );

		$submit_page = is_multisite() ? 'edit.php?action='.$this->get_options_menuname() : 'options.php';
		
		if (is_multisite()) {
			$this->unloct_options_do_network_errors();
		}
		?>
		  
		<div>
		<div id="fb-root"></div>
		<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v9.0&appId=417156578749851&autoLogAppEvents=1" nonce="y1un0nYL"></script>
		<script>window.twttr = (function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0],
			t = window.twttr || {};
		  if (d.getElementById(id)) return t;
		  js = d.createElement(s);
		  js.id = id;
		  js.src = "https://platform.twitter.com/widgets.js";
		  fjs.parentNode.insertBefore(js, fjs);

		  t._e = [];
		  t.ready = function(f) {
			t._e.push(f);
		  };

		  return t;
		}(document, "script", "twitter-wjs"));</script>	
			<table border="0" width="100%">
				<tr>
					<td width="40%"><h2><?php _e('Unloct - Getting Setup', 'unloct-login'); ?></h2>
					<a href="https://unloct.com/register" target="_blank">
						<img src="<?php echo plugin_dir_url( __FILE__ ) . 'logo.png'; ?>" alt="<?php esc_attr_e( 'Unloct.com', 'Unloct' ); ?>">
					</a>

					<?php $this->unloct_section_text_end();?></td>
				</tr>
			</table>	
			<div id="unloct-tablewrapper">		
				<div id="unloct-tableleft" class="unloct-tablecell">
					<!-- <?php $this->unloct_section_text_end();?>			 -->
					<h2 id="unloct-tabs" class="nav-tab-wrapper">
						<a href="#main" id="main-tab" class="nav-tab nav-tab-active">Welcome to Unloct!</a>
					</h2>
					<p><b>Step 1:</b> To get started, register a free account at <a href="https://unloct.com/register">Unloct.com</a>. Note that, as a content creator, you don't need to subscribe to monetize your content with Unloct. Only your fans and those who wish to access your premium content need to subscribe (and thus paying you in the process of engaging with your website). Make sure you check your email inbox and junk folder for the activation link.</p>
					<hr><p><b>Step 2:</b> Once you have successfully registered your free account at <a href="https://unloct.com/register">Unloct.com</a>, login to your Unloct account at <a href="https://unloct.com/login">https://Unloct.com/login</a>. Once you have successfully logged in to <a href="https://unloct.com/register">Unloct.com</a>, you should see a drop-down menu called "Creators" in the upper right-hand corner of Unloct.com. Click on it, then click the link that says "Setup".</p>
					<hr><p><b>Step 3:</b> Once you click "Setup" from Step 2 above, you should see a box titled 'OAuth Clients'. In the upper right-hand corner of that box is a link that says 'Create New Client'. Click it</p>
					<hr><p><b>Step 4:</b> Once you have clicked 'Create New Client' from Step 3, a new window should pop up that has two fields that you must fill out: 'Name' and 'Redirect URL'. In the 'Name' field text box, enter a name that represents your website well; something your users will recognize.</p>
					<hr><p><b>Step 5:</b> Once you have entered a user-friendly 'Name' in Step 4 above, you will need to enter in your 'Redirect URL'. This should be the URL of your website's login page. It will be something like 'https://yourwebsite.com/wp-login.php'. It is very important you use the correct 'http' or 'https' depending on if your website uses SSL or not (most websites do these days). This page will attempt to auto-generate your Redirect URL now so that you can copy and paste the results over at Unloct.com. If it doesn't work then you will need to key in your Redirect URL manually. Attempted automated response: <b><?php echo wp_login_url()?></b></p>
					<hr><p><b>Step 6:</b> Once you have completed Step 4 & Step 5 in the window on Unloct.com, click the 'Create' button. This will generate a 'Client ID' and 'Secret' that are unique to your website. Never share either of these values with anyone. Copy and paste the 'Client ID' into the 'Client ID' field below on this page. Then copy and paste the 'Secret' into the 'Client Secret' field on this page below. Next you should navigate to the 'Settings' menu within your Admin Dashboard and click on 'General'. Within the 'General' settings page, you should see a checkbox that reads 'Anyone can register'. Make sure this checkbox is NOT checked. If anyone can register to your website, it defeats the purpose of the Unloct platform as a way for you to monetize your content.</p>
					<hr><p><b>Step 7:</b> At this point your website is ready for Unloct Subscribers to login to your website. A button will appear on your login page that reads 'Login with Unloct'. Your fans and supporters (who are subscribed to Unloct) can click that button and log into your website with their Unloct credentials. But there is more to this platform that you should consider, such as adding a 'Support' prompt to your front page to let your fans know they can support your monetarily via Unloct. This plugin has a built-in widget that lets you do just that. Log into this website with your Admin Credentials and go to the Admin Dashboard (hint: you already did that to get to this page). Locate the 'Widgets' link within the 'Appearance' drop-down menu and click on it.</p>
					<hr><p><b>Step 8:</b> Once you have navigated to the 'Widgets' section of this website's Admin Dashboard as described in Step 7 above, locate the widget titled 'Unloct'. Click and drag that widget to one of your front-end sections like a sidebar or footer. Then click the caret (the downward arrow) next to the Unloct title to display the widget's options. From here you can select the image link that your website's visitors will see which prompts them to subscribe with Unloct, and then login to your website to support you. There are different images with different color schemes; choose the one that best suits your brand. You will also notice that the widget options require your 'Client ID'. This is the same 'Client ID' from Step 6 above. Enter your 'Client ID' into this field and click 'Done'. This widget will not function properly if you do not enter the correct Client ID so this step is very important. Don't forget to try different image color schemes to see which ones you like the best.</p>
					<hr><form action="<?php echo $submit_page; ?>" method="post" id="gal_form" enctype="multipart/form-data" >
						<?php 
						settings_fields($this->get_options_pagename());
						$this->unloct_mainsection_text();
						?>
						<p class="submit">
							<input type="submit" value="<?php esc_attr_e( 'Save Changes' , 'unloct-login'); ?>" class="button button-primary" id="submit" name="submit">
						</p>
					</form>
					<hr><p><b>Step 9:</b> Another powerful feature this platform provides is the ability to place premium content behind a paywall that only logged in users can see. Once this plugin is installed, activated, and configured, you have access to some powerful 'Shortcodes' that allow you to give special access to premium content for logged in users only. If you haven't used shortcodes before, they are bits of text surrounded by square brackets; like this: <b>[Unloct][/Unloct]</b>. To use the Unloct shortcodes, simply place some content within a Post or Page on this WordPress website (a podcast audio file, a blog, a video, cosplay pics, how-to guides, etc.). Then, surround that content with the shortcodes <b>[Unloct]</b> and <b>[/Unloct]</b>. So within your post or page, it would look like this: <b>[Unloct]This is my premium content that only logged in users can see[/Unloct]</b> but obviously you would put something meaningful that users would be willing to pay for between the starting and ending shortcodes.</p>
					<hr><p><b>Step 10:</b> Finally, you might want to embed into your posts or pages a message to users that disappears once they log in, something like "Thanks for visiting my website! If you want to support me, subscribe to Unloct.com then login to this website using the link to the right. Everytime you log in here, I get a portion of your monthly fee." The way that you make this message disappear is by using the shortcodes <b>[Visitor][/Visitor]</b> and placing your message between those two shortcodes (this shortcode is also included with this plugin). By the way, the users of your website, logged in or not, will not see the actual shortcodes on the front-end; they will only see the text you type between the shortcodes. Why don't you try it out and see what we mean?</p>
					<hr><p>You are done! Congrats on joining the Unloct Platform, the revolutionary way that creators are monetizing their content on the web. The next step is to promote, promote, promote! Get those tweets, Instagram stories, Facebook group posts, and word-of-mouth channels working for you to drive traffic to your content. This step is crucial to growing the userbase of Unloct and thus your fanbase as well. And be sure to tell your creative friends and colleagues about Unloct so they can help us grow our ever expanding list of creators!</p>
				</div>
				<hr>
				<p><b>Follow us across the web:</b></p>
				<hr><div class="fb-like" data-href="https://facebook.com/unloct" data-width="" data-layout="standard" data-action="like" data-size="large" data-share="true"></div>
				<hr><a class="twitter-follow-button"
				  href="https://twitter.com/unloct" target="_blank">
				Follow @Unloct</a>
				<hr><a href="https://instagram.com/unloct_social" target="_blank">
						<img src="<?php echo plugin_dir_url( __FILE__ ) . 'Instagram.png'; ?>" alt="<?php esc_attr_e( 'Unloct.com', 'Unloct' ); ?>">
				</a><p><b><i><a href="https://instagram.com/unloct_social" target="_blank">Unloct on Instagram</a></i></b></p>
				<?php $this->unloct_options_do_sidebar(); ?>
			</div>
		</div>  <?php
	}
	
	// Extended in premium
	protected function draw_more_tabs() {
	}
	
	// Extended in premium
	protected function ga_moresection_text() {
	}
	
	// Has content in Basic
	protected function unloct_options_do_sidebar() {
	}
	
	protected function unloct_options_do_network_errors() {
		if (isset($_REQUEST['updated']) && $_REQUEST['updated']) {
			?>
				<div id="setting-error-settings_updated" class="updated settings-error">
				<p>
				<strong><?php _e( 'Settings saved.', 'unloct-login'); ?></strong>
				</p>
				</div>
			<?php
		}

		if (isset($_REQUEST['error_setting']) && is_array($_REQUEST['error_setting'])
			&& isset($_REQUEST['error_code']) && is_array($_REQUEST['error_code'])) {
			$error_code 	= 	sanitize_text_field($_REQUEST['error_code']);
			$error_setting 	= 	sanitize_text_field($_REQUEST['error_setting']);
			if (count($error_code) > 0 && count($error_code) == count($error_setting)) {
				for ($i=0; $i<count($error_code) ; ++$i) {
					?>
				<div id="setting-error-settings_<?php echo $i; ?>" class="error settings-error">
				<p>
				<strong><?php echo htmlentities2($this->get_error_string($error_setting[$i].'|'.$error_code[$i])); ?></strong>
				</p>
				</div>
					<?php
				}
			}
		}
	}
	
	protected function unloct_mainsection_text() {
		
		
		
		echo '<div id="main-section" class="galtab active">';
		$options = $this->get_option_unloct_login(); // Must be in this order to invoke upgrade code

		echo '<label for="input_unloct_clientid" class="textinput big">'.__('Client ID', 'unloct-login').'</label>';
		echo "<input id='input_unloct_clientid' class='textinput' name='".$this->get_options_name()."[unloct_clientid]' size='20' type='text' value='".esc_attr($options['unloct_clientid'])."' />";
		echo '<br class="clear"/><p class="desc big">';
		printf( __('Should be numeric like: 1234567890', 'unloct-login'));
		echo '</p>';
		
		echo '<label for="input_unloct_clientsecret" class="textinput big">'.__('Client Secret', 'unloct-login').'</label>';
		echo "<input id='input_unloct_clientsecret' class='textinput' name='".$this->get_options_name()."[unloct_clientsecret]' size='70' type='text' value='".esc_attr($options['unloct_clientsecret'])."' />";
		echo '<br class="clear" /><p class="desc big">';
		printf( __('Normally something like: tttttXXXXXXrrrrrDDDDDDDDDD', 'unloct-login'));
		echo '</p>';
		
		/*echo '<hr color="green">';
		echo '<label for="input_unloct_shortcode_message">'.__('Text that is displayed between the shortcodes [unloct][/unloct] if the website user is not logged in.', 'unloct-login').'</label>';
		echo "<input id='input_unloct_shortcode_message' class='textinput' name='".$this->get_options_name()."[unloct_shortcode_message]' size='200' type='text' value='".esc_attr($options['unloct_shortcode_message'])."' />";
		echo '<br class="clear" /><p class="desc big">';
		printf( __('The default value is: Only Unloct subcribers can access this content. Subscribe @ Unloct.com and get unlimited access to this creator and many more for one fixed monthly price.', 'unloct-login'));
		echo '</p>';*/
		
		echo '</div>';
	}
	
	

	public function unloct_options_validate($input) {
		$newinput = Array();
		$newinput['unloct_clientid'] = isset($input['unloct_clientid']) ? trim(sanitize_text_field($input['unloct_clientid'])) : '';
		$newinput['unloct_clientsecret'] = isset($input['unloct_clientsecret']) ? trim(sanitize_text_field($input['unloct_clientsecret'])) : '';
		/*$newinput['unloct_shortcode_message'] = isset($input['unloct_shortcode_message']) ? trim(sanitize_text_field($input['unloct_shortcode_message'])) : '';*/
		if(!preg_match('/^.{2}.*$/i', $newinput['unloct_clientid'])) {
			add_settings_error(
			'unloct_clientid',
			'tooshort_texterror',
			self::get_error_string('unloct_clientid|tooshort_texterror'),
			'error'
			);
		}
		if(!preg_match('/^.{12}.*$/i', $newinput['unloct_clientsecret'])) {
			add_settings_error(
			'unloct_clientsecret',
			'tooshort_texterror',
			self::get_error_string('unloct_clientsecret|tooshort_texterror'),
			'error'
			);
		}
		/*if(!preg_match('/^.{6}.*$/i', $newinput['unloct_shortcode_message'])) {
			add_settings_error(
			'unloct_shortcode_message',
			'tooshort_texterror',
			self::get_error_string('unloct_shortcode_message|tooshort_texterror'),
			'error'
			);
		}
		if(!preg_match('/^.{1}.*$/i', $newinput['unloct_shortcode_message'])) {
			$newinput['unloct_shortcode_message'] = 'Only Unloct subcribers can access this content. Subscribe @ Unloct.com and get unlimited access to this creator and many more for one fixed monthly price.';
		}*/
		$newinput['ga_version'] = $this->PLUGIN_VERSION;
		return $newinput;
	}
	
	protected function get_error_string($fielderror) {
		$local_error_strings = Array(
				'unloct_clientid|tooshort_texterror' => __('The Client ID should be longer than that', 'unloct-login') ,
				'unloct_clientsecret|tooshort_texterror' => __('The Client Secret should be longer than that', 'unloct-login') ,
				/*'unloct_shortcode_message|tooshort_texterror' => __('The Shortcode message should be longer than that', 'unloct-login')*/
		);
		if (isset($local_error_strings[$fielderror])) {
			return $local_error_strings[$fielderror];
		}
		return __( 'Unspecified error' , 'unloct-login');
	}
	
	protected function get_options_name() {
		return 'galogin';
	}

	protected function get_default_options() {
		return Array('ga_version' => $this->PLUGIN_VERSION, 
						'unloct_clientid' => '', 
						'unloct_clientsecret' => '',
						/*'unloct_shortcode_message' => '',*/
						'unloct_clientlogo'=>'');
	}
	
	protected $unloct_options = null;
	public function get_option_unloct_login() {
		if ($this->unloct_options != null) {
			return $this->unloct_options;
		}
			
		$option = get_site_option($this->get_options_name(), Array());
		
		$default_options = $this->get_default_options();
		foreach ($default_options as $k => $v) {
			if (!isset($option[$k])) {
				$option[$k] = $v;
			}
		}
		
		$this->unloct_options = apply_filters( 'gal_options', $option );
		return $this->unloct_options;
	}
	
	protected function save_option_galogin($option) {
		update_site_option($this->get_options_name(), $option);
		$this->unloct_options = $option;
	}
	
	

	
	public function unloct_get_clientid() {
		$options = $this->get_option_unloct_login();
		return $options['unloct_clientid'];
	}

	public function unloct_get_clientsecret() {
		$options = $this->get_option_unloct_login();
		return $options['unloct_clientsecret'];
	}

	// PLUGINS PAGE
	
	public function ga_plugin_action_links( $links, $file ) {
		if ($file == $this->my_plugin_basename()) {
			$settings_link = '<a href="'.$this->get_settings_url().'">'.__( 'Settings' , 'unloct-login').'</a>';
			array_unshift( $links, $settings_link );
		}
	
		return $links;
	}
	
	// HOOKS AND FILTERS
	// *****************
	
	protected function add_actions() {
		
		add_action('login_enqueue_scripts', array($this, 'unloct_login_styles'));
		add_action('login_form', array($this, 'unloct_login_form'));
		add_filter('authenticate', array($this, 'unloct_authenticate'), 5, 3);
		
		add_filter('login_redirect', array($this, 'unloct_login_redirect'), 5, 3 );
		add_action('init', array($this, 'unloct_init'), 1);
		
		add_action('admin_init', array($this, 'unloct_admin_init'), 5, 0);
				
		add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', array($this, 'ga_admin_menu'));
		
		add_filter('unloct_get_clientid', Array($this, 'unloct_get_clientid') );
		
		add_filter( 'plugin_action_links', array($this, 'ga_plugin_action_links'), 10, 2 );
	
	}

	// Abstract

	protected function my_plugin_basename() {
		throw new Exception("core_unloct_login is an abstract class");
	}

	protected function my_plugin_url() {
		throw new Exception("core_unloct_login is an abstract class");
	}
   
	protected function get_user_details($access_token) {
	    $args	=	array(
						'method' => 'GET',
						'headers' => array('Authorization' => 'Bearer ' . $access_token),
						'httpversion' => 1.1,
						'timeout' => 30
					);
	    $result = wp_remote_get('https://unloct.com/api/user', $args);
	    
	    $response = json_decode(wp_remote_retrieve_body($result));
	    return $response;
	}

	protected function get_access_token($code) {
     	$args = array(
          'method' => 'POST',
          'header' => array('Content-Type' => 'application/x-www-form-urlencoded'),
          'body' => array(
          				'client_id' => $this->unloct_get_clientid(),
          				'client_secret' => $this->unloct_get_clientsecret(),
          				'scope' => '*',
          				'grant_type' => 'authorization_code',
          				'redirect_uri' => $this->get_login_url(),
          				'code' => $code
          		),
          'httpversion' => 1.1,
          'timeout' => 30
				);
      $result = wp_remote_get('https://unloct.com/oauth/token', $args);
      
      $response = json_decode(wp_remote_retrieve_body($result));
      if($response && $response->access_token) {
          return $response->access_token;
      }
      else {
          return "ERROR";
      }
	}	
	
}

?>