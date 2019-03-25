import Modals from "./Modals";
import Popup from "./Popup";
import ButtonLoading from "./ButtonLoading";

class ProductLists
{
    constructor()
    {
        this.uiForm             = $('.create_list_form');
        this.uiModal            = $('.list_modal');
        this.uiModalButton      = $('.btn_addto_list');
        this.uiFaveButton       = $('.btn_addto_fave');
    }

    watch()
    {
        // add modals
        Modals.add(this.uiModal, this.uiModalButton);

        // on submitting a new
        this.uiForm.on('submit', event => {
            event.preventDefault();

            const payload = {
                itemId: this.uiForm.find('#list_item_id').val().trim(),
                name:   this.uiForm.find('#list_name').val().trim(),
                option: this.uiForm.find('#list_option').val().trim(),
            };

            this.create(payload);
        });

        // on fave clicking
        this.uiFaveButton.on('click', event => {
            this.addToFavourite();
        });
    }

    addToFavourite()
    {
        ButtonLoading.start(this.uiFaveButton);

        return;

        // itemId
        // todo - do it

        // send request
        $.ajax({
            url: mog.url_lists_favourite,
            type: "POST",
            dataType: "json",
            data: JSON.stringify({
                itemId: itemId,
            }),
            contentType: "application/json",
            success: response => {
                response.state ? this.uiFaveButton.addClass('on') : this.uiFaveButton.removeClass('on');
                ButtonLoading.finish(this.uiFaveButton);
                this.uiFaveButton.find('span').text(response.state ? 'Faved' : 'Favourite');
                Modals.close(this.uiModal);
                Popup.success(
                    response.state ? 'Added to Faves' : 'Removed from Faves',
                    response.state ? 'Why you love this item so much!? Added to your favourites.' : 'Unpopular item ey, removed from your favourites.'
                );
            },
            error: (a,b,c) => {
                Popup.error('Error 37', 'Could not add to favourites!');
                console.log('--- ERROR ---');
                console.log(a,b,c)
            }
        });
    }

    create(payload)
    {
        const $btn = this.uiForm.find('button[type="submit"]');
        ButtonLoading.start($btn);

        // send request
        $.ajax({
            url: mog.url_create_alert,
            type: "POST",
            dataType: "json",
            data: JSON.stringify(payload),
            contentType: "application/json",
            success: response => {
                ButtonLoading.finish($btn);

                // if alert ok
                if (response.ok) {
                    // load current alerts
                    this.loadItemAlerts();

                    // close modals
                    Modals.close(this.uiModal);

                    // todo - reset form

                    // confirm
                    Popup.success('Alert Created','Information on this alert will appear on the homepage!');
                    return;
                }

                // error
                Popup.success('Error',response.message);
            },
            error: (a,b,c) => {
                Popup.error('Error 37', 'Could not create alert.');
                console.log('--- ERROR ---');
                console.log(a,b,c)
            }
        });
    }
}

export default new ProductLists;
