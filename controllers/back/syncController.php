<?php 
/**
 * @author Bernardo Fuentes
 * @since 19/03/2024
 */

require_once(__DOCUMENT_ROOT__."/libs/simpleXLSX/simpleXLSX.php");
require_once(__DOCUMENT_ROOT__."/libs/simpleXLSXGen/simpleXLSXGen.php");
require_once(__DIR_MODULES__."itivos_importproducts/classes/itivos_import_products_file_upload_task.php");
require_once(__DIR_MODULES__."itivos_importproducts/classes/itivos_importproductsc.php");


use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

class syncController extends ModulesBackControllers
{
    function __construct()
    {
        $this->is_logged = true;
        $this->type_controller = "backend";
        $this->ajax_anabled = true;
        parent::__construct();
        $this->view->assign('page', $this->l("Importación masiva"));
    }
    public function index()
    {
        if (isIsset('uploadFileFrom')) {
            if (isIsset('type_file')) {
                self::protectProcessForm();
            }
            self::protectForm();
            die();
        }else {
            $uri_download = __URI__.__ADMIN__."/module/".$this->name."/sync/download_example?type=";
            $this->view->assign(
                array(
                    "uri_categories_import" => $uri_download."categories_import",
                    "uri_products_import" => $uri_download."products_import",
                    "uri_features_import" => $uri_download."features_import",

                    "uri_categories_update" => $uri_download."categories_update",
                    "uri_products_update" => $uri_download."products_update",
                    "uri_features_update" => $uri_download."features_update",
                )
            );
            $this->html = $this->view->fetch($this->template_dir. "header_sync.tpl");
        }
        $this->renderHtml("back");
    }
    public function protectForm($data = null)
    {
        $this->html = 
        "
        <div class='menu_app'>
            <nav>
                <ul>
                    <li>
                        <a href='".__URI__.__ADMIN__."/module/itivos_importproducts/sync'>
                           <i class='material-icons'>arrow_left</i>
                            Volver atras
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        ";
        $file_types = array(
            array(
                "type" => "categories", 
                "name" => "Categorias", 
            ),
            array(
                "type" => "products", 
                "name" => "Productos", 
            ),
            array(
                "type" => "features", 
                "name" => "Caracteristicas", 
            ),
        );
        $type_import = array(
            array(
                "type_import" => "update", 
                "name" => "Actualizar", 
            ),
            array(
                "type_import" => "insert", 
                "name" => "Agregar", 
            ),
        );
        $this->form = array(
            "form" => array(
                "type" => "inline",
                "method" => "POST",
                "legend" => array(
                    "title" => "Cargar archivo"
                ),
                "extends" => "back",
                "inputs" => array(
                    array(
                        "type" => "file",
                        "label" => "Archivo",
                        "files_type" => array("excel"),
                        "required" => true,
                        "name" => "name",
                    ),
                    array(
                        "type" => "select",
                        "label" => "Tipo de archivo",
                        "name" => "type_file",
                        "required" => true,
                        "options" => array(
                            "query" => $file_types,
                            "id" => "type",
                            "name" => "name",
                        )
                    ),
                    array(
                        "type" => "text",
                        "label" => "Email",
                        "name" => "email_notification",
                    ),
                    array(
                        "type" => "switch",
                        "name" => "delete_current_images",
                        "label" => "Reemplazar imagenes",
                        "desc" => "Si se activa esta opción todas las imagenes se eliminarán",
                        "values" => array(
                            array(
                                "id" => "active_off",
                                "value" => "no",
                                "label" => "No"
                            ),
                            array(
                                "id" => "active_on",
                                "value" => "yes",
                                "label" => "Si"
                            )
                        ),
                    ),
                ),
                "submit" => array(
                    "title" => "Cargar",
                    "action" => "submit"
                ),
                "values" => $data
            )
        );
        $this->renderForm();
    }
    public function protectProcessForm()
    {
        $upload = uploadFile($_FILES['name']);
        $total = "";
        if ($upload['error'] == false) {
            if ( $xlsx = SimpleXLSX::parse(__DOCUMENT_ROOT__."/".$upload['url']) ) {
                $rows = $xlsx->rows();
                $total = count($rows) - 1;
            }else {
                $_SESSION['type_message'] = "danger";
                $_SESSION['message'] = "No se pudo leer el archivo excel ó no cumple con la estructura";
                if(isset($_SERVER['HTTP_REFERER'])) {
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                } else {
                    header("Location: ".__URI__.__ADMIN__."/module/itivos_importproducts/sync");
                }
            }
            $file_upload_task_obj = new itivosImportProductsFileUploadTask();
            $file_upload_task_obj->uri_file = $upload['url'];
            $file_upload_task_obj->next = 1;
            $file_upload_task_obj->total = $total;
            $file_upload_task_obj->send_notification = "yes";
            $file_upload_task_obj->type_file = $_POST['type_file'];
            $file_upload_task_obj->email_notification = $_POST['email_notification'];
            if (isset($_POST['delete_current_images'])) {
                $file_upload_task_obj->delete_current_images = $_POST['delete_current_images'];
            }else {
                $file_upload_task_obj->delete_current_images = "no";
            }
            $file_upload_task_obj->save();

            $_SESSION['type_message'] = "success";
            $_SESSION['message'] = "Será informado por correo electronicó cuando se procese el archivo";
            header("Location: ".__URI__.__ADMIN__."/module/itivos_importproducts/sync");
        }else {
            $_SESSION['type_message'] = "danger";
            $_SESSION['message'] = "No se pudo cargar el archivo excel";
            if(isset($_SERVER['HTTP_REFERER'])) {
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            } else {
                header("Location: ".__URI__.__ADMIN__."/module/itivos_importproducts/sync");
            }
        }
    }
    public function download_example()
    {
        self::protectDownloadExample(getValue("type"));
    }
    public function protectDownloadExample($type)
    {
        switch ($type) {
            case 'categories_import':
                $row_header = array();
                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "name_for_" . $lang['iso_code'] ; 
                    if ($lang['iso_code'] == "en") {
                        $row_header[1][] = "Example name for " . $lang['label'] ;
                    }elseif ($lang['iso_code'] == "es") {
                        $row_header[1][] = "Nombre de ejemplo para " . $lang['label'] ; 
                    }else {
                        $row_header[1][] = "Example name for " . $lang['label'] ;
                    }
                }
                $file = array(
                    $row_header[0],
                    $row_header[1],
                );
                $xlsx = Shuchkin\SimpleXLSXGen::fromArray($file);
                $xlsx->downloadAs('import_categories.xlsx');

                break;
            case 'features_import':
                $row_header = array();
                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "name_for_" . $lang['iso_code'] ; 
                    if ($lang['iso_code'] == "en") {
                        $row_header[1][] = "Example name for " . $lang['label'] ;
                    }elseif ($lang['iso_code'] == "es") {
                        $row_header[1][] = "Nombre de ejemplo para " . $lang['label'] ; 
                    }else {
                        $row_header[1][] = "Example name for " . $lang['label'] ;
                    }
                }
                $file = array(
                    $row_header[0],
                    $row_header[1],
                );
                $xlsx = Shuchkin\SimpleXLSXGen::fromArray($file);
                $xlsx->downloadAs('import_features.xlsx');

            break;
            case 'products_import':
                $row_header = array();
                $row_header[0][] = "type_product";
                $row_header[1][] = "simple";

                $row_header[0][] = "is_virtual_product";
                $row_header[1][] = "no";
                $row_header[0][] = "sku";
                $row_header[0][] = "category_id";
                $row_header[1][] = "1";
                $row_header[1][] = "ejemplo_sku";


                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "name_for_" . $lang['iso_code'] ; 
                    if ($lang['iso_code'] == "en") {
                        $row_header[1][] = "Example name for " . $lang['label'] ;
                    }elseif ($lang['iso_code'] == "es") {
                        $row_header[1][] = "Nombre de ejemplo para " . $lang['label'] ; 
                    }else {
                        $row_header[1][] = "Example name for " . $lang['label'] ;
                    }
                }

                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "short_description_" . $lang['iso_code'] ; 
                    if ($lang['iso_code'] == "en") {
                        $row_header[1][] = ".- List 1 .- list 2 .- list 3" ;
                    }elseif ($lang['iso_code'] == "es") {
                        $row_header[1][] = ".- Lista 1 .- lista 2 .- lista 3" ; 
                    }else {
                        $row_header[1][] = ".- List 1 .- list 2 .- list 3";
                    }
                }

                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "description_" . $lang['iso_code'] ; 
                    if ($lang['iso_code'] == "en") {
                        $row_header[1][] = "describe your product in plain text, do not use html information, everything will be converted to an html element within a paragraph." ;
                    }elseif ($lang['iso_code'] == "es") {
                        $row_header[1][] = "describe tu producto en texto plano, no usar informacion html, todo se convertirá a un elemento html dentro de un parrafo" ; 
                    }else {
                        $row_header[1][] = "describe your product in plain text, do not use html information, everything will be converted to an html element within a paragraph.";
                    }
                }


                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "featureID_example name1_" . $lang['iso_code'] ; 
                    if ($lang['iso_code'] == "en") {
                        $row_header[1][] = "value for feature 1" ;
                    }elseif ($lang['iso_code'] == "es") {
                        $row_header[1][] = "valor para la caracteristica 1" ; 
                    }else {
                        $row_header[1][] = "value for feature 1";
                    }
                }

                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "featureID_example name2_" . $lang['iso_code'] ; 
                    if ($lang['iso_code'] == "en") {
                        $row_header[1][] = "value for feature 2" ;
                    }elseif ($lang['iso_code'] == "es") {
                        $row_header[1][] = "valor para la caracteristica 2" ; 
                    }else {
                        $row_header[1][] = "value for feature 2";
                    }
                }
                /*
                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "attributes_1-" . $lang['iso_code'] ; 
                    if ($lang['iso_code'] == "en") {
                        $row_header[1][] = "value for attributes 1" ;
                    }elseif ($lang['iso_code'] == "es") {
                        $row_header[1][] = "valor para la atributo 1" ; 
                    }else {
                        $row_header[1][] = "value for attributes 1";
                    }
                }
                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "attributes_2-" . $lang['iso_code'] ; 
                    if ($lang['iso_code'] == "en") {
                        $row_header[1][] = "value for attributes 2" ;
                    }elseif ($lang['iso_code'] == "es") {
                        $row_header[1][] = "valor para la atributo 2" ; 
                    }else {
                        $row_header[1][] = "value for attributes 2";
                    }
                }
                */

                $row_header[0][] = "images";
                $row_header[1][] = "https://itivos.com/image/product_example1.jpg, https://itivos.com/image/product_example2.png, https://itivos.com/image/product_example3.png";

                $file = array(
                    $row_header[0],
                    $row_header[1],
                );
                $xlsx = Shuchkin\SimpleXLSXGen::fromArray($file);
                $xlsx->downloadAs('import_products.xlsx');

                break;
            case 'categories_update':
                $row_header = array();
                $row_header[0][] = 'id_category';
                $iso_codes = array();
                $rows = array();
                foreach (language::getLangs() as $key => $lang) {
                    array_push($iso_codes, $lang['iso_code']);
                    $row_header[0][] = "name_for_" . $lang['iso_code'] ;
                }
                $categories = itivosImportproductsC::getCategoriesListByIsoCode($iso_codes);
                $data = array_merge($row_header, $categories);
                $xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);
                $xlsx->downloadAs('update_categories.xlsx');
                break;
            case 'features_update':
                $row_header = array();
                $row_header[0][] = 'id_feature';
                $iso_codes = array();
                $rows = array();
                foreach (language::getLangs() as $key => $lang) {
                    array_push($iso_codes, $lang['iso_code']);
                    $row_header[0][] = "name_for_" . $lang['iso_code'] ;
                }
                $features = itivosImportproductsC::getFeaturesListByIsoCode($iso_codes);
                $data = array_merge($row_header, $features);
                $xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);
                $xlsx->downloadAs('update_features.xlsx');
            break;
            case 'products_update':
                $iso_codes = array();

                $row_header[0][] = "id_product";
                $row_header[0][] = "type_product";

                $row_header[0][] = "is_virtual_product";
                $row_header[0][] = "sku";
                $row_header[0][] = "category_id";

                foreach (language::getLangs() as $key => $lang) {
                    array_push($iso_codes, $lang['iso_code']);
                    $row_header[0][] = "name_for_" . $lang['iso_code'] ; 
                }

                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "short_description_" . $lang['iso_code'] ; 
                }

                foreach (language::getLangs() as $key => $lang) {
                    $row_header[0][] = "description_" . $lang['iso_code'] ; 
                }
                $headers_features = itivosImportproductsC::getProductFeatureHeaders($iso_codes);
                foreach ($headers_features as $key => $feature) {
                    $row_header[0][] = $feature;
                }
                $products = itivosImportproductsC::getProductInfoBaseByIsoCode($iso_codes);
                $data = array_merge($row_header, $products);
                $xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);
                $xlsx->downloadAs('update_products.xlsx');
            break;
            default:
                break;
        }
    }
}