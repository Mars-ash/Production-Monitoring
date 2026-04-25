@extends('layouts.app')

@section('title', 'Manajemen User — Daily Live Production')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <h4 class="fw-bold text-dark mb-0">
            <i class="bi bi-people-fill me-2"></i>Manajemen User
        </h4>
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-plus-lg me-1"></i>Tambah User
        </button>
    </div>

    {{-- Error Validation Flash --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card chart-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-production table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Dibuat Pada</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="ps-4 fw-medium text-muted">#{{ $user->id }}</td>
                                <td class="fw-bold">{{ $user->username }}</td>
                                <td>
                                    @if($user->isAdmin())
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">Admin</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">Viewer</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $user->created_at->format('d M Y, H:i') }}</td>
                                <td class="text-end pe-4">
                                    @if(Auth::id() !== $user->id)
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user {{ $user->username }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" title="Hapus User">
                                                <i class="bi bi-trash3-fill"></i> Hapus
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" disabled title="Anda tidak dapat menghapus diri sendiri">
                                            <i class="bi bi-trash3-fill"></i> Hapus
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-people d-block mb-2" style="font-size: 2rem;"></i>
                                    Tidak ada data user.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white py-3 border-0">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="addUserModalLabel">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-4">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-medium text-muted small">Username</label>
                        <input type="text" class="form-control bg-light" id="username" name="username" value="{{ old('username') }}" >
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label fw-medium text-muted small">Peran (Role)</label>
                        <select class="form-select bg-light" id="role" name="role" required>
                            <option value="viewer" {{ old('role') === 'viewer' ? 'selected' : '' }}>Viewer (Hanya lihat Dashboard)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin (Akses Penuh)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-medium text-muted small">Password</label>
                        <input type="password" class="form-control bg-light" id="password" name="password" required placeholder="Minimal 5 karakter">
                    </div>
                    <div class="mb-0">
                        <label for="password_confirmation" class="form-label fw-medium text-muted small">Konfirmasi Password</label>
                        <input type="password" class="form-control bg-light" id="password_confirmation" name="password_confirmation" required placeholder="Ulangi password">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
