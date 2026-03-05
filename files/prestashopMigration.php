<?php
/**
 * PrestaShop Migration Module
 * Transfers products from PrestaShop 1.7.5.1 to PrestaShop 9
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PrestashopMigration extends Module
{
    public function __construct()
    {
        $this->name = 'prestashopMigration';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'madload59';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7.5.1', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('PrestaShop Migration Module');
        $this->description = $this->l('Transfer products from PrestaShop 1.7.5.1 to PrestaShop 9');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('displayAdminProductsMain')
            || !$this->registerHook('actionAdminControllerSetMedia')) {
            return false;
        }

        return $this->createDatabaseTables();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    private function createDatabaseTables()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'migration_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `new_product_id` INT,
            `status` VARCHAR(50),
            `error_message` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )';

        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue('MIGRATION_DB_HOST', Tools::getValue('MIGRATION_DB_HOST'));
            Configuration::updateValue('MIGRATION_DB_USER', Tools::getValue('MIGRATION_DB_USER'));
            Configuration::updateValue('MIGRATION_DB_PASSWORD', Tools::getValue('MIGRATION_DB_PASSWORD'));
            Configuration::updateValue('MIGRATION_DB_NAME', Tools::getValue('MIGRATION_DB_NAME'));
            $output .= '<div class="alert alert-success">' . $this->l('Settings updated') . '</div>';
        }

        return $output . $this->renderForm();
    }

    private function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_language = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANGUAGE');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Migration Settings'),
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Source Database Host'),
                        'name' => 'MIGRATION_DB_HOST',
                        'size' => 20,
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Source Database User'),
                        'name' => 'MIGRATION_DB_USER',
                        'size' => 20,
                        'required' => true,
                    ),
                    array(
                        'type' => 'password',
                        'label' => $this->l('Source Database Password'),
                        'name' => 'MIGRATION_DB_PASSWORD',
                        'size' => 20,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Source Database Name'),
                        'name' => 'MIGRATION_DB_NAME',
                        'size' => 20,
                        'required' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            ),
        );

        return $helper->generateForm(array($fields_form));
    }

    private function getConfigFieldsValues()
    {
        return array(
            'MIGRATION_DB_HOST' => Configuration::get('MIGRATION_DB_HOST'),
            'MIGRATION_DB_USER' => Configuration::get('MIGRATION_DB_USER'),
            'MIGRATION_DB_PASSWORD' => Configuration::get('MIGRATION_DB_PASSWORD'),
            'MIGRATION_DB_NAME' => Configuration::get('MIGRATION_DB_NAME'),
        );
    }
}