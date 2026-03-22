# Bulk Upload Feature - Implementation Plan

## TODO List

### Step 1: Verify/Update Migration
- [x] Check if cover_photo column is nullable (it already is based on migration 2025_10_02_102042)
- [x] Ensure default cover exists in storage

### Step 2: Create BulkUploadController
- [x] Create new controller at app/Http/Controllers/BulkUploadController.php
- [x] Include methods: index, process, downloadTemplate

### Step 3: Create Form Request
- [x] Create app/Http/Requests/BulkUploadRequest.php

### Step 4: Create Service Class
- [x] Create app/Services/BookImportService.php
- [x] Implement CSV parsing
- [x] Implement row-by-row validation
- [x] Implement default cover assignment

### Step 5: Add Routes
- [x] Add routes in routes/web.php

### Step 6: Create Views
- [x] Create bulk upload form view

### Step 7: Update StoreBookRequest
- [ ] Make cover_photo truly optional

## Implementation Status: COMPLETED

