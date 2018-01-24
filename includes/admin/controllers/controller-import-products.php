<?php

use CodesWholesale\Client;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('CW_Controller_Import_products')) :
    include_once(plugin_dir_path( __FILE__ ).'controller.php');
   
    /**
     * 
     */     
    class CW_Controller_Import_products extends CW_Controller
    {
        /**
         * 
         * @var WP_ImportPropertyModel[] 
         */
        public $import_history;
        
        /**
         * 
         * @var bool
         */
        public $import_in_progress;
        
        /**
         *
         * @var WP_ImportPropertyRepository 
         */
        public $import_repository;
        
        /**
         * 
         */
        public function __construct()
        {
            parent::__construct();
            
            // ajax actions
            add_action( 'wp_ajax_import_products_async', array($this, 'import_products_async'));
            add_action( 'wp_ajax_remove_import_details_async', array($this, 'remove_import_details_async'));
            
            $this->import_repository = new WP_ImportPropertyRepository();
            $this->import_history   = $this->import_repository->findAll();
            $this->import_in_progress = $this->import_repository->isActive();
        }
        
        public function init_view() {
            if (!CW()->get_codes_wholesale_client() instanceof \CodesWholesale\Client) {
                include_once(plugin_dir_path( __FILE__ ).'../views/view-blocked.php');
                return;
            }

            include_once(plugin_dir_path( __FILE__ ) . '../views/view-import-products.php');
        }

        public function remove_import_details_async() {
            $id = $_POST['id'];

            $result = new AjaxResult();

            try{
                $model = $this->import_repository->find($id);

                $this->import_repository->delete($model);
                $result->status = true;
                $result->message = 'Done';
            } catch(\Exception $e) {
                $result->status = false;
                $result->message = $e->getMessage();
            }


            echo json_encode($result);

            wp_die();
        }

        public function import_products_async() {
            $result = new AjaxResult();
            $errorValidation = false;

            try {
                WP_ConfigurationChecker::checkPhpVersion();
                WP_ConfigurationChecker::checkDbConnection();
            } catch (\Exception $e) {
                $errorValidation = true;
                $result->status = false;
                $result->message = $e->getMessage();
            }

            if (false === $errorValidation) {
                if($this->import_in_progress) {
                    $result->status = false;
                    $result->message = __("The import is in progress", "woocommerce");
                } else {
                    try {
                        $importModel = WP_ImportPropertyModelFactory::createInstanceToSave($_POST);

                        $this->import_repository->save($importModel);

                        ExecManager::exec(ExecManager::PHP_PATH, 'import-exec.php');

                        $result->status = true;
                        $result->message = __("The import is in progress", "woocommerce");
                    } catch (Exception $ex) {
                        $result->status = false;
                        $result->message = $ex->getMessage();
                    }
                }
            }
    
            echo json_encode($result);

            wp_die();
        }
        
        public function getRegionOptions() {
            $options = [];

            foreach(Client::getInstance()->getRegions() as $region) {
                /** @var $region CodesWholesale\Resource\V2\Region */
                $options[$region->getName()] = $region->getName();
            }

            return $options;
        }
        
        public function getPlatformOptions() {
            $options = [];

            foreach(Client::getInstance()->getPlatforms() as $platform) {
                /** @var $platform CodesWholesale\Resource\V2\Platform */
                $options[$platform->getName()] = $platform->getName();
            }
            
            return $options;
        }
        
        public function getLanguageOptions() {
            $options = [];

            foreach(Client::getInstance()->getLanguages() as $language) {
                /** @var $language CodesWholesale\Resource\V2\Language */
                $options[$language->getName()] = $language->getName();
            }

            return $options;  
        }
    }

    // @todo create global object in vendor
    class AjaxResult {
        public $status;
        public $message;
    }
        
endif;

return new CW_Controller_Import_products();