<?php

namespace App\Actions;

use App\Enums\ClientType;
use App\Enums\LeadSource;
use App\Enums\ListingPurpose;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Notifications\NewInquiry;
use Illuminate\Support\Facades\DB;

/**
 * A visitor's message about a listing: files it under the client with that
 * mobile number (adding the client to the listing's agent when new) and
 * tells the listing's agent.
 */
class ReceiveInquiry
{
    public function handle(Listing $listing, string $name, string $mobile, ?string $message, LeadSource $source): Inquiry
    {
        $inquiry = DB::transaction(function () use ($listing, $name, $mobile, $message, $source): Inquiry {
            $client = Client::firstOrCreate(
                ['workspace_id' => $listing->workspace_id, 'mobile' => $mobile],
                [
                    'agent_id' => $listing->agent_id,
                    'name' => $name,
                    'type' => $listing->purpose === ListingPurpose::Rent ? ClientType::Tenant : ClientType::Buyer,
                    'source' => $source,
                ],
            );

            $inquiry = Inquiry::create([
                'workspace_id' => $listing->workspace_id,
                'listing_id' => $listing->id,
                'client_id' => $client->id,
                'agent_id' => $listing->agent_id,
                'name' => $name,
                'mobile' => $mobile,
                'message' => $message,
                'source' => $source,
            ]);

            Activity::record($client, 'client.inquiry', ['source' => $source->label(), 'listing' => $listing->title]);

            return $inquiry;
        });

        $listing->agent?->notify(new NewInquiry($inquiry));

        return $inquiry;
    }
}
