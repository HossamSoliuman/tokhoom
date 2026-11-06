<?php

namespace App\Http\Controllers;

use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SiteQrController extends Controller
{
    /**
     * Download the QR code of the member's public page. Scans are attributed to the QR source.
     */
    public function __invoke(Request $request, string $format): Response
    {
        $membership = $request->user()->currentMembership();
        $url = $membership->publicUrl().'?src=qr';
        $filename = 'tokhoom-'.$membership->workspace->slug.'.'.$format;

        return $format === 'png'
            ? response(QrCode::png($url), 200, ['Content-Type' => 'image/png', 'Content-Disposition' => 'attachment; filename="'.$filename.'"'])
            : response(QrCode::svg($url), 200, ['Content-Type' => 'image/svg+xml', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
    }
}
