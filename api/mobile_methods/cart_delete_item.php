<?php
//������ ��� ������ �������� ������� �������
defined('DOCPART_MOBILE_API') or die('No access');


//�������� �������� ������
$params = $request["params"];
$login = $params["login"];
$session = $params["session"];

//������� ��������� ������� ������ ������������
$user_query = $db_link->prepare('SELECT `user_id` FROM `users` WHERE `main_field` = ?;');
$user_query->execute( array($login) );
$user_record = $user_query->fetch();
if( $user_record == false )
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "User not found";
	exit(json_encode($answer));
}

$user_id = $user_record["user_id"];

//������ ��������� ������� ������
$session_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `user_id` = ? AND `session` = ?;');
$session_query->execute( array($user_id, $session) );
if( $session_query->fetchColumn() > 0 )
{
	//������ ���� - ��������
	$cart_record_id = $params["cart_record_id"];
	
	
	//��������� �������������� ������ � ������������
	$check_item_query = $db_link->prepare('SELECT `user_id` FROM `shop_carts` WHERE `id` = ?;');
	$check_item_query->execute( array($cart_record_id) );
	$check_item_record = $check_item_query->fetch();
	if( $check_item_record == false )
	{
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = "��� ����� ������� �������";
		exit(json_encode($answer));
	}
	
	if($check_item_record["user_id"] != $user_id)
	{
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = "������ �������������� ������� �������";
		exit(json_encode($answer));
	}
	
	
	//����� �� ���� - ������ ����� ������� �������
	$request_object = array();
	$request_object["tech_key"] = $DP_Config->tech_key;
	$request_object["records_to_del"] = array($cart_record_id);
	
	
	//�������� ��� �� ������, ��� � � ������� �� �����
	$postdata = http_build_query(
		array(
			'request_object' => json_encode($request_object)
		)
	);//���������
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $DP_Config->domain_path."content/shop/order_process/ajax_delete_cart_record.php");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	$curl_result = curl_exec($curl);
	
	
	curl_close($curl);
	$curl_result = json_decode($curl_result, true);
	
	
	
	//���������� � ��������� ���������
	if( $curl_result["status"] == true )
	{
		//������ �����
		$answer = array();
		$answer["status"] = true;
		$answer["message"] = "Ok";
		$answer["handler_answer"] = $curl_result;
		exit(json_encode($answer));
	}
	else
	{
		//���� ��� �������� - �������� ������ � ���������� ������ (����� �� ������� �������� JSON)
		if( $curl_result["message"] != NULL )
		{
			//������ ����� - �������� ������
			$answer = array();
			$answer["status"] = false;
			$answer["handler_answer"] = $curl_result;
			$answer["message"] = "������ ����������� �� �������. �������� ������";
			$answer["readable_error"] = true;//�������� ������ $curl_result
			exit(json_encode($answer));
		}
		else
		{
			//������ ����� - ���������� ������
			$answer = array();
			$answer["status"] = false;
			$answer["message"] = "������ ����������� �� �������. �� �������� ������";
			$answer["readable_error"] = false;//�� �������� ������
			exit(json_encode($answer));
		}
	}
}
else
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "No session";
	exit(json_encode($answer));
}
?>