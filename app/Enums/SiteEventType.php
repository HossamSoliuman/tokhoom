<?php

namespace App\Enums;

enum SiteEventType: string
{
    case SiteVisit = 'site_visit';
    case ListingView = 'listing_view';
    case WhatsappClick = 'whatsapp_click';
    case CallClick = 'call_click';
    case Share = 'share';
}
