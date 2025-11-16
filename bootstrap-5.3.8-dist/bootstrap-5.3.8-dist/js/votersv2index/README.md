# Single-File Voting System

A complete voting system in a single PHP file with automatic database creation and full CRUD functionality.

## Features

- ✅ **Auto Database Creation** - Automatically creates database and tables on first access
- ✅ **Full CRUD Operations** - Create, Read, Update, Delete for Positions, Candidates, and Voters
- ✅ **Voting System** - Cast votes with support for single and multiple selections
- ✅ **Winners Calculation** - Automatically calculate winners based on vote counts
- ✅ **Results Display** - View voting results with pagination
- ✅ **Settings Management** - Configure voting period and status
- ✅ **SQL Joins** - Efficient queries using JOIN operations
- ✅ **No Authentication** - Direct access, no login required
- ✅ **Bootstrap 5 UI** - Clean, responsive design

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- XAMPP/WAMP/LAMP (or any PHP server)
- Web browser

## Installation

1. **Copy the file** to your web server directory:
   ```
   Copy votersv2index/index.php to your htdocs/www directory
   ```

2. **Configure Database** (if needed):
   - Open `index.php`
   - Edit the database configuration at the top:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'election');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Start your server**:
   - Start XAMPP/WAMP control panel
   - Start Apache and MySQL services

4. **Access the system**:
   - Open browser and go to: `http://localhost/votersv2index/`
   - The database and tables will be created automatically on first access

## Usage Guide

### 1. Dashboard
- Default page showing statistics
- Quick actions: Calculate Winners, Reset Voting Session

### 2. Positions Management
- **Add Position**: Click "Add Position" button
  - Enter position name
  - Set maximum number of winners
- **Edit Position**: Click "Edit" button on any position
- **Delete Position**: Click "Delete" button (only if no candidates assigned)

### 3. Candidates Management
- **Add Candidate**: Click "Add Candidate" button
  - Enter candidate full name
  - Select position from dropdown
- **Edit Candidate**: Click "Edit" button on any candidate
- **Delete Candidate**: Click "Delete" button

### 4. Voters Management
- **Add Voter**: Click "Add Voter" button
  - Enter full name
  - Enter email address
- **Edit Voter**: Click "Edit" button on any voter
- **Delete Voter**: Click "Delete" button
- **Pagination**: Navigate through voter list using page numbers

### 5. Voting
- Select a voter from dropdown (only non-voted voters available)
- For each position:
  - **Single selection**: Use dropdown if max_winners = 1
  - **Multiple selection**: Use checkboxes if max_winners > 1
- Click "Submit Vote" to cast vote
- Each voter can only vote once

### 6. Results
- View all voting results
- Shows candidate name, position, and vote count
- Results sorted by position and vote count
- Pagination support for large datasets

### 7. Winners
- View calculated winners
- Grouped by position
- Shows candidate name and vote count
- Click "Calculate Winners" to compute results

### 8. Settings
- **Voting Start Time**: Set when voting begins
- **Voting End Time**: Set when voting ends
- **Voting Status**: Active or Inactive
- Save settings to apply changes

## Database Structure

The system automatically creates the following tables:

### positions
- `position_id` (Primary Key)
- `position_name` (Unique)
- `max_winners` (Default: 1)
- `created_at`

### candidates
- `candidate_id` (Primary Key)
- `full_name`
- `position_id` (Foreign Key → positions)
- `photo` (Optional)
- `created_at`

### users
- `user_id` (Primary Key)
- `full_name`
- `email` (Unique)
- `password`
- `role` (admin/voter)
- `has_voted` (0/1)
- `created_at`

### votes
- `vote_id` (Primary Key)
- `voter_id` (Foreign Key → users)
- `candidate_id` (Foreign Key → candidates)
- `position_id` (Foreign Key → positions)
- `voted_at`

### settings
- `setting_key` (Primary Key)
- `setting_value`

### winners
- `winner_id` (Primary Key)
- `position_id` (Foreign Key → positions)
- `position_name`
- `candidate_id` (Foreign Key → candidates)
- `candidate_name`
- `vote_count`
- `created_at`

## SQL Joins Used

The system uses efficient JOIN queries:

1. **Candidates with Positions**:
   ```sql
   SELECT c.*, p.position_name 
   FROM candidates c 
   JOIN positions p ON c.position_id = p.position_id
   ```

2. **Votes with Candidates and Positions**:
   ```sql
   SELECT c.full_name, p.position_name, COUNT(*) AS votes 
   FROM votes v 
   JOIN candidates c ON v.candidate_id = c.candidate_id
   JOIN positions p ON v.position_id = p.position_id
   ```

3. **Winners with All Related Data**:
   ```sql
   SELECT w.*, c.full_name, p.position_name
   FROM winners w
   JOIN candidates c ON w.candidate_id = c.candidate_id
   JOIN positions p ON w.position_id = p.position_id
   ```

## Workflow

1. **Setup Phase**:
   - Add positions (e.g., President, Vice President)
   - Add candidates for each position
   - Add voters

2. **Configuration Phase**:
   - Set voting start and end times in Settings
   - Set voting status to Active

3. **Voting Phase**:
   - Voters cast their votes
   - System tracks who has voted

4. **Results Phase**:
   - View results anytime
   - Calculate winners after voting ends
   - View winners list

5. **Reset Phase** (Optional):
   - Use "Reset Voting Session" to clear all votes and candidates
   - Positions remain intact

## Important Notes

- **No Login Required**: The system has no authentication. Anyone with access to the URL can manage the system.
- **Database Auto-Creation**: Database and tables are created automatically on first access.
- **Transaction Support**: Voting uses database transactions to ensure data integrity.
- **Foreign Key Constraints**: Tables use foreign keys to maintain referential integrity.
- **Pagination**: Large lists (voters, results) are paginated for better performance.

## Troubleshooting

### Database Connection Error
- Check if MySQL is running
- Verify database credentials in index.php
- Ensure MySQL user has CREATE DATABASE privileges

### Tables Not Created
- Check MySQL error logs
- Verify user has CREATE TABLE privileges
- Check for existing tables with same names

### Voting Not Working
- Check voting status in Settings
- Verify voting period is set correctly
- Ensure voter hasn't already voted

## File Structure

```
votersv2index/
├── index.php    (Single file containing everything)
└── README.md    (This file)
```

## Support

For issues or questions:
1. Check the troubleshooting section
2. Verify all requirements are met
3. Check PHP error logs
4. Check MySQL error logs

## License

Free to use and modify as needed.

