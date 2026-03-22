# Library Management System - Class Diagram

## Overview
This is a comprehensive class diagram for the Library Management System built with Laravel framework.

## Models Layer

### User (extends Authenticatable)
```
+-----------------------------+
|           User              |
+-----------------------------+
| - name: string              |
| - firstname: string         |
| - mi: string                |
| - lastname: string          |
| - gender: string            |
| - gender_id: foreignKey     |
| - library_id: string        |
| - year: string              |
| - year_level_id: foreignKey |
| - course: string            |
| - course_id: foreignKey     |
| - email: string (Primary login) |
| - password: string (hidden, hashed) |
| - role: string              |
| - role_id: foreignKey       |
| - remember_token: string (hidden) |
| - preferences: array (cast) |
| - email_verified_at: datetime (cast) |
+-----------------------------+
| + borrowRecords()           |
| + currentBorrowedBooks()    |
| + isStudent(): bool         |
| + isLibrarian(): bool         |
| + hasAdminPrivileges(): bool |
| + course()                   |
| + yearLevel()               |
| + gender()                  |
| + role()                    |
| + getFullNameAttribute()    |
| + getCustomIdAttribute()    |
+-----------------------------+
```

### Book (extends Model)
```
+-----------------------------+
|            Book             |
+-----------------------------+
| - title: string             |
| - description: text         |
| - isbn: string              |
| - resource_type: string     |
| - volume: string            |
| - issue: string             |
| - advisor: string           |
| - defense_date: date        |
| - degree: string            |
| - cover_photo: string       |
| - pdf_file: string          |
| - epub_file: string         |
| - doc_file: string          |
| - content: text             |
| - copyright_year: int (cast) |
| - availability_status: enum |
| - course: string            |
| - publisher_id: foreignKey  |
| - language: string          |
+-----------------------------+
| + borrowRecords()           |
| + authors()                 |
| + categories()              |
| + publisher()               |
| + currentBorrower()         |
| + isAvailable(): bool       |
| + scopeAvailable()          |
| + scopeByCategory()         |
| + scopeSearch()             |
| + scopeByCourse()           |
| + hasPdfFile(): bool        |
| + hasEpubFile(): bool       |
| + hasDocFile(): bool        |
| + hasAnyEbookFile(): bool  |
| + hasReadableContent(): bool|
| + getPdfUrl()               |
| + getPrimaryFileUrl()       |
| + getPrimaryFileType()      |
| + getAvailableFormats()     |
| + getAuthorAttribute()      |
| + getCategoryAttribute()    |
| + getPublisherNameAttribute() |
| + getCustomIdAttribute()    |
+-----------------------------+
```

### BorrowRecord (extends Model)
```
+----------------------------+
|       BorrowRecord         |
+----------------------------+
| - user_id: foreignKey      |
| - book_id: foreignKey      |
| - borrowed_date: datetime   |
| - due_date: datetime       |
| - returned_date: datetime  |
| - status: enum             |
| - borrowing_duration: int  |
| - renewal_count: int       |
| - notes: text              |
+----------------------------+
| + user()                   |
| + book()                   |
| + scopeBorrowed()          |
| + scopeReturned()          |
| + scopeDueToday()          |
| + autoReturnIfDue()        |
| + getDaysRemaining(): int  |
+----------------------------+
```

### Author (extends Model)
```
+-----------------------------+
|           Author            |
+-----------------------------+
| - name: string              |
+-----------------------------+
| + books()                   |
+-----------------------------+
```

### Category (extends Model)
```
+-----------------------------+
|          Category           |
+-----------------------------+
| - name: string              |
| - slug: string              |
+-----------------------------+
| + books()                   |
+-----------------------------+
```

### Publisher (extends Model)
```
+-----------------------------+
|         Publisher           |
+-----------------------------+
| - name: string              |
+-----------------------------+
| + books()                   |
+-----------------------------+
```

### Notification (extends Model)
```
+-----------------------------+
|       Notification          |
+-----------------------------+
| - user_id: foreignKey       |
| - type: string              |
| - message: text             |
| - data: json                |
| - is_read: boolean          |
| - read_at: datetime         |
+-----------------------------+
| + user()                    |
| + scopeUnread()             |
| + scopeRead()               |
| + markAsRead()              |
| + markAsUnread()            |
+-----------------------------+
```

### Course (extends Model)
```
+-----------------------------+
|          Course             |
+-----------------------------+
| - name: string              |
| - code: string              |
| - department: string        |
+-----------------------------+
| + users()                   |
| + scopeByCode()             |
| + scopeByDepartment()       |
+-----------------------------+
```

### YearLevel (extends Model)
```
+-----------------------------+
|        YearLevel            |
+-----------------------------+
| - level: string             |
| - numeric_level: int        |
+-----------------------------+
| + users()                   |
| + scopeByNumericLevel()     |
| + getDisplayNameAttribute() |
+-----------------------------+
```

### Gender (extends Model)
```
+-----------------------------+
|          Gender             |
+-----------------------------+
| - name: string              |
| - abbreviation: string      |
+-----------------------------+
| + users()                   |
| + scopeByAbbreviation()     |
| + getDisplayNameAttribute() |
+-----------------------------+
```

### Role (extends Model)
```
+-----------------------------+ 
|            Role             |
+-----------------------------+
| - name: string              |
| - display_name: string       |
+-----------------------------+
| + users()                   |
| + scopeByName()             | 
| + isStudent(): bool         |
| + isLibrarian(): bool       |
| + hasAdminPrivileges(): bool |
+-----------------------------+
```

## Controllers Layer

### Main Controllers
```
+-----------------------------+
|        Controllers          |
+-----------------------------+
| - HomeController            |
| - BookController            |
| - BorrowingController       |
| - HistoryController         |
+-----------------------------+
```

### Authentication Controllers
```
+-----------------------------+
|     Auth Controllers        |
+-----------------------------+
| - LoginController           |
+-----------------------------+
```

### Librarian Controllers
+-----------------------------+
|    Librarian Controllers    |
+-----------------------------+
| - LibrarianController       |
| - LibrarianDashboardController|
| - LibrarianBookController   |
| - LibrarianStudentController|
| - LibrarianLoanController   |
| - LibrarianReportController |
+-----------------------------+

### System Actors
### **Primary Actors:**
1. **Student** - Library patron who borrows/reads books
2. **Librarian** - Library staff who manages operations
```
+-----------------------------+
|    Student Controllers      |
+-----------------------------+
| - StudentDashboardController|
+-----------------------------+
```

## Services Layer

```
+-----------------------------+
|         Services            |
+-----------------------------+
| - BookService               |
| - LoanService               |
| - StudentService            |
| - ReportService             |
| - LibrarianDashboardService |
+-----------------------------+
```

## Repositories Layer

```
+-----------------------------+
|       Repositories          |
+-----------------------------+
| - BookRepository            |
| - BorrowRecordRepository    |
| - UserRepository            |
+-----------------------------+
```

## Data Models Layer

### BookManagement (extends Model)
```
+-----------------------------+
|      BookManagement         |
+-----------------------------+
| + getFilteredBooks()        |
| + getAvailableCategories()  |
| + getBookStatistics()       |
| + getBooksForStudent()      |
| + getBookWithBorrowInfo()    |
| + getRecentlyAdded()        |
| + getFeaturedBooks()        |
+-----------------------------+
```

### DashboardData (extends Model)
```
+-----------------------------+
|       DashboardData         |
+-----------------------------+
| + getStudentStats()         |
| + getRecommendedBooks()     |
| + getRecentBooks()          |
| + getPopularBooks()         |
| + getReadingHistory()       |
+-----------------------------+
```

### LibrarianDashboardData (extends Model)
```
+-----------------------------+
|   LibrarianDashboardData    |
+-----------------------------+
| + getBasicStats()           |
| + getBooksByStatus()        |
| + getMonthlyTrends()        |
| + getPopularCategories()    |
| + getMostBorrowedBooks()    |
| + getTodaysSummary()        |
+-----------------------------+
```

## Frontend JavaScript Classes

### BooksManager (Frontend Class)
```
+-----------------------------+
|       BooksManager          |
+-----------------------------+
| - state: object             |
|   - searchTerm: string      |
|   - allBooks: array         |
|   - filteredBooks: array    |
|   - currentPage: int        |
|   - booksPerPage: int       |
|   - totalPages: int         |
| - elements: object          |
|   - booksContainer          |
|   - searchInput             |
|   - resultsCount            |
+-----------------------------+
| + init()                    |
| + setupEventListeners()     |
| + loadBooks()               |
| + extractBooksFromDOM()     |
| + filterBooks()             |
| + renderBooks()             |
| + renderPaginationControls()|
| + generatePageNumbers()     |
| + goToPage(page)            |
| + clearSearch()             |
| + createBookCard(book)      |
| + updateResultsCount()      |
| + showBorrowPopup(bookId)   |
| + hideBorrowPopup()         |
| + confirmBorrow()           |
| + filterRecommendedBooks()  |
| + showAllBooksWithPagination()|
| + hideCourseSections()      |
| + showCourseSections()      |
| + showUserCourseSection()   |
| + alwaysHideBSNSection()     |
| + clearFilter()             |
| + limitText(text, n)        |
| + escapeHtml(text)          |
+-----------------------------+
```

### StudentDashboard (Frontend Class)
```
+-----------------------------+
|    StudentDashboard        |
+-----------------------------+
| - state: object             |
| - elements: object          |
+-----------------------------+
| + init()                    |
| + setupEventListeners()     |
| + borrowBookQuick(bookId)   |
| + showNotification()        |
| + loadDashboardData()       |
| + updateBorrowingHistory()  |
+-----------------------------+
```

## Frontend Components

### UI Components
```
+-----------------------------+
|      UI Components          |
+-----------------------------+
| - BookCard Component        |
| - PaginationComponent       |
| - SearchComponent           |
| - BorrowPopupComponent      |
| - NotificationComponent     |
| - FilterComponent           |
+-----------------------------+
```

### View Templates
```
+-----------------------------+
|       View Templates        |
+-----------------------------+
| - dashboard/books.blade.php |
| - Student dashboard views   |
| - Librarian views           |
| - Auth views (login only)   |
| - PDF viewer component      |
+-----------------------------+
```

## Relationships

### User Relationships
- User 1..* BorrowRecord (hasMany)
- User *..* Book (through BorrowRecord)
- User 1..1 Course (belongsTo)
- User 1..1 YearLevel (belongsTo)
- User 1..1 Gender (belongsTo)
- User 1..1 Role (belongsTo)

### Book Relationships  
- Book 1..* BorrowRecord (hasMany)
- Book *..* Author (many-to-many through author_book)
- Book *..* Category (many-to-many through book_category)
- Book 1..1 Publisher (belongsTo)

### BorrowRecord Relationships
- BorrowRecord 1..1 User (belongsTo)
- BorrowRecord 1..1 Book (belongsTo)

### Author Relationships
- Author *..* Book (many-to-many through author_book)

### Category Relationships
- Category *..* Book (many-to-many through book_category)

### Publisher Relationships
- Publisher 1..* Book (hasMany)

### Course Relationships
- Course 1..* User (hasMany)
- Course *..* Book (through course field)

### YearLevel Relationships
- YearLevel 1..* User (hasMany)

### Gender Relationships
- Gender 1..* User (hasMany)

### Role Relationships
- Role 1..* User (hasMany)

### Notification Relationships
- Notification 1..1 User (belongsTo)

## Architecture Pattern

This system follows a **Repository Pattern** with **Service Layer** architecture:

```
┌─────────────────┐
│   Controllers   │ ──► Services ──► Repositories ──► Models
└─────────────────┘
        │
        ▼
┌─────────────────┐
│     Views       │
└─────────────────┘
```

## Key Features Supported by Classes

1. **User Management**: Role-based access (Student/Librarian) - Portal Account Integration
2. **Book Management**: Multi-format support (PDF, EPUB, DOC)
3. **Borrowing System**: Track loans, due dates, returns
4. **Catalog System**: Authors, Categories, Publishers
5. **Notification System**: User notifications
6. **Search & Filter**: Advanced book search capabilities
7. **Dashboard Analytics**: Separate dashboards for students and librarians
8. **Academic Structure**: Course, Year Level, and Gender management
9. **Data Analytics**: Comprehensive statistics and reporting
10. **Book Management Service**: Centralized book operations
11. **Portal Authentication**: Student portal account login (Library ID based)

## Data Flow

1. **Request Flow**: Controller → Service → Repository → Model → Database
2. **Response Flow**: Database → Model → Repository → Service → Controller → View
3. **Authentication**: LoginController handles student portal authentication (Library ID)
4. **Authorization**: Role-based access control through User model

This architecture provides separation of concerns, testability, and maintainability for the library management system.
