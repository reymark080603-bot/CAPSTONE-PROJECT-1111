# Updated Library System Class Diagram


## System Actors


### **Primary Actors:**
1. **Student** - Library patron who borrows/reads books
2. **Librarian/Admin** - System administrator who manages library operations


### **Actor Responsibilities:**


#### **Student Actor:**
- Browse and search books
- Borrow books (automatic system)
- Read e-books online
- View borrowing history
- Receive notifications
- **Login via Student Portal Account (Email + Password)**


#### **Librarian/Admin Actor:**
- Manage student accounts (create/activate/deactivate)
- Manage book catalog (add/edit/delete books)
- Monitor borrowing activities
- Generate reports
- Manage system settings
- Handle administrative tasks


## Core Models and Relationships


### **Authentication System Updates (Client Requirements)**
- **Login Method**: Email + Password (Student Portal Account Integration)
- **Registration**: Disabled - no account creation functionality
- **User Management**: Portal-linked accounts (no profile editing)
- **Role-based Access**: Student, Librarian authentication
- **Profile Management**: Removed - names linked to portal system

### 1. User Model
**Purpose**: Represents both Student and Librarian/Admin actors in the system


**Attributes:**
- `id` (primary key)
- `name` (string) - **fillable**
- `firstname` (string) - **fillable**
- `mi` (string) - **fillable**
- `lastname` (string) - **fillable**
- `gender` (string) - **fillable**
- `gender_id` (foreign key → Gender.id) - **fillable**
- `library_id` (string) - **fillable**
- `year` (string) - **fillable**
- `year_level_id` (foreign key → YearLevel.id) - **fillable**
- `course` (string) - **fillable**
- `course_id` (foreign key → Course.id) - **fillable**
- `email` (string) - **fillable, Primary login identifier from portal**
- `password` (hashed string) - **fillable, hidden**
- `role` (string) - **fillable**
- `role_id` (foreign key → Role.id) - **fillable**
- `remember_token` (string) - **hidden**
- `preferences` (array) - **fillable, cast to array**
- `email_verified_at` (datetime) - **cast to datetime**
- `created_at` (timestamp)
- `updated_at` (timestamp)


**Methods:**
- `borrowRecords()` - hasMany BorrowRecord (Student: view own, Librarian: view all)
- `course()` - belongsTo Course (Student: academic info, Librarian: reporting)
- `yearLevel()` - belongsTo YearLevel (Student: academic level, Librarian: analytics)
- `role()` - belongsTo Role (System: determines permissions/access)
- `gender()` - belongsTo Gender (System: demographic data)
- `currentBorrowedBooks()` - belongsToMany Book (Student: current loans, Librarian: monitoring)
- `isStudent()` - boolean check (System: role validation)
- `isLibrarian()` - boolean check (System: role validation)
- `isAdmin()` - boolean check (System: admin privileges)
- `hasAdminPrivileges()` - boolean check (System: access control)
- `getFullNameAttribute()` - accessor (UI: display name)
- `getCustomIdAttribute()` - accessor (UI: user identification)

### 2. Book Model
**Purpose**: Core entity managed by Librarian, accessed by Students

**Attributes:**
- `id` (primary key)
- `title` (string) - **fillable**
- `description` (text) - **fillable**
- `isbn` (string) - **fillable**
- `resource_type` (string) - **fillable**
- `volume` (string) - **fillable**
- `issue` (string) - **fillable**
- `advisor` (string) - **fillable**
- `defense_date` (date) - **fillable**
- `degree` (string) - **fillable**
- `cover_photo` (string) - **fillable**
- `pdf_file` (string) - **fillable**
- `epub_file` (string) - **fillable**
- `doc_file` (string) - **fillable**
- `content` (text) - **fillable**
- `published_year` (integer) - **fillable, cast to integer**
- `availability_status` (enum: available, borrowed)
- `course` (string) - **fillable**
- `publisher_id` (foreign key → Publisher.id) - **fillable**
- `language` (string) - **fillable**
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**
- `borrowRecords()` - hasMany BorrowRecord (Librarian: management, Student: history)
- `authors()` - belongsToMany Author (Both: book information)
- `categories()` - belongsToMany Category (Both: filtering/search)
- `publisher()` - belongsTo Publisher (Both: book details)
- `currentBorrower()` - hasOne BorrowRecord (Librarian: availability, Student: borrowing status)
- `isAvailable()` - boolean check (Both: availability status)
- `scopeAvailable()` - query scope (Both: filtering)
- `scopeByCategory()` - query scope (Both: filtering)
- `scopeSearch()` - query scope (Both: search functionality)
- `scopeByCourse()` - query scope (Both: academic filtering)
- `hasPdfFile()` - boolean check (Student: reading access)
- `hasEpubFile()` - boolean check (Student: reading access)
- `hasDocFile()` - boolean check (Student: reading access)
- `hasAnyEbookFile()` - boolean check (Student: reading access)
- `hasReadableContent()` - boolean check (Student: reading access)
- `getPdfUrl()` - accessor (Student: file access)
- `getPrimaryFileUrl()` - accessor (Student: file access)
- `getPrimaryFileType()` - accessor (Student: file type)
- `getAvailableFormats()` - accessor (Student: format options)
- `getAuthorAttribute()` - accessor (Both: display)
- `getCategoryAttribute()` - accessor (Both: display)
- `getPublisherNameAttribute()` - accessor (Both: display)
- `getCustomIdAttribute()` - accessor (Both: identification)

### 3. BorrowRecord Model
**Purpose**: Tracks borrowing interactions between Students and Books, managed by Librarian

**Attributes:**
- `id` (primary key)
- `user_id` (foreign key → User.id)
- `book_id` (foreign key → Book.id)
- `borrowed_date` (datetime)
- `due_date` (datetime)
- `returned_date` (datetime)
- `status` (enum: borrowed, returned)
- `borrowing_duration` (integer)
- `renewal_count` (integer)
- `notes` (text)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**
- `user()` - belongsTo User (Librarian: student info, Student: own records)
- `book()` - belongsTo Book (Both: book details)
- `scopeBorrowed()` - query scope (Librarian: active loans, Student: current borrows)
- `scopeReturned()` - query scope (Librarian: history, Student: past borrows)
- `autoReturnIfDue()` - auto-return overdue books (System: automation)
- `processAutoReturn()` - process auto-return with duration (System: automation)
- `isEligibleForAutoReturn()` - boolean check (System: automation)
- `getBorrowingDurationAttribute()` - accessor (Both: display)
- `getDaysRemainingAttribute()` - accessor (Student: due dates, Librarian: monitoring)
- `getDaysPastDueAttribute()` - accessor (Librarian: overdue tracking)
- `isDueForAutoReturn()` - boolean check (System: automation)
- `getActualDurationAttribute()` - accessor (Both: statistics)
- `getStatusColorAttribute()` - accessor (Both: UI display)
- `getStatusDescriptionAttribute()` - accessor (Both: UI display)
- `getCustomIdAttribute()` - accessor (Both: identification)

### 4. Course Model
**Purpose**: Academic organization for Students, used by Librarian for reporting

**Attributes:**
- `id` (primary key)
- `name` (string)
- `code` (string)
- `department` (string)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**
- `users()` - hasMany User (Librarian: student management, reporting)
- `scopeByCode()` - query scope (Librarian: filtering)
- `scopeByDepartment()` - query scope (Librarian: analytics)

### 5. YearLevel Model
**Purpose**: Academic level classification for Students, used by Librarian for analytics

**Attributes:**
- `id` (primary key)
- `level` (string)
- `numeric_level` (integer, unique)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**
- `users()` - hasMany User (Librarian: student management, analytics)
- `scopeByNumericLevel()` - query scope (Librarian: filtering)
- `getDisplayNameAttribute()` - accessor (Both: display)

### 6. Role Model
**Purpose**: Defines system permissions for Student and Librarian/Admin actors

**Attributes:**
- `id` (primary key)
- `name` (string)
- `display_name` (string)
- `description` (text)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**
- `users()` - hasMany User (System: role assignment)
- `scopeByName()` - query scope (System: role filtering)
- `isStudent()` - boolean check (System: permission validation)
- `isStaff()` - boolean check (System: deprecated, use librarian)
- `isLibrarian()` - boolean check (System: permission validation)
- `hasAdminPrivileges()` - boolean check (System: access control)
- `isAdmin()` - boolean check (System: admin privileges)

### 7. Author Model
**Attributes:**
- `id` (primary key)
- `name` (string)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**
- `books()` - belongsToMany Book (via author_book)
- `getBookCountAttribute()` - accessor

### 8. Category Model
**Attributes:**
- `id` (primary key)
- `name` (string)
- `slug` (string)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**
- `books()` - belongsToMany Book (via book_category)

### 9. Publisher Model
**Attributes:**
- `id` (primary key)
- `name` (string)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**
- `books()` - hasMany Book
- `getBookCountAttribute()` - accessor

### 10. Gender Model (NEW)
**Attributes:**
- `id` (primary key)
- `name` (string)
- `abbreviation` (string)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**
- `users()` - hasMany User
- `scopeByAbbreviation()` - query scope
- `getDisplayNameAttribute()` - accessor

### 11. Notification Model (NEW)
**Attributes:**
- `id` (primary key)
- `user_id` (foreign key → User.id)
- `type` (string)
- `message` (text)
- `data` (array)
- `is_read` (boolean)
- `read_at` (datetime)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Methods:**
- `user()` - belongsTo User
- `scopeUnread()` - query scope
- `scopeRead()` - query scope
- `markAsRead()` - update method
- `markAsUnread()` - update method

## Key Relationships

### User Relationships:
- User → Course (Many-to-One)
- User → YearLevel (Many-to-One)
- User → Role (Many-to-One)
- User → Gender (Many-to-One)
- User → BorrowRecord (One-to-Many)
- User → Book (Many-to-Many via BorrowRecord)

### Book Relationships:
- Book → BorrowRecord (One-to-Many)
- Book → Publisher (Many-to-One)
- Book → Author (Many-to-Many via author_book)
- Book → Category (Many-to-Many via book_category)

### BorrowRecord Relationships:
- BorrowRecord → User (Many-to-One)
- BorrowRecord → Book (Many-to-One)

### Other Relationships:
- Course → User (One-to-Many)
- YearLevel → User (One-to-Many)
- Role → User (One-to-Many)
- Gender → User (One-to-Many)
- Publisher → Book (One-to-Many)
- Author → Book (Many-to-Many)
- Category → Book (Many-to-Many)
- Notification → User (Many-to-One)

## Important Notes

1. **Authentication Changes**: System updated per client requirements - Email + password login (portal integration)
2. **Portal Integration**: Student accounts linked to external portal system (Giant account)
3. **Profile Management**: Removed - student names managed through portal system
4. **Account Management**: Student accounts managed through portal, librarian accounts admin-created
5. **Auto-Return System**: BorrowRecord includes sophisticated auto-return functionality for overdue books
6. **Multi-Format Support**: Book model supports PDF, EPUB, DOC, and HTML content
7. **Enhanced User System**: Users have preferences, custom IDs, and multiple role-checking methods
8. **Gender Management**: Separate Gender model for better data normalization
9. **Notification System**: Complete notification framework for user communications
10. **Pivot Tables**: author_book and book_category for many-to-many relationships
11. **Soft Features**: Multiple accessor methods for UI-friendly data presentation

## Authentication Flow

### Student Login Process:
1. **Input**: Email + Password
2. **Validation**: Check email exists and password matches
3. **Role Check**: Verify user has student role
4. **Portal Integration**: Authenticate against portal account data
5. **Session**: Create student guard session
6. **Redirect**: Route to student dashboard

### Librarian Login Process:
1. **Input**: Email + Password  
2. **Validation**: Check email exists and password matches
3. **Role Check**: Verify user has librarian role
4. **Session**: Create librarian guard session
5. **Redirect**: Route to librarian dashboard

### Security Features:
- **Portal Authentication**: Students authenticate via Email from portal system
- **Account Deactivation**: Students with inactive portal accounts are blocked
- **Password Hashing**: All passwords stored using Laravel's Hash facade
- **Session Management**: Proper session regeneration on login/logout
- **Guard Separation**: Different auth guards for students vs librarians
- **No Profile Editing**: Student data managed through external portal
