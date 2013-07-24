# Http Request

Библиотека для простой работы с HTTP протоколом, основанная на [одноименной библиотеке Kevin Sawicki](https://github.com/kevinsawicki/http-request/).
### Небольшие примеры
#### Получение ответа от стороннего сервиса в формате JSON
```php
try {
    $http = HttpRequest::get("http://geocode-maps.yandex.ru/1.x/?format=json",array("geocode"=>$city))->acceptJson();
    $json = $http->ok() ? json_decode($http->body()) : null;
} catch (HttpRequestException $e) {
    exit($e->getMessage());
}
```
#### Отправка формы методом POST со своими заголовками
```php
$http = HttpRequest::post("http://example.com/")->form(
  array(
    "param1" => "value",
    "param2" => "value",
    "file" => "@/home/vasya/attach.txt"
  ))
    ->header(HttpRequest::HEADER_USER_AGENT, "Opera/9.60 (J2ME/MIDP; Opera Mini/4.2.14912/812; U; ru)")
    ->header(HttpRequest::HEADER_REFERER,"http://google.com");
```
#### Отправка файла методом PUT
```php
$http = HttpRequest::put("http://example.com/")->upload("/home/vasja/attach.txt");
```
#### Вывести все заголовки ответа сервера
```php
print_r( HttpRequest::head("http://example.com")->headers() );
```
#### Загрузка файла с сервера
```php
$image=fopen('image.jpg','wb');
$loaded=HttpRequest::get("http://example.com/file.jpg")->receive($image)->ok(); // boolean
```