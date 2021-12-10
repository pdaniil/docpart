<?php
//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");



//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");


//Проверяем право менеджера
if( ! DP_User::isAdmin())
{
	exit("Forbidden");
}
?>


<head>
	<title>История изменений</title>
</head>

<style>
*
{
	font-family:Calibri;
}

div
{
	border-top:1px dotted #000;
	padding:5px;
	margin-top:10px;
}
h2
{
	display:block;
	padding:5px;
	background-color:#C33;
	color:#FFF;
}
</style>


<body>


<h2>29 ноября 2018</h2>

<div>Добавлена страница "Изменения Docpart"</div>

<div>Обновлен платежный протокол Альфабанка</div>

<div>Новые поставщики:
	<ul>
		<li>Форвард (СПБ и ЕКБ)</li>
		<li>adkulan.kz</li>
		<li>alfa-nw.ru</li>
		<li>autokontinent.ru</li>
		<li>avtogut.ru</li>
		<li>v01.ru (mparts)</li>
		<li>euroauto.ru (! Требуется персональная настройка сервера)</li>
	</ul>
</div>




<div>Обновлены поставщики:
	<ul>
		<li>Росско (доработан SAO-заказ, если у клиента указана доставка по адресу)</li>
		<li>Москворечье (Добавлен SAO-заказ, опция выбора способа доставки заказа в настроку склада, добавлено получение наименования деталей в get_manufacturers.php)</li>
		<li>API abcp (добавлен двухэтапный поиск, добавлено SAO - заказ и проверка состояния)</li>
		<li>Фаворит (замен хост для API)</li>
		<li>Иверс</li>
	</ul>
</div>


<div>Добавлена кнопка показа своего IP-адреса для проценки.</div>


<div>Доработки с управлением пользователями в панели управления:
	<ul>
		<li>дополнительные поля регистрации теперь можно добавлять в фильтры в менеджер пользователей</li>
		<li>дополнительные поля регистрации теперь можно добавлять в колонки в менеджер пользователей</li>
		<li>при создании/редактировании дополнительных полей регистрации происходит проверка ключа на значения, зарезервированные системой</li>
		<li>при создании/редактировании дополнительных полей регистрации происходит проверка заполнения названия</li>
		<li>при создании/редактировании дополнительных полей регистрации теперь есть проверка на частую логическую ошибку пользователей (поставить галку "Обязательно", и при этом не поставить "Показывать")</li>
		<li>на странице редактирования пользователя в панели управления теперь нельзя сохранить, если пользователь не привязан ни к одной из групп</li>
		<li>на странице редактирования пользователя в панели управления теперь нельзя сменить группу админа у самого себя</li>
		<li>добавлена блокировка удаления учетной записи админа (т.е. нельзя теперь удалить самого себя)</li>
	</ul>
</div>



<div>Модернизирован алгоритм назначения наценок:
	<ul>
		<li>стал работать быстрее</li>
		<li>теперь можно выбирать диапазоны закупочных цен с большими числовыми значениями</li>
	</ul>
</div>

<div>Добавлен предпросмотр прайс-листов в панели управления в менеджере прайс-листов - для того, чтобы админ мог сразу убедиться в корректности загруженного файла</div>


<div>Добавлен общий механизм подсказок в панель управления и добавлены первые подсказки на страницу "Позиции заказов" и "Заказы". По мере развития платформы - будут добавляться подсказки и на других страницах</div>


<div>Доработка страницы "Позиции заказов" для админов:
	<ul>
		<li>вместо ID клиента для каждой позиции теперь выводится компонуемая строка, которая содержит все поля регистрации пользователя</li>
		<li>поиск позиций по поставщику</li>
		<li>добавлена яркая кнопка перехода на страницу заказа</li>
		<li>добавлено обозначение способа получения</li>
		<li>добавлено отображение суммарных показателей по позициям</li>
		<li>добавлены подсказки для админа</li>
	</ul>
</div>

<div>Доработана страница "Заказы":
	<ul>
		<li>отображение суммарных показателей</li>
		<li>вместо ID клиента для каждого заказа теперь выводится компонуемая строка, которая содержит все поля регистрации пользователя</li>
		<li>добавлены подсказки для админа</li>
	</ul>
</div>






<h2>07 марта 2019</h2>
<div>Доработана проценка API-поставщиков:
	<ul>
		<li>опрос поставщиков теперь осуществлятся параллельно, что значительно уменьшает общее время опроса</li>
		<li>поставщиков можно делить на группы опроса (группы опрашиваются последовательно). Эта возможность нужна, чтобы поставщиков, которые отвечают быстро - опрашивать в первую очередь, а, поставщиков, которые отвечают медленнее - опрашивать во вторую очередь и т.д.</li>
		<li>доработан страничный скрипт с отображением результата опроса поставщиков - стал работать быстрее</li>
	</ul>
</div>

<div>
Добавлена функция "Блокнот для гаража"
</div>


<div>
Сразу при установке платформы заполнена таблица синонимов производителей наиболее часто встречающимися синонимами
</div>



<div>
Обновлен модуль корзины в header:
	<ul>
		<li>Теперь модуль корзины указывает количество позиций в корзине и их стоимость</li>
		<li>При нажатии на модуль происходит перенаправление пользователя на страницу корзины</li>
	</ul>
</div>


<div>
Добавлена ТК "СДЭК"
</div>


<div>
Поключены новые поставщики:
	<ul>
		<li>4tochki.ru</li>
		<li>crimea-drive.ru</li>
		<li>dyadko.ru</li>
	</ul>
</div>



<div>
При сохранении настроек подключения к поставщикам, скрипт обрабатывает наиболее часто встречаемые недочеты пользовательского ввода
</div>


<div>
Исправлены все баги, которые были выявлены в предыдущей версии
</div>






<h2>24 апреля 2019</h2>
<div>Добавлена функция логирования API поставщиков, благодаря которой теперь значительно легче решать возможные проблемы при подключении API</div>


<div>
	Обновлено подключение к API поставщиков:
	<ul>
		<li>Автосоюз-юг</li>
		<li>crimea-drive.ru</li>
		<li>EMEX (Добавлен двухэтапный поиск. Добавлен запрос состояния заказа. Изменены скрипты добавления в корзину и создания заказа.)</li>
		<li>Армтек (Добавлено указание минимального заказа)</li>
		<li>Автоевро (Добавлено указание минимального заказа)</li>
		<li>Росско (Добавлено указание минимального заказа)</li>
		<li>Шате-М</li>
		<li>gpi24.ru (обновлен API поставщика)</li>
		<li>impex-jp.com (обновлен API поставщика - теперь требуется ключ для подключения к API)</li>
	</ul>
</div>


<div>
	Новые поставщики, подключаемые по API:
	<ul>
		<li>Планета Авто (b2b.planetavto.ru)</li>
	</ul>
</div>



<div>
	Новые SMS-операторы для отправки сообщений с сайта:
	<ul>
		<li>Smsimple (smsimple.ru)</li>
	</ul>
</div>


<div>
Добавлена совместимость функции API-проценки для старой версии PHP 5.3 (некоторым клиентам еще требуется)
</div>



<div>
	Доработки встроенного каталога товаров:
	<ul>
		<li>в редакторе дерева категорий товаров теперь можно автоматически добавлять базовые свойства (Производитель и Артикул)</li>
		<li>на странице редактирования карточки товара, свойство Артикул (типа "Текст") автоматически очищается от лишних знаков при сохранении и приводится в верхний регистр</li>
		<li>на странице менеджера линейных списков добавлена защита от удаления линейного списка с ID 10 (т.к. он используется при автоматическом добавлении базовых свойств товаров в редакторе дерева категорий)</li>
		<li>добавлены подсказки на страницу "Редактор категорий товаров"</li>
		<li>добавлены подсказки на страницу "Редактирование одного линейного списка"</li>
	</ul>
</div>






<h2>30 июня 2019</h2>
<div>Оформление заказа теперь идет через транзакцию</div>
<div>Обновлены скрипты асинхронной проценки</div>
<div>Добавлены индексы на несколько таблиц в базе данных</div>
<div>Появились подсказки на странице настроек в панели управления</div>
<div>Скорректирован скрипт модуля меню</div>
<div>Улучшена SEO-оптимизация базовой структуры сайта</div>
<div>Повышена безопасность системы регистрации пользователей</div>








<h2>10 сентября 2019</h2>

<div>
	Добавлены новые поставщики:
	<ul>
		<li>Froza (froza.ru), двухэтапный поиск, SAO-добавить в козину, SAO-заказ, SAO-проверка состояния</li>
		<li>Колеса Даром (kolesa-darom.ru), поиск без учета производителя</li>
		<li>lekoparts.ru (lekoparts.ru), двухэтапный поиск</li>
		<li>ml-auto.ru (ml-auto.ru), двухэтапный поиск</li>
		<li>Optipart (optipart.ru), двухэтапный поиск</li>
		<li>77volvo.ru (77volvo.ru), двухэтапный поиск</li>
		<li>AUTOCODE (autocode.ru), двухэтапный поиск</li>
	</ul>
</div>


<div>
	Добавлен новый настраиваемый модуль печати документов<br>
	Доступные документы для печати:
	<ul>
		<li>Товарный чек</li>
		<li>счет на оплату</li>
		<li>ТОРГ-12</li>
		<li>УПД</li>
	</ul>
</div>

<div>Добавлена усиленная проверка сессии пользователя</div>


<div>
Добавлен новый способ отображения встроенного каталога товаров - через пагинацию (постраничное отображение).<br>
При этом, сохранен старый способ отображения каталога - через асинхронную загрузку.<br>
Выбрать способ отображения можно в панели управления в настройках.
</div>

<div>
	В редакторе материалов "Дерево материалов" добавлена защита от изменения и удаления системных материалов:
	<ul>
		<li>при перетаскивании элементов (рекурсивная проверка)</li>
		<li>при удалении элементов (рекурсивная проверка)</li>
		<li>при снятии с публикации</li>
		<li>при переходе на редактирование</li>
		<li>при двойном щелчке по узлу (для переименования)</li>
		<li>при изменении параметров (title, description, права доступа и т.д.)</li>
	</ul>
	Включать и отключать защиту можно в настройках в панели управления<br>
	Добавлена защита от изменения и удаления системных материалов в редакторе "No tree" (в менеджере материалов и на странице создания/редактирования материала)
</div>



<div>
Добавлен новый шаблон:
	<ul>
		<li>с возможнотью выбирать абсолютно любой цвет</li>
		<li>с выпадающим меню для встроенного каталога товаров</li>
	</ul>
</div>


<div>Доработана таблица кроссов. Добавлена кнопка удаления с учетом поиска и исправлены некоторые ошибки.</div>
<div>Исправлена ошибка переключения между страницами в менеджере прайс листов</div>
<div>Доработаны скрипты связанные с модулем выбора способа доставки. Исправлены ошибки возникающие в браузерах на платформе IOS</div>
<div>Изменен текст наличия товара при оформлении заказа, что бы клиент не думал что можно забрать заказ сразу после оформления</div>
<div>Исправлена ошибка увеличения или уменьшения требуемого количесва не соответствующего минимальной партии товара</div>
<div>В панели управления добавлена ссылка на текст "Панель управления", иначе ограниченные в правах менеджеры не могут попасть на главную так как отключен модуль левого меню. Так же в шапку добавлены кнопки для быстрого перехода на часто используемые разделы. Увеличен интервал обновления количества непросмотренных заказов до 5 минут, т.к. он вызывал нагрузку на хостинге.</div>
<div>Добавлено множество индексов в БД - скорость работы большинства страниц заметно повысилась</div>
<div>Добавлена возможность создавать страницы ошибок 404 и 403</div>
<div>Добавлена библиотека каталога neoriginal.ru</div>
<div>Появилось ЧПУ при проценке товаров по артикулу</div>




<h2>14 ноября 2019</h2>

<div>API мобильного приложения доработано под новую, усиленную проверку сессии пользователя</div>

<div>При настройке подключения поставщика по API, доработано отображение названий поставщиков в селекторе выбора:
	<ul>
		<li>алфавитный порядок</li>
		<li>единый формат написания названий поставщиков</li>
	</ul>
</div>



<div>Доработано подключение к API поставщиков (исправления и изменения):
	<ul>
		<li>ABS</li>
		<li>Пролига</li>
		<li>Optipart</li>
		<li>MPARTS</li>
		<li>Smartec</li>
		<li>Фаворит</li>
	</ul>
</div>



<div>Подключены новые поставщики по API:
	<ul>
		<li>omega.page</li>
		<li>busmarket.group</li>
		<li>the-parts.ru</li>
		<li>tormoza32.ru</li>
	</ul>
</div>

<div>Реализован API платформы parts-soft.ru. Таким образом, можно подключать по API всех поставщиков, работающих на данной платформе.</div>


<div>Подбавлен новый SMS-оператор Rocketsms.BY</div>


<div>Исправлены все выявленные ошибки</div>







<h2>12 декабря 2019</h2>




<div>Добавлена большая интеграция (платная) программной оболочки для работы с онлайн-кассами по Закону 54-ФЗ (настраивается индивидуально под каждого клиента):
	<ul>
		<li>единый класс ККТ, который используется при ручном создании чеков, а также, который можно использовать для автоматического создания чека при платеже через эквайринг</li>
		<li>единое модальное окно, которое позволяет создавать чеки вручную с произвольными позициями, а также, с позициями из заказов</li>
		<li>отдельное модальное окно для создания чеков коррекции</li>
		<li>страница корневого раздела онлайн-касс и чеков с настройкой значений по умолчанию для параметров, которые используются при ручном создании чеков</li>
		<li>страница с просмотром ККТ</li>
		<li>страница с просмотром чеков</li>
		<li>на страницах ПУ "Позиции заказов" и "Заказ" добавлена возможность создания чека для указанных позиций и там же добавлена индикация количества чеков по позициям с возможностью перехода на просмотр этих чеков</li>
		<li>на странице ПУ "Заказы" добавлена индикация количества чеков по заказам с возможностью перехода на просмотр этих чеков</li>
	</ul>
</div>






<div>На страницу "Склады" добавлена кнопка "Свой IP для проценки"</div>





<div>
Доработки выбора способов получения заказов:
	<ul>
		<li>Доработаны шаблоны отображения данных для лучшего визуального восприятия информации</li>
		<li>Убрана галочка принятия пользовательского соглашения из тех способов где клиент не вводит не каких личных данных</li>
		<li>Изменен способ отображения яндекс карты, для снижения нагрузки карта подгружается только при раскрытии свернутого блока</li>
		<li>Перенесена форма ввода телефона для неавторизованного клиента на страницу последнего шага оформления заказа</li>
		<li>Изменены названия способов получения товара для лучшего восприятия информации</li>
	</ul>
</div>





<div>В корзине добавлена функция прямого перехода в проценку для перезаказа позиции с удалением текущей из корзины</div>



<div>В корзине добавлена функция добавления записи в блокнот гаража</div>


<div>Оптимизирован алгоритм загрузки прайс-листов в формате CSV/TXT через панель управления сайта</div>



<div>
Доработки импорта товаров для встроенного каталога через CSV:
	<ul>
		<li>Добавлена загрузка нескольких картинок через запятую</li>
		<li>Добавлена загрузка нескольких элементов множественного списка через запятую</li>
	</ul>
</div>




<div>Тип материала пользовательского соглашения заменен на текстовый - для возможности его упрощенного редактирования пользователем без навыков программирования</div>


<div>
Обновлена проценка для API:
	<ul>
		<li>Major Auto</li>
		<li>ZZAP</li>
		<li>Burjauto</li>
	</ul>
</div>



<div>
Добавлена проценка для API:
	<ul>
		<li>Платформа КАИС</li>
		<li>Новый API Автопитера, без SAO. Старый остался доступен</li>
	</ul>
</div>



<div>Исправлены все выявленные ошибки</div>





<h2>11 февраля 2020</h2>

<div>Добавлен новый виджет каталога levam.ru с адаптивной версткой и возможностью самостоятельного подключения каталога.</div>

<div>Добавлена защита от создания сессий для роботов. Оптимизирована нагрузка на базу данных сайта.</div>


<div>Добавлено формирование ссылки с параметрами выставления фильтра свойств для встроенного каталога товаров.<br>
Т.е. когда пользователь выставляет фильтр свойств на странице категории товаров каталога, в адресную строку браузера добавляются аргументы со значениями этих свойств.<br>
Далее эту ссылку можно использовать в директе, например для шин с определенными размерами или для АКБ с определенной емкостью. Т.е. заходя по такой ссылке - будет выставляться фильтр свойств в соответствии со значениями из URL.<br>
Туда же - в URL добавляются настройки сортировки и вида каталога.</div>

<div>Оптимизированы запросы в базу данных при работе со встроенным каталогом товаров</div>


<div>На странице настройки подключения поставщика в ПУ теперь выводится подробное описание типа технического интерфейса, если оно заполнено для выбранного типа. Это сделано для большей информативности при настройке подключения.</div>


<div>Исправлены все выявленные ошибки</div>







<h2>31 марта 2020</h2>



<div>Доработан поиск запчастей по загруженным прайс-листам в Excel</div>



<div>
Обновлена проценка для API:
	<ul>
		<li>magistral-nn.ru</li>
		<li>Arkona</li>
		<li>Армтек</li>
		<li>auto1.by</li>
		<li>ml-auto.by</li>
		<li>Росско</li>
		<li>Юником</li>
		<li>ADEO.PRO</li>
	</ul>
</div>




<div>
Подключены новые поставщики по API:
	<ul>
		<li>av34.ru</li>
		<li>b2motor.ru</li>
		<li>korona-auto.com</li>
		<li>avtobat.com.ua</li>
		<li>tehnomir.com.ua</li>
	</ul>
</div>



<div>
Обновления платежных систем (интернет-эквайринг):
	<ul>
		<li>Paykeeper</li>
		<li>Яндекс.Касса</li>
	</ul>
</div>


<div>Исправлены все выявленные ошибки</div>





<h2>28 мая 2020</h2>

<div>Доработан менеджер материалов (notree-редактор) - инструмент панели управления сайта, который позволяет создавать и редактировать страницы.</div>


<div>Добавлен инструмент для отслеживания изменений в структуре страниц сайта. С его помощью можно создавать дампы с текущим перечнем страниц сайта и затем сравнивать дампы, сделанные в разное время между собой.</div>


<div>В таблицу content базы данных сайта добавлен уникальный индекс для поля url для повышения скорости работы сайта.</div>


<div>Обновлена библиотека платного каталога neoriginal.ru</div>


<div>Кодировка в модуле печати документов указана явно. Это решило проблему с кодировкой при использовании модуля печати, которая возникала на некоторых хостингах.</div>


<div>По совету маркетологов, в функции проценки, на странице выбора производителя (когда одинаковый артикул встречается у разных производителей), добавлена кнопка "К ценам" напротив каждого варианта производителя - для лучшей юзабельности данной функции.</div>



<div>
	Обновлена проценка для API поставщиков:
	<ul>
		<li>The parts</li>
		<li>ADEO.PRO</li>
		<li>Автоевро</li>
		<li>Поставщики, работающие на ABCP</li>
	</ul>
</div>



<div>Исправлены все выявленные ошибки</div>








<h2>14 июля 2020</h2>


<div>Существенно доработан свой API. Эта функция используется в пакете Оптовик, а также для интеграции с 1С - когда проценка поставщиков требуется на стороне 1С.</div>



<div>
	Подключены новые поставщики по API:
	<ul>
		<li>Аванта</li>
		<li>Восход (v-avto.ru, не путать с Восход-Авторусь - тот уже давно есть)</li>
	</ul>
</div>




<div>
	Обновлена проценка для API поставщиков:
	<ul>
		<li>Автосоюз-юг</li>
		<li>АвтоТО</li>
		<li>EMEX</li>
		<li>Фаетон37</li>
		<li>v01.ru (М Партс)</li>
		<li>Автоевро</li>
		<li>ОМЕГА (etsp.ru)</li>
	</ul>
</div>

<div>Исправлены все выявленные ошибки</div>






<h2>18 августа 2020</h2>

<div>Добавлена поддержка PHP 7 (версии 7.0 - 7.4). Поддержка версий PHP 5.4 - 5.6 сохранена.</div>






<h2>24 августа 2020</h2>


<div>Марки автомобилей каталога Ucats ТО в табах поиска на главной странице теперь выводятся асинхронно - для ускорения отображения главной страницы</div>

<div>Доработана функция определения своего IP-адреса для проценки</div>




<h2>17 ноября 2020</h2>

<div>
	Обновлены поставщики:
	<ul>
		<li>Юником (проценка)</li>
		<li>ТринитиПартс (проценка)</li>
		<li>Иксора (SAO)</li>
		<li>Аркона (проценка, SAO)</li>
		<li>Смартек (проценка)</li>
	</ul>
</div>

<div>Новый поставщик Autoleader</div>

<div>Доработана функция баланса покупателей в панели управления</div>

<div>Доработана функция редактирования заказов в панели управления</div>


<div>Дорабатан вывод изображения во встроенном каталоге товаров</div>

<div>Доработана функция загрузки обычного CSV-прайса через панель управления сайта</div>

<div>Доработан модуль доставки для DPD</div>

<div>Доработан модуль доставки для Boxberry</div>

<div>
	Обновлены платежные модули систем:
	<ul>
		<li>assist</li>
		<li>Тинькофф</li>
		<li>Яндекс.Деньги</li>
	</ul>
</div>






<h2>30 ноября 2020</h2>
<div>Дополнительная оптимизация ядра CMS</div>






<h2>28 декабря 2020</h2>
<div>
	Обновлены платежные модули систем:
	<ul>
		<li>Paymaster</li>
		<li>Яндекс.Касса замен на ЮMoney</li>
	</ul>
</div>
<div>Исправлена обработка артикулов с кириллическими символами (часто встречаются у отечественных грузовиков)</div>
<div>Обновлен API поставщика Автостелс</div>
<div>Обновлены модули для справочников Ucats - для работы через протокол HTTPS</div>




<h2>15 февраля 2021</h2>

<div>Добавлена возможность совместного использования E-mail и телефона в учетной записи пользователя в качестве основных контактов</div>



<h2>20 февраля 2021</h2>
<div>Добавлена функция настройки шаблонов E-mail и SMS уведомлений</div>



<h2>24 февраля 2021</h2>

<div>Добавлены вспомогательные функции настройки E-mail и SMS уведомлений:
	<ul>
		<li>Страница настройки способов связи с возможностью тестирования и отладки</li>
		<li>Индикаторы текущего состояния способов связи в верхней части панели управления</li>
	</ul>
</div>

<div>К пунктам бокового меню панели управления добавлены пиктограммы</div>




<h2>14 марта 2021</h2>


<div>Добавлен эмулятор интернет-эквайринга для тестовых задач</div>

<div>Добавлена возможность частичной оплаты заказов:
	<ul>
		<li>можно включать и отключать функцию частичной оплаты для покупателя</li>
		<li>можно настраивать размер минимально-допустимого платежа в процентах от суммы заказа</li>
		<li>со стороны продавца функция частичной оплаты включена всегда</li>
		<li>появился учет по каждому заказу - сколько оплачено покупателем и сколько осталось доплатить</li>
		<li>добавлены ограничения на изменения заказа при наличии по заказу платежей</li>
	</ul>
</div>

<div>Добавлена функция возврата средств по заказу:
	<ul>
		<li>на баланс покупателя</li>
		<li>прямой возврат</li>
	</ul>
</div>

<div>Добавилась возможность перерасхода средств на балансе покупателя при оплате заказов (овердрафт):
	<ul>
		<li>можно включать и отключать функцию офердрафта</li>
		<li>можно настраивать размер допустимого овердрафта</li>
	</ul>
</div>

<div>Существенно доработана функция управления счетами покупателей:
	<ul>
		<li>доработан менеджер счетов (юзабельность и информативность)</li>
		<li>появилась функция создания и редактирования пользовательских видов финансовых операций</li>
	</ul>
</div>





<h2>25 марта 2021</h2>

<div>Добавлена функция копирования категорий товаров в редакторе дерева категорий:
	<ul>
		<li>Кнопка "Копировать"</li>
		<li>Кнопка "Вырезать"</li>
		<li>Кнопка "Вставить"</li>
	</ul>
</div>


<div>Добавлена функция шаблонов категорий товаров, которая позволяет хранить описание нужных категорий товаров и использовать их для создания новых категорий</div>

<div>Исправлена функция включения/отключения уведомлений пользователям по E-mail и SMS. Для каждого вида уведомлений теперь можно включать и отключать отправку - отдельно на E-mail и отдельно на телефон (SMS).</div>

<div>Исправлено сохранение шаблонов текстов E-mail и SMS уведомлений</div>

<div>Обновлены скрипты для работы с популярной платежной системой ЮMoney</div>




<div>Обновлены API поставщиков:
	<ul>
		<li>Автостелс (allautoparts)</li>
		<li>avdmotors</li>
		<li>b2motor</li>
		<li>mparts</li>
	</ul>
</div>

<div>Доработан чат по заказам со стороны продавца</div>

<div>Доработана библиотека системы управления учетными записями пользователей</div>


<div>Исправлены все выявленные баги</div>









<h2>7 апреля 2021</h2>


<div>Существенные доработки для пакета Оптовик:
	<ul>
		<li>Разделение платежных систем для магазинов (точек выдачи). Для каждой точки выдачи теперь можно подключать отдельную систему интернет-эквайринга с отдельным договором - для приема платежей на разные ИП или ООО.</li>
		<li>Разделение настроек печати документов по магазинам (точкам выдачи). Теперь такие документы, как УПД, ТОРГ-12, счет на оплату и т.д. могут настраиваться отдельно для каждой точки выдачи, с указанием разных ИП и ООО.</li>
	</ul>
</div>


<div>Доработано подключение к Docpart API (для подключения поставщиков, которые сами работают на платформе Docpart)</div>

<div>Добавлена функция включения/отключения покупки без регистрации клиента (ранее эта функция была включена постоянно).</div>







<h2>15 апреля 2021</h2>

<div>Добавлена функция простановки цен в прайс-листах, которая поддерживает:
	<ul>
		<li>Проставление цен в определенном прайс-листе на основе других прайс-листов</li>
		<li>Определение средней цены на товар</li>
		<li>Расчет цены на товар путем прибавления или вычетания определенного процента из базовой отметки. Базовой отметкой может быть максимальная, средняя или минимальная рыночная цена, взятая из других прайс-листов.</li>
	</ul>
</div>






<h2>27 апреля 2021</h2>

<div>Обновление подключений к API поставщиков:
	<ul>
		<li>Автосоюз - Исправлена ошибка определения наценки товара</li>
		<li>Optipart - Исправлена ошибка</li>
	</ul>
</div>




<div>Обновление для платежей на сайте:
	<ul>
		<li>Сбербанк - Добавлено информационное сообщение с инструкциями об оплате</li>
		<li>Промсвязьбанк - Исправлена ошибка формирования цифровой подписи возникавшая при наличии кириллических символов</li>
	</ul>
</div>



<div>Доработан скрипт формирования Карты сайта sitemap.xml. Добавлен запрет добавления в Карту сайта товаров и подкатегорий, которые находятся внутри скрытой категории</div>


<div>Доработан скрипт формирования Прайс-листа в панели управления. Добавлено округление цен в соответствии с выставленным округлением в настройках сайта. Изменено формирование строк csv файла для корректного отображения данных прайс-листа в программе Excel. Исправлена ошибка добавления в формируемый файл пустой строки при отсутствии позиции прайс-листа в базе.</div>


<div>Доработан скрипт страницы "Изменение позиции заказа" в панели управления. Исправлено отображение даты создания позиции и наименования склада позиции. Добавлены поля редактирования срока поставки позиции.</div>


<div>Доработан скрипт печатного документа "Счет на оплату". Добавлено отображение КПП.</div>


<div>Доработан скрипт печатного документа "Накладная ТОРГ-12". Добавлено отображение Артикула. Добавлен перенос строк в наименовании товара.</div>

<div>Доработан скрипт печатного документа "УПД". Добавлено отображение Артикула. Добавлен перенос строк в наименовании товара. Изменен номер свойства Статус.</div>


<div>Доработан скрипт страницы "Выгрузка на Яндекс.Маркет" в панели управления. Добавлен выбор выгружаемых категорий.</div>


<div>Для модуля подключения онлайн-кассы добавлена поддержка сервиса Инитпро</div>

<div>Доработан скрипт страницы "Статистика" в панели управления. Добавлено ограничение на количество отображаемых артикулов.</div>

<div>Доработан скрипт загрузки картинок при редактировании текстовых страниц сайта через панель управления.</div>

<div>Доработан "Импорт товаров в каталог из CSV". Добавлены подсказки по свойствам категорий. Исправлены ошибки. Добавлена очистка Артикула от лишних знаков. Добавлена загрузка применимости по нескольким элементам древовидного списка (для привязки товара одновременно к нескольким автомобилям). Добавлена настройка формирования URL-страницы по заданным свойствам. Добавлена настройка кодировки файла. Добавлена возможность очистки категории перед обработкой файла. Добавлено обновление свойств товара при повторной загрузке.</div>



<div>Доработана функция отладки подключения к API поставщиков</div>


<div>Оптимизирован исходный robots.txt</div>


<div>Доработан скрипт страницы "Регистрационные варианты" в панели управления. Добавлена проверка наличия пользователей с удаляемыми регистрационными вариантами. Если пользовати есть, то, платформа не даст удалить соответствующий регистрационный вариант.</div>


<div>Доработана функция "Экспорт каталога товаров в XML и JSON". Добавлена выгрузка древовидных свойств (для привязки товаров к автомобилям).</div>

<div>Доработана функция "Импорт каталога товаров из XML и JSON". Добавлена загрузка товаров с учетом ID и исправлены ошибки.</div>





<h2>26 мая 2021</h2>

<div>Добавлены новые поставщики для подключения к сайту через API-проценку:
	<ul>
		<li>avto-ms.ru</li>
		<li>comtt.ru</li>
		<li>kngnn.ru</li>
		<li>tmtr.ru</li>
		<li>unitrade.kz</li>
		<li>zap-pro.ru</li>
	</ul>
</div>



<div>Обновлено подключение к API следующих поставщиков:
	<ul>
		<li>optipart.ru</li>
		<li>korona-auto.com</li>
		<li>avdmotors.ru</li>
		<li>Форум-Авто</li>
		<li>Микадо</li>
		<li>Берг</li>
		<li>Emex</li>
		<li>auto1.by</li>
	</ul>
</div>


<div>Доработан модуль поиска товаров в Excel-прайсах</div>


<div>Доработана юзабельность функций и форм регистрации и авторизации</div>


<div>Обновлено подключение к SMS-оператору IQsms (для отправки SMS-уведомлений с сайта)</div>


<div>Доработан модуль для взаимодействия с нашей программой для загрузки прайс-листов</div>


<div>Доработан движок для создания собственного каталога товаров</div>


<div>Доработан модуль API-проценки поставщиков</div>


<div>Доработано подключение к банку Тинькофф (для приема платежей через сайт)</div>





<h2>03 июня 2021</h2>

<div>Доработано ядро CMS. Доступны более широкие возможности для лицензирования.</div>






<h2>04 июня 2021</h2>

<div>
	Обновления для платежных систем:
	<ul>
		<li>Сбербанк</li>
		<li>ЮКасса</li>
	</ul>
</div>


<div>Доработан поиск запчастей в загружаемых Excel-прайсах</div>


<div>Доработано подключение к API-проценке поставщика ABS-авто</div>

<div>Появилась возможность подключения обновленной версии платного каталога Laximo</div>




<h2>14 июня 2021</h2>
<div>Исправления багов, доработки, связанные с безопасностью</div>


<h2>15 июня 2021</h2>
<div>Исправления багов</div>



<h2>29 июня 2021</h2>
<div>Существенная доработка функции специальных поисков, которая позволяет создавать вспомогательные модули поиска по встроенному каталогу товаров, например "Подбор шин по автомобилю", "Подбор дисков по автомобилю", "Подбор масел по автомобилю" и т.д.<br>Что нового в данной функции:
	<ul>
		<li>Существенные доработки SEO для спецпоисков</li>
		<li>Страницы спецпоисков теперь работают с ЧПУ (человеко-понятный адрес страницы, который состоит из разделов и подразделов)</li>
		<li>Для всех страниц спецпоисков на всех уровнях вложенности можно теперь настраивать шаблоны метаданных с динамически подствляемыми значениями. Метаданные, которые можно настраивать: тег h1, тег title, тег мета-description, тег мета-keywords, тег мета-robots)</li>
		<li>Для элементов древовидных списков можно теперь указывать значения с транслитом, из которых формируются URL страниц</li>
		<li>Добавлена удобная инструкция по работе со спепоисками прямо в панель управления сайта</li>
	</ul>
</div>


<div>Добавлены настраиваемые уведомления продавцов и покупателей при появлении новых сообщений в чате заказа</div>

<div>Добавлено SAO для API поставщика Техномир (т.е. функции работы с заказами в системе поставщика - из панели управления своего сайта), а именно функции:
	<ul>
		<li>Добавление в корзину</li>
		<li>Удаление из корзины</li>
		<li>Заказ из корзины</li>
		<li>Отслеживание статуса заказа</li>
	</ul>
</div>


<div>Для популярного поставщика Росско полностью обновлено подключение к API, т.е. разработано подключение к его API новой версии.</div>


<div>Исправлены все выявленные ошибки</div>




<h2>17 августа 2021</h2>

<div>Доработана выгрузка на Яндекс.Маркет. Добавлено дерево категорий. Полностью переписан алгоритм формирования YML со значительным ускорением работы.</div>

<div>Доработано подключение к API Docpart (когда к подключается поставщик, чей сайт работает также на платформе Docpart)</div>

<div>Добавлен новый шаблон дизайна для клиентской части - Nero</div>

<div>Добавлены инструкции по настройке гео-узлов</div>

<div>Доработана функция импорта каталога товаров из XML (используется для загрузки каталога товаров на сайт из 1С и других учетных систем)</div>

<div>Доработан редактор категорий товаров встроенного каталога автозапчастей</div>


<div>Добавлен новый SMS-оператор smstraffic.ru</div>


<div>
	Обновлены подключения к платежным системам:
	<ul>
		<li>Сбербанк</li>
		<li>Paybox</li>
	</ul>
</div>


<div>Добавлена печать документа УПД (форма от 01.07.2021). При этом сохранена функция печати УПД старого образца.</div>


<div>Исправлены все выявленные ошибки</div>




<h2>19 августа 2021</h2>

<div>Добавлена функция быстрого выбора артикула. Когда покупатель ставит курсор в строку поиска по артикулу, то, выпадает список ранее запрошенных товаров, в котором покупатель, при желании, может выбрать из предложенных вариантов.</div>




<h2>11 октября 2021</h2>

<div>
	Обновлены подключения к платежным системам:
	<ul>
		<li>Альфабанк</li>
		<li>Промсвязьбанк</li>
		<li>Яндекс</li>
	</ul>
</div>



<div>
	Обновлены подключения к API следующих поставщиков:
	<ul>
		<li>RMS Auto</li>
		<li>Москворечье</li>
		<li>Шате-М</li>
		<li>Major Auto</li>
		<li>Emex</li>
		<li>Автогут</li>
		<li>АвтоЕвро</li>
		<li>gpi24</li>
	</ul>
</div>


<div>Исправлены все выявленные ошибки</div>


</body>