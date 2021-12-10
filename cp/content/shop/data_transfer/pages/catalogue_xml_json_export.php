<?php
/**
Страничный скрипт для страницы экспорта каталога в xml
*/
defined('_ASTEXE_') or die('No access');
?>

<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Действия
		</div>
		<div class="panel-body">
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/perenos-dannyx">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/xml.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Функции переноса данных</div>
			</a>
		
		
		
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Выход</div>
			</a>
		</div>
	</div>
</div>





<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Настройки
		</div>
		<div class="panel-body">
			<div class="col-lg-12 text-center"><h3>Настройки формирования данных</h3></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Выгружать текстовое описание товаров
				</label>
				<div class="col-lg-6">
					<input type="checkbox" id="output_products_text" checked="checked" />
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Выгружать изображения товаров
				</label>
				<div class="col-lg-6">
					<input type="checkbox" id="output_products_images" checked="checked" />
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Выгружать наличие и цены магазинов
				</label>
				<div class="col-lg-6">
					<input type="checkbox" id="output_products_suggestions" checked="checked" />
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Магазины
				</label>
				<div class="col-lg-6">
					<select multiple="multiple" id="offices">
						<?php
						$offices_query = $db_link->prepare("SELECT * FROM `shop_offices`");
						$offices_query->execute();
						while( $office = $offices_query->fetch() )
						{
							?>
							<option value="<?php echo $office["id"]; ?>"><?php echo $office["caption"]." (ID ".$office["id"].")"; ?></option>
							<?php
						}
						?>
					</select>
					<script>
						//Делаем из селектора виджет с чекбоками
						$('#offices').multipleSelect({placeholder: "Нажмите для выбора...", width:"100%"});
						
						$("#offices").multipleSelect('checkAll');
					</script>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12"></div>
			
			
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Группа пользователей (для наценок)
				</label>
				<div class="col-lg-6">
					<select id="groups" class="form-control">
						<?php
						$groups_query = $db_link->prepare("SELECT * FROM `groups`");
						$groups_query->execute();
						while( $group = $groups_query->fetch() )
						{
							?>
							<option value="<?php echo $group["id"]; ?>"><?php echo $group["value"]." (ID ".$group["id"].")"; ?></option>
							<?php
						}
						?>
					</select>
				</div>
			</div>
			
			
			
			
			<div class="col-lg-12 text-center"><h3>Настройки выгрузки данных</h3></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Формат выгрузки
				</label>
				<div class="col-lg-6">
					<select id="output_format" class="form-control">
						<option value="xml">XML</option>
						<option value="json">JSON</option>
					</select>
				</div>
			</div>
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Способ выгрузки данных
				</label>
				<div class="col-lg-6">
					<select id="data_output_mode" class="form-control">
						<option value="create_file">Создать файл во временной папке</option>
						<option value="download_file">Скачать в виде файла XML/JSON</option>
						<option value="open_file_browser">Открыть в отдельной вкладке браузера</option>
					</select>
				</div>
			</div>
		</div>
		<div class="panel-footer">
			<div class="row">
				<div class="col-lg-12">
					<button onclick="exec_export();" class="btn btn-success " type="button"><i class="fa fa-download"></i> <span class="bold">Выгрузить</span></button>
				</div>
			</div>
		</div>
	</div>
</div>









<a href="" id="a_download" target="_blank" download></a>
<a href="" id="a_open_tab" target="_blank"></a>

<script>
//Функция запроса на экспорт
function exec_export()
{
	var request = new Object;
	request.output_format = document.getElementById("output_format").value;
	request.output_products_text = document.getElementById("output_products_text").checked;
	request.output_products_images = document.getElementById("output_products_images").checked;
	request.output_products_suggestions = document.getElementById("output_products_suggestions").checked;
	request.data_output_mode = document.getElementById("data_output_mode").value;
    request.offices = [].concat( $("#offices").multipleSelect('getSelects') );
	request.group_id = document.getElementById("groups").value;

	jQuery.ajax({
		type: "GET",
		async: true, //Запрос синхронный
		url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/data_transfer/ajax/ajax_catalogue_to_xml.php",
		dataType: "json",//Тип возвращаемого значения
		data: "export_options="+encodeURI(JSON.stringify(request)),
		success: function(answer)
		{
			console.log(answer);
			if(answer.status == true)
			{
				if(document.getElementById("data_output_mode").value == "create_file")
				{
					alert("Файл создан во временной папке");
				}
				else if(document.getElementById("data_output_mode").value == "download_file")
				{
					document.getElementById("a_download").setAttribute("href", '/<?php echo $DP_Config->backend_dir; ?>/tmp/'+answer.filename);
					document.getElementById("a_download").click();
				}
				else if(document.getElementById("data_output_mode").value == "open_file_browser")
				{
					document.getElementById("a_open_tab").setAttribute("href", '/<?php echo $DP_Config->backend_dir; ?>/tmp/'+answer.filename);
					document.getElementById("a_open_tab").click();
				}
			}
			else
			{
				alert("Ошибка");
			}
		}
	});
}
</script>
