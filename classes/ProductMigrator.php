<?php
/**
 * Product Migrator Class
 * Handles product migration from old to new PrestaShop
 */

class ProductMigrator
{
    private $sourceDb;
    private $targetDb;
    private $sourcePrefix = 'ps_';
    private $errors = array();
    private $module;

    public function __construct()
    {
        $this->targetDb = Db::getInstance();
        $this->module = Module::getInstanceByName('prestashopMigration');
    }

    public function migrate()
    {
        try {
            $this->sourceDb = $this->connectSourceDatabase();

            if (!$this->sourceDb) {
                return array(
                    'success' => false,
                    'error' => 'Failed to connect to source database'
                );
            }

            $this->migrateAllProducts();
            $this->migrateAllCombinations();

            return array(
                'success' => true,
                'message' => 'Migration completed successfully',
                'errors' => $this->errors
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    private function connectSourceDatabase()
    {
        $host = Configuration::get('MIGRATION_DB_HOST');
        $user = Configuration::get('MIGRATION_DB_USER');
        $password = Configuration::get('MIGRATION_DB_PASSWORD');
        $dbname = Configuration::get('MIGRATION_DB_NAME');

        try {
            $connection = new mysqli($host, $user, $password, $dbname);
            if ($connection->connect_error) {
                throw new Exception('Connection failed: ' . $connection->connect_error);
            }
            return $connection;
        } catch (Exception $e) {
            return false;
        }
    }

    private function migrateAllProducts()
    {
        $query = 'SELECT * FROM ' . $this->sourcePrefix . 'product WHERE active = 1';
        $result = $this->sourceDb->query($query);

        if (!$result) {
            $this->errors[] = 'Failed to fetch products from source';
            return;
        }

        $count = 0;
        while ($product = $result->fetch_assoc()) {
            $this->migrateProduct($product);
            $count++;
        }

        $this->logMigration('Products migrated: ' . $count);
    }

    private function migrateProduct($sourceProduct)
    {
        try {
            $product = new Product();
            $product->name = $sourceProduct['name'];
            $product->description = $sourceProduct['description'];
            $product->description_short = $sourceProduct['description_short'];
            $product->ean13 = $sourceProduct['ean13'];
            $product->active = $sourceProduct['active'];
            $product->price = $sourceProduct['price'];
            $product->wholesale_price = $sourceProduct['wholesale_price'];
            $product->quantity = $sourceProduct['quantity'];
            $product->reference = $sourceProduct['reference'];
            $product->supplier_reference = $sourceProduct['supplier_reference'];

            if (!$product->save()) {
                $this->errors[] = 'Failed to save product: ' . $sourceProduct['id'];
            } else {
                $this->logProductMigration($sourceProduct['id'], $product->id, 'success');
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->logProductMigration($sourceProduct['id'], 0, 'error', $e->getMessage());
        }
    }

    private function migrateAllCombinations()
    {
        $query = 'SELECT * FROM ' . $this->sourcePrefix . 'product_attribute';
        $result = $this->sourceDb->query($query);

        if (!$result) {
            $this->errors[] = 'Failed to fetch combinations from source';
            return;
        }

        $count = 0;
        while ($combination = $result->fetch_assoc()) {
            $this->migrateCombination($combination);
            $count++;
        }

        $this->logMigration('Combinations migrated: ' . $count);
    }

    private function migrateCombination($sourceCombination)
    {
        try {
            $sourceProductId = $sourceCombination['id_product'];
            $query = 'SELECT new_product_id FROM ' . _DB_PREFIX_ . 'migration_log 
                     WHERE product_id = ' . (int)$sourceProductId . ' AND status = "success" LIMIT 1';
            $result = $this->targetDb->executeS($query);

            if (empty($result)) {
                $this->errors[] = 'Product not found for combination: ' . $sourceCombination['id'];
                return;
            }

            $newProductId = $result[0]['new_product_id'];

            $combination = new Combination();
            $combination->id_product = $newProductId;
            $combination->reference = $sourceCombination['reference'];
            $combination->supplier_reference = $sourceCombination['supplier_reference'];
            $combination->ean13 = $sourceCombination['ean13'];
            $combination->quantity = $sourceCombination['quantity'];
            $combination->price = $sourceCombination['price'];
            $combination->weight = $sourceCombination['weight'];

            if (!$combination->save()) {
                $this->errors[] = 'Failed to save combination: ' . $sourceCombination['id'];
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    private function logProductMigration($sourceId, $newId, $status, $error = '')
    {
        $query = 'INSERT INTO ' . _DB_PREFIX_ . 'migration_log 
                  (product_id, new_product_id, status, error_message) 
                  VALUES (' . (int)$sourceId . ', ' . (int)$newId . ', "' . pSQL($status) . '", "' . pSQL($error) . '")';
        $this->targetDb->execute($query);
    }

    private function logMigration($message)
    {
        PrestaShopLogger::addLog($message, 1, null, 'PrestashopMigration');
    }
}