<?php
//Скрипт страницы создания/редактирования финансовых операций
defined('_ASTEXE_') or die('No access');



if( isset($_POST['action']) )
{
	if( $_POST['action'] == 'save_operation' )
	{
		try
		{
			//Старт транзакции
			if( ! $db_link->beginTransaction()  )
			{
				throw new Exception("Не удалось стартовать транзакцию");
			}
			
			//Проверки входных пераметров
			if( !isset($_POST['operation_id']) || !isset($_POST['income']) || !isset($_POST['name']) )
			{
				throw new Exception("Не хватает параметров");
			}
			if( ( $_POST['income']!=0 && $_POST['income']!=1 ) || empty($_POST['name']) )
			{
				throw new Exception("Некорректные параметры");
			}
			
			$operation_id = $_POST['operation_id'];
			$income = $_POST['income'];
			$name = $_POST['name'];
			
			
			if( $operation_id == 0 )
			{
				//Создаем новый вид операции
				$success_message = "Операция создана";
				
				if( !$db_link->prepare( 'INSERT INTO `shop_accounting_codes` (`income`, `name`, `manual_available`) VALUES (?,?,?);' )->execute( array($income, $name, 1) ) )
				{
					throw new Exception("Ошибка добавления учетной записи вида операции");
				}
				
				$operation_id = $db_link->lastInsertId();
				
				if( !$operation_id )
				{
					throw new Exception("Ошибка определения ID нового вида операций. Вид операции не создан");
				}
			}
			else
			{
				//Редактируем существующую операцию
				$success_message = "Операция отредактирована";
				
				
				//Перед редактированием проверяем - что вид операции не является системный или что операции данного вида еще не совершались
				$operation_query = $db_link->prepare('SELECT *, (SELECT COUNT(*) FROM `shop_users_accounting` WHERE `operation_code` = `shop_accounting_codes`.`id` ) AS `used` FROM `shop_accounting_codes` WHERE `id` = ?;');
				$operation_query->execute( array($operation_id) );
				$operation = $operation_query->fetch();
				
				if( $operation == false )
				{
					throw new Exception("Вид операции не найден");
				}
				if( $operation['system'] )
				{
					throw new Exception("Нельзя редактировать системный вид операции");
				}
				if( $operation['used'] > 0 )
				{
					throw new Exception("Нельзя редактировать вид операции, которые ранее совершались (т.е. отражены в балансах покупателей)");
				}
				
				if( !$db_link->prepare('UPDATE `shop_accounting_codes` SET `income` = ?, `name` = ? WHERE `id` = ?;')->execute( array($income, $name, $operation_id) ) )
				{
					throw new Exception("Ошибка редактирования учетной записи вида операции");
				}
			}
		}
		catch (Exception $e)
		{
			//Откатываем все изменения
			$db_link->rollBack();
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/finance/operations_editor/operation?operation_id=<?php echo $operation_id; ?>&error_message=<?php echo urlencode($e->getMessage()); ?>";
			</script>
			<?php
			exit;
		}

		//Дошли до сюда, значит выполнено ОК
		$db_link->commit();//Коммитим все изменения и закрываем транзакцию
		?>
		<script>
			location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/finance/operations_editor/operation?operation_id=<?php echo $operation_id; ?>&success_message=<?php echo urlencode($success_message); ?>";
		</script>
		<?php
		exit;
	}
}
else//Действий нет - выводим страницу
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
				
				
				<?php
				//Сохранить
				print_backend_button( array("background_color"=>"#63ce1c", "fontawesome_class"=>"fas fa-save", "caption"=>"Сохранить", "url"=>"javascript:void(0);", "onclick"=>"save_form_submit();") );
				?>
				
				
				
				<?php
				//Редактор видов операций
				print_backend_button( array("background_color"=>"#3498db", "fontawesome_class"=>"fas fa-align-justify", "caption"=>"Вернуться в редактор видов операций", "url"=>"/".$DP_Config->backend_dir."/shop/finance/operations_editor") );
				?>

				
				<?php
				//Вернуться обратно в "Счета покупателей"
				print_backend_button( array("background_color"=>"#27ae60", "fontawesome_class"=>"fas fa-money-check-alt", "caption"=>"Вернуться в счета покупателей", "url"=>"/".$DP_Config->backend_dir."/shop/finance/account_operations") );
				?>

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
				
				
			</div>
		</div>
	</div>
	
	
	
	
	
	
	<?php
	$operation_id = 0;
	$income = 1;
	$name = '';
	$system = 0;
	$used = 0;
	if( isset($_GET['operation_id']) )
	{
		$operation_query = $db_link->prepare('SELECT *, (SELECT COUNT(*) FROM `shop_users_accounting` WHERE `operation_code` = `shop_accounting_codes`.`id` ) AS `used` FROM `shop_accounting_codes` WHERE `id` = ?;');
		$operation_query->execute( array($_GET['operation_id']) );
		$operation = $operation_query->fetch();
		
		if( $operation != false )
		{
			$operation_id = $operation['id'];
			$income = $operation['income'];
			$name = $operation['name'];
			$system = $operation['system'];
			$used = $operation['used'];
		}
	}
	?>
	
	
	<form name="operation_save_form" method="POST">
	<input type="hidden" name="action" value="save_operation" />
	<input type="hidden" name="operation_id" value="<?php echo $operation_id; ?>" />
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Параметры вида финансовой операции
			</div>
			<div class="panel-body">
				
				<?php
				if( $operation_id > 0 )
				{
					?>
					<div class="form-group">
						<label class="col-sm-2 control-label">ID (код операции)</label>
						<div class="col-sm-10">
							<?php echo $operation_id; ?>
						</div>
					</div>
					<div class="hr-line-dashed"></div>
					<?php
				}
				if( $system )
				{
					?>
					
					<div style="color:#FFF;background-color:#e74c3c;border-radius:3px;padding:6px 12px;font-weight:normal;">Данный вид финансовой операции является системным. Его нельзя редактировать</div>
					
					<div class="hr-line-dashed"></div>
					<?php
				}
				if( $used > 0 )
				{
					?>
					
					<div style="color:#FFF;background-color:#e74c3c;border-radius:3px;padding:6px 12px;font-weight:normal;">Операции данного вида уже совершались (отражены в балансах покупателей). Данный вид операций нельзя редактировать</div>
					
					<div class="hr-line-dashed"></div>
					<?php
				}
				?>
			
				
				<div class="form-group">
					<label class="col-sm-2 control-label">Направление</label>
                    <div class="col-sm-10">
						<select class="form-control m-b" name="income" id="income">
							<option value="1">Приход</option>
							<option value="0">Расход</option>
						</select>
						<script>
						document.getElementById('income').value = '<?php echo $income; ?>';
						</script>
                    </div>
                </div>
			
				<div class="hr-line-dashed"></div>
				
				<div class="form-group">
					<label class="col-sm-2 control-label">Наименование</label>

                    <div class="col-sm-10">
						<input type="text" placeholder="Укажите наименование операции" name="name" id="name" value="<?php echo $name; ?>" class="form-control" />
					</div>
                </div>
				
			
			</div>
		</div>
	</div>
	
	
	
	</form>
	
	<script>
	function save_form_submit()
	{
		<?php
		if($system)
		{
			?>
			alert('Нельзя редактировать системные виды операций');
			return;
			<?php
		}
		?>
		
		
		<?php
		if($used > 0)
		{
			?>
			alert('Нельзя редактировать виды операций, которые совершались (т.е. отражены на балансах пользователей)');
			return;
			<?php
		}
		?>
		
		if( document.getElementById('name').value == '' )
		{
			alert('Заполните наименование вида операции');
			return;
		}
		
		
		document.forms['operation_save_form'].submit();
	}
	</script>
	
	
	<?php
}



?>