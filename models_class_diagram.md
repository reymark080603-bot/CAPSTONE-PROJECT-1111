# Library Management System - Models Class Diagram

## Overview
This document contains the complete class diagram for all models in the Library Management System.

---

## User (extends Authenticatable)
```
+-----------------------------+
|           User              |
+-----------------------------+
| - id: bigint                |
| - name: string              |
| - firstname: string         |
| - mi: string                |
| - lastname: string          |
| - gender: string            |
| - library_id: string        |
| - year: string              |
| - course: string            |
| - email: string             |
| - email_verified_at: datetime|
| - password: string          |
| - role: enum ('staff', 'student') |
| - remember_token: string    |
| - created_at: datetime      |
| - updated_at: datetime      |
+-----------------------------+
| + borrowRecords()           |
| + currentBorrowedBooks()    |
| + isStudent(): bool         |
| + librarian(): bool         |
| + getFullNameAttribute(): string|
+-----------------------------+
```

---

## Book (extends Model)
```
+-----------------------------+
|            Book             |
+-----------------------------+
| - id: bigint                |
| - title: string             |
| - author: string            |
| - category: string          |
| - description: text         |
| - cover_photo: string       |
| - pdf_file: string          |
| - epub_file: string         |
| - doc_file: string          |
| - file_type: string         |
| - content: text             |
| - publication_year: int     |
| - availability_status: enum |
| - course: string            |
| - publisher_id: foreignKey  |
| - language: string          |
| - isbn: string              |
| - pages: int                |
| - created_at: datetime      |
| - updated_at: datetime      |
+-----------------------------+
| + borrowRecords()           |
| + isAvailable(): bool       |
| + currentBorrower()         |
| + scopeAvailable()          |
| + scopeByCategory()         |
| + scopeSearch()             |
| + scopeByCourse()           |
| + hasPdfFile(): bool        |
| + getPdfUrl()               |
| + hasReadableContent(): bool|
| + getAvailableFormats()     |
| + authors()                 |
| + categories()              |
| + publisher()               |
| + getCoverUrlAttribute(): string|
+-----------------------------+
```

---

## BorrowRecord (extends Model)
```
+----------------------------+
|       BorrowRecord         |
+----------------------------+
| - id: bigint               |
| - user_id: foreignKey      |
| - book_id: foreignKey      |
| - borrowed_date: datetime   |
| - due_date: datetime       |
| - returned_date: datetime  |
| - status: enum             |
| - borrowing_duration: int  |
| - renewal_count: int       |
| - notes: text              |
| - created_at: datetime     |
| - updated_at: datetime     |
+----------------------------+
| + user()                   |
| + book()                   |
| + isOverdue(): bool        |
| + daysOverdue(): int       |
| + scopeBorrowed()          |
| + scopeReturned()          |
| + scopeOverdue()           |
| + scopeDueToday()          |
| + autoReturnIfDue()        |
| + getDaysRemaining(): int  |
| + getStatusColorAttribute(): string|
+----------------------------+
```

---

## Author (extends Model)
```
+-----------------------------+
|           Author            |
+-----------------------------+
| - id: bigint                |
| - name: string              |
| - created_at: datetime      |
| - updated_at: datetime      |
+-----------------------------+
| + books()                   |
| + getBookCountAttribute(): int|
+-----------------------------+
```

---

## Category (extends Model)
```
+-----------------------------+
|          Category           |
+-----------------------------+
| - id: bigint                |
| - name: string              |
| - slug: string              |
| - description: text         |
| - color: string             |
| - icon: string              |
| - parent_id: foreignKey     |
| - created_at: datetime      |
| - updated_at: datetime      |
+-----------------------------+
| + books()                   |
| + parent()                  |
| + children()                |
| + getBookCountAttribute(): int|
+-----------------------------+
```

---

## Publisher (extends Model)
```
+-----------------------------+
|         Publisher           |
+-----------------------------+
| - id: bigint                |
| - name: string              |
| - created_at: datetime      |
| - updated_at: datetime      |
+-----------------------------+
| + books()                   |
| + getBookCountAttribute(): int|
+-----------------------------+
```

---

## Notification (extends Model)
```
+-----------------------------+
|       Notification          |
+-----------------------------+
| - id: bigint                |
| - user_id: foreignKey       |
| - type: string              |
| - title: string             |
| - message: text             |
| - data: json                |
| - is_read: boolean          |
| - read_at: datetime         |
| - created_at: datetime      |
| - updated_at: datetime      |
+-----------------------------+
| + user()                    |
| + scopeUnread()             |
| + scopeRead()               |
| + markAsRead()              |
| + markAsUnread()            |
| + getFormattedDateAttribute(): string|
+-----------------------------+
```

---

## BookManagement (extends Model)
```
+-----------------------------+
|      BookManagement         |
+-----------------------------+
| - id: bigint                |
| - book_id: foreignKey       |
| - managed_by: foreignKey    |
| - action: enum              |
| - description: text         |
| - created_at: datetime      |
| - updated_at: datetime      |
+-----------------------------+
| + book()                    |
| + manager()                 |
+-----------------------------+
```

---

## Model Relationships

### User Relationships
- **User 1..* BorrowRecord** (hasMany)
- **User *..* Book** (through BorrowRecord)
- **User 1..* Notification** (hasMany)
- **User 1..* BookManagement** (hasMany as managed_by)

### Book Relationships  
- **Book 1..* BorrowRecord** (hasMany)
- **Book *..* Author** (many-to-many through author_book)
- **Book *..* Category** (many-to-many through book_category)
- **Book 1..1 Publisher** (belongsTo)
- **Book 1..* BookManagement** (hasMany)

### BorrowRecord Relationships
- **BorrowRecord 1..1 User** (belongsTo)
- **BorrowRecord 1..1 Book** (belongsTo)

### Author Relationships
- **Author *..* Book** (many-to-many through author_book)

### Category Relationships
- **Category *..* Book** (many-to-many through book_category)
- **Category 1..1 Category** (self-referential parent/child)

### Publisher Relationships
- **Publisher 1..* Book** (hasMany)

### Notification Relationships
- **Notification 1..1 User** (belongsTo)

### BookManagement Relationships
- **BookManagement 1..1 Book** (belongsTo)
- **BookManagement 1..1 User** (belongsTo as managed_by)

---

## Database Schema Summary

### Tables
1. **users** - User accounts and authentication
2. **books** - Book catalog and metadata
3. **borrow_records** - Book borrowing transactions
4. **authors** - Book authors
5. **categories** - Book categories with hierarchy
6. **publishers** - Book publishers
7. **notifications** - User notifications
8. **book_management** - Book management logs
9. **author_book** - Pivot table for books-authors
10. **book_category** - Pivot table for books-categories

### Key Features
- **Role-based access control** (Student, Staff, Admin)
- **Multi-format book support** (PDF, EPUB, DOC)
- **Comprehensive borrowing system** with fines and renewals
- **Hierarchical categorization** system
- **Notification system** for users
- **Audit trail** for book management
- **Advanced search and filtering** capabilities

This model structure provides a robust foundation for a complete library management system with all necessary relationships and features.
