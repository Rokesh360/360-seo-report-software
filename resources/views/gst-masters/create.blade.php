@include('gst-masters._form', [
    'gstMaster' => null,
    'action'    => route('gst-masters.store'),
    'method'    => 'POST',
    'title'     => 'Add GST',
])