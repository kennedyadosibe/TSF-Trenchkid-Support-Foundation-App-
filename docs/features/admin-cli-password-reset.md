# Admin CLI Password Reset

## Summary

Added a command-line utility for safely resetting the admin dashboard password without storing plain text passwords in the project or database.

## File

`tools/reset_admin_password.php`

## How It Works

- Runs only from PHP CLI, not through a browser.
- Uses PHP `password_hash()` to store only a bcrypt-compatible password hash in the `admin.password` column.
- Clears failed login lockout values after a reset.
- Deletes old password reset tokens for the admin account after a successful CLI reset.
- Can generate a strong temporary password or read a password from standard input.

## Commands

Generate a new temporary password:

```text
php tools/reset_admin_password.php --username=tsf_admin --generate
```

Read a password from standard input:

```text
echo "NewStrongPassword123!" | php tools/reset_admin_password.php --username=tsf_admin --password-stdin
```

Update the recovery email at the same time:

```text
php tools/reset_admin_password.php --username=tsf_admin --email=admin@example.com --generate
```

## Notes

Do not commit generated passwords or real secrets. The generated password is printed once so the admin can log in, then it should be changed or stored securely.
