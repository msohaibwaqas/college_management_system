@extends('layouts.app')

@section('title', 'Asset Management')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-boxes me-2"></i>Physical Assets</h1>
            <p class="page-subtitle">Track institution inventory, equipment, and furniture.</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('assets.categories') }}" class="btn btn-outline-primary">Categories</a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                <i class="fas fa-plus me-1"></i> Add Asset
            </button>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Asset Name</th>
                                    <th>Category</th>
                                    <th>Serial Number</th>
                                    <th>Cost</th>
                                    <th>Condition</th>
                                    <th>Location</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assets as $asset)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $asset->name }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $asset->category->name }}</span></td>
                                    <td><code>{{ $asset->serial_number ?? '---' }}</code></td>
                                    <td>PKR {{ number_format($asset->purchase_cost) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $asset->condition == 'damaged' ? 'danger' : ($asset->condition == 'new' ? 'success' : 'info') }}">
                                            {{ strtoupper($asset->condition) }}
                                        </span>
                                    </td>
                                    <td>{{ $asset->location ?? 'Unspecified' }}</td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary edit-asset-btn" 
                                                data-asset="{{ json_encode($asset) }}"
                                                data-bs-toggle="modal" data-bs-target="#editAssetModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this asset?');" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($assets->isEmpty())
                        <div class="p-5 text-center text-muted">No assets found in the inventory.</div>
                    @endif
                </div>
            </div>
            <div class="mt-3">
                {{ $assets->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('assets.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Physical Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Asset Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Cost (PKR)</label>
                            <input type="number" name="purchase_cost" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g. Lab 1, Admin Office">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition</label>
                        <select name="condition" class="form-select" required>
                            <option value="new">New</option>
                            <option value="good">Good / Functional</option>
                            <option value="damaged">Damaged / Repair Needed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Asset Modal -->
<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editAssetForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Physical Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Asset Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="edit_category_id" class="form-select" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" id="edit_serial_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Cost (PKR)</label>
                            <input type="number" name="purchase_cost" id="edit_purchase_cost" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" id="edit_location" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition</label>
                        <select name="condition" id="edit_condition" class="form-select" required>
                            <option value="new">New</option>
                            <option value="good">Good / Functional</option>
                            <option value="damaged">Damaged / Repair Needed</option>
                            <option value="disposed">Disposed / Scrapped</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editBtns = document.querySelectorAll('.edit-asset-btn');
        const editForm = document.getElementById('editAssetForm');
        
        editBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const asset = JSON.parse(this.getAttribute('data-asset'));
                
                // Set form action
                editForm.action = `/admin/assets/${asset.id}`;
                
                // Set field values
                document.getElementById('edit_name').value = asset.name;
                document.getElementById('edit_category_id').value = asset.category_id;
                document.getElementById('edit_serial_number').value = asset.serial_number || '';
                document.getElementById('edit_purchase_cost').value = asset.purchase_cost || '';
                document.getElementById('edit_location').value = asset.location || '';
                document.getElementById('edit_condition').value = asset.condition;
            });
        });
    });
</script>
@endsection
@endsection

