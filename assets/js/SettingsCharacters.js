import xivapi from './XIVAPI';
import Popup from './Popup';
import ButtonLoading from './ButtonLoading';

class SettingsCharacters
{
    constructor()
    {
        this.addCharacterBtn = $('.account-add-character button');
        this.confirmCharacterBtn = $('.account-check-verification');
    }

    watch()
    {
        this.addCharacterBtn.on('click', event => {
            let string = $('.account-add-character input[name="string"]').val().trim();
            let server = $('.account-add-character select[name="server"]').val().trim();

            this.addCharacter(string, server);
        });

        this.confirmCharacterBtn.on('click', event => {
            let lodestoneId = $(event.currentTarget).attr('data-id');
            ButtonLoading.start(this.confirmCharacterBtn);

            $.ajax({
                url: mog.urls.characters.confirm.replace('-id-', lodestoneId),
                success: ok => {
                    location.reload();
                    // Popup.success('Character Confirmed', 'lorem ipsum');
                },
                error: (a,b,c) => {
                    Popup.error('Could not confirm', a.responseJSON.Message);
                },
                complete: () => {
                    ButtonLoading.finish(this.confirmCharacterBtn);
                }
            });

            console.log(lodestoneId);
        })
    }

    /**
     * Add a character
     */
    addCharacter(string, server)
    {
        let lodestoneId = null;

        if ($.isNumeric(string)) {
            lodestoneId = string;
        } else if (string.indexOf('finalfantasyxiv.com') > -1) {
            lodestoneId = string.split('/')[5];
        }

        if (lodestoneId) {
            ButtonLoading.start(this.addCharacterBtn);

            xivapi.getCharacter(lodestoneId, response => {
                if (response.Info.Character.State !== 2) {
                    // Character not yet added on XIVAPI
                    Popup.info('Not yet ready', 'This character has been put in queue to XIVAPI and will be added in a moment, please try again in a minute.');
                    return;
                }

                // todo - add loading/notification to user from here

                console.log(`Adding: ${response.Character.Name}`);

                $.ajax({
                    url: mog.urls.characters.add,
                    data: {
                        lodestone_id: lodestoneId,
                    },
                    success: ok => {
                        location.reload();
                        // Popup.success('Character Added!', 'Your character has been added, information will sync shortly from XIVAPI.');
                    },
                    error: (a,b,c) => {
                        Popup.error('Could not add', a.responseJSON.Message);
                    },
                    complete: () => {
                        ButtonLoading.finish(this.addCharacterBtn);
                    }
                });
            });
        } else {
            xivapi.searchCharacter(string, server, response => {
                if (response.Results.length === 0) {
                    // todo - improve this error
                    alert('no results');
                    return;
                }

                // try find a name match
                let foundId = null;
                for(let i in response.Results) {
                    let row = response.Results[i];

                    if (row.Name.toLowerCase() === string.toLowerCase()) {
                        foundId = row.ID;
                        break;
                    }
                }

                // still not found
                if (foundId === null) {
                    Popup.error('Could not find', 'Could not find your character on lodestone, try using the Lodestone URL instead.');
                    return;
                }

                console.log(foundId);

                // recall this with the found ID
                this.addCharacter(foundId);
            });
        }
    }
}

export default new SettingsCharacters;
