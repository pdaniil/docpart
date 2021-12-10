<?php
/**
 * Страничный скрипт для загрузки файла прайс-листа
 * 
 * 
 * - сразу импортировать файл в таблицу назначения;
 * - таблицу сразу создавать с именованными колонками нужного типа
 * - обработку значений колонок запускать отдельно из js
*/


//--------------------------------------------------------------------------------------------------------
//Функция очистки каталога ($clear_only: true - только очистить, false - удалить и сам каталог)
function clear_dir($dir, $clear_only) 
{
	foreach(glob($dir . '/*') as $file) 
	{
		if(is_dir($file))
		{
			clear_dir($file, false);
		}
		else
		{
			$file_name = explode("/", $file);
			$file_name = $file_name[ count($file_name) - 1 ];
			if( $file_name != "index.html" )
			{
				unlink($file);
			}
		}
	}
	if(!$clear_only)
	{
		rmdir($dir);
	}
}
//--------------------------------------------------------------------------------------------------------
?>



<?php
if(!empty($_POST["action"]))
{
    if($_POST["action"] == "upload")
    {
        $price_id = $_POST["price_id"];
        $clean_before = 0;
        if( !empty($_POST["clean_before"]) )
        {
            $clean_before = 1;
        }
        
        
        //Проверяем наличие временного каталога для загрузки. ПРИ НЕОБХОДИМОСТИ 0 СОЗДАЕМ
        $treelax_tmp_dir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir.$DP_Config->tmp_dir_prices_upload;//Путь к каталогу для загрузки файлов прайс-листов
        if(!is_dir($treelax_tmp_dir))
        {
            if(!mkdir($treelax_tmp_dir))
            {
                $error_message = "Не удалось создать временный каталог для загрузки файла";
                ?>
                <script>
                    location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/prices/upload?price_id=<?php echo $price_id; ?>&error_message=<?php echo $error_message; ?>";
                </script>
                <?php
                exit;
            }
        }
        else//Каталог есть - предварительно очищаем его
        {
            clear_dir($treelax_tmp_dir, true);//Функция очистки каталога (true - очистить, а сам каталог оставить)
        }

        
        //БРОСАЕМ В НЕГО ФАЙЛ
        //Проверям на расширение из трех знаков (txt, csv, xls)
        $file_format = substr($_FILES['price_file']['name'], strlen($_FILES['price_file']['name'])-4, 4);
        $file_format = strtolower($file_format);//К нижнему регистру
        if($file_format != ".txt" && $file_format != ".csv" && $file_format != ".xls" && $file_format != ".zip" && $file_format != ".rar")
        {
            //Из трех знаков не подходит - получаем четыре знака
        	$file_format = substr($_FILES['price_file']['name'], strlen($_FILES['price_file']['name'])-5, 5);
        }
        //Теперь полная проверка расширения
        if(strtoupper($file_format) != ".TXT" &&
        strtoupper($file_format) != ".CSV" &&
        strtoupper($file_format) != ".XLS" &&
        strtoupper($file_format) != ".XLSX" &&
        strtoupper($file_format) != ".ZIP" &&
        strtoupper($file_format) != ".RAR")
        {
            $error_message = "Не совместимый тип файла. Используйте файлы TXT, CSV, XLS, XLSX, ZIP, RAR";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/prices/upload?price_id=<?php echo $price_id; ?>&error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
        //Загружаем файл
        $uploaddir = $treelax_tmp_dir."/";
        $uploadfile = $uploaddir . basename($_FILES['price_file']['name']);
        if (! move_uploaded_file($_FILES['price_file']['tmp_name'], $uploadfile)) 
        {
            $error_message = "Не удалось загрузить файл";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/prices/upload?price_id=<?php echo $price_id; ?>&error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
        
        
        //Файл загружен - теперь выводим страницу с фунцией импорта прайс-листа по AJAX-запросу
        ?>
        
        
        <!-- ЛОГ ПРОЦЕССА -->
		<div class="col-lg-12">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					Лог процесса
				</div>
				<div class="panel-body" id="import_log">
				</div>
			</div>
			
			<div class="log_div_loading_gif" id="loading_gif" style="text-align:center;">
				<img src="/content/files/images/ajax-loader-transparent.gif" />
			</div>
		</div>
		
        
        
        <script>
			//Панель кнопок после выполнения
			var buttons_panel_after_work = "<a class=\"btn w-xs btn-success\" href=\"/<?php echo $DP_Config->backend_dir; ?>/shop/prices\">Менеджер прайс-листов</a> <a class=\"btn w-xs btn-primary2\" href=\"/<?php echo $DP_Config->backend_dir; ?>/shop/prices/upload?price_id=<?php echo $price_id; ?>\"><i class=\"fa fa-upload\"></i> <span class=\"bold\">Загрузить снова</span></a>";
		
		
            //УПРАВЛЕНИЕ ПРОЦЕССОМ ИПОРТА
            
            var action_loading_img = "<img src=\"/content/files/images/ajax-loader-transparent.gif\" style=\"width:15px\" />";
            
            var current_action_indicator = 2;
            
            document.getElementById("import_log").innerHTML += "<p>Предварительная очистка временного каталога - <span id=\"indicator_0\" class=\"complete\">Выполнено</span></p>";
            document.getElementById("import_log").innerHTML += "<p>Загрузка файла на сервер - <span id=\"indicator_1\" class=\"complete\">Выполнено</span></p>";
            document.getElementById("import_log").innerHTML += "<p>Работа с архивами - <span id=\"indicator_"+current_action_indicator+"\" class=\"process\">"+action_loading_img+"</span></p>";
            
            
            //АЛГОРИТМ ИМПОРТА ФАЙЛА
            jQuery.ajax({
                type: "GET",
                async: true, //Запрос асинхронный
                url: "<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/shop/prices_upload/ajax_2_extract_files.php",
                dataType: "json",//Тип возвращаемого значения
                success: function(answer){
                    console.log(answer);
                    if(answer.packs_count == 0)//Архивов не было - все нормально
                    {
                        //Работаем с логом
                        var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                        action_indicator.innerHTML = "Выполнено. Архивы не обнаружены";
                        action_indicator.setAttribute("class", "complete");
                        
                        excel_convert();//Далее запускаем конвертирование файлов Excel
                    }
                    else//Были обнаружены архивы
                    {
                        if(answer.packs_error > 0)//Есть ошибки при работе с архивами
                        {
                            //Работаем с логом
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Ошибка. Найдено архивов: "+answer.packs_count+". Не удалось распаковать: "+answer.packs_error;
                            action_indicator.setAttribute("class", "error");
                            
                            //Убираем loading.gif
                            document.getElementById("loading_gif").innerHTML = buttons_panel_after_work;
                        }
                        else//Все архивы извлечены успешно
                        {
                            //Работаем с логом
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Выполнено. Распаковано архивов: "+answer.packs_count;
                            action_indicator.setAttribute("class", "complete");
                            
                            excel_convert();//Далее запускаем конвертирование файлов Excel
                        }
                    }
                }
            });
            
            
            
            // --------------------------------------------------------------------------------------------------------------
            //Команда на конвертацию файлов Excel
            function excel_convert()
            {
                //Пишем лог
                current_action_indicator++;//Следующий шаг
                document.getElementById("import_log").innerHTML += "<p>Проверка файлов Excel - <span id=\"indicator_"+current_action_indicator+"\" class=\"process\">"+action_loading_img+"</span></p>";
                document.getElementById("import_log").scrollTop = document.getElementById("import_log").scrollHeight;//Прокручиваем лог
            
                jQuery.ajax({
                    type: "GET",
                    async: true, //Запрос асинхронный
                    url: "<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/shop/prices_upload/ajax_3_excel_convert.php",
                    dataType: "json",//Тип возвращаемого значения
                    success: function(answer){
                        console.log(answer);
                        if(answer.result == 1)
                        {
                            //Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Выполнено";
                            action_indicator.setAttribute("class", "complete");
                            
                            //ЗАПУСК СЛЕДУЮЩЕЙ КОМАНДЫ...
                            csv_prepare();//Обработка файлов csv
                        }
                        else
                        {
                            //Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Ошибка. "+answer.message;
                            action_indicator.setAttribute("class", "error");
                            
                            //Убираем loading.gif
                            document.getElementById("loading_gif").innerHTML = buttons_panel_after_work;
                        }
                    }
                });
            }//~function excel_convert()
            // --------------------------------------------------------------------------------------------------------------
            //Команда на обработку файлов csv
            function csv_prepare()
            {
                //Пишем лог
                current_action_indicator++;//Следующий шаг
                document.getElementById("import_log").innerHTML += "<p>Подготовка файлов csv - <span id=\"indicator_"+current_action_indicator+"\" class=\"process\">"+action_loading_img+"</span></p>";
                document.getElementById("import_log").scrollTop = document.getElementById("import_log").scrollHeight;//Прокручиваем лог
                
                jQuery.ajax({
                    type: "GET",
                    async: true, //Запрос асинхронный
                    url: "<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/shop/prices_upload/ajax_4_prepare_csv.php",
                    dataType: "json",//Тип возвращаемого значения
                    success: function(answer){
                        console.log(answer);
                        if(answer.result == 1)
                        {
                            //Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Выполнено";
                            action_indicator.setAttribute("class", "complete");
                            
                            //ЗАПУСК СЛЕДУЮЩЕЙ КОМАНДЫ...
                            import_csv_to_db();//Команда импорта csv файлов в БД
                        }
                        else
                        {
                            //Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Ошибка. "+answer.message;
                            action_indicator.setAttribute("class", "error");
                            
                            //Убираем loading.gif
                            document.getElementById("loading_gif").innerHTML = buttons_panel_after_work;
                        }
                    }
                });
            }//~function csv_prepare()
            // --------------------------------------------------------------------------------------------------------------
            //Команда импорта csv файлов в БД
            var has_import_json_answer = false;//Флаг - импорт файла вернул JSON-ответ в штатном режиме
			function import_csv_to_db()
            {
                //Пишем лог
                current_action_indicator++;//Следующий шаг
                document.getElementById("import_log").innerHTML += "<p>Импорт файлов CSV в БД - <span id=\"indicator_"+current_action_indicator+"\" class=\"process\">"+action_loading_img+"</span></p>";
                document.getElementById("import_log").scrollTop = document.getElementById("import_log").scrollHeight;//Прокручиваем лог
                
                jQuery.ajax({
                    type: "GET",
                    async: true, //Запрос асинхронный
                    url: "<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/shop/prices_upload/ajax_5_import_csv_to_db.php",
                    dataType: "json",//Тип возвращаемого значения
                    data: "price_id=<?php echo $price_id; ?>&initiator=js&clean_before=<?php echo $clean_before; ?>",
                    success: function(answer){
                        console.log(answer);
                        if(answer.result == 1)
                        {
							has_import_json_answer = true;
							
                            //Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Выполнено";
                            action_indicator.setAttribute("class", "complete");
                            
                            //ЗАПУСК СЛЕДУЮЩЕЙ КОМАНДЫ...
                            complete_session();//Завершение сессии
                        }
                        else
                        {
							has_import_json_answer = true;
							
                            //Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Ошибка. "+answer.message;
                            action_indicator.setAttribute("class", "error");
                            
                            //Убираем loading.gif
                            document.getElementById("loading_gif").innerHTML = buttons_panel_after_work;
                        }
                    },
					complete: function( jqXHR, textStatus )
					{
						if( ! has_import_json_answer)
						{
							//Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Импорт не успел завершиться, т.к. на сервере ограничено время для выполнения PHP-скриптов. Необходимо увеличить время выполнения скриптов на сервере, либо пользоваться Windows-программой для загрузки прайс-листов";
                            action_indicator.setAttribute("class", "error");
                            
                            //Убираем loading.gif
                            document.getElementById("loading_gif").innerHTML = buttons_panel_after_work;
							
							table_enable_keys();
						}
					}
					
                });
            }
            // --------------------------------------------------------------------------------------------------------------
            //Завершение сессии
            function complete_session()
            {
                //Пишем лог
                current_action_indicator++;//Следующий шаг
                document.getElementById("import_log").innerHTML += "<p>Запись времени обновления - <span id=\"indicator_"+current_action_indicator+"\" class=\"process\">"+action_loading_img+"</span></p>";
                document.getElementById("import_log").scrollTop = document.getElementById("import_log").scrollHeight;//Прокручиваем лог
                
                jQuery.ajax({
                    type: "GET",
                    async: true, //Запрос асинхронный
                    url: "<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/shop/prices_upload/ajax_6_complete_session.php",
                    dataType: "json",//Тип возвращаемого значения
                    data: "price_id=<?php echo $price_id; ?>",
                    success: function(answer){
                        console.log(answer);
                        if(answer.result == 1)
                        {
                            //Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Выполнено";
                            action_indicator.setAttribute("class", "complete");
                            
                            //ВСЕ ВЫПОЛНЕНО
                            document.getElementById("loading_gif").innerHTML = buttons_panel_after_work;
                        }
                        else
                        {
                            //Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Ошибка. "+answer.message;
                            action_indicator.setAttribute("class", "error");
                            
                            //Убираем loading.gif
                            document.getElementById("loading_gif").innerHTML = buttons_panel_after_work;
                        }
                    },
                    statusCode: {
                        502: function () {
                            //Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = "Ошибка 502";
                            action_indicator.setAttribute("class", "error");
                            
                            //Убираем loading.gif
                            document.getElementById("loading_gif").innerHTML = buttons_panel_after_work;
                        }
                    }
                });
            }
			// --------------------------------------------------------------------------------------------------------------
            //Включить индексы в таблице - необходимо, если заргузка CSV не успела выполниться полностью
            function table_enable_keys()
            {
                jQuery.ajax({
                    type: "GET",
                    async: true, //Запрос асинхронный
                    url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/prices_upload/ajax_7_enable_keys.php",
                    dataType: "json",//Тип возвращаемого значения
                    success: function(answer){
                        console.log(answer);
						if(answer.result == 1)
                        {
                            //Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = action_indicator.innerHTML + "<br><b>Индексы таблицы включены</b>";
                        }
						else
						{
							//Пишем лог
                            var action_indicator = document.getElementById("indicator_"+current_action_indicator);
                            action_indicator.innerHTML = action_indicator.innerHTML + "<br><b>Ошибка при включении индексов таблицы</b>";
						}
                    }
                });
            }
            // --------------------------------------------------------------------------------------------------------------
        </script>
        
        
        
        <?php
    }
}
else//Действий нет - выводим форму выбора файла
{
    $price_id = $_GET['price_id'];
    
    //Получаем имя прайс-листа
	$price_info_query = $db_link->prepare("SELECT `name` FROM `shop_docpart_prices` WHERE `id` = ?;");
	$price_info_query->execute( array($price_id) );
    $price_record = $price_info_query->fetch();
	if($price_record == false)
    {
        exit("No such price record");
    }
    $price_name = $price_record["name"];
    ?>
    
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
    
    
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" href="javascript:void(0);" onclick="submitForm();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/upload.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Загрузить</div>
				</a>
				
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/prices">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/excel.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер прайс-листов</div>
				</a>

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
    
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Выбор файла
			</div>
			<div class="panel-body">
				<form method="post" enctype="multipart/form-data" name="upload_form">
					<input type="hidden" name="price_id" value="<?php echo $price_id; ?>" />
					<input type="hidden" name="action" value="upload" />
					
					
					<input type="file" name="price_file" id="price_file" class="form-control" />
					
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Полностью обновить
						</label>
						<div class="col-lg-6">
							<input type="checkbox" name="clean_before" checked="checked" />
						</div>
					</div>
					
				</form>
			</div>
		</div>
	</div>
	
    
    
    <script>
    //Проверка формы
    function submitForm()
    {
        var price_file = document.getElementById("price_file");
        
        if(price_file.value == "" || price_file.value == null)
        {
            alert("Выберите файл для загрузки");
            return;
        }
        
        document.forms['upload_form'].submit();
    }
    </script>
    <?php
}
?>