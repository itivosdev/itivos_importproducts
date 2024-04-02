{extends file='admin_views/v1/mails/mail.tpl'}
{if isset($data.title)}
	{block name=title}{$data.title}{/block}
{/if}
{block name=main}
	<p>
		{l s='Hola' mod='theme.front.email'} {$data.customer_name}, 
	    {l s="Te informamos que hemos procesado tu archivo excel." mod='theme.front.email'}
	</p>
	<p>
		<b>Resultado de la importación</b><br>
	</p>
		<ul>
			<li> 
				Cantidad de partidas <b>{$data.products_total}</b>.
			</li>
			<li> 
				Partidas procesadas correctamente <b>{$data.product_process}</b>.
			</li>
			<li> 
				Partidas no procesadas <b>{$data.product_no_process}</b>
			</li>
		</ul>
	{if $data.success eq "completed"}
		<p>
			El archivo se procesó correctamente sin errores
		</p>
		{else}
		<p>
			Se encontraron los siguientes errores en el archivo: 
			{foreach from=$data.results item=$result key=key}
				<br> - {$result}
			{/foreach}
		</p>
	{/if}
	<div class="divider"></div>
	<center>
		<p class="italic">
			{l s="Este correo es generado de forma automática por Itivos Biz, por favor no responda a esta dirección. No se recibirá su respuesta."}
		</p>
	</center>
{/block}