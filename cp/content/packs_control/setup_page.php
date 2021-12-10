<?php
/**
 * Скрипт для страницы установки пакета
*/
defined('_ASTEXE_') or die('No access');

if(!empty($_POST["setup_pack"]))
{
    //Проверяем расширение файла
    $file_name = $_FILES['pack_file']['name'];
    $file_ext = explode(".", $file_name);
    $file_ext = $file_ext[count($file_ext) - 1];
    if(strtoupper($file_ext) != "ZIP")
    {
        $error_message = "Файл не поддерживается - используйте файл *.zip";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/packs/setup?error_message=<?php echo $error_message; ?>";
        </script>
        <?php
        exit;
    }
    
    //Загружаем файл
    $uploaddir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/pack_setup/";
    $uploadfile = $uploaddir . basename($_FILES['pack_file']['name']);
    if (! move_uploaded_file($_FILES['pack_file']['tmp_name'], $uploadfile)) 
    {
        $error_message = "Ну удалось загрузить файл на сервер";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/packs/setup?error_message=<?php echo $error_message; ?>";
        </script>
        <?php
        exit;
    } 
    
    //ДАЛЕЕ ВЫВОД СТРАНИЦЫ
    ?>
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/packs/packs_manager">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/packs.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер пакетов</div>
				</a>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/packs/setup">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/pack_setup.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Установить еще пакет</div>
				</a>
			
			
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Процесс установки пакета
			</div>
			<div class="panel-body">
				<div id="progressbar"></div>
				<div id="setup_messages" style="padding:5px"></div>
				<div id="pack_info_div" style="padding:5px"></div>
			</div>
		</div>
	</div>
	
    
    
    
    
    <script>
    //УПРАВЛЕНИЕ ПРОЦЕССОМ УСТАНОВКИ ПАКЕТА
    var pack_id = 0;//Переменная для ID устанавливаемого пакета (определеяется после первого шага)
    start_prepare_setup();
    
    
    
    
    // ---------------------------------------------------------------------------------------------
    //1. ЗАПРОС: проверка файла пакета и создание учетной записи
    function start_prepare_setup()
    {
        setupIndication(10, "Проверка пакета");
        jQuery.ajax({
                type: "POST",
                async: true, //Запрос асинхронный
                url: "/<?php echo $DP_Config->backend_dir; ?>/content/packs_control/ajax_prepare_setup.php",
                dataType: "json",//Тип возвращаемого значения
                data: "pack_file=<?php echo $uploadfile; ?>",
                success: function(answer) {
                    after_prepare_setup(answer);
                }
            });
    }
    // ---------------------------------------------------------------------------------------------
    //1. ОТВЕТ: Обработка ответа от скрипта подготовки пакета
    function after_prepare_setup(answer)
    {
        //Подготовка прошла успешно - следующий шаг - Копирование файлов
        if(answer.result_code == 0)
        {
            pack_id = answer.pack_id;//Принимаем ID устанавливаемого пакета
            processingFiles();//Запуск второго запроса - обработка файлов
        }
        else//Ошибка на данном шаге:
        {
            clearTmpFolder();//Очищаем временный каталог
            showMessage(answer.message, "error", 1);
        }
    }
    // ---------------------------------------------------------------------------------------------
    //2. ЗАПРОС: обработка файлов (копирование)
    function processingFiles()
    {
        setupIndication(20, "Идет копирование файлов");
        jQuery.ajax({
                type: "POST",
                async: true, //Запрос асинхронный
                url: "/<?php echo $DP_Config->backend_dir; ?>/content/packs_control/ajax_processing_files.php",
                dataType: "json",//Тип возвращаемого значения
                data: "pack_id=" + pack_id,
                success: function(answer) {
                    after_processing_files(answer);
                }
            });
    }
    // ---------------------------------------------------------------------------------------------
    //2. ОТВЕТ: обработка файлов (копирование)
    function after_processing_files(answer)
    {
        //Копирование фалов прошло успешно
        if(answer.result_code == 0)
        {
            insert_extensions();//Следующий шаг - создание записей расширений
        }
        else//Ошибка на данном шаге:
        {
            clearTmpFolder();//Очищаем временный каталог
            showMessage(answer.message, "error", 2);
        }
    }
    // ---------------------------------------------------------------------------------------------
    //3. ЗАПРОС - СОЗДАНИЕ ЗАПИСЕЙ РАСШИРЕНИЙ
    function insert_extensions()
    {
        setupIndication(55, "Создание записей расширений");
        jQuery.ajax({
                type: "POST",
                async: true, //Запрос асинхронный
                url: "/<?php echo $DP_Config->backend_dir; ?>/content/packs_control/ajax_insert_extensions.php",
                dataType: "json",//Тип возвращаемого значения
                data: "pack_id=" + pack_id,
                success: function(answer) {
                    after_insert_extensions(answer);    
                }
            });
    }
    // ---------------------------------------------------------------------------------------------
    //3. ОТВЕТ - СОЗДАНИЕ ЗАПИСЕЙ РАСШИРЕНИЙ
    function after_insert_extensions(answer)
    {
        //Создание записей расширений прошло успешно
        if(answer.result_code == 0)
        {
            setupIndication(90, "Завершение установки");
            afterSuccessInstall(answer.pack_info_ob);
        }
        else//Ошибка на данном шаге:
        {
            clearTmpFolder();//Очищаем временный каталог
            showMessage(answer.message, "error", 3);
        }
    }
    // ---------------------------------------------------------------------------------------------
    //4. УСТАНОВКА ЗАВЕРШЕНА - ОТОБРАЖАЕМ ИНФОРМАЦИЮ ПО УСТАНОВЛЕННОМУ ПАКЕТУ
    function afterSuccessInstall(pack_info_ob)
    {
        setupIndication(100, "Выполнено");
    
        var html_pack_info = " <div class=\"panel panel-default\"><div class=\"panel-heading\">Информация по установленнному пакету</div><div class=\"panel-body\">";
        html_pack_info += "<table class=\"table\">";
        
        html_pack_info += "<tr> <td>Название:</td> <td>"+pack_info_ob.caption+"</td> </tr>";
        html_pack_info += "<tr> <td>ID:</td> <td>"+pack_id+"</td> </tr>";
        html_pack_info += "<tr> <td>Разработчик:</td> <td>"+pack_info_ob.author+"</td> </tr>";
        html_pack_info += "<tr> <td>Версия:</td> <td>"+pack_info_ob.version+"</td> </tr>";
        
        html_pack_info += "<tr> <td colspan=\"2\" align=\"center\"><b>Состав пакета</b></td> </tr>";
        if(pack_info_ob.files.length > 0)
        {
            html_pack_info += "<tr> <td colspan=\"2\">Файлы:</td> </tr>";
            for(var i=0; i < pack_info_ob.files.length; i++)
            {
                html_pack_info += "<tr> <td colspan=\"2\">"+pack_info_ob.files[i]["server_path"]+pack_info_ob.files[i]["file_name"]+"</td> </tr>";
            }
        }
        if(pack_info_ob.modules_prototypes.length > 0)
        {
            html_pack_info += "<tr> <td colspan=\"2\">Прототипы модулей:</td> </tr>";
            for(var i=0; i < pack_info_ob.modules_prototypes.length; i++)
            {
                html_pack_info += "<tr> <td colspan=\"2\">"+pack_info_ob.modules_prototypes[i]["caption"]+", ID "+pack_info_ob.modules_prototypes[i]["id"]+"</td> </tr>";
            }
        }
        if(pack_info_ob.plugins.length > 0)
        {
            html_pack_info += "<tr> <td colspan=\"2\">Плагины:</td> </tr>";
            for(var i=0; i < pack_info_ob.plugins.length; i++)
            {
                html_pack_info += "<tr> <td colspan=\"2\">"+pack_info_ob.plugins[i]["caption"]+", ID "+pack_info_ob.plugins[i]["id"]+"</td> </tr>";
            }
        }
        if(pack_info_ob.templates.length > 0)
        {
            html_pack_info += "<tr> <td colspan=\"2\">Шаблоны:</td> </tr>";
            for(var i=0; i < pack_info_ob.templates.length; i++)
            {
                html_pack_info += "<tr> <td colspan=\"2\">"+pack_info_ob.templates[i]["caption"]+", ID "+pack_info_ob.templates[i]["id"]+"</td> </tr>";
            }
        }
        
        
        html_pack_info += "</table></div></div>";
        
        document.getElementById("pack_info_div").innerHTML = html_pack_info;//Информация по установленному пакету
        showMessage("Пакет установлен", "success", 4);//Сообщение об успешной становке
        clearTmpFolder();//ОЧИЩАЕМ ВРЕМЕННЫЙ КАТАЛОГ С ПАКЕТОМ НА СЕРВЕРЕ
        
        document.getElementById("actions_panel").setAttribute("style", "display:block");//Показываем панель действий
    }
    // ---------------------------------------------------------------------------------------------
    //Функция индикации прогресса
    function setupIndication(percent, message)
    {
        //Значение progressbar
        $( "#progressbar" ).progressbar({
				value: percent
			});
    	
    	//Сообщение (текущее действие)
    	document.getElementById("setup_messages").innerHTML = message;
    }
    // ---------------------------------------------------------------------------------------------
    //Функция очистки временного каталога
    function clearTmpFolder()
    {
        jQuery.ajax({
            type: "POST",
            async: true, //Запрос асинхронный
            url: "/<?php echo $DP_Config->backend_dir; ?>/content/packs_control/ajax_clear_tmp_folder.php",
            dataType: "json",//Тип возвращаемого значения
            success: function(answer) {
                if(answer == "Success")
                {
                    showMessage("Временный каталог очищен", "info", 5);
                }
                else
                {
                    showMessage("Ошибка при очистке временного каталога", "error", 6);
                }
                
            }
        });
    }
    // ---------------------------------------------------------------------------------------------
    //Вывод сообщенний
    function showMessage(text, type, id_pre)
    {
        document.getElementById("setup_messages").innerHTML += "<div class=\"alert alert-"+type+" alert-dismissable\" id=\""+type+"_div_"+id_pre+"\"><button type=\"button\" class=\"close\" onclick=\"clearAlert('"+type+"_div_"+id_pre+"');\">&times;</button>"+text+"</div>";
    }
    // ---------------------------------------
    //Удаляем сообщение
    function clearAlert(alert_div_id)
    {
        var alert_div = document.getElementById(alert_div_id);
        alert_div.parentNode.removeChild(alert_div);
    }
    // ---------------------------------------------------------------------------------------------
    </script>
    <?php
}
else//Действий нет - выводим страницу указания файла.
{
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
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/packs/packs_manager">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/packs.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер пакетов</div>
				</a>
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Выбор файла
			</div>
			<div class="panel-body">
				<form method="POST" enctype="multipart/form-data" onsubmit="return checkSubmit();">
        	        <input type="hidden" name="setup_pack" id="setup_pack" value="setup_pack" />
					
					<div class="col-lg-6">
						<input class="form-control" type="file" name="pack_file" id="pack_file" accept="multipart/x-zip,application/zip,application/x-zip-compressed,application/x-compressed" />
					</div>
					<div class="col-lg-6">
						<button class="btn btn-success " type="submit"><i class="fa fa-check"></i> <span class="bold">Установить</span></button>
					</div>
					
        	        
					
        	        
        	    </form>
			</div>
		</div>
	</div>
    
    
    
    
    
    
    <script>
        //Проверка выбора файла
        function checkSubmit()
        {
            if(document.getElementById("pack_file").value == "")
            {
                alert("Выберите файл");
                return false;
            }
            return true;
        }
    </script>
    
    <?php
}
?>