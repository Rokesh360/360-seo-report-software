<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ClientRepository
{
    public function paginate(
        int $perPage = 10,
        ?string $fromDate = null,
        ?string $toDate = null
    ): LengthAwarePaginator {
        return Client::withCount('seoReports')
            ->when($fromDate, function ($query) use ($fromDate) {
                $query->whereDate('date_of_campaign', '>=', $fromDate);
            })
            ->when($toDate, function ($query) use ($toDate) {
                $query->whereDate('date_of_campaign', '<=', $toDate);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function activeList(): Collection
    {
        return Client::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'website']);
    }

    public function withSeoReportCounts(): Collection
    {
        return Client::withCount('seoReports')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): Client
    {
        return Client::findOrFail($id);
    }

    public function create(array $data): Client
    {
        return Client::create($data);
    }

    public function update(int $id, array $data): Client
    {
        $client = Client::findOrFail($id);
        $client->update($data);

        return $client;
    }

    /**
     * Returns false when the client still has reports.
     */
    public function delete(int $id): bool
    {
        $client = Client::withCount('seoReports')->findOrFail($id);

        if ($client->seo_reports_count > 0) {
            return false;
        }

        return (bool) $client->delete();
    }
}
