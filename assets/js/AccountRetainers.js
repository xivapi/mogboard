import xivapi from './XIVAPI';
import Popup from './Popup';
import ButtonLoading from './ButtonLoading';

class AccountRetainers
{
    constructor()
    {
        this.uiAddRetainerResponse = $('.retainer_add_response');
        this.uiItemSearchResponse = $('.retainer_item_search_response');
    }

    watch()
    {
        if (mog.path != 'account') {
            return;
        }

        this.handleNewRetainerAdd();
        this.watchItemSearchInput();
        this.watchRetainerConfirmation();
    }

    watchRetainerConfirmation()
    {
        const $button = $('.retainer_confirm');

        $button.on('click', event => {
            ButtonLoading.start($button);
            const id = $(event.currentTarget).attr('data-id');

            $.ajax({
                url: mog.urls.retainers.confirm.replace('-id-', id),
                success: response => {
                    if (response == false) {
                        Popup.error('Not yet!', 'Could not find your retainer on the market board for the chosen item. <br><br> It may be that Companion has not yet synchronised with the game servers. <br><br> Try again in 15 minutes or contact Vekien on discord for help!');
                        return;
                    }

                    Popup.success('Retainer Confirmed!', 'You are all good to go, the retainer is yours! <br> The site will refresh in 3 seconds.');
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                },
                error: (a,b,c) => {
                    console.error(a,b,c);
                },
                complete: () => {
                    ButtonLoading.finish($button);
                    ButtonLoading.disable($button);
                }
            })
        });
    }

    /**
     * Watch item search input
     */
    watchItemSearchInput()
    {
        const $input = $('.retainer_item_search');
        let timeout = null;
        let searched = null;

        $input.on('keyup', event => {
            const string = $input.val().trim();
            clearTimeout(timeout);

            if (searched === string) {
                return;
            }

            searched = string;
            this.uiItemSearchResponse.html('<div align="center"><img src="/i/svg/loading3.svg" height="32"></div>');

            timeout = setTimeout(() => {
                if (string.length > 1) {
                    xivapi.searchLimited(string, response => {
                        if (response.Pagination.Results == 0) {
                            this.uiItemSearchResponse.html('<p>Could not find an item</p>');
                            return;
                        }

                        this.uiItemSearchResponse.html('');
                        response.Results.forEach(item => {
                            this.uiItemSearchResponse.append(
                                `<button class="item_button" data-id="${item.ID}">${item.Name}</button>`
                            );
                        });
                    })
                }
            }, 500);
        });

        this.uiItemSearchResponse.on('click', 'button', event => {
            const itemId = $(event.currentTarget).attr('data-id');
            const server = $('#retainer_server').val().trim();
            this.uiItemSearchResponse.html('<p>Checking market prices to ensure this is a safe item to verify with.</p>');

            xivapi.getMarketPrices(itemId, server, response => {
                this.uiItemSearchResponse.html('');

                if (typeof response.Error !== 'undefined') {
                    Popup.error('Error', 'There was an error returning market information, this is a problem with XIVAPI. This is being looked into.');
                    return;
                }

                const market = response[server];

                if (market.Prices.length >= 50) {
                    Popup.warning('High Sale', 'There are over 50 of these items for sale. Companion API can only provide a maximum of 50 sales, please choose an item with a lower sale stock count.');
                    return;
                }

                Popup.success('Good!', `You will be asked to place 1x ${market.Item.Name} up for sale at an exact price once you add your retainer.`);
                $('.retainer_item_search').val(market.Item.Name);
                $('.retainer_add').prop('disabled', false);
                $('#retainer_item').val(itemId);
            });
        })
    }

    /**
     * Handles adding a new retainer
     */
    handleNewRetainerAdd()
    {
        const $button = $('.retainer_add');

        // add retainer clicked
        $button.on('click', event => {
            // grab entered info
            const retainer = {
                name: $('#retainer_string').val().trim(),
                server: $('#retainer_server').val().trim(),
                itemId: $('#retainer_item').val().trim(),
            };

            if (retainer.name.length == 0) {
                Popup.error('Nothing entered?', 'I think you forgot to type something...');
                return;
            }

            ButtonLoading.start($button);

            $.ajax({
                url: mog.urls.retainers.add,
                data: retainer,
                success: response => {
                    if (response === true) {
                        Popup.success('Retainer Added!', 'Your retainer has been added, the page will refresh in 3 seconds.');
                        setTimeout(() => {
                            location.reload();
                        }, 3000);

                        return;
                    }

                    Popup.error('Retainer failed to add', `Error: ${response.Message}`);
                },
                error: (a,b,c) => {
                    Popup.error('Something Broke (code 148)', 'Could not add your retainer, please hop on discord and complain to Vekien');
                    console.error(a,b,c);
                },
                complete: () => {
                    this.uiAddRetainerResponse.html('');
                    ButtonLoading.finish($button);
                }
            })
        });
    }
}

export default new AccountRetainers;
