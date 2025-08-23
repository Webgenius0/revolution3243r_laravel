@extends('backend.app', ['title' => 'Subscription Plans'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                {{-- PAGE HEADER --}}
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <h1 class="page-title mb-0 me-3">Subscription Plans</h1>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="#">Subscription</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Plans</li>
                        </ol>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary ms-3" id="addPlanBtn" data-bs-toggle="modal"
                            data-bs-target="#createPlanModal">
                            <i class="fa fa-plus me-1"></i> Create New Plan
                        </button>
                    </div>
                </div>

                {{-- PAGE HEADER --}}

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card box-shadow-0">
                            <div class="card-body">

                                <div class="table-responsive">
                                    <table id="plansTable" class="table table-bordered text-nowrap">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Plan Name</th>
                                                <th>Price</th>
                                                <th>Duration</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {{-- DataTables will load plans data here --}}
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- Create Plan Modal --}}
                <div class="modal fade" id="createPlanModal" tabindex="-1" aria-labelledby="createPlanModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <form action="{{ route('admin.subscription.store') }}" method="POST" id="createPlanForm">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="createPlanModalLabel">Create New Subscription Plan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" id="planID" name="id">

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Plan Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price</label>
                                        <input type="number" step="0.01" class="form-control" id="price"
                                            name="price" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="duration" class="form-label">Duration</label>
                                        <select class="form-select" id="duration" name="interval" required>
                                            <option value="">Select duration</option>
                                            <option value="month">Monthly</option>
                                            <option value="year">Yearly</option>
                                        </select>

                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Create Plan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                {{-- End Create Plan Modal --}}

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            // Initialize DataTable
            var table = $('#plansTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.subscription.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'price',
                        name: 'price'
                    },
                    {
                        data: 'interval',
                        name: 'interval'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                lengthMenu: [10, 25, 50],
                responsive: true,
            });

            // Reset form & open modal for adding
            $('#addPlanBtn').click(function() {
                $('#createPlanModalLabel').text('Create New Subscription Plan');
                $('#createPlanForm')[0].reset();
                $('#planID').val('');
                $('.error-text').text('');
                $('#createPlanModal').modal('show');
            });

            // Handle form submit (Create / Update)
            $('#createPlanForm').on('submit', function(e) {
                e.preventDefault();
                var id = $('#planID').val();
                var url = id ? "{{ route('admin.subscription.update', ':id') }}".replace(':id', id) :
                    "{{ route('admin.subscription.store') }}";
                var method = id ? 'POST' : 'POST'; // both POST, backend decides

                $.ajax({
                    url: url,
                    type: method,
                    data: new FormData(this),
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $('#createPlanForm button[type=submit]').prop('disabled', true).text(
                            'Processing...');
                    },
                    success: function(res) {
                        if (res.status == 0) {
                            $.each(res.error, function(prefix, val) {
                                $('span.' + prefix + '_error').text(val[0]);
                            });
                        } else {
                            $('#createPlanModal').modal('hide');
                            $('#createPlanForm')[0].reset();
                            table.ajax.reload();
                            toastr.success(res.message);
                        }
                        $('#createPlanForm button[type=submit]').prop('disabled', false).text(
                            'Save changes');
                    },
                    error: function() {
                        $('#createPlanForm button[type=submit]').prop('disabled', false).text(
                            'Save changes');
                        toastr.error('Something went wrong. Please try again.');
                    }
                });
            });

            // Edit Plan
            $(document).on('click', '.editPlan', function() {
                var id = $(this).data('id');
                var url = "{{ route('admin.subscription.edit', ':id') }}".replace(':id', id);

                $.get(url, function(res) {
                    $('#createPlanModalLabel').text('Edit Subscription Plan');
                    $('#planID').val(res.data.id);
                    $('#name').val(res.data.name);
                    $('#price').val(res.data.price);
                    $('#duration').val(res.data.interval);
                    $('#createPlanModal').modal('show');
                });
            });
        });

        // make confirmDelete GLOBAL
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure you want to delete this record?',
                text: 'If you delete this, it will be gone forever.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteItem(id);
                }
            });
        }

        function deleteItem(id) {
            NProgress.start();
            let url = "{{ route('admin.subscription.destroy', ':id') }}".replace(':id', id);
            let csrfToken = '{{ csrf_token() }}';
            $.ajax({
                type: "DELETE",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(resp) {
                    NProgress.done();
                    toastr.success(resp.message);
                    $('#plansTable').DataTable().ajax.reload(); // ✅ corrected
                },
                error: function(xhr) {
                    NProgress.done();
                    toastr.error(xhr.responseJSON?.message || 'Delete failed');
                }
            });
        }
    </script>
@endpush
