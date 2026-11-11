<?php

namespace App\Repositories;

use App\Models\GstMaster;
use Illuminate\Database\Eloquent\Collection;

class GstMasterRepository
{
    public function all(): Collection
    {
        return GstMaster::latest('id')->get();
    }

    public function find(int $id): GstMaster
    {
        return GstMaster::findOrFail($id);
    }

    public function create(array $data): GstMaster
    {
        return GstMaster::create($data);
    }

    public function update(GstMaster $gstMaster, array $data): GstMaster
    {
        $gstMaster->update($data);

        return $gstMaster;
    }

    public function delete(GstMaster $gstMaster): bool
    {
        return (bool) $gstMaster->delete();
    }
}