# Cardano Blog Post Signing - Setup Guide

## Overview

Your Umbrella Blog plugin now includes **blockchain-based post signing** capabilities! Sign your blog posts on the Cardano blockchain to provide immutable proof of authorship.

---

## Features

✅ **Server-side signing** - Keys never exposed to frontend
✅ **ADA Handle integration** - Your handle displays as author
✅ **CIP-20 metadata** - Post data stored on-chain
✅ **Flexible wallet sources** - Use Mint Manager, import, or generate
✅ **Beautiful signature display** - Shows handle image + transaction link

---

## Setup Instructions

### Step 1: Run Database Migration

Visit this URL once to add signature fields to your blog posts table:

```
http://your-site.local/wp-content/plugins/umbrella-blog/migrations/add-signature-fields.php?run_migration=signature_fields
```

Or run manually via SQL:

```sql
ALTER TABLE wp_umbrella_blog_posts
ADD COLUMN signature_tx_hash VARCHAR(64) DEFAULT NULL,
ADD COLUMN signature_wallet_address VARCHAR(255) DEFAULT NULL,
ADD COLUMN signature_handle VARCHAR(100) DEFAULT NULL,
ADD COLUMN signature_handle_image TEXT DEFAULT NULL,
ADD COLUMN signed_at DATETIME DEFAULT NULL,
ADD COLUMN signature_metadata TEXT DEFAULT NULL;
```

### Step 2: Configure Signing Wallet

Go to: **Blog > Signing Wallet** in WordPress admin

Choose one of three options:

#### Option A: Use Mint Manager Wallet (Recommended)

- Select "Use Mint Manager Wallet"
- Click "Save Configuration"
- Your existing policy wallet from Cardano Mint Pay will be used
- ✅ Already has your ADA Handle
- ✅ Already encrypted and secure

#### Option B: Import Existing Wallet

- Select "Import Existing Wallet"
- Enter your 24-word mnemonic phrase
- Choose network (Mainnet/Preprod)
- Click "Save Configuration"

#### Option C: Generate New Wallet

- Select "Generate New Wallet"
- Choose network (Mainnet/Preprod)
- Click "Save Configuration"
- **IMPORTANT:** Save the displayed mnemonic - it will only show once!

### Step 3: Verify Configuration

The settings page will show:
- ✅ Wallet Connected status
- Wallet address
- Network (Mainnet/Preprod)
- ADA Handle (if detected)

---

## How to Sign a Blog Post

1. Create or edit a blog post
2. Click "🚀 Publish" to publish it
3. In the sidebar, you'll see the **🔐 Cardano Signature** section
4. Click **"📝 Sign This Post"**
5. Confirm the transaction (~0.17 ADA fee)
6. Wait ~30 seconds for blockchain confirmation
7. Page reloads with signature displayed!

---

## What Gets Signed?

When you sign a post, a transaction is created with **CIP-20 metadata** containing:

```json
{
  "type": "blog_post_signature",
  "version": "1.0",
  "post_id": "123",
  "title": "Your Post Title",
  "slug": "your-post-slug",
  "url": "https://your-site.com/blog/your-post-slug",
  "published_at": "2025-11-03 12:00:00",
  "author_handle": "$yourhandle",
  "word_count": 1234,
  "excerpt": "Post excerpt..."
}
```

This metadata is **permanently stored on the Cardano blockchain** and can be verified by anyone.

---

## Frontend Display

Signed posts automatically display a beautiful signature block at the bottom:

```
┌─────────────────────────────────────────┐
│  🔐 Signed on Cardano                   │
│                                          │
│  [Handle Image]  $yourhandle            │
│                  Signed: Nov 3, 2025     │
│                  TX: ab12cd34ef56...     │
│                                          │
│  This post has been cryptographically    │
│  signed on the Cardano blockchain...     │
└─────────────────────────────────────────┘
```

---

## Technical Details

### Security

- ✅ Signing keys encrypted with AES-256-CBC
- ✅ Keys only decrypted server-side during signing
- ✅ No keys exposed to JavaScript/frontend
- ✅ Uses same encryption as Mint Manager plugin

### Transaction Details

- **Cost:** ~0.17 ADA per signature (~$0.10 USD)
- **Network:** Mainnet or Preprod (matches wallet)
- **Transaction:** 1 ADA sent to self with metadata
- **Explorer:** Automatic links to CardanoScan

### Files Created

```
umbrella-blog/
├── includes/
│   ├── WalletLoader.php         - Smart wallet selection
│   ├── BlogSigner.php           - Transaction signing
│   └── BlockfrostHelper.php     - ADA Handle fetching
├── admin/
│   └── settings.php             - Wallet configuration UI
├── migrations/
│   └── add-signature-fields.php - Database migration
└── CARDANO_SIGNING_README.md    - This file
```

### Modified Files

- `umbrella-blog.php` - Added AJAX endpoint + admin menu
- `admin/editor.php` - Added signature section to sidebar
- `templates/single-post.php` - Added signature display block

---

## Troubleshooting

### "Wallet Not Configured"

- Ensure you've selected a wallet source in Settings
- If using Mint Manager, ensure Cardano Mint Pay plugin is active
- Check that wallet has been created in Mint Manager

### "No ADA Handle Found"

- This is optional - posts will sign anonymously without handle
- Ensure wallet contains an ADA Handle NFT
- Wait a few minutes for Blockfrost to index new handles

### "Transaction Failed"

- Ensure wallet has at least 2 ADA balance
- Check Anvil API is configured (from Cardano.Place setup)
- Verify network matches (mainnet/preprod)

### "Signing Keys Not Found"

- For Mint Manager mode: Create policy wallet in Mint Manager first
- For custom mode: Re-enter your mnemonic in settings
- Check encryption is working (EncryptionHelper class available)

---

## Cost Breakdown

Each signature costs approximately:
- **Transaction fee:** ~0.17 ADA
- **No additional costs** (metadata is free)
- **Network:** Same regardless of mainnet/preprod

---

## Support

For issues or questions:
1. Check WordPress error logs: `/wp-content/debug.log`
2. Enable `WP_DEBUG` in `wp-config.php`
3. Check browser console for JavaScript errors
4. Verify Anvil API and Blockfrost are configured

---

## Future Enhancements

Possible future features:
- ✨ Auto-sign on publish (optional setting)
- ✨ Batch signing multiple posts
- ✨ On-chain verification tool
- ✨ IPFS content hash storage
- ✨ NFT badges for verified authors

---

**Enjoy your blockchain-powered blog!** 🚀
