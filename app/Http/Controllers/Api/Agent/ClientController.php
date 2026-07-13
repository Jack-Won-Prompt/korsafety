<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    private function agentId(Request $request): int
    {
        return $request->user()->agent->id;
    }

    public function index(Request $request)
    {
        $clients = Client::where('agent_id', $this->agentId($request))
            ->withCount('orders')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => $this->payload($c));

        return response()->json(['data' => $clients]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['agent_id'] = $this->agentId($request);
        $client = Client::create($data);

        return response()->json(['data' => $this->payload($client)], 201);
    }

    public function update(Request $request, Client $client)
    {
        abort_unless($client->agent_id === $this->agentId($request), 403);
        $client->update($this->validated($request));

        return response()->json(['data' => $this->payload($client)]);
    }

    public function destroy(Request $request, Client $client)
    {
        abort_unless($client->agent_id === $this->agentId($request), 403);
        $client->delete();

        return response()->json(['message' => '삭제되었습니다.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|in:company,hospital,etc',
            'contact_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'business_no' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'memo' => 'nullable|string|max:1000',
        ]);
    }

    private function payload(Client $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->type,
            'type_label' => $c->type_label,
            'contact_name' => $c->contact_name,
            'phone' => $c->phone,
            'business_no' => $c->business_no,
            'address' => $c->address,
            'memo' => $c->memo,
            'orders_count' => $c->orders_count ?? $c->orders()->count(),
        ];
    }
}
