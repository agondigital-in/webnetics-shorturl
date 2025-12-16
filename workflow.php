<?php
// workflow.php - Complete workflow documentation for the Ads Platform
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ads Platform Workflow Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        .header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .step {
            display: flex;
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            background-color: #f1f5f9;
        }
        .step-number {
            background: #2563eb;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        .database-table {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .table-name {
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        .field {
            margin-left: 1rem;
            padding: 0.2rem 0;
        }
        .relationship {
            background: #ede9fe;
            border-left: 4px solid #8b5cf6;
            padding: 1rem;
            margin: 1rem 0;
        }
        .highlight {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 5px;
            padding: 0.5rem;
            margin: 0.5rem 0;
        }
        .role-card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: #f9fafb;
        }
        .feature-list li {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="header text-center">
        <div class="container">
            <h1><i class="fas fa-project-diagram me-2"></i>Ads Platform Workflow Documentation</h1>
            <p class="lead">Complete system architecture, user roles, database structure, and workflow processes</p>
        </div>
    </div>

    <div class="container">
        <!-- System Overview -->
        <div class="section">
            <h2><i class="fas fa-info-circle me-2"></i>System Overview</h2>
            <p>The Ads Platform is a comprehensive advertising management system that allows super admins to create and manage advertising campaigns, assign them to advertisers and publishers, and track performance through unique short URLs.</p>
            
            <div class="highlight">
                <h5>Key Features:</h5>
                <ul class="feature-list">
                    <li>Multi-role user system (Super Admin, Admin, Publisher)</li>
                    <li>Campaign creation and management with unique short URLs</li>
                    <li>Publisher-specific tracking links with click counting</li>
                    <li>Advertiser-publisher-campaign relationship management</li>
                    <li>Performance analytics and reporting</li>
                    <li>Role-based dashboards and access control</li>
                </ul>
            </div>
        </div>

        <!-- User Roles -->
        <div class="section">
            <h2><i class="fas fa-users me-2"></i>User Roles</h2>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="role-card">
                        <h4><i class="fas fa-crown text-warning me-2"></i>Super Admin</h4>
                        <ul class="feature-list">
                            <li>Create/edit/delete campaigns</li>
                            <li>Manage advertisers and publishers</li>
                            <li>Assign campaigns to users</li>
                            <li>View all system analytics</li>
                            <li>Manage admin users</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="role-card">
                        <h4><i class="fas fa-user-shield text-primary me-2"></i>Admin</h4>
                        <ul class="feature-list">
                            <li>Manage assigned campaigns</li>
                            <li>View campaign analytics</li>
                            <li>Monitor publisher performance</li>
                            <li>Generate reports</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="role-card">
                        <h4><i class="fas fa-share-alt text-success me-2"></i>Publisher</h4>
                        <ul class="feature-list">
                            <li>View assigned campaigns</li>
                            <li>Access unique tracking links</li>
                            <li>Monitor personal click performance</li>
                            <li>Track earnings</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Structure -->
        <div class="section">
            <h2><i class="fas fa-database me-2"></i>Database Structure</h2>
            
            <div class="database-table">
                <div class="table-name">users</div>
                <div class="field">id (INT, AUTO_INCREMENT, PRIMARY KEY)</div>
                <div class="field">username (VARCHAR(50), UNIQUE, NOT NULL)</div>
                <div class="field">password (VARCHAR(255), NOT NULL)</div>
                <div class="field">role (ENUM: 'super_admin', 'admin', 'publisher', NOT NULL)</div>
                <div class="field">created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)</div>
            </div>
            
            <div class="database-table">
                <div class="table-name">advertisers</div>
                <div class="field">id (INT, AUTO_INCREMENT, PRIMARY KEY)</div>
                <div class="field">name (VARCHAR(100), NOT NULL)</div>
                <div class="field">email (VARCHAR(100), UNIQUE, NOT NULL)</div>
                <div class="field">company (VARCHAR(100))</div>
                <div class="field">phone (VARCHAR(20))</div>
                <div class="field">created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)</div>
            </div>
            
            <div class="database-table">
                <div class="table-name">publishers</div>
                <div class="field">id (INT, AUTO_INCREMENT, PRIMARY KEY)</div>
                <div class="field">name (VARCHAR(100), NOT NULL)</div>
                <div class="field">email (VARCHAR(100), UNIQUE, NOT NULL)</div>
                <div class="field">website (VARCHAR(255))</div>
                <div class="field">phone (VARCHAR(20))</div>
                <div class="field">password (VARCHAR(255))</div>
                <div class="field">created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)</div>
            </div>
            
            <div class="database-table">
                <div class="table-name">campaigns</div>
                <div class="field">id (INT, AUTO_INCREMENT, PRIMARY KEY)</div>
                <div class="field">name (VARCHAR(100), NOT NULL)</div>
                <div class="field">shortcode (VARCHAR(20), UNIQUE, NOT NULL)</div>
                <div class="field">target_url (TEXT, NOT NULL)</div>
                <div class="field">start_date (DATE, NOT NULL)</div>
                <div class="field">end_date (DATE, NOT NULL)</div>
                <div class="field">advertiser_payout (DECIMAL(10, 2), DEFAULT 0.00)</div>
                <div class="field">publisher_payout (DECIMAL(10, 2), DEFAULT 0.00)</div>
                <div class="field">campaign_type (ENUM: 'CPR', 'CPL', 'CPC', 'CPM', 'CPS', 'None', DEFAULT 'None')</div>
                <div class="field">target_leads (INT, DEFAULT 0)</div>
                <div class="field">validated_leads (INT, DEFAULT 0)</div>
                <div class="field">click_count (INT, DEFAULT 0)</div>
                <div class="field">status (ENUM: 'active', 'inactive', DEFAULT 'active')</div>
                <div class="field">payment_status (ENUM: 'pending', 'completed', DEFAULT 'pending')</div>
                <div class="field">created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)</div>
                <div class="field">updated_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)</div>
            </div>
            
            <div class="database-table">
                <div class="table-name">campaign_advertisers (Junction table)</div>
                <div class="field">id (INT, AUTO_INCREMENT, PRIMARY KEY)</div>
                <div class="field">campaign_id (INT, FOREIGN KEY to campaigns.id)</div>
                <div class="field">advertiser_id (INT, FOREIGN KEY to advertisers.id)</div>
                <div class="field">assigned_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)</div>
            </div>
            
            <div class="database-table">
                <div class="table-name">campaign_publishers (Junction table)</div>
                <div class="field">id (INT, AUTO_INCREMENT, PRIMARY KEY)</div>
                <div class="field">campaign_id (INT, FOREIGN KEY to campaigns.id)</div>
                <div class="field">publisher_id (INT, FOREIGN KEY to publishers.id)</div>
                <div class="field">assigned_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)</div>
                <div class="field">clicks (INT, DEFAULT 0)</div>
            </div>
            
            <div class="database-table">
                <div class="table-name">publisher_short_codes</div>
                <div class="field">id (INT, AUTO_INCREMENT, PRIMARY KEY)</div>
                <div class="field">campaign_id (INT, FOREIGN KEY to campaigns.id)</div>
                <div class="field">publisher_id (INT, FOREIGN KEY to publishers.id)</div>
                <div class="field">short_code (VARCHAR(20), UNIQUE, NOT NULL)</div>
                <div class="field">clicks (INT, DEFAULT 0)</div>
                <div class="field">created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)</div>
            </div>
            
            <div class="relationship">
                <h5><i class="fas fa-link me-2"></i>Relationships</h5>
                <ul>
                    <li>One campaign can have multiple advertisers (Many-to-Many via campaign_advertisers)</li>
                    <li>One campaign can have multiple publishers (Many-to-Many via campaign_publishers)</li>
                    <li>Each publisher assigned to a campaign gets a unique short code (One-to-One via publisher_short_codes)</li>
                </ul>
            </div>
        </div>

        <!-- System Workflow -->
        <div class="section">
            <h2><i class="fas fa-sitemap me-2"></i>System Workflow</h2>
            
            <div class="step">
                <div class="step-number">1</div>
                <div>
                    <h5>System Installation</h5>
                    <p>Run <code>install.php</code> to create the database schema and default super admin user. After installation, run <code>update_database.php</code> to create the publisher_short_codes table.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div>
                    <h5>User Authentication</h5>
                    <p>Users access the system through different login portals:</p>
                    <ul>
                        <li><strong>Admin/Super Admin:</strong> <code>login.php</code> - Authenticates against the <code>users</code> table</li>
                        <li><strong>Publisher:</strong> <code>publisher_login.php</code> - Authenticates against the <code>publishers</code> table</li>
                    </ul>
                    <p>Authentication is handled by <code>auth.php</code> which sets session variables for user role and ID.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div>
                    <h5>Campaign Creation (Super Admin)</h5>
                    <p>Super admins create campaigns through <code>super_admin/add_campaign.php</code>:</p>
                    <ul>
                        <li>Enter campaign details (name, target URL, dates, payouts)</li>
                        <li>Select advertisers to associate with the campaign</li>
                        <li>Select publishers to assign to the campaign</li>
                        <li>System automatically generates:
                            <ul>
                                <li>Unique campaign shortcode</li>
                                <li>Unique short codes for each assigned publisher</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div>
                    <h5>Campaign Management</h5>
                    <p>Super admins can edit campaigns through <code>super_admin/edit_campaign.php</code>:</p>
                    <ul>
                        <li>Update target URL (existing tracking links automatically redirect to new URL)</li>
                        <li>Modify advertiser assignments</li>
                        <li>Modify publisher assignments:
                            <ul>
                                <li>Click counts preserved for publishers that remain assigned</li>
                                <li>Short codes removed for publishers being removed</li>
                                <li>New short codes generated for newly added publishers</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">5</div>
                <div>
                    <h5>Publisher Access</h5>
                    <p>Assigned publishers access their dashboard at <code>publisher_dashboard.php</code>:</p>
                    <ul>
                        <li>View list of assigned campaigns</li>
                        <li>See unique tracking links for each campaign</li>
                        <li>Monitor click performance for each link</li>
                        <li>Copy tracking links for use in promotions</li>
                    </ul>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">6</div>
                <div>
                    <h5>Link Tracking & Redirection</h5>
                    <p>All tracking is handled through <code>redirect.php</code>:</p>
                    <ul>
                        <li>Receives short code and optional publisher ID parameters</li>
                        <li>Looks up campaign and publisher details</li>
                        <li>Increments click counts in multiple tables:
                            <ul>
                                <li><code>publisher_short_codes</code> - for publisher-specific links</li>
                                <li><code>campaign_publishers</code> - for standard campaign-publisher links</li>
                                <li><code>campaigns</code> - total campaign clicks</li>
                            </ul>
                        </li>
                        <li>Performs HTTP redirect to campaign's target URL</li>
                        <li>Ensures campaign is active and within date range</li>
                    </ul>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">7</div>
                <div>
                    <h5>Analytics & Reporting</h5>
                    <p>Different dashboards provide analytics based on user role:</p>
                    <ul>
                        <li><strong>Super Admin:</strong> <code>super_admin/dashboard.php</code> - Complete system overview</li>
                        <li><strong>Admin:</strong> <code>admin/dashboard.php</code> - Campaign and user management</li>
                        <li><strong>Publisher:</strong> <code>publisher_dashboard.php</code> - Personal performance tracking</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Click Tracking Workflow -->
        <div class="section">
            <h2><i class="fas fa-mouse-pointer me-2"></i>Click Tracking Workflow</h2>
            <p>This workflow describes how the system handles each click on a short URL:</p>
            
            <div class="step">
                <div class="step-number">1</div>
                <div>
                    <h5>User Clicks Tracking URL</h5>
                    <p><strong>Format:</strong></p>
                    <code>http://webneticads.com/{shortcode}?pub={publisher_id}</code>
                    <p><strong>Inputs:</strong></p>
                    <ul>
                        <li><code>shortcode</code></li>
                        <li><code>publisher_id</code> (optional but needed to track publisher clicks)</li>
                    </ul>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div>
                    <h5>Load redirect.php</h5>
                    <p><code>redirect.php</code> receives the request and performs the following actions.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div>
                    <h5>Validate Campaign Shortcode</h5>
                    <p>System checks:</p>
                    <pre>SELECT * FROM campaigns WHERE shortcode = :shortcode;</pre>
                    <p>If campaign does not exist → stop & show error.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div>
                    <h5>Validate Date Range</h5>
                    <p>Check if campaign is active:</p>
                    <pre>current_date >= start_date
AND
current_date <= end_date
AND
status = 'active'</pre>
                    <p>If expired or inactive → stop redirect.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">5</div>
                <div>
                    <h5>Validate Publisher Assigned to Campaign</h5>
                    <p>Query:</p>
                    <pre>SELECT * FROM campaign_publishers 
WHERE campaign_id = :campaign_id 
AND publisher_id = :publisher_id;</pre>
                    <p>If not assigned → no click should be counted.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">6</div>
                <div>
                    <h5>Increase Click Counts</h5>
                    <p>If valid, increase click count in ALL THREE TABLES:</p>
                    <p><strong>1. publisher_short_codes</strong></p>
                    <pre>UPDATE publisher_short_codes 
SET clicks = clicks + 1 
WHERE short_code = :shortcode 
AND publisher_id = :publisher_id;</pre>
                    <p><strong>2. campaign_publishers</strong></p>
                    <pre>UPDATE campaign_publishers 
SET clicks = clicks + 1 
WHERE campaign_id = :campaign_id 
AND publisher_id = :publisher_id;</pre>
                    <p><strong>3. campaigns (total clicks)</strong></p>
                    <pre>UPDATE campaigns 
SET click_count = click_count + 1 
WHERE id = :campaign_id;</pre>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">7</div>
                <div>
                    <h5>Redirect to Target URL</h5>
                    <p>If everything is valid:</p>
                    <p><strong>302 Redirect</strong> → campaign.target_url</p>
                </div>
            </div>
        </div>

        <!-- Daily Report Workflow (Super Admin) -->
        <div class="section">
            <h2><i class="fas fa-chart-bar me-2"></i>Daily Report Workflow (Super Admin)</h2>
            
            <div class="step">
                <div class="step-number">1</div>
                <div>
                    <h5>Select a Date</h5>
                    <p>Super Admin opens:</p>
                    <p><code>super_admin/daily_report.php</code></p>
                    <p>Chooses:</p>
                    <p><strong>Report Date</strong> → e.g., 2025-02-15</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div>
                    <h5>Fetch All Active/Inactive Campaigns</h5>
                    <p>Query:</p>
                    <pre>SELECT * FROM campaigns;</pre>
                    <p>You will list:</p>
                    <ul>
                        <li>Campaign name</li>
                        <li>Shortcode</li>
                        <li>Total clicks</li>
                        <li>Advertisers</li>
                        <li>Publishers</li>
                    </ul>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div>
                    <h5>Campaign Daily Click Summary</h5>
                    <p>Query:</p>
                    <pre>SELECT campaign_id, publisher_id, clicks
FROM campaign_publishers
WHERE DATE(updated_at) = :report_date;</pre>
                    <p>System generates:</p>
                    <ul>
                        <li>Total clicks of the day</li>
                        <li>Clicks per campaign</li>
                        <li>Clicks per publisher</li>
                        <li>Weekly / Monthly summary (optional)</li>
                    </ul>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div>
                    <h5>Publisher Daily Summary</h5>
                    <p>Query:</p>
                    <pre>SELECT publisher_id, campaign_id, clicks 
FROM publisher_short_codes
WHERE DATE(created_at) <= :report_date;</pre>
                    <p>You output:</p>
                    <ul>
                        <li>Publisher → Campaign → Clicks (all clicks up to selected date)</li>
                        <li>Earnings calculation</li>
                        <li>Click performance</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Technical Implementation Details -->
        <div class="section">
            <h2><i class="fas fa-code me-2"></i>Technical Implementation Details</h2>
            
            <div class="highlight">
                <h5>Short URL Generation</h5>
                <p>All shortened URLs use the fixed domain <strong>webneticads.com</strong> with the format: <code>http://webneticads.com/{shortcode}</code></p>
            </div>
            
            <div class="highlight">
                <h5>Database Connection</h5>
                <p>Uses PDO with environment-based configuration through <code>.env</code> file. Connection managed by <code>db_connection.php</code> with singleton pattern.</p>
            </div>
            
            <div class="highlight">
                <h5>Security Features</h5>
                <ul>
                    <li>Password hashing using PHP's <code>password_hash()</code> function</li>
                    <li>Prepared statements for all database queries</li>
                    <li>Role-based access control with session validation</li>
                    <li>Input validation and sanitization</li>
                </ul>
            </div>
            
            <div class="highlight">
                <h5>Click Tracking Process</h5>
                <ol>
                    <li>User clicks tracking link: <code>http://webneticads.com/{shortcode}?pub={publisher_id}</code></li>
                    <li><code>redirect.php</code> receives request and parses parameters</li>
                    <li>Database lookup to verify campaign and publisher association</li>
                    <li>Increment click counts in all relevant tables</li>
                    <li>Verify campaign is active and within date range</li>
                    <li>HTTP redirect to target URL using 302 status code</li>
                </ol>
            </div>
        </div>

        <!-- File Structure -->
        <div class="section">
            <h2><i class="fas fa-folder-tree me-2"></i>Key Files & Directories</h2>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>Core Files</h5>
                    <ul>
                        <li><code>index.php</code> - Main landing page</li>
                        <li><code>login.php</code> - Admin login portal</li>
                        <li><code>publisher_login.php</code> - Publisher login portal</li>
                        <li><code>auth.php</code> - Authentication handler</li>
                        <li><code>db_connection.php</code> - Database connection utility</li>
                        <li><code>redirect.php</code> - Link redirection and tracking</li>
                        <li><code>install.php</code> - Initial database setup</li>
                        <li><code>update_database.php</code> - Additional table creation</li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h5>Directories</h5>
                    <ul>
                        <li><code>/super_admin/</code> - Super admin dashboard and management files</li>
                        <li><code>/admin/</code> - Admin dashboard and management files</li>
                    </ul>
                    
                    <h5 class="mt-3">Environment Configuration</h5>
                    <ul>
                        <li><code>.env</code> - Database configuration (required)</li>
                        <li><code>.env.example</code> - Configuration template</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2025 Ads Platform. Workflow Documentation.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>