<?php namespace TourFacil\Core\Services\Integracao\NovaXS\Snowland;

use Exception;

/**
 * Class SnowlandAPI
 * @package TourFacil\Core\Services\Snowland
 */
class SnowlandAPI
{
    /**
     * Nome do usuário de login da API
     *
     * @var string
     */
    protected $login;

    /**
     * Senha do usuario na API
     *
     * @var string
     */
    protected $password;

    /**
     * TOKEN de acesso para API
     *
     * @var string
     */
    protected $token;

    /**
     * URL base da API usada tbm para impressao do voucher
     *
     * @var string
     */
    protected $base_url;

    /**
     * URL da API
     *
     * @var string
     */
    protected $url;

    /**
     * Configura a classe de acordo com as configurações que vem do arquivo
     * de configurações das integrações
     *
     * @return void
     */
    public function __construct() {

        $this->login = config('integracao.snowland.login');
        $this->password = config('integracao.snowland.password');
        $this->token = config('integracao.snowland.token');
        $this->base_url = config('integracao.base_url');

        $this->url = $this->base_url . config('integracao.snowland.suffix_url');
    }

    /**
     * Função para realizar POST na API
     *
     * @param $metodo
     * @param array $dados
     * @param bool $returnArray
     * @return mixed|string
     */
    private function consultaAPI($metodo, $dados = [], $returnArray = false) {
        // É necessário que os dados sejam array
        if(is_array($dados) && $metodo != "") {
            try {
                // Parte do array que faz a autenticação
                $autenticacao = [
                    "method" => $metodo,
                    "token" => $this->token,
                    "login" => $this->login,
                    // "password" => $this->password
                ];

                // Juntamos os dois arrays
                $dados_post = array_merge($autenticacao, $dados);

                // Criação da solicitação HTTP
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => ['Content-Type: application/x-www-form-urlencoded'],
                        'content' => http_build_query($dados_post)
                    ]
                ]);

                // Realiza o POST
                $resultado = file_get_contents($this->url, false, $context);

                return json_decode(utf8_encode($resultado), $returnArray);

            } catch (Exception $erro) {
                // Retorno do erro caso a página não esteja online
                return $erro->getMessage();
            }
        }

        return "Os dados precisam ser um array";
    }

    /**
     * Pesquisa e retorna os serviços disponiveis
     * Para o dia do serviço comprado
     *
     * @param array $dados
     * @return mixed|string
     */
    public function getProductsByDate($dados = [])
    {
        return $this->consultaAPI(__FUNCTION__, $dados, true);
    }

    /**
     * Solicita a compra para o Snowland
     *
     * @param array $dados
     * @return mixed|string
     */
    public function buyToBillFor($dados = [])
    {
        return $this->consultaAPI(__FUNCTION__, $dados, true);
    }

    /**
     * Confirma a compra para o Snowland
     *
     * @param array $dados
     * @return mixed|string
     */
    public function billFor($dados = [])
    {
        return $this->consultaAPI(__FUNCTION__, $dados, true);
    }

    /**
     * Recupera a lista de viajantes para o Snowland
     *
     * @param array $dados
     * @return mixed
     */
    public function getAccessList($dados = [])
    {
        return json_decode($this->consultaAPI(__FUNCTION__, $dados, true), true);
    }

    /**
     * Salva a lista de viajantes
     *
     * @param array $dados
     * @return mixed|string
     */
    public function setAccessList($dados = [])
    {
        return $this->consultaAPI(__FUNCTION__, $dados, true);
    }

    /**
     * Cancela a compra realizada
     *
     * @param array $dados
     * @return mixed|string
     */
    public function cancelBill($dados = [])
    {
        return $this->consultaAPI(__FUNCTION__, $dados, true);
    }
}

