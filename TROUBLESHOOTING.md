# 🔧 Admin Dashboard Action Buttons - Troubleshooting Guide

## ✅ What I've Fixed

I've identified and fixed a **critical script loading order issue** where functions were being called before they were defined. Here's what I did:

### 1. **Moved Core Functions to First Script Tag**
- Moved `openModal()`, `closeModal()`, `openEditModal()`, `confirmDelete()`, and `updateUserStatus()` to the FIRST `<script>` tag (around line 1515)
- This ensures they're available BEFORE any HTML tries to use them in `onclick` handlers

### 2. **Made Functions Globally Available**
All action button functions are now explicitly attached to the `window` object:
```javascript
window.openModal = openModal;
window.closeModal = closeModal;
window.openEditModal = async function(userId, role) { ... };
window.confirmDelete = async function(userId, role) { ... };
window.updateUserStatus = async function(userId, status, role) { ... };
```

### 3. **Added Test Buttons**
I've added two blue test buttons in the User Management header:
- **🧪 Test Edit** - Tests the openEditModal function
- **🧪 Test Delete** - Tests the confirmDelete function

## 🧪 How to Test

### Step 1: Open the Admin Dashboard
1. Navigate to: `http://localhost/Prefix-MockTest/admin_dashboard/admin-dashboard.html`
2. Open Browser Console (Press `F12`)

### Step 2: Check Console Messages
You should see:
```
✅ Core modal functions loaded
✅ Action button handlers loaded
```

If you DON'T see these messages, the script didn't load properly.

### Step 3: Test the Blue Diagnostic Buttons
In the User Management section header, you'll see two new blue buttons:
1. Click **🧪 Test Edit**
   - Console should show: `✏️ openEditModal called: {userId: 2, role: "expert"}`
   - The Edit Profile modal should open
   
2. Click **🧪 Test Delete**
   - Console should show: `🗑️ confirmDelete called: {userId: 2, role: "expert"}`
   - The Delete Confirmation modal should open

### Step 4: Test the Actual Table Buttons
1. Go to the "Experts" tab in User Management
2. Find a user in the table
3. Click the **pencil icon** (Edit button)
   - Should see console log and modal should open
4. Click the **trash icon** (Delete button)
   - Should see console log and modal should open

## 🔍 Alternative Diagnostic Page

I've also created a standalone diagnostic page:

**URL**: `http://localhost/Prefix-MockTest/admin_dashboard/diagnostic.html`

This page will:
- ✅ Check if all functions exist on the `window` object
- ✅ Test each function individually
- ✅ Simulate dynamic button generation (like the admin dashboard does)
- ✅ Test the API endpoint
- ✅ Show all console output in a readable format

## ❌ If Buttons Still Don't Work

### Check 1: Console Errors
Open the browser console and look for:
- `❌ Modal not found: modal-edit-user` - The modal HTML is missing
- `❌ openEditModal is not a function` - Function didn't load
- `❌ Error loading user:` - API issue
- Any red error messages

### Check 2: Network Tab
1. Open DevTools → Network tab
2. Click an Edit button
3. Look for a request to `manage_user.php?action=get&user_id=X&role=expert`
4. Check if it returns 200 OK or an error

### Check 3: Verify Functions Exist
In the browser console, type:
```javascript
typeof window.openEditModal
```
Should return: `"function"`

If it returns `"undefined"`, the function didn't load.

### Check 4: Test Manually
In the browser console, try calling the function directly:
```javascript
window.openEditModal(2, 'expert')
```

This should open the edit modal. If it doesn't, check the console for errors.

## 📝 What the Functions Do

### `openEditModal(userId, role)`
1. Fetches user data from `../api/admin/manage_user.php?action=get&user_id=X&role=Y`
2. Populates the edit form fields
3. Opens the `modal-edit-user` modal

### `confirmDelete(userId, role)`
1. Opens the `modal-confirm-delete` modal
2. Sets up the "Delete Account" button to call the delete API
3. On success, reloads the page

### `updateUserStatus(userId, status, role)`
1. Sends a POST request to update the user status
2. Shows an alert with the result
3. Reloads the page

## 🐛 Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| Buttons don't respond to clicks | Functions not loaded | Check console for "✅ Action button handlers loaded" |
| Modal doesn't open | Modal HTML missing or wrong ID | Check if `modal-edit-user` exists in the HTML |
| API error | Backend issue | Check `api/admin/manage_user.php` is accessible |
| "User not found" | Invalid user ID | Check if the user ID exists in the database |

## 🎯 Next Steps

1. **Test the diagnostic buttons first** (the blue 🧪 buttons)
2. **Check the browser console** for any errors
3. **Try the diagnostic page** at `/admin_dashboard/diagnostic.html`
4. **Report back** what you see in the console when you click the buttons

The functions are now properly loaded and should work. If they still don't, the issue is likely:
- A caching problem (try Ctrl+F5 to hard refresh)
- A JavaScript error preventing the script from loading
- The user data not being fetched from the database

Let me know what you see in the console!
