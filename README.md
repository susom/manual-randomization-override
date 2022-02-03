# Manual Randomization Overide
This EM allows designated users to select the random outcome value for a record when REDCap's built-in allocation-based randomization is in use.  Essentially, you the user can bypass the normal randomization step and set the value to their choice.

## Use Cases
In many studies, use cases arise where REDCap is unavailable at the time of randomization.  The study owners must then use an offline randomization method, such as picking from a set of sealed envelopes or flipping a coin, in order to make a decision so the participant can be processed.  Once REDCap is later available, the study owner wants to set the record to the random output that was used.  This tool permits such a workflow.

When a record is manually randomized, it claims the next available value from the allocation table for the set output, just as it were done using the normal randomization, so your final allocation should still be as configured.

## How it Works
This module injects a new button into the normal randomization workflow (only for enabled users).  This button allows them to override the normal process and set the outcome.

Note: If all allocated values are already claimed the UI inputs will remain "disabled".  You should monitor your randomization allocation status during a study to ensure you have sufficient unused allocation slots.

Below is a quick visual overview of what the EM will add to the project:

![Fig 1](https://raw.githubusercontent.com/susom/manual-randomization/master/images/allowed_users.png)
Be sure to set usernames with override permissions in the EM config settings.

![Fig 2](https://raw.githubusercontent.com/susom/manual-randomization/master/images/red_button.png)
Fields chosen to be Randomized fields will have the default "Randomize" button with a new red button labeled "Manual Selection" next to them.

![Fig 3](https://raw.githubusercontent.com/susom/manual-randomization/master/images/inject_ui.png)
When the Manual Selection button is clicked , if pre-requisite fields are filled in, then the options will be enabled and a value can be chosen.

![Fig 4](https://raw.githubusercontent.com/susom/manual-randomization/master/images/red_note.png)
Once a value is set (along with reason for manual selection) the selections will be disabled with the reason displayed in red.

![Fig 5](https://raw.githubusercontent.com/susom/manual-randomization/master/images/log_link.png)

![Fig 6](https://raw.githubusercontent.com/susom/manual-randomization/master/images/manual_log_page.png)
A log of all manual randomization occurrences can be viewed through the link on the sidebar for this module.
