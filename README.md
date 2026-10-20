# Small Victories Skillsbook

> **No longer actively maintained.** The code stays available as is. Forks are welcome; issues and pull requests may not get an answer.

A WordPress plugin that allows visitors to submit and manage their contact information with email confirmation.

It was built for one school's parent council: the interface and default texts are Dutch only (no i18n), the children field label names that school, and entries expire on 15 July. Email texts and messages are configurable in the settings; the rest needs a code change.

## Features

- **Contact Form Submission** - Visitors can submit their name, children info, email, phone, and a message
- **Email Confirmation** - Double opt-in workflow with email verification
- **Edit Functionality** - Users receive a unique link to edit their information later
- **Duplicate Detection** - Handles duplicate email submissions gracefully using localStorage
- **Auto-Expiration** - Entries automatically expire on July 15th each year
- **Auto-Renewal** - `valid_until` date automatically renews when users confirm or edit
- **Admin Management** - Full admin interface to view and manage entries
- **Label System** - Organize entries with custom labels (admin-only)
- **Inline Editing** - Edit entries directly from the admin list view
- **Bulk Renewal Emails** - Send renewal reminders to expired entries
- **Configurable Form Page** - Choose which page displays the form

## Installation

1. Upload the plugin files to `/wp-content/plugins/small-victories-skillsbook/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **SV Skillsbook → Instellingen** to configure the form page
4. Add the shortcode `[skillsbook_form]` to your chosen page

## Usage

### Initial Setup

1. **Configure Form Page**
   - Go to **SV Skillsbook → Instellingen**
   - Select the page where you want the form to appear (e.g., "ikkanda")
   - Save settings
   - Add `[skillsbook_form]` shortcode to that page

### Visitor Flow

1. Visitor fills out the form with their information
2. System sends confirmation email with verification link
3. Visitor clicks link to confirm their email
4. System sends edit link for future updates
5. Visitor can use edit link anytime to update their information

### Admin Features

- **View Entries** - See all submissions at **SV Skillsbook**
- **Inline Editing** - Click "Bewerken" to edit any field directly
- **Manage Labels** - Create and assign labels at **SV Skillsbook → Labels**
- **Send Renewals** - Bulk email expired entries to request renewal
- **Email Settings** - Customize renewal email template

### Label Management

1. Go to **SV Skillsbook → Labels**
2. Create labels like "Verjaardag", "Kerstmarkt", etc.
3. Assign labels to entries in the main list view
4. Labels help organize and categorize volunteers

### Renewal Workflow

When entries expire (after July 15th):

1. Admin clicks "Verstuur vernieuwingsemails" button
2. System sends emails to all expired entries
3. Users click edit link and submit form
4. `valid_until` date automatically extends to next July 15th

## Database Tables

The plugin creates three tables:

- `wp_skillsbook_entries` - Main submission data
- `wp_skillsbook_labels` - Label definitions
- `wp_skillsbook_submission_labels` - Junction table for entry-label relationships

## Automatic Date Extension

The `valid_until` date is automatically recalculated to the next upcoming July 15th when:

- User confirms their email (initial or repeat confirmation)
- User edits their information
- User responds to a renewal email

## Form Fields

- **Naam** (Name) - Required
- **Naam & klas van kinderen die op GO! Flora zitten** (Children) - Required textarea
- **Email** - Required, used for confirmation and edit links
- **Telefoonnummer** (Phone) - Optional
- **Bericht** (Message) - Required textarea

## Shortcode

```
[skillsbook_form]
```

Place this shortcode on any page to display the form.

## Email Templates

### Confirmation Email
Sent when a new submission is made. Contains verification link.

### Edit Email
Sent after email confirmation. Contains unique edit link.

### Renewal Email
Customizable template at **SV Skillsbook → Instellingen**. Supports placeholders:
- `[name]` - Recipient's name
- `[link]` - Edit link

## Security

- All form submissions validated and sanitized
- AJAX requests protected with WordPress nonces
- Email verification required before entry is confirmed
- UUID-based unique links for editing
- Admin capabilities required for management functions

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- MySQL 5.6 or higher

## Version History

### 1.1.0
- Added automatic `valid_until` date renewal on confirmation and edit
- Fixed infinite redirect loop issue
- Updated all email functions to use configured form page

### 1.0.9
- Fixed template integration by removing redirect for edit pages
- Updated email URLs to use configured page

### 1.0.8
- Added better error handling for page redirects

### 1.0.7
- Added settings page for configurable form page
- Implemented redirect-based workflow

### 1.0.6
- Removed frontend label checkboxes (admin-only labels)

### 1.0.5
- Added label management system
- Added inline editing for entries
- Added label autocomplete

### 1.0.4
- Added children field

### 1.0.3
- Fixed database table creation issues
- Fixed confirmation/edit page display

### 1.0.2
- Initial working version with email confirmation

## Support

For issues or questions, please contact Small Victories.

## License

GPL v2 or later
