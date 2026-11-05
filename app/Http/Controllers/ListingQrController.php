<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Support\QrCode;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ListingQrController extends Controller
{
    /**
     * Download the listing's QR code. Scans are attributed to the QR source.
     */
    public function __invoke(Listing $listing, string $format): Response
    {
        Gate::authorize('view', $listing);

        $url = $listing->shortUrl().'?src=qr';
        $filename = 'tokhoom-'.$listing->short_code.'.'.$format;

        return $format === 'png'
            ? response(QrCode::png($url), 200, ['Content-Type' => 'image/png', 'Content-Disposition' => 'attachment; filename="'.$filename.'"'])
            : response(QrCode::svg($url), 200, ['Content-Type' => 'image/svg+xml', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
    }
}
