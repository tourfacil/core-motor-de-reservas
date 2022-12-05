<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class VendaInternaLink extends Model
{
    protected $fillable = [
        'user_id',
        'afiliado_id',
        'vendedor_id',
        'uuid',
        'carrinho',
    ];
}
