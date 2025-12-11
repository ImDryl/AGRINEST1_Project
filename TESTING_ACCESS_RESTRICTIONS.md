# Testing Access Restrictions - Step by Step Guide

## Prerequisites
1. You need at least **2 user accounts**:
   - **Admin account** (with ROLE_ADMIN)
   - **Staff account** (with ROLE_STAFF, NOT ROLE_ADMIN)

## Test 1: Staff Cannot Access Admin Dashboard

### Steps:
1. **Log in as Staff user**
2. Try to access: `http://localhost:8000/admin` or `http://localhost:8000/admin/dashboard`
3. **Expected Result**: 
   - Redirected to Products page
   - See error message: "Access denied. Only administrators can access the dashboard."

### Manual URL Test:
- Type in browser: `/admin/dashboard`
- Should redirect to `/admin/products`

---

## Test 2: Staff Cannot Access Activity Logs

### Steps:
1. **Log in as Staff user**
2. Try to access: `http://localhost:8000/admin/logs`
3. **Expected Result**: 
   - Redirected to Products page
   - See error message: "Access denied. Only administrators can view activity logs."

### Manual URL Test:
- Type in browser: `/admin/logs`
- Should redirect to `/admin/products`

---

## Test 3: Staff Cannot Access User List

### Steps:
1. **Log in as Staff user**
2. Try to access: `http://localhost:8000/admin/users`
3. **Expected Result**: 
   - Redirected to Products page
   - See error message: "Access denied. Only administrators can view users."

### Manual URL Test:
- Type in browser: `/admin/users`
- Should redirect to `/admin/products`

---

## Test 4: Staff Cannot Create Users

### Steps:
1. **Log in as Staff user**
2. Try to access: `http://localhost:8000/admin/users/new`
3. **Expected Result**: 
   - Redirected to Products page
   - See error message: "Access denied. Only administrators can create users."

### Manual URL Test:
- Type in browser: `/admin/users/new`
- Should redirect to `/admin/products`

---

## Test 5: Staff Cannot Edit Users

### Steps:
1. **Log in as Staff user**
2. Get a user ID (e.g., user ID = 1)
3. Try to access: `http://localhost:8000/admin/users/1/edit`
4. **Expected Result**: 
   - Redirected to Products page
   - See error message: "Access denied. Only administrators can edit users."

### Manual URL Test:
- Type in browser: `/admin/users/1/edit` (replace 1 with actual user ID)
- Should redirect to `/admin/products`

---

## Test 6: Staff Cannot Delete Users

### Steps:
1. **Log in as Staff user**
2. Open browser Developer Tools (F12)
3. Go to Console tab
4. Try to send a POST request to delete a user:
   ```javascript
   fetch('/admin/users/1', {
       method: 'POST',
       headers: {
           'Content-Type': 'application/x-www-form-urlencoded',
       },
       body: '_token=test&_method=POST'
   })
   ```
5. **Expected Result**: 
   - Request redirected
   - Error message shown

### Alternative Test:
- Create a form in browser console and submit it
- Should redirect with error message

---

## Test 7: Staff Cannot Change System Roles

### Steps:
1. **Log in as Staff user**
2. Try to access your own profile edit (if exists)
3. **Expected Result**: 
   - Role selection field should NOT be visible
   - Only username, email, and password fields shown

### Test via Admin Account:
1. **Log in as Admin**
2. Edit a user: `/admin/users/1/edit`
3. **Expected Result**: 
   - Role selection field IS visible
   - Can select Admin/Staff roles

---

## Test 8: Staff Cannot Create Staff/Admin Accounts via Registration

### Steps:
1. **Log out** (or use incognito/private window)
2. Go to registration page: `http://localhost:8000/register`
3. Try to select "Admin" or "Staff" role
4. Submit the form
5. **Expected Result**: 
   - If roles are selected, they should be cleared
   - User created with only ROLE_USER

---

## Test 9: Verify Staff CAN Access Allowed Pages

### Steps:
1. **Log in as Staff user**
2. Try to access:
   - `/admin/products` ✅ Should work
   - `/admin/categories` ✅ Should work
   - `/admin/suppliers` ✅ Should work
   - `/profile` ✅ Should work
3. **Expected Result**: All pages load normally

---

## Test 10: Verify Admin CAN Access Everything

### Steps:
1. **Log in as Admin user**
2. Try to access:
   - `/admin/dashboard` ✅ Should work
   - `/admin/users` ✅ Should work
   - `/admin/logs` ✅ Should work
   - `/admin/products` ✅ Should work
   - `/admin/categories` ✅ Should work
   - `/admin/suppliers` ✅ Should work
3. **Expected Result**: All pages load normally

---

## Quick Test Checklist

- [ ] Staff cannot access `/admin/dashboard` → Redirects
- [ ] Staff cannot access `/admin/logs` → Redirects
- [ ] Staff cannot access `/admin/users` → Redirects
- [ ] Staff cannot access `/admin/users/new` → Redirects
- [ ] Staff cannot access `/admin/users/{id}/edit` → Redirects
- [ ] Staff cannot delete users → Redirects
- [ ] Staff cannot see role selection in forms → Hidden
- [ ] Staff CAN access `/admin/products` → Works
- [ ] Staff CAN access `/admin/categories` → Works
- [ ] Staff CAN access `/admin/suppliers` → Works
- [ ] Admin CAN access all pages → Works

---

## How to Create Test Accounts

### Option 1: Via Admin Panel (if you have admin account)
1. Log in as admin
2. Go to Users → Create New User
3. Create a staff account (select "Staff" role)

### Option 2: Via Database
1. Open phpMyAdmin or database tool
2. Go to `user` table
3. Insert a new user with roles: `["ROLE_STAFF"]` (JSON format)
4. Password should be hashed (use existing user's password hash as reference)

### Option 3: Via Registration (for regular user)
1. Go to `/register`
2. Register a new account (will be ROLE_USER by default)
3. Then manually change role in database to `["ROLE_STAFF"]`

---

## Testing Tips

1. **Use Browser Developer Tools**:
   - Press F12 to open DevTools
   - Check Network tab to see redirects
   - Check Console for any errors

2. **Check Flash Messages**:
   - Look for red error messages at top of page
   - Messages should say "Access denied..."

3. **Test Direct URL Access**:
   - Type URLs directly in address bar
   - Don't rely on navigation links (they might be hidden)

4. **Test with Different Browsers**:
   - Try incognito/private mode
   - Clear cookies if needed

5. **Check Activity Logs** (as Admin):
   - After testing, log in as admin
   - Go to Activity Logs
   - Verify that access attempts were logged (if applicable)

---

## Expected Behavior Summary

| Action | Staff User | Admin User |
|--------|-----------|------------|
| Access Dashboard | ❌ Redirected | ✅ Allowed |
| Access Activity Logs | ❌ Redirected | ✅ Allowed |
| View Users List | ❌ Redirected | ✅ Allowed |
| Create User | ❌ Redirected | ✅ Allowed |
| Edit User | ❌ Redirected | ✅ Allowed |
| Delete User | ❌ Redirected | ✅ Allowed |
| Change Roles | ❌ Field Hidden | ✅ Allowed |
| Access Products | ✅ Allowed | ✅ Allowed |
| Access Categories | ✅ Allowed | ✅ Allowed |
| Access Suppliers | ✅ Allowed | ✅ Allowed |

