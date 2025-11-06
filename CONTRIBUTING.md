# Contributing to Umbrella Blog

First off, thank you for considering contributing to Umbrella Blog! It's people like you that make this plugin better for everyone.

## Code of Conduct

Be respectful, be kind, and assume good intentions. We're all here to build something cool together.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When you create a bug report, include as many details as possible:

**Bug Report Template:**

```
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '...'
3. See error

**Expected behavior**
What you expected to happen.

**Screenshots**
If applicable, add screenshots.

**Environment:**
 - WordPress Version: [e.g. 6.4]
 - PHP Version: [e.g. 8.1]
 - Plugin Version: [e.g. 1.1.2]
 - Network: [Mainnet/Preprod]

**Additional context**
Add any other context about the problem here.
```

### Suggesting Enhancements

Enhancement suggestions are welcome! Please provide:

- **Clear description** of the feature
- **Use case** - why would this be useful?
- **Examples** - how would it work?
- **Mockups** - if applicable

### Pull Requests

1. **Fork the repo** and create your branch from `main`
2. **Name your branch** descriptively: `feature/add-nft-minting` or `fix/handle-image-bug`
3. **Write clear commit messages** that explain what and why
4. **Test your changes** thoroughly:
   - Test on WordPress 5.0+
   - Test with both Markdown and Rich Text editors
   - Test Cardano signing on Preprod network
   - Check for PHP errors with `WP_DEBUG` enabled
5. **Update documentation** if you change functionality
6. **Submit your PR** with a clear description of changes

## Development Setup

### Requirements

- Local WordPress installation (we use Local by Flywheel)
- PHP 7.4+
- MySQL 5.7+
- Cardano Preprod API keys for testing:
  - Blockfrost Preprod key
  - Ada Anvil Preprod key

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/umbrella-blog.git

# Copy to WordPress plugins directory
cp -r umbrella-blog /path/to/wordpress/wp-content/plugins/

# Activate the plugin in WordPress admin
```

### Testing Cardano Features

1. Set up Preprod network in plugin settings
2. Generate a test wallet
3. Get free test ADA from [Cardano Testnet Faucet](https://docs.cardano.org/cardano-testnet/tools/faucet/)
4. Test signing functionality
5. Verify transactions on [Preprod CardanoScan](https://preprod.cardanoscan.io/)

## Coding Standards

### PHP

- Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use WordPress core functions when available
- Sanitize all user input with `sanitize_text_field()`, `sanitize_textarea_field()`, etc.
- Escape all output with `esc_html()`, `esc_url()`, `esc_attr()`, etc.
- Use WordPress database class `$wpdb` with prepared statements
- Add WordPress capability checks to all admin actions

### Security

**Critical Security Rules:**

- NEVER hardcode API keys, mnemonics, or private keys
- ALWAYS validate and sanitize user input
- ALWAYS escape output to prevent XSS
- ALWAYS use nonces for form submissions
- ALWAYS use prepared statements for database queries
- NEVER expose wallet private keys in API responses
- ALWAYS check user capabilities before admin actions

### File Organization

```
umbrella-blog/
├── admin/           # WordPress admin pages and UI
├── includes/        # Core logic and classes
│   └── vendor/      # Third-party libraries
├── templates/       # Frontend display templates
├── migrations/      # Database setup scripts
└── Documentation/   # User guides and technical docs
```

### Naming Conventions

- **Classes**: `PascalCase` (e.g., `WalletController`)
- **Functions**: `snake_case` (e.g., `generate_wallet()`)
- **Database tables**: `wp_umbrella_blog_*` prefix
- **WordPress hooks**: `umbrella_blog_*` prefix

## What We're Looking For

### High Priority

- Bug fixes (especially security-related)
- Performance improvements
- Better error handling
- Improved user experience
- Documentation improvements
- Test coverage

### Medium Priority

- New editor features (tables, footnotes, etc.)
- Additional color themes
- Mobile UI improvements
- Internationalization (i18n)

### Future Features

- Multi-signature workflows
- NFT minting integration
- IPFS archival
- Additional blockchain features

## Questions?

Not sure where to start? Open an issue and ask! We're happy to help new contributors get oriented.

## Recognition

Contributors will be acknowledged in the README and release notes. Significant contributions may lead to maintainer status.

---

**Thank you for helping make Umbrella Blog better!**
