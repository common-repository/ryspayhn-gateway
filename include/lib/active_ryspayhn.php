<?php

require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once plugin_dir_path(__FILE__) . 'ryspayhn_request.php';
require_once plugin_dir_path(__DIR__) . '/menu/menu_ryspayhn.php';
function wc_ryspayhn_add_to_gateways($gateways)
{
	$gateways[] = 'WC_Gateway_ryspayhn';
	return $gateways;
}

add_filter('woocommerce_payment_gateways', 'wc_ryspayhn_add_to_gateways');

function wc_ryspayhn_paymentGateway_plugin_links($links)
{
	$plugin_links = [
		'<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=ryspayhn_paymentGateway') . '">' . __('Configure', 'wc-ryspayhn-gateway') . '</a>'
	];
	return array_merge($plugin_links, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_ryspayhn_paymentGateway_plugin_links');

add_action('plugins_loaded', 'wc_ryspayhn_paymentGateway_init', 11);
function wc_ryspayhn_paymentGateway_init()
{
	if (!class_exists('WC_Payment_Gateway') || !is_plugin_active('woocommerce/woocommerce.php')) {
		deactivate_plugins('ryspayhn - WooCommerce');
		return;
	}
	if (!class_exists('WC_Payment_Gateway_CC') || !is_plugin_active('woocommerce/woocommerce.php')) {
		deactivate_plugins('ryspayhn - WooCommerce');
		return;
	}
	$optionDate = "ryspayhnDate";
	$optionIntents = "ryspayhnIntents";
	$optionMount = "ryspayhnMount";
	$optionDay = "ryspayhnDay";
	if (!get_option($optionDate)) {
		update_option($optionDate, time());
		update_option($optionIntents, 3);
		update_option($optionMount, 10);
		update_option($optionDay, time());
	}
	
	
	class WC_Gateway_ryspayhn extends WC_Payment_Gateway
	{
		/**
		 * Constructor for the gateway.
		 */
		private $optionDate = "ryspayhnDate";
		private $optionIntents = "ryspayhnIntents";
		private $optionMount = "ryspayhnMount";
		private $optionDay = "ryspayhnDay";
		
		public function __construct()
		{
			
			$this->id = 'ryspayhn';
			$this->icon = '';
			$this->has_fields = true;
			$this->method_title = 'Ryspayhn Gateway';
			$this->method_description = 'Recibe pagos a traves de ryspayhn';
			$this->supports = [
				'products'
			];
			$this->init_form_fields();
			$this->init_settings();
			$this->title = $this->get_option('title');
			$this->description = $this->get_option('description');
			$this->enabled = $this->get_option('enabled');
			$this->user_tokenryspayhn = $this->get_option('user_tokenryspayhn') ? $this->get_option('user_tokenryspayhn') : '';
			$this->ryspayhn_license = $this->get_option('ryspayhn_license') ? $this->get_option('ryspayhn_license') : '';
			$this->ryspaynh_sandbox = $this->get_option('ryspaynh_sandbox');
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
		}
		
		public function init_form_fields()
		{
			
			$this->form_fields = [
				'enabled' => [
					'title' => 'Enable/Disable',
					'label' => 'Enable Ryspayhn Gateway',
					'type' => 'checkbox',
					'description' => '',
					'default' => 'no'
				],
				'ryspaynh_sandbox' => [
					'title' => 'Test Mode',
					'label' => 'Enable Ryspayhn Test Mode',
					'type' => 'checkbox',
					'description' => '',
					'default' => 'no'
				],
				'title' => [
					'title' => 'Title',
					'type' => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default' => 'Credit Card',
					'desc_tip' => true,
				],
				'description' => [
					'title' => 'Description',
					'type' => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default' => 'Pay with your credit card via ryspayhn.',
				],
				'user_tokenryspayhn' => [
					'title' => 'User PayGate Token',
					'type' => 'text'
				],
				'ryspayhn_license' => [
					'title' => 'User Ryspayhn License',
					'type' => 'text'
				]
			];
		}
		
		public function payment_fields()
		{
			$cc_form = new WC_Payment_Gateway_CC();
			$cc_form->id = $this->id;
			$cc_form->supports = $this->supports;
			$cc_form->form();
		}
		
		public function validate_fields()
		{
			if (empty($this->get_option('user_tokenryspayhn'))) {
				wc_add_notice('Disculpe, aun no aceptamos pagos por esta via, por favor contacte al administrador para mas informacion', 'error');
				return false;
			}
			if (empty(sanitize_text_field($_POST['ryspayhn-card-number']))) {
				wc_add_notice('El numero de la tarjeta es requerido', 'error');
				return false;
			}
			if (empty(sanitize_text_field($_POST['ryspayhn-card-expiry']))) {
				wc_add_notice('La fecha de vencimiento de la tarjeta es obligatoria', 'error');
				return false;
			}
			$_POST['ryspayhn-card-expiry'] = str_replace(' ', '', $_POST['ryspayhn-card-expiry']);
			$date = sanitize_text_field($_POST['ryspayhn-card-expiry']);
			$values = explode('/', $date);
			$expires = DateTime::createFromFormat('my', $values[0] . $values[1]);
			$now = new DateTime();
			
			if ($expires < $now || $values[0] > 12) {
				wc_add_notice('Su tarjeta ha expirado o la fecha ingresada es invalida', 'error');
				return false;
			}
			if (empty($_POST['ryspayhn-card-cvc'])) {
				wc_add_notice('El CVV de la tarjeta es obligatorio', 'error');
				return false;
			}
			$_POST['ryspayhn-card-number'] = str_replace(' ', '', sanitize_text_field($_POST['ryspayhn-card-number']));
			if ($this->luhn_check($_POST['ryspayhn-card-number']) === false) {
				wc_add_notice('Su tarjeta es invalida', 'error');
				return false;
			}
			$cardType = $this->check_cc($_POST['ryspayhn-card-number']);
			if ($cardType === false) {
				wc_add_notice('Su tarjeta no es soportada por nuestro procesador de pagos', 'error');
				return false;
			}
			$_POST['ryspayhn-card-type'] = sanitize_text_field($cardType);
			return true;
		}
		
		public function luhn_check($number)
		{
			$number = preg_replace('/\D/', '', $number);
			$number_length = strlen($number);
			$parity = $number_length % 2;
			$total = 0;
			for ($i = 0; $i < $number_length; $i++) {
				$digit = $number[$i];
				if ($i % 2 == $parity) {
					$digit *= 2;
					if ($digit > 9) {
						$digit -= 9;
					}
				}
				$total += $digit;
			}
			return ($total % 10 == 0) ? true : false;
		}
		
		function check_cc($cc)
		{
			$cards = [
				"visa" => "(4\d{12}(?:\d{3})?)",
				"amex" => "(3[47]\d{13})",
				"jcb" => "(35[2-8][89]\d\d\d{10})",
				"maestro" => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
				"mastercard" => "(5[1-5]\d{14})",
				'dinners' => '/^3(0[0-5]|[68][0-9])[0-9]{11}$/',
				"union" => '^(62[0-9]{14,17})$',
				"discover" => '^65[4-9][0-9]{13}|64[4-9][0-9]{13}|6011[0-9]{12}|(622(?:12[6-9]|1[3-9][0-9]|[2-8][0-9][0-9]|9[01][0-9]|92[0-5])[0-9]{10})$'
			];
			$names = ["VISA", "AMERICAN_EXPRESS", "JCB", "MAESTRO", "MASTERCARD", "DINERSCLUB", "UNIONPAY", "DISCOVER"];
			$matches = [];
			$pattern = "#^(?:" . implode("|", $cards) . ")$#";
			$result = preg_match($pattern, str_replace(" ", "", $cc), $matches);
			return ($result > 0) ? $names[sizeof($matches) - 2] : false;
		}
		
		public function thankyou_page()
		{
			if ($this->instructions) {
				echo wpautop(wptexturize($this->instructions));
			}
		}
		
		public function email_instructions($order, $sent_to_admin, $plain_text = false)
		{
			
			if ($this->instructions && !$sent_to_admin && $this->id === $order->payment_method && $order->has_status('on-hold')) {
				echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
			}
		}
		
		public function process_payment($order_id)
		{
			$order = wc_get_order($order_id);
			$card_number = sanitize_text_field($_POST['ryspayhn-card-number']);
			$card_exp = sanitize_text_field($_POST['ryspayhn-card-expiry']);
			$card_cvc = sanitize_text_field($_POST['ryspayhn-card-cvc']);
			$card_type = sanitize_text_field($_POST['ryspayhn-card-type']);
			$user_token = $this->get_option('user_tokenryspayhn');
			$order_data = $order->get_data();
			try {
				$date1 = (int)get_option($this->optionDay);
				$date1 = DateTime::createFromFormat('U', $date1);
				$date2 = DateTime::createFromFormat('U', (int)time());
				$interval = new DateInterval('P30D');
				if ($date2->format("Y-m-d") > $date1->format("Y-m-d")) {
					update_option($this->optionIntents, 3);
					update_option($this->optionDay, time());
				}
				$date2->add($interval);
				if ($date2->format("Y-m-d") <= $date1->format("Y-m-d")) {
					wc_add_notice('Lo lamentamos, la version de prueba de Ryspayhn-Gateways ha caducado, por favor, ingrese una licencia, para mayor informacion visite www.ryspayhn.com', 'error');
					return false;
				}
			} catch (Exception $e) {
				error_log(print_r($e, true));
			}
			if (empty($this->get_option('ryspayhn_license')) || $this->license_verification() !== 200) {
				if ($order_data['total'] > get_option($this->optionMount)) {
					wc_add_notice('Lo lamentamos, por este medio de pago solo se aceptan montos menores a: ' . get_option($this->optionMount) . '. Para mayor informacion visite www.ryspayhn.com', 'error');
					return false;
				} else
					if (get_option($this->optionIntents) <= 0) {
						wc_add_notice('Lo lamentamos, por hoy no aceptamos mas pagos por este medio. Para mayor informacion visite www.ryspayhn.com', 'error');
						return false;
					} else if (get_option($this->optionIntents) <= 0) {
						wc_add_notice('Lo lamentamos, por hoy no aceptamos mas pagos por este medio. Para mayor informacion visite www.ryspayhn.com', 'error');
						return false;
					}
			}
			$response = new ryspayhn_request($user_token, $card_number, $card_type, $card_exp, $card_cvc, $order_data['billing']['first_name'], $order_data['billing']['last_name'], $order_data['total'], $order_data['id']);
			$response = $response->request_post();
			if ($response->code === 200) {
				$order->payment_complete();
				wc_reduce_stock_levels($order);
				WC()->cart->empty_cart();
				$order->update_status('on-hold', __('Awaiting Admin verification payment'));
				update_option($this->optionMount, $this->get_option($this->optionMount) - 1);
				return [
					'result' => 'success',
					'redirect' => $this->get_return_url($order)
				];
			} else if ($response->code === 401) {
				if ($this->license_verification() !== 200) {
					wc_add_notice('Error procesando el pedido. Por favor, comunicate con el administrador', 'error');
					return false;
				}
				wc_add_notice('Error procesando el pedido. Por favor, comunicate con el administrador', 'error');
				return false;
			} else {
				if ($this->license_verification() !== 200) {
					wc_add_notice('Error procesando el pedido. Por favor, comunicate con el administrador', 'error');
					return false;
				}
				wc_add_notice("Ha ocurrido un error, por favor verifique los datos ingresados. Error: " . $response->response->message, 'error');
				return false;
			}
		}
		
		public function license_verification()
		{
			$body = [
				'license' => $this->get_option('ryspayhn_license'),
				'server' => $_SERVER['HTTP_HOST'],
			];
			$args = [
				'body' => $body,
				'timeout' => '5',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => [],
				'cookies' => []
			];
			$response = wp_remote_post("http://licencias.ryspayhn.com/sistema/modules/include/verify.php", $args);
			
			$response = wp_remote_retrieve_response_code($response);
			return $response;
		}
	}
}
