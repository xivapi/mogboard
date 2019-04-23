import xivapi from './XIVAPI';
import Popup from './Popup';
import ButtonLoading from './ButtonLoading';

class AccountCharacters
{
    constructor()
    {
        this.uiAddCharacterResponse = $('.character_add_response');
    }

    watch()
    {
        if (mog.path != 'account') {
            return;
        }

        this.handleNewCharacterSearch();
    }

    /**
     * Handles adding a new character
     */
    handleNewCharacterSearch()
    {
        const $button = $('.character_add');

        // add character clicked
        $button.on('click', event => {
            // grab entered info
            const character = {
                string: $('#character_string').val().trim(),
                server: $('#character_server').val().trim(),
            };

            // validate IDs
            let lodestoneId = null;
            if (character.string.indexOf('finalfantasyxiv.com') > -1) {
                character.string = character.string.split('/');
                character.string = character.string[5];
                lodestoneId = character.string;
            }

            if (character.string.indexOf(' ') == -1) {
                lodestoneId = character.string;
            }

            if (character.string.length == 0) {
                Popup.error('Nothing entered?', 'I think you forgot to type something...');
                return;
            }

            ButtonLoading.start($button);

            // if lodestone id, we good to go
            if (lodestoneId) {
                this.handleNewCharacterViaLodestoneId(lodestoneId);
                return;
            }

            // else search and find a lodestone id.
            this.uiAddCharacterResponse.html('Searching lodestone for your character...');
            xivapi.searchCharacter(character.string, character.server, response => {
                // not foundz
                if (response.Pagination.ResultsTotal === 0) {
                    Popup.error('Not Found (code 32)', 'Could not find your character on lodestone, try entering the Lodestone URL for your character.');
                    this.uiAddCharacterResponse.html('');
                    ButtonLoading.finish($button);
                    return;
                }

                // look through results
                let found = false;
                response.Results.forEach(row => {
                    if (row.Name == character.string) {
                        found = true;
                        this.handleNewCharacterViaLodestoneId(row.ID);
                    }
                });

                if (found === false) {
                    Popup.error('Not Found (code 8)', 'Could not find your character on lodestone, try entering the Lodestone URL for your character.');
                    ButtonLoading.finish($button);
                    this.uiAddCharacterResponse.html('');
                }
            });
        });
    }

    /**
     * Handle a character via their lodestone id
     */
    handleNewCharacterViaLodestoneId(lodestoneId, reCalled)
    {
        const $button = $('.character_add');
        this.uiAddCharacterResponse.html('Searching for your character...');

        xivapi.getCharacter(lodestoneId, response => {
            // if its a 1 and we've not recalled, try add
            if (response.Info.Character.State == 1 && reCalled !== true) {
                this.uiAddCharacterResponse.html('Character being added to XIVAPI, please wait ...');

                setTimeout(() => {
                    // try again
                    this.handleNewCharacterViaLodestoneId(lodestoneId, true);
                }, 3000);

                return;
            }

            // if character found, do a
            if (response.Info.Character.State == 2) {
                this.uiAddCharacterResponse.html('Character found, verifying auth code.');

                // try verify the profile
                xivapi.characterVerification(lodestoneId, verify_code, response => {
                    if (response.Pass === false) {
                        Popup.error('Auth Code Not Found', `Could not find your auth code (${verify_code}) on your characters profile, try again!`);
                        this.uiAddCharacterResponse.html('');
                        ButtonLoading.finish($button);
                        return;
                    }

                    this.uiAddCharacterResponse.html('Auth code found, adding character...');

                    $.ajax({
                        url: mog.urls.characters.add.replace('-id-', lodestoneId),
                        success: response => {
                            if (response === true) {
                                Popup.success('Character Added!', 'Your character has been added, the page will refresh in 3 seconds.');
                                Popup.setForcedOpen(true);
                                setTimeout(() => {
                                    location.reload();
                                }, 3000);

                                return;
                            }

                            Popup.error('Character failed to add', `Error: ${response.Message}`);
                        },
                        error: (a,b,c) => {
                            Popup.error('Something Broke (code 145)', 'Could not add your character, please hop on discord and complain to Vekien');
                            console.error(a,b,c);
                        },
                        complete: () => {
                            this.uiAddCharacterResponse.html('');
                            ButtonLoading.finish($button);
                        }
                    })
                });

                return;
            }

            Popup.error('Failed to Add Character', 'Could not add character, please hop on Discord and complain to Vekien!');
            this.uiAddCharacterResponse.html('');
            ButtonLoading.finish($button);
        })

    }
}

export default new AccountCharacters;
