# Staff Management Implementation - Complete Setup Guide

## ✅ Implementation Complete

Your Jewellery POS application now has a complete, production-ready staff management system with CRUD operations, role-based access control, and comprehensive permission management.

## 📋 What Was Implemented

### 1. **Middleware Files Created**
- `app/Http/Middleware/AdminMiddleware.php` - Ensures only admins access staff management
- `app/Http/Middleware/EnsureUserIsActive.php` - Prevents inactive users from accessing system

### 2. **Form Request Classes**
- `app/Http/Requests/StoreStaffRequest.php` - Validates staff creation with authorization
- `app/Http/Requests/UpdateStaffRequest.php` - Validates staff updates with authorization

### 3. **Service Layer**
- `app/Services/PermissionService.php` - Centralized permission checking and queries

### 4. **User Model Enhancement**
- `app/Traits/HasPermissions.php` - Permission helper methods added to User model
- Updated `app/Models/User.php` - Now uses HasPermissions trait

### 5. **Authorization Layer**
- `app/Policies/UserPolicy.php` - Laravel Policy for blade directives and authorization checks

### 6. **Configuration Updates**
- `bootstrap/app.php` - Registered both middleware aliases
- `app/Providers/AppServiceProvider.php` - Registered User policy

### 7. **Controller Updates**
- Enhanced `app/Http/Controllers/StaffController.php` with:
  - Constructor-based middleware application
  - Form request validation
  - Comprehensive authorization checks
  - Safe deletion with last-admin protection

### 8. **Route Updates**
- `routes/web.php` - Wrapped staff routes with `admin` middleware

### 9. **Documentation**
- `STAFF_MANAGEMENT.md` - Comprehensive documentation
- `STAFF_MANAGEMENT_EXAMPLES.md` - Practical code examples

---

## 🚀 Quick Start

### 1. Create a New Admin User

```php
<?php
// In your controller or command
$user = User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'phone' => '1234567890',
    'password' => bcrypt('password123'),
    'role' => 'Admin',
    'status' => 'Active'
]);
```

### 2. Access Staff Management

Navigate to `/staff` (requires admin role) to manage all staff members.

### 3. Use Helper Methods

```php
// Check if user is admin
if (auth()->user()->isAdmin()) {
    // Show admin content
}

// Check if user can manage staff
if (auth()->user()->canManageStaff()) {
    // User is admin and active
}

// Use permission service
use App\Services\PermissionService;
$totalStaff = PermissionService::countStaff();
```

---

## 🔐 Security Features

1. **Admin-Only Middleware** - All staff routes protected by `admin` middleware
2. **Password Hashing** - All passwords automatically bcrypted
3. **Email Uniqueness** - Enforced at database and validation level
4. **Status Checking** - Inactive users automatically logged out
5. **Last Admin Protection** - Cannot delete the only admin user
6. **CSRF Protection** - All forms protected from CSRF attacks
7. **Authorization Policies** - Fine-grained access control via policies

---

## 📚 Core Files Reference

### Using the Helper Methods

```php
$user = User::find(1);

// Role checks
$user->isAdmin();           // true/false
$user->isStaff();           // true/false
$user->hasRole('Admin');    // true/false

// Status checks
$user->isActive();          // true/false
$user->isInactive();        // true/false
$user->hasStatus('Active'); // true/false

// Permission checks
$user->canManageStaff();    // true/false
$user->canBeDeleted();      // true/false
```

### In Blade Templates

```blade
@if(auth()->user()->isAdmin())
    <a href="{{ route('staff.index') }}">Manage Staff</a>
@endif

@can('manageStaff', auth()->user())
    <button onclick="showAddStaffModal()">Add Staff</button>
@endcan

@can('delete', $staff)
    <button onclick="deleteStaff({{ $staff->id }})">Delete</button>
@endcan
```

### Routes Protected

```php
// All these routes require admin role:
GET    /staff                 // List all staff
POST   /staff                 // Create staff
GET    /staff/{id}            // View staff details
PUT    /staff/{id}            // Update staff
DELETE /staff/{id}            // Delete staff
PATCH  /staff/{id}/status     // Change status
```

---

## 📝 Database Schema

The users table now includes:

| Field | Type | Default |
|-------|------|---------|
| `role` | Enum (Admin, Staff) | Staff |
| `status` | Enum (Active, Inactive) | Active |
| `phone` | String | NULL |
| `sales_today` | Integer | 0 |
| `email_verified_at` | Timestamp | NULL |

**Migration:** `2026_02_13_000000_add_staff_fields_to_users_table.php`

---

## ✨ Key Features

### ✅ CRUD Operations
- **Create** - Add new staff with role and status
- **Read** - View staff list or individual details
- **Update** - Modify staff info, role, or password
- **Delete** - Remove staff (with safety checks)

### ✅ Role-Based Access Control
- **Admin** - Can manage all staff
- **Staff** - Limited access to system features

### ✅ Status Management
- **Active** - User can access the system
- **Inactive** - User cannot access (auto-logout)

### ✅ Advanced Features
- Password confirmation required on create
- Optional password update on edit
- Safe deletion (last admin protected)
- Centralized permission service
- Policy-based authorization
- Activity-friendly helper methods

---

## 🧪 Testing the Setup

### Test 1: Admin Can Manage Staff

```bash
# Create a staff member (as admin)
curl -X POST http://localhost/staff \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Staff",
    "email": "staff@example.com",
    "phone": "1234567890",
    "password": "securepass123",
    "password_confirmation": "securepass123",
    "role": "Staff",
    "status": "Active"
  }'
```

### Test 2: Non-Admin Cannot Access

```bash
# Try to access as staff member
curl -X GET http://localhost/staff \
  -H "Authorization: Bearer $STAFF_TOKEN"
# Expected: 403 Forbidden
```

### Test 3: Inactive User Cannot Access

```bash
# User with status='Inactive' is automatically logged out
# Any request will redirect to login
```

### Test 4: Cannot Delete Last Admin

```bash
# Try to delete the only admin
curl -X DELETE http://localhost/staff/1 \
  -H "Authorization: Bearer $ADMIN_TOKEN"
# Expected: 422 "Cannot delete the only admin user"
```

---

## 📋 Configuration Checklist

- ✅ AdminMiddleware created and registered
- ✅ EnsureUserIsActive middleware created and registered
- ✅ Form requests created with validation
- ✅ User model uses HasPermissions trait
- ✅ User policy created and registered
- ✅ Permission service created
- ✅ StaffController updated with middleware and validation
- ✅ Routes protected with admin middleware
- ✅ Bootstrap app.php configured
- ✅ AppServiceProvider configured
- ✅ Database migration exists (staff fields)

---

## 🎯 Common Use Cases

### Check if Logged-In User is Admin

```php
if (auth()->user()->isAdmin()) {
    // Show admin dashboard
}
```

### Get Count of Active Staff

```php
use App\Services\PermissionService;
$count = PermissionService::countActiveStaff();
```

### Prevent Inactive User Access

```php
// Applied automatically through EnsureUserIsActive middleware
// Any inactive user will be logged out
```

### Safely Delete a Staff Member

```php
$user = User::find($id);

if ($user->canBeDeleted()) {
    $user->delete();
} else {
    // "Cannot delete the only admin"
}
```

### List All Admin Users

```php
use App\Services\PermissionService;
$admins = PermissionService::getAdmins();
```

---

## 🔧 Troubleshooting

### Issue: "Admin role required" when trying to access staff routes

**Solution:** Make sure your user account has `role = 'Admin'` set in the database.

```sql
UPDATE users SET role = 'Admin' WHERE id = 1;
```

### Issue: Getting 419 (CSRF token mismatch)

**Solution:** Ensure you're including the CSRF token in your forms:

```blade
<form method="POST" action="/staff">
    @csrf
    <!-- form fields -->
</form>
```

### Issue: User can't log in

**Solution:** If their status is 'Inactive', they're automatically logged out. Change to 'Active':

```sql
UPDATE users SET status = 'Active' WHERE id = X;
```

### Issue: Routes not working

**Solution:** Make sure routes are within an authenticated middleware group:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('admin')->group(function () {
        Route::resource('staff', StaffController::class);
    });
});
```

---

## 📖 Documentation Files

1. **STAFF_MANAGEMENT.md** - Complete technical documentation
2. **STAFF_MANAGEMENT_EXAMPLES.md** - Code examples and usage patterns
3. **This file** - Setup guide and quick reference

---

## 🎓 Next Steps

1. Create your first admin user manually via database
2. Log in and navigate to `/staff`
3. Create additional staff members as needed
4. Customize the staff management views as per your design
5. Add the staff management to your navigation menu
6. Test different roles and statuses

---

## 🚀 For Developers

### Understanding the Authorization Flow

1. **Request arrives** → User tries to access `/staff` route
2. **Auth middleware** checks if user is logged in
3. **Admin middleware** checks if `role = 'Admin'`
4. **Controller** processes request with form validation
5. **Policy** provides additional fine-grained checks
6. **Response** returns success or error

### Extending the System

To add more roles in the future:

1. Modify the migration to add new roles to the enum:
   ```php
   $table->enum('role', ['Admin', 'Staff', 'Manager'])->default('Staff');
   ```

2. Add new methods to `HasPermissions` trait:
   ```php
   public function isManager(): bool {
       return $this->role === 'Manager';
   }
   ```

3. Create new policy methods or middleware as needed

---

**Implementation Date:** February 13, 2026  
**Status:** ✅ Production Ready  
**Version:** 1.0
