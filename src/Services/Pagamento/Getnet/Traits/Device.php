<?php namespace TourFacil\Core\Services\Pagamento\Getnet\Traits;

/**
 * Trait Device
 * @package TourFacil\Core\Services\Pagamento\Getnet\Traits
 */
trait Device
{
    /**
     * Ip do dispositivo do cliente
     * @param string $ip_address
     * @return $this
     */
    public function setDeviceIp(string $ip_address)
    {
        $this->payload['device']['ip_address'] = $ip_address;

        return $this;
    }

    /**
     * Id do dispositivo do cliente
     * @param string $device_id
     * @return $this
     */
    public function setDeviceId(string $device_id)
    {
        $this->payload['device']['device_id'] = $device_id;

        return $this;
    }
}
