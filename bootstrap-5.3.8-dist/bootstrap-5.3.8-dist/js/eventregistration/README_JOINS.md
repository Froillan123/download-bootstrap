# 📚 Database JOINs Guide - Events Registration System

## 🎯 Unsa ang JOIN?

**JOIN** kay para mag-combine og data gikan sa **multiple tables** para makita nimo tanan sa **usa ka query** lang.

### Example:
Instead of:
```php
// Query 1: Get registration
$reg = $pdo->query("SELECT * FROM registration WHERE regCode = 1")->fetch();
// Result: regCode=1, partID=5, evCode=3, regDate='2024-10-20'

// Query 2: Get participant name
$part = $pdo->query("SELECT * FROM participants WHERE partID = 5")->fetch();
// Result: partID=5, partFName='Juan', partLName='Dela Cruz'

// Query 3: Get event name
$event = $pdo->query("SELECT * FROM events WHERE evCode = 3")->fetch();
// Result: evCode=3, evName='Tech Summit 2024'
```

**With JOIN (one query lang!):**
```php
$result = $pdo->query("
    SELECT r.*, p.partFName, p.partLName, e.evName
    FROM registration r
    LEFT JOIN participants p ON r.partID = p.partID
    LEFT JOIN events e ON r.evCode = e.evCode
    WHERE r.regCode = 1
")->fetch();
// Result: regCode=1, partID=5, evCode=3, regDate='2024-10-20', 
//         partFName='Juan', partLName='Dela Cruz', evName='Tech Summit 2024'
// All in one result! 🎉
```

---

## 🗄️ Database Structure (Event Registration System)

### Tables:

**1. participants**
```
┌─────────┬─────────────┬─────────────┬──────────────┐
│ partID  │ partFName   │ partLName   │ partDRate    │
├─────────┼─────────────┼─────────────┼──────────────┤
│    1    │    Juan     │  Dela Cruz  │    50.00     │
│    2    │    Maria    │   Santos    │    25.00     │
│    3    │    Pedro    │   Reyes     │     0.00     │
└─────────┴─────────────┴─────────────┴──────────────┘
```

**2. events**
```
┌─────────┬──────────────────────┬─────────────┬──────────┐
│ evCode  │      evName          │   evDate    │  evRFee  │
├─────────┼──────────────────────┼─────────────┼──────────┤
│    1    │  Tech Summit 2024    │  2024-11-15 │  500.00  │
│    2    │  Business Expo       │  2024-12-01 │  300.00  │
└─────────┴──────────────────────┴─────────────┴──────────┘
```

**3. registration** (has FOREIGN KEYS)
```
┌──────────┬─────────┬─────────┬─────────────┬──────────┐
│ regCode  │ partID  │ evCode  │   regDate   │ regRFee  │
├──────────┼─────────┼─────────┼─────────────┼──────────┤
│    1     │    1    │    1    │  2024-10-20 │  450.00  │
│    2     │    2    │    1    │  2024-10-21 │  475.00  │
│    3     │    1    │    2    │  2024-10-22 │  300.00  │
└──────────┴─────────┴─────────┴─────────────┴──────────┘
         ↑              ↑
    FOREIGN KEY    FOREIGN KEY
    (references    (references
    participants)   events)
```

---

## 🔗 Types of JOINs

### 1. LEFT JOIN (Most Common - Ginagamit sa Event Registration)

**Syntax:**
```sql
SELECT columns
FROM main_table alias1
LEFT JOIN related_table alias2 
ON alias1.foreign_key = alias2.primary_key
```

**What it does:**
- Shows **ALL records** from the LEFT table (main table)
- Shows matching records from RIGHT table
- If no match, RIGHT table columns = **NULL**

**Visual Example:**
```
registration (LEFT)          participants (RIGHT)        RESULT
───────────────              ───────────────             ───────────────
regCode | partID            partID | partFName          regCode | partID | partFName
────────┼────────           ───────┼─────────           ────────┼────────┼──────────
   1    |   1        JOIN      1   |   Juan        →       1    |   1    |   Juan
   2    |   2        with      2   |   Maria       →       2    |   2    |   Maria
   3    |   99       (no match)    |              →       3    |   99   |   NULL ✅
```

**Code Example from Event Registration (Line 593-599):**
```php
$registrations = $pdo->query("
    SELECT r.*, p.partFName, p.partLName, e.evName, e.evDate, e.evRFee
    FROM registration r
    LEFT JOIN participants p ON r.partID = p.partID
    LEFT JOIN events e ON r.evCode = e.evCode
    ORDER BY r.regDate DESC, r.created_at DESC
")->fetchAll();
```

**Breakdown:**
- `r.*` = All columns from registration table
- `p.partFName, p.partLName` = Columns from participants table
- `e.evName, e.evDate, e.evRFee` = Columns from events table
- `r` = Alias for registration (shorter to type)
- `p` = Alias for participants
- `e` = Alias for events
- `ON r.partID = p.partID` = Join condition (foreign key = primary key)
- `ON r.evCode = e.evCode` = Join condition (foreign key = primary key)

**Result:**
```
regCode | partID | evCode | regDate   | partFName | partLName  | evName
────────┼────────┼────────┼───────────┼───────────┼────────────┼──────────────
   1    |   1    |   1    | 2024-10-20|   Juan    | Dela Cruz  | Tech Summit
   2    |   2    |   1    | 2024-10-21|   Maria   | Santos     | Tech Summit
   3    |   1    |   2    | 2024-10-22|   Juan    | Dela Cruz  | Business Expo
```

---

### 2. INNER JOIN

**What it does:**
- Shows **ONLY matching records** from both tables
- Excludes records without matches

**Visual Example:**
```
registration (LEFT)          participants (RIGHT)        RESULT
───────────────              ───────────────             ───────────────
regCode | partID            partID | partFName          regCode | partID | partFName
────────┼────────           ───────┼─────────           ────────┼────────┼──────────
   1    |   1        JOIN      1   |   Juan        →       1    |   1    |   Juan
   2    |   2        with      2   |   Maria       →       2    |   2    |   Maria
   3    |   99       (no match)    |              →       ❌ EXCLUDED
```

**Code Example:**
```php
// INNER JOIN - only shows registrations with valid participants
$registrations = $pdo->query("
    SELECT r.*, p.partFName, p.partLName
    FROM registration r
    INNER JOIN participants p ON r.partID = p.partID
")->fetchAll();
```

**When to use:**
- When you ONLY want records with valid relationships
- When you don't want NULL values

---

## 📍 JOIN Examples sa Event Registration System

### Example 1: Registration Page (Line 593-599)

**Purpose:** Show all registrations with participant names and event names

```php
$registrations = $pdo->query("
    SELECT r.*, p.partFName, p.partLName, e.evName, e.evDate, e.evRFee
    FROM registration r
    LEFT JOIN participants p ON r.partID = p.partID
    LEFT JOIN events e ON r.evCode = e.evCode
    ORDER BY r.regDate DESC, r.created_at DESC
")->fetchAll();
```

**Why LEFT JOIN?**
- To show ALL registrations, even if participant or event was deleted
- Sa skills test, usually gusto nimo makita tanan transactions

**Display (Line 655-671):**
```php
<?php foreach($registrations as $reg): ?>
    <tr>
        <td><?= $reg['regCode'] ?></td>
        <td><?= htmlspecialchars($reg['evName'] ?? '') ?></td>  <!-- From events table -->
        <td><?= htmlspecialchars(getFullName($reg['partFName'] ?? '', $reg['partLName'] ?? '')) ?></td>  <!-- From participants table -->
        <td><?= htmlspecialchars($reg['regDate']) ?></td>  <!-- From registration table -->
        <td><?= number_format($reg['regRFee'], 2) ?></td>
    </tr>
<?php endforeach; ?>
```

**Note:** `?? ''` handles NULL values from LEFT JOIN

---

### Example 2: Monitoring Page (Line 678-692)

**Purpose:** Show registration records with filters and aggregate functions

```php
$monitorQuery = "
    SELECT r.*, e.evName, e.evRFee, p.partFName, p.partLName
    FROM registration r
    LEFT JOIN events e ON r.evCode = e.evCode
    LEFT JOIN participants p ON r.partID = p.partID
";
$params = [];
if ($eventFilter) {
    $monitorQuery .= " WHERE r.evCode = ? ";
    $params[] = $eventFilter;
}
$monitorQuery .= " ORDER BY r.regDate DESC, r.created_at DESC";
$stmt = $pdo->prepare($monitorQuery);
$stmt->execute($params);
$records = $stmt->fetchAll();
```

**Why multiple JOINs?**
- Need data from 3 tables: registration, events, participants
- Can chain multiple LEFT JOINs

**Display (Line 730-737):**
```php
<?php foreach($records as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['evName'] ?? '') ?></td>  <!-- From events -->
        <td><?= htmlspecialchars(getFullName($row['partFName'] ?? '', $row['partLName'] ?? '')) ?></td>  <!-- From participants -->
        <td><?= htmlspecialchars($row['regDate']) ?></td>  <!-- From registration -->
        <td><?= number_format($row['regRFee'], 2) ?></td>  <!-- From registration -->
    </tr>
<?php endforeach; ?>
```

---

## 🎓 Step-by-Step: How to Write a JOIN

### Step 1: Identify what you need
```
Question: "I need to show registration records with participant names and event names"

Tables needed:
- registration (main table - has the records we want to show)
- participants (related table - has names)
- events (related table - has event names)
```

### Step 2: Identify the relationships
```
registration.partID → participants.partID (FOREIGN KEY → PRIMARY KEY)
registration.evCode → events.evCode (FOREIGN KEY → PRIMARY KEY)
```

### Step 3: Write the JOIN
```sql
SELECT 
    r.*,                    -- All columns from registration
    p.partFName,            -- First name from participants
    p.partLName,            -- Last name from participants
    e.evName                -- Event name from events
FROM registration r         -- Main table (LEFT table)
LEFT JOIN participants p    -- Related table (RIGHT table)
    ON r.partID = p.partID  -- Join condition
LEFT JOIN events e          -- Another related table
    ON r.evCode = e.evCode  -- Join condition
```

### Step 4: Add conditions (optional)
```sql
WHERE r.regDate >= '2024-10-01'  -- Filter by date
ORDER BY r.regDate DESC          -- Sort results
```

---

## 🔑 Key Points to Remember

### 1. JOIN Condition
```sql
ON table1.foreign_key = table2.primary_key
```
- Always: **Foreign Key = Primary Key**
- From your code: `r.partID = p.partID` (registration's foreign key = participants' primary key)

### 2. Table Aliases
```sql
FROM registration r
LEFT JOIN participants p
```
- `r` = alias for registration (shorter to type)
- `p` = alias for participants
- Use aliases in SELECT and ON clauses

### 3. Column Names
```sql
SELECT r.regCode, p.partFName, e.evName
```
- Use alias prefix: `r.regCode` (from registration table)
- Prevents confusion if columns have same name

### 4. Handling NULL Values
```php
<?= htmlspecialchars($reg['evName'] ?? '') ?>
```
- LEFT JOIN can return NULL if no match
- Always use `?? ''` or check for NULL

---

## 🎯 Common JOIN Patterns for Skills Tests

### Pattern 1: Master-Detail (One-to-Many)
```sql
-- Show orders with customer names
SELECT o.*, c.customerName
FROM orders o
LEFT JOIN customers c ON o.customerID = c.customerID
```

### Pattern 2: Transaction with Multiple Relations
```sql
-- Show transactions with related data from multiple tables
SELECT t.*, 
       c.customerName,
       p.productName,
       s.supplierName
FROM transactions t
LEFT JOIN customers c ON t.customerID = c.customerID
LEFT JOIN products p ON t.productID = p.productID
LEFT JOIN suppliers s ON p.supplierID = s.supplierID
```

### Pattern 3: Filtered JOIN
```sql
-- Show registrations for specific event
SELECT r.*, p.partFName, p.partLName
FROM registration r
LEFT JOIN participants p ON r.partID = p.partID
WHERE r.evCode = 1  -- Filter by event
```

---

## ❓ FAQ

### Q: Nganong LEFT JOIN man, dili INNER JOIN?
**A:** LEFT JOIN shows ALL records from main table, even if related record doesn't exist. Sa skills test, usually gusto nimo makita tanan transactions.

### Q: Pwede ba multiple JOINs?
**A:** Oo! Pwede ka mag-chain og multiple JOINs:
```sql
SELECT ...
FROM table1 t1
LEFT JOIN table2 t2 ON t1.fk = t2.pk
LEFT JOIN table3 t3 ON t1.fk2 = t3.pk
LEFT JOIN table4 t4 ON t2.fk = t4.pk
```

### Q: Unsaon pag-identify kung unsa ang foreign key?
**A:** 
- Foreign key = column nga nag-reference sa ubang table
- Sa registration table: `partID` ug `evCode` kay foreign keys
- Sila nag-reference sa `participants.partID` ug `events.evCode`

### Q: Nganong naa man `?? ''` sa display?
**A:** LEFT JOIN can return NULL if walay match. `?? ''` prevents errors:
```php
$reg['evName'] ?? ''  // If evName is NULL, use empty string instead
```

---

## 📝 Quick Reference

### Basic JOIN Syntax
```sql
SELECT columns
FROM main_table alias
LEFT JOIN related_table alias2 
ON alias.foreign_key = alias2.primary_key
```

### Multiple JOINs
```sql
SELECT columns
FROM table1 t1
LEFT JOIN table2 t2 ON t1.fk1 = t2.pk
LEFT JOIN table3 t3 ON t1.fk2 = t3.pk
```

### With WHERE clause
```sql
SELECT columns
FROM table1 t1
LEFT JOIN table2 t2 ON t1.fk = t2.pk
WHERE t1.status = 'active'
```

### With ORDER BY
```sql
SELECT columns
FROM table1 t1
LEFT JOIN table2 t2 ON t1.fk = t2.pk
ORDER BY t1.date DESC
```

---

## 🎉 Summary

1. **JOIN** = Combine data from multiple tables
2. **LEFT JOIN** = Show all from left table, matching from right (most common)
3. **INNER JOIN** = Show only matching records
4. **Join condition** = `foreign_key = primary_key`
5. **Always handle NULL** = Use `?? ''` in PHP
6. **Use aliases** = Shorter and clearer code

**Remember:** Foreign key column = JOIN condition! 🎯

---

*Created for Events Registration System - Skills Test Reference*

