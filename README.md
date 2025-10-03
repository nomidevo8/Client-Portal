# Client Portal WordPress Plugin

A comprehensive client portal system for WordPress with profile management, document uploads, support tickets, and invoice management.

## Features

- **User Profiles**: Clients can manage their contact information
- **Document Management**: Secure file uploads and downloads
- **Support Tickets**: Create and manage support tickets with threaded messages
- **Invoices**: View and manage invoices with Stripe integration support
- **REST API**: Complete API for frontend integration
- **Custom Post Types**: Documents, Tickets, and Invoices
- **Role Management**: Dedicated "Client" user role
- **Admin Dashboard**: Comprehensive backend management

## Installation

### Plugin Structure

Create the following folder structure in `wp-content/plugins/`:

```
client-portal/
├── client-portal.php (main plugin file)
├── includes/
│   ├── class-cp-post-types.php
│   ├── class-cp-api.php
│   ├── class-cp-user-meta.php
│   ├── class-cp-documents.php
│   ├── class-cp-tickets.php
│   └── class-cp-invoices.php
└── README.md
```

### Steps

1. **Upload Files**: Copy all plugin files to `wp-content/plugins/client-portal/`

2. **Activate Plugin**: Go to WordPress Admin → Plugins → Activate "Client Portal"

3. **Verify Installation**: Check that the following are created:
   - Custom post types: Documents, Tickets, Invoices
   - Client role in Users
   - Admin menu: "Client Portal"

4. **Flush Permalinks**: Go to Settings → Permalinks and click "Save Changes"

## Usage

### Creating Client Users

1. Go to **Users → Add New**
2. Fill in user details
3. Set **Role** to "Client"
4. Fill in Client Portal Information fields
5. Click **Add New User**

### REST API Endpoints

Base URL: `/wp-json/client-portal/v1/`

#### Authentication
All endpoints require WordPress authentication. Use:
- Cookie authentication (for same-domain requests)
- Application passwords
- JWT tokens (with additional plugin)

#### Profile Endpoints

**GET** `/profile` - Get current user's profile
```json
{
  "fullName": "John Doe",
  "email": "john@example.com",
  "phone": "555-0123",
  "company": "Acme Inc",
  "address": "123 Main St"
}
```

**POST** `/profile` - Update profile
```json
{
  "fullName": "John Doe",
  "email": "john@example.com",
  "phone": "555-0123",
  "company": "Acme Inc",
  "address": "123 Main St"
}
```

#### Document Endpoints

**GET** `/documents` - List all documents

**POST** `/documents` - Upload document (multipart/form-data)
- Field: `file` (the file)
- Field: `title` (optional custom title)

**GET** `/documents/{id}` - Get single document

**GET** `/documents/{id}/download` - Get download URL

**DELETE** `/documents/{id}` - Delete document

#### Ticket Endpoints

**GET** `/tickets` - List all tickets

**POST** `/tickets` - Create ticket
```json
{
  "subject": "Help with login",
  "message": "I can't log into my account",
  "priority": "normal"
}
```

**GET** `/tickets/{id}` - Get single ticket

**PUT** `/tickets/{id}` - Update ticket
```json
{
  "status": "closed"
}
```

**POST** `/tickets/{id}/reply` - Add reply to ticket
```json
{
  "message": "Thank you for contacting us..."
}
```

#### Invoice Endpoints

**GET** `/invoices` - List all invoices

**GET** `/invoices/{id}` - Get single invoice

#### Dashboard Endpoint

**GET** `/dashboard` - Get dashboard summary
```json
{
  "docCount": 5,
  "openTickets": 2,
  "recentDocs": ["file1.pdf", "file2.docx"],
  "recentTickets": ["Login issue", "Payment question"]
}
```

## Frontend Integration

### Example: Fetch Profile with Fetch API

```javascript
fetch('/wp-json/client-portal/v1/profile', {
  method: 'GET',
  credentials: 'include', // Important for cookie auth
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce // Get from wp_localize_script
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

### Example: Upload Document

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('title', 'My Document');

fetch('/wp-json/client-portal/v1/documents', {
  method: 'POST',
  credentials: 'include',
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

### Example: Create Ticket

```javascript
fetch('/wp-json/client-portal/v1/tickets', {
  method: 'POST',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: JSON.stringify({
    subject: 'Support Request',
    message: 'I need help with...',
    priority: 'high'
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

## Admin Features

### Managing Documents

1. Go to **Client Portal → Documents**
2. View all uploaded documents
3. Click on a document to see details
4. Download or delete documents

### Managing Tickets

1. Go to **Client Portal → Support Tickets**
2. View all tickets with status and priority
3. Click on a ticket to view messages
4. Use the "Add Reply" box to respond
5. Change status using the taxonomy dropdown

### Managing Invoices

1. Go to **Client Portal → Invoices**
2. Click **Add New** to create an invoice
3. Fill in:
   - Title (invoice description)
   - Amount
   - Due Date
   - Client (select from authors)
   - Status (unpaid, paid, overdue, cancelled)
4. Publish the invoice

## Security Features

- Document uploads are stored outside public directory
- Permission checks on all API endpoints
- Users can only access their own data
- Administrators have full access
- File type restrictions on uploads
- Nonce verification on all requests

## Stripe Integration (Optional)

To enable Stripe payments:

1. Install Stripe PHP SDK
2. Add Stripe API keys to settings
3. Create a Customer Portal session endpoint
4. Update frontend to use Stripe Customer Portal links

Example server-side code:
```php
// In a custom endpoint or function
$stripe = new \Stripe\StripeClient(get_option('stripe_secret_key'));
$session = $stripe->billingPortal->sessions->create([
  'customer' => $customer_id,
  'return_url' => home_url('/client-portal'),
]);
return $session->url;
```

## Customization

### Adding Custom Fields

Edit `includes/class-cp-user-meta.php` to add more profile fields.

### Modifying Post Types

Edit `includes/class-cp-post-types.php` to customize post type settings.

### Extending API

Add new endpoints in `includes/class-cp-api.php`:

```php
register_rest_route(self::$namespace, '/custom-endpoint', array(
    'methods' => 'GET',
    'callback' => array(__CLASS__, 'custom_callback'),
    'permission_callback' => array(__CLASS__, 'check_permission'),
));
```

## Troubleshooting

### 404 Errors on API Endpoints
- Go to Settings → Permalinks and click Save Changes
- Ensure mod_rewrite is enabled

### Permission Denied Errors
- Verify user is logged in
- Check user has "client" or "administrator" role
- Verify nonce is being sent correctly

### File Upload Issues
- Check PHP upload_max_filesize and post_max_size
- Verify wp-content/uploads is writable
- Check allowed MIME types in includes/class-cp-api.php

## Support

For issues and feature requests, please contact your development team.

## Changelog

### Version 1.0.0
- Initial release
- Profile management
- Document uploads
- Support tickets
- Invoice management
- Complete REST API

## License

GPL v2 or later