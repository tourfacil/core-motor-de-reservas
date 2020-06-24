<?php namespace TourFacil\Core\Services;

use Image;
use TourFacil\Core\Enum\FotoServicoEnum;
use TourFacil\Core\Models\BannerDestino;
use TourFacil\Core\Models\CanalVenda;
use TourFacil\Core\Models\Categoria;
use TourFacil\Core\Models\Destino;
use TourFacil\Core\Models\Servico;
use Storage;

/**
 * Class UploadPhotoService
 * @package TourFacil\Core\Services
 */
class UploadPhotoService
{
    /**
     * upload das fotos do serviço
     *
     * @param $fotos
     * @param Servico $servico
     * @param CanalVenda $canal_venda
     * @return array
     */
    public static function uploadFotosServico($fotos, Servico $servico, CanalVenda $canal_venda)
    {
        $data_model = []; $key = 0;

        // Tamanho das fotos
        $largura_fotos = Servico::$PHOTO_PRESET;

        // Path das fotos slug do canal de venda
        $path = str_slug($canal_venda->nome) . "/servicos/";

        // Percorre as fotos
        foreach($fotos as $foto) {

            // Array para cada foto
            $foto_servico = [];

            // Gera as fotos nos 3 tamanhos
            foreach($largura_fotos as $type => $preset) {

                // Random name image
                $name = $servico->slug . "-" . uniqid() . "-" . str_slug($type) . ".jpg";

                // Cria a imagem
                $image = self::makePhoto($foto, $preset);

                // Upload photo
                $storage = Storage::cloud()->put($path . $name, $image->__toString(), "public");

                // Verifica se enviou
                if($storage) $foto_servico[$type] = $path . $name;
            }

            // Caso subiu as fotos
            if(sizeof($foto_servico) > 0) $data_model[$key++] = $foto_servico;
        }

        // Caso as fotos foi enviadas
        if(sizeof($data_model) > 0) {
            return ['upload' => true, 'fotos' => $data_model];
        }

        return ['upload' => false];
    }

    /**
     * Faz o upload da foto da categoria
     *
     * @param $foto
     * @param Categoria $categoria
     * @param Destino $destino
     * @param CanalVenda $canal_venda
     * @return array
     */
    public static function uploadFotoCategoria($foto, Categoria $categoria, Destino $destino, CanalVenda $canal_venda)
    {
        // Tamanho da foto
        $largura_foto = Categoria::$PHOTO_PRESET[FotoServicoEnum::MEDIUM];

        // Path das fotos slug do canal de venda
        $path = str_slug($canal_venda->nome) . "/categorias/";

        // Random name image
        $name = $categoria->slug . "-" . $destino->slug . "-" . uniqid() . "-" . str_slug(FotoServicoEnum::MEDIUM) . ".jpg";

        // Cria a foto com o tamanho do preset
        $image = self::makePhoto($foto, $largura_foto);

        // Upload photo
        $storage = Storage::cloud()->put($path . $name, $image->__toString(), "public");

        // Verifica se enviou
        if($storage) return ['upload' => true, 'foto' => $path . $name];

        return ['upload' => false];
    }

    /**
     * Faz o upload do banner da categoria
     *
     * @param $foto
     * @param Categoria $categoria
     * @param Destino $destino
     * @param CanalVenda $canal_venda
     * @return array
     */
    public static function uploadBannerCategoria($foto, Categoria $categoria, Destino $destino, CanalVenda $canal_venda)
    {
        // Tamanho do banner
        $largura_banner = Categoria::$PHOTO_PRESET[FotoServicoEnum::LARGE];

        // Path das fotos slug do canal de venda
        $path = str_slug($canal_venda->nome) . "/categorias/";

        // Random name image
        $name = $categoria->slug . "-" . $destino->slug . "-" . uniqid() . "-" . str_slug(FotoServicoEnum::LARGE) . ".jpg";

        // Cria o banner com o tamanho do preset
        $image = self::makePhoto($foto, $largura_banner);

        // Upload photo
        $storage = Storage::cloud()->put($path . $name, $image->__toString(), "public");

        // Verifica se enviou
        if($storage) return ['upload' => true, 'foto' => $path . $name];

        return ['upload' => false];
    }

    /**
     * Faz o upload do banner do destino
     *
     * @param $foto
     * @param $titulo_banner
     * @param CanalVenda $canal_venda
     * @return array
     */
    public static function uploadBannerDestino($foto, $titulo_banner, CanalVenda $canal_venda)
    {
        // Path das fotos slug do canal de venda
        $path = str_slug($canal_venda->nome) . "/banners/";

        // Random name image
        $name = str_slug($titulo_banner) . "-" . uniqid() . ".jpg";

        // Cria o banner com o tamanho do preset
        $image = self::makePhoto($foto, BannerDestino::$PHOTO_PRESET);

        // Upload photo
        $storage = Storage::cloud()->put($path . $name, $image->__toString(), "public");

        // Verifica se enviou
        if($storage) return ['upload' => true, 'foto' => $path . $name];

        return ['upload' => false];
    }

    /**
     * Envia a foto do destino
     *
     * @param $foto
     * @param Destino $destino
     * @param CanalVenda $canal_venda
     * @return array
     */
    public static function uploadPhotoDestino($foto, Destino $destino, CanalVenda $canal_venda)
    {
        $data_model = [];

        // Tamanho das fotos
        $largura = Destino::$PHOTO_PRESET;

        // Path das fotos slug do canal de venda
        $path = str_slug($canal_venda->nome) . "/destinos/";

        // Gera as fotos nos 3 tamanhos
        foreach($largura as $type => $preset) {

            // Random name image
            $name = $destino->slug . "-" . uniqid() . "-" . str_slug($type) . ".jpg";

            // Caso tenha um tamanho fixo
            $image = self::makePhoto($foto, $preset);

            // Upload photo
            $storage = Storage::cloud()->put($path . $name, $image->__toString(), "public");

            // Verifica se enviou
            if($storage) $data_model[$type] = $path . $name;
        }

        // Caso as fotos foi enviadas
        if(sizeof($data_model) > 0) {
            return ['upload' => true, 'fotos' => $data_model];
        }

        return ['upload' => false];
    }

    /**
     * Deleta imagem na S3
     *
     * @param string|array $paths
     * @return bool
     */
    public static function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        return Storage::cloud()->delete(array_values($paths));
    }

    /**
     * Gera a foto conforme o preset
     *
     * @param $photo
     * @param array $preset
     * @return \Psr\Http\Message\StreamInterface
     */
    private static function makePhoto($photo, array $preset)
    {
        // Caso tenha um tamanho fixo
        if(isset($preset['height'])) {
            // Recorta a imagem
            $image = Image::make($photo->getRealPath())->fit($preset["width"], $preset["height"], function ($constraint) {
                $constraint->upsize();
            })->stream("jpg", 90);
        } else {
            // Recorta a imagem
            $image = Image::make($photo->getRealPath())->widen($preset["width"], function ($constraint) {
                $constraint->upsize();
            })->stream("jpg", 90);
        }

        return $image;
    }
}
