# REDCap External Module: Randomizer Override

## Overview

The Randomizer Override External Module for REDCap provides an intuitive solution for overriding the values of "randomizer" fields in your project. These are fields for which values are pre-allocated in an allocation table. The module enriches the "Add/Edit" workflow by enabling the user to manually select and "claim" values from the allocation table.

## Features

- **Manual Override**: Enables users to manually override automatically generated values in "randomizer" fields.
- **UI Enhancement**: Adds custom input fields in the "Add/Edit" workflow for selecting values from the allocation table.
- **Value Claiming**: Allows the user to "claim" a value, marking it as used in the allocation table.
- **Automatic Disablement**: Input fields will automatically be disabled if all values in the allocation table have been claimed.

## Visual Overview

### Setting Permissions
![Fig 1: EM Config Settings](https://raw.githubusercontent.com/susom/manual-randomization/master/images/allowed_users.png)

Be sure to set usernames with override permissions in the EM config settings.

### User Interface Enhancement
![Fig 2: Manual Selection Button](https://raw.githubusercontent.com/susom/manual-randomization/master/images/red_button.png)

Fields designated as Randomized will display the default "Randomize" button along with a new red button labeled "Manual Selection."

### Manual Value Selection
![Fig 3: UI Injection](https://raw.githubusercontent.com/susom/manual-randomization/master/images/inject_ui.png)

Clicking the "Manual Selection" button will enable the value options, provided that all prerequisite fields have been filled.

### Value Confirmation
![Fig 4: Confirmation Note](https://raw.githubusercontent.com/susom/manual-randomization/master/images/red_note.png)

Upon setting a value and providing a reason for manual selection, the input options will become disabled. The reason will be displayed in red text.

### Overridden Randomizations Log
![Fig 5: Log Link](https://raw.githubusercontent.com/susom/manual-randomization/master/images/log_link.png)
![Fig 6: Manual Log Page](https://raw.githubusercontent.com/susom/manual-randomization/master/images/manual_log_page.png)

A log of overridden randomizations can be accessed via the EM link.

## Installation & Configuration

1. **Install Module**: Install the Randomizer Override EM at the project level.
2. **Set Permissions**: In EM config, add comma-separated usernames allowed to use the manual override.
3. **Enable Module**: In the project setup, enable a Randomization module.
4. **Configure Randomization**: Access 'Set Up a Randomization' to specify options and the designated field.
5. **Upload Table**: Upload your allocation table.
6. **User Interface**: The Manual Randomization UI will now appear in the designated field for whitelisted usernames.
 
## Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for more details.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## Support

If you encounter any issues or require further assistance, please file an issue on the GitHub repository.


