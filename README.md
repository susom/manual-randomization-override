# Randomizer Overide
This EM will allow for manual overide of "randomizer" fields.  Namely the fields where values are drawn from a pregenerated allocation table.

This EM will inject appropriate UI into the "add/edit" workflow to allow the user to manually select the desired value, and subsequently "claim" the allocated value in the allocation table.

If all allocated values are already claimed the UI inputs will remain "disabled"

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
A log of Randomizations overridden can be viewed with this EM link

![Fig 6](https://raw.githubusercontent.com/susom/manual-randomization/master/images/manual_log_page.png)

