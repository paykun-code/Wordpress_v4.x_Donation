<?php
/**
 * Plugin Name: Paykun Payment Donation
 * Plugin URI: https://github.com/Paykun-Payments/
 * Description: This plugin allow you to accept donation payments using Paykun. This plugin will add a simple form that user will fill, when he clicks on submit he will redirected to Paykun website to complete his transaction and on completion his payment, paykun will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see all transaction details with payment status by going to "Paykun Payment Details" from menu in admin.
 * Version: 1.0
 * Author: Paykun
 * Author URI: http://paykun.com/
 * Text Domain: Paykun Payments
 */
//ini_set('display_errors','On');
register_activation_hook(__FILE__, 'paykun_activation');
register_deactivation_hook(__FILE__, 'paykun_deactivation');
// do not conflict with WooCommerce Paykun Plugin Callback

if(isset($_GET["wc-api"]) && $_GET["wc-api"] == "paykun-donation" && !isset($_GET['pk_msg'])){

    add_action('init', 'paykun_donation_response');

}
add_shortcode( 'paykuncheckout', 'paykun_donation_handler' );

/*Response message hook start*/
if(isset($_GET['pk_msg']) && $_GET['pk_msg'] != ""){

    add_action('the_content', 'paykunDonateShowMessage');

    function paykunDonateShowMessage($content){

        return '<div class="box">'.htmlentities(urldecode($_GET['pk_msg'])).'</div>'.$content;

    }
}
/*Response message hook end*/

$GLOBALS['missingFieldError'] = '<p><strong>PAYKUN:</strong> Some of the required field missing in admin. Please make sure Merchant Id, Access Token and 
            Encryption Key is filled properly.</p>';


function paykun_activation() {

    global $wpdb, $wp_rewrite;
    $settings = paykun_settings_list();
    foreach ($settings as $setting) {
        add_option($setting['name'], $setting['value']);
    }
    add_option( 'paykun_donation_details_url', '', '', 'yes' );
    $post_date = date( "Y-m-d H:i:s" );
    $post_date_gmt = gmdate( "Y-m-d H:i:s" );
    $ebs_pages = array(
        'paykun-page' => array(
            'name' => 'Paykun Transaction Details page',
            'title' => 'Paykun Transaction Details page',
            'tag' => '[paykun_donation_details]',
            'option' => 'paykun_donation_details_url'
        ),
    );

    $newpages = false;

    $paykun_page_id = $wpdb->get_var("SELECT id FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%" . $paykun_pages['paykun-page']['tag'] . "%'	AND `post_type` != 'revision'");
    if(empty($paykun_page_id)){
        $paykun_page_id = wp_insert_post( array(
            'post_title'	=>	$paykun_pages['paykun-page']['title'],
            'post_type'		=>	'page',
            'post_name'		=>	$paykun_pages['paykun-page']['name'],
            'comment_status'=> 'closed',
            'ping_status'	=>	'closed',
            'post_content' =>	$paykun_pages['paykun-page']['tag'],
            'post_status'	=>	'publish',
            'post_author'	=>	1,
            'menu_order'	=>	0
        ));
        $newpages = true;
    }
    update_option( $paykun_pages['paykun-page']['option'], _get_page_link($paykun_page_id) );
    unset($paykun_pages['paykun-page']);

    $table_name = $wpdb->prefix . "paykun_donation";
    $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`name` varchar(255),
				`email` varchar(255),
				`phone` varchar(255),
				`address` varchar(255),
				`city` varchar(255),
				`country` varchar(255),
				`state` varchar(255),
				`zip` varchar(255),
				`amount` varchar(255),
				`payment_status` varchar(255),
				`payment_id` varchar(255),
				`date` datetime
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
    if($newpages){
        wp_cache_delete( 'all_page_ids', 'pages' );
        $wp_rewrite->flush_rules();
    }
}

function paykun_deactivation() {
    $settings = paykun_settings_list();
    foreach ($settings as $setting) {
        delete_option($setting['name']);
    }
}

/**
 * @return array
 * Admin configuration settings
 */
function paykun_settings_list(){
    $settings = array(
        array(
            'display' => 'Merchant ID',
            'name'    => 'paykun_merchant_id',
            'value'   => '',
            'type'    => 'textbox',
            'hint'    => 'Merchant Id Provided by Paykun'
        ),
        array(
            'display' => 'Access Token',
            'name'    => 'paykun_access_token',
            'value'   => '',
            'type'    => 'textbox',
            'hint'    => 'Merchant Secret Key Provided by Paykun'
        ),
        array(
            'display' => 'Encryption Key',
            'name'    => 'paykun_enc_key',
            'value'   => '',
            'type'    => 'textbox',
            'hint'    => 'Merchant Encryption Key Provided by Paykun'
        ),
        array(
            'display' => 'Default Amount',
            'name'    => 'paykun_amount',
            'value'   => '100',
            'type'    => 'textbox',
            'hint'    => 'the default donation amount, WITHOUT currency signs -- ie. 100, Minimum amount is 10'
        ),
        array(
            'display' => 'Amount editable by user?',
            'name'    => 'paykun_is_amount_editable',
            'values'   => array('1' => '---Yes---', '0' => '---No---'),
            'type'    => 'select',
            'checked'   => 'checked',
            'hint'    => 'Indicate whether the user can edit default amount or not. if selected "NO" user will not be able to edit this amount.'
        ),
        array(
            'display' => 'Default Button/Link Text',
            'name'    => 'paykun_content',
            'value'   => 'Paykun',
            'type'    => 'textbox',
            'hint'    => 'the default text to be used for buttons or links if none is provided'
        ),
        array(
            'display' => 'Enable log?',
            'name'    => 'paykun_log',
            'values'   => array('yes' => '---Yes---', 'no' => '---No---'),
            'type'    => 'select',
            'default'   => 'yes',
            'hint'    => 'Enable this settings for trouble shootings, log file path plugin > paykun-donate > logs'
        ),
        array(
            'type' => 'separator',
            'display'   => '',
            'title'   => 'Extra fields settings',
        ),
        array(
            'display' => 'Is Address field mandatory field?',
            'name'    => 'paykun_is_address_field_mandatory',
            'values'   => array('no' => '---No---', 'yes' => '---Yes---'),
            'type'    => 'select',
            'hint'    => 'Yes => User must have to enter this field value, No => The field is optional.'
        ),
        array(
            'display' => 'Is City field mandatory field?',
            'name'    => 'paykun_is_city_mandatory',
            'values'   => array('no' => '---No---', 'yes' => '---Yes---'),
            'type'    => 'select',
            'hint'    => 'Yes => User must have to enter this field value, No => The field is optional.'
        ),
        array(
            'display' => 'Is State field mandatory field?',
            'name'    => 'paykun_is_state_mandatory',
            'values'   => array('no' => '---No---', 'yes' => '---Yes---'),
            'type'    => 'select',
            'hint'    => 'Yes => User must have to enter this field value, No => The field is optional.'
        ),
        array(
            'display' => 'Is Postal code field mandatory field?',
            'name'    => 'paykun_is_postalcode_mandatory',
            'values'   => array('no' => '---No---', 'yes' => '---Yes---'),
            'type'    => 'select',
            'hint'    => 'Yes => User must have to enter this field value, No => The field is optional.'
        ),
        array(
            'display' => 'Is Country field mandatory field?',
            'name'    => 'paykun_is_country_mandatory',
            'values'   => array('no' => '---No---', 'yes' => '---Yes---'),
            'type'    => 'select',
            'hint'    => 'Yes => User must have to enter this field value, No => The field is optional.'
        ),

    );
    return $settings;
}

if (is_admin()) {

    add_action( 'admin_menu', 'paykun_admin_menu' );
    add_action( 'admin_init', 'paykun_register_settings' );

}

/**
 * Add admin menu for the paykun settings
 */
function paykun_admin_menu() {

    add_menu_page('Paykun Donation', 'Paykun Donation', 'manage_options', 'paykun_options_page', 'paykun_options_page', plugin_dir_url(__FILE__).'assets/logo.ico');
    add_submenu_page('paykun_options_page', 'Paykun Donation Settings', 'Settings', 'manage_options', 'paykun_options_page');
    add_submenu_page('paykun_options_page', 'Paykun Donation Payment Details', 'Payment Details', 'manage_options', 'wp_paykun_donation', 'wp_paykun_donation_listings_page');

    require_once(dirname(__FILE__) . '/paykun-donation-listings.php');
}

/**
 * Paykun admin configuration
 */
function paykun_options_page() {

    echo	'<div class="wrap">
				<h1>Paykun Configuarations</h1>
				<form method="post" action="options.php">';
    wp_nonce_field('update-options');
    echo '<table class="form-table">';
    $settings = paykun_settings_list();
    foreach($settings as $setting){
        echo '<tr valign="top">
                <th scope="row">'.$setting['display'].'</th>
                <td>';
        if ($setting['type']=='radio') {
            echo $setting['yes'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="1" '.(get_option($setting['name']) == 1 ? 'checked="checked"' : "").' />';
            echo $setting['no'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="0" '.(get_option($setting['name']) == 0 ? 'checked="checked"' : "").' />';

        } elseif ($setting['type']=='select') {
            echo '<select name="'.$setting['name'].'">';
            foreach ($setting['values'] as $value => $name) {
                echo '<option value="'.$value.'" ' .(get_option($setting['name'])==$value? '  selected="selected"' : ''). '>'.$name.'</option>';
            }
            echo '</select>';
        } elseif($setting['type'] == 'separator') {
            echo "<h1>=======================</h1>";
            echo "<h1>".$setting['title']."</h1>";
            echo "<h1>=======================</h1>";
        } else {
            echo '<input type="'.$setting['type'].'" name="'.$setting['name'].'" value="'.get_option($setting['name']).'" />';
        }
        echo '<p class="description" id="tagline-description">'.$setting['hint'].'</p>';
        echo '</td></tr>';
    }
    echo '<tr>
									<td colspan="2" align="center">
										<input type="submit" class="button-primary" value="Save Changes" />
										<input type="hidden" name="action" value="update" />';
    echo '<input type="hidden" name="page_options" value="';
    foreach ($settings as $setting) {
        echo $setting['name'].',';
    }
    echo '" />
									</td>
								</tr>
								<tr>
								</tr>
							</table>
						</form>';
    $last_updated = "";
    $path = plugin_dir_path( __FILE__ ) . "/paykun_version.txt";
    if(file_exists($path)){
        $handle = fopen($path, "r");
        if($handle !== false){
            $date = fread($handle, 11); // i.e. DD-MM-YYYY or 25-04-2018
            $last_updated = '<p>Last Updated: '. date("d F Y", strtotime($date)) .'</p>';
        }
    }
    include( ABSPATH . WPINC . '/version.php' );
    $footer_text = '<hr/><div class="text-center">'.$last_updated.'<p>Wordpress Version: '. $wp_version .'</p></div><hr/>';
    echo $footer_text.'</div>';

}

function paykun_register_settings() {

    $settings = paykun_settings_list();
    foreach ($settings as $setting) {
        register_setting($setting['name'], $setting['value']);
    }

}

function paykun_donation_handler(){

    if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "paykun_donation_request"){
        /*Form is submitted by donor, redirect request to the server*/
        return paykun_donation_submit();
    }
    else {
        /*Donor is on the donation info form, display donor info get form*/
        return paykun_donation_form();
    }
}

/**
 * @return bool
 */
function checkIfRequiredFieldMissing() {

    $merchantId     =  trim(get_option('paykun_merchant_id'));
    $accessToken    =  trim(get_option('paykun_access_token'));
    $encKey         =  trim(get_option('paykun_enc_key'));
    return ($merchantId == "" || $accessToken == "" || $encKey == "");

}

/*Get donor info*/
function paykun_donation_form(){
    $current_url = "//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    $html = "";
    try {
        if(isset($_GET['pk_message']) && $_GET['pk_message'] != "") {

            $html = '<div class="box"><h1>Payment Failed</h1>'.htmlentities(urldecode($_GET['pk_message'])).'</div>';
            if(isset($_GET['isPaid']) && $_GET['isPaid'] == 0) {
                $html.= "<a href='".home_url( $wp->request )."' style='padding: 10px;background: #698fe9;color: #fff;display: inline-block;margin-top: 10px;'>Let's Try again</a>";
            }

        } else {

            if(checkIfRequiredFieldMissing() == true) {

                return  $GLOBALS['missingFieldError'];

            }
            $isAmountEditable = trim(get_option('paykun_is_amount_editable'));
            $isAmountReadOnly = ($isAmountEditable == 0) ? 'readonly' : '';
            $optionalOrMustFields = array(
                'address'   =>  trim(get_option('paykun_is_address_field_mandatory')) == 'yes' ? 'required' : '',
                'addressReq'   =>  trim(get_option('paykun_is_address_field_mandatory')) == 'yes' ? '<em>*</em>' : '',

                'city'   =>  trim(get_option('paykun_is_city_mandatory')) == 'yes' ? 'required' : '',
                'cityReq'   =>  trim(get_option('paykun_is_city_mandatory')) == 'yes' ? '<em>*</em>' : '',

                'state'   =>  trim(get_option('paykun_is_state_mandatory')) == 'yes' ? 'required' : '',
                'stateReq'   =>  trim(get_option('paykun_is_state_mandatory')) == 'yes' ? '<em>*</em>' : '',

                'zip'   =>  trim(get_option('paykun_is_postalcode_mandatory')) == 'yes' ? 'required' : '',
                'zipReq'   =>  trim(get_option('paykun_is_postalcode_mandatory')) == 'yes' ? '<em>*</em>' : '',

                'country'   =>  trim(get_option('paykun_is_country_mandatory')) == 'yes' ? 'required' : '',
                'countryReq'   =>  trim(get_option('paykun_is_country_mandatory')) == 'yes' ? '<em>*</em>' : '',
            );
            $html = '<form name="frmTransaction" method="post">
					<p>
						<label class="pk_lbl" for="donor_name">Name:<em>*</em></label>
						<input type="text" name="donor_name" maxlength="255" value="" required/>
					</p>
					<p>
						<label class="pk_lbl" for="donor_email">Email:<em>*</em></label>
						<input type="text" name="donor_email" maxlength="255" value="" required/>
					</p>
					<p>
						<label class="pk_lbl" for="donor_phone">Phone:<em>*</em></label>
						<input type="text" name="donor_phone" maxlength="15" value="" required/>
					</p>
					<p>
						<label class="pk_lbl" for="donor_amount">Amount:<em>*</em></label>
						<input type="text" name="donor_amount" maxlength="10" value="'.trim(get_option('paykun_amount')).'" required min="10" '.$isAmountReadOnly.'/>
					</p>
					<p>
						<label class="pk_lbl" for="donor_address">Address:'.$optionalOrMustFields['addressReq'].'</label>
						<input type="text" name="donor_address" maxlength="255" value="" '.$optionalOrMustFields['address'].'/>
					</p>
					<p>
						<label class="pk_lbl" for="donor_city">City:'.$optionalOrMustFields['cityReq'].'</label>
						<input type="text" name="donor_city" maxlength="255" value="" '.$optionalOrMustFields['city'].'/>
					</p>
					<p>
						<label class="pk_lbl" for="donor_state">State:'.$optionalOrMustFields['stateReq'].'</label>
						<input type="text" name="donor_state" maxlength="255" value="" '.$optionalOrMustFields['state'].'/>
					</p>
					<p>
						<label class="pk_lbl" for="donor_postal_code">Postal Code:'.$optionalOrMustFields['zipReq'].'</label>
						<input type="text" name="donor_postal_code" maxlength="10" value="" '.$optionalOrMustFields['zip'].'/>
					</p>
					<p>
						<label class="pk_lbl" for="donor_country">Country:'.$optionalOrMustFields['countryReq'].'</label>
						<input type="text" name="donor_country" maxlength="255" value="" '.$optionalOrMustFields['country'].'/>
					</p>
					<p>
						<input type="hidden" name="action" value="paykun_donation_request">
						<input type="submit" value="' . trim(get_option('paykun_content')) .'"/>
					</p>
					<style type="text/css">
					.pk_lbl{
					    width: 34%;
                        display: inline-block;
                        text-align: right;
                        padding-right: 7px;
					}
                    </style>
				</form>';
        }
        return $html;
    } catch (Exception $e) {

        echo $e->getMessage();
        addLog($e->getMessage());
        return null;

    }

}

/*Donor submit the donation form*/
function paykun_donation_submit(){
    $valid = true; // default input validation flag
    $html = '';
    $msg = '';

    require_once "Paykun/Payment.php";
    require_once "Paykun/Errors/ValidationException.php";

    if(checkIfRequiredFieldMissing() == true) {

        return  $GLOBALS['missingFieldError'];

    }

    if( trim($_POST['donor_name']) != ''){
        $donor_name = $_POST['donor_name'];
    } else {
        $valid = false;
        $msg.= 'Name is required </br>';
    }

    if( trim($_POST['donor_phone']) != ''){
        $donor_phone = $_POST['donor_phone'];
    } else {
        $valid = false;
        $msg.= 'Phone is required </br>';
    }

    if( trim($_POST['donor_email']) != ''){
        $donor_email = $_POST['donor_email'];
        if( preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/" , $donor_email)){}
        else{
            $valid = false;
            $msg.= 'Invalid email format </br>';
        }
    } else {
        $valid = false;
        $msg.= 'E-mail is required </br>';
    }

    if( trim($_POST['donor_amount']) != ''){
        $donor_amount = $_POST['donor_amount'];
        if( (is_numeric($donor_amount)) && ( (strlen($donor_amount) > '1') || (strlen($donor_amount) == '1')) ){}
        else{
            $valid = false;
            $msg.= 'Amount cannot be less then 10</br>';
        }
    } else {
        $valid = false;
        $msg.= 'Amount is required </br>';
    }

    if($valid){

        global $wpdb;
        $table_name = $wpdb->prefix . "paykun_donation";
        $data = array(
            'name' => sanitize_text_field($_POST['donor_name']),
            'email' => sanitize_email($_POST['donor_email']),
            'phone' => sanitize_text_field($_POST['donor_phone']),
            'address' => sanitize_text_field($_POST['donor_address']),
            'city' => sanitize_text_field($_POST['donor_city']),
            'country' => sanitize_text_field($_POST['donor_country']),
            'state' => sanitize_text_field($_POST['donor_state']),
            'zip' => sanitize_text_field($_POST['donor_postal_code']),
            'amount' => sanitize_text_field($_POST['donor_amount']),
            'payment_status' => 'Pending Payment',
            'date' => date('Y-m-d H:i:s'),
        );
        $result = $wpdb->insert($table_name, $data);
        if(!$result){
            throw new Exception($wpdb->last_error);
        }

        $order_id = $wpdb->insert_id;
        $html = "<center><h1>Please do not refresh this page...</h1></center>";
        $preparedData = prepareData($data, $order_id);

        $frmData = initPayment($preparedData);
        if($frmData !== null) {
            $html .= $frmData;
        } else {
            $html = "";
        }
        return $html;
    }
    else {

        return $msg;

    }
}

/**
 * @param $message
 */
function addLog($message) {

    //You can find this log on the path (wp-content\uploads\wc-logs)
    $log = trim(get_option('paykun_log'));
    if($log == "yes"){
        if(is_array($message)) {
            $message = explode(',', $message);
        }

        $time = date('h:ia');
        $todayFileLog =  date('m-d-Y');
        $message = date('Y-m-d G:i:s')." => " . $message."\n";
        $filePath = plugin_dir_path( __FILE__).'/logs/'.$todayFileLog;
        error_log(print_r($message, TRUE), 3, $filePath);

    }
}

/**
 * @param $orderDetail
 * @return null|string
 */
function initPayment ($orderDetail) {

    try {
        addLog(
            "merchantId => ".$orderDetail['merchantId'].
            ", accessToken=> ".$orderDetail['accessToken'].
            ", encKey => ".$orderDetail['encKey'].
            ", orderId => ".$orderDetail['orderId'].
            ", purpose=>".$orderDetail['purpose'].
            ", amount=> ".$orderDetail['amount']
        );

        $obj = new Payment($orderDetail['merchantId'], $orderDetail['accessToken'], $orderDetail['encKey'], true, true);

        // Initializing Order
        $obj->initOrder($orderDetail['orderId'], $orderDetail['purpose'], $orderDetail['amount'],
            $orderDetail['successUrl'], $orderDetail['failureUrl']);

        // Add Customer
        $obj->addCustomer($orderDetail['customerName'], $orderDetail['customerEmail'], $orderDetail['customerMoNo']);

        // Add Shipping address
        $obj->addShippingAddress($orderDetail['s_country'], $orderDetail['s_state'], $orderDetail['s_city'], $orderDetail['s_pinCode'],
            $orderDetail['s_addressString']);

        // Add Billing Address
        $obj->addBillingAddress($orderDetail['b_country'], $orderDetail['b_state'], $orderDetail['b_city'], $orderDetail['b_pinCode'],
            $orderDetail['b_addressString']);

        $obj->setCustomFields(['udf_1' => $orderDetail['orginalOrderId']]);
        //Render template and submit the form
        $data = $obj->submit();

        addLog("AllParams : " . $data['encrypted_request']); //Set here encryption request
        addLog("Access Token : " . $data['access_token']); //Set here encryption request


        $form = $obj->prepareCustomFormTemplate($data, $orderDetail['cancelOrderUrl'], $orderDetail['loaderUrl']);

        return $form;

    } catch (ValidationException $e) {

        addLog($e->getMessage());
        echo $e->getMessage();
        return null;

    }

}


/**
 * @param $order
 * @param $orderId
 */
function prepareData($order, $order_id, $redirect_url = '') {
    global $wp;
    $url = home_url( $wp->request )."?wc-api=paykun-donation";
    if(count($_GET) > 0) {
        //query string exist please add your new query string with &wc-api=paykun-donation
        $url = home_url( $wp->request )."&wc-api=paykun-donation";
    }

    return array(
        'merchantId'    => trim(get_option('paykun_merchant_id')),
        'accessToken'   => trim(get_option('paykun_access_token')),
        'encKey'    =>  trim(get_option('paykun_enc_key')),

        'orginalOrderId' => $order_id,
        'orderId'   => getOrderIdForPaykun($order_id),
        'purpose'   => 'Donation',
        "amount"    => $order['amount'],
        'successUrl' => $url,
        'failureUrl' => $url,

        /*customer data*/
        "customerName"  => $order['name'],
        "customerEmail" =>  $order['email'],
        "customerMoNo"  => $order['phone'],
        /*customer data over*/

        /*Shipping detail*/
        "s_country"     =>  $order['country'],
        "s_state"       =>  $order['state'],
        "s_city"        =>  $order['city'],
        "s_pinCode"     =>  $order['zip'],
        "s_addressString" => $order['address'],
        /*Shipping detail over*/

        /*Billing detail*/
        "b_country"     =>  $order['country'],
        "b_state"       =>  $order['state'],
        "b_city"        =>  $order['city'],
        "b_pinCode"     =>  $order['zip'],
        "b_addressString" => $order['address'],
        /*Billing detail over*/
        "cancelOrderUrl"    => '',
        "loaderUrl" => "../images/loading.gif",
    );

}

/**
 * @param $orderId
 * @return string
 */
function getOrderIdForPaykun($orderId) {

    $orderNumber = str_pad((string)$orderId, 10, '0', STR_PAD_LEFT);
    return $orderNumber;

}

function paykun_donation_meta_box() {
    $screens = array( 'paykuncheckout' );

    foreach ( $screens as $screen ) {
        add_meta_box(  'myplugin_sectionid', __( 'Paykun', 'myplugin_textdomain' ),'paykun_donation_meta_box_callback', $screen, 'normal','high' );
    }
}
add_action( 'add_meta_boxes', 'paykun_donation_meta_box' );

function paykun_donation_meta_box_callback($post) {
    echo "admin";
}

/**
 * Get response using payment-id
 */
function paykun_donation_response(){

    if(! empty($_GET) && isset($_GET['payment-id'])){

        $paymentId = $_GET['payment-id'];
        $response = getcurlInfo($paymentId);
        $order_id = $response['data']['transaction']['custom_field_1'];
        global $wpdb;
        $isPaid = 0;
        if(isset($response['status']) && $response['status'] == "1" || $response['status'] == 1 ) {

            $payment_status = $response['data']['transaction']['status'];
            $qryStr = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."paykun_donation WHERE id = %d", $order_id);
            $order = $wpdb->get_row($qryStr, object);

            if($payment_status === "Success") { //Transaction is success
            //if(1) {
                $resAmout = $response['data']['transaction']['order']['gross_amount'];

                if(($order->amount	== $resAmout)) {

                    $msg = "Thank you for your order. Your transaction has been successful.";
                    $wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix .
                        "paykun_donation SET payment_status = 'Complete Payment', payment_id = %s WHERE  id = %d", $paymentId ,sanitize_text_field($order_id)));
                    $isPaid = 1;
                } else {

                    $msg = "It seems some issue in server to server communication. Kindly connect with administrator.";
                    $wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paykun_donation SET payment_status = 'Fraud Payment'
                     , payment_id = %s WHERE id = %d", $paymentId,  sanitize_text_field($order_id)));

                }
            } else {

                /*Transaction is failed*/
                $msg = "Thank You. However, the transaction has been Failed For Reason: Transaction is cancelled by the user.";
                $wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paykun_donation SET payment_status = 'Cancelled Payment' 
                , payment_id = %s WHERE id = %d", $paymentId, sanitize_text_field($order_id)));

            }
        } else {

            $msg = "It seems some issue in server to server communication. Kindly connect with administrator.";
            $wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paykun_donation SET payment_status = 'Fraud Payment' 
            , payment_id = %s WHERE id = %d", $paymentId, sanitize_text_field($order_id)));

        }

//        $redirect_url = get_site_url() . '/' . get_permalink(get_the_ID());
//        $redirect_url = add_query_arg( array('pk_msg'=> urlencode($msg)));
//          wp_redirect( $redirect_url,301 );
        wp_redirect( home_url( $wp->request )."?pk_message=".urlencode($msg)."&isPaid=$isPaid" );
        exit;
    }
}

function getcurlInfo($iTransactionId) {

    try {

        $cUrl        = 'https://api.paykun.com/v1/merchant/transaction/' . $iTransactionId . '/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $cUrl);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $merchantId = trim(get_option('paykun_merchant_id'));
        $accessToken = trim(get_option('paykun_access_token'));

        curl_setopt($ch, CURLOPT_HTTPHEADER, array("MerchantId:$merchantId", "AccessToken:$accessToken"));

        $response       = curl_exec($ch);
        $error_number   = curl_errno($ch);
        $error_message  = curl_error($ch);

        $res = json_decode($response, true);
        curl_close($ch);

        return ($error_message) ? null : $res;

    } catch (ValidationException $e) {

        addLog("Server couldn't respond, ".$e->getMessage());
        return null;

    }

}