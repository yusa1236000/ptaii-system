<?php
// app/Models/UnitOfMeasure.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    use HasFactory;

    protected $primaryKey = 'uom_id';
    protected $fillable = ['name', 'symbol', 'description'];

    public function items()
    {
        return $this->hasMany(Item::class, 'uom_id', 'uom_id');
    }

    public function fromConversions()
    {
        return $this->hasMany(UOMConversion::class, 'from_uom_id', 'uom_id');
    }

    public function toConversions()
    {
        return $this->hasMany(UOMConversion::class, 'to_uom_id', 'uom_id');
    }
}