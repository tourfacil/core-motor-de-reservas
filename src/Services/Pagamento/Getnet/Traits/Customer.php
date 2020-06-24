<?php namespace TourFacil\Core\Services\Pagamento\Getnet\Traits;

/**
 * Trait Customer
 * @package TourFacil\Core\Services\Pagamento\Getnet\Traits
 */
trait Customer
{
    /**
     * Id do cliente
     * @param string $id
     * @return $this
     */
    public function setCustomerId(string $id)
    {
        $this->payload['customer']['customer_id'] = $id;

        return $this;
    }

    /**
     * Primeiro nome do cliente
     *
     * @param string $name
     * @return $this
     */
    public function setCustomerName(string $name)
    {
        $name = $this->removeAccentuation($name);
        $sobrenome = explode(" ", $name);

        $this->payload['customer']['name'] = $this->removeAccentuation($name);
        $this->payload['customer']['first_name'] = $sobrenome[0] ?? $name;
        $this->payload['customer']['last_name'] = $sobrenome[1] ?? $name;

        return $this;
    }

    /**
     * Email do cliente
     *
     * @param string $email
     * @return $this
     */
    public function setCustomerEmail(string $email)
    {
        $this->payload['customer']['email'] = $email;

        return $this;
    }

    /**
     * Documento do cliente
     *
     * @param $document_number
     * @return $this
     */
    public function setCustomerDocumentNumber(string $document_number)
    {
        $this->payload['customer']['document_number'] = $this->onlyNumbers($document_number);
        $this->payload['customer']['document_type'] = "CPF";

        return $this;
    }

    /**
     * Configura o telefone do cliente, contendo o DDD
     *
     * @param $phone_number
     * @return $this
     */
    public function setCustomerPhoneNumber(string $phone_number)
    {
        $this->payload['customer']['phone_number'] = $this->onlyNumbers($phone_number);

        return $this;
    }
}
