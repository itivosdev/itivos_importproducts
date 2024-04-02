<?php
/**
 * @author Bernardo Fuentes
 * @since 21/03/2024 
 */

class itivos_import_products_file_upload_task extends Model
{
	public $id;
	public $uri_file;
	public $type_file;
	public $type_import;
	public $next;
	public $total;
	public $send_notification;
	public $email_notification;
	public $delete_current_images;
	public $status;

	public static function setStatus($id, $status)
	{
		$query = "UPDATE ".__DB_PREFIX__."itivos_import_products_file_upload_task 
					SET `status`= '".$status."' 
				 WHERE id = ".$id."";
		return connect::execute($query);
	}
	public static function getListByStatus($status)
	{
		$query = "SELECT t.* 
					FROM ".__DB_PREFIX__."itivos_import_products_file_upload_task t
				  WHERE  t.status = '".$status."'
				  ORDER by t.id ASC LIMIT 5
				  ";
		return connect::execute($query, "select");
	}
}
class_alias("itivos_import_products_file_upload_task", "itivosImportProductsFileUploadTask");