<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    private function agentId(): int
    {
        return Auth::user()->agent_id;
    }

    public function index()
    {
        $clients = Client::where('agent_id', $this->agentId())
            ->withCount('orders')->latest('id')->paginate(15);
        return view('agent.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('agent.clients.form', ['client' => new Client(['type' => 'company'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['agent_id'] = $this->agentId();
        Client::create($data);
        return redirect()->route('agent.clients.index')->with('status', '거래처가 등록되었습니다.');
    }

    public function edit(Client $client)
    {
        $this->authorizeOwner($client);
        return view('agent.clients.form', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $this->authorizeOwner($client);
        $client->update($this->validated($request));
        return redirect()->route('agent.clients.index')->with('status', '거래처가 수정되었습니다.');
    }

    public function destroy(Client $client)
    {
        $this->authorizeOwner($client);
        $client->delete();
        return back()->with('status', '거래처가 삭제되었습니다.');
    }

    private function authorizeOwner(Client $client): void
    {
        abort_unless($client->agent_id === $this->agentId(), 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:company,hospital,etc',
            'contact_name' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:30',
            'business_no' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:200',
            'memo' => 'nullable|string|max:500',
        ], [], ['name' => '거래처명', 'type' => '유형']);
    }
}
