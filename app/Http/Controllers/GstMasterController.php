<?php

namespace App\Http\Controllers;

use App\Http\Requests\GstMasterRequest;
use App\Models\GstMaster;
use App\Repositories\GstMasterRepository;

class GstMasterController extends Controller
{
    public function __construct(protected GstMasterRepository $gstMasters)
    {
    }

    public function index()
    {
        return view('gst-masters.index', [
            'gstMasters' => $this->gstMasters->all(),
        ]);
    }

    public function create()
    {
        return view('gst-masters.create');
    }

    public function store(GstMasterRequest $request)
    {
        $this->gstMasters->create($request->validated());

        return redirect()->route('gst-masters.index')
            ->with('success', 'GST created successfully.');
    }

    public function edit(GstMaster $gstMaster)
    {
        return view('gst-masters.edit', compact('gstMaster'));
    }

    public function update(GstMasterRequest $request, GstMaster $gstMaster)
    {
        $this->gstMasters->update($gstMaster, $request->validated());

        return redirect()->route('gst-masters.index')
            ->with('success', 'GST updated successfully.');
    }

    public function destroy(GstMaster $gstMaster)
    {
        $this->gstMasters->delete($gstMaster);

        return redirect()->route('gst-masters.index')
            ->with('success', 'GST deleted successfully.');
    }
}