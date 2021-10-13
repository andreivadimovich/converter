<?php

namespace App\Service;

use App\Service\CurrencyConverter;

class CurrencyImport
{
    /**
     * @param string $data
     * @param string $nodeName
     * @param array|string[] $elementsName
     * @return array
     */
    public function fromXML(string $data, string $nodeName, array $elementsName = ['label' => 'currency',
        'value' => 'rate']): array
    {
        try {
            $result = [];
            $xml = new \SimpleXMLElement($data);
            if (!$xml) {
                throw new \Exception('Load XML error');
            }

            $xmlNamespaces = $xml->getNamespaces(true);
            $selectedNamespace = end($xmlNamespaces) ?: current($xmlNamespaces);
            $xml->registerXPathNamespace('f', $selectedNamespace);
            $xpath = $xml->xpath('//f:' . $nodeName);
            if (empty($xpath)) {
                throw new \Exception("nodeName - {$nodeName} not found");
            }

            foreach ($xpath as $row) {
                $currency = (string)$row[$elementsName['label']];
                $rate = (string)$row[$elementsName['value']];
                if (!empty($currency) && !empty($rate)) {
                    $result[] = [
                        'code' => $currency,
                        'value' => $rate
                    ];
                }
            }

            if (count($result) == 0) {
                throw new \Exception("Empty result. Check elementsName");
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Получение последнего значения элемента из JSON.
     * Используется для api.coindesk.com
     * @param string $data
     * @param string $nodeName
     * @return array
     */
    public function fromLastRowJSON(string $data, string $nodeName): array
    {
        try {
            $json = json_decode($data);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(json_last_error());
            }

            $json = (array)$json;
            if (!empty($json[$nodeName])) {
                // Актуальная котировка BTC в USD
                $value = end($json[$nodeName]) ?: 0;
                if (!empty($value)) {
                    return [
                        'code' => 'BTC',
                        'value' => $value
                    ];
                }
                throw new \Exception("Empty result");
            } else {
                throw new \Exception("nodeName - {$nodeName} not found");
            }

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Изменить курс BTC по отношению к EUR
     * @param array $data
     * @return array
     */
    public function convertBtcToEur(array $data): array
    {
        $main = $data['0'];
        $keyBtc = array_search('BTC', array_column($main, 'code'));
        $keyUsd = array_search('USD', array_column($main, 'code'));

        $btc = $main[$keyBtc]['value'];
        $usd = $main[$keyUsd]['value'];
        if (!empty($btc) && !empty($usd)) {
            $data['0'][$keyBtc]['value'] = CurrencyConverter::btcToEur($btc, $usd);
        }

        return $data;
    }


    /**
     * @param array $data
     * @return array
     */
    public function rebuildData(array $data): array
    {
        $main = array_column($data['0'],  'value', 'code');
        $result = $main;

        // Добавление котировок из дополнительных источников, если нет в главном массиве
        $second = array_slice($data, 1);
        if (isset($second['0']) && !empty($second['0'])) {
            $secondResult = self::rebuildSecondData($second);
            $result = array_merge($result, $secondResult);
        }

        return $result;
    }


    /**
     * @param array $second
     * @return array
     */
    protected function rebuildSecondData(array $second): array
    {
        $secondFormatted = [];
        foreach($second as $key => $row) {
            $more = array_column($row,  'value', 'code');
            $secondFormatted = array_merge($secondFormatted, $more);
        }
        $secondFormatted = array_unique($secondFormatted);

        $result = [];
        foreach($secondFormatted as $key => $val) {
            if (empty($main[$key])) {
                $result[$key] = $val;
            }
        }

        return $result;
    }


    // тут можно добавить обработчики для новых источников данных
}