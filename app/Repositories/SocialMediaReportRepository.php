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
            ->with('client:id,name')   // client instead of user
            ->when(!empty($filters['report_type']), fn ($q) => $q->where('report_type', $filters['report_type']))
            ->when(!empty($filters['client_id']),   fn ($q) => $q->where('client_id', $filters['client_id']))
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): SocialMediaReport
    {
        return $this->model
            ->with('client:id,name')
            ->findOrFail($id);
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