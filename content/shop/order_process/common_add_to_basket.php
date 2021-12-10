<?php
/**
 * Единый скрипт фунции добавления в товаров в корзину
*/
?>

<script>
//Обработка кнопки "Купить"
function purchase_action(div_id)
{
    var product_object_div = document.getElementById(div_id);
    
    console.log(product_object_div);
    
    var product_object = new Object;//Объект продукта, который добавляем в корзину
    product_object.product_type = 1;//Каталожный продукт
    product_object.product_id = product_object_div.getAttribute("product_id");
    product_object.office_id = product_object_div.getAttribute("office_id");
    product_object.storage_id = product_object_div.getAttribute("storage_id");
    product_object.storage_record_id = product_object_div.getAttribute("storage_record_id");
    product_object.price = product_object_div.getAttribute("price");
    product_object.check_hash = product_object_div.getAttribute("check_hash");
	
	
    //Данные в корзину можно класть сразу целым перечнем - поэтому приводим к массиву
    var product_objects = new Array;
    product_objects.push(product_object);
	
    jQuery.ajax({
        type: "POST",
        async: false, //Запрос синхронный
        url: "/content/shop/order_process/ajax_add_to_basket.php",
        dataType: "json",//Тип возвращаемого значения
        data: "product_objects="+encodeURI(JSON.stringify(product_objects)),
        success: function(answer)
        {
            if(answer.status == true)
            {
                //alert("Добавлено");
                //location = "/shop/cart";
				
				updateCartInfo();//Обновление корзины снизу
				showAdded();//Показываем лэйбл снизу
            }
            else
            {
                if(answer.code == "already")
                {
                    alert("Товар уже был добавлен ранее");
                }
                else
                {
                    alert("Ошибка добавления в корзину");
                }
            }
        }
    });
}
</script>