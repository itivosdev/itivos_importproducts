<?php
/**
 * @author Bernardo Fuentes
 * @since 19/03/2024
 */

class itivosImportProducts extends Modules
{   
    public $html = "";
    public function __construct()
    {
        $this->name ='itivos_importproducts';
        $this->displayName = $this->l('Import products');
        $this->description = $this->l('Importar desde excel catalogos, ficha tecnica y categorías');
        $this->category  ='administration';
        $this->version ='1.0.0';
        $this->author ='Bernardo Fuentes';
        $this->versions_compliancy = array('min'=>'1.43', 'max'=> __SYSTEM_VERSION__);
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');
        $this->template_dir = __DIR_MODULES__."itivos_importproducts/views/back/";
        $this->template_dir_front = __DIR_MODULES__."itivos_importproducts/views/front/";
        parent::__construct();
        $this->key_module = "ca6e384ab0dc15184366c75b75c04e57";
        $this->crontLink = __URI__.__ADMIN__."/module/".$this->name."/crontab?key=".$this->key_module."";
        $this->assetsFilesPath = $_SERVER['DOCUMENT_ROOT']."/modules/itivos_importproducts/libs/";
        $this->filePath = __DOCUMENT_ROOT__."/views/themes/".configuration::getValue('front_theme')."/pages/_partials/";
        $this->baseDirTheme = __DOCUMENT_ROOT__."/views/themes/".configuration::getValue('front_theme')."/";
        $this->overridesDir = __DOCUMENT_ROOT__."/overrides/modules/itivos_cart/";
        $this->filePathCart = $this->baseDirTheme."modules/itivos_cart/";
    }
    public function install()
    {
         if(!$this->registerHook("displayHead") ||
            !$this->registerHook("displayBottom") ||
            !$this->registerHook("actionImportCategories") ||
            !$this->registerHook("actionImportFeatures") ||
            !$this->registerHook("actionImportProducts") ||
            !$this->installTab("itivosImportProducts", "Importación masiva", "itivosImportProducts", "link", "sync", "publish") ||
            !$this->installDB() ||
            !$this->defaultData() 
            ){
            return false;
        }
        return true;
    }
    public function uninstallConfig()
    {
        $return = true;
        $return &= connect::execute("DELETE FROM ".__DB_PREFIX__. "configuration WHERE module = '".$this->name."'");
        return $return;
    }
    public function uninstall($drop = true)
    {
        $return = true;
        if ($drop == true) {
            $return &= connect::execute("DROP TABLE IF EXISTS ".__DB_PREFIX__. "itivos_import_products_file_upload_task");
        }
        if(!$this->uninstallConfig() ||
           !$this->uninstallTab("itivosImportProducts")){
            return false;
        }
        return true;
    }
    public function defaultData()
    {
        $return = true;
        $return &= Configuration::updateValue(
            "itivos_importproducts_max_product", 
            "500",
            "itivos_import_products"
        );
        $return &= Configuration::updateValue(
            "itivos_importproducts_next_row", 
            "500",
            "itivos_import_products"
        );
        $return &= Configuration::updateValue(
            "itivos_importproducts_next_product", 
            "500",
            "itivos_import_products"
        );
        
        return $return;
    }
    public function installDB()
    {
        $return = true;
        $return &= connect::execute('
            CREATE TABLE IF NOT EXISTS `'.__DB_PREFIX__.'itivos_import_products_file_upload_task` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `uri_file` longtext NOT NULL,
              `type_file` set("categories", "products", "features") NOT NULL DEFAULT "categories",
              `type_import` set("insert", "update") NOT NULL DEFAULT "insert",
              `next` INT(11) NOT NULL,
              `total` INT(11) NOT NULL,
              `send_notification` set("yes", "no") NOT NULL DEFAULT "no",
              `email_notification` varchar(150) NULL,
              `delete_current_images` set("yes", "no") NOT NULL DEFAULT "no",
              `status` set("pending", "processing", "completed") NOT NULL DEFAULT "pending",
              PRIMARY KEY (id)
            ) ENGINE ='.__MYSQL_ENGINE__.' DEFAULT CHARSET=utf8 ;'
        );
        return $return;
    }
    public function getConfig()
    {
        if (isIsset('submit_action')) {
            Configuration::updateValue("itivos_importproducts_max_product", 
                                       getValue("itivos_importproducts_max_product"),
                                       'itivos_import_products');
            $_SESSION['message'] = $this->l("configuración actualizada correctamente");
            $_SESSION['type_message'] = "success";
            header("Location: ".__URI__.__ADMIN__."/modules/config/".$this->name."");
        }
        $helper = new HelperForm();
        $helper->tpl_vars = array(
            'fields_values' => array(
                "itivos_importproducts_max_product" =>  Configuration::getValue(
                    "itivos_importproducts_max_product")
            ),
            'languages' => language::getLangs($this->lang),
        );
        $helper->submit_action = "updateAction";
        return $this->html = $helper->renderForm(self::generateForm());
    }
    public function generateForm()
    {
        $form = array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('configuración'),
                        'icon' => 'icon-cogs',
                    ),
                    'inputs' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('Cantidad maxima de productos a procesar por bucle.'),
                            'name' => 'itivos_importproducts_max_product',
                            'required' => true,
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Guardar configuración'),
                    ),
                ),
            );
        return $form;
    }
    public function saveCategories($params)
    {
        $max = Configuration::getValue("itivos_importproducts_max_product");
       // if ( (int) $params['total'] < $max) {
            $l = array();
            $has_id_header = false;
            $has_image_header = false;
            $key_image = 0; 
            $key_status = 0; 
            foreach ($params['data'] as $key => $row) {
                if ($key == 0) {
                    foreach ($row as $key_2 => $val) {
                        if (str_contains($val, "name_for_")){
                            $lang = str_replace("name_for_", "", $val);
                            $id_lang = language::getIdLang($lang);
                            $l[$key_2]['iso_code'] = $lang;
                            $l[$key_2]['id_lang'] = $id_lang;
                        }
                    }
                    foreach ($params['data'][0] as $key_header => $header) {
                        if ($header == "image") {
                            $has_image_header = true;
                            $key_image = $key_header;
                        }
                        if ($header == "status") {
                            $key_status = $key_header;
                        }
                        if ($header == "id_category") {
                            $has_id_header = true;
                        }
                    }
                }else {
                    $category_obj = New Categories();
                    $category_obj->status = "disabled";
                    if ($has_id_header) {
                        $category_obj = New Categories($row[0]);
                    }
                    $langs = array();
                    foreach ($l as $key_lang => $la) {
                        $langs[$la['id_lang']]['id_lang'] = $la['id_lang'];
                        $langs[$la['id_lang']]['name'] = strtolower($row[$key_lang]);
                    }
                    if ($has_image_header) {
                        /**
                         * Save the imagen local, after converter
                         * Pending save 
                         */
                        $category_obj->image = imagenCreateWebp($row[$key_image]);
                    }else {
                        unset($category_obj->image);
                    }
                    if ($key_status != 0) {
                        $category_obj->status = $row[$key_status];
                    }
                    $category_obj->langs = $langs;
                    $category_obj->save();
                }
            }
            itivosImportProductsFileUploadTask::setStatus($params['id'], "completed");
        //}
    }
    public function saveFeatures($params)
    {
        $l = array();
        $has_id_header = false;
        $key_status = 0; 
        foreach ($params['data'] as $key => $row) {
            if ($key == 0) {
                foreach ($row as $key_2 => $val) {
                    if (str_contains($val, "name_for_")){
                        $lang = str_replace("name_for_", "", $val);
                        $id_lang = language::getIdLang($lang);
                        $l[$key_2]['iso_code'] = $lang;
                        $l[$key_2]['id_lang'] = $id_lang;
                    }
                }
                foreach ($params['data'][0] as $key_header => $header) {
                    if ($header == "status") {
                        $key_status = $key_header;
                    }
                    if ($header == "id_feature") {
                        $has_id_header = true;
                    }
                }
            }else {
                $feature_obj = New Features();
                $feature_obj->status = "disabled";
                if ($has_id_header) {
                    $feature_obj = New Features($row[0]);
                }
                $langs = array();
                foreach ($l as $key_lang => $la) {
                    $langs[$la['id_lang']]['id_lang'] = $la['id_lang'];
                    $langs[$la['id_lang']]['name'] = strtolower($row[$key_lang]);
                }
                if ($key_status != 0) {
                    $feature_obj->status = $row[$key_status];
                }
                $feature_obj->langs = $langs;
                $feature_obj->save();
            }
        }
        itivosImportProductsFileUploadTask::setStatus($params['id'], "completed");
    }
    public function saveProducts($params)
    {
        $l_names = array();
        $iso_codes = array();
        $l_short_descriptions = array();
        $l_descriptions = array();
        $has_id_header = false;
        $key_status = 0; 
        $key_type = 0;
        $key_sku = 0;
        $key_is_virtual = 0;
        $key_id_category = 0;
        $key_images = 0;
        $key_price = 0;
        $key_price_drop = 0;
        $features_keys = array();
        $images_key = 0;
        foreach ($params['data'] as $key => $row) {
            $deleted_images = false;
            if ($key == 0) {
                foreach ($row as $key_column => $val) {
                    if (str_contains($val, "name_for_")){
                        $lang = str_replace("name_for_", "", $val);
                        $id_lang = language::getIdLang($lang);
                        $l_names[$key_column]['iso_code'] = $lang;
                        $l_names[$key_column]['id_lang'] = $id_lang;
                        array_push($iso_codes, array('id_lang' => $id_lang, "iso_code" => $lang ));
                    }
                    if (str_contains($val, "short_description_")){
                        $lang = str_replace("short_description_", "", $val);
                        $id_lang = language::getIdLang($lang);
                        $l_short_descriptions[$key_column]['iso_code'] = $lang;
                        $l_short_descriptions[$key_column]['id_lang'] = $id_lang;
                    }
                    if (str_contains($val, "description_")){
                        if (!str_contains($val, "short")) {
                            $lang = str_replace("description_", "", $val);
                            $id_lang = language::getIdLang($lang);
                            $l_descriptions[$key_column]['iso_code'] = $lang;
                            $l_descriptions[$key_column]['id_lang'] = $id_lang;
                        }
                    }
                    if (str_contains($val, "category_id")){
                        $key_id_category = $key_column;
                    }
                    if (str_contains($val, "type_product")){
                        $key_type = $key_column;
                    }
                    if (str_contains($val, "sku")){
                        $key_sku = $key_column;
                    }
                    if (str_contains($val, "is_virtual")){
                        $key_is_virtual = $key_column;
                    }
                    if (str_contains($val, "price")){
                        $key_price = $key_column;
                    }
                    if (str_contains($val, "price_drop")){
                        $key_price_drop = $key_column;
                    }
                    if (str_contains($val, "id_product")){
                        $has_id_header = true;
                    }
                    if (str_contains($val, "feature")){
                        array_push($features_keys, $key_column);
                    }
                    if (str_contains($val, "images")){
                        $key_images = $key_column;
                    }
                }
            }else {
                $product_obj = New Products();
                $product_obj->id_attribute_value = "0";
                /*
                $product_obj->id_product_parent = "0";
                */
                $product_obj->status = "disabled";
                $product_obj->type = $row[$key_type];
                $product_obj->price = "1";
                if ($has_id_header) {
                    $product_obj = New Products( (int) $row[0]);
                }
                $names_lang = array();
                foreach ($l_names as $key_lang => $la_name) {
                    $names_lang[$la_name['id_lang']]['id_lang'] = $la_name['id_lang'];
                    if ($has_id_header) {
                        $names_lang[$la_name['id_lang']]['id_product'] = $product_obj->id_product;
                    }
                    $names_lang[$la_name['id_lang']]['name'] = base64_encode(strtolower($row[$key_lang]));
                }
                foreach ($l_short_descriptions as $key_lang => $l_short_description) {
                    $array_list = explode(".-", $row[$key_lang]);
                    $short_description = "";
                    if (!empty($array_list)) {
                        $short_description .="<ul>";
                        foreach ($array_list as $key => $list) {
                            if (!empty($list)) {
                                $short_description .= "<li>{$list}</li>";
                            }
                        }
                        $short_description .="</ul>";
                    }
                    $names_lang[$l_short_description['id_lang']]['short_description'] = base64_encode($short_description);
                }
                foreach ($l_descriptions as $key_lang => $l_description) {
                    $description = '<p style="text-align:justify">';
                    $description .= "{$row[$key_lang]}</p>";
                    $names_lang[$l_description['id_lang']]['description'] = base64_encode($description);
                }
                if ($key_status != 0) {
                    $product_obj->status = $row[$key_status];
                }
                if ($key_price != 0) {
                    $product_obj->price = $row[$key_price];
                }
                if ($key_price_drop != 0) {
                    $product_obj->price_drop = $row[$key_price_drip];
                }
                if (!empty($names_lang)) {
                    $product_obj->names = $names_lang;
                }
                $features = array();
                foreach ($iso_codes as $key => $lang) {
                    if (!array_key_exists($lang['id_lang'], $features)) {
                        $features[$lang['id_lang']] = array();
                    }
                    foreach ($features_keys as $key => $feature_key) {
                        if (str_contains($params['data'][0][$feature_key], $lang['iso_code'])) {
                            $titles_feature_array = explode("_", $params['data'][0][$feature_key]);
                            $id_feature = (int) str_replace("feature", "", $titles_feature_array[0]);
                            if (!empty($row[$feature_key])) {
                                $features[$lang['id_lang']][$id_feature] = $row[$feature_key];
                            }else {
                                Products::delFeature($product_obj->id_product, $id_feature);
                            }
                        }
                    }
                }
                if (!empty($features)) {
                    $product_obj->features = $features;
                }
                $product_obj->type = $row[$key_type];
                $product_obj->is_virtual = $row[$key_is_virtual];
                $product_obj->sku = $row[$key_sku];
                $product_obj->category = $row[$key_id_category];
                $product_obj->save();
                if ($params['delete_current_images'] == "yes") {
                    if (!$deleted_images) {
                        self::deleteProductImages($product_obj->id_product);
                        $deleted_images = true;
                    }
                }
                if ($key_images != 0) {
                    $images = explode(",", $row[$key_images]);
                    self::createProductsImages($params['id'], $product_obj->id_product, $images);
                }
                /**
                 *  We need a input for deleted or not the current images
                **/
            }
        }
        itivosImportProductsFileUploadTask::setStatus($params['id'], "completed");
    }
    public function createProductsImages($id_feed, $id_product, $images)
    {
        $return = true;
        foreach ($images as $key => $image) {
            if (!empty($image)) {
                $return &= self::createProductImg($id_feed, $id_product, $image);
            }
        }
        $images = read_directory_img($id_product."/", null, true, true);
    }
    public function createProductImg($id_feed, $id_product, $image_link)
    {
        $return = false;
        $logFilePath = __DIR__ . "/{$id_feed}";
        $log = function ($message) use ($logFilePath) {
            file_put_contents($logFilePath, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
        };
        
        try {
            $headers = @get_headers($image_link);
            if ($headers && strpos($headers[0], '200') !== false) {
                $image = file_get_contents($image_link);
                if ($image !== false) {
                    $img_data = 'data:image/png;base64,'.base64_encode($image);
                    $return = true;
                    $dir = create_dir($id_product);
                    $exploded = explode(',', $img_data, 2);
                    $encoded = $exploded[1]; 
                    $name = md5(time().rand());

                    // Crear la imagen a partir del string base64
                    $image_resource = imagecreatefromstring(base64_decode($encoded));

                    // Obtener dimensiones de la imagen
                    $image_width = imagesx($image_resource);
                    $image_height = imagesy($image_resource);

                    // Crear un lienzo con fondo blanco del mismo tamaño que la imagen
                    $canvas = imagecreatetruecolor($image_width, $image_height);
                    $white = imagecolorallocate($canvas, 255, 255, 255);
                    imagefilledrectangle($canvas, 0, 0, $image_width, $image_height, $white);

                    // Superponer la imagen en el lienzo con fondo blanco
                    imagecopy($canvas, $image_resource, 0, 0, 0, 0, $image_width, $image_height);

                    // Guardar la imagen con fondo blanco
                    $output_path = __DOCUMENT_ROOT__."/img_upload/".$id_product."/".$name.".png";
                    $success = imagepng($canvas, $output_path);

                    // Liberar memoria
                    imagedestroy($image_resource);
                    imagedestroy($canvas);

                    if ($success) {
                        create_thumbails(
                            $output_path, 
                            __DOCUMENT_ROOT__."/img_upload/".$id_product."/", 
                            $id_product 
                        );
                    } else {
                        $log("No se pudo guardar la imagen con fondo blanco para el producto {$id_product}");
                    }
                } else {
                    $log("No se pudo obtener la imagen de la URL: {$image_link} para el producto {$id_product}");
                }
            }
        } catch (Exception $e) {
            $log("Error al intentar obtener la imagen de la URL: {$image_link} para el producto {$id_product}. Error: " . $e->getMessage());
        }
        return $return;
    }
    public function deleteProductImages($id_product)
    {
        $return = true;
        $images = read_directory_img($id_product."/", null, true, true);
        if (isset($images['original'])) {
            foreach ($images['original'] as $key => $image) {
                $src = str_replace("product_img", __DIR_UPLOAD__, $image);
                $return &= delete_file($src, true);
            }
        }
        return $return;
    }
    public function hookActionImportCategories($params)
    {
        if (!isset($params['type_import'])) {
            return false;
        }
        self::saveCategories($params);
    }
    public function hookActionImportFeatures($params)
    {
        if (!isset($params['type_import'])) {
            return false;
        }
        self::saveFeatures($params);
    }
    public function hookActionImportProducts($params)
    {
        if (!isset($params['type_import'])) {
            return false;
        }
        self::saveProducts($params);
    }
}