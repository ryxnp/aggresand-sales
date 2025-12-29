<form action="../process/create_account.php" method="POST" class="p-3 border rounded bg-light">

    <h4>Create Admin Account</h4>

    <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
            <option value="admin">Admin</option>
            <option value="superadmin">Super Admin</option>
            <option value="encoder">Encoder</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            <option value="active">Active</option>
            <option value="disabled">Disabled</option>
        </select>
    </div>

    <button type="submit" name="create_account" class="btn btn-primary">Create Account</button>

</form>
