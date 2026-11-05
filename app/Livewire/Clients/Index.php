<?php

namespace App\Livewire\Clients;

use App\Enums\ClientType;
use App\Enums\LeadSource;
use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Client;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('العملاء')]
class Index extends Component
{
    use HasDialogs, InteractsWithWorkspace, ShowsToasts;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = 'all';

    public int $limit = 30;

    public string $name = '';

    public string $mobile = '';

    public string $clientType = 'tenant';

    public string $source = 'direct';

    public ?int $agentId = null;

    public function openAddClient(): void
    {
        $this->reset('name', 'mobile', 'clientType', 'source');
        $this->agentId = $this->user()->id;
        $this->openDialog('client');
    }

    public function clearSearch(): void
    {
        $this->reset('search', 'type');
    }

    public function showMore(): void
    {
        $this->limit += 30;
    }

    /**
     * The client already registered with the typed mobile number, if any.
     */
    #[Computed]
    public function duplicate(): ?Client
    {
        $mobile = Phone::normalize($this->mobile);

        if (! Phone::isMobile($mobile)) {
            return null;
        }

        return Client::where('workspace_id', $this->workspace()->id)->where('mobile', $mobile)->with('agent')->first();
    }

    public function save(): void
    {
        $this->mobile = Phone::normalize($this->mobile);

        if ($this->duplicate) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'mobile' => ['required', 'regex:/^05\d{8}$/'],
            'clientType' => ['required', Rule::enum(ClientType::class)],
            'source' => ['required', Rule::enum(LeadSource::class)],
            'agentId' => ['required', Rule::in($this->assignableAgents()->pluck('id'))],
        ], ['mobile.regex' => 'أدخل رقم جوال سعودي يبدأ بـ 05.']);

        $client = Client::create([
            'workspace_id' => $this->workspace()->id,
            'agent_id' => $this->agentId,
            'name' => trim($this->name),
            'mobile' => $this->mobile,
            'type' => $this->clientType,
            'source' => $this->source,
        ]);

        $this->closeDialog();
        $this->flashToast('تمت إضافة العميل');
        $this->redirectRoute('clients.show', $client, navigate: true);
    }

    /**
     * Owners and managers can assign any member; agents add clients for themselves.
     */
    private function assignableAgents()
    {
        return $this->seesWholeWorkspace()
            ? $this->workspace()->members()->orderBy('memberships.id')->get()
            : collect([$this->user()]);
    }

    public function render(): View
    {
        $type = ClientType::tryFrom($this->type);
        $search = trim($this->search);
        $digits = preg_replace('/\D/', '', $search);

        $query = Client::visibleTo($this->membership())
            ->with(['agent', 'nextReminder'])
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->when($digits !== '', fn (Builder $query) => $query->orWhere('mobile', 'like', '%'.Phone::normalize($digits).'%'))))
            ->latest()
            ->latest('id');

        return view('livewire.clients.index', [
            'clients' => (clone $query)->limit($this->limit)->get(),
            'total' => $query->count(),
            'hasAnyClient' => Client::visibleTo($this->membership())->exists(),
            'agents' => $this->dialog === 'client' ? $this->assignableAgents() : collect(),
        ]);
    }
}
