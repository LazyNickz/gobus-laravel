# Registration Flow Fix Plan

## Problem
After user registration, the system automatically logs users in and redirects to `/user/reservations`. This should be changed so users must manually login after registration.

## Current Flow (INCORRECT)
1. User registers → Automatically logged in → Redirected to `/user/reservations`

## Desired Flow (CORRECT)  
1. User registers → No auto-login → Redirected to `/user-login` to login manually

## Changes Required

### 1. Update AuthController.php
- Remove automatic session setting in `register()` method
- Remove automatic session setting in `ajaxRegister()` method  
- Change redirect from `/user/reservations` to `/user-login`
- Add success message to inform user to login

### 2. Update AJAX response
- Change redirect from `/user/reservations` to `/user-login`
- Add message indicating successful registration and need to login

### 3. Update user-login view
- Add success message display for registration confirmation
- Ensure login form is clear and user-friendly

## Technical Details

### AuthController Changes:
- Remove: `$r->session()->put('gobus_user_logged', true);`
- Remove: `$r->session()->put('gobus_user_email', $user->email);`  
- Remove: `$r->session()->put('gobus_user_name', $user->name);`
- Change redirect to: `return redirect('/user-login')->with('success', 'Registration successful! Please login with your credentials.');`

### AJAX Response Changes:
- Change: `'redirect' => '/user/reservations'` to `'redirect' => '/user-login'`
- Add success message in response

## Files to Edit:
1. `app/Http/Controllers/AuthController.php` - Main fix
2. `resources/views/user-login.blade.php` - Add success message display
3. Check `public/frontend/book.js` - Update any AJAX handlers if needed

## Testing Steps:
1. Test web registration flow
2. Test AJAX registration flow  
3. Verify redirect to login page
4. Verify manual login works after registration
5. Verify success messages display correctly
