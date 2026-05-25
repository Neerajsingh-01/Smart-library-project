# Smart Library

A simple PHP/MySQL college library project with student and admin roles.

## Features

- Student login and admin login
- Students can search books
- Students can issue available books
- Students can request notifications for unavailable books
- Students can suggest new books
- Admin can add, edit, and delete books
- Admin can see issued book records
- Only admin can mark a book as returned
- Notification queue works on first-come-first-served priority
- Students get page alerts and browser notifications when a requested book becomes available

## Demo Login

```text
Student: STU001 / 12345
Admin:   ADM001 / admin123
```

## Setup

1. Start Apache and MySQL in XAMPP.
2. Open `setupDatabase.php` once in the browser.
3. Open `index.html` through localhost.

Example:

```text
http://localhost/ALL%20PHP/college_library_project/setupDatabase.php
http://localhost/ALL%20PHP/college_library_project/
```

## Main Files

- `index.html` - app layout
- `css/style.css` - UI styles
- `js/app.js` - frontend behavior
- `db.php` - database and auth helpers
- `issueBook.php` - student issue action
- `notify.php` - student notification request
- `suggestBook.php` - student suggestion
- `saveBook.php` - admin add/update book
- `returnBook.php` - admin return action
- `getIssuedBooks.php` - admin issued records
