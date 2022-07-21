<?php 

namespace TourFacil\Core\Services\ActiveCampaign;

use TourFacil\Core\Models\Cliente;
use \GuzzleHttp\Client;

/**
 * Class ActiveCampaignService
 * @package TourFacil\Core\Services
 */
class ActiveCampaignService
{
    private $url = "";
    private $key = "";
    private $client ;

    public function __construct() {
        $this->client = new Client();

        $this->url = 'https://tourfacilcontas.api-us1.com/api/3/';
        $this->key = 'db3e6bafca1b5718a9cdd884da150d78c618786c81caafcfb3d0b015e98dbedfeb33067f';
    }

    public function criarContato(Cliente $cliente) {

        $nome = $this->separarNomeSobrenome($cliente->nome);

        $body = [
            'contact' => [
                'email' => $cliente->email,
                'firstName' => $nome['primeiro_nome'],
                'lastName' => $nome['segundo_nome'],
                'phone' => $cliente->telefone,
                'fieldValues' => [
                    [
                        'field' => '1',
                        'value' => '2022-07-01'
                    ],
                    [
                        'field' => '2',
                        'value' => '2022-08-01'
                    ]
                ],
            ],
        ];

        $request = $this->postReq('contacts', $body);
        dd(json_decode($request), true);
    }   

    private function postReq(String $url, Array $body) {

        $link = $this->url . $url; 

        $request = $this->client->request('POST', $link, [
            'body' => json_encode($body),
            'headers' => [
                'Accept' => 'application/json',
                'Api-Token' => 'db3e6bafca1b5718a9cdd884da150d78c618786c81caafcfb3d0b015e98dbedfeb33067f',
                'Content-Type' => 'application/json',
            ],
        ]);

        return $request->getBody()->getContents();
    }

    private function separarNomeSobrenome(String $nome) {
        $nome_separado = explode(' ', $nome);

        $primeiro_nome = '';
        $segundo_nome = '';

        foreach($nome_separado as $key => $parte_nome) {

            if($key == 0) {
                $primeiro_nome = $parte_nome;
            } else {
                $segundo_nome .= $parte_nome . " ";
            }
        }

        $segundo_nome = substr($segundo_nome, 0, -1);

        return [
            'primeiro_nome' => $primeiro_nome,
            'segundo_nome' => $segundo_nome,
        ];
    }
}
