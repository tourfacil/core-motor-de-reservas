<?php namespace TourFacil\Core\Services\Pagamento;

/**
 * Class CardBinService
 * @package TourFacil\Core\Services\Pagamento
 */
abstract class CardBinService
{
    const AMERICAN_EXPRESS = 'american_express';
    const DINERS_CLUB = 'diners_club';
    const ELO = 'elo';
    const HIPERCARD = 'hipercard';
    const MASTERCARD = 'mastercard';
    const VISA = 'visa';
    const DISCOVER = 'discover';
    const JCB = 'jcb';

    /** Regex Brands */
    const REGEX_BRANDS = [
        self::AMERICAN_EXPRESS => '/^3[47]\d{13}$/',
        self::DINERS_CLUB => '/^3(?:0[0-5]|[68]\d)\d{11}$/',
        self::ELO => '/(4011|431274|438935|451416|457393|4576|457631|457632|504175|627780|636297|636368|636369|(6503[1-3])|(6500(3[5-9]|4[0-9]|5[0-1]))|(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|(650(48[5-9]|49[0-9]|50[0-9]|51[1-9]|52[0-9]|53[0-7]))|(6505(4[0-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|(6507(0[0-9]|1[0-8]))|(6507(2[0-7]))|(650(90[1-9]|91[0-9]|920))|(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[1-9]))|(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8]))|(506(699|77[0-8]|7[1-6][0-9))|(509([0-9][0-9][0-9])))/',
        self::HIPERCARD => '/^(606282\d{10}(\d{3})?)|(3841\d{15})$/',
        self::MASTERCARD => '/^5[1-5]\d{14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{12}$/',
        self::VISA => '/^4\d{12}(?:\d{3})?$/',
        self::DISCOVER => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        self::JCB => '/^(?:2131|1800|35\d{3})\d{11}$/',
    ];

    /** Nome das bandeiras */
    const BRANDS_NAME = [
        self::AMERICAN_EXPRESS => 'American Express',
        self::DINERS_CLUB => 'Diners Club',
        self::ELO => 'Elo',
        self::HIPERCARD => 'Hipercard',
        self::MASTERCARD => 'Mastercard',
        self::VISA => 'Visa',
        self::DISCOVER => 'Discover',
        self::JCB => 'JCB',
    ];

    /**
     * Valida o cartao e retorna a bandeira
     *
     * @param $number
     * @return mixed
     */
    public static function getBrandByCardNumber($number)
    {
        $number = preg_replace("/[^0-9]/", "", $number);
        $return = ['brand' => '', 'brand_enum' => '', 'is_valid' => false, 'number' => $number];

        foreach (self::REGEX_BRANDS as $brand => $regex) {
            if(preg_match($regex, $number) > 0) {
                $return['brand'] = self::BRANDS_NAME[$brand];
                $return['brand_enum'] = $brand;
                $return['is_valid'] = true;
                break;
            }
        }

        return $return;
    }
}
