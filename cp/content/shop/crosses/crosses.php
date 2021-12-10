<?php
/**
 * Скрипт для управления таблицей кроссов
*/
defined('_ASTEXE_') or die('No access');
?>
<style>
	.panel-body > div{
		padding-left:0px;
		padding-right:10px;
	}
	.panel-body > div:last-child{
		padding-right:0px;
	}
	@media screen and (max-width: 1200px) {
		.panel-body > div{
			padding-right:0px;
		}
	}
	.panel-footer .btn{
		margin-right: 5px;
	}
	.table_crosses td:last-child, .table_crosses th:last-child{
		text-align:right;
	}
	.pagination_box{
		text-align:center;
	}
	.pagination_box a{
		font-size: 14px;
		display: inline-block;
		background: #eee;
		border-radius: 2px;
		color: #333;
		padding: 2px 8px;
		margin-right:2px;
		border:1px solid #333;
	}
	.pagination_active{
		background: #34495e !important;
		color: #fff !important;
	}
	#div_table_crosses > .panel-footer{
		color: inherit;
		border: 1px solid #e4e5e7;
		border-top: none;
		font-size: 90%;
		background: #f7f9fa;
		padding: 10px 15px;
	}
	#div_table_crosses > .panel-body{
		overflow-x: auto;
	}
	.table_crosses{
		margin-bottom:0px;
	}
</style>
<div class="row" style="margin: 0;">
	<div class="col-lg-8">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Добавить данные в ручную
			</div>
			<div class="panel-body">
				<div class="col-lg-3"><label>Артикул:</label><input class="form-control" type="text" id="new_article" name="article"/></div>
				<div class="col-lg-3"><label>Производитель:</label><input class="form-control" type="text" id="new_manufacturer_article" name="manufacturer"/></div>
				<div class="col-lg-3"><label>Аналог:</label><input class="form-control" type="text" id="new_analog" name="article"/></div>
				<div class="col-lg-3"><label>Производитель:</label><input class="form-control" type="text" id="new_manufacturer_analog" name="manufacturer"/></div>
			</div>
			<div class="panel-footer text-right">
				<img id="img_crosses_add" style="height: 31px; margin-right: 5px;" class="hidden" src="/content/files/images/ajax-loader-transparent.gif"/><a id="btn_crosses_add" onclick="crosses_add();" class="btn btn-ar btn-primary"><i class="fa fa-plus"></i> Добавить</a>
			</div>
		</div>
	</div>




	<div class="col-lg-4">
		<div class="hpanel">
			<div class="panel-heading hbuilt" style="position:relative;">
				Добавить данные из .csv файла
				<a style="position:absolute; right:20px;" href="/<?=$DP_Config->backend_dir;?>/content/shop/crosses/crosses.csv" download title="Скачать шаблон .csv файла"><i class="fa fa-file"></i></a>
			</div>
			<div class="panel-body">
				<label>Выберете файл:</label><input class="form-control" type="file" id="file_csv" name="file"/>
			</div>
			<div class="panel-footer text-right">
				<img id="img_crosses_add_of_csv" style="height: 31px; margin-right: 5px;" class="hidden" src="/content/files/images/ajax-loader-transparent.gif"/><a id="btn_crosses_add_of_csv" onclick="crosses_add_of_csv();" class="btn btn-ar btn-primary"><i class="fa fa-plus"></i> Добавить</a>
			</div>
		</div>
	</div>
</div>




<div class="row" style="margin: 0;">
	<div class="col-lg-4 col-lg-push-8">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Поиск кроссов
			</div>
			<div class="panel-body">
				<div class="col-lg-6">
					<label>Введите артикул:</label>
					<input class="form-control" type="text" id="search_article" onchange="get_search_manufacturer();" onkeyup="get_search_manufacturer();" name="article"/>
				</div>
				<div class="col-lg-6">
					<label>Выберете производителя:</label>
					<select id="search_manufacturer" class="form-control"><option id="all">Все производители</option></select>
				</div>
			</div>
			<div class="panel-footer text-left">
				<a onclick="del_search_crosses();" class="btn btn-ar btn-danger">Удалить записи с учетом поиска</a>
			</div>
			<div class="panel-footer text-right">
				<a onclick="clear_search();" class="btn btn-ar btn-default"><i class="fa fa-eraser"></i> Сбросить</a>
				<a onclick="show_table_crosses();" class="btn btn-ar btn-primary"><i class="fa fa-search"></i> Поиск</a>
			</div>
		</div>
	</div>




	<div class="col-lg-8 col-lg-pull-4">
		<div class="hpanel">
			<div class="panel-heading hbuilt" style="position:relative;">
				Таблица кроссов
				<a style="position:absolute; right:20px;" onclick="download_crosses();" title="Скачать кроссы в .csv файле"><i class="fa fa-file"></i></a>
			</div>
			<div id="div_table_crosses"></div>
		</div>
	</div>
</div>




<script>
	var page = 1;// Текущая страница таблицы кроссов
	
	// Функция перехода по страницам таблицы кроссов
	function go_to_page(p){
		page = p;
		show_table_crosses();
	}
	
	// Функция отображает таблицу кроссов с условиями фильтрации
	function show_table_crosses(){
		document.getElementById('div_table_crosses').innerHTML = '';
		
		setTimeout(function(){ 
			if(document.getElementById('div_table_crosses').innerHTML == ''){
				// Отображаем индикатор загрузки
				document.getElementById('div_table_crosses').innerHTML = '<div class="panel-body text-center"><img src="/content/files/images/ajax-loader-transparent.gif"/></div>';
			}
		}, 500)

		// Если заданы ограничения фильтрации
		var article = document.getElementById("search_article").value;
		var manufacturer = '';
		if(article){
			var n = document.getElementById("search_manufacturer").options.selectedIndex;
			if(n > 0){
				var val = document.getElementById("search_manufacturer").options[n].value;
				manufacturer = val;
			}
			page = 1;
		}
		
		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'get_table_crosses';
		request_object.page = page;
		request_object.article = encodeURIComponent(article);
		request_object.manufacturer = encodeURIComponent(manufacturer);

		// Отправляем запрос
		jQuery.ajax({
            type: "POST",
            async: true,
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
            dataType: "text",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object)),
            success: function(answer)
            {
				// Вставляем сформированный html на страницу
				document.getElementById('div_table_crosses').innerHTML = answer;
		    }
        });
	}
	
	
	
	// Функция ручного добавления кросса
	function crosses_add(){
		var article 			 = document.getElementById('new_article').value;
		var manufacturer_article = document.getElementById('new_manufacturer_article').value;
		var analog 				 = document.getElementById('new_analog').value;
		var manufacturer_analog  = document.getElementById('new_manufacturer_analog').value;

		if(article === '' || manufacturer_article === '' || analog === '' || manufacturer_analog === ''){
			alert("Заполните все поля");
			return;
		}
		
		// Очищаем форму
		document.getElementById("new_article").value = '';
		document.getElementById("new_manufacturer_article").value = '';
		document.getElementById("new_analog").value = '';
		document.getElementById("new_manufacturer_analog").value = '';
		
		$('#btn_crosses_add').addClass('disabled');// Блокируем кнопку
		$('#img_crosses_add').removeClass('hidden');// Отображаем индикатор загрузки
		
		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'add_crosses';
		request_object.article = encodeURIComponent(article);
		request_object.manufacturer_article = encodeURIComponent(manufacturer_article);
		request_object.analog = encodeURIComponent(analog);
		request_object.manufacturer_analog = encodeURIComponent(manufacturer_analog);
    
        jQuery.ajax({
            type: "POST",
            async: true, //Запрос синхронный
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object)),
            success: function(answer)
            {
                $('#btn_crosses_add').removeClass('disabled');// Разблокируем кнопку
				$('#img_crosses_add').addClass('hidden');// Убираем индикатор загрузки
				
				//console.log(answer);
                if(answer.status == true)
                {
                   show_table_crosses();
                }
                else
                {
					alert("Ошибка добавления. Возможно добавляемый аналог уже существует");
                }
            }
        });
	}
	
	

	// Функция загрузки кроссов из csv файла
	function crosses_add_of_csv(){
		
		//Проверка наличия файла
		var csv_file = document.getElementById("file_csv").value;
		if( csv_file == "" )
		{
			alert("Выберите .csv файл для загрузки");
			return;
		}
		
		//Отправляем файл на сервер
		var csv_file = $("#file_csv");//Input с файлом
		var formData = new FormData;//Объект данных формы
		formData.append('csv_file', csv_file.prop('files')[0]);//Добавляем в объект формы - файл
		
		document.getElementById("file_csv").value = '';// Очищаем форму
		$('#btn_crosses_add_of_csv').addClass('disabled');// Блокируем кнопку
		$('#img_crosses_add_of_csv').removeClass('hidden');// Отображаем индикатор загрузки
		
		$.ajax({
			url: '/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_upload_file_to_tmp.php',
			data: formData,
			processData: false,
			contentType: false,
			type: 'POST',
			dataType: "json",//Тип возвращаемого значения
			success: function (result) 
			{
				if( result.status != true )
				{
					alert("Не удалось загрузить файл");
					$('#btn_crosses_add_of_csv').removeClass('disabled');// Разблокируем кнопку
					$('#img_crosses_add_of_csv').addClass('hidden');// Убираем индикатор загрузки
				}
				else//Файл загружен, запускаем импорт
				{
					//Создаем объект с параметрами импорта
					var import_options = new Object;
					import_options.file_full_path = result.file_full_path;//Полный путь к файлу
					
					//Передаем запрос на сервер для запуска процесса
					jQuery.ajax({
						type: "POST",
						async: true,
						url: '/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_handle_file.php',
						dataType: "json",//Тип возвращаемого значения
						data: "import_options="+encodeURI( JSON.stringify(import_options) ),
						success: function(result)
						{
							$('#btn_crosses_add_of_csv').removeClass('disabled');// Разблокируем кнопку
							$('#img_crosses_add_of_csv').addClass('hidden');// Убираем индикатор загрузки
							
							if(result.status != true)
							{
								alert("Ошибка. "+result.message);
							}
							else
							{
								alert("Успешно");
							}
						}
					});
				}
			}
		});
	}


	
	
	// Функция отображает форму редактирования
	function crosses_edit(id){
        $('#show_line_'+id).addClass('hidden');
        $('#edit_line_'+id).removeClass('hidden');
	}

	// Функция отменяет редактирование
	function crosses_edit_otmena(id){
        $('#edit_line_'+id).addClass('hidden');
		$('#show_line_'+id).removeClass('hidden');
	}

	// Функция сохранения редактируемого кросса
	function crosses_edit_save(id){
		var article = document.getElementById('article_edit_'+id).value;
		var manufacturer_article = document.getElementById('manufacturer_article_edit_'+id).value;
		var analog = document.getElementById('analog_edit_'+id).value;
		var manufacturer_analog = document.getElementById('manufacturer_analog_edit_'+id).value;
		
		if(article === '' || manufacturer_article === '' || analog === '' || manufacturer_analog === ''){
			alert("Заполните все поля");
			return;
		}
		
		//Объект для запроса
        var request_object = new Object;
		request_object.action = 'save_crosses';
		request_object.id = id;
		request_object.article = encodeURIComponent(article);
		request_object.manufacturer_article = encodeURIComponent(manufacturer_article);
		request_object.analog = encodeURIComponent(analog);
		request_object.manufacturer_analog = encodeURIComponent(manufacturer_analog);
    
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object)),
            success: function(answer)
            {
                //console.log(answer);
                if(answer.status == true)
                {
                    show_table_crosses();
                }
                else
                {
					alert("Ошибка сохранения. Возможно редактируемый аналог уже существует");
                }
            }
        });
	}
	
	// Функция удаления
	function crosses_del(id){
        if(confirm('Вы действительно хотите удалить запись?')){
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'del_crosses';
			request_object.id = id;
		
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object)),
				success: function(answer)
				{
					//console.log(answer);
					if(answer.status == true)
					{
						show_table_crosses();
					}
					else
					{
						alert("Ошибка удаления");
					}
				}
			});
		}
	}
	
	// Функция удаления с учетом поиска
	function del_search_crosses(){
        if(confirm('Вы действительно хотите удалить найденные записи?')){
			
			// Если заданы ограничения фильтрации
			var article = document.getElementById("search_article").value;
			var manufacturer = '';
			if(article){
				var n = document.getElementById("search_manufacturer").options.selectedIndex;
				if(n > 0){
					var val = document.getElementById("search_manufacturer").options[n].value;
					manufacturer = val;
				}
				page = 1;
			}
			
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'del_search_crosses';
			request_object.page = page;
			request_object.article = encodeURIComponent(article);
			request_object.manufacturer = encodeURIComponent(manufacturer);
			
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object)),
				success: function(answer)
				{
					//console.log(answer);
					if(answer.status == true)
					{
						show_table_crosses();
					}
					else
					{
						alert("Ошибка удаления");
					}
				}
			});
		}
	}
	
	
	
	
	// Функция запроса списка производителей
	function get_search_manufacturer(){
		var article = document.getElementById('search_article').value;
		if(article.length >= 2){
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'get_search_manufacturer';
			request_object.article = encodeURIComponent(article);
			
			jQuery.ajax({
				type: "POST",
				async: true,
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/crosses/ajax_operations.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object)),
				success: function(answer)
				{
					//console.log(answer);
					if(answer.status == true)
					{
						var select = '<option id="all">Все производители</option>';
						var list_manufacturer = JSON.parse(answer.list_manufacturer);
						if(list_manufacturer){
							for(var i = 0; list_manufacturer.length > i; i++){
								select += '<option id="'+ list_manufacturer[i] +'">'+ list_manufacturer[i] +'</option>';
							}
						}
						document.getElementById('search_manufacturer').innerHTML = select;
					}
				}
			});
		}else{
			document.getElementById('search_manufacturer').innerHTML = '<option id="all">Все производители</option>';
		}
	}
	
	
	
	// Функция сбрасывает фильтры поиска
	function clear_search(){
		page = 1;
		document.getElementById('search_article').value = '';
		document.getElementById('search_manufacturer').innerHTML = '<option id="all">Все производители</option>';
		show_table_crosses();
	}

	
	
	// Скачать файл кроссов
	function download_crosses(){
		window.open('/<?=$DP_Config->backend_dir;?>/content/shop/crosses/download_crosses.php', '_blank');
	}


	// После открытия страницы отображаем таблицу кроссов
	show_table_crosses();
</script>