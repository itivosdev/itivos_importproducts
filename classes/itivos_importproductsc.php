<?php
/**
 * @author Bernardo Fuentes
 * @since 25/03/2024
 */
class itivos_importproductsc extends Model
{
	public static function getCategoriesListByIsoCode($iso_codes)
	{
	    // Convert ISO language codes into language identifiers
	    $id_langs = array();
	    foreach ($iso_codes as $iso_code) {
	        $id_langs[] = language::getIdLang($iso_code);
	    }

	    // Build the query part for language conditions
	    $lang_conditions = array();
	    foreach ($id_langs as $id_lang) {
	        $lang_conditions[] = "lc.id_lang = " . (int)$id_lang;
	    }
	    $lang_conditions_str = implode(" OR ", $lang_conditions);

	    // Build the final query
	    $query = "SELECT c.id";
	    foreach ($iso_codes as $iso_code) {
	        $query .= ", MAX(CASE WHEN l.iso_code = '$iso_code' THEN lc.name END) AS name_for_$iso_code";
	    }
	    $query .= "
	              FROM " . __DB_PREFIX__ . "categories c
	              LEFT JOIN " . __DB_PREFIX__ . "lang_category lc ON lc.id_category = c.id
	              LEFT JOIN " . __DB_PREFIX__ . "languages l ON lc.id_lang = l.id
	              WHERE $lang_conditions_str
	              GROUP BY c.id 
	              ORDER BY c.id asc";

	    // Execute the query and return the results
		$result = connect::execute($query, "select");

		 // Convert each subarray to numeric array
	    $numeric_result = array();
	    foreach ($result as $subarray) {
	        $numeric_result[] = array_values($subarray);
	    }

	    return $numeric_result;
	}
	public static function getFeaturesListByIsoCode($iso_codes)
	{
	    // Convert ISO language codes into language identifiers
	    $id_langs = array();
	    foreach ($iso_codes as $iso_code) {
	        $id_langs[] = language::getIdLang($iso_code);
	    }

	    // Build the query part for language conditions
	    $lang_conditions = array();
	    foreach ($id_langs as $id_lang) {
	        $lang_conditions[] = "fl.id_lang = " . (int)$id_lang;
	    }
	    $lang_conditions_str = implode(" OR ", $lang_conditions);

	    // Build the final query
	    $query = "SELECT f.id";
	    foreach ($iso_codes as $iso_code) {
	        $query .= ", MAX(CASE WHEN l.iso_code = '$iso_code' THEN fl.name END) AS name_for_$iso_code";
	    }
	    $query .= "
	              FROM " . __DB_PREFIX__ . "features f
	              LEFT JOIN " . __DB_PREFIX__ . "lang_feature fl ON fl.id_feature = f.id
	              LEFT JOIN " . __DB_PREFIX__ . "languages l ON fl.id_lang = l.id
	              WHERE $lang_conditions_str
	              GROUP BY f.id 
	              ORDER BY f.id asc";
	    // Execute the query and return the results
		$result = connect::execute($query, "select");

		 // Convert each subarray to numeric array
	    $numeric_result = array();
	    foreach ($result as $subarray) {
	        $numeric_result[] = array_values($subarray);
	    }

	    return $numeric_result;
	}
	public static function getFeaturesListIs()
	{
		$query = "SELECT f.id 
					FROM ".__DB_PREFIX__."features f 
					WHERE f.status != 'deleted'
				  ";
		return connect::execute($query, "select");
	}
	public static function getProductFeatureHeaders($iso_codes)
	{
	    $feature_ids = self::getFeaturesListIs();
	    $headers = array();

	    foreach ($feature_ids as $feature_id) {
	        foreach ($iso_codes as $iso_code) {
	            // Consulta para obtener el nombre de la característica
	            $feature_name_query = "SELECT name FROM ".__DB_PREFIX__."lang_feature WHERE id_feature = {$feature_id['id']} AND id_lang = " . language::getIdLang($iso_code);
	            $feature_name = connect::execute($feature_name_query, "select", true);

	            // Construir el encabezado para la característica
	            if ($feature_name['name']) {
	                $header = "feature{$feature_id['id']}_{$feature_name['name']}_$iso_code";
	            } else {
	                $header = "feature{$feature_id['id']}_$iso_code";
	            }

	            // Agregar el encabezado al array de encabezados
	            $headers[] = $header;
	        }
	    }

	    return $headers;
	}
	public static function getProductInfoBaseByIsoCode($iso_codes)
	{
	    $feature_ids = self::getFeaturesListIs();

	    // Convert ISO language codes into language identifiers
	    $id_langs = array();
	    foreach ($iso_codes as $iso_code) {
	        $id_langs[] = language::getIdLang($iso_code);
	    }

	    // Build the query part for language conditions
	    $lang_conditions = array();
	    foreach ($id_langs as $id_lang) {
	        $lang_conditions[] = "lp.id_lang = " . (int)$id_lang;
	    }
	    $lang_conditions_str = implode(" OR ", $lang_conditions);

	    // Build the final query
	    $query = "SELECT p.id, p.type, p.is_virtual, p.sku, p.status, p.category";

	    // Add columns for names, descriptions, etc.
	    foreach ($iso_codes as $iso_code) {
	        $query .= ", MAX(CASE WHEN l.iso_code = '$iso_code' 
	                                THEN FROM_BASE64(lp.name) 
	                                END) AS name_for_$iso_code";
	    }
	    foreach ($iso_codes as $iso_code) {
	        $query .= ", REPLACE(REPLACE(MAX(CASE WHEN l.iso_code = '$iso_code' 
	                                  THEN REPLACE(REPLACE(FROM_BASE64(lp.short_description), '<ul>', ''), '</ul>', '') 
	                              END), '<li>', '.- '), '</li>', '') AS short_description_for_$iso_code";
	    }
	    foreach ($iso_codes as $iso_code) {
	        $query .= ", MAX(CASE WHEN l.iso_code = '$iso_code' 
	                                THEN FROM_BASE64(lp.description) 
	                                END) AS description_for_$iso_code";
	    }

	    // Add columns for features with names
	    foreach ($feature_ids as $feature_id) {
	        foreach ($iso_codes as $iso_code) {
	            // Consulta para obtener el nombre de la característica
	            $feature_name_query = "SELECT name FROM ".__DB_PREFIX__."lang_feature WHERE id_feature = {$feature_id['id']} AND id_lang = " . language::getIdLang($iso_code);
	            $feature_name = connect::execute($feature_name_query, "select", true);
	            // Si se encuentra el nombre de la característica, agregarlo a la columna
	            if ($feature_name['name']) {
	                $query .= ", MAX(CASE WHEN l.iso_code = '$iso_code' AND fp.id_feature = {$feature_id['id']} 
	                                      THEN REPLACE(REPLACE(fp.value, '<ul>', ''), '</ul>', '') 
	                                      ELSE '' END) AS 'feature{$feature_id['id']}_{$feature_name['name']}_$iso_code'";
	            } else {
	                // Si no se encuentra el nombre de la característica, usar solo el ID de la característica
	                $query .= ", MAX(CASE WHEN l.iso_code = '$iso_code' AND fp.id_feature = {$feature_id['id']} 
	                                      THEN REPLACE(REPLACE(fp.value, '<ul>', ''), '</ul>', '') 
	                                      ELSE '' END) AS feature{$feature_id['id']}_$iso_code";
	            }
	        }
	    }

	    // Complete the query
	    $query .= "
	              FROM " . __DB_PREFIX__ . "products p
	              LEFT JOIN " . __DB_PREFIX__ . "lang_product lp ON lp.id_product = p.id_product AND (p.status_db = 'active')
	              LEFT JOIN " . __DB_PREFIX__ . "languages l ON lp.id_lang = l.id
	              LEFT JOIN " . __DB_PREFIX__ . "feature_product fp ON fp.id_product = p.id_product
	              WHERE $lang_conditions_str 
	              GROUP BY p.id 
	              ORDER BY p.id ASC";

	    // Execute the query and return the results
	    $result = connect::execute($query, "select");
	    // Convert each subarray to numeric array
	    $numeric_result = array();
	    foreach ($result as $subarray) {
	        $numeric_result[] = array_values($subarray);
	    }
	    return $numeric_result;
	}
}
class_alias("itivos_importproductsc", "itivosImportproductsC");