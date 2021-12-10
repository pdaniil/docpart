<?php
/**
 * Скрипт для работы с одним материалом
*/
defined('_ASTEXE_') or die('No access');
?>




<?php
if(!empty($_POST["save_content"]))
{
    //$_POST["content"] = addcslashes($_POST["content"], '\'');
    
	$save_result = $db_link->prepare("UPDATE `content` SET `content_type` = ?, `content` = ?, `time_edited` = ? WHERE `id` = ?;")->execute( array($_POST["content_type"], $_POST["content"], time(), $_GET["content_id"]) );
	
    if($save_result == true)
    {
        $success_message = "Материал сохранен успешно!";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/edit_content?content_id=<?php echo $_POST["content_id"]; ?>&success_message=<?php echo $success_message; ?>";
        </script>
        <?php
        exit;
    }
    else
    {
        $error_message = "Ошибка при сохранении!";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/edit_content?content_id=<?php echo $_POST["content_id"];?>&error_message=<?php echo $error_message; ?>";
        </script>
        <?php
        exit;
    }
}
else//Выводим страницу
{
	$content_query = $db_link->prepare("SELECT * FROM `content` WHERE `id` = ?;");
	$content_query->execute( array($_GET["content_id"]) );
    $content_record = $content_query->fetch();
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
				<a class="panel_a" onClick="save_button_clicked();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>

				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir;?>/content/content_tree">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/documents.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер материалов</div>
				</a>
				


				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
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
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Тип содержимого
					</label>
					<div class="col-lg-6">
						<select id="content_type_select" name="content_type_select" onchange="content_type_changed();" class="form-control">
    	                    <option value="text">Текст</option>
    	                    <option value="php">Подключаемый php-скрипт</option>
    	                </select>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Содержимое материала
			</div>
			<div class="panel-body">
				<div id="content_value_area"></div>
			</div>
		</div>
	</div>
	
	
	<!-- Для загрузки файлов через TinyMCE -->
	<iframe id="file_form_target" name="file_form_target" style="display:none"></iframe>
	<form id="file_form" action="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/lib/tinymce/postAcceptor.php" target="file_form_target" method="post" enctype="multipart/form-data" style="width:0px;height:0;overflow:hidden">
		<input id="image_input" name="image" type="file" onchange="onFileSelected();">
	</form>
    <script>
	//Обработка выбора файла текстовом редакторе
	function onFileSelected()
	{
		//Создаем данные для формы
		var formData = new FormData();
		formData.append('image', $('input[type=file]')[0].files[0]); 
		
		//Передаем форму с файлом на сервер
		$.ajax({
			url: '<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/lib/tinymce/postAcceptor.php',
			data: formData,
			dataType:"json",
			type: "POST",
			contentType: false,
			processData: false,
			success : function (answer){
				console.log("Ответ сервера: "+answer);
				
				if(answer.status == true)
				{
					//Указываем имя файл в окне его выбора от TinyMCE и закрываем окно
					top.$('.mce-btn.mce-open').parent().find('.mce-textbox').val(answer.url).closest('.mce-window').find('.mce-primary').click();
					
					//Очищаем input
					document.getElementById("image_input").value = '';
				}
				else
				{
					alert("Ошибка: "+answer.message)
					
					//Очищаем input
					document.getElementById("image_input").value = '';
				}
			}
		})
	}
	</script>
	
	

    
    
    
    <form style="display:none" method="POST" name="save_form" id="save_form">
        <input type="hidden" name="save_content" id="save_content" value="save_content" /><!--Флаг - идет сохранение-->
        <input type="hidden" name="content_id" value="<?php echo $_GET["content_id"];?>"/>
        <input type="hidden" name="content_type" id="content_type" value="" /><!--Тип содержимое-->
        <input type="hidden" name="content" id="content" value="" /><!--Содержимое-->
    </form>


    <script>
    //-----------------------------------------------------
    
    //Нажатие Сохранить
    function save_button_clicked()
    {
        //Содержимое
        var content_type = document.getElementById("content_type_select").value;//Текущий выбранный тип содержимого
        var content = "";//Содежимое
        if(content_type == "text")
        {
            content = tinymce.activeEditor.getContent();//Получаем содержимое из текстового редактора
        }
        else if(content_type == "php")
        {
            content = document.getElementById("php_file_path").value;//Получаем содержимое для поля ввода пути к файлу
        }
        if(content != "")
        {
            document.getElementById("content").value = content;
            document.getElementById("content_type").value = content_type;
        }
        else
        {
            webix.message({type:"error", text:"Содержимое материала не должно быть пустым"});
            return;
        }
        
        //console.log(content);
        //return;
        
        document.forms["save_form"].submit();//Отправляем
    }
    
    
    //-----------------------------------------------------
    //Вспомогательные паременные для запоминания содержимого при переключении типа 
    var text_content = "";
    var php_content = "";
    var already_loaded = false;//Флаг - Страница полностью загружена. При загрузке страницы еще некоторые объекты не доступны и к ним нельзя обращаться.
    
    //Переключение типа содержимого
    function content_type_changed()
    {
        var content_type = document.getElementById("content_type_select").value;
        var content_value_area = document.getElementById("content_value_area");
        
        if(content_type == "php")
        {
            if(already_loaded)
            {
                text_content = tinymce.activeEditor.getContent();//Сначала запоминаем текстовое содержимое, чтобы не потерять
            }
			
			
			content_value_area.innerHTML = "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Путь к файлу</label><div class=\"col-lg-6\"><input type=\"text\" name=\"php_file_path\" id=\"php_file_path\" value=\"\" class=\"form-control\" /></div></div>";
			
			
            
            if(already_loaded)
            {
                document.getElementById("php_file_path").value = php_content;//Восстанавливаем запомненное содержимое (если оно было)
            }
        }
        else if(content_type == "text")
        {
            if(already_loaded)
            {
                php_content = document.getElementById("php_file_path").value;//Сначала запоминаем php-содержимое, чтобы не потерять
            }
            
            content_value_area.innerHTML = "<textarea style=\"min-height:400px\" class=\"tinymce_editor\" id=\"tinymce_editor\"></textarea>";
            tinymce.init({
                selector: "textarea.tinymce_editor",
                plugins: [
                    "advlist autolink lists link image charmap print preview anchor",
                    "searchreplace visualblocks code fullscreen",
                    "insertdatetime media table contextmenu paste textcolor"
                ],
				extended_valid_elements:"script[*]",
                toolbar: [ 
                        "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect | formatselect | fontselect | fontsizeselect | ", 
                        "cut copy paste | bullist numlist | outdent indent | blockquote | undo redo | removeformat subscript superscript | link image | forecolor backcolor",
                ],
				file_browser_callback: function(field_name, url, type, win) {
					if(type=='image') $('#file_form input').click();
				}
            });
            
            if(already_loaded)
            {
                document.getElementById("tinymce_editor").value = tinymce.activeEditor.setContent(text_content);//Восстанавливаем запомненное содержимое (если оно было)
            }
        }
    }//~function content_type_changed()
    

    
    
    
    //-------------- ДЕЙСВТВИЯ ПОСЛЕ ЗАГРУЗКИ СТРАНИЦЫ -------------->
    //Тип содержимого при загрузке страницы:
    var current_content_type = "<?php echo $content_record["content_type"]?>";
    
    //Выставляем текущий вариант типа содержимого:
    content_type_select = document.getElementById("content_type_select");//Селектор типов содержимого
    for(var j=0; j<content_type_select.options.length; j++)
    {
        if(content_type_select.options[j].value == current_content_type)
        {
            content_type_select.options[j].selected = true;
            break;
        }
    }
    content_type_changed();//Обработка выбора типа содержимого
    
    
    
    //Заполняем текущее содержимое:
	<?php
	if($content_record["content_type"] == "text")
	{
		$content = addcslashes(str_replace(array("\n","\r"), '', $content_record["content"]), "'");
		$content = str_replace("/", "\/", $content);
	}
	else if($content_record["content_type"] == "php")
	{
		$content = $content_record["content"];
	}
	?>
	var current_content = '<?php echo $content; ?>';
    if(current_content_type == "text")
    {
        console.log(current_content);
        document.getElementById("tinymce_editor").value = current_content;
    }
    else if(current_content_type == "php")
    {
        document.getElementById("php_file_path").value = current_content;
    }
    
    already_loaded = true;//Страница загружена
    </script>
        
    
    <?php
}
?>