import Popup from './Popup';
import ButtonLoading from './ButtonLoading';

class AccountPatreon
{
    watch()
    {
        if (mog.path != 'account') {
            return;
        }

        this.handlePatreonStatusCheck();
    }

    /**
     * Handles the button for checking patreon status on the account page.
     */
    handlePatreonStatusCheck()
    {
        const $button = $('.check_patreon_status');

        $button.on('click', event => {
            ButtonLoading.start($button);

            $.ajax({
                url: mog.urls.account.check_patreon,
                success: response => {
                    if (response.ok) {
                        Popup.success('Patreon Confirmed!', 'Your support is much appreciated. Refresh the site to see the changes :) - Thank you');
                        return;
                    }

                    Popup.error('There was a problem (code: 22)', 'Could not detect Patreon status, please make sure you are in the XIVAPI Admin and have accepted your Discord Reward on Patreon, if you have problems, message Vekien on Discord.');
                },
                error: (a,b,c) => {
                    Popup.error('There was a problem (code: 47)', 'Please jump on discord and message Vekien with the error code to sort this out! Thank you');
                    console.error(a,b,c);
                },
                complete: () => {
                    ButtonLoading.finish($button);
                }
            })
        });
    }
}

export default new AccountPatreon;
