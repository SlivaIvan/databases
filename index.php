<?php
require('autoload.php');

$db = new Database('mydatabase.sqlite');

$client = new ClientsDatabase('mydatabase.sqlite');
$shop = new ShopDatabase('mydatabase.sqlite');
$orders = new OrdersDatabase('mydatabase.sqlite');

$columns = ['shop_id', 'client_id', 'created_at'];
$rows = ['Утконос','Москва, проспект Мира д 5', $order_date];

$orders->insert($columns, $rows);

//Установка дата и времени для записи продаж
$date_str = "2025-05-21 15:20:00";
$order_date = date('Y-m-d H:i:s', strtotime($date_str));

?>

<!-- HTML форма для отправки данных -->
<form method="POST">
    <input type="hidden" name="name" value="Macbook">
    <input type="hidden" name="price" value="150000">
    <input type="hidden" name="count" value="2">
    <button type="submit">Добавить запись</button>
</form>








