<?php

namespace App\Models;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Model;

class CafeTable extends Model
{
    protected $table = 'cafe_tables';

    protected $fillable = ['table_number', 'qr_code'];

    protected function casts(): array
    {
        return [];
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function getQrCodeUrlAttribute(): string
    {
        return route('pelanggan.identitas', ['table' => $this->table_number]);
    }

    public function getQrCodeSvgAttribute(): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'eccLevel' => EccLevel::L,
            'outputBase64' => false,
        ]);

        return (new QRCode($options))->render($this->qr_code);
    }

    public function getQrCodeSvgDataUriAttribute(): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->qr_code_svg);
    }

    public function getQrCodePngDataUriAttribute(): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => EccLevel::L,
            'outputBase64' => true,
            'scale' => 10,
        ]);

        return 'data:image/png;base64,'.(new QRCode($options))->render($this->qr_code);
    }

    public function generatePngDownload(): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => EccLevel::L,
            'scale' => 10,
        ]);

        $raw = (new QRCode($options))->render($this->qr_code);

        // render() returns data:image/png;base64,XXXX — strip the prefix
        $base64 = substr($raw, strpos($raw, ',') + 1);

        return base64_decode($base64);
    }

    protected static function booted(): void
    {
        static::creating(function (CafeTable $table) {
            if (empty($table->qr_code)) {
                $table->qr_code = route('pelanggan.identitas', ['table' => $table->table_number]);
            }
        });

        static::updating(function (CafeTable $table) {
            if ($table->isDirty('table_number')) {
                $table->qr_code = route('pelanggan.identitas', ['table' => $table->table_number]);
            }
        });
    }
}
