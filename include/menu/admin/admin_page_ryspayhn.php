<?php
/**
 * Copyright (c) 2020.
 * File: admin_page_ryspayhn.php
 * Last Modified: 13/1/20 10:10 a. m.
 * Jesus Nuñez
 */
require_once dirname(__DIR__, 2) . '/lib/ryspayhn_request.php';
function ryspayhn_styles($hook)
{
	$current_screen = get_current_screen();
	if (!strpos($current_screen->base, 'ryspayhn')) {
		return;
	} else {
		wp_enqueue_script('vuejs_Ryspayhn', plugin_dir_url(__FILE__) . 'assets/js/vue.js');
		wp_enqueue_script('datatables_Ryspayhn', plugin_dir_url(__FILE__) . 'assets/js/jquery.dataTables.min.js', ['jquery']);
		wp_enqueue_script('dataBootstrap_Ryspayhn', plugin_dir_url(__FILE__) . 'assets/js/dataTables.bootstrap4.min.js');
		
		wp_register_style('bootstrap_Ryspayhn', plugin_dir_url(__FILE__) . 'assets/css/bootstrap.css');
		wp_register_style('datatables_Ryspayhn', plugin_dir_url(__FILE__) . 'assets/css/dataTables.bootstrap4.min.css');
		wp_enqueue_style('bootstrap_Ryspayhn');
		wp_enqueue_style('datatables_Ryspayhn');
	}
}

function ryspayhn_admin()
{
	
	?>
	<div id="app" class="container">
		<h6 v-if="information!==null" class="text-danger">
			{{information}}
		</h6>
		<div class="row" v-if='typeof message !== "string"'>
			<div class="col">
				<table id="transactions" class="table table-striped table-bordered" style="width:100%">
					<thead>
					<tr>
						<th>Status</th>
						<th>Orden</th>
						<th>Monto</th>
						<th>Fecha</th>
					</tr>
					</thead>
					<tbody>
					<tr v-for="(transactions, index) of message">
						<td>{{transactions.status}}</td>
						<td>{{transactions.description}}</td>
						<td>{{transactions.amount}}</td>
						<td>{{transactions.createdAt}}</td>
					</tr>
					</tbody>
					<tfoot>
					<tr>
						<th>Status</th>
						<th>Orden</th>
						<th>Monto</th>
						<th>Fecha</th>
					</tr>
					</tfoot>
				</table>
			</div>
		</div>
		<h1 v-else>
			{{ message }}
		</h1>
	</div>
	<script>
        jQuery(document).ready(function () {
            jQuery('#transactions').DataTable();
        });
        var app = new Vue({
            el: '#app',
            data: {
                information: null,
                message: <?php
				$a = get_ryspayhn_transactions();
				echo $a;
				?>,
                messages: <?php
				$a = get_ryspayhn_license();
				echo $a;
				?>
            },
            mounted() {
                try {
                    if (this.message) {
                        this.message = JSON.parse(this.message);
                    }
                    this.messages = JSON.parse(this.messages);
                } catch (e) {
                    this.message = "Token PayGate Invalido".toString()
                }
                if (this.messages.status !== true) {
                    this.information = 'Esta usando la version de prueba de Ryspayhn-Gateways por 30 dias, recuerde solo se permiten 3 pagos al dia por montos menores a 10$, para mayor informacion visite www.ryspayhn.com'
                } else if (this.message.hasOwnProperty('message')) {
                    this.message = 'El token utilizado es invalido';
                } else {
                    for (let i in this.message) {
                        if (this.message[i].hasOwnProperty('createdAt')) {
                            this.message[i].createdAt = new Date(this.message[i].createdAt);
                        }
                    }
                }
            },
        })
	</script>
	<?php
}

function get_ryspayhn_transactions()
{
	$transactions = new ryspayhn_request(null, null, null, null, null, null, null, null, null);
	$transactions = $transactions->get_user_transaccions();
	
	return $transactions;
}

function get_ryspayhn_license()
{
	$transactions = new ryspayhn_request(null, null, null, null, null, null, null, null, null);
	$transactions = $transactions->license_verification();
	
	return $transactions;
}