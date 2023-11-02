<?php

class AffiliateMaster {

	public function __construct() {
		//echo $_COOKIE['pcode'];	
		if (is_admin()) {
			add_action('woocommerce_product_options_general_product_data', array($this, 'ProductCustomFields'));
			add_action('woocommerce_process_product_meta', array($this, 'ProductCustomFieldsSave'));
			add_action('add_meta_boxes', array($this, 'OrderMetaBoxes'));
			add_action('woocommerce_order_status_completed', array($this, 'OrderComplete'));
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action('woocommerce_process_shop_order_meta', array($this, 'OrderSave'));
			add_action('edit_user_profile', array($this, 'userFields'));
			add_action('profile_personal_options', array($this, 'userFields'));
			add_action('edit_user_profile_update', array($this, 'userFieldsUpdate'));
			add_action('profile_update', array($this, 'userFieldsUpdate'));
		}
		add_action('init', array($this, 'SetAffCookie'));
		add_filter('woocommerce_get_price_html', array($this, 'ProductPriceDisplay'));
		add_shortcode('affiliatemaster',  array($this, 'ShortCodes'));
		wp_register_style('affiliatemaster', AMPL_URL.'affiliatemaster.css');
		wp_enqueue_style('affiliatemaster');
		add_action('wp_footer', array($this, 'AffFooter'));
		add_action('plugins_loaded', array($this, 'LangLoad'));
		add_filter('woocommerce_before_calculate_totals', array($this, 'add_cart_item'), 10, 3);
		add_filter('woocommerce_add_cart_item_data', array($this, 'custom_cart_data'), 10, 2 );
		add_action('woocommerce_thankyou', array($this, 'OrderFinish'), 10, 1);
		add_filter('woocommerce_account_menu_items', array($this, 'MyAccountLinks'));
		add_action('woocommerce_account_partnercommission_endpoint', array($this, 'PartnerCommissions'));		
		add_filter('woocommerce_account_orders_columns', array($this, 'add_account_orders_column'), 10, 1);
		add_action('woocommerce_my_account_my_orders_column_partnercommission', array($this, 'orders_column_partnercommission'));
		add_action('woocommerce_my_account_my_orders_column_distribpoints', array($this, 'orders_column_distribpoints'));
		add_action('wp_head', array($this, 'buffer_start'));
		add_action('wp_footer', array($this, 'buffer_end'));

	}


	function callback($buffer) {
		global $wpdb;
		if (isset($_COOKIE['pcode'])) {
			$userid = $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}usermeta WHERE (meta_key='partnercode') AND (meta_value='$_COOKIE[pcode]')");
			$partnerphone = get_user_meta($userid, 'partnerphone', true);
			$contact = "Телефон: $partnerphone<br />";
		}
		else {
			$contact = "
				Адрес: Yambol, ul. \"Mramorno More 36\" <br />
				Телефон: 0892055112 <br />
				Имейл: kristian@bionicfoxinc.com
			";
		}
		$buffer = str_replace('[%footercontact%]', $contact, $buffer);
		return $buffer;
	}




	function buffer_start() { ob_start(array($this, 'callback')); }

	function buffer_end() { ob_end_flush(); }



	function AffFooter() {
		$current_user = wp_get_current_user();
		$userid = isset($current_user->ID)?$current_user->ID:0;
		$partnercode = get_user_meta($userid, 'partnercode', true);
		
		if ($partnercode) {
			global $wp;
			//$url = add_query_arg($wp->query_vars, home_url());
			$url = home_url($wp->request);
			if (substr($url, -1) != '/') $url .= '/';
			if (!strpos($url, '?')) $url .= '?';
			else $url .= '&';
			$url .= 'partner='.$partnercode;
			if (!strpos($_SERVER['REQUEST_URI'], 'partner=')) die("<meta http-equiv='refresh' content='0;$url' />");
			echo "
				<div style='position: fixed; background: #eee; border-top: 1px solid #000; padding: 5px 20px; color: #333; bottom: 0; left: 0; width: 100%; z-index: 10000;'>
					Партньорска Връзка към тази страница: <a href='$url'>$url</a>
				<div style=' display:none;font-size:13px;' id='copyToClipboard$pastsku2'>$url</div>
					   <button title='$url' class='button success is-small' id='btn' onclick='copyToClickBoardcopyToClipboard$pastsku2()'>Копирай линка</button></div>
			";
			echo"
<script>
function copyToClickBoardcopyToClipboard$pastsku(){
    var content = document.getElementById('copyToClipboard$pastsku').innerHTML;

    navigator.clipboard.writeText(content)
        .then(() => {
        console.log('Text copied to clipboard...')
        alert('Линка:$url е копиран!');
    })
        .catch(err => {
        console.log('Something went wrong', err);
    })
 
}
</script>
					   ";
		}
	}

	function admin_menu() {
		add_options_page('Affiliate Master', 'Affiliate Master', 'manage_options', 'amsettings', array($this, 'Commissions'));
	}




	function Commissions() {
		global $wpdb;
		if (isset($_GET['sub']) && ($_GET['sub'] == 'settings')) return $this->Settings();
		$partnerid = isset($_GET['partnerid'])?$_GET['partnerid']:0;
		$selpartner = "<select name='partnerid' onchange='this.form.submit();'><option value='0'>[изберете]</option>";
		$data = $wpdb->get_results("SELECT u.ID, u.display_name, um.meta_value FROM {$wpdb->prefix}usermeta as um LEFT JOIN {$wpdb->prefix}users as u ON u.ID=um.user_id WHERE meta_key='partnercode'");
		foreach ($data as $k=>$info) {
			$info = (array)$info;
			$selpartner .= sprintf("<option value='$info[meta_value]'%s>$info[display_name] ($info[meta_value])</option>", ($partnerid==$info['meta_value'])?' selected':'');
		}
		$selpartner .= '</select>';
		echo "
			<div class='wrap'>
			<form method='get' action='admin.php'>
				<input type='hidden' name='page' value='amsettings' />
				<input type='hidden' name='sub' value='commissions' />
				<div id='icon-edit-pages' class='icon32'></div>		
					<h2>Affiliate Master - Commissions <a href='admin.php?page=amsettings&sub=settings' class='button add-new-h2'>".__('Settings', 'affmaster')."</a></h2>
					<div style='padding: 10px 0;'>
						Изберете Партньор: $selpartner
					</div>
				</div>
			</form>
		";
		if (!$partnerid) {
			echo '<br /><strong>Моля изберете партньор!</strong>';
			return ;
		}
		if (isset($_POST['DoPay'])) {
			foreach ($_POST['gopay'] as $id) {
				update_post_meta($id, 'commisionpaid', 1);
			}
		}
		$html .= "
			<form method='post'>
			<table class='wp-list-table widefat fixed pages' style='margin-top: 2px;' cellspacing='0' id='tblmembers'>
				<thead>
					<tr style='text-align: center;'>
						<th scope='col' class='manage-column column-cb check-column'><input type='checkbox' /></th>
						<th>Поръчка №</th>
						<th>Дата, Час</th>
						<th>Клиент</th>
						<th style='text-align: center'>Изпл.</th>
						<th style='text-align: right'>Сума</th>
						<th style='text-align: right'>Комисионна</th>
						<th style='text-align: center'>Бонус Точки</th>
					</tr>
				</thead>
		";
		$sumpoints = 0; $sumcomm = 0; $sumtotal = 0; $commremain = 0;
		$orders = wc_get_orders(array('order_status' => 'wc-completed'));
		foreach ($orders as $stub=>$order) {
			$orderid = $order->get_id();
			$affcode = get_post_meta($orderid, 'partnercode', true);
			if ($affcode != $partnerid) continue;
			//if ($order->get_status() != 'completed') continue;
			$names = $order->get_shipping_first_name().' '.$order->get_shipping_last_name();
    		$points = get_post_meta($orderid, 'distribpoints', true);
    		$commission = get_post_meta($orderid, 'distribcommission', true);
    		$sumcomm  += $commission;
    		$sumpoints += $points;
    		$sumtotal += $order->get_total();
    		$paid = get_post_meta($orderid, 'commisionpaid', true);
    		if (!$paid) $commremain += $this->plxRound($commission);
    		$paid = $paid?'ДА':'НЕ';
			$html .= "
				<tr>
					<th scope='row' class='check-column'><input type='checkbox' name='gopay[]' value='$orderid' /></th>
					<td>$orderid</td>
					<td>".$order->get_date_completed()."</td>
					<td>$names</td>
					<td style='text-align: center'>$paid</td>
					<td style='text-align: right'>".$this->plxRound($order->get_total())." лв.</td>
					<td style='text-align: right'>".$this->plxRound($commission)." лв.</td>
					<td style='text-align: center'>$points</td>
				</tr>
			";
		}
		$html .= "
				<tr style='font-weight: bold;'>
					<td colspan='5'>Общо:</td>
					<td style='text-align: right'>".$this->plxRound($sumtotal)." лв.</td>
					<td style='text-align: right'>".$this->plxRound($sumcomm)." лв.</td>
					<td style='text-align: center'>$sumpoints</td>
				</tr>
			</table><br />
			<input class='button-secondary' type='submit' name='DoPay' value='".__('Pay selected', 'affmaster')."' /> &nbsp;&nbsp;&nbsp;&nbsp;<strong>Неизплатена комисионна:</strong> $commremain лв.
			</form>
			
		";		
		echo $html;		
	}



	function Settings() {
		$msg = '';
		if (isset($_POST['dosave'])) {
			update_option('afmpartnermailsubj', $_POST['afmpartnermailsubj']);
			update_option('afmpartnermaimsg', $_POST['afmpartnermaimsg']);
			$msg = "<br /><span style='color:green'>Settings saved!</span><br /><br />";
		}		
		$afmpartnermailsubj = get_option('afmpartnermailsubj');
		$afmpartnermaimsg = get_option('afmpartnermaimsg');
		echo "
			<div class='wrap'>
			<div id='icon-edit-pages' class='icon32'></div>
				<h2>Affiliate Master - Settings <a href='admin.php?page=amsettings' class='button add-new-h2'>".__('Commissions', 'affmaster')."</a></h2>
				$msg
				<h3>".__('Partner Email on Order Complete', 'affmaster').":</h3>
				<form method='post'>
					<input type='hidden' name='dosave' value='1' />
					".__('Subject', 'affmaster').":<br />
					<input type='text' name='afmpartnermailsubj' value='$afmpartnermailsubj' style='width: 600px;' /><br /><br />
					".__('Message', 'affmaster').":<br />
					<textarea name='afmpartnermaimsg' style='width: 600px;' rows=15>$afmpartnermaimsg</textarea><br /><br />
					<input class='button-primary' type='submit' name='gosave' value='".__('Save Settings', 'affmaster')."' /> &nbsp;&nbsp;&nbsp;
					<strong>".__('Tags', 'affmaster').":</strong> [ordernum] [orderamount] [ordercommission] [bonuspoints] [clientname]
				</form>
			</div>
		";
	}


	
	function add_account_orders_column($columns) {
		$current_user = wp_get_current_user();
		$userid = isset($current_user->ID)?$current_user->ID:0;
		$partnercode = get_user_meta($userid, 'partnercode', true);
		if ($partnercode) {
			$new = array('partnercommission'=>__('Discount', 'affmaster'), 'distribpoints'=>__('Bonus Points', 'affmaster'));
			$columns = array_slice($columns, 0, 4, true) + $new + array_slice($columns, 4, NULL, true);	    	
	    }
	    return $columns;
	}

	
	function orders_column_partnercommission($order) {
	    if ($value = $order->get_meta('distribcommission')) echo $value.' лв.';
	}
	function orders_column_distribpoints($order) {
	    if ($value = $order->get_meta('distribpoints')) echo $value;
	}	


	function MyAccountLinks($menu_links) {
		$current_user = wp_get_current_user();
		$userid = isset($current_user->ID)?$current_user->ID:0;
		$partnercode = get_user_meta($userid, 'partnercode', true);
		if ($partnercode) {
			$new = array('partnercommission'=>'Комисионна');
			$menu_links = array_slice($menu_links, 0, 2, true) + $new + array_slice($menu_links, 2, NULL, true);
		}
	 	return $menu_links;
	}
 


	function PartnerCommissions() {
		//https://businessbloomer.com/woocommerce-easily-get-order-info-total-items-etc-from-order-object/
		$current_user = wp_get_current_user();
		$userid = isset($current_user->ID)?$current_user->ID:0;
		$partnercode = get_user_meta($userid, 'partnercode', true);		
		$url = get_site_url();
		if (!strpos($url, '?')) $url .= '?';
		else $url .= '&';
		$url .= 'partner='.$partnercode;		
		$html = "Вашата партньорска връзка: <a href='$url'>$url</a><br /><br />";
		$html .= "
			<h2>История на Поръчките</h2>
			<table>
				<tr style='text-align: center;'>
					<th>Поръчка №</th>
					<th>Дата, Час</th>
					<th>Клиент</th>
					<th>Сума</th>
					<th>Комисионна</th>
					<th>Бонус Точки</th>
				</tr>
		";
		$sumpoints = 0; $sumcomm = 0; $sumtotal = 0;
		$orders = wc_get_orders(array('order_status' => 'wc-completed'));
		foreach ($orders as $stub=>$order) {
			$orderid = $order->get_id();
			$affcode = get_post_meta($orderid, 'partnercode', true);
			if ($affcode != $partnercode) continue;
			if ($order->get_status() != 'completed') continue;
			$names = $order->get_shipping_first_name().' '.$order->get_shipping_last_name();
    		$points = get_post_meta($orderid, 'distribpoints', true);
    		$commission = get_post_meta($orderid, 'distribcommission', true);
    		$sumcomm  += $commission;
    		$sumpoints += $points;
    		$sumtotal += $order->get_total();
			$html .= "
				<tr>
					<td>$orderid</td>
					<td>".$order->get_date_completed()."</td>
					<td>$names</td>
					<td style='text-align: right'>".$this->plxRound($order->get_total())." лв.</td>
					<td style='text-align: right'>".$this->plxRound($commission)." лв.</td>
					<td style='text-align: center'>$points</td>
				</tr>
			";
		}
		$html .= "
				<tr style='font-weight: bold;'>
					<td colspan='3'>Общо:</td>
					<td style='text-align: right'>".$this->plxRound($sumtotal)." лв.</td>
					<td style='text-align: right'>".$this->plxRound($sumcomm)." лв.</td>
					<td style='text-align: center'>$sumpoints</td>
				</tr>
			</table>
		";		
		echo $html;
	}



	function plxRound($num, $accuracy=2) {
		if (abs($num)<0.000000001) $num = 0;
		$ret = round($num, $accuracy);
		if ($accuracy) {
			$pos = strpos($ret, '.');
			if (!$pos) {
				$lenpart = 0;
				$ret .= '.';
			}
			else $lenpart = strlen(substr($ret, $pos))-1;
			if ($accuracy>$lenpart) $ret .= str_repeat('0', $accuracy-$lenpart);
		}
		return $ret;
	}

	function LangLoad() {
		if (file_exists(AMPL_DIR.'/lang/affiliatemaster-'.get_locale().'.mo')) load_textdomain('affmaster', AMPL_DIR.'/lang/affiliatemaster-'.get_locale().'.mo');
		else load_textdomain('affmaster', AMPL_DIR.'/lang/affiliatemaster-en_US.mo');
	}

	function SetAffCookie() {
		add_rewrite_endpoint('partnercommission', EP_PERMALINK | EP_PAGES);
		flush_rewrite_rules();		
		if (isset($_POST['user_username'])) $this->DoLogin();
		if (isset($_GET['partner'])) {
			if ($_GET['partner']) setcookie('pcode', $_GET['partner'], time()+365*24*3600, '/');
			else setcookie('pcode', $_GET['partner'], time()-3600, '/');
		}
	}


	function custom_cart_data($itemdata, $productid) {
		global $current_user;	
		$current_user = wp_get_current_user();
		$userid = isset($current_user->ID)?$current_user->ID:0;
		$partnercode = get_user_meta($userid, 'partnercode', true);
		if ($partnercode) {
			$product = wc_get_product($productid);
			$distrprice = $product->get_meta('distribprice');
			if ($distrprice > 0.001) $itemdata['customprice'] = $distrprice;
		}
		return $itemdata;
	}


	function add_cart_item($cart) {
	   	$woo_ver = WC()->version; 
		foreach ($cart->get_cart() as $key=>$value) {
			if (isset($value['customprice'])) $value['data']->set_price($value['customprice']);
		}
	}



	function OrderComplete($order_id) {
		global $wpdb;
		wp_mail('kristian@onoffdigital.com', 'woo mail test', 'woo mail test');
    	$affcode = get_post_meta($order_id, 'partnercode', true);
    	$affid = $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}usermeta WHERE (meta_key='partnercode') AND (meta_value='$affcode')");
    	if ($affid) {
			$subj = get_option('afmpartnermailsubj');
			$msg = get_option('afmpartnermaimsg');
    		$points = get_post_meta($post->ID, 'distribpoints', true);
    		$commission = get_post_meta($post->ID, 'distribcommission', true);
    		$order = wc_get_order($order_id);
    		$orderamount = $order->get_formatted_order_total();
    		$clientname = $order->get_shipping_first_name().' '.$order->get_shipping_last_name();    		
			$subj = str_replace('[ordernum]', $order_id, $subj);
			$msg = str_replace('[ordernum]', $order_id, $msg);
			$subj = str_replace('[orderamount]', $orderamount, $subj);
			$msg = str_replace('[orderamount]', $orderamount, $msg);
			$subj = str_replace('[clientname]', $clientname, $subj);
			$msg = str_replace('[clientname]', $clientname, $msg);
			$subj = str_replace('[bonuspoints]', $points, $subj);
			$msg = str_replace('[bonuspoints]', $points, $msg);
			$subj = str_replace('[ordercommission]', $commission, $subj);
			$msg = str_replace('[ordercommission]', $commission, $msg);
			$mailto = $wpdb->get_var("SELECT user_email FROM {$wpdb->prefix}users WHERE ID=$affid");
    		wp_mail($mailto, $subj, $msg);
    	}	
	}



	function OrderFinish($order_id) {
		$current_user = wp_get_current_user();
		$userid = isset($current_user->ID)?$current_user->ID:0;
		$partnercode = $userid?get_user_meta($userid, 'partnercode', true):'';
		if (isset($_COOKIE['pcode'])) update_post_meta($order_id, 'partnercode', $_COOKIE['pcode']);
		if (isset($_COOKIE['pcode']) || $partnercode) {
			$order = wc_get_order($order_id);
	    	$points = 0; $commission = 0;
	    	foreach($order->get_items() as $item_id=>$line_item) {
	    		$product = $line_item->get_product();
				$productid = $product->get_id();
	    		$quant = $line_item->get_quantity();
	    		$distribprice = $product->get_meta('distribprice');   		
	    		$prodpoints = $product->get_meta('prodpoints'); 
	    		//$price = $product->get_regular_price();
	    		$price = $product->get_price();
	    		if ($distribprice > 0.001) $commission += $quant*($price-$distribprice);
	    		$points += $quant*$prodpoints;
	    	}
	    	update_post_meta($order_id, 'distribpoints', $points);
	    	update_post_meta($order_id, 'distribcommission', $commission);
		}
	}



    function OrderMetaBoxes() {
        add_meta_box('order_partnermeta', __('Partner Details','affiliatemaster'), array($this, 'OrderMetaDetails'), 'shop_order', 'side', 'core' );
    }


    function OrderSave() {
    	if (isset($_POST['commissionupdate'])) update_post_meta($_POST['commissionupdate'], 'commisionpaid', isset($_POST['commisionpaid'])?1:0);
    }
    

    function OrderMetaDetails() {
    	global $post, $wpdb;
    	$orderid = $post->ID;
    	$order = wc_get_order($orderid);
    	$userid = $order->get_user_id();
		$partnercode = get_user_meta($userid, 'partnercode', true);
		if ($partnercode) {
			$dname = $wpdb->get_var("SELECT display_name FROM {$wpdb->prefix}users WHERE ID=$userid");
			$affcode = get_post_meta($orderid, 'partnercode', true);
    		$points = get_post_meta($orderid, 'distribpoints', true);
    		$commission = get_post_meta($orderid, 'distribcommission', true);			
			echo "
				<strong>Поръчка на дистрибутор:</strong><br />
				$dname ($partnercode)<br />
				<strong>Спестени:</strong> $commission лв.<br />
				<strong>Бонус точки:</strong> $points<br /><br />
			";			
		}
    	$affcode = get_post_meta($orderid, 'partnercode', true);
    	$affid = $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}usermeta WHERE (meta_key='partnercode') AND (meta_value='$affcode')");
    	if ($affid) {
    		$affid = $wpdb->get_var("SELECT display_name FROM {$wpdb->prefix}users WHERE ID=$affid");
    		if ($affid) {
    			$points = get_post_meta($orderid, 'distribpoints', true);
    			$commission = get_post_meta($orderid, 'distribcommission', true);
    			$commisionpaid = get_post_meta($orderid, 'commisionpaid', true)?' checked':'';
    			echo "
    				<strong>Партньор:</strong> $affid ($affcode)<br />
    				<strong>Kомисионна:</strong> $commission лв.<br />
    				<strong>Бонус точки:</strong> $points<br /><br />
    				<input type='hidden' name='commissionupdate' value='$orderid' />
    				<label><input type='checkbox' name='commisionpaid' value='1'$commisionpaid /> Kомисионната изплатена</label>
    			";
    		}
    	}
    	else echo '-';
	}



	function ProductPriceDisplay($price) {
		if (is_user_logged_in()) {
			$current_user = wp_get_current_user();
			$userid = isset($current_user->ID)?$current_user->ID:0;
			$partnercode = get_user_meta($userid, 'partnercode', true);
			if ($partnercode) {
				$product = wc_get_product();
				$distribprice = $product->get_meta('distribprice');
				if (is_product_category()) {
					if ($distribprice) $price .= '<br />Д: '.$distribprice . ' лв.';
				}
				else {
					$dicount = $product->get_regular_price() - $distribprice;
					$discountperc = round(100*($dicount/$product->get_regular_price()), 2);
					$price = __('
					<div style="
			            font-size: 16px;
                        margin: .5em 0;
                        font-weight: bolder;
                        color:#;
                        ">
					Клиентска Цена:
					
					') . $product->get_regular_price() . ' лв.</div>';
					$price .= __('
					<div style="
			            font-size: 16px;
                        margin: .5em 0;
                        font-weight: bolder;
                        color:#49B33D;
                        ">
					Дистрибуторска Цена').': ' . $distribprice . ' лв.</div>';
					$price .= __('
					<div style="
			            font-size: 16px;
                        margin: .5em 0;
                        font-weight: bolder;
                        color:#c1272d;
                        ">
					Отстъпка').": $dicount лв. ($discountperc%) </div>";
					$price .= __('
					<div style="
			            font-size: 16px;
                        margin: .5em 0;
                        font-weight: bolder;
                        color:#e6be1e;
                        ">
					Бонус Точки').': ' . $product->get_meta('prodpoints').'</div>';
					$url = get_permalink($product->get_id());
					if (!strpos($url, '?')) $url .= '?';
					else $url .= '&';
					$url .= 'partner='.$partnercode;
					$pastsku = $product->get_id();
					$price .= __('
					<div style="
			            font-size: 16px;
                        margin: .5em 0;
                        font-weight: bolder;
                        color:#;
                        ">Връзка').":</div> <div style=' display:none;font-size:13px;' id='copyToClipboard$pastsku'>$url</div>
					   <button title='$url' class='button success is-small' id='btn' onclick='copyToClickBoardcopyToClipboard$pastsku()'>Копирай линка</button>";
					   					   echo"
<script>
function copyToClickBoardcopyToClipboard$pastsku(){
    var content = document.getElementById('copyToClipboard$pastsku').innerHTML;

    navigator.clipboard.writeText(content)
        .then(() => {
        console.log('Text copied to clipboard...')
        alert('Линка е копиран: $url');
    })
        .catch(err => {
        console.log('Something went wrong', err);
    })
 
}
</script>
					   ";
				}
			}
		}
		return $price;
	}



	function RegForm() {
		$err = '';
		if (isset($_POST['user_login'])) {
			require_once(ABSPATH . WPINC . '/registration.php');
			if (!$_POST['user_partnercode']) $err = __('Please enter Partner Code!', 'affmaster');
			elseif (!$_POST['user_city']) $err = __('Please enter City!', 'affmaster');
			elseif (!$_POST['user_login']) $err = __('Please enter Username!', 'affmaster');
			elseif (!$_POST['user_email']) $err = __('Please enter Email!', 'affmaster');
			elseif (!$_POST['pass']) $err = __('Please enter Password!', 'affmaster');
			elseif ($_POST['pass'] != $_POST['pass2']) $err = __('Passwords and confirmation do not match!', 'affmaster');
			elseif (username_exists($_POST['user_login'])) $err = __('Username already taken!', 'affmaster');
			elseif (!validate_username($_POST['user_login'])) $err = __('Invalid username!', 'affmaster');
			elseif (!is_email($_POST['user_email'])) $err = __('Invalid email!', 'affmaster');
			elseif (email_exists($_POST['user_email'])) $err = __('Email already registered!', 'affmaster');
			else {
				$userid = wp_insert_user(array(
						'user_login'		=> $_POST['user_login'],
						'user_pass'	 		=> $_POST['pass'],
						'user_email'		=> $_POST['user_email'],
						'first_name'		=> $_POST['user_fname'],
						'last_name'			=> $_POST['user_lname'],
						'user_registered'	=> date('Y-m-d H:i:s'),
						'role'				=> 'customer'
					)
				);
				update_user_meta($userid, 'partnercity', $_POST['user_city']);
				update_user_meta($userid, 'partnercode', $_POST['user_partnercode']);
				update_user_meta($userid, 'partnerphone', $_POST['user_phone']);
				echo __('Registration complete!', 'affmaster');
				return ;
			}
		}
		if ($err) $err = "<div style='padding: 10px; font-weight: bold; color: red;'>$err</div>";
		return "
			$err
 			<form method='post' class='certregform'>
				<fieldset>
					<p>
						<label for='user_partnercode'>".__('Partner Code', 'affmaster')."</label>
						<input type='text' name='user_partnercode' id='user_partnercode' value='$_POST[user_partnercode]' />
					</p>					
					<p>
						<label for='user_city'>".__('City', 'affmaster')."</label>
						<input type='text' name='user_city' id='user_city' value='$_POST[user_city]' />
					</p>					
					<p>
						<label for='user_phone'>".__('Phone', 'affmaster')."</label>
						<input type='text' name='user_phone' id='user_phone' value='$_POST[user_phone]' />
					</p>					
					<p>
						<label for='user_Login'>".__('Username', 'affmaster')."</label>
						<input type='text' name='user_login' id='user_login' value='$_POST[user_login]' />
					</p>
					<p>
						<label for='user_email'>".__('Email', 'affmaster')."</label>
						<input type='text' name='user_email' id='user_email' value='$_POST[user_email]' />
					</p>
					<p>
						<label for='user_fname'>".__('First Name', 'affmaster')."</label>
						<input type='text' name='user_fname' id='user_fname' value='$_POST[user_fname]' />
					</p>					
					<p>
						<label for='user_lname'>".__('Last Name', 'affmaster')."</label>
						<input type='text' name='user_lname' id='user_lname' value='$_POST[user_lname]' />
					</p>
					<p>
						<label for='pass'>".__('Password', 'affmaster')."</label>
						<input type='password' name='pass' id='pass' value='$_POST[pass]' />
					</p>
					<p>
						<label for='pass2'>".__('Confirm Password', 'affmaster')."</label>
						<input type='password' name='pass2' id='pass2' value='$_POST[pass2]' />
					</p>					
					<p style='text-align: center;'>	
						<input type='submit' class='button button-primary' value='".__('Register', 'affmaster')."' />
					</p>
				</fieldset>
			</form>
		";
	}



	function ProductCustomFieldsSave($post_id) {
		 $product = wc_get_product($post_id);
		 $distribprice = isset($_POST['distribprice'])?$_POST['distribprice']:'';
		 $product->update_meta_data('distribprice', $distribprice);
		 $prodpoints = isset($_POST['prodpoints'])?$_POST['prodpoints']:'';
		 $product->update_meta_data('prodpoints', $prodpoints);
		 $product->save();
	}


	function ProductCustomFields() {
		 $args = array(
			 'id' => 'distribprice',
			 'label' => __('Partner Price', 'affmaster')
		 );
		 woocommerce_wp_text_input($args);
		 $args = array(
			 'id' => 'prodpoints',
			 'label' => __('Bonus Points', 'affmaster')
		 );
		 woocommerce_wp_text_input($args);		 
	}




	function DoLogin() {
		if (!isset($_POST['user_username'])) return ;
		$user = get_userdatabylogin($_POST['user_username']);
 		if (!$user) $err = __('Invalid username!', 'affmaster');
		elseif (!wp_check_password($_POST['user_pass'], $user->user_pass, $user->ID)) $err = __('Incorrect password!', 'affmaster');
		else {
			wp_logout();
			wp_set_current_user($user->ID, $_POST['user_username']);
			wp_set_auth_cookie($user->ID, true);
			//do_action('wp_login', $_POST['user_username']);
			die("<META HTTP-EQUIV='Refresh' Content='0; URL=".get_site_url()."/my-account/' />");
		}
		$_GET['loginerr'] = $err;
	}

	function LoginForm() {
		$err = isset($_GET['loginerr'])?$_GET['loginerr']:'';
		if ($err) $err = "<div style='padding: 10px; font-weight: bold; color: red;'>$err</div>";
		return "
			$err
			<form class='certregform' method='post'>
				<fieldset>
					<p>
						<label for='user_username'>".__('Username', 'affmaster')."</label>
						<input type='text' name='user_username' id='user_username' />
					</p>
					<p>
						<label for='user_pass'>".__('Password', 'affmaster')."</label>
						<input type='password' name='user_pass' id='user_pass' />
					</p>
					<p style='text-align: center;'>	
						<input type='submit' value='".__('Login', 'affmaster')."' />
					</p>
				</fieldset>
			</form>
		";
	}


	function ShortCodes($atts) {
		global $wpdb, $current_user;
		if ($atts['sub'] == 'phone') {
			$partnerphone = '';
			if (isset($_COOKIE['pcode'])) {
				$userid = $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}usermeta WHERE (meta_key='partnercode') AND (meta_value='$_COOKIE[pcode]')");
				$partnerphone = get_user_meta($userid, 'partnerphone', true);
			}
			if ($partnerphone) return '<span>За поръчки и въпроси<br><b>' . $partnerphone . '</b></span>';
			else return '<span>За поръчки и въпроси<br><b>0884 666 235</b></span>';
		}
		if ($atts['sub'] == 'regform') {
			//if (get_option('users_can_register')) return $this->RegForm();
			//else return __('User registration is not enabled', 'affmaster');
			if (!is_user_logged_in()) {
				if (get_option('users_can_register')) return $this->RegForm();
				else return __('User registration is not enabled', 'affmaster');
			}
			else return __('Already logged in!', 'affmaster')."<br />".$current_user->display_name;
		}
		if ($atts['sub'] == 'loginform') {
			//return wp_login_form(array('echo'=>false));
			//return $this->LoginForm();
			if (!is_user_logged_in()) {
				return $this->LoginForm();
			}
			else return __('Already logged in!', 'affmaster')."<br />".$current_user->display_name;
		}		
	}

	function userFields() {
		global $wpdb;
		$userid = isset($_GET['user_id'])?$_GET['user_id']:get_current_user_id();
		echo "<h3>Партньорски данни</h3>";
		$partnercity = get_user_meta($userid, 'partnercity', true);
		$partnercode = get_user_meta($userid, 'partnercode', true);
		$partnerphone = get_user_meta($userid, 'partnerphone', true);
		echo "
			Партньорски код: <input type='text' name='partnercode' maxlength='10' minlength='10' value='$partnercode' /> &nbsp;&nbsp;
			Телефон: <input type='text' name='partnerphone' value='$partnerphone' />
			Град: <input type='text' name='partnercity' value='$partnercity' />
		";
	}

	function userFieldsUpdate($user_id) {
		update_user_meta($user_id, 'partnercode', $_POST['partnercode']);
		update_user_meta($user_id, 'partnerphone', $_POST['partnerphone']);
		update_user_meta($user_id, 'partnercity', $_POST['partnercity']);
	}

}
?>
