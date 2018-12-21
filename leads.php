<?php
/**
 * Created by PhpStorm.
 * User: triest
 * Date: 18.12.2018
 * Time: 21:26
 */

//возвращает сделки без задач
function getLeads()
{
    /* Для начала нам необходимо инициализировать данные, необходимые для составления запроса. */
    $subdomain = 'triest21'; #Наш аккаунт - поддомен
    /* Формируем ссылку для запроса */
    $link = 'https://' . $subdomain . '.amocrm.ru/api/v2/leads';
    /* Заметим, что в ссылке можно передавать и другие параметры, которые влияют на выходной результат (смотрите документацию
    выше).
    Следовательно, мы можем заменить ссылку, приведённую выше на одну из следующих, либо скомбинировать параметры так, как Вам
    необходимо. */
    // $link = 'https://' . $subdomain . '.amocrm.ru/api/v2/leads?limit_rows=50';
    $link = 'https://' . $subdomain . '.amocrm.ru/api/v2/leads?limit_rows=50&filter[tasks]=1'; //сделки без задач
    /* Следующий запрос вернёт список сделок, у которых есть почта 'test@mail.com' */
    // $link='https://'.$subdomain.'.amocrm.ru/api/v2/leads?query=test@mail.com';
    /* Нам необходимо инициировать запрос к серверу. Воспользуемся библиотекой cURL (поставляется в составе PHP). Подробнее о
    работе с этой
    библиотекой Вы можете прочитать в мануале. */
    $curl = curl_init();
    /* Устанавливаем необходимые опции для сеанса cURL */
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
    curl_setopt($curl, CURLOPT_URL, $link);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    /* Вы также можете передать дополнительный HTTP-заголовок IF-MODIFIED-SINCE, в котором указывается дата в формате D, d M Y
    H:i:s. При
    передаче этого заголовка будут возвращены сделки, изменённые позже этой даты. */
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('IF-MODIFIED-SINCE: Mon, 01 Aug 2013 07:07:23'));
    /* Выполняем запрос к серверу. */
    $out = curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    /* Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
    $code = (int)$code;
    $errors = array(
        301 => 'Moved permanently',
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable'
    );
    try {
        /* Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке */
        if ($code != 200 && $code != 204) {
            throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
        }
    } catch (Exception $E) {
        die('Ошибка: ' . $E->getMessage() . PHP_EOL . 'Код ошибки: ' . $E->getCode());
    }
    /*
     Данные получаем в формате JSON, поэтому, для получения читаемых данных,
     нам придётся перевести ответ в формат, понятный PHP
     */

    $json_output = json_decode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return $json_output;
}


function getIdList($response)
{
    $response = $response["_embedded"];
    $response = $response["items"];
    $ids = [];
    if (!empty($response)) {
        foreach ($response as $item) {
            array_push($ids, $item['id']);
        }
    }
    return $ids;
}

//создает задачу для сделка с вфодным id
function createTask($id, $name, $end_data, $responsible_user_id)
{

    $tasks['add'] = array(
        #Привязываем к сделке
        array(
            'element_id' => $id, #ID сделки
            'element_type' => 2, #Показываем, что это - сделка, а не контакт
            'task_type' => 1, #Звонок
            'text' => $name,
            'responsible_user_id' => $responsible_user_id,
            'complete_till_at' => $end_data
        ),

    );
    /* Теперь подготовим данные, необходимые для запроса к серверу */
    $subdomain = 'triest21'; #Наш аккаунт - поддомен
    #Формируем ссылку для запроса
    $link = 'https://' . $subdomain . '.amocrm.ru/api/v2/tasks';
    /* Нам необходимо инициировать запрос к серверу. Воспользуемся библиотекой cURL (поставляется в составе PHP). Подробнее о
    работе с этой
    библиотекой Вы можете прочитать в мануале. */
    $curl = curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
    curl_setopt($curl, CURLOPT_URL, $link);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($tasks));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    $out = curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    /* Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
    $code = (int)$code;
    $errors = array(
        301 => 'Moved permanently',
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable'
    );
    try {
        #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
        if ($code != 200 && $code != 204) {
            throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
        }
    } catch (Exception $E) {
        die('Ошибка: ' . $E->getMessage() . PHP_EOL . 'Код ошибки: ' . $E->getCode());
    }
}

//получает массив слелок и вызывает функцию создания задач
function CreateTaskInLoop($array, $userId, $complete_till_at)
{
    if (!empty($array)) {
        foreach ($array as $item) {
            createTask($item, 'сделка без задачи', $complete_till_at, $userId);
        }
    }
}


$response = getLeads();


// тут загоняем в массив id сделок, без задачь (просто извлекаем id из jsona

$userId = 23393644;

$arrayIDs = getIdList($response);
$timestamp = strtotime("22-12-2018 15:00");

echo $date;
$date=new DateTime();
$date=$date->format('Y-m-d h:i:s');
$date=date('Y-m-d h:i:s', strtotime($date. ' + 1 days'));
CreateTaskInLoop($arrayIDs, $userId, $date);

//triest user id 23393644
