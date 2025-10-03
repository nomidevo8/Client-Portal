# Client Portal - Quick Start Guide

## 📁 File Structure

Create this folder structure in your WordPress installation:

```
wp-content/plugins/client-portal/
├── client-portal.php
├── README.md
├── QUICK-START.md
└── includes/
    ├── class-cp-post-types.php
    ├── class-cp-api.php
    ├── class-cp-user-meta.php
    ├── class-cp-documents.php
    ├── class-cp-tickets.php
    ├── class-cp-invoices.php
    └── class-cp-shortcode.php
```

## 🚀 Installation Steps

### 1. Upload Plugin Files

1. Create folder: `wp-content/plugins/client-portal/`
2. Create subfolder: `wp-content/plugins/client-portal/includes/`
3. Upload all files to their respective locations

### 2. Activate Plugin

1. Go to WordPress Admin → **Plugins**
2. Find **Client Portal** in the list
3. Click **Activate**

### 3. Flush Permalinks

1. Go to **Settings → Permalinks**
2. Click **Save Changes** (don't change anything, just save)

### 4. Verify Installation

Check that these are created:
- ✅ Admin menu: "Client Portal"
- ✅ Post types: Documents, Support Tickets, Invoices
- ✅ User role: Client

## 👥 Create Client Users

### Option A: Create New Client User

1. Go to **Users → Add New**
2. Fill in:
   - Username: `johndoe`
   - Email: `john@example.com`
   - Password: (generate or set)
   - **Role: Client** ⚠️ Important!
3. Scroll down to **Client Portal Information**
4. Fill in: Full Name, Phone, Company, Address
5. Click **Add New User**

### Option B: Convert Existing User

1. Go to **Users → All Users**
2. Click on a user
3. Change **Role** to **Client**
4. Fill in **Client Portal Information**
5. Click **Update User**

## 📄 Create Portal Page

### 1. Create New Page

1. Go to **Pages → Add New**
2. Title: `Client Portal` (or any name)
3. In the content editor, add: `[client_portal]`
4. Click **Publish**

### 2. Set Permalink (Optional)

1. Click **Edit** next to the permalink
2. Set to: `client-portal`
3. Click **OK**

### 3. Test the Portal

1. Copy the page URL
2. Open in new private/incognito window
3. You should see the login form
4. Log in with a client user account

## 📊 Add Sample Data (Testing)

### Create a Document

1. Log in as a client user on the frontend
2. Go to **Documents** tab
3. Click **Choose File** and select a PDF
4. Add optional title
5. Click **Upload**

### Create a Ticket

1. Go to **Messages** tab
2. Fill in:
   - Subject: "Test Support Request"
   - Priority: Normal
   - Message: "This is a test ticket"
3. Click **Create Ticket**

### Create an Invoice (Admin)

1. Log in to WordPress Admin
2. Go to **Client Portal → Invoices**
3. Click **Add New**
4. Fill in:
   - Title: "Monthly Service Fee"
   - Content: "Payment for services rendered"
   - Author: Select the client user
5. Scroll down to **Invoice Details**:
   - Amount: 99.99
   - Due Date: (select date)
6. On the right sidebar, set:
   - **Invoice Status**: unpaid
7. Click **Publish**

## 🔧 API Testing (For Developers)

### Test with Browser Console

```javascript
// Get profile
fetch('/wp-json/client-portal/v1/profile', {
  credentials: 'include',
  headers: {
    'X-WP-Nonce': document.querySelector('meta[name="wp-nonce"]')?.content || ''
  }
})
.then(r => r.json())
.then(console.log);

// Get documents
fetch('/wp-json/client-portal/v1/documents', {
  credentials: 'include',
  headers: {
    'X-WP-Nonce': document.querySelector('meta[name="wp-nonce"]')?.content || ''
  }
})
.then(r => r.json())
.then(console.log);
```

### Test with cURL

```bash
# Login first to get cookie
curl -X POST https://yoursite.com/wp-login.php \
  -d "log=username&pwd=password" \
  -c cookies.txt

# Then make API requests
curl https://yoursite.com/wp-json/client-portal/v1/profile \
  -b cookies.txt \
  -H "X-WP-Nonce: YOUR_NONCE"
```

## 🎨 Customization

### Change Colors

Edit the CSS variables in `class-cp-shortcode.php`:

```css
:root {
    --max: 1200px;
    --card: #ffffff;
    --radius: 12px;
    --accent: #2563eb;  /* Change this for primary color */
    --muted: #6b7280;   /* Change this for text color */
}
```

### Add Custom Fields to Profile

Edit `includes/class-cp-user-meta.php` and add fields in the `add_custom_user_profile_fields` method.

### Modify API Endpoints

Edit `includes/class-cp-api.php` to add new endpoints or modify existing ones.

## 🔐 Security Checklist

- ✅ Plugin checks user permissions on all API calls
- ✅ Documents are stored securely
- ✅ Users can only access their own data
- ✅ File type restrictions on uploads
- ✅ Nonce verification on requests
- ✅ SQL injection protection via WordPress functions

## 📱 Mobile Responsive

The portal is mobile-responsive by default:
- Desktop: 2-column layout
- Tablet/Mobile: Stacked single-column layout

## 🐛 Common Issues

### "404 Not Found" on API endpoints

**Solution:** Go to Settings → Permalinks and click Save Changes

### "You do not have permission"

**Solutions:**
1. Verify user has "Client" or "Administrator" role
2. Check user is logged in
3. Clear browser cache/cookies

### Documents won't upload

**Solutions:**
1. Check PHP `upload_max_filesize` (increase to 20M or more)
2. Check PHP `post_max_size` (increase to 25M or more)
3. Verify `wp-content/uploads` is writable (chmod 755 or 775)
4. Check file type is allowed in `class-cp-api.php`

### Shortcode displays raw text `[client_portal]`

**Solutions:**
1. Verify plugin is activated
2. Check file `includes/class-cp-shortcode.php` exists
3. Look for PHP errors in WordPress debug log

## 📚 Next Steps

1. **Customize branding**: Update colors and logo
2. **Configure Stripe**: Add Stripe integration for payments
3. **Email notifications**: Add email alerts for new tickets
4. **Custom fields**: Add industry-specific profile fields
5. **Reports**: Create admin reports dashboard

## 💡 Tips

- **Test with multiple users**: Create 2-3 test client accounts
- **Check mobile**: Test on phone/tablet before going live
- **Backup first**: Always backup before customizing
- **Use child theme**: For CSS customizations
- **Document changes**: Keep notes of customizations

## 🆘 Support

For help with:
- **WordPress issues**: Check WordPress.org forums
- **Plugin bugs**: Review error logs at `wp-content/debug.log`
- **Custom development**: Hire a WordPress developer

## 📝 Quick Reference

| Feature | Location |
|---------|----------|
| Add Client | Users → Add New |
| View Documents | Client Portal → Documents |
| View Tickets | Client Portal → Support Tickets |
| View Invoices | Client Portal → Invoices |
| Settings | Client Portal → Settings |
| User Profile Fields | Users → Edit User |
| API Docs | README.md |

---

**Ready to go!** 🎉 Your client portal is now set up and ready for use.

## ⚙️ Airtable Integration

If you'd like new client users to be sent to an Airtable base, the plugin includes optional support.

Configuration options (choose one):

- Define constants in `wp-config.php` (recommended for security):

  - `define('CP_AIRTABLE_API_KEY', 'pat_...');`
  - `define('CP_AIRTABLE_BASE', 'appXXXXXXXXXXXX');`
  - `define('CP_AIRTABLE_TABLE', 'tblXXXXXXXXXXXX');`

- Or set WordPress options (not provided in UI by default):

  - `cp_airtable_api_key`
  - `cp_airtable_base`
  - `cp_airtable_table`

Behavior:

- When a user is created with the `client` role, the plugin will call the Airtable API and create a record using a default field mapping (Username, First Name, Last Name, E-mail Address, Status, Notes).
- Developers can customize the fields by filtering `cp_airtable_user_fields`.
- After the API call the `cp_airtable_after_send` action fires with the user ID and the result (true or WP_Error).

Security notes:

- Don't commit API keys to version control. Prefer `wp-config.php` constants or a secure secrets manager.
- The plugin performs the request server-side using WordPress HTTP API (`wp_remote_post`). Ensure outgoing HTTPS connections are allowed from your host.
