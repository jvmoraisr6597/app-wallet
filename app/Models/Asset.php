<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Models\User;

class Asset extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'code',
        'asset_type',
        'order_type',
        'original_price',
        'current_price',
        'order_date',
        'sale_date',
        'quantity',
        'created_at',
        'updated_at'
    ];

    protected $table = 'assets';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
