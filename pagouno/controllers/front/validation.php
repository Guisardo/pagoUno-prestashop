<?php

class PagounoValidationModuleFrontController extends ModuleFrontController
{
    public function doPost($postUrl, $params, $key)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $postUrl,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: ' . $key
            )
        ));
        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return [$http_status, $response];
    }

    public function getPagoUnoStatus()
    {
        $status = OrderState::getOrderStates((int)$this->context->language->id);
        $pagounostatus = array();
        for ($i = 0; $i < count($status); $i ++) {
            switch ($status[$i]['name']) {
                case 'pagoUno - transacción aprobada':
                    $pagounostatus['status_aprobada'] = $status[$i]['id_order_state'];
                    break;
                case 'pagoUno - transacción rechazada':
                    $pagounostatus['status_rechazada'] = $status[$i]['id_order_state'];
                    break;
                default: break;
            }
        }
        return $pagounostatus;
    }

    public function postProcess()
    {
        $cuotas_options = Pagouno::pagounoCuotas();
        // token
        $token = Tools::getValue("pagouno_token");
        // cuotas, total y metodo de pago
        if (Tools::getValue("pagouno_cuotas") == 'no') {
            $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
            $cuotas = 1;
            $metodo_de_pago = 'pagoUno';
        } else {
            $cuotas = $cuotas_options[Tools::getValue("pagouno_cuotas")]['option']['cuotas'];
            $total = $cuotas_options[Tools::getValue("pagouno_cuotas")]['option']['total'];
            $metodo_de_pago = 'pagoUno - '.$cuotas_options[Tools::getValue("pagouno_cuotas")]['option']['inner'];
        }

        if ($total == null) {
        } else {
            // carrito
            $cart = $this->context->cart;
            $authorized = false;

            // status de pagoUno
            $pustatus = $this->getPagoUnoStatus();

            // verifica si esta activo el modulo, si es un cliente valido, y si la direcciones son validas
            if (!$this->module->active
                || $cart->id_customer == 0
                || $cart->id_address_delivery == 0
                || $cart->id_address_invoice == 0) {
                Tools::redirect('index.php?controller=order&step=1');
            }

            // verifica el modulo si esta autorizado
            foreach (Module::getPaymentModules() as $module) {
                if ($module['name'] == 'pagoUno') {
                    $authorized = true;
                    break;
                }
            }

            if (!$authorized) {
                die($this->l('Metodo de pago no disponible.'));
            } else {
                // nuevo customer
                $customer = new Customer($cart->id_customer);

                // verifica si es valida la cuenta del cliente
                if (!Validate::isLoadedObject($customer)) {
                    Tools::redirect('index.php?controller=order&step=1');
                } else {
                    // formateo de los valores para el servicio de cobro
                    $formated_price = (int)(str_replace(array(".", '"', "$"), "", number_format((float)$total, 2, '.', '')));
                    $formated_dues = (int)($cuotas);
                    $url = str_replace(array("http://", "https://", "www.", ".com", ".org", ".net", ".ar", ".cl", ".ur", ".br", ".", "/", "-", "\/"), "", Tools::getHttpHost(true).__PS_BASE_URI__);
                    if (Tools::strlen((string)($cart->id)) == 25) {
                        $seller_descriptor = (string)($cart->id);
                    } else {
                        if (Tools::strlen((string)($url . "*" . (string)($cart->id))) <= 25) {
                            $seller_descriptor = (string)($url . "*" . (string)($cart->id));
                        } else {
                            $difference = -1 * abs((Tools::strlen((string)($url)) + Tools::strlen((string)("*" . (string)($cart->id)))) - 25);
                            $chop_url = Tools::substr($url, 0, $difference);
                            $seller_descriptor = (string)($chop_url . "*" . (string)($cart->id));
                        }
                    }

                    // obejto que va en el servicio de cobro
                    $purch_list = new StdClass();
                    $purch_list -> merchant_code_group = Tools::getValue('PAGOUNO_CODIGO_AGRUPADOR', Configuration::get('PAGOUNO_CODIGO_AGRUPADOR'));
                    $purch_list -> transaction_amount = $formated_price;
                    $purch_list -> installments_plan = 0;
                    $purch_list -> installments = $formated_dues;
                    $purch_list -> transaction_currency_code = "032";
                    $purch_list -> seller_descriptor = $seller_descriptor;

                    $prim_acc = new StdClass();
                    $prim_acc -> token_id = $token;
                    $prim_acc -> purchase_list = [$purch_list];

                    $data = new StdClass();
                    $data -> transaction_group_type = 1;
                    $data -> customer_transaction_identificator = (string)($cart->id);
                    $data -> external_reference = (string)($cart->id);
                    $data -> primary_account_number_list = [$prim_acc];

                    $payload = json_encode($data);

                    //envio la info
                    $response = $this->doPost(
                        'https://api.pagouno.com/v1/Transaction/purchasegroup',
                        $payload,
                        Tools::getValue('PAGOUNO_PRIVATE_KEY', Configuration::get('PAGOUNO_PRIVATE_KEY'))
                    );

                    if ($response[0] == 200) {
                        $body = json_decode($response[1], true);
                        switch ($body['status']) {
                            case 200:
                                if ($body['data']['success']) {
                                    try {
                                        $this->module->validateOrder(
                                            (int) $this->context->cart->id,
                                            $pustatus['status_aprobada'],
                                            $this->context->cart->getOrderTotal(true, Cart::BOTH),
                                            $metodo_de_pago,
                                            null,
                                            null,
                                            (int) $this->context->currency->id,
                                            false,
                                            $customer->secure_key
                                        );

                                        $db = Db::getInstance();

                                        $order_reference = $db->executeS('SELECT reference FROM '._DB_PREFIX_.'orders WHERE id_order = '.(int)$this->module->currentOrder);

                                        $db->update('orders', array(
                                            'total_paid_tax_incl' => pSQL((float)$total), // total pagado que tiene aparece como precio total
                                            'date_upd' => date('Y-m-d H:i:s')
                                        ), 'id_order = '.(int)$this->module->currentOrder, 1, true);

                                        $db->update('order_payment', array(
                                            'amount' => pSQL((float)$total),
                                            'card_number' => $body['data']['request'][0]['last_4_digits'],
                                            'card_brand' => $body['data']['request'][0]['card']
                                        ), 'order_reference = "'.$order_reference[0]['reference'].'"', 1, true);

                                        // pagina de confirmacion
//                                        Tools::redirect(
//                                            __PS_BASE_URI__
//                                            .'index.php?controller=order-confirmation'
//                                            .'&id_cart='.(int)$cart->id
//                                            .'&id_module='.(int)$this->module->id
//                                            .'&id_order='.$this->module->currentOrder
//                                            .'&key='.$customer->secure_key
//                                        );
                                        
                                        $this->setTemplate('module:pagoUno/views/templates/hook/payment_return.tpl');
                                    } catch (Exception $e) {
                                    }
                                } else {
                                    Tools::redirect(__PS_BASE_URI__ .'index.php?controller=order&step=1&puerror=1');
                                }
                                break;
                            case 400:
                                Tools::redirect(__PS_BASE_URI__ .'index.php?controller=order&step=1&puerror=400');
                                break;
                            case 403:
                                Tools::redirect(__PS_BASE_URI__ .'index.php?controller=order&step=1&puerror=403');
                                break;
                        }
                    } else {
                        Tools::redirect(__PS_BASE_URI__ .'index.php?controller=order&step=1&puerror=2');
                    }
                }
            }
        }
    }
}
