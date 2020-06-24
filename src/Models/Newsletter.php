<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Newsletter
 * @package TourFacil\Core\Models
 */
class Newsletter extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        "canal_venda_id",
        "email",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function canalVenda()
    {
        return $this->belongsTo(CanalVenda::class);
    }
}
