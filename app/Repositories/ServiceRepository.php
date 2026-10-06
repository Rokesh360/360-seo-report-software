<?php

namespace App\Repositories;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ServiceRepository
{
    public function __construct(protected Service $model)
    {
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->model
            ->when(!empty($filters['search']), fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%'))
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate($perPage);
    }

    public function all()
    {
        return $this->model->orderBy('name')->get(['id', 'name', 'status']);
    }

    public function active()
    {
        return $this->model->where('status', 'active')->orderBy('name')->get(['id', 'name']);
    }

    public function find(int $id): Service
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Service
    {
        return $this->model->create($data);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);

        return $service->fresh();
    }

    public function delete(Service $service): bool
    {
        return (bool) $service->delete();
    }
}