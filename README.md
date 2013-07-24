# Http Request

Библиотека для простой работы с HTTP протоколом, основанная на [одноименной библиотеке Kevin Sawicki](https://github.com/kevinsawicki/http-request/)

## Примеры использования

### Получение ответа от стороннего сервиса в формате JSON
```php
try {
    $http = HttpRequest::get("http://geocode-maps.yandex.ru/1.x/?format=json",array("geocode"=>$city))->acceptJson();
    $json = $http->ok() ? json_decode($http->body()) : null;
} catch (HttpRequestException $e) {
    exit($e->getMessage());
}
```

## Ссылки
