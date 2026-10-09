<?php

namespace App\Repositories;

use App\Models\AssignService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AssignServiceRepository
{
    public function paginate(int $perPage = 15, ?int $clientId = null): LengthAwarePaginator
    {
        return AssignService::with(['client'])       // ← 'service' removed
            ->when($clientId, fn($query) => $query->where('client_id', $clientId))
            ->latest()
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return AssignService::with(['client'])->latest()->get();
    }

    public function find(int $id): AssignService
    {
        return AssignService::with(['client'])->findOrFail($id);
    }

    public function create(array $data): AssignService
    {
        return AssignService::create($data);
    }

    public function update(AssignService $assignService, array $data): AssignService
    {
        $assignService->update($data);

        return $assignService->fresh(['client']);
    }

    public function delete(AssignService $assignService): bool
    {
        return $assignService->delete();
    }
}