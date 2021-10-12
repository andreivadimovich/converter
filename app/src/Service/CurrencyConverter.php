<?php

namespace App\Service;

class CurrencyConverter
{
    const BASE_CURRENCY = 'EUR'; // Базовая валюта - Евро
    const SCALE = 7; // Количество цифр после запятой

    /**
     * BTC в EUR через USD
     * @param string $btc
     * @param string $usd
     * @return string
     */
    public static function btcToEur(string $btc, string $usd): string
    {
        return bcdiv($usd, $btc,self::SCALE);
    }

    /**
     * Конвертация из одной валюты в другую
     * @param array $from
     * @param array $to
     * @param string $amount
     * @return array
     */
    public static function estimation(array $from, array $to, string $amount): array
    {
        $data = [
//            'from'=> $from, 'to'=> $to, 'amount' => $amount,
            'emptyData' => false
        ];
        $from['value'] = number_format($from['value'], self::SCALE, '.', '');
        $to['value'] = number_format($to['value'], self::SCALE, '.', '');

        // Из базовой валюты(EUR) в валюту конвертации
        if ($from['code'] == self::BASE_CURRENCY) {
            $data['result'] = bcmul($amount, $to['value'], self::SCALE);

        // Из валюты конвертации в базовую валюту(EUR)
        } elseif ($to['code'] == self::BASE_CURRENCY) {
            $data['result'] = bcdiv($amount, $from['value'], self::SCALE);

        // Расчет крос курсом
        } else {
            $cross = bcmul($amount, $to['value'], self::SCALE);
            $cross = bcdiv($cross, $from['value'], self::SCALE);
            $data['result'] = $cross;
         }

        if ($data['result'] == 0 || $data['result'] < 0) {
            $data['emptyData'] = true;
        }

        return $data;
    }
}