<?php
// app/Models/UOMConversion.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UOMConversion extends Model
{
    use HasFactory;

    protected $primaryKey = 'conversion_id';
    protected $fillable = ['from_uom_id', 'to_uom_id', 'conversion_factor'];

    public function fromUOM()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'from_uom_id', 'uom_id');
    }

    public function toUOM()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'to_uom_id', 'uom_id');
    }
}