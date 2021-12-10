<?php
/**
 * Страничный скрипт для страницы "Мои данные"
 * 
*/
defined('_ASTEXE_') or die('No access');

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");


if(DP_User::getUserId() == 0)
{
    echo "Необходимо авторизоваться";
}
else//Пользователь авторизован - выводим его данные
{
    $user_profile = DP_User::getUserProfile();//Получаем данные пользователя
	?>
	
	
	
	<!-- Здесь храним html для формы ввода кода подтверждения телефона -->
	<div id="phone_code_store" style="display:none;" class="hidden">
		<form method="GET" action="/users/confirm_contact">
			<input type="hidden" name="u_id" value="<?php echo DP_User::getUserId(); ?>" />
			<input type="hidden" name="type" value="phone" />
		
			<div class="input-group">
				<input value="" type="text" class="form-control" placeholder="Введите код подтверждения из SMS" name="code" id="code" />
				<span class="input-group-btn">
					<button class="btn btn-ar btn-primary" type="submit">Подтвердить</button>
				</span>
			</div>
		</form>
	</div>
	
	
    
    <table class="table">
	<?php
	//Регистрационный вариант
	$all_reg_variants_query = $db_link->prepare('SELECT COUNT(*) FROM `reg_variants`;');
	$all_reg_variants_query->execute();
	if( $all_reg_variants_query->fetchColumn() > 1)
	{
	    //Теперь запрос своего варианта
		$user_reg_variant_query = $db_link->prepare( 'SELECT * FROM `reg_variants` WHERE `id` = ?;' );
	    $user_reg_variant_query->execute( array($user_profile["reg_variant"]) );
	    $user_reg_variant_record = $user_reg_variant_query->fetch();
	    
	    echo "<tr> <td><b>Вариант регистрации</b></td> <td>".$user_reg_variant_record["caption"]."</td></tr>";
	}//в противном случае не выводим регистрационный вариант
	
	
	
	
	//Контакты email/phone
	?>
	<script>
	// ---------------------------------------------------------------------------------------------------
	//Настройка html в соответствии с контактом
	function set_contact_html(contact, contact_confirmed, type)
	{
		//Кнопки
		var button_confirm = '<div class="form-group"><a onclick="contacts_works_action_widgets(\''+type+'\', \'confirm\');" class="btn btn-ar btn-primary" href="javascript:void(0);"><i class="fa fa-check-square-o"></i> Подтвердить</a></div>';
		var button_set = '<div class="form-group"><a onclick="contacts_works_action_widgets(\''+type+'\', \'set\');" class="btn btn-ar btn-primary" href="javascript:void(0);" ><i class="fa fa-pencil"></i> Указать</a></div>';
		var button_change = '<div class="form-group"><a onclick="contacts_works_action_widgets(\''+type+'\', \'change\', \''+contact+'\', '+contact_confirmed+');" class="btn btn-ar btn-primary" href="javascript:void(0);"><i class="fa fa-pencil"></i> Сменить</a></div>';
		
		
		if( contact == '' )
		{
			//Контакт не указан
			document.getElementById(type+'_work').innerHTML = '<div class="form-inline"> <div class="form-group">Не указан </div> ' + button_set + '</div>';
		}
		else
		{
			//Контакт указан
			if( parseInt(contact_confirmed) == 1 )
			{
				//Подтвержден
				document.getElementById(type+'_work').innerHTML = '<div class="form-inline"> <div class="form-group">' + contact + ' <i class="fa fa-check-circle" style="color:#0A0;cursor:pointer;" title="Подтвержден"></i> </div> ' + button_change + '</div>';
			}
			else
			{
				//НЕ подтвержден
				document.getElementById(type+'_work').innerHTML = '<div class="form-inline"> <div class="form-group">' + contact + ' <i class="fa fa-exclamation-triangle" style="color:#F00;cursor:pointer;" title="Не подтвержден"></i> </div> '+button_confirm+' '+button_change + '</div>';
			}
		}
	}
	// ---------------------------------------------------------------------------------------------------
	//Получение виджетов при нажатии кнопок Указать, Подтвердить, Сменить
	function contacts_works_action_widgets(type, action, contact = '', contact_confirmed = 0)
	{
		if( action == 'set' )
		{
			document.getElementById( type+'_work' ).innerHTML = '<div class="form-inline"> <div class="form-group"> <input class="form-control" type="text" id="'+type+'_contact_input" /> </div> <div class="form-group"> <button onclick="contacts_works_execute(\''+type+'\', \'set\');" class="btn btn-ar btn-primary" style="margin-bottom:0!important;"><i class="fa fa-check"></i> Применить</button> </div> <div class="form-group"> <button onclick="set_contact_html(\'\', 0, \''+type+'\');" class="btn btn-ar btn-default">Отмена</button> </div> </div>';
			
			document.getElementById(type+'_contact_input').focus();
		}
		else if( action == 'confirm' )
		{
			contacts_works_execute(type, action);
		}
		else if( action == 'change' )
		{
			document.getElementById( type+'_work' ).innerHTML = '<div class="form-inline"> <div class="form-group"> <input class="form-control" type="text" id="'+type+'_contact_input" /> </div> <div class="form-group"> <button onclick="contacts_works_execute(\''+type+'\', \'change\');" class="btn btn-ar btn-primary"><i class="fa fa-check"></i> Применить</button> </div> <div class="form-group"> <button onclick="set_contact_html(\''+contact+'\', '+contact_confirmed+', \''+type+'\');" class="btn btn-ar">Отмена</button> </div> </div>';
			
			document.getElementById(type+'_contact_input').focus();
		}
	}
	// ---------------------------------------------------------------------------------------------------
	//Выполнение действий Указать, Подтвердить, Сменить
	function contacts_works_execute(type, action)
	{
		var contact = '';
		if( document.getElementById( type+'_contact_input' ) != undefined )
		{
			contact = document.getElementById( type+'_contact_input' ).value;
		}
		
		
		<?php
		//Защита от CSRF-атак
		$user_session = DP_User::getUserSession();
		?>
		
		
		jQuery.ajax({
			type: "POST",
			async: false, //Запрос синхронный
			url: "/content/users/ajax_contacts_works.php",
			dataType: "text",//Тип возвращаемого значения
			data: "type="+type+"&action="+action+"&contact="+contact+"&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
			success: function(answer){
				
				//console.log(answer);
				
				var answer_ob = JSON.parse(answer);
				
				//В случае ошибки - с виджетами ничего делать не нужно. Просто показываем сообщение с ошибкой
				
				//Если некорректный парсинг ответа
				if( typeof answer_ob.status === "undefined" )
				{
					alert("Неизвестная ошибка");
				}
				else
				{
					if( answer_ob.status == true )
					{
						//УСПЕХ
						/*
						На данный момент для всех действий (Указать, Подтвердить, Сменить) - в случае успешного выполнения - отправляется код подтверждения
						*/
						//Для email
						if( answer_ob.type == 'email' )
						{
							//Сообщение
							if( answer_ob.action == 'set' || answer_ob.action == 'confirm' )
							{
								alert('На указанный E-mail отправлена ссылка для подтверждения');
							}
							else if( answer_ob.action == 'change' )
							{
								alert('На новый E-mail отправлена ссылка для подтверждения. Старый E-mail будет использоваться до подтверждения нового');
							}
							
							//Переотображаем страницу (клиент пока увидит текущий статус контакта)
							location = '/users/profile';
						}
						//Для телефона
						else
						{
							//Сообщение
							if( answer_ob.action == 'set' || answer_ob.action == 'confirm' )
							{
								alert('На указанный Телефон отправлен код для подтверждения');
							}
							else if( answer_ob.action == 'change' )
							{
								alert('На новый Телефон отправлен код для подтверждения. Старый Телефон будет использоваться до подтверждения нового');
							}
							
							//Отображаем форму для кода
							document.getElementById('phone_work').innerHTML = document.getElementById('phone_code_store').innerHTML;
						}
					}
					else
					{
						alert(answer_ob.message);
					}
				}
				
			}
		});
	}
	// ---------------------------------------------------------------------------------------------------
	</script>
	<?php
	//Доступные способы связи
	$available_communications = DP_User::available_communications();//Получаем доступные способы связи
	//Телефон (если доступны все виды связи или только телефон)
	if( $available_communications["all"] || $available_communications["sms"] )
	{
		?>
        <tr> 
			<td><b>Телефон</b></td>
			<td id="phone_work"></td>
		</tr>
		<script>
		set_contact_html('<?php echo $user_profile['phone']; ?>', <?php echo (int)$user_profile['phone_confirmed']; ?>, 'phone');//Инициализация при загрузке страницы
		</script>
        <?php
	}
	//E-mail (1. Если доступны все виды связи. 2. Если доступны не все виды и при этом не доступен телефон (если включен SMS, но нет E-mail, то E-mail не показываем) )
	if( $available_communications["all"] ||  ( !$available_communications["all"] && !$available_communications["sms"] )  )
	{
		?>
        <tr> 
			<td><b>E-mail</b></td>
			<td id="email_work"></td>
		</tr>
		<script>
		set_contact_html('<?php echo $user_profile['email']; ?>', <?php echo (int)$user_profile['email_confirmed']; ?>, 'email');//Инициализация при загрузке страницы
		</script>
		<?php
	}
	

	
	//Перед выводом профиля получаем имена колонок таблицы users, чтобы отфильтровать их при выводе профиля
	$users_table_columns_query = $db_link->prepare("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE TABLE_NAME = 'users' AND `TABLE_SCHEMA` = '".$DP_Config->db."';");
	$users_table_columns_query->execute();
	$users_table_columns = array();
	while( $col_record =  $users_table_columns_query->fetch() )
	{
		$users_table_columns[] = $col_record['COLUMN_NAME'];
	}
   
	//Выводим поля профиля пользователя
    foreach($user_profile as $key => $value)
    {
		//Фильтруем все, что не относится к users_profiles и что не нужно показывать пользователю
        if( array_search($key, $users_table_columns ) !== false )
        {
            continue;
        }
        
        //Получаем название поля
        $parameter = "";
        if($key == "user_id")
        {
            $parameter = "ID пользователя";
        }
        else if($key == "groups")
        {
            $parameter = "Группы пользователя";
            $groups_names = "";
            //Получаем названия групп
            for($i=0; $i < count($value); $i++)
            {
				$group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `id` = ?;');
				$group_query->execute( array($value[$i]) );
                $group_record = $group_query->fetch();
                if($groups_names != "")
                {
                    $groups_names .= ";<br>";
                }
                $groups_names .= $group_record["value"];
            }
            $value = $groups_names;//Для вывода
        }
        else
        {
            //Название из таблицы регистрационны полей
			$field_caption_query = $db_link->prepare('SELECT * FROM `reg_fields` WHERE `name`=?;');
			$field_caption_query->execute( array($key) );
            $field_caption_record = $field_caption_query->fetch();
            $parameter = $field_caption_record["caption"];
        }
        
        ?>
        <tr> <td><b><?php echo $parameter?></b></td> <td><?php echo $value?></td></tr>
        <?php
    }//foreach($user_profile AS $key => $value)
    ?>
    
    </table>
    
    <a class="btn btn-ar btn-primary" href="/users/editform">Изменить мои данные</a>
    
<?php
}//else//Пользователь не авторизован
?>