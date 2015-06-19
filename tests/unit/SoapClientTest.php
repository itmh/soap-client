<?php

class SoapClientTest extends \Codeception\TestCase\Test
{
    public function testGetQuoteFromWebServiceX()
    {
        $client = new \ITMH\Soap\Client(
            'http://www.webservicex.net/StockQuote.asmx?WSDL',
            []
        );
        $result = $client->GetQuote([
            'symbol' => 'USD'
        ]);
        codecept_debug($result);
        self::assertTrue($result !== false);
    }
}
