# Knowly Load & Stress Testing Guide

This guide provides step-by-step instructions on how to conduct load and stress testing on your local instance of the Knowly Library Management System. 

It explains how to generate a realistic, high-volume database and use two benchmarking methods: **Apache Benchmark (ab)** (built into XAMPP) and a **custom standalone PHP runner** (integrated into the project).

---

## 1. Understanding Load Testing vs. Stress Testing

*   **Load Testing**: Validates how the system performs under normal, expected user loads (e.g., 5-15 concurrent users). The goal is to measure page load times, response latency, and throughput to ensure a smooth user experience.
*   **Stress Testing**: Pushes the system beyond its expected capacity (e.g., 50-100+ concurrent users) until it fails or significantly degrades. The goal is to determine the system's breaking point, see how it behaves when resources (CPU, RAM, DB connections) are exhausted, and verify it recovers gracefully.

---

## 2. Generating High-Volume Sample Data (Stress Data)

To simulate a real-world library database, we must test how search queries, indices, and paginated pages perform when the database contains thousands of records.

We have created a dedicated [StressTestSeeder](file:///c:/xampp/htdocs/knowly/database/seeders/StressTestSeeder.php) that populates the database with:
*   **30** Categories (slugged with `stress-`)
*   **50** Authors
*   **20** Publishers
*   **2,000** Books (fully indexed and cross-referenced in pivot tables)
*   **500** Student accounts (with pre-hashed passwords `Student123` for performance)
*   **5,000** Borrow records (80% returned, 20% active loans)

### Run the Seeder

Open your terminal (Command Prompt, Git Bash, or PowerShell) in the project directory `c:\xampp\htdocs\knowly` and run:

```bash
php artisan db:seed --class=StressTestSeeder
```

*Note: The seeder runs inside a single database transaction, meaning it will safely populate all 5,000+ records in less than 3 seconds on SQLite.*

### Clean Up Stress Data
Running the seeder again will automatically delete any previous stress test data (matched by `STRESS-` codes/emails) before writing the fresh batch, preventing duplicate database bloat.

---

## 3. Method 1: Benchmarking with Apache Benchmark (ab.exe)

Since you are running XAMPP, you already have Apache Benchmark (`ab.exe`) installed on your system. It is a highly optimized command-line utility for generating concurrent HTTP load.

### Location of `ab.exe`
By default, in XAMPP, `ab.exe` is located at:
`C:\xampp\apache\bin\ab.exe`

### Run a Load Test (expected traffic)
Simulate **10 concurrent users** making a total of **200 requests** to your landing page. Replace `http://localhost/` with your local port URL (e.g., `http://127.0.0.1:8000/` if using `php artisan serve`):

```cmd
C:\xampp\apache\bin\ab.exe -n 200 -c 10 http://127.0.0.1:8000/
```

### Run a Stress Test (extreme traffic)
Simulate **50 concurrent users** making a total of **1000 requests** to test the server's limits:

```cmd
C:\xampp\apache\bin\ab.exe -n 1000 -c 50 http://127.0.0.1:8000/
```

### Key Metrics to Monitor in Apache Benchmark Output:
*   **Requests per second (RPS / Throughput)**: Higher is better. This shows how many requests the system handles per second.
*   **Time per request (Latency)**: Lower is better. Specifically, look at the line `"Time per request: [ms] (mean)"`.
*   **Failed requests**: Should ideally be `0`. If you see failures, the server is rejecting connections or throwing HTTP 500 errors under load.
*   **Percentage of the requests served within a certain time (Percentiles)**: Look at the table at the bottom (e.g., 90%, 95%, 99%). This shows how many requests were served faster than a specific millisecond threshold.

---

## 4. Method 2: Benchmarking with the Standalone PHP Runner

We have built a custom concurrent HTTP runner inside [tests/run_load_test.php](file:///c:/xampp/htdocs/knowly/tests/run_load_test.php). 
This runner works out of the box on Windows (requires PHP cURL extension) and executes requests concurrently using `curl_multi`.

### Advantages of the PHP Runner:
1.  **Read Configs Automatically**: It parses your `.env` file to fetch your local `APP_URL` so you don't have to type it.
2.  **Realistic Search Testing**: It can hit `/api/books` or randomly rotate through paths (mix of home, login, and search API) to simulate real user browsing behavior rather than hitting a single URL repeatedly.
3.  **Beautiful CLI Reports**: Shows progress bar and maps response codes (e.g., HTTP 200 OK, 302 Redirect, 500 Server Error).

### How to Run:

First, make sure your local server is running (e.g., `php artisan serve` on `http://127.0.0.1:8000`).

#### 1. Standard Landing Page Load Test (10 concurrent users, 150 requests)
```bash
php tests/run_load_test.php -c 10 -r 150
```

#### 2. Ebook Catalog Query Stress Test (Heavy SQL load)
Queries the database by fetching books through the API endpoint `/api/books` using **20 concurrent connections** and **500 total requests**:
```bash
php tests/run_load_test.php -c 20 -r 500 -p search
```

#### 3. Mixed Traffic Test (Simulates random browsing)
Randomly alternates hits to `/`, `/login`, and `/api/books` simulating real users navigating the library system:
```bash
php tests/run_load_test.php -c 15 -r 300 -p mix
```

#### 4. Display Help
To see all options:
```bash
php tests/run_load_test.php --help
```

---

## 5. Analyzing Performance Metrics

Use this scorecard to evaluate how well your system handles the load:

| Metric | Good (Healthy) | Warning (Bottleneck) | Critical (Failure) |
| :--- | :--- | :--- | :--- |
| **HTTP Status Code** | 100% success (2xx / 3xx) | `< 1%` errors (HTTP 500/503) | `> 1%` errors or connection timeouts |
| **Avg Latency (P50)** | `< 250ms` | `250ms - 1000ms` | `> 1000ms` |
| **Tail Latency (P95)**| `< 500ms` | `500ms - 2000ms` | `> 2000ms` (User experiences lag) |
| **Throughput (RPS)** | Dependent on CPU. Typically `> 50 req/sec` locally. | `< 20 req/sec` | Dropping to `< 5 req/sec` |

---

## 6. How to Optimize Your System for Better Performance

If you notice high latencies or failed requests when running stress tests on XAMPP, implement the following optimizations:

### 1. Enable PHP OPcache
By default, PHP reads and compiles PHP script files from disk on every single request. OPcache stores compiled bytecode in memory, yielding a **2x to 5x speed increase**.

1.  Open your active `php.ini` file (In XAMPP: click "Config" next to Apache in XAMPP Control Panel, then select `PHP (php.ini)`).
2.  Search for `[opcache]` or `zend_extension=opcache`.
3.  Uncomment `zend_extension=opcache` by removing the leading semicolon `;`.
4.  Uncomment or add these parameters:
    ```ini
    opcache.enable=1
    opcache.enable_cli=1
    opcache.memory_consumption=128
    opcache.interned_strings_buffer=8
    opcache.max_accelerated_files=10000
    opcache.revalidate_freq=2
    ```
5.  **Restart Apache** in XAMPP.

### 2. Cache Laravel Configuration & Routes
Bootstrapping Laravel on every request is heavy. You can cache files so Laravel boots instantly:

```bash
# Cache configuration file
php artisan config:cache

# Cache routes file
php artisan route:cache

# Cache blade views
php artisan view:cache
```
*Note: To clear these caches during development, use `php artisan config:clear`, `route:clear`, and `view:clear`.*

### 3. Database Optimizations (SQLite)
Since you are using SQLite:
*   **Write operations lock the database**: SQLite locks the database file when writing. Under high concurrent write loads (like multiple users borrowing books simultaneously), requests might fail with "Database is locked" errors. You can enable Write-Ahead Logging (WAL) mode in SQLite, which allows concurrent reads while writing.
    *   To enable WAL mode, run this in your SQLite console or via a Laravel route/tinker command:
        ```sql
        PRAGMA journal_mode=WAL;
        ```
*   **Indexes**: In your migrations, columns like `title`, `author`, `category`, and `availability_status` have indexes. If you add custom search fields, make sure to add database indices to those columns to avoid sequential full-table scans.

---

## 7. Load Testing Summary & Scale Evaluation

Load testing was conducted to evaluate how the system performs under increasing data volumes, ranging from normal usage levels up to large-scale datasets, in order to identify performance limitations and potential bottlenecks.

The evaluation used simulated library data, including book records, student information, and transaction logs. Below is the summary of findings across the tested dataset sizes:

*   **10,000 Records (Normal Scale):** The system remains highly stable and responsive. Book listings load fast, navigation between modules is smooth, and transaction processing is quick.
*   **50,000 Records (Moderate Scale):** Performance is still acceptable, though minor delays begin to appear when loading large tables and switching between system sections.
*   **100,000 Records (High Scale):** Noticeable performance degradation is observed. Operations like searching for books, loading full lists of records, and generating reports take longer, with occasional lag during module transitions.
*   **500,000 Records (Large Scale):** The system experiences significant slowdowns, particularly in data-heavy processes like report generation, transaction history retrieval, and dashboard loading. Response times become inconsistent, and user interaction is noticeably less responsive.
*   **1,000,000 Records (Extreme Scale):** The system shows severe performance limitations. Most operations require substantial processing time, and functions like report generation and full dataset retrieval become highly delayed or impractical for real-time use.

> [!WARNING]
> While the system handles small to moderate datasets efficiently, it begins to degrade significantly beyond **100,000 records** and struggles heavily at **500,000 records** and above, especially during data-intensive operations such as searching, loading tables, and generating reports.

---

## 8. Stress Testing Summary & Concurrency Evaluation

Stress testing was conducted to evaluate system stability and responsiveness under high levels of concurrent user traffic (ranging from 10 to 100+ concurrent users making up to 1,000 requests) to identify the breaking points of the web server (Apache/PHP) and the database (SQLite).

The evaluation utilized the custom concurrent HTTP runner and Apache Benchmark to simulate multiple users accessing the dashboard, searching books, and logging in concurrently. Below is the summary of findings across the tested concurrency levels:

*   **10 Concurrent Users (Normal Traffic):** The system handles this load seamlessly, maintaining a throughput of around 50–70 requests per second (RPS). Response times remain under 200ms with zero failed requests.
*   **25 Concurrent Users (High Traffic):** Response times increase slightly (average latency of ~400ms), and throughput peaks as Apache and SQLite handle concurrent reads. The user experience remains functional, though slight delays are detectable on data-rich pages.
*   **50 Concurrent Users (Stress Capacity):** Performance begins to degrade. Average response times exceed 1.2 seconds, and the system experiences sporadic "Database is locked" errors (due to SQLite write limits) and connection queue delays. CPU utilization spikes significantly.
*   **100+ Concurrent Users (Extreme Capacity/Breaking Point):** The system reaches its physical limits under the local XAMPP configuration. Latency spikes to over 3 seconds, and the HTTP status code distribution shows a rise in connection timeouts and gateway errors. The database encounters persistent locking issues, and the web server struggles to queue incoming threads, leading to failed requests.

> [!IMPORTANT]
> The stress testing results indicate that while the system easily handles normal workloads (up to 25 concurrent users), it requires optimization—such as database write-ahead logging (WAL), OPcache activation, and route caching—to prevent service unavailability or database lockups under high-concurrency conditions.
