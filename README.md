# RandomizerOveride
This EM will allow for manual overide of "randomizer" fields.  Namely the fields where values are drawn from a pregenerated allocation table.

Currently REDCAP has no mechanism to bypass the randomizer functionality.

This EM will inject appropriate UI into the "add/edit" flow to allow the user to manually select the desired value, and subsequently "claim" the allocated value in the allocation table.  

If all allocated values are already claimed the UI inputs will remain "disabled"

