<?php

namespace TourFacil\Core\Services\Integracao\NovaXS\AcquaMotion;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TourFacil\Core\Enum\IntegracaoEnum;
use TourFacil\Core\Models\AcquaMotionReservaPedido;
use TourFacil\Core\Models\OlivasReservaPedido;
use TourFacil\Core\Models\ReservaPedido;
use TourFacil\Core\Models\VilaDaMonicaReservaPedido;

class AcquaMotionService
{
    /** @var AcquaMotionReservaPedido */
    protected $acqua_motion;

    /** @var array  */
    protected $accessList = [];

    /** @var ReservaPedido */
    protected $reserva;

    /** @var array */
    protected $servicosDisponiveis;

    /** @var array */
    protected $productsArray;

    /** @var array */
    protected $personAsString;

    /** @var  array */
    protected $buyToBillFor;

    /** @var  array */
    protected $billFor;

    /** @var  array */
    protected $getAccessList;

    /** @var string  */
    protected $path = "integracao/acqua_motion/";

    /** @var string */
    const CRIANCA = "crian";

    /** @var string */
    const ADULTO = "adulto";

    const SENIOR = "melhor";

    /**
     * ATENÇÃO NÃO MUDAR A ORDEM
     *
     * @var array
     */
    const TIPO_PESSOAS = [
        self::CRIANCA, self::ADULTO, self::SENIOR
    ];

    /**
     * OlivasService constructor.
     * @param ReservaPedido $reservaPedido
     */
    public function __construct(ReservaPedido $reservaPedido)
    {
        $this->reserva = $reservaPedido;
        $this->acqua_motion = new AcquaMotionAPI();
        // Nome do log
        $this->path = $this->path . "{$reservaPedido->id}.txt";
        // Cria um arquivo de log
        Storage::append($this->path, "Inicializado integração: " . date('d/m/Y H:i:s'));
    }

    /**
     * Gera o voucher da olivas
     *
     * @throws Exception
     */
    public function gerarVoucherAcquaMotion()
    {
        // Recupera os serviços disponível para a data do serviço
        $this->servicosDisponiveis = $this->acqua_motion->getProductsByDate([
            'date' => $this->reserva->agendaDataServico->data->format('d/m/Y')
        ]);

        // Log
        Storage::append($this->path, "#". $this->reserva->id .": Servicos disponiveis: " . json_encode($this->servicosDisponiveis));

        // Forma a lista de serviços e separa os IDs de cada serviço por categoria de idade
        $this->productsArray = $this->productsArray();

        // Log
        Storage::append($this->path, "#" . $this->reserva->id . "productsArray: " . json_encode($this->productsArray));

        // Retorna os dados do comprador
        $this->personAsString = $this->personAsString($this->reserva->pedido->cliente);

        // Bloqueio de compra
        $this->buyToBillFor = $this->acqua_motion->buyToBillFor([
            'productsArray' => json_encode($this->productsArray['productsArray']),
            'personAsString' => json_encode($this->personAsString)
        ]);

        // Log
        Storage::append($this->path, "#" . $this->reserva->id . "buyToBillFor: " . json_encode($this->buyToBillFor));

        // Confirmação da compra
        $this->billFor = $this->acqua_motion->billFor([
            'bill' => $this->buyToBillFor['id']
        ]);

        // Log
        Storage::append($this->path, "#" . $this->reserva->id . "billFor: " . json_encode($this->billFor));

        // Recupera a lista de passageiros
        $this->getAccessList = $this->acqua_motion->getAccessList([
            'bill' => $this->buyToBillFor['id']
        ]);

        // Log
        Storage::append($this->path, "#" . $this->reserva->id . "getAccessList: " . json_encode($this->getAccessList));

        // Cria a lista de passageiros conforme os clientes
        $this->createAccessList();

        // Salva a lista de viajantes para o olivas
        $this->acqua_motion->setAccessList([
            'bill' => $this->buyToBillFor['id'],
            'list' => json_encode($this->accessList)
        ]);

        // Salva as informações de impressão no banco
        AcquaMotionReservaPedido::create([
            'reserva_pedido_id' => $this->reserva->id,
            'bill_id' => $this->buyToBillFor['id'],
            'data_servico' => $this->reserva->agendaDataServico->data,
            'voucher_impressao' => $this->billFor['voucher'],
            'token_impressao' => $this->billFor['token'],
            'status' => IntegracaoEnum::VOUCHER_ATIVO,
        ]);

        // Log
        Storage::append($this->path, "#" . $this->reserva->id . "Integração finalizada: " . date('d/m/Y H:i:s'));
    }

    /** Filtra a lista de serviços disponiveis */
    private function filterServicoAcquaMotion() {

        // Array para armazenar os novos id
        $servicos = [];

        // Percorre todos os serviços disponiveis
        foreach ($this->servicosDisponiveis as $servico) {
            // Verifica se existe a casa shortName pois os combos nao tem shortName
            if(isset($servico['shortName'])) {
                // Procura o serviço pelas categorias
                $nome_servico = preg_replace("/(ç|Ç)/", "c", mb_strtolower($servico['name']));

                // Percorre os tipos de pessoas disponiveis
                foreach (self::TIPO_PESSOAS as $tipo_pessoa) {

                    if(strripos($nome_servico, $tipo_pessoa)) {
                        $servicos[Str::slug($tipo_pessoa, "_")] = $servico['path'];
                    }
                }
            }
        }

        // Atualiza a lista de serviços disponiveis
        $this->servicosDisponiveis = $servicos;
    }

    /**
     * Retorna os serviços para enviar a comprar ao olivas
     * ID,QUANTIDADE,DATA
     *
     * @return array
     * @throws Exception
     */
    private function productsArray() {

        // Filtra os serviços disponiveis
        $this->filterServicoAcquaMotion();

        // Data de utilizacao
        $data_utilizacao = $this->reserva->agendaDataServico->data->format('d/m/Y');

        // Array com os produtos olivas
        $productsArray = [];
        $variacoes_id = [];
        $productsIdArray = [];

        // Percorre a quantidade adquirida na reserva
        foreach ($this->reserva->quantidadeReserva as $quantidade_reserva) {

            // Variacao adquirida
            $nome_variacao = preg_replace("/(ç|Ç)/", "c", mb_strtolower($quantidade_reserva->variacaoServico->nome));
            $nome_variacao = str_replace('ê', 'e', $nome_variacao);

            // Somente pessoas pagantes
            if($quantidade_reserva->valor_net > 0) {

                /** Recupera os dados para crianca pagamente */
                if(Str::contains($nome_variacao, "pagante")) {
                    $product_path = $this->servicosDisponiveis[Str::slug(self::CRIANCA, "_")];
                    $productsArray[] = [
                        "path" => $product_path,
                        "amount" => (string) $quantidade_reserva->quantidade,
                        "date" => $data_utilizacao,
                        "name" => self::CRIANCA
                    ];
                    // Salva qual é a variacao
                    $variacoes_id[self::CRIANCA] = $quantidade_reserva->variacaoServico->id;
                    $productsIdArray[self::CRIANCA] = $this->onlyNumbers($product_path);
                }

                /** Recupera os dados para o adulto */
                if(Str::contains($nome_variacao, self::ADULTO)) {
                    $product_path = $this->servicosDisponiveis[Str::slug(self::ADULTO, "_")];
                    $productsArray[] = [
                        "path" => $product_path,
                        "amount" => (string) $quantidade_reserva->quantidade,
                        "date" => $data_utilizacao,
                        "name" => self::ADULTO
                    ];
                    // Salva qual é a variacao
                    $variacoes_id[self::ADULTO] = $quantidade_reserva->variacaoServico->id;
                    $productsIdArray[self::ADULTO] = $this->onlyNumbers($product_path);
                }

                /** Recupera o ID do serviço para melhor idade */
                if($nome_variacao == 'senior') {
                    $product_path = $this->servicosDisponiveis[Str::slug(self::SENIOR, "_")];
                    $productsArray[] = [
                        "path" => $this->servicosDisponiveis[Str::slug(self::SENIOR, "_")],
                        "amount" => (string) $quantidade_reserva->quantidade,
                        "date" => $data_utilizacao,
                        "name" => self::SENIOR
                    ];
                    // Salva qual é a variacao
                    $variacoes_id[self::SENIOR] = $quantidade_reserva->variacaoServico->id;
                    $productsIdArray[self::SENIOR] = $this->onlyNumbers($product_path);
                }
            }
        }

        return [
            'productsArray' => $productsArray,
            'productsIdArray' => $productsIdArray,
            'variationsId' => $variacoes_id
        ];
    }

    /**
     * Retorna em objeto os dados do comprador
     *
     * @param $cliente
     * @return array
     */
    private function personAsString($cliente){
        return [
            'name' => $this->formataNomeBuyer($this->clearString($cliente->nome)),
            'cpf' => preg_replace("/[^0-9]/", "", $cliente->cpf),
            'cellPhone' => preg_replace("/[^0-9]/", "", $cliente->telefone),
            'email' => $cliente->email
        ];
    }

    /** Intera a lista de passageiros com a lista de clientes */
    private function createAccessList(){

        // Recupera todos os adultos do pedido
        $adultos = $this->reserva->dadoClienteReservaPedido->where('variacao_servico_id', $this->productsArray['variationsId'][self::ADULTO] ?? null);

        // Recupera todos os senior da reserva
        $senior = $this->reserva->dadoClienteReservaPedido->where('variacao_servico_id', $this->productsArray['variationsId'][self::SENIOR] ?? null);

        // Recupera todas as crianças do pedido
        $criancas = $this->reserva->dadoClienteReservaPedido->where('variacao_servico_id', $this->productsArray['variationsId'][self::CRIANCA] ?? null);

        // Percorre a lista retornada pela API
        foreach ($this->getAccessList as $viajante) {

            // Se for adulto
            if(isset($this->productsArray['productsIdArray'][self::ADULTO])) {
                if($viajante['customData']['productId'] == $this->productsArray['productsIdArray'][self::ADULTO]) {
                    // Cria array conforme o retorno
                    $this->accessList[] = $this->createPeople($viajante, $adultos->last());
                    // Remove o ultimo item do array
                    $adultos->pop();
                    continue;
                }
            }

            // Se for senior
            if(isset($this->productsArray['productsIdArray'][self::SENIOR])) {
                if($viajante['customData']['productId'] == $this->productsArray['productsIdArray'][self::SENIOR]) {
                    // Cria array conforme o retorno
                    $this->accessList[] = $this->createPeople($viajante, $senior->last());
                    // Remove o ultimo item do array
                    $senior->pop();
                    continue;
                }
            }

            // Se for criança
            if(isset($this->productsArray['productsIdArray'][self::CRIANCA])) {
                if($viajante['customData']['productId'] == $this->productsArray['productsIdArray'][self::CRIANCA]) {
                    // Cria array conforme o retorno
                    $this->accessList[] = $this->createPeople($viajante, $criancas->last());
                    // Remove o ultimo item do array
                    $criancas->pop();
                    continue;
                }
            }
        }
    }

    /**
     * Retorna o Array para pessoas conforme a referencia
     * @param $reference
     * @param $cliente
     * @return array
     */
    private function createPeople($reference, $cliente)
    {
        if($cliente != null) {
            return [
                "id" => $reference["id"],
                "internalId" => $reference["internalId"],
                "trash" => $reference["trash"],
                "frozen" => $reference["frozen"],
                "inHistory" => $reference["inHistory"],
                "lastVersion" => $reference["lastVersion"],
                "lazy" => $reference["lazy"],
                "customData" => $reference["customData"],
                "accessPersons" => [[
                    "internalId" => $cliente->id ?? "",
                    "name" => $this->formataNomeBuyer($this->clearString($cliente->nome ?? "")),
                    "document" => $cliente->documento ?? "",
                    "birth" => $cliente->nascimento->format('d/m/Y'),
                    "itemIdentificator" => $reference["accessPersons"][0]["itemIdentificator"]
                ]],
            ];
        } else {
            return [
                "id" => $reference["id"],
                "internalId" => $reference["internalId"],
                "trash" => $reference["trash"],
                "frozen" => $reference["frozen"],
                "inHistory" => $reference["inHistory"],
                "lastVersion" => $reference["lastVersion"],
                "lazy" => $reference["lazy"],
                "customData" => $reference["customData"],
            ];
        }
    }

    /**
     * Cancela o voucher do olivas
     *
     * @param OlivasReservaPedido $olivasReservaPedido
     * @return array
     */
    public function cancelarVoucher(VilaDaMonicaReservaPedido $olivasReservaPedido)
    {
        // Solicita o cancelamento ao olivas
        $cancelado = $this->acqua_motion->cancelBill([
            'bill' => $olivasReservaPedido->bill_id
        ]);

        // atualiza o status do voucher para cancelado
        $olivasReservaPedido->update(['status' => IntegracaoEnum::VOUCHER_CANCELADO]);

        return ['cancelamento' => $cancelado];
    }

    /**
     * Formata o nome do comprador
     *
     * @param $nome
     * @return string
     */
    private function formataNomeBuyer($nome)
    {
        $words = explode(' ', mb_strtolower(trim(preg_replace("/\s+/", ' ', $nome))));
        $return[] = ucfirst($words[0]);

        unset($words[0]);

        foreach ($words as $word) {
            if (!preg_match("/^([dn]?[aeiou][s]?|em)$/i", $word)) {
                $word = ucfirst($word);
            }
            $return[] = $word;
        }

        return implode(' ', $return);
    }

    /**
     * remove acentuação da string
     *
     * @param $string
     * @return string|string[]|null
     */
    private function clearString($string)
    {
        $string = preg_replace('/[áàãâä]/ui', 'a', $string);
        $string = preg_replace('/[éèêë]/ui', 'e', $string);
        $string = preg_replace('/[íìîï]/ui', 'i', $string);
        $string = preg_replace('/[óòõôö]/ui', 'o', $string);
        $string = preg_replace('/[úùûü]/ui', 'u', $string);
        $string = preg_replace('/[ç]/ui', 'c', $string);

        return $string;
    }

    /**
     * Recupera somente os numeros de uma string
     *
     * @param $str
     * @return int
     */
    private function onlyNumbers($str) {
        return (int) preg_replace("/[^0-9]/", "", $str);
    }
}
