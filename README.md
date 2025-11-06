# Umbrella Blog 🌂

**Sign and authenticate your web content with cryptographic proof.**

A WordPress blogging plugin that provides what every writer needs: a simple way to prove "these words are mine." Using native Cardano blockchain integration, you can one-click sign your blog posts with immutable, cryptographic proof of authorship - no wallet popups, no complicated ceremonies, just write and sign.

![Version](https://img.shields.io/badge/version-1.1.2-00E6FF)
![WordPress](https://img.shields.io/badge/WordPress-5.0+-00E6FF)
![Cardano](https://img.shields.io/badge/Cardano-Enabled-00E6FF)
![License](https://img.shields.io/badge/license-MIT-00E6FF)

---

## 🎯 The Problem This Solves

Ever write something online and wish you had a permanent, tamper-proof receipt proving you wrote it at that exact moment?

Traditional publishing has no built-in proof of authorship. Anyone can copy your words, claim them as theirs, or dispute when you actually wrote something. Digital signatures exist, but they're complicated - requiring browser extensions, wallet connections, and technical knowledge most writers don't have.

**Umbrella Blog changes that.**

## ✨ What Makes This Special

This isn't just another WordPress blog plugin. Umbrella Blog combines:

- ⛓️ **True Native Cardano Integration** - Server-side signing using PHP, not browser extensions
- 🎯 **One-Click Signing** - No wallet popups, no complex words, just click "Sign Post"
- 🔐 **Server-Side Wallet** - Fund once with a few dollars, sign hundreds of posts
- 🎭 **ADA Handle Integration** - Your $handle becomes your author identity
- 📝 **Distraction-Free Writing** - Markdown + Rich Text editors, no Gutenberg bloat
- 🎨 **Cyberpunk UI** - Beautiful dark glassmorphic theme with neon accents
- 🚀 **Complete SEO Suite** - Meta tags, Open Graph, Twitter Cards, JSON-LD
- ⚡ **Lightning Fast** - Custom database tables, no WordPress CPT overhead

---

## 🎯 Key Features

### 📝 Writing & Editing

- **Dual Editor Modes** - Toggle between Markdown (with live preview) and Rich Text
- **Draft/Publish Workflow** - Save drafts or publish immediately
- **Word Count & Reading Time** - Auto-calculated as you write
- **Categories & Tags** - Full taxonomy system with auto-complete
- **Image Upload** - Drag-and-drop image management for posts
- **Clean Interface** - Focus on writing, not fighting with the editor

### ⛓️ Cardano Blockchain Integration

**How it works:**

Unlike traditional EVM chains, Cardano uses a UTXO model - just like shopping at a grocery store with cash. You send a transaction, receive change, and get a receipt. Transactions on Cardano are cheap (around 0.2 ADA) and can contain rich metadata.

The plugin creates a server-side Cardano wallet that you fund with a few dollars. When you click "Sign Post with Cardano" - BAM, done. The server builds the transaction, signs it with your encrypted wallet, and submits it to the blockchain. No browser extension required.

**Features:**

- **Cryptographic Signing** - Sign published posts on Cardano blockchain
- **CIP-20 Metadata** - Store post metadata permanently on-chain
- **ADA Handle Support** - Use your $handle as author identity
- **Wallet Management** - Generate or import HD wallets (BIP39 24-word mnemonic)
- **Encrypted Storage** - Wallet keys encrypted with AES-256
- **Mainnet & Preprod** - Supports both networks with network-specific API keys
- **Transaction Verification** - Direct links to CardanoScan for signature verification
- **Immutable Proof** - Cryptographic proof of authorship and publication date

**Built on:**
- [PHP-Cardano](https://github.com/CardanoPress/php-cardano) - Native PHP wallet generation and signing
- [Anvil API](https://ada-anvil.io) - Transaction building and submission
- [Blockfrost](https://blockfrost.io) - Blockchain queries and ADA Handle lookup

### 🔍 SEO & Metadata

- **Complete Meta Tags** - Title, description, keywords, robots
- **Open Graph Protocol** - Beautiful previews on Facebook, LinkedIn, Discord
- **Twitter Cards** - Optimized Twitter/X sharing
- **JSON-LD Structured Data** - Google rich snippets for better rankings
- **XML Sitemap** - Auto-generated at `/blog-sitemap.xml`
- **Canonical URLs** - Prevent duplicate content issues
- **Focus Keywords** - Track primary SEO keywords per post
- **Character Counters** - Live feedback for optimal meta tag lengths

### 🎨 Design & Theming

- **Cyberpunk Theme** - Dark glassmorphic design with neon cyan/magenta accents
- **Color Presets** - 6 built-in themes (Cyberpunk, Matrix, Sunset, Ocean, Purple, Fire)
- **Custom Colors** - Live preview with primary/secondary gradient customization
- **Responsive Design** - Beautiful on desktop, tablet, and mobile
- **Clean URLs** - `/blog/` and `/blog/your-post-title`
- **Fast Loading** - Optimized CSS with backdrop-filter blur effects

---

## 🚀 Installation

### Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- For Cardano features:
  - Blockfrost API key (mainnet/preprod)
  - Ada Anvil API key (mainnet/preprod)

### Step 1: Upload Plugin

1. Download the plugin files
2. Upload to `/wp-content/plugins/umbrella-blog/`
3. Or use **WordPress Admin → Plugins → Add New → Upload**

### Step 2: Activate

1. Go to **WordPress Admin → Plugins**
2. Find "Umbrella Blog"
3. Click **Activate**

### Step 3: Database Setup

After activation, the plugin automatically creates database tables. If you experience issues:

1. Go to **Umbrella Blog → Database Setup**
2. Click **"Run Full Setup"**

### Step 4: Configure Settings

1. Go to **Umbrella Blog → Settings**
2. Set your **default network** (Mainnet or Preprod)
3. Add your **API keys**:
   - Blockfrost Preprod/Mainnet keys
   - Ada Anvil Preprod/Mainnet keys
4. Optionally customize **color theme**

### Step 5: Flush Permalinks

1. Go to **Settings → Permalinks**
2. Click **Save Changes**

This registers the `/blog/` URLs.

---

## 💼 Cardano Wallet Setup

### Generate New Wallet

1. Go to **Umbrella Blog → Wallet Manager**
2. Click **"Generate New Wallet"** tab
3. Enter wallet name
4. Click **"Generate Wallet"**
5. **IMPORTANT:** Copy and securely store your 24-word recovery phrase
6. The wallet is encrypted and stored in your database

### Import Existing Wallet

1. Go to **Umbrella Blog → Wallet Manager**
2. Click **"Import from Mnemonic"** tab
3. Enter wallet name
4. Paste your 24-word recovery phrase
5. Click **"Import Wallet"**

### Wallet Security

- Private keys are encrypted with AES-256
- Mnemonics are only displayed once after generation
- Never share your recovery phrase
- Wallets support both Mainnet and Preprod networks

### Multiple Wallets

- Create separate wallets for Mainnet and Preprod
- Archive unused wallets
- Only one wallet can be active per network

---

## ✍️ Writing Your First Post

### Create Post

1. Go to **Umbrella Blog → Add New Post**
2. Enter **Title** and **Excerpt**
3. Choose editor mode:
   - **Markdown** - Clean syntax, live preview, great for code
   - **Rich Text** - Familiar HTML formatting
4. Write your content

### Add Metadata (Sidebar)

- **Categories & Tags** - Organize your content
- **SEO Basics** - Meta title, description, slug, focus keyword
- **Social Sharing** - Custom OG/Twitter images (optional)
- **Advanced** - Canonical URL, robots directive

### Save or Publish

- **Save as Draft** - Store privately, continue later
- **Publish** - Make live immediately

---

## ⛓️ Signing Posts on Cardano

### Prerequisites

1. Active wallet for your selected network
2. Sufficient ADA for transaction fees (~0.2 ADA)
3. (Optional) ADA Handle in wallet for author identity

### Sign a Post

1. **Publish** your post first
2. Go to **Umbrella Blog → Blog Posts**
3. Find your published post
4. Click **"Sign on Cardano"**
5. Wait for transaction to be built and submitted
6. Transaction hash will appear when complete

### What Gets Signed

The blockchain transaction includes CIP-20 metadata (label 674):

```json
{
  "674": {
    "msg": {
      "type": "blog_post_signature",
      "version": "1.0",
      "post_id": "123",
      "title": "Your Post Title",
      "slug": "your-post-slug",
      "url": "https://yoursite.com/blog/your-post-slug",
      "published_at": "2025-11-04 12:00:00",
      "author_handle": "$yourhandle",
      "word_count": 1234,
      "excerpt": "Your post excerpt...",
      "signed_with": "Umbrella Blog v1.0"
    }
  }
}
```

### Viewing Signatures

Published posts with signatures display:

- **Author ADA Handle** - With profile image (if available)
- **Signed Date** - When the signature was created
- **Transaction Hash** - Link to CardanoScan for verification
- **On-Chain Metadata** - Expandable view of blockchain data
- **Immutability Notice** - Explanation of blockchain permanence

---

## 🎨 Customization

### Color Themes

**Built-in Presets:**

- **Cyberpunk** (Default) - Cyan/Magenta neon
- **Matrix Green** - Classic terminal green
- **Sunset Vibes** - Warm red/yellow gradients
- **Deep Ocean** - Cool blue tones
- **Purple Dream** - Purple/violet gradients
- **Fire & Ice** - Orange/yellow heat

### Custom Colors

1. Go to **Umbrella Blog → Settings**
2. Scroll to **"Appearance"**
3. Choose a preset or enter custom hex colors
4. See live preview of gradient
5. Save changes

### Network Settings

- **Default Network** - Choose Mainnet or Preprod
- **API Keys** - Different keys for each network
- **Dev Mode** - Use mainnet handles on preprod (for testing)

---

## 📊 Database Structure

The plugin creates 5 custom tables:

### `wp_umbrella_blog_posts`

Main posts table with fields:
- Basic: `id`, `title`, `slug`, `content`, `excerpt`, `status`
- Editor: `editor_mode` (markdown/richtext)
- SEO: `meta_title`, `meta_description`, `focus_keyword`, `canonical_url`, `meta_robots`
- Social: `og_title`, `og_description`, `og_image`, `twitter_*` fields
- Stats: `word_count`, `reading_time`
- Cardano: `signature_tx_hash`, `signature_metadata`, `signature_wallet_address`, `signature_handle`, `signed_at`
- Timestamps: `created_at`, `updated_at`

### `wp_umbrella_blog_wallets`

Cardano wallet storage:
- `id`, `name`, `network`, `status` (active/archived)
- `payment_address`, `payment_keyhash`
- `mnemonic_encrypted`, `skey_encrypted` (AES-256)
- `created_at`

### `wp_umbrella_blog_categories`

Category definitions:
- `id`, `name`, `slug`, `description`

### `wp_umbrella_blog_tags`

Tag definitions:
- `id`, `name`, `slug`

### Relationship Tables

- `wp_umbrella_blog_post_categories` - Many-to-many post ↔ category
- `wp_umbrella_blog_post_tags` - Many-to-many post ↔ tag

---

## 🛠️ Technical Details

### Cardano Integration

**Wallet Generation:**
- Uses `CardanoWalletPHP` for pure PHP HD wallet derivation
- Implements BIP39 mnemonic generation (24 words)
- Derives payment keys using CIP-1852 derivation paths
- Supports both enterprise (payment-only) and base addresses

**Transaction Signing:**
- Uses Ed25519 pure PHP implementation for signing
- Builds transactions via Ada Anvil API
- Signs locally with encrypted wallet keys
- Submits to Cardano network via Anvil

**Blockchain Queries:**
- Blockfrost API for address balance, UTXOs, ADA Handles
- Ada Anvil for transaction building and submission
- Network-aware API endpoint switching

### Security Features

- **AES-256 Encryption** for wallet private keys
- **WordPress nonces** for CSRF protection
- **Capability checks** (`manage_options`) for admin actions
- **SQL injection prevention** via `$wpdb->prepare()`
- **XSS protection** via `esc_html()`, `esc_url()`, `esc_attr()`
- **Input sanitization** for all form data

### Performance Optimizations

- **No WordPress CPTs** - Custom tables for faster queries
- **Direct database access** - No post meta bloat
- **Minimal dependencies** - Only marked.js for Markdown preview
- **CSS custom properties** - Efficient theming system
- **Debounced auto-save** - Reduces database writes

---

## 🌐 API Integrations

### Blockfrost API

Used for:
- Fetching wallet balances and UTXOs
- Retrieving ADA Handles from addresses
- Verifying transaction confirmations

**Required API Keys:**
- Preprod: `https://cardano-preprod.blockfrost.io/api/v0`
- Mainnet: `https://cardano-mainnet.blockfrost.io/api/v0`

Get your keys at: https://blockfrost.io

### Ada Anvil API

Used for:
- Building Cardano transactions
- Submitting signed transactions
- CIP-20 metadata attachment

**API Endpoints:**
- Preprod: `https://preprod.api.ada-anvil.app/v2/services`
- Mainnet: `https://prod.api.ada-anvil.app/v2/services`

Get your keys at: https://ada-anvil.io

---

## 📝 Markdown Cheatsheet

```markdown
# Heading 1
## Heading 2
### Heading 3

**bold text**
*italic text*
~~strikethrough~~

[Link text](https://example.com)

![Image alt text](image-url.jpg)

- Bullet point
- Another point

1. Numbered list
2. Another item

`inline code`

\`\`\`javascript
// Code block with syntax highlighting
const greeting = "Hello World";
\`\`\`

> Blockquote text

---

Horizontal rule
```

---

## 🔧 Development

### File Structure

```
umbrella-blog/
├── umbrella-blog.php          # Main plugin file
├── admin/
│   ├── css/
│   │   └── umbrella-admin.css # Cyberpunk theme styles
│   ├── editor.php             # Post editor page
│   ├── post-list.php          # Posts listing page
│   ├── wallet-manager.php     # Wallet management UI
│   ├── plugin-settings.php    # Settings page
│   └── image-upload-section.php
├── includes/
│   ├── WalletController.php   # Wallet CRUD operations
│   ├── WalletModel.php        # Wallet database queries
│   ├── WalletLoader.php       # Wallet decryption helper
│   ├── BlogSigner.php         # Cardano signing logic
│   ├── BlockfrostHelper.php   # Blockfrost API wrapper
│   ├── SEOMetaTags.php        # Meta tag generation
│   └── vendor/
│       ├── CardanoWalletPHP.php    # HD wallet derivation
│       ├── CardanoCLI.php          # Transaction signing
│       ├── AnvilHelper.php         # Anvil API wrapper
│       └── UmbrellaBlog_EncryptionHelper.php
├── templates/
│   ├── blog-list.php          # Blog listing page
│   └── single-post.php        # Single post view
└── README.md
```

### Adding Features

The plugin is modular and easy to extend:

- **New admin pages**: Add to `admin/` directory
- **New database tables**: Add to `activate()` in main plugin file
- **New API integrations**: Create helper class in `includes/`
- **New editor features**: Extend `admin/editor.php`

---

## 🤝 Contributing

Contributions are welcome! Whether you're fixing bugs, adding features, or improving documentation.

**Areas for improvement:**
- Additional color themes
- More editor features (table support, footnotes, etc.)
- Enhanced blockchain features (multi-sig workflows, NFT minting)
- Performance optimizations
- Better mobile experience
- Internationalization (i18n)

**Getting Started:**

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Test thoroughly on WordPress 5.0+
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

---

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

Feel free to use, modify, and distribute this plugin.

---

## 🙏 Credits

**Built with:**
- [Blockfrost](https://blockfrost.io) - Cardano API
- [Ada Anvil](https://ada-anvil.io) - Transaction building
- [marked.js](https://marked.js.org) - Markdown parsing
- [CardanoWalletPHP](https://github.com/adosia/CardanoWalletPHP) - HD wallet derivation
- Ed25519 Pure PHP Implementation

**Inspired by:**
- Cardano's eUTXO model
- CIP-20 (Transaction Message/Comment Metadata)
- CIP-1852 (HD Wallets for Cardano)
- Cyberpunk aesthetics and glassmorphism design trends

---

## 💬 Support & Community

For issues, questions, or feature requests:
- **GitHub Issues**: [Report bugs or request features](../../issues)
- **Documentation**: Check the `/Documentation/` folder for detailed guides
- **WordPress Debugging**: Enable `WP_DEBUG` in `wp-config.php` for detailed error logs

**Get Help:**
- Read `CARDANO_SIGNING_README.md` for blockchain setup assistance
- Check `HANDLE_SELECTION_UPDATE.md` for ADA Handle features
- Review WordPress error logs at `/wp-content/debug.log`

---

## 🚀 Roadmap

**Planned features:**
- Multi-signature post approval workflows
- NFT minting for blog posts (turn posts into NFTs)
- Gated content with token/NFT verification
- RSS feed with blockchain verification hashes
- Archive posts to IPFS for permanent storage
- Reader comments via Cardano transactions
- CIP-8 message signing for portable identity
- Integration with other Cardano DApps

**Want to sponsor a feature?** Open an issue and let's talk!

---

## 🌟 Why This Matters

In a world of AI-generated content, deepfakes, and content theft, having cryptographic proof of authorship matters more than ever.

Every time you sign a blog post with Umbrella Blog, you're creating a permanent, tamper-proof record on the Cardano blockchain that says:
- "I wrote these words"
- "I published them at this exact time"
- "This is the original version"

This isn't just for tech people. This is for writers, journalists, researchers, artists - anyone who creates original content and wants to protect their work.

---

**Built with 💙 for writers who value authenticity and immutability.**

**Powered by Cardano ⛓️**

---

## 📚 Additional Resources

- **Cardano Official**: https://cardano.org
- **ADA Handles**: https://adahandle.com
- **Anvil API**: https://ada-anvil.io
- **Blockfrost API**: https://blockfrost.io
- **CIP-20 Specification**: https://cips.cardano.org/cips/cip20/
- **CIP-1852 (HD Wallets)**: https://cips.cardano.org/cips/cip1852/
