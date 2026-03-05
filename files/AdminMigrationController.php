<?php
/**
 * Admin Migration Controller
 */

class AdminMigrationController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitMigration')) {
            $this->migrateProducts();
        }

        parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign(array(
            'migration_url' => $this->context->link->getAdminLink('AdminMigration'),
        ));

        $this->setTemplate('migration.tpl');
    }

    private function migrateProducts()
    {
        $migrator = new ProductMigrator();
        $result = $migrator->migrate();

        if ($result['success']) {
            $this->confirmations[] = $this->module->l('Products migrated successfully');
        } else {
            $this->errors[] = $result['error'];
        }
    }
}