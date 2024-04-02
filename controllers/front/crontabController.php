<?php
/**
 * @author Bernardo Fuentes
 * @since 21/03/2024 
 */

set_time_limit(0);
require_once(__DOCUMENT_ROOT__."/libs/simpleXLSX/simpleXLSX.php");
require_once(__DOCUMENT_ROOT__."/libs/simpleXLSXGen/simpleXLSXGen.php");
require_once(__DIR_MODULES__."itivos_importproducts/classes/itivos_import_products_file_upload_task.php");
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

class CrontabController extends ModulesFrontControllers
{
    function __construct()
    {
        $this->is_logged = false;
        $this->ajax_anabled = true;
        $this->type_controller = "frontend";
        parent::__construct();
        $this->view->assign('page', "crontab import files");
        if (!isIsset('token')) {
            die();
        }else {
            $obj = new itivosImportProducts();
            if (getValue('token') != $obj->key_module) {
                die("el token no es valido");
            }
        }
        if (!Modules::isInstalled('itivos_importproducts')) {
            die("Modulo no instalado");
        }
    }
    public function processFile()
    {
        $feeds = itivosImportProductsFileUploadTask::getListByStatus("pending");
        foreach ($feeds as $key => $feed) {
            itivosImportProductsFileUploadTask::setStatus($feed['id'], "processing");
            if ( $xlsx = SimpleXLSX::parse(__DOCUMENT_ROOT__."/".$feed['uri_file']) ) {
                $rows = $xlsx->rows();
                $feed['data'] = $rows;
                switch ($feed['type_file']) {
                    case 'categories':
                        itivos_hook('actionImportCategories', $feed);
                    break;
                    case 'features':
                        itivos_hook('actionImportFeatures', $feed);
                    break;
                    case 'products':
                        itivos_hook('actionImportProducts', $feed);
                    break;
                    default:
                        die("El archivo no es valido");
                    break;
                }
            }else {
                itivosImportProductsFileUploadTask::setStatus($feed['id'], "completed");
                /*
                $mail_customer = "bfuentes@itivos.com";//$info_customer->email;
                $data = array(
                        "customer_name" => $info_customer->firstname ." " .$info_customer->lastname ,
                        "products_total" => $feed['total'],
                        "product_process" => 0,
                        "product_no_process" => 0,
                        "results" => "El archivo cargado no se pudo leer, por favor validar la estructura.",
                        "success" => "no_completed" 
                );
                $mail = new ItivosMailer();
                $mail->sendMail($mail_customer, 
                                "No se pudo importar el archivo", 
                                $data, 
                                "validate_order");
                */
            }
        }
    }
}