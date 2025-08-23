@extends('backend.app', ['title' => 'Edit Subscription Plan'])

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                {{-- PAGE HEADER --}}
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title mb-0">Edit Subscription Plan</h1>
                    </div>
                    <div class="d-flex align-items-center">
                        <nav aria-label="breadcrumb" class="me-3">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="#">Subscription</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Plan</li>
                            </ol>
                        </nav>
                        <a href="{{ route('admin.subscription.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>

                {{-- PAGE HEADER --}}

                <div class="row">
                    <div class="col-lg-6 mx-auto">
                        <div class="card box-shadow-0">
                            <div class="card-body">
                                <form action="{{ route('admin.subscription.update', $plan->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Plan Name</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name', $plan->name) }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price</label>
                                        <input type="number" step="0.01" class="form-control" id="price"
                                            name="price" value="{{ old('price', $plan->price) }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="interval" class="form-label">Duration</label>
                                        <select class="form-select" id="interval" name="interval" required>
                                            <option value="">Select duration</option>
                                            <option value="month" {{ $plan->interval == 'month' ? 'selected' : '' }}>
                                                Monthly</option>
                                            <option value="year" {{ $plan->interval == 'year' ? 'selected' : '' }}>Yearly
                                            </option>
                                        </select>
                                    </div>


                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save me-1"></i> Update Plan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
