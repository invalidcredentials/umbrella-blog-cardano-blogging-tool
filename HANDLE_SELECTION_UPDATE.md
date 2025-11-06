# 🎨 Handle Selection Feature - Update Guide

## What's New?

Your blog signing just got even better! Now you can **select which ADA Handle** to use for signing posts from all the handles in your wallet!

---

## ✨ New Features

### 1. **Multiple Handle Detection**
- Automatically fetches **ALL ADA Handles** from your wallet
- Shows count of handles detected
- Works with both Mainnet and Preprod

### 2. **Handle Selection Dropdown**
- Choose which handle to display as author
- Live preview with handle image
- Option to sign anonymously (no handle)

### 3. **Beautiful Handle Badges**
- **Admin Editor:** Shows selected handle before signing + full badge after signed
- **Frontend:** Displays handle image + name on published posts
- **Settings Page:** Live preview when selecting handles

---

## 🎯 How to Use

### Step 1: Configure Signing Wallet
1. Go to **Blog > Signing Wallet**
2. Select your wallet source (Use Mint Manager recommended)
3. Click "Save Configuration"

### Step 2: Select Your Handle
1. After wallet is connected, a new section appears: **"Select Handle for Signing"**
2. Dropdown shows all handles in your wallet:
   ```
   Anonymous (no handle)
   $yourhandle1
   $yourhandle2
   $yourhandle3
   ```
3. Select the handle you want to use
4. **Live preview appears** showing the handle + image
5. Click "Save Configuration"

### Step 3: Sign Posts
1. Edit or create a published blog post
2. In the sidebar, you'll see a preview: **"Will sign as $yourhandle"**
3. Click "📝 Sign This Post"
4. Your selected handle will be included in the signature!

---

## 🔍 How It Works

### Blockfrost Integration

The system fetches ALL handles from your wallet using:

```php
UmbrellaBlog_BlockfrostHelper::getAllHandlesFromAddress($address, $network)
```

This scans the wallet for NFTs matching the ADA Handle policy ID:
- **Mainnet:** `f0ff48bbb7bbe9d59a40f1ce90e9e9d0ff5002ec48f232b49ca0fb9a`
- **Preprod:** `f0ff48bbb7bbe9d59a40f1ce90e9e9d0ff5002ec48f232b49ca0fb9a`

For each handle found:
- Extracts handle name from asset hex
- Fetches metadata (including image URL)
- Resolves IPFS URLs to HTTP gateways
- Returns array of all handles

### Storage

Selected handle is stored in WordPress options:
```php
cardano_blog_signer_selected_handle = "$yourhandle"
```

### Signing Process

When signing a post:
1. Loads selected handle from settings
2. Fetches all handles from wallet
3. Finds matching handle by name
4. Uses that handle's image + name in signature
5. Stores in database with post

---

## 💎 UI Enhancements

### Admin Settings Page

**Before wallet connection:**
```
⚠️ Wallet Not Configured
```

**After wallet connection (multiple handles):**
```
✅ Wallet Connected
Source: mint_manager
Address: addr1q...
Network: Mainnet
ADA Handles: 3 handles detected

[Select Handle for Signing]
Dropdown: Anonymous (no handle)
          $handle1 ▼
          $handle2
          $handle3

[Live Preview Box]
┌─────────────────────────────┐
│ [Handle Image] $handle1     │
│ This handle will appear on  │
│ signed blog posts           │
└─────────────────────────────┘
```

### Admin Editor Sidebar (Before Signing)

```
🔐 Cardano Signature
┌─────────────────────────────┐
│ 👤 $yourhandle              │
│    Will sign as this handle │
├─────────────────────────────┤
│ Sign this post on the       │
│ Cardano blockchain...       │
│                             │
│ [📝 Sign This Post]         │
│                             │
│ Cost: ~0.17 ADA (~$0.10)    │
└─────────────────────────────┘
```

### Admin Editor Sidebar (After Signing)

```
🔐 Cardano Signature
┌─────────────────────────────┐
│ ✅ Signed on Cardano        │
│                             │
│ [Handle Image] $yourhandle  │
│                Author Handle│
│                             │
│ Signed: Nov 3, 2025 3:45 PM│
│ TX: ab12cd34ef56...         │
└─────────────────────────────┘
```

### Frontend Display

```
┌──────────────────────────────────────────┐
│ 🔐 Signed on Cardano                     │
│                                          │
│ [Handle Image]  $yourhandle              │
│  (80x80)        Signed: Nov 3, 2025      │
│                 TX: ab12cd34... (link)   │
│                                          │
│ This post has been cryptographically     │
│ signed on the Cardano blockchain...      │
└──────────────────────────────────────────┘
```

---

## 🛠️ Technical Details

### Files Modified

1. **`includes/BlockfrostHelper.php`**
   - Added `getAllHandlesFromAddress()` method
   - Returns array of ALL handles with images

2. **`admin/settings.php`**
   - Added handle selection dropdown
   - Live preview with JavaScript
   - Saves selected handle to options

3. **`includes/BlogSigner.php`**
   - Uses selected handle instead of first found
   - Fetches all handles and finds match
   - Falls back to first handle if none selected

4. **`admin/editor.php`**
   - Shows "Will sign as" preview before signing
   - Displays handle badge after signed

5. **`templates/single-post.php`**
   - Already had handle display (no changes needed!)

### Database

No new database columns needed! Uses existing:
- `signature_handle` - Handle name (e.g., "$yourhandle")
- `signature_handle_image` - IPFS/HTTP image URL

### Options Added

- `cardano_blog_signer_selected_handle` - Selected handle name

---

## 🎨 Design Features

### Color Scheme
- **Primary:** `#00E6FF` (Cyan)
- **Background:** `rgba(0, 230, 255, 0.05)` (Light cyan tint)
- **Border:** `rgba(0, 230, 255, 0.2)` (Semi-transparent cyan)

### Handle Images
- **Settings Preview:** 60x60px, circular, cyan border
- **Admin Badge:** 40x40px, circular, cyan border
- **Frontend Badge:** 80x80px, circular, cyan border with glow

### Responsive Design
- Mobile: Handle badge stacks vertically
- Desktop: Handle badge displays horizontally

---

## 🚀 Performance

### Caching
- Blockfrost API calls only when needed
- Settings page caches handle list during page load
- Signing process fetches fresh handle data

### Rate Limiting
- Blockfrost has built-in rate limits
- Future enhancement: Add transient caching for handles

---

## 🎉 Result

Your blog posts now have **verifiable blockchain authorship** with beautiful handle badges showing exactly WHO signed each post!

**Example Workflow:**
1. You own 3 handles: `$crypto_writer`, `$tech_guru`, `$anon_blogger`
2. Select `$crypto_writer` in settings
3. Write a crypto article → Sign with `$crypto_writer`
4. Readers see your handle + image as the verified author!

---

**This feature makes your blog the most Web3-native content platform!** 🔥
