<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    public const SIDE_BUY = 'buy';
    public const SIDE_SELL = 'sell';

    public const STATUS_OPEN = 1;
    public const STATUS_FILLED = 2;
    public const STATUS_CANCELLED = 3;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:8',
            'amount' => 'decimal:8',
            'status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeForSymbol(Builder $query, string $symbol): Builder
    {
        return $query->where('symbol', strtoupper($symbol));
    }

    public function scopeForSide(Builder $query, string $side): Builder
    {
        return $query->where('side', strtolower($side));
    }

    public function scopeOpenBook(Builder $query, string $symbol, string $side): Builder
    {
        $side = strtolower($side);

        $query
            ->forSymbol($symbol)
            ->open()
            ->forSide($side);

        if ($side === self::SIDE_BUY) {
            $query->orderBy('price', 'desc');
        } else {
            $query->orderBy('price', 'asc');
        }

        return $query->orderBy('created_at', 'asc');
    }
}
