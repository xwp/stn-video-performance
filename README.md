# STN Video Performance

A lightweight WordPress plugin that extends the sendtonews plugin to provide performance optimizations for STN Videos.

## Features

- **Hide Featured Video Player Meta Box**: Provides an option to hide the Featured Video Player meta box from post and page edit screens, improving editor performance by preventing unnecessary script loading.
- **Video Player Load Delay**: Delays loading of STN Video player scripts to prioritize critical page resources and improve initial page load performance.
- **CLS Prevention**: Applies inline CSS to video player containers to prevent layout shift during video load, using aspect-ratio preservation and placeholder backgrounds.
- **Video Thumbnail Placeholders**: Automatically displays video thumbnails as background images while the player loads, providing visual continuity and improved user experience (requires STN Video Meta plugin), which also doubles as LCP optimization.

## Installation

### Using Composer

To install the plugin via Composer, follow these steps:

1. **Add the Repository:**
   - Open your project's `composer.json` file.
   - Add the following under the `repositories` section:

     ```json
     "repositories": [
         {
             "type": "vcs",
             "url": "https://github.com/xwp/ga4-extensions"
         }
     ]
     ```

2. **Require the Plugin:**
   - Run the following command in your terminal:

     ```bash
     composer require xwp/ga4-extensions
     ```

3. **Activate the Plugin:**
   - Once installed, activate the plugin through the 'Plugins' menu in WordPress.

4. **Configure the Plugin**
  - Navigate to Settings > STN Video to configure the plugin

### Manual Installation

1. **Download the Plugin:**
  - Download the `stn-video-performance` plugin folder.

2. **Upload the Plugin:**
  - Add the `stn-video-performance` folder to the `/wp-content/plugins/` directory of your WordPress installation.

3. **Activate the Plugin:**
  - Activate the plugin through the 'Plugins' menu in WordPress.

4. **Configure the Plugin**
  - Navigate to Settings > STN Video to configure the plugin

## Usage

All settings are configured on the **Settings > STN Video** page in your WordPress admin, in the **Performance Options** section (appears above the STN Video settings).

### Hiding the Featured Video Player Meta Box

1. Go to **Settings > STN Video** in your WordPress admin, at the **Performance Options** section
2. Check the box labeled "Hide Featured Video Player meta box from post and page edit screens"
3. Click "Save Performance Settings"

When enabled:

- The Featured Video Player meta box will not appear on post/page edit screens
- Editor performance will be improved by not loading unnecessary scripts
- Existing video metadata will be preserved and videos will continue to display on the frontend
- The setting can be reversed at any time by unchecking the box

### Delaying Video Player Script Loading

1. Go to **Settings > STN Video** in your WordPress admin, at the **Performance Options** section
2. Set the "Video Player Load Delay" value in milliseconds (recommended: 1000-3000ms)
3. Click "Save Performance Settings"

When configured:

- Video player scripts will load after the `window.load` event plus the configured delay
- Critical page resources will load first, improving perceived page speed
- The STN Video player container (`<div>`) remains in place with CLS prevention styling
- Video thumbnails are displayed as placeholder backgrounds during load (when available)
- Set to `0` to disable delayed loading and load scripts immediately
- The delay applies only to frontend pages where the `[sendtonews]` shortcode is used

## Technical Details

This plugin integrates seamlessly with the STN Video plugin:

### Settings Integration

- Uses the `sendtonews_settings_enqueue` action to render a separate Performance Options form on the STN Video settings page
- Has its own form submission handler with proper nonce verification and capability checks
- All settings are stored separately in the `stn_video_performance_settings` WordPress option (not autoloaded for performance)

### Meta Box Removal

- Uses `remove_meta_box()` with priority 999 on the `add_meta_boxes` hook to remove the meta box after STN Video registers it
- Leverages the `stnvideo_featured_video_screens` filter to dynamically determine which post types to remove the meta box from

### Delayed Script Loading

- Hooks into `do_shortcode_tag` with the `sendtonews` shortcode for efficient, targeted processing
- Only processes pages where the `[sendtonews]` shortcode is present (not all content)
- Extracts the script URL from the shortcode output using regex pattern matching
- Enforces HTTPS on protocol-relative URLs and validates all script URLs with `esc_url_raw()`
- Replaces the original `<script>` tag with an inline delayed loader while preserving the player container `<div>`
- Uses `window.load` event plus configured delay before injecting the script into the DOM
- Skips processing in admin contexts

### CLS Prevention & Video Thumbnails

- Injects inline CSS to apply `aspect-ratio: 16/9` to all `.s2nPlayer` containers
- Adds placeholder background color (`rgba(0, 0, 0, 0.05)`) to prevent layout shift
- Integrates with STN Video Meta plugin to retrieve video thumbnails from post metadata
- Reads `stnvm_status`, `stnvm_keys`, and schema data from `hvy_video_schema_data` post meta
- Dynamically generates CSS custom properties to apply video-specific thumbnail backgrounds
- Uses `.s2nPlayer.k-{video_key}` selector to target specific video players with their thumbnails
- Thumbnail URLs are validated and normalized to HTTPS
- Gracefully degrades when thumbnails are not available (shows only placeholder background)

## Requirements

- WordPress 6.8 or higher
- STN Video plugin must be installed and activated
- PHP 8.2 or higher
- STN Video Meta plugin (optional, required for video thumbnail placeholders)

## Changelog

### 1.0.0

- Initial release
- Added option to hide Featured Video Player meta box
- Added configurable delay for video player script loading to improve page load performance
- Added CLS prevention with aspect-ratio preservation and placeholder backgrounds
- Added video thumbnail placeholder integration with STN Video Meta plugin
