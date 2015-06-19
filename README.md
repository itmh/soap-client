# soap-client
Реализация SOAP-клиента c использованием cURL.

пример использования:
```$client = new \ITMH\Soap\Client(
    'http://www.webservicex.net/StockQuote.asmx?WSDL',
    [
        'login' => '',
        'password' => ''
    ]
);

$result = $client->GetQuote([
    'symbol' => 'USD'
]);

print_r($result);
```
