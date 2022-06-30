<?php namespace TourFacil\Core\Services;

use Hashids\Hashids;
use TourFacil\Core\Models\Cliente;
use TourFacil\Core\Models\Pedido;
use TourFacil\Core\Models\EnderecoCliente;
use TourFacil\Core\Models\Servico;

/**
 * Class ClienteService
 * @package TourFacil\Core\Services
 */
class ClienteService
{
    /**
     * Recupera ou cadastra o cliente no canal de venda
     *
     * @param array $dados
     * @param $canal_venda_id
     * @return mixed
     */
    public static function findOrNew(array $dados, $canal_venda_id)
    {
        // Procura o cliente pelo email e canal de venda
        $cliente = Cliente::where('email', 'LIKE', $dados['email'])
            ->where('canal_venda_id', $canal_venda_id)->first();

        // Caso nao encontre
        if(is_null($cliente)) {

            // Cadastra o cliente
            $cliente = self::cadastrarCliente($dados, $canal_venda_id)['cliente'];

        } else {
            // Atualiza os dados do cliente
            $cliente->update($dados);
        }

        return $cliente;
    }

    /**
     * Cria um novo cadastro para o cliente
     *
     * @param array $dados
     * @param $canal_venda_id
     * @return array
     */
    public static function cadastrarCliente(array $dados, $canal_venda_id)
    {
        // Gera uma nova senha para o cliente
        $password = $dados['password'] ?? (new Hashids('clientes', 8))->encode(time());

        // Gera uma nova senha
        $dados['password'] = bcrypt($password);

        // Coloca o canal de venda ID
        $dados['canal_venda_id'] = $canal_venda_id;

        // Cadastra o cliente no canal de venda
        $cliente = Cliente::create($dados);

        $dados['cliente_id'] = $cliente->id;

        $endereco = EnderecoCliente::create($dados);

        return [
            'cliente' => $cliente,
            'password' => $password
        ];
    }

    /**
     * Dados do pedido realizado para os trackers
     *
     * @param $codigo_pedido
     * @param $carrinho
     * @param $cliente
     * @return array
     */
    public static function pedidoTrackers($codigo_pedido, $carrinho, $cliente)
    {
        $compra_cliente = [];

        // Recupera os dados do pedido
        $pedido = Pedido::where(['codigo' => $codigo_pedido, 'cliente_id' => $cliente->id])->first();

        if(is_null($pedido)) return [];

        // Informações do pedido
        $compra_cliente['codigo'] = $pedido->codigo;
        $compra_cliente['total'] = $pedido->valor_total + $pedido->juros;
        $compra_cliente['cliente'] = $cliente->email;
        $compra_cliente['date'] = now()->addDays(1)->format('Y-m-d');

        // Informaçoes dos servicos comprados
        foreach ($carrinho as $servico_carrinho) {

            // Dados GTM
            $compra_cliente['gtm'][] = [
                "id" => $servico_carrinho['uuid'],
                "name" => $servico_carrinho['nome_servico'],
                "price" => $servico_carrinho['valor_total'],
                "category" => $servico_carrinho['categoria'] . " em " . $servico_carrinho['cidade'],
                "quantity" => 1,
                "google_business_vertical" => 'custom' // Google ADS
            ];

            // Dados Facebook
            $compra_cliente['fcb'][] = ["id" => $servico_carrinho['uuid'], "quantity" => 1];

            // Avaliacao do consumidor
            $compra_cliente['gapi'][] = ["gtin" => $servico_carrinho['gtin']];
        }

        return $compra_cliente;
    }
}
