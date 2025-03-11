<div class="main_app_trans">
	<h3 class="h3_div">Importación masiva</h3>

	<p class="italic">Descarga archivos Excel de ejemplo para <b>importar:</b> 
		<a href="{$uri_categories_import}">categorías</a>, 
		<a href="{$uri_features_import}">caracteristicas</a>,  
		<a href="{$uri_brands_import}">marcas</a> y 
		<a href="{$uri_products_import}">productos</a>
	</p>

	<h4>Archivos de actualización</h4>
	<p class="italic">Si deseas <b>actualizar</b> todo tu catalogo actual debes descargar los archivos con la información correspondiente en las siguientes hojas de datos en excel:
	</p>
	<ul>
		<li>
			<a href="{$uri_categories_update}">Categorias</a>
		</li>
		<li>
			<a href="{$uri_features_update}">Caracteristicas</a>
		</li>
		<li>
			<a href="{$uri_brands_update}">Marcas</a>
		</li>
		<li>
			<a href="{$uri_products_update}">Productos</a>
		</li>
	</ul>

	<form method="get">
		<button class="button button-secondary loading_full_screen_enable" name="uploadFileFrom" value="true">
			<i class="material-icons">upload</i>
			Cargar archivo de datos
		</button>
	</form>
</div>