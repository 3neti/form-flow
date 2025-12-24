# Release Checklist

This checklist ensures the package is ready for publication to GitHub and Packagist.

## Pre-Release Steps

### 1. Repository Setup
- [ ] Create GitHub repository at https://github.com/3neti/form-flow
- [ ] Add repository description: "Driver-based form flow orchestration system with DirXML-style mapping for Laravel applications"
- [ ] Add topics: `laravel`, `form-flow`, `multi-step-form`, `wizard`, `inertia`, `vue`, `dirxml`, `mapper`

### 2. Local Preparation (COMPLETED ✓)
- [x] composer.json has complete metadata (keywords, homepage, author info)
- [x] README.md has proper badges and documentation
- [x] LICENSE file exists (MIT)
- [x] CHANGELOG.md documents v1.0.0 release
- [x] .gitignore is comprehensive
- [x] CONTRIBUTING.md added
- [x] SECURITY.md added
- [x] WARP.md added for Warp AI context
- [x] GitHub Actions CI workflow created (.github/workflows/tests.yml)

### 3. Commit and Push
```bash
# Review changes
git status
git diff

# Stage all files
git add .

# Commit with descriptive message
git commit -m "Prepare for v1.0.0 release

- Add comprehensive package metadata to composer.json
- Enhance README with badges and documentation
- Add CONTRIBUTING.md and SECURITY.md
- Add GitHub Actions CI workflow
- Improve .gitignore
- Add WARP.md for AI assistance
"

# Set remote (after creating GitHub repo)
git remote add origin https://github.com/3neti/form-flow.git

# Push to GitHub
git push -u origin main
```

### 4. Create Release Tag
```bash
# Create annotated tag
git tag -a v1.0.0 -m "Release version 1.0.0

Initial release with:
- Driver-based form flow orchestration
- DirXML-style mapping support
- Built-in handlers (Form, Splash, Missing)
- Vue components for Inertia.js
- Plugin architecture
- Laravel 11 & 12 support
"

# Push tag
git push origin v1.0.0
```

### 5. GitHub Release
- [ ] Go to https://github.com/3neti/form-flow/releases/new
- [ ] Select tag: v1.0.0
- [ ] Release title: "v1.0.0 - Initial Release"
- [ ] Copy content from CHANGELOG.md
- [ ] Publish release

### 6. Packagist Submission
- [ ] Go to https://packagist.org/packages/submit
- [ ] Submit repository URL: https://github.com/3neti/form-flow
- [ ] Enable auto-update hook (GitHub webhook)

### 7. Post-Release
- [ ] Verify package appears on Packagist
- [ ] Test installation: `composer require 3neti/form-flow`
- [ ] Update README if needed
- [ ] Announce release

## Notes

- **Minimum PHP Version**: 8.2
- **Laravel Versions**: 11.x, 12.x
- **License**: MIT
- **Namespace**: `LBHurtado\FormFlowManager`

## Testing Before Release

```bash
# Install dependencies
composer install

# Run tests
composer test

# Validate composer.json
composer validate --strict
```
