<?php
// Блокнот
defined('_ASTEXE_') or die('No access');


//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();

$garage_id = (int)$_GET['garage'];

if($user_id > 0){
	
		if($_SERVER["REQUEST_METHOD"] === 'POST'){
			
			$error_message = '';
			$success_message = '';
			
			switch($_POST['action']){
				case 'add' :
					$brend = trim(htmlspecialchars(strip_tags($_POST['brend'])));
					$article = trim(htmlspecialchars(strip_tags($_POST['article'])));
					$name = trim(htmlspecialchars(strip_tags($_POST['name'])));
					$exist = (int) trim($_POST['exist']);
					$price = (float) trim($_POST['price']);
					$comment = trim(htmlspecialchars(strip_tags($_POST['comment'])));
					
					if(!empty($article)){
						
						if($db_link->prepare('INSERT INTO `shop_docpart_garage_notepad` (`user_id`, `garage_id`, `brend`, `article`, `name`, `exist`, `price`, `comment`) VALUES (?,?,?,?,?,?,?,?);')->execute( array($user_id, $garage_id, $brend, $article, $name, $exist, $price, $comment) ))
						{
							$success_message = 'Позиция добавлена в блокнот';
						}else{
							$error_message = 'Ошибка добавления позиции';
						}
						
					}else{
						$error_message = 'Поле Артикул обязательно для заполнения';
					}
				break;
				case 'edit' :
					$id = (int)$_POST['id'];
					$brend = trim(htmlspecialchars(strip_tags($_POST['brend'])));
					$article = trim(htmlspecialchars(strip_tags($_POST['article'])));
					$name = trim(htmlspecialchars(strip_tags($_POST['name'])));
					$exist = (int) trim($_POST['exist']);
					$price = (float) trim($_POST['price']);
					$comment = trim(htmlspecialchars(strip_tags($_POST['comment'])));
					
					if(!empty($article)){
						
						if($db_link->prepare('UPDATE `shop_docpart_garage_notepad` SET `brend` = ?, `article` = ?, `name` = ?, `exist` = ?, `price` = ?, `comment` = ? WHERE `id` = ? AND `user_id` = ? AND `garage_id` = ?;')->execute( array($brend, $article, $name, $exist, $price, $comment, $id, $user_id, $garage_id) ))
						{
							$success_message = 'Позиция изменена';
						}else{
							$error_message = 'Ошибка изменения записи';
						}
						
					}else{
						$error_message = 'Поле Артикул обязательно для заполнения';
					}
				break;
				case 'del' :
					$id = (int)$_POST['id'];
					if($db_link->prepare('DELETE FROM `shop_docpart_garage_notepad` WHERE `id` = ? AND `user_id` = ? AND `garage_id` = ?')->execute( array($id, $user_id, $garage_id) ))
					{
						$success_message = 'Позиция удалена';
					}else{
						$error_message = 'Ошибка удаления';
					}
				break;
			}
			
			$location_url = '/garazh/bloknot';
			?>
			<script>
				location="<?=$location_url?>?garage=<?=$garage_id;?>&error_message=<?=$error_message;?>&success_message=<?=$success_message;?>";
			</script>
			<?php
			
		}else{
			require_once($_SERVER["DOCUMENT_ROOT"]."/content/general/actions_alert.php");
			?>
			
			<div class="row">
			<div class="col-lg-12" style="margin-bottom:20px;">
				<form method="POST">
					<input type="hidden" name="action" value="add"/>
					<div class="col-lg-2">
						<label>Бренд</label>
						<input class="form-control" name="brend"/>
					</div>
					<div class="col-lg-2">
						<label>Артикул</label>
						<input class="form-control" name="article"/>
					</div>
					<div class="col-lg-4">
						<label>Наименование</label>
						<input class="form-control" name="name"/>
					</div>
					<div class="col-lg-2">
						<label>Количество</label>
						<input class="form-control" name="exist"/>
					</div>
					<div class="col-lg-2">
						<label>Цена</label>
						<input class="form-control" name="price"/>
					</div>
					
					<div class="col-lg-12">
						<label>Комментарий</label>
						<textarea rows="5" class="form-control" name="comment"></textarea>
					</div>
					
					<div class="col-lg-12" style="text-align:right;">
						<button style="margin-top:10px;" type="submit" class="btn btn-ar btn-primary"><i class="fa fa-plus-square" aria-hidden="true"></i> Добавить<span class="hidden-xs"> позицию</span></button>
					</div>
				</form>
			</div>
			</div>
			
			
			<div class="col-lg-12" style="margin-bottom:20px; overflow-x: auto;">
			<?php
			$query = $db_link->prepare('SELECT * FROM `shop_docpart_garage_notepad` WHERE `user_id` = ? AND `garage_id` = ?;');
			$query->execute( array($user_id, $garage_id) );
			
			$notepad_items = array();
			while($record = $query->fetch())
			{
				$notepad_items[] = $record;
			}
			
			if(!empty($notepad_items))
			{
				echo '<form id="form_edit" method="POST" style="display:none;">
						<input type="hidden" name="action" value="edit"/>
						<input type="hidden" id="inp_id" name="id" value=""/>
						<input type="hidden" id="inp_brend" name="brend"/>
						<input type="hidden" id="inp_article" name="article"/>
						<input type="hidden" id="inp_name" name="name"/>
						<input type="hidden" id="inp_exist" name="exist"/>
						<input type="hidden" id="inp_price" name="price"/>
						<textarea type="hidden" id="inp_comment" name="comment"></textarea>
					  </form>
					  
					  <table class="table">';
				echo '<tr>';
						echo '<th>Бренд</th>';
						echo '<th>Артикул</th>';
						echo '<th>Наименование</th>';
						echo '<th>Комментарий</th>';
						echo '<th>Количество</th>';
						echo '<th>Цена</th>';
					echo '</tr>';
				foreach($notepad_items as $record)
				{
					echo '<tr style="font-size:12px;">';
					echo '<td>
							<span class="show_text_garage_'. $record['id'] .'">'. $record['brend'] .'</span>
							<input id="edit_brend_'. $record['id'] .'" style="display:none;" class="form-control edit_text_garage_'. $record['id'] .'" value="'. $record['brend'] .'"/>
						 </td>';
						 
						 $article_link = $record['article'];
						 if(!empty($article_link)){
							 $article_link = '<a target="_blank" href="/shop/part_search?article='.$article_link.'"><i class="fa fa-search" aria-hidden="true"></i> '.$article_link.'</a>';
						 }
						 
					echo '<td>
							<span class="show_text_garage_'. $record['id'] .'">'. $article_link .'</span>
							<input id="edit_article_'. $record['id'] .'" style="display:none;" class="form-control edit_text_garage_'. $record['id'] .'" value="'. $record['article'] .'"/>
						 </td>';
					echo '<td>
							<span class="show_text_garage_'. $record['id'] .'">'. $record['name'] .'</span>
							<input id="edit_name_'. $record['id'] .'" style="display:none;" class="form-control edit_text_garage_'. $record['id'] .'" value="'. $record['name'] .'"/>
						 </td>';
					echo '<td>
							<span class="show_text_garage_'. $record['id'] .'">'. $record['comment'] .'</span>
							<input id="edit_comment_'. $record['id'] .'" style="display:none;" class="form-control edit_text_garage_'. $record['id'] .'" value="'. $record['comment'] .'"/>
						 </td>';
					echo '<td>
							<span class="show_text_garage_'. $record['id'] .'">'. $record['exist'] .'</span>
							<input id="edit_exist_'. $record['id'] .'" style="display:none;" class="form-control edit_text_garage_'. $record['id'] .'" value="'. $record['exist'] .'"/>
						 </td>';
					echo '<td>
							<span class="show_text_garage_'. $record['id'] .'">'. $record['price'] .'</span>
							<input id="edit_price_'. $record['id'] .'" style="display:none;" class="form-control edit_text_garage_'. $record['id'] .'" value="'. $record['price'] .'"/>
						 </td>';
					echo '</tr>';
					
					echo '<tr>';	
					echo '<td colspan="6" style="text-align:right; border:none; padding-top:10px;">
							
							<a class="btn btn-sm btn-primary show_text_garage_'. $record['id'] .'" href="javascript:void(0);" onClick="edit('. $record['id'] .');"><i class="fa fa-edit"></i> Изменить</a>
							
							<a class="btn btn-sm btn-danger show_text_garage_'. $record['id'] .'" href="javascript:void(0);" onClick="del('. $record['id'] .');"><i class="fa fa-trash" aria-hidden="true"></i> Удалить</a>
							
							
							<a href="javascript:void(0);" onClick="save('. $record['id'] .');" style="display:none;" class="btn btn-sm btn-primary edit_text_garage_'. $record['id'] .'"><i class="fa fa-save" aria-hidden="true"></i> Сохранить</a>
							
							<a href="javascript:void(0);" onClick="cancel('. $record['id'] .');" style="display:none;" class="btn btn-sm btn-info edit_text_garage_'. $record['id'] .'"><i class="fa fa-sign-out" aria-hidden="true"></i> Отменить</a>
						 </td>';
					echo '</tr>';
				}
				echo '</table>';
			}else{
				echo 'Позиций нет';
			}
			?>
			</div>
			<form id="form_del" method="POST" class="hidden">
				<input type="hidden" name="action" value="del"/>
				<input type="hidden" id="input_del" name="id" value=""/>
			</form>
			<script>
			function del(id){
				if(confirm('Вы действительно хотите удалить позицию?')){
					document.getElementById('input_del').value = id;
					document.getElementById('form_del').submit();
				}
			}
			function edit(id){
				$(".show_text_garage_"+id).css('display', 'none');
				$(".edit_text_garage_"+id).css('display', 'inline');
			}
			function cancel(id){
				$(".show_text_garage_"+id).css('display', 'inline');
				$(".edit_text_garage_"+id).css('display', 'none');
			}
			function save(id){
				document.getElementById('inp_id').value = id;
				document.getElementById('inp_brend').value = document.getElementById('edit_brend_'+id).value;
				document.getElementById('inp_article').value = document.getElementById('edit_article_'+id).value;
				document.getElementById('inp_name').value = document.getElementById('edit_name_'+id).value;
				document.getElementById('inp_comment').value = document.getElementById('edit_comment_'+id).value;
				document.getElementById('inp_exist').value = document.getElementById('edit_exist_'+id).value;
				document.getElementById('inp_price').value = document.getElementById('edit_price_'+id).value;
				document.getElementById('form_edit').submit();
			}
			</script>
			<?php
		}
	
}else{
	?>
    <p>На данной странице отображается блокнот покупателей</p>
    	
	<div class="panel panel-primary">
	<?php
	//Единый механизм формы авторизации
	$login_form_postfix = "garage";
	require($_SERVER["DOCUMENT_ROOT"]."/modules/login/login_form_general.php");
	?>
	</div>
    <?php
}
?>