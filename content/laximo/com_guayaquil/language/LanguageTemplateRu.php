<?php

namespace guayaquil\language;

abstract class LanguageTemplateRu extends LanguageTemplate
{

    public static $language_data = array(
        'TotalDetails'                       => 'Всего деталей: ',
        'welcomeText'                        => 'Для начала работы необходимо настроить логин/ключ в файле Config.php',
        'welcomeLinkOem'                     => 'Начать просмотр Laximo.OEM',
        'welcomeLinkAm'                      => 'Начать просмотр Laximo.Aftermarket',
        'welcomeLinkAuto3nAM'                => 'Начать просмотр Auto3n.Aftermarket',
        'interfaceLanguage'                  => 'Язык интерфейса',
        'findOems'                           => 'Поиск узлов по OEM',
        'findApplicableVehicle'              => 'Поиск автомобилей по детали',
        'findApplicableVehicleTip'           => 'Введите артикул (OEM)',
        'Engine number'                      => 'Номер двигателя',
        'findByEngineNumber'                 => 'номеру двигателя',
        'findByChassisNumber'                => 'номеру шасси',
        'findByChassis'                      => 'Поиск по номеру шасси',
        'byfindbyoem'                        => 'детали',
        'Chassis'                            => 'Шасси',
        'Chassis number'                     => 'Шасси',
        'Model code'                         => 'Код модели',
        'searchByModelCode'                  => 'коду модели',
        'searchByChassis'                    => 'шасси',
        'catalog title'                      => 'Наименование каталога',
        'catalog date'                       => 'Дата',
        'searchbyvin'                        => 'Поиск по VIN',
        'searchbyvinframe'                   => 'Поиск по VIN/Frame',
        'byfindvehicle'                      => 'VIN/Frame',
        'searchbycustom'                     => 'Поиск по',
        'inputvin'                           => 'Введите VIN автомобиля, например, %s',
        'search'                             => 'Поиск',
        'searchbyframe'                      => 'Поиск по Frame',
        'inputframe'                         => 'Введите код и номер кузова автомобиля, например, %s',
        'inputvinframe'                      => 'Введите VIN или номер кузова автомобиля, например, %s',
        'orinputvinframe'                    => ' или %s',
        'searchdetail'                       => 'Поиск запчастей по OEM коду',
        'searchbyoem'                        => 'Введите OEM номер',
        'searchbyoemtab'                     => 'Поиск по ОЕМ',
        'searchbyoemexample'                 => 'Введите OEM номер, например, %s',
        'search by wizard'                   => 'Поиск автомобиля по параметрам',
        'findfailed'                         => 'Поиск по %s ничего не дал',
        'findby'                             => 'Поиск по %s',
        'chasis'                             => 'Шасси',
        'Search by chassis'                  => 'Поиск по шасси',
        'cars'                               => 'Найденные автомобили',
        'columnvehiclebrand'                 => 'Бренд',
        'brand'                              => 'Бренд',
        'columnvehiclename'                  => 'Название',
        'name'                               => 'Название',
        'columnvehiclemodification'          => 'Модификация',
        'columnvehiclegrade'                 => 'Комплектация',
        'columnvehicleframe'                 => 'Кузов',
        'columnvehicledate'                  => 'Дата выпуска',
        'columnvehicleengine'                => 'Двигатель',
        'columnvehicleengineno'              => '№ двигателя',
        'columnvehicletransmission'          => 'КП',
        'columnvehicledoors'                 => 'Дверей',
        'columnvehiclemanufactured'          => 'Выпущено',
        'columnvehicleoptions'               => 'Опции',
        'columnvehiclecreationregion'        => 'Сделано в',
        'columnvehicleframes'                => 'Кузова',
        'columnvehicleframecolor'            => 'Цвет',
        'columnvehicletrimcolor'             => 'Цвет салона',
        'columnvehiclemodel'                 => 'Модель',
        'columnvehicledatefrom'              => 'Выпуск с',
        'columnvehicledateto'                => 'Выпуск по',
        'categories'                         => 'Категории',
        'carname'                            => 'Автомобиль %s',
        'selecteddetail'                     => 'Выбрана деталь %s',
        'unitname'                           => ' Узел %s',
        'columndetailcodeonimage'            => '№',
        'columndetailname'                   => 'Наименование детали',
        'columndetailoem'                    => 'OEM',
        'columndetailamount'                 => 'Количество',
        'columndetailnote'                   => 'Примечание',
        'columndetailprice'                  => 'Цена',
        'addtocarthint'                      => 'Добавить деталь в корзину',
        'togglereplacements'                 => 'Показать/скрыть информацию о дубликатах',
        'replacementway'                     => 'Тип заменяемости',
        'replacementwayfull'                 => 'Точный дубликат',
        'replacementwayforward'              => 'Замена возможна указанным дубликатом возможна, но обратная замена не гарантируется',
        'replacementwaybackward'             => 'Замена НЕ возможна',
        'wheretobuy'                         => 'Где купить',
        'searchbyoemresult'                  => 'Результаты поиска по OEM %s',
        'vehiclelink'                        => 'Перейти в список узлов',
        'groupdetails'                       => 'Запчасти в группе',
        'quickgroupslink'                    => 'Поиск по группам',
        'list vehicles'                      => 'Показать автомобили',
        'columnvehicledestinationregion'     => 'Для региона',
        'unit_legend'                        => 'Управление',
        'unit_legend_mouse_wheel'            => 'Колесо мыши',
        'unit_legend_image_resizing'         => 'масштабирование изображения',
        'unit_legend_show_replacement_parts' => 'показ дубликатов запчастей',
        'unit_legend_mouse_image_drag'       => 'Перетаскивание картинки мышью',
        'unit_legend_mouse_scroll_image'     => 'прокрутка картинки',
        'unit_legend_add_to_cart'            => 'добавление детали в корзину',
        'unit_legend_hover_parts'            => 'Наведение мышью на деталь',
        'unit_legend_highlight_parts'        => 'подсветка всех деталей на картинке и в таблице',
        'unit_legend_show_hind'              => 'показ подробной информации о запчасти',
        'otherunitparts'                     => 'Остальные детали узла',
        'enter_group_name'                   => 'Введите наименование для быстрого поиска',
        'reset_group_name'                   => 'Сброс',
        'applicabilitybrandselectortitle'    => 'Выберите вариант, по которому необходимо показать применимость',
        'applicability'                      => '(Показать применимость)',
        'copyright'                          => 'guayaquil',
        'searchbycode'                       => 'search by code',
        'needconfirm'                        => '(нажмите для уточнения)',
        'refineparams'                       => 'Уточнение параметров автомобиля',
        'selectfromdropdown'                 => 'Выберите ваш вариант из выпадающего списка',
        'apply'                              => 'Применить',
        'unitfilterheader'                   => 'Выберите из списка значение условия',
        'skipselection'                      => 'Пропустить выбор',
        'confirm'                            => 'Подтвердить',
        'notspecifed'                        => '-- Не указано --',
        'notfound'                           => 'Поиск по %s %s не дал результатов',
        'byfindByWizard2'                    => 'параметрам',
        'unitnotspecifed'                    => 'Наименование не указано',
        'findByWizard2'                      => 'Идентификация автомобиля по параметрам',
        'vehiclesFind'                       => 'Найденные автомобили',
        'detailsInGroup'                     => 'Запчасти в группе',
        'catalogsList'                       => 'Онлайн каталоги',
        'detailvariant'                      => 'Вар. ',
        'selectdetail'                       => 'Выберите один из подходящих артикулов',
        'unitDetailsNoResulst'               => 'Подходящих деталей не найдено',
        'qDetailsNoResult'                   => 'Ничего не найдено, воспользуйтесь ',
        'qDetailsNoResultLink'               => 'списком узлов ',
        'qDetailsNoResultAfterLink'          => 'для поиска требуемой детали',
        'date'                               => 'Дата',
        'engine'                             => 'Двигатель',
        'model'                              => 'Модель',
        'trimcolor'                          => 'Цвет салона',
        'showMore'                           => 'Подробнее',
        'showMoreVehicles'                   => 'Больше модификаций',
        'hideMoreVehicles'                   => 'Спрятать',
        'hide'                               => 'Скрыть',
        'REQUEST_TIME'                       => 'Время выполнения запроса %s сек.',
        'USERNAME'                           => 'Логин',
        'PASSWORD'                           => 'Ключ',
        'LOGIN'                              => 'Войти',
        'AUTHORIZED'                         => 'Вы вошли как %s',
        'UNAUTHORIZED'                       => 'Логин или ключ введены неверно',
        'CURRENT_VERSION'                    => 'Текущая версия',
        'RESPONSE_DATA'                      => 'Посмотреть ответ',
        'REQUEST_DATA'                       => 'Посмотреть запросы',
        'NO_NAME'                            => 'Без названия',
        'GET_LOGIN'                          => 'Введите логин и ключ от сервиса <a href="https://laximo.ru" target="_blank">Laximo.OEM</a>',
        'GET_LOGIN_AM'                       => 'Введите логин и ключ от сервиса <a href="https://laximo.ru" target="_blank">Laximo.AM</a>',
        'E_ACCESSDENIED'                     => 'Доступ к каталогу заблокирован, возможно он не оплачен или истек срок действия лицензии.',
        'SELECTBRAND'                        => 'Выбор производителя',
        'SELECTBRANDDESC'                    => 'По Вашему запросу было найдено несколько вариантов. ',
        'E_STANDARD_PART_SEARCH'             => 'Извините, список применимых модификаций слишком большой',
        'NOTHING_FOUND'                      => 'Извините, ничего не нашлось',
        'SHOW_MORE_DETAILS_IN'               => 'Смотреть еще в %s',
        '500_HEADING'                        => 'Ошибка :(',
        '500_DESCRIPTION'                    => 'Произошла ошибка и ваш запрос не может быть выполнен'
    );
}