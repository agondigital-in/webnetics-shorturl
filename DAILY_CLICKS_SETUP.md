# Daily Click Tracking Setup

## Overview
This feature tracks daily clicks for each publisher's campaign URL. When someone clicks on a publisher's unique short URL, the system now records:
- Total clicks (existing feature)
- Daily clicks per publisher per campaign (NEW)

## Installation Steps

### 1. Create the Database Table
Run this command to create the new `publisher_daily_clicks` table:

```bash
php update_daily_clicks_table.php
```

This will create a new table with the following structure:
- `campaign_id` - The campaign being tracked
- `publisher_id` - The publisher whose link was clicked
- `click_date` - The date of the clicks
- `clicks` - Number of clicks on that date
- Unique constraint ensures one record per publisher/campaign/date combination

### 2. How It Works

When a user clicks on a publisher's short URL (e.g., `https://webneticads.com/c/CAMPRCLXWI6M4CFG`):

1. The `redirect.php` file processes the click
2. Updates the total click count (existing)
3. **NEW:** Records/updates the daily click count in `publisher_daily_clicks` table
4. Redirects the user to the target URL

### 3. Viewing Daily Click Statistics

Access the daily click report from:
- Campaign Tracking Stats page: `http://localhost/webnetics-shorturl/super_admin/campaign_tracking_stats.php?id=33`
- Click the "View Daily Clicks" button
- Or directly: `http://localhost/webnetics-shorturl/super_admin/publisher_daily_clicks.php?id=33`

### Features:
- **Date Range Filter**: View clicks for specific date ranges
- **Publisher Summary**: See total clicks and active days per publisher
- **Daily Details**: View click breakdown by date and publisher
- **Average Clicks/Day**: Calculate performance metrics

## Database Schema

```sql
CREATE TABLE `publisher_daily_clicks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `publisher_id` INT NOT NULL,
    `click_date` DATE NOT NULL,
    `clicks` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`publisher_id`) REFERENCES `publishers`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_publisher_campaign_date` (`campaign_id`, `publisher_id`, `click_date`)
)
```

## Files Modified/Created

### Modified:
- `redirect.php` - Added daily click tracking logic

### Created:
- `update_daily_clicks_table.php` - Database setup script
- `super_admin/publisher_daily_clicks.php` - Daily click statistics page
- `DAILY_CLICKS_SETUP.md` - This documentation

## Usage Example

1. Publisher gets unique URL: `https://webneticads.com/c/CAMPRCLXWI6M4CFG`
2. User clicks the URL on Day 1 → 1 click recorded for that date
3. User clicks again on Day 1 → 2 clicks for that date
4. User clicks on Day 2 → New record created with 1 click for Day 2
5. View report to see daily breakdown

## Benefits

- Track publisher performance over time
- Identify peak traffic days
- Calculate daily averages
- Better payment reporting
- Historical data analysis
