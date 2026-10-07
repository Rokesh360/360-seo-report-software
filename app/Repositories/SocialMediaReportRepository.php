<?php

namespace App\Repositories;

use App\Models\SocialMediaReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SocialMediaReportRepository
{
    public function __construct(protected SocialMediaReport $model)
    {
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->model
            ->when(!empty($filters['assign_service_id']),
                fn ($q) => $q->whereJsonContains('assign_service_ids', (int) $filters['assign_service_id']))
            ->when(!empty($filters['from']),
                fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(!empty($filters['to']),
                fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): SocialMediaReport
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): SocialMediaReport
    {
        return $this->model->create($data);
    }

    public function update(SocialMediaReport $report, array $data): SocialMediaReport
    {
        $report->update($data);
        return $report->fresh();
    }

    public function delete(SocialMediaReport $report): bool
    {
        return (bool) $report->delete();
    }
}