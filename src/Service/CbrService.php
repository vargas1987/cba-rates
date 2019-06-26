<?php
namespace CbrRates\Service;

use GuzzleHttp\Client;

/**
 * Class CbrService
 */
class CbrService
{
    const URL_DAILY_RATES = 'http://www.cbr.ru/scripts/XML_daily.asp';
    const URL_CURRENCIES  = 'http://www.cbr.ru/scripts/XML_valFull.asp';

    const PARAM_DATE  = 'date_req';
    const DATE_FORMAT = 'd/m/Y';

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * CbrService constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->httpClient = $client;
    }

    /**
     * @param \DateTime $date
     *
     * @return array
     */
    public function getRates($date)
    {
        $response = $this->httpClient->get(self::URL_DAILY_RATES.'?'.http_build_query([self::PARAM_DATE => $date->format(self::DATE_FORMAT)]));

        $response = json_decode(json_encode((array) simplexml_load_string($response->getBody())), true);

        if (false === $response || !is_array($response)) {
            return null;
        }

        return array_map(function ($item) {
            unset($item['@attributes']);

            return $item;
        }, $response['Valute']);
    }

    /**
     * @return array
     */
    public function getCurrencies()
    {
        $response = $this->httpClient->get(self::URL_CURRENCIES);

        $response = json_decode(json_encode((array) simplexml_load_string($response->getBody())), true);

        if (false === $response || !is_array($response)) {
            return null;
        }

        return array_map(function ($item) {
            unset($item['@attributes']);

            return $item;
        }, $response['Item']);
    }
}
