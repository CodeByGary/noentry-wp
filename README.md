# NoEntryWP: Admin Page Access Control

**Restrict access to specific WordPress admin pages for selected users. Fully customizable per-user access rules based on URL matching.**

![WordPress Version](https://img.shields.io/badge/WordPress-5.3+-blue.svg)
![Tested up to](https://img.shields.io/badge/Tested%20up%20to-6.8-brightgreen.svg)
![PHP](https://img.shields.io/badge/PHP-7.0%2B-orange.svg)
![License](https://img.shields.io/badge/License-GPLv2--or--later-blue.svg)

---

## Description

**NoEntryWP** lets you control which admin pages individual users can access in WordPress.

Create rules by user ID, and block access based on URL patterns:

- `Contains`
- `Equals`
- `Starts with`
- `Regular expression`

### 🔑 Features

- ✅ Lightweight and secure
- ✅ Clean and responsive settings page
- ✅ jQuery UI accordion interface for organizing user rules
- ✅ Localization-ready

---

## 📦 Installation

1. Upload the plugin files to the `/wp-content/plugins/noentry-wp` directory, or install via the WordPress Plugin Directory.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings > Admin Access Control** to configure user-specific rules.

---

## 🧭 Instructions

To restrict access to specific WordPress admin pages for selected users, follow these steps:

1. Find the user in the list. Each accordion section represents a user account.
2. For each user, add one or more **URL match rules** by selecting a match type and entering the page path or pattern.
3. You can add multiple rules per user. A user will be blocked if any rule matches the current admin page URL.
4. Click the **“+”** button to add a new rule, or the **“–”** button to remove a rule.
5. Changes are saved automatically when you click the **“Save Changes”** button.

**Match types available:**

- **Contains** – Blocks if the URL contains the given string
- **Equals** – Blocks if the URL exactly matches the string
- **Starts with** – Blocks if the URL starts with the given string
- **Regular expression** – Blocks if the URL matches the regex pattern

---

## 💖 Support Development

If you find this plugin helpful, consider supporting its development:

[![Buy Me A Coffee](https://cdn.buymeacoffee.com/buttons/v2/default-blue.png)](https://www.buymeacoffee.com/codebygary)

---

## 📄 License

This plugin is licensed under the GPLv2 or later.  
See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

---

**Made with ❤️**
