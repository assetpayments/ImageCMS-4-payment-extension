## ImageCMS 4.X payment extension

### Installation

* Backup your webstore and database (module replaces files!!!)
* Edit file application/modules/shop/classes/SPaymentSystems.php 
  * Find code<br />
  
  'YandexMoneySystem'=>array(<br />
   &nbsp;&nbsp; 'filePath' =>'YandexMoney/YandexMoneySystem.php',<br />
   &nbsp;&nbsp; 'listName' =>'YandexMoney',<br />
   &nbsp;&nbsp; 'class'    => null<br />
	),<br />
   'QiWiSystem'=>array(<br />
   &nbsp;&nbsp;		'filePath' =>'QiWi/QiWiSystem.php',<br />
   &nbsp;&nbsp;		'listName' =>'QiWi',<br />
   &nbsp;&nbsp;		'class'    => null<br />
	), <br />
   'PayPalSystem'=>array( <br />
   &nbsp;&nbsp;		'filePath' =>'PayPal/PayPalSystem.php', <br />
   &nbsp;&nbsp;		'listName' =>'PayPal', <br />
   &nbsp;&nbsp;		'class'    => null <br />
	),  <br />

  * Add new method

   'AssetPayments'=>array(<br />
   &nbsp;&nbsp;		'filePath' =>'AssetPayments/AssetPayments.php',<br />
   &nbsp;&nbsp;		'listName' =>'AssetPayments',<br />
   &nbsp;&nbsp;		'class'    => null<br />
	),<br />
	
* Copy Upload contents to root directory of your site via ftp
* Create the module in Settings -> Payment Method 
* Press Create payment method button and fill
  * Name - Credit card Visa/MasterCard
  * Currency - Choose your currency
  * Enable - Yes
  * Description - Payment processing company "Asset Payments"
  * Press Create button again
* Configure the payment settings:
  * Choose processor - AssetPayments
  * Merchant Id
  * Secret key
  * Template ID = 19 by default
  * Save and exit
* Edit delivery methods in Settings -> Delivery methods and allow method usage

### Notes
Tested with ImageCMS 4.2 Premium and eCommerce
  
## Модуль оплаты ImageCMS 4.X 

### Установка
* Создайте резервную копию вашего магазина и базы данных (модуль перезаписывает существующие файлы!!!)
* Отредактируйте файл application/modules/shop/classes/SPaymentSystems.php 
  * Найдите код
  
  'YandexMoneySystem'=>array(
    'filePath' =>'YandexMoney/YandexMoneySystem.php',
    'listName' =>'YandexMoney',
    'class'    => null
	),
	'QiWiSystem'=>array(
		'filePath' =>'QiWi/QiWiSystem.php',
		'listName' =>'QiWi',
		'class'    => null
	),
	'PayPalSystem'=>array(
		'filePath' =>'PayPal/PayPalSystem.php',
		'listName' =>'PayPal',
		'class'    => null
	),

  * Добавьте новый метод

	'AssetPayments'=>array(
		'filePath' =>'AssetPayments/AssetPayments.php',
		'listName' =>'AssetPayments',
		'class'    => null
	),

* Скопируйте содержимое директории Upload в корневую директорию Вашего сайта по фтп
* Создайте модуль оплаты в Найстройки -> Методы оплаты 
* Нажмите кнопку Создать и заполните поля
  * Название - Банковская карта Visa/MasterCard
  * Валюта - Укажите валюту магазина
  * Активировать - Да
  * Описание - Процессинговая компания "Asset Payments"
  * Нажмите кнопку Создать
* Задайте в настройках модуля:
  * Обработчик - AssetPayments
  * Id магазина
  * Ключ магазина
  * Id шаблона - по умолчанию = 19
  * Сохранить и выйти
* Отредактируйте методы оплаты в Настройки -> Методы доставки и поставьте птичку чтобы разрешить метод оплаты для данного вида доставки

### Примечания
Разработано и протестировано c ImageCMS 4.2 Premium and eCommerce
