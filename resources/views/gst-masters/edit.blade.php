@include('gst-masters._form', [
    'gstMaster' => $gstMaster,
    'action'    => route('gst-masters.update', $gstMaster->id),
    'method'    => 'PUT',
    'title'     => 'Edit GST',
])