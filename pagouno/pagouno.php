<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pagouno extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'pagouno';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Guisardo';
        $this->controllers = array('payment', 'validation');
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;
        $this->displayName = 'pagoUno';
        $this->description = 'Cobre con tarjeta de credito y débito con pagoUno.';
        $this->confirmUninstall = '¿Esta seguro que quiere desinstalar el módulo pagoUno?';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        parent::__construct();
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install()
            && $this->registerHook('header')
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
            && $this->registerHook('orderConfirmation')
            && $this->registerHook('actionValidateOrder')
            && $this->addOrderState();
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }


    public function addOrderState() {
        // nombre y color de los estados
        $status = [
            [
                'name'  => $this->l('pagoUno - transacción aprobada'),
                'color' => '#ebf5e6',
                'template' => 'payment',
                'paid' => true
            ],
            [
                'name'  => $this->l('pagoUno - transacción rechazada'),
                'color' => '#ffaaaa',
                'template' => 'payment_error',
                'paid' => false
            ]
        ];

        for ($i = 0; $i < count($status); $i ++) {
            $state_exist = false;
            $states = OrderState::getOrderStates((int)$this->context->language->id);

            // si el estado existe
            foreach ($states as $state) {
                if (in_array($status[$i]['name'], $state)) {
                    $state_exist = true;
                    break;
                }
            }

            if (!$state_exist) {
                // nuevo order status
                $order_state = new OrderState();
                $order_state->name = array();
                $order_state->template = $status[$i]['template'];
                $order_state->module_name = 'pagoUno';
                $order_state->color = $status[$i]['color'];
                $order_state->invoice = true;
                $order_state->send_email = true;
                $order_state->unremovable = false;
                $order_state->hidden = false;
                $order_state->logable = true;
                $order_state->delivery = false;
                $order_state->shipped = false;
                $order_state->paid = $status[$i]['paid'];
                $order_state->deleted = false;

                $languages = Language::getLanguages(false);
                foreach ($languages as $language)
                    $order_state->name[ $language['id_lang'] ] = $status[$i]['name'];

                // actualizar
                if ($order_state->add()) {
                    $file = _PS_ROOT_DIR_ . '/img/os/' . (int) $order_state->id . '.gif';
                    copy((dirname(__FILE__) . '/views/img/pu_icon.gif'), $file);
                }
            }
        }
        return true;
    }

    public function getPagoUnoStatus() {
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

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        function isNumeric ($num) {
            if (is_numeric($num)) {
                if ($num == 0) {
                    return 'no';
                } else {
                    return $num;
                }
            } else {
                if (empty($num)) {
                    return '1';
                } else {
                    return 'no';
                }
            }
        }

        $output = null;

        if (Tools::isSubmit('submit'.$this->name)) {

                $output .= $this->displayConfirmation($this->l('Configuración guardada'));

            }
        }

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Load current value
        $helper->fields_value['PAGOUNO_VALIDACION_ADICIONAL'] = Tools::getValue('PAGOUNO_VALIDACION_ADICIONAL', Configuration::get('PAGOUNO_VALIDACION_ADICIONAL'));
        $helper->fields_value['PAGOUNO_PUBLIC_KEY'] = Tools::getValue('PAGOUNO_PUBLIC_KEY', Configuration::get('PAGOUNO_PUBLIC_KEY'));
        $helper->fields_value['PAGOUNO_PRIVATE_KEY'] = Tools::getValue('PAGOUNO_PRIVATE_KEY', Configuration::get('PAGOUNO_PRIVATE_KEY'));
        $helper->fields_value['PAGOUNO_CODIGO_AGRUPADOR'] = Tools::getValue('PAGOUNO_CODIGO_AGRUPADOR', Configuration::get('PAGOUNO_CODIGO_AGRUPADOR'));

        // cuotas
        $helper->fields_value['PAGOUNO_AC3'] = Tools::getValue('PAGOUNO_AC3', Configuration::get('PAGOUNO_AC3'));
        $helper->fields_value['PAGOUNO_AC6'] = Tools::getValue('PAGOUNO_AC6', Configuration::get('PAGOUNO_AC6'));
        $helper->fields_value['PAGOUNO_AC9'] = Tools::getValue('PAGOUNO_AC9', Configuration::get('PAGOUNO_AC9'));
        $helper->fields_value['PAGOUNO_AC12'] = Tools::getValue('PAGOUNO_AC12', Configuration::get('PAGOUNO_AC12'));
        $helper->fields_value['PAGOUNO_AC24'] = Tools::getValue('PAGOUNO_AC24', Configuration::get('PAGOUNO_AC24'));
        $helper->fields_value['PAGOUNO_AA3'] = Tools::getValue('PAGOUNO_AA3', Configuration::get('PAGOUNO_AA3'));
        $helper->fields_value['PAGOUNO_AA6'] = Tools::getValue('PAGOUNO_AA6', Configuration::get('PAGOUNO_AA6'));
        $helper->fields_value['PAGOUNO_AA12'] = Tools::getValue('PAGOUNO_AA12', Configuration::get('PAGOUNO_AA12'));
        $helper->fields_value['PAGOUNO_AA18'] = Tools::getValue('PAGOUNO_AA18', Configuration::get('PAGOUNO_AA18'));
        $helper->fields_value['PAGOUNO_AS3'] = Tools::getValue('PAGOUNO_AS3', Configuration::get('PAGOUNO_AS3'));
        $helper->fields_value['PAGOUNO_AS6'] = Tools::getValue('PAGOUNO_AS6', Configuration::get('PAGOUNO_AS6'));
        $helper->fields_value['PAGOUNO_AS12'] = Tools::getValue('PAGOUNO_AS12', Configuration::get('PAGOUNO_AS12'));

        // coeficientes
        $helper->fields_value['PAGOUNO_C3'] = Tools::getValue('PAGOUNO_C3', Configuration::get('PAGOUNO_C3'));
        $helper->fields_value['PAGOUNO_C6'] = Tools::getValue('PAGOUNO_C6', Configuration::get('PAGOUNO_C6'));
        $helper->fields_value['PAGOUNO_C9'] = Tools::getValue('PAGOUNO_C9', Configuration::get('PAGOUNO_C9'));
        $helper->fields_value['PAGOUNO_C12'] = Tools::getValue('PAGOUNO_C12', Configuration::get('PAGOUNO_C12'));
        $helper->fields_value['PAGOUNO_C24'] = Tools::getValue('PAGOUNO_C24', Configuration::get('PAGOUNO_C24'));
        $helper->fields_value['PAGOUNO_CA3'] = Tools::getValue('PAGOUNO_CA3', Configuration::get('PAGOUNO_CA3'));
        $helper->fields_value['PAGOUNO_CA6'] = Tools::getValue('PAGOUNO_CA6', Configuration::get('PAGOUNO_CA6'));
        $helper->fields_value['PAGOUNO_CA12'] = Tools::getValue('PAGOUNO_CA12', Configuration::get('PAGOUNO_CA12'));
        $helper->fields_value['PAGOUNO_CA18'] = Tools::getValue('PAGOUNO_CA18', Configuration::get('PAGOUNO_CA18'));
    
        $this->context->controller->addJS($this->_path . 'views/js/cleave.js');
        $this->context->controller->addJS($this->_path . 'views/js/configuration-mask.js');

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => [
                    'title' => $this->l('CONFIGURACIÓN DE PAGOUNO'),
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar la validación adicional'),
                        'name' => 'PAGOUNO_VALIDACION_ADICIONAL',
                        'size' => 20,
                        'required' => false,
                        'class' => 't',
                        //'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_VALIDACION_ADICIONAL_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_VALIDACION_ADICIONAL_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Llave de Acceso Privada (Private Key)'),
                        'name' => 'PAGOUNO_PRIVATE_KEY',
                        'col' => '4',
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Llave de Acceso Pública (Public Key)'),
                        'name' => 'PAGOUNO_PUBLIC_KEY',
                        'col' => '4',
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Código de Agrupador'),
                        'name' => 'PAGOUNO_CODIGO_AGRUPADOR',
                        'col' => '4',
                        'required' => true
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>Habilitar las Siguientes Cantidades de Cuotas: </strong></hr>'
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>3 Cuotas</strong></hr>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar 3 Cuotas'),
                        'name' => 'PAGOUNO_AC3',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AC3_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AC3_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Coeficiente para 3 Cuotas'),
                        'col' => '4',
                        'name' => 'PAGOUNO_C3',
                        'required' => false
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>6 Cuotas</strong></hr>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar 6 Cuotas'),
                        'name' => 'PAGOUNO_AC6',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AC6_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AC6_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Coeficiente para 6 Cuotas'),
                        'col' => '4',
                        'name' => 'PAGOUNO_C6',
                        'required' => false
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>9 Cuotas</strong></hr>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar 9 Cuotas'),
                        'name' => 'PAGOUNO_AC9',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AC9_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AC9_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Coeficiente para 9 Cuotas'),
                        'col' => '4',
                        'name' => 'PAGOUNO_C9',
                        'required' => false
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>12 Cuotas</strong></hr>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar 12 Cuotas'),
                        'name' => 'PAGOUNO_AC12',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AC12_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AC12_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Coeficiente para 12 Cuotas'),
                        'col' => '4',
                        'name' => 'PAGOUNO_C12',
                        'required' => false
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>24 Cuotas</strong></hr>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar 24 Cuotas'),
                        'name' => 'PAGOUNO_AC24',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AC24_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AC24_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Coeficiente para 24 Cuotas'),
                        'col' => '4',
                        'name' => 'PAGOUNO_C24',
                        'required' => false
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>Ahora 3</strong></hr>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar Ahora 3'),
                        'name' => 'PAGOUNO_AA3',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AA3_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AA3_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Coeficiente para Ahora 3'),
                        'col' => '4',
                        'name' => 'PAGOUNO_CA3',
                        'required' => false
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>Ahora 6</strong></hr>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar Ahora 6'),
                        'name' => 'PAGOUNO_AA6',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AA6_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AA6_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Coeficiente para Ahora 6'),
                        'col' => '4',
                        'name' => 'PAGOUNO_CA6',
                        'required' => false
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>Ahora 12</strong></hr>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar Ahora 12'),
                        'name' => 'PAGOUNO_AA12',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AA12_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AA12_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Coeficiente para Ahora 12'),
                        'col' => '4',
                        'name' => 'PAGOUNO_CA12',
                        'required' => false
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>Ahora 18</strong></hr>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar Ahora 18'),
                        'name' => 'PAGOUNO_AA18',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AA18_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AA18_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Coeficiente para Ahora 18'),
                        'col' => '4',
                        'name' => 'PAGOUNO_CA18',
                        'required' => false
                    ],
                    [
                        'type' => 'html',
                        'name' => 'PAGOUNO_HTML',
                        'html_content' => '<hr><strong>Habilitar las Siguientes Cantidades de Cuotas Sin Interes: </strong></hr>'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar 3 Cuotas Sin Interes'),
                        'name' => 'PAGOUNO_AS3',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AS3_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AS3_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar 6 Cuotas Sin Interes'),
                        'name' => 'PAGOUNO_AS6',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AS6_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AS6_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar 12 Cuotas Sin Interes'),
                        'name' => 'PAGOUNO_AS12',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'PAGOUNO_AS12_OFF',
                                'value' => 0,
                                'lavel' => $this->l('Desactivado')
                            ),
                            array(
                                'id' => 'PAGOUNO_AS12_ON',
                                'value' => 1,
                                'lavel' => $this->l('Activado')
                            )
                        )
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ]
            )
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $output = '';

        $val_adicional = strval(Tools::getValue('PAGOUNO_VALIDACION_ADICIONAL'));
        $public_key    = strval(Tools::getValue('PAGOUNO_PUBLIC_KEY'));
        $private_key   = strval(Tools::getValue('PAGOUNO_PRIVATE_KEY'));
        $merch_code    = strval(Tools::getValue('PAGOUNO_CODIGO_AGRUPADOR'));
        // cuotas
        $ac3           = strval(Tools::getValue('PAGOUNO_AC3'));
        $ac6           = strval(Tools::getValue('PAGOUNO_AC6'));
        $ac9           = strval(Tools::getValue('PAGOUNO_AC9'));
        $ac12          = strval(Tools::getValue('PAGOUNO_AC12'));
        $ac24          = strval(Tools::getValue('PAGOUNO_AC24'));
        $aa3           = strval(Tools::getValue('PAGOUNO_AA3'));
        $aa6           = strval(Tools::getValue('PAGOUNO_AA6'));
        $aa12          = strval(Tools::getValue('PAGOUNO_AA12'));
        $aa18          = strval(Tools::getValue('PAGOUNO_AA18'));
        $as3           = strval(Tools::getValue('PAGOUNO_AS3'));
        $as6           = strval(Tools::getValue('PAGOUNO_AS6'));
        $as12          = strval(Tools::getValue('PAGOUNO_AS12'));
        // coeficientes
        $coef3         = Tools::getValue('PAGOUNO_C3');
        $coef6         = Tools::getValue('PAGOUNO_C6');
        $coef9         = Tools::getValue('PAGOUNO_C9');
        $coef12        = Tools::getValue('PAGOUNO_C12');
        $coef24        = Tools::getValue('PAGOUNO_C24');
        $coefA3        = Tools::getValue('PAGOUNO_CA3');
        $coefA6        = Tools::getValue('PAGOUNO_CA6');
        $coefA12       = Tools::getValue('PAGOUNO_CA12');
        $coefA18       = Tools::getValue('PAGOUNO_CA18');

        if (!$public_key
        ||  !$private_key
        ||  !$merch_code
        ||  empty($public_key)
        ||  empty($private_key)
        ||  empty($merch_code)
        ||  !Validate::isGenericName($public_key)
        ||  !Validate::isGenericName($private_key)
        ||  !Validate::isGenericName($merch_code)
        ||  isNumeric($coef3) == 'no'
        ||  isNumeric($coef6) == 'no'
        ||  isNumeric($coef9) == 'no'
        ||  isNumeric($coef12) == 'no'
        ||  isNumeric($coef24) == 'no'
        ||  isNumeric($coefA6) == 'no'
        ||  isNumeric($coefA12) == 'no'
        ||  isNumeric($coefA18) == 'no') {

            $output .= $this->displayError($this->l('Configuración inválida'));

        } else {
            Configuration::updateValue('PAGOUNO_VALIDACION_ADICIONAL', $val_adicional);
            Configuration::updateValue('PAGOUNO_PUBLIC_KEY', $public_key);
            Configuration::updateValue('PAGOUNO_PRIVATE_KEY', $private_key);
            Configuration::updateValue('PAGOUNO_CODIGO_AGRUPADOR',$merch_code);
            //Configuration::updateValue('PAGOUNO_CUOTAS', $pu_cuotas_arr);
            // cuotas
            Configuration::updateValue('PAGOUNO_AC3', $ac3);
            Configuration::updateValue('PAGOUNO_AC6', $ac6);
            Configuration::updateValue('PAGOUNO_AC9', $ac9);
            Configuration::updateValue('PAGOUNO_AC12', $ac12);
            Configuration::updateValue('PAGOUNO_AC24', $ac24);
            Configuration::updateValue('PAGOUNO_AA3', $aa3);
            Configuration::updateValue('PAGOUNO_AA6', $aa6);
            Configuration::updateValue('PAGOUNO_AA12', $aa12);
            Configuration::updateValue('PAGOUNO_AA18', $aa18);
            Configuration::updateValue('PAGOUNO_AS3', $as3);
            Configuration::updateValue('PAGOUNO_AS6', $as6);
            Configuration::updateValue('PAGOUNO_AS12', $as12);
            // coeficientes
            Configuration::updateValue('PAGOUNO_C3', $coef3);
            Configuration::updateValue('PAGOUNO_C6', $coef6);
            Configuration::updateValue('PAGOUNO_C9', $coef9);
            Configuration::updateValue('PAGOUNO_C12', $coef12);
            Configuration::updateValue('PAGOUNO_C24', $coef24);
            Configuration::updateValue('PAGOUNO_CA3', $coefA3);
            Configuration::updateValue('PAGOUNO_CA6', $coefA6);
            Configuration::updateValue('PAGOUNO_CA12', $coefA12);
            Configuration::updateValue('PAGOUNO_CA18', $coefA18);
        }

        return $output;
    }
    public function pagounoCuotas() {
        $total = Context::getContext()->cart->getOrderTotal(true);

        function option($cuotas, $total, $coef, $case) {
            switch ($case) {
                case 'cint':
                    return [
                        'inner' =>
                            $cuotas
                            . ' cuotas de $'
                            . number_format((float)(( $total * $coef ) / $cuotas), 2, '.', '')
                            . ' (Total: $' . number_format((float)($total * $coef), 2, '.', '')
                            . ')',
                        'cuotas' => $cuotas,
                        'total' => number_format((float)($total * $coef), 2, '.', '')
                    ];
                    break;
                case 'sint':
                    return [
                        'inner' =>
                            $cuotas
                            . ' cuotas sin interés de $'
                            . number_format((float)(( $total * $coef ) / $cuotas), 2, '.', '')
                            . ' (Total: $' . number_format((float)($total * $coef), 2, '.', '')
                            . ')',
                        'cuotas' => $cuotas,
                        'total' => number_format((float)($total * $coef), 2, '.', '')
                    ];
                    break;
                case 'ahora':
                    $acuota = '';
                    switch ($cuotas) {
                        case 13: $acuota = 3; break;
                        case 16: $acuota = 6; break;
                        case 7: $acuota = 12; break;
                        case 8: $acuota = 18; break;
                    };
                    return [
                        'inner' =>
                            $acuota
                            . ' cuotas con Ahora ' . $acuota . ' de $'
                            . number_format((float)(( $total * $coef ) / $acuota), 2, '.', '')
                            . ' (Total: $' . number_format((float)($total * $coef), 2, '.', '')
                            . ')',
                        'cuotas' => $cuotas,
                        'total' => number_format((float)($total * $coef), 2, '.', '')
                    ];
                    break;
            }
        }

        function active_options($val) {
            $options = [];
            for ($i = 0; $i < count($val); $i++) {
                if ($val[$i]['isActive'] == 1) {
                    array_push($options, $val[$i]);
                }
            }
            return $options;
        }

        $options = [
            [
                'cuota'    => '3 cuotas de $'.number_format((float)$total, 2, '.', '').' (Total: $'.$total.')',
                'isActive' => Tools::getValue('PAGOUNO_AC3', Configuration::get('PAGOUNO_AC3')),
                'option'   => option(3, number_format((float)$total, 2, '.', ''), Tools::getValue('PAGOUNO_C3', Configuration::get('PAGOUNO_C3')), 'cint')
            ],
            [
                'cuota'    => '6 cuotas',
                'isActive' => Tools::getValue('PAGOUNO_AC6', Configuration::get('PAGOUNO_AC6')),
                'option'   => option(6, number_format((float)$total, 2, '.', ''), Tools::getValue('PAGOUNO_C6', Configuration::get('PAGOUNO_C6')), 'cint')
            ],
            [
                'cuota'    => '9 cuotas',
                'isActive' => Tools::getValue('PAGOUNO_AC9', Configuration::get('PAGOUNO_AC9')),
                'option'   => option(9, number_format((float)$total, 2, '.', ''), Tools::getValue('PAGOUNO_C9', Configuration::get('PAGOUNO_C9')), 'cint')
            ],
            [
                'cuota'    => '12 cuotas',
                'isActive' => Tools::getValue('PAGOUNO_AC12', Configuration::get('PAGOUNO_AC12')),
                'option'   => option(12, number_format((float)$total, 2, '.', ''), Tools::getValue('PAGOUNO_C12', Configuration::get('PAGOUNO_C12')), 'cint')
            ],
            [
                'cuota'    => '24 cuotas',
                'isActive' => Tools::getValue('PAGOUNO_AC24', Configuration::get('PAGOUNO_AC24')),
                'option'   => option(24, number_format((float)$total, 2, '.', ''), Tools::getValue('PAGOUNO_C24', Configuration::get('PAGOUNO_C24')), 'cint')
            ],
            [
                'cuota'    => 'ahora 3',
                'isActive' => Tools::getValue('PAGOUNO_AA3', Configuration::get('PAGOUNO_AA3')),
                'option'   => option(13, number_format((float)$total, 2, '.', ''), Tools::getValue('PAGOUNO_CA3', Configuration::get('PAGOUNO_CA3')), 'ahora')
            ],
            [
                'cuota'    => 'ahora 6',
                'isActive' => Tools::getValue('PAGOUNO_AA6', Configuration::get('PAGOUNO_AA6')),
                'option'   => option(16, number_format((float)$total, 2, '.', ''), Tools::getValue('PAGOUNO_CA6', Configuration::get('PAGOUNO_CA6')), 'ahora')
            ],
            [
                'cuota'    => 'ahora 12',
                'isActive' => Tools::getValue('PAGOUNO_AA12', Configuration::get('PAGOUNO_AA12')),
                'option'   => option(7, number_format((float)$total, 2, '.', ''), Tools::getValue('PAGOUNO_CA12', Configuration::get('PAGOUNO_CA12')), 'ahora')
            ],
            [
                'cuota'    => 'ahora 18',
                'isActive' => Tools::getValue('PAGOUNO_AA18', Configuration::get('PAGOUNO_AA18')),
                'option'   => option(8, number_format((float)$total, 2, '.', ''), Tools::getValue('PAGOUNO_CA18', Configuration::get('PAGOUNO_CA18')), 'ahora')
            ],
            [
                'cuota'    => '3 cuotas sin interes',
                'isActive' => Tools::getValue('PAGOUNO_AS3', Configuration::get('PAGOUNO_AS3')),
                'option'   => option(3, number_format((float)$total, 2, '.', ''), 1, 'sint')
            ],
            [
                'cuota'    => '6 cuotas sin interes',
                'isActive' => Tools::getValue('PAGOUNO_AS3', Configuration::get('PAGOUNO_AS6')),
                'option'   => option(6, number_format((float)$total, 2, '.', ''), 1, 'sint')
            ],
            [
                'cuota'    => '12 cuotas sin interes',
                'isActive' => Tools::getValue('PAGOUNO_AS3', Configuration::get('PAGOUNO_AS12')),
                'option'   => option(12, number_format((float)$total, 2, '.', ''), 1, 'sint')
            ]
        ];

        return active_options($options);
    }

    public function hookHeader() {
        $this->context->controller->addJS($this->_path . 'views/js/cleave.js');
        $this->context->controller->addJS($this->_path . 'views/js/mask.js');
        $this->context->controller->addJS($this->_path . 'views/js/pagouno.js');
        $this->context->controller->addCSS($this->_path . 'views/css/pagouno-css-form.css');

        Media::addJsDef(array(
            'php_params' => array(
                'extendedForm' => Tools::getValue('PAGOUNO_VALIDACION_ADICIONAL', Configuration::get('PAGOUNO_VALIDACION_ADICIONAL')),
                'publickey' => Tools::getValue('PAGOUNO_PUBLIC_KEY', Configuration::get('PAGOUNO_PUBLIC_KEY')),
            )
        ));
    }

    public function hookPaymentOptions($params) {
        /*
         * Verify if this module is active
         */

        if (!$this->active) {
            return;
        }

        $formAction = $this->context->link->getModuleLink($this->name, 'validation', array(), true);

        $this->smarty->assign(['action' => $formAction]);
        $this->smarty->assign(['id_cart' => $this->context->cart->id]);
        $this->smarty->assign(['payment_options' => $this->pagounoCuotas()]);
        $this->smarty->assign(['payment_total' => number_format((float)Context::getContext()->cart->getOrderTotal(true), 2, '.', '')]);

        if (Tools::getValue('PAGOUNO_VALIDACION_ADICIONAL', Configuration::get('PAGOUNO_VALIDACION_ADICIONAL')) == 0) {
            $paymentForm = $this->fetch('module:pagoUno/views/templates/hook/pagouno-form.tpl');
        } else {
            $paymentForm = $this->fetch('module:pagoUno/views/templates/hook/pagouno-form-extra.tpl');
        }

        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $newOption->setModuleName($this->displayName)
            ->setCallToActionText('Tarjeta de Crédito o Débito')
            ->setAction($formAction)
            ->setForm($paymentForm);

        $payment_options = array(
            $newOption
        );

        return $payment_options;
    }

    public function hookPaymentReturn($params) {

        if (!$this->active) {
            return;
        }

        return $this->fetch('module:pagoUno/views/templates/hook/payment_return.tpl');
    }

    public function hookOrderConfirmation($params) {
    }

    public function hookActionValidateOrder($params) {
    }
}
