# Staff Management - Quick Reference Card

## 🔑 Key Files & Their Purpose

| File | Purpose | Type |
|------|---------|------|
| `AdminMiddleware.php` | Protect routes - only admins | Middleware |
| `EnsureUserIsActive.php` | Logout inactive users | Middleware |
| `StoreStaffRequest.php` | Validate create requests | Form Request |
| `UpdateStaffRequest.php` | Validate update requests | Form Request |
| `HasPermissions.php` | Helper methods on User | Trait |
| `PermissionService.php` | Static permission checks | Service |
| `UserPolicy.php` | Blade authorization checks | Policy |
| `StaffController.php` | CRUD operations | Controller |

---

## 📍 Routes

All require admin role + authentication:

```
GET    /staff                    List all staff
POST   /staff                    Create staff
GET    /staff/{id}               View staff
PUT    /staff/{id}               Update staff
DELETE /staff/{id}               Delete staff
PATCH  /staff/{id}/status        Change status
```

---

## 🔐 Authorization

### Middleware
```php
// Applied to all staff routes
middleware('admin')  // Only admins can access

// Applied globally to protected routes
middleware('active') // Inactive users are logged out
```

### User Model Methods
```php
$user->isAdmin()              // Is admin?
$user->isStaff()              // Is staff?
$user->isActive()             // Is active?
$user->canManageStaff()       // Can manage staff?
$user->canBeDeleted()         // Can be deleted?
```

### Service Methods
```php
PermissionService::isAdmin()           // Current user admin?
PermissionService::countStaff()        // Total staff count
PermissionService::countActiveStaff()  // Active staff count
PermissionService::getAdmins()         // Get admin list
```

---

## ✍️ Validation Rules

### Create/Update
| Field | Rules | Type |
|-------|-------|------|
| name | required, string, max:255 | Create & Update |
| email | required, email, unique | Create & Update |
| phone | required (create), nullable (update) | Create & Update |
| password | required (create), optional (update) | Create & Update |
| role | required, in:Admin,Staff | Create & Update |
| status | required (create), optional (update) | Create & Update |

---

## 🎯 Common Operations

### Check if Current User is Admin
```php
if (auth()->user()->isAdmin()) {
    // Admin code
}
```

### In Blade Template
```blade
@if(auth()->user()->isAdmin())
    <a href="{{ route('staff.index') }}">Staff</a>
@endif
```

### Get Staff Count
```php
use App\Services\PermissionService;
$count = PermissionService::countStaff();
```

### Create Staff Member
```php
User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '1234567890',
    'password' => bcrypt('password'),
    'role' => 'Staff',
    'status' => 'Active'
]);
```

### Delete Staff Member
```php
$user = User::find($id);
if ($user->canBeDeleted()) {
    $user->delete();
} else {
    // Cannot delete (only admin)
}
```

---

## 🔄 Request/Response

### Create Staff Request
```json
POST /staff
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "1234567890",
  "password": "securepass123",
  "password_confirmation": "securepass123",
  "role": "Staff",
  "status": "Active"
}

Response:
{
  "success": true,
  "message": "Staff member created successfully"
}
```

### Update Staff Request
```json
PUT /staff/1
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "987654321",
  "password": null,
  "role": "Admin",
  "status": "Active"
}

Response:
{
  "success": true,
  "message": "Staff member updated successfully"
}
```

### Change Status Request
```json
PATCH /staff/1/status
{
  "status": "Inactive"
}

Response:
{
  "success": true,
  "message": "Staff status updated successfully"
}
```

---

## 🚫 Error Responses

| Status | Message | Cause |
|--------|---------|-------|
| 403 | Admin role required | Non-admin tried to access |
| 404 | Staff member not found | Invalid user ID |
| 422 | Cannot delete the only admin | Deletion prevented |
| 422 | Validation failed | Invalid input data |
| 500 | Failed to create/update | Server error |

---

## 🧪 Database Query Examples

```php
// Get all admins
$admins = User::where('role', 'Admin')->get();

// Get active staff
$active = User::where('status', 'Active')->get();

// Get active admins
$activeAdmins = User::where('role', 'Admin')
    ->where('status', 'Active')
    ->get();

// Count by role
$count = User::where('role', 'Staff')->count();

// Count active
$activeCount = User::where('status', 'Active')->count();
```

---

## 🛡️ Safety Features

1. **Last Admin Protected** - Cannot delete the only admin
2. **Password Hashing** - All passwords bcrypted automatically
3. **Email Unique** - Duplicate emails prevented
4. **Status Check** - Inactive users logged out automatically
5. **CSRF Protected** - All forms protected
6. **Authorization** - Admin middleware on all routes
7. **Validation** - Form requests validate all input

---

## 🔧 Configuration

### In bootstrap/app.php
```php
$middleware->alias([
    'admin' => AdminMiddleware::class,
    'active' => EnsureUserIsActive::class,
]);
```

### In AppServiceProvider.php
```php
Gate::policy(User::class, UserPolicy::class);
```

### In routes/web.php
```php
Route::middleware('admin')->group(function () {
    Route::resource('staff', StaffController::class);
});
```

---

## 📖 Documentation Files

| File | Content |
|------|---------|
| STAFF_MANAGEMENT.md | Full API documentation |
| STAFF_MANAGEMENT_EXAMPLES.md | Code examples & patterns |
| SETUP_GUIDE.md | Installation & quick start |
| STAFF_MANAGEMENT_CHECKLIST.md | Implementation summary |
| QUICK_REFERENCE.md | This file |

---

## 🎓 Learning Path

1. Read SETUP_GUIDE.md - Understand the system
2. Check STAFF_MANAGEMENT_EXAMPLES.md - See how to use
3. Review STAFF_MANAGEMENT.md - Learn all API details
4. Look at STAFF_MANAGEMENT_CHECKLIST.md - Understand architecture
5. Use this QUICK_REFERENCE.md - Daily reference

---

## ⚡ Key Points to Remember

✅ All staff routes require admin role  
✅ Passwords must be hashed with bcrypt  
✅ Status = 'Inactive' means logged out  
✅ Cannot delete the only admin  
✅ Email must be unique  
✅ Use HasPermissions methods on User model  
✅ Use PermissionService for static checks  
✅ Use UserPolicy for blade directives  

---

## 🆘 Troubleshooting

**User can't access staff management**
→ Check if `role = 'Admin'` in database

**Getting 419 CSRF error**
→ Include `@csrf` in form

**User getting logged out**
→ Check if `status = 'Active'` (Inactive auto-logout)

**Can't delete user**
→ Check if it's the only admin

**Routes not working**
→ Verify in routes/web.php middleware applied

---

**Quick Reference Version:** 1.0  
**Last Updated:** February 13, 2026
