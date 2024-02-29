# Options Changer WordPress Plugin

Options Changer is a simple WordPress plugin designed to facilitate the fetching and saving of options within the WordPress database. This plugin provides a user-friendly interface to interact with WordPress options, making it easy to manage and modify various settings.

## Features

- **Fetch Option:** Retrieve the current value of a specified option from the database.
- **Save Option:** Update or store a new value for a particular option in the WordPress options table.
- **Option Type Detection:** Automatically detects the type of the option value (String, Serialized, JSON) for better handling.

## Installation

1. Download the ZIP file of the plugin from the [Releases](https://github.com/yourusername/options-changer/releases) page.
2. Upload the ZIP file to your WordPress site.
3. Activate the plugin via the WordPress admin dashboard.

## Usage

1. Navigate to the "Options Changer" page under the Tools menu in the WordPress admin.
2. Select the desired option from the dropdown list.
3. Change the select value in the dropdown list or Click the "Fetch" button to retrieve and display the current option value.
4. Modify the option value in the textarea.
5. Click the "Save" button to update the option value in the database.

## Requirements

- WordPress version 5.0 or higher.

## Screenshots
![image](https://github.com/erikyo/wp-options-changer/assets/8550908/8d79c565-778e-4bd4-bee4-492ef9d8b356)

### Custom Styles

The plugin includes a custom admin style for a better user experience. You can modify the styles by editing the `style.css` file included in the plugin directory.

### Helper Functions

The plugin includes the following helper functions:

- `isJson($string)`: Checks if a given string is a valid JSON.
- `get_real_option_value($option)`: Retrieves the real value of a specified option from the database.

## Contributions

Contributions are welcome! If you encounter any issues or have suggestions for improvements, please open an issue or submit a pull request.
