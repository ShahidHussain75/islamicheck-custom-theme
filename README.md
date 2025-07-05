# IslamiCheck Theme Documentation

This is a child theme for the Astra WordPress theme, specifically customized for IslamiCheck. The theme includes various custom functionalities for vendor management, registration, and WooCommerce integration for Dokan.

## Theme Structure

```
astra-child-islamicheck/
├── functions.php          # Main theme functions
├── style.css             # Theme styling
├── screenshot.jpg        # Theme screenshot
├── dokan/               # Dokan template overrides
│   ├── store.php
│   └── global/
└── inc/                 # Custom functionality
    ├── improved-vendor-profile-editor.php
    ├── improved-vendor-registration.php
    ├── shortcode-to-display-cart.php
    └── vendor-product-acf-add.php
```

## Core Functionality

### functions.php
The main theme file that includes core functionality:

1. **Theme Setup** (Lines 1-28)
   - Defines theme constants
   - Enqueues child theme styles
   - Fixes SVG metadata handling

2. **Custom Registration Fields** (Lines 29-90)
   - Adds custom name fields to WooCommerce registration
   - Implements first name, middle name, and last name fields
   - Adds terms acceptance checkbox
   - Adds promotional emails opt-in

3. **Form Customizations** (Lines 91-120)
   - Customizes form placeholder text
   - Removes password visibility toggle button

4. **Product Management** (Lines 121-160)
   - Sets default virtual product type for vendors
   - Customizes vendor dashboard interface
   - Hides specific product options in vendor dashboard

5. **Vendor Rating System** (Lines 161-200)
   - Implements vendor rating summary functionality
   - Calculates average ratings for vendor products

### inc/improved-vendor-registration.php
Advanced vendor registration system with the following features:

1. **Custom Registration Form** [Shortcode: `[custom_vendor_form]`]
   - Comprehensive business information collection
   - 18 custom fields including:
     - Business details
     - Contact information
     - Document uploads
     - Profile and banner images

2. **Field Structure**
   - Business Information
     - Company name
     - Registration number
     - Tax information
     - Business type selection
   - Contact Details
     - Phone number
     - Support contact
     - Website URL
   - Location Information
     - Business address
     - City
     - Country selection
   - Document Verification
     - ID document upload
     - Business registration proof
     - Profile and banner images

### inc/improved-vendor-profile-editor.php
Handles the vendor profile editing functionality in the Dokan dashboard.

### inc/shortcode-to-display-cart.php
Provides custom cart display functionality through shortcodes.

### inc/vendor-product-acf-add.php
Integrates Advanced Custom Fields (ACF) with vendor products.

### dokan/store.php
Custom template for vendor store pages.

## Usage

1. The vendor registration form can be displayed using the shortcode `[custom_vendor_form]`
2. Vendor dashboard is automatically customized with the improved interface
3. All custom fields are accessible in the Dokan dashboard under "Business Details"

## Requirements

- WordPress 5.0+
- Astra Theme (Parent)
- WooCommerce
- Dokan Pro
- Advanced Custom Fields Pro

## Notes

- The theme automatically sets products as virtual for vendors
- Password visibility toggle is removed from forms
- Custom placeholder text is implemented for all form fields
- Vendor ratings are automatically calculated and displayed
